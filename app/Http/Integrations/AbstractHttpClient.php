<?php

namespace App\Http\Integrations;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

abstract class AbstractHttpClient
{
    abstract public function getBaseUri(): string;

    abstract public function getPendingRequest(): PendingRequest;

    public function get(string $endpoint, array $query = [], array $headers = []): Response
    {
        return $this->getPendingRequest()->withHeaders($headers)->get($this->getFormattedRequestUri($endpoint), $query);
    }

    public function post(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->getPendingRequest()->withHeaders($headers)->post($this->getFormattedRequestUri($endpoint), $data);
    }

    public function put(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->getPendingRequest()->withHeaders($headers)->put($this->getFormattedRequestUri($endpoint), $data);
    }

    public function patch(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->getPendingRequest()->withHeaders($headers)->patch($this->getFormattedRequestUri($endpoint), $data);
    }

    public function delete(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->getPendingRequest()->withHeaders($headers)->delete($this->getFormattedRequestUri($endpoint), $data);
    }

    private function getFormattedRequestUri(string $endpoint): string
    {
        return sprintf('%s/%s', $this->getBaseUri(), ltrim($endpoint, '/'));
    }
}
