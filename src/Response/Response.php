<?php

declare(strict_types=1);

namespace App\Response;

/**
 * Abstract HTTP Response.
 * 
 * All controller actions return a Response object.
 * The App calls send() to output it to the client.
 */
abstract class Response
{
    protected int $statusCode;
    protected array $headers = [];

    public function __construct(int $statusCode = 200)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Add a response header.
     */
    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Send the response to the client.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        $this->sendBody();
    }

    /**
     * Output the response body. Implemented by subclasses.
     */
    abstract protected function sendBody(): void;
}