<?php

declare(strict_types=1);

namespace App\Response;

/**
 * JSON Response - returns data as JSON.
 * 
 * Used for API endpoints or when the client sends Accept: application/json.
 * 
 * Usage in controller:
 *   return new JsonResponse(['bikes' => $bikes]);
 *   return new JsonResponse(['error' => 'Not found'], 404);
 */
class JsonResponse extends Response
{
    private mixed $data;

    public function __construct(mixed $data = [], int $statusCode = 200)
    {
        parent::__construct($statusCode);
        $this->data = $data;

        $this->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    protected function sendBody(): void
    {
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}