<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\TheftReportRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\TheftService;

/**
 * Handles bike theft reporting, the public stolen-bikes list, and theft resolution.
 */
class TheftController
{
    private BikeRepository $bikeRepository;
    private TheftReportRepository $theftReportRepository;
    private TheftService $theftService;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        BikeRepository $bikeRepository,
        TheftReportRepository $theftReportRepository,
        TheftService $theftService,
        AuthService $authService,
        Session $session
    ) {
        $this->bikeRepository = $bikeRepository;
        $this->theftReportRepository = $theftReportRepository;
        $this->theftService = $theftService;
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Show theft report form.
     * GET /theft/report/{bikeId}
     */
    public function reportForm(Request $request): Response
    {
        $bikeId = (int) $request->param('bikeId');
        $bike = $this->bikeRepository->findById($bikeId, withPhotos: true);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Krádež může nahlásit pouze majitel kola.', 403);
        }

        if ($bike->isStolen()) {
            $this->session->flash('error', 'Toto kolo je již nahlášeno jako odcizené.');

            return redirect('/bike/' . $bike->getQrHash());
        }

        return view('theft/report', [
            'title' => 'Nahlásit krádež – BikeSwap',
            'bike' => $bike,
            'currentUser' => $currentUser,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Process theft report submission.
     * POST /theft/report/{bikeId}
     */
    public function report(Request $request): Response
    {
        $bikeId = (int) $request->param('bikeId');
        $bike = $this->bikeRepository->findById($bikeId);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        // Validate
        $validator = new Validator($request->all());
        $validator->required('theft_location_text', 'Místo krádeže je povinné.');

        if ($request->input('address_validated', '0') !== '1') {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', 'Vyberte adresu z nabídky nebo použijte tlačítko "Zjistit moji polohu".');

            return redirect("/theft/report/{$bikeId}");
        }

        if ($validator->fails()) {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', $validator->allErrors()[0]);

            return redirect("/theft/report/{$bikeId}");
        }

        // Validate: theft date cannot be before bike manufacture year
        $bikeYear  = $bike->getYearOfManufacture();
        $theftDate = $request->input('theft_date', '');
        if ($bikeYear && $theftDate && (int) date('Y', strtotime($theftDate)) < $bikeYear) {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', "Datum krádeže nemůže být před rokem výroby kola ({$bikeYear}).");

            return redirect("/theft/report/{$bikeId}");
        }

        // Report the theft
        try {
            $reportId = $this->theftService->reportTheft($bike, $currentUser->getId(), [
                'theft_date'          => $request->input('theft_date') ?: null,
                'theft_location_text' => $request->input('theft_location_text'),
                'theft_location_lat'  => $request->input('theft_location_lat') ?: null,
                'theft_location_lng'  => $request->input('theft_location_lng') ?: null,
                'description'         => $request->input('description'),
                'police_case_number'  => $request->input('police_case_number'),
                'reporter_ip'         => $request->ip(),
            ]);
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());

            return redirect("/theft/report/{$bikeId}");
        }

        if ($request->wantsJson()) {
            return json(['report_id' => $reportId], 201);
        }

        $this->session->flash('success', 'Krádež byla nahlášena. Vaše kolo je nyní v databázi odcizených kol.');

        return redirect('/bike/' . $bike->getQrHash());
    }

    /**
     * Public list of stolen bikes (searchable by anyone).
     * GET /stolen
     */
    public function publicList(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if ($currentUser && $currentUser->isPolice()) {
            return redirect('/admin/bikes?status=stolen');
        }

        $search      = trim($request->query('search', '')) ?: null;
        $color       = trim($request->query('color', '')) ?: null;
        $yearFrom    = ($y = (int) $request->query('year_from', 0)) > 0 ? $y : null;
        $yearTo      = ($y = (int) $request->query('year_to', 0)) > 0 ? $y : null;
        $frameNumber = trim($request->query('frame_number', '')) ?: null;
        $qrHash      = trim($request->query('qr_hash', '')) ?: null;

        $stolenBikes = $this->bikeRepository->findStolen(
            withPhotos: true,
            search: $search,
            color: $color,
            yearFrom: $yearFrom,
            yearTo: $yearTo,
            frameNumber: $frameNumber,
            qrHash: $qrHash
        );
        $yearRange = $this->bikeRepository->getStolenBikeYearRange();

        if ($request->wantsJson()) {
            return json(['bikes' => array_map(fn($b) => [
                'id' => $b->getId(),
                'brand' => $b->getBrand(),
                'model' => $b->getModel(),
                'color' => $b->getColor(),
                'qr_hash' => $b->getQrHash(),
            ], $stolenBikes)]);
        }

        $filters = [
            'search'       => $search ?? '',
            'color'        => $color ?? '',
            'year_from'    => $yearFrom ?? '',
            'year_to'      => $yearTo ?? '',
            'frame_number' => $frameNumber ?? '',
            'qr_hash'      => $qrHash ?? '',
        ];
        $hasFilters = !empty(array_filter($filters, fn($v) => $v !== ''));

        $currentUser = $this->authService->currentUser();
        $layout = $currentUser ? 'layouts/app' : 'layouts/public';

        return view('theft/stolen-list', [
            'title'       => 'Odcizená kola – BikeSwap',
            'bikes'       => $stolenBikes,
            'filters'     => $filters,
            'hasFilters'  => $hasFilters,
            'yearMin'     => $yearRange['min'],
            'yearMax'     => $yearRange['max'],
            'currentUser' => $currentUser,
            'session'     => $this->session,
        ])->withLayout($layout);
    }

    /**
     * Resolve a theft report (owner found their bike).
     * POST /theft/{reportId}/resolve
     */
    public function resolve(Request $request): Response
    {
        $reportId = (int) $request->param('reportId');
        $report = $this->theftReportRepository->findById($reportId);
        $currentUser = $this->authService->currentUser();

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        $bike = $this->bikeRepository->findById($report->getBikeId());

        if ($bike === null || (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin())) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        try {
            $this->theftService->resolveTheft($reportId, $bike->getId());
            $this->session->flash('success', 'Kolo bylo označeno jako nalezené. Děkujeme!');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect('/bike/' . $bike->getQrHash());
    }
}