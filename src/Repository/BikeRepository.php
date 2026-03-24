<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\Bike;
use App\Entity\BikePhoto;

/**
 * Repository for the bikes and bike_photos tables.
 *
 * Handles all database queries related to bikes and their photos.
 * Returns Bike entities and BikePhoto entities, never raw arrays.
 */
class BikeRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // ── Bikes ──────────────────────────────────────────────────

    /**
     * Find a bike by ID, optionally with photos loaded.
     */
    public function findById(int $id, bool $withPhotos = false): ?Bike
    {
        $row = $this->db->fetchOne("SELECT * FROM bikes WHERE id = ?", [$id]);

        if ($row === null) {
            return null;
        }

        $bike = Bike::fromRow($row);

        if ($withPhotos) {
            $bike->setPhotos($this->findPhotosByBikeId($id));
        }

        return $bike;
    }

    /**
     * Find a bike by its QR hash (used when scanning QR code).
     */
    public function findByQrHash(string $qrHash, bool $withPhotos = false): ?Bike
    {
        $row = $this->db->fetchOne("SELECT * FROM bikes WHERE qr_hash = ?", [$qrHash]);

        if ($row === null) {
            return null;
        }

        $bike = Bike::fromRow($row);

        if ($withPhotos) {
            $bike->setPhotos($this->findPhotosByBikeId($bike->getId()));
        }

        return $bike;
    }

    /**
     * Find a bike by its frame/serial number (exact match).
     */
    public function findByFrameNumber(string $frameNumber): ?Bike
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM bikes WHERE frame_number = ?",
            [$frameNumber]
        );

        return $row ? Bike::fromRow($row) : null;
    }

    /**
     * Get all bikes owned by a user.
     *
     * @return Bike[]
     */
    public function findByOwner(int $ownerId, bool $withPhotos = false): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM bikes WHERE owner_id = ? ORDER BY created_at DESC",
            [$ownerId]
        );

        $bikes = array_map(fn(array $row) => Bike::fromRow($row), $rows);

        if ($withPhotos) {
            $this->loadPhotosForBikes($bikes);
        }

        return $bikes;
    }

    /**
     * Get all stolen bikes, optionally filtered by search criteria.
     * 
     * @return Bike[]
     */
    public function findStolen(
        bool $withPhotos = false,
        ?string $search = null,
        ?string $color = null,
        ?int $yearFrom = null,
        ?int $yearTo = null,
        ?string $frameNumber = null,
        ?string $qrHash = null
    ): array {
        $where  = ["status = 'stolen'"];
        $params = [];

        if ($qrHash !== null && $qrHash !== '') {
            $where[]  = "qr_hash = ?";
            $params[] = $qrHash;
        }

        if ($search !== null && $search !== '') {
            $where[]  = "(brand LIKE ? OR model LIKE ? OR description LIKE ?)";
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($color !== null && $color !== '') {
            $where[]  = "color LIKE ?";
            $params[] = '%' . $color . '%';
        }

        if ($yearFrom !== null) {
            $where[]  = "year_of_manufacture >= ?";
            $params[] = $yearFrom;
        }

        if ($yearTo !== null) {
            $where[]  = "year_of_manufacture <= ?";
            $params[] = $yearTo;
        }

        if ($frameNumber !== null && $frameNumber !== '') {
            $where[]  = "frame_number LIKE ?";
            $params[] = '%' . $frameNumber . '%';
        }

        $sql  = "SELECT * FROM bikes WHERE " . implode(' AND ', $where) . " ORDER BY updated_at DESC";
        $rows = $this->db->fetchAll($sql, $params);
        $bikes = array_map(fn(array $row) => Bike::fromRow($row), $rows);

        if ($withPhotos) {
            $this->loadPhotosForBikes($bikes);
        }

        return $bikes;
    }

    /**
     * Get min/max year of manufacture from stolen bikes (for year slider range).
     * Returns ['min' => int, 'max' => int].
     */
    public function getStolenBikeYearRange(): array
    {
        $row = $this->db->fetchOne(
            "SELECT MIN(year_of_manufacture) AS min_year, MAX(year_of_manufacture) AS max_year
             FROM bikes WHERE status = 'stolen' AND year_of_manufacture IS NOT NULL"
        );
        $currentYear = (int) date('Y');
        $min = $row && $row['min_year'] ? (int) $row['min_year'] : 1990;
        $max = $row && $row['max_year'] ? (int) $row['max_year'] : $currentYear;

        if ($max - $min < 4) {
            $min = $min - 2;
            $max = max($max + 2, $currentYear);
        }

        return ['min' => $min, 'max' => $max];
    }

    /**
     * Get all bikes marked as shared (available for borrowing).
     * 
     * @return Bike[]
     */
    public function findShared(
        bool $withPhotos = false,
        ?string $search = null,
        ?string $color = null,
        ?int $yearFrom = null,
        ?int $yearTo = null,
        array $excludeIds = [],
        ?string $qrHash = null
    ): array {
        $where  = ["is_shared = 1", "status = 'active'"];
        $params = [];

        if ($qrHash !== null && $qrHash !== '') {
            $where[]  = "qr_hash = ?";
            $params[] = $qrHash;
        }

        if ($search !== null && $search !== '') {
            $where[]  = "(brand LIKE ? OR model LIKE ? OR description LIKE ?)";
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($color !== null && $color !== '') {
            $where[]  = "color LIKE ?";
            $params[] = '%' . $color . '%';
        }

        if ($yearFrom !== null) {
            $where[]  = "year_of_manufacture >= ?";
            $params[] = $yearFrom;
        }

        if ($yearTo !== null) {
            $where[]  = "year_of_manufacture <= ?";
            $params[] = $yearTo;
        }

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $where[]      = "id NOT IN ({$placeholders})";
            $params       = array_merge($params, $excludeIds);
        }

        $sql  = "SELECT * FROM bikes WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
        $rows = $this->db->fetchAll($sql, $params);

        $bikes = array_map(fn(array $row) => Bike::fromRow($row), $rows);

        if ($withPhotos) {
            $this->loadPhotosForBikes($bikes);
        }

        return $bikes;
    }

    /**
     * Get min/max year of manufacture from shared active bikes (for year slider range).
     * Returns ['min' => int, 'max' => int].
     */
    public function getSharedBikeYearRange(): array
    {
        $row = $this->db->fetchOne(
            "SELECT MIN(year_of_manufacture) AS min_year, MAX(year_of_manufacture) AS max_year
             FROM bikes WHERE is_shared = 1 AND status = 'active' AND year_of_manufacture IS NOT NULL"
        );
        $currentYear = (int) date('Y');
        $min = $row && $row['min_year'] ? (int) $row['min_year'] : 1990;
        $max = $row && $row['max_year'] ? (int) $row['max_year'] : $currentYear;

        // Ensure at least a 4-year range so the slider is always usable
        if ($max - $min < 4) {
            $min = $min - 2;
            $max = max($max + 2, $currentYear);
        }

        return ['min' => $min, 'max' => $max];
    }

    /**
     * Get distinct colors from all shared active bikes (for filter dropdown).
     */
    public function getSharedBikeColors(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT color FROM bikes WHERE is_shared = 1 AND status = 'active' ORDER BY color ASC"
        );
        return array_column($rows, 'color');
    }

    /**
     * Get distinct colors from all stolen bikes (for filter dropdown).
     */
    public function getStolenBikeColors(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT color FROM bikes WHERE status = 'stolen' ORDER BY color ASC"
        );
        return array_column($rows, 'color');
    }

    /**
     * Create a new bike. Returns the new bike's ID.
     */
    public function create(array $data): int
    {
        return $this->db->insert('bikes', [
            'owner_id'             => $data['owner_id'],
            'qr_hash'              => $data['qr_hash'],
            'brand'                => $data['brand'],
            'model'                => $data['model'] ?? null,
            'color'                => $data['color'],
            'frame_number'         => $data['frame_number'] ?? null,
            'year_of_manufacture'  => $data['year_of_manufacture'] ?? null,
            'description'          => $data['description'] ?? null,
            'is_shared'            => $data['is_shared'] ?? 0,
        ]);
    }

    /**
     * Update a bike's details.
     */
    public function update(int $bikeId, array $data): void
    {
        $allowed = ['brand', 'model', 'color', 'frame_number', 'year_of_manufacture', 'description', 'is_shared'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        if (empty($filtered)) {
            return;
        }

        $this->db->update('bikes', $filtered, 'id = ?', [$bikeId]);
    }

    /**
     * Update bike status (active, stolen, found, recovered).
     */
    public function updateStatus(int $bikeId, string $status): void
    {
        $this->db->update('bikes', ['status' => $status], 'id = ?', [$bikeId]);
    }

    /**
     * Delete a bike and all its related data (CASCADE handles photos).
     */
    public function delete(int $bikeId): void
    {
        $this->db->delete('bikes', 'id = ?', [$bikeId]);
    }

    /**
     * Check if a QR hash already exists.
     */
    public function qrHashExists(string $hash): bool
    {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM bikes WHERE qr_hash = ?",
            [$hash]
        );

        return (int) $count > 0;
    }

    /**
     * Count bikes by owner.
     */
    public function countByOwner(int $ownerId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM bikes WHERE owner_id = ?",
            [$ownerId]
        );
    }

    /**
     * Count all bikes, optionally by status.
     */
    public function countAll(?string $status = null): int
    {
        if ($status !== null) {
            return (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM bikes WHERE status = ?",
                [$status]
            );
        }

        return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM bikes");
    }

    /**
     * Find all bikes, optionally filtered by status, with photos.
     * @return Bike[]
     */
    public function findAll(?string $status = null, bool $withPhotos = false): array
    {
        if ($status !== null) {
            $rows = $this->db->fetchAll(
                "SELECT * FROM bikes WHERE status = ? ORDER BY created_at DESC",
                [$status]
            );
        } else {
            $rows = $this->db->fetchAll("SELECT * FROM bikes ORDER BY created_at DESC");
        }

        $bikes = array_map(fn(array $row) => Bike::fromRow($row), $rows);

        if ($withPhotos) {
            $this->loadPhotosForBikes($bikes);
        }

        return $bikes;
    }

    /**
     * Get seized bikes with optional search filters (including owner name/email via JOIN).
     *
     * @return Bike[]
     */
    public function findSeized(
        bool $withPhotos = false,
        ?string $search = null,
        ?string $color = null,
        ?int $yearFrom = null,
        ?int $yearTo = null,
        ?string $frameNumber = null,
        ?string $qrHash = null,
        ?string $ownerSearch = null
    ): array {
        $where  = ["b.status = 'seized'"];
        $params = [];
        $join   = '';

        if ($ownerSearch !== null && $ownerSearch !== '') {
            $join = 'JOIN users u ON b.owner_id = u.id';
            $like = '%' . $ownerSearch . '%';
            $where[]  = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($qrHash !== null && $qrHash !== '') {
            $where[]  = "b.qr_hash = ?";
            $params[] = $qrHash;
        }

        if ($search !== null && $search !== '') {
            $where[]  = "(b.brand LIKE ? OR b.model LIKE ? OR b.description LIKE ?)";
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($color !== null && $color !== '') {
            $where[]  = "b.color LIKE ?";
            $params[] = '%' . $color . '%';
        }

        if ($yearFrom !== null) {
            $where[]  = "b.year_of_manufacture >= ?";
            $params[] = $yearFrom;
        }

        if ($yearTo !== null) {
            $where[]  = "b.year_of_manufacture <= ?";
            $params[] = $yearTo;
        }

        if ($frameNumber !== null && $frameNumber !== '') {
            $where[]  = "b.frame_number LIKE ?";
            $params[] = '%' . $frameNumber . '%';
        }

        $sql  = "SELECT b.* FROM bikes b {$join} WHERE " . implode(' AND ', $where) . " ORDER BY b.updated_at DESC";
        $rows = $this->db->fetchAll($sql, $params);
        $bikes = array_map(fn(array $row) => Bike::fromRow($row), $rows);

        if ($withPhotos) {
            $this->loadPhotosForBikes($bikes);
        }

        return $bikes;
    }

    /**
     * Get min/max year of manufacture from seized bikes (for year slider range).
     */
    public function getSeizedBikeYearRange(): array
    {
        $row = $this->db->fetchOne(
            "SELECT MIN(year_of_manufacture) AS min_year, MAX(year_of_manufacture) AS max_year
             FROM bikes WHERE status = 'seized' AND year_of_manufacture IS NOT NULL"
        );
        $currentYear = (int) date('Y');
        $min = $row && $row['min_year'] ? (int) $row['min_year'] : 1990;
        $max = $row && $row['max_year'] ? (int) $row['max_year'] : $currentYear;

        if ($max - $min < 4) {
            $min = $min - 2;
            $max = max($max + 2, $currentYear);
        }

        return ['min' => $min, 'max' => $max];
    }

    // ── Photos ─────────────────────────────────────────────────

    /**
     * Get all photos for a bike.
     * 
     * @return BikePhoto[]
     */
    public function findPhotosByBikeId(int $bikeId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM bike_photos WHERE bike_id = ? ORDER BY is_primary DESC, uploaded_at ASC",
            [$bikeId]
        );

        return array_map(fn(array $row) => BikePhoto::fromRow($row), $rows);
    }

    /**
     * Find a single photo by ID.
     */
    public function findPhotoById(int $photoId): ?BikePhoto
    {
        $row = $this->db->fetchOne("SELECT * FROM bike_photos WHERE id = ?", [$photoId]);

        return $row ? BikePhoto::fromRow($row) : null;
    }

    /**
     * Add a photo to a bike. Returns the photo ID.
     */
    public function addPhoto(int $bikeId, string $filePath, int $uploadedBy, bool $isPrimary = false): int
    {
        try {
            $this->db->beginTransaction();

            // If this is primary, unset other primaries first
            if ($isPrimary) {
                $this->db->update('bike_photos', ['is_primary' => 0], 'bike_id = ?', [$bikeId]);
            }

            $photoId = $this->db->insert('bike_photos', [
                'bike_id'     => $bikeId,
                'file_path'   => $filePath,
                'is_primary'  => $isPrimary ? 1 : 0,
                'uploaded_by' => $uploadedBy,
            ]);

            $this->db->commit();

            return $photoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a photo.
     */
    public function deletePhoto(int $photoId): void
    {
        $this->db->delete('bike_photos', 'id = ?', [$photoId]);
    }

    /**
     * Set a photo as primary for its bike.
     */
    public function setPrimaryPhoto(int $photoId, int $bikeId): void
    {
        try {
            $this->db->beginTransaction();

            $this->db->update('bike_photos', ['is_primary' => 0], 'bike_id = ?', [$bikeId]);
            $this->db->update('bike_photos', ['is_primary' => 1], 'id = ?', [$photoId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ── Batch loading ──────────────────────────────────────────

    /**
     * Load photos for multiple bikes at once (avoids N+1 queries).
     * 
     * @param Bike[] $bikes
     */
    private function loadPhotosForBikes(array $bikes): void
    {
        if (empty($bikes)) {
            return;
        }

        $ids = array_map(fn(Bike $b) => $b->getId(), $bikes);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = $this->db->fetchAll(
            "SELECT * FROM bike_photos WHERE bike_id IN ({$placeholders}) ORDER BY is_primary DESC, uploaded_at ASC",
            $ids
        );

        // Group photos by bike_id
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['bike_id']][] = BikePhoto::fromRow($row);
        }

        // Assign photos to bikes
        foreach ($bikes as $bike) {
            $bike->setPhotos($grouped[$bike->getId()] ?? []);
        }
    }
}