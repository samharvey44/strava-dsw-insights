<?php

namespace App\Http\Integrations;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

abstract class AbstractHttpClient
{
    abstract protected function getBaseUri(): string;

    abstract protected function getPendingRequest(?User $forUser): PendingRequest;

    public function get(
        string $endpoint,
        array $query = [],
        array $headers = [],
        ?User $forUser = null,
    ): Response {
        return $this->getPendingRequest($forUser)->withHeaders($headers)->get($this->getFormattedRequestUri($endpoint), $query);
    }

    public function post(
        string $endpoint,
        array $data = [],
        array $headers = [],
        ?User $forUser = null,
    ): Response {
        return $this->getPendingRequest($forUser)->withHeaders($headers)->post($this->getFormattedRequestUri($endpoint), $data);
    }

    public function put(
        string $endpoint,
        array $data = [],
        array $headers = [],
        ?User $forUser = null,
    ): Response {
        return $this->getPendingRequest($forUser)->withHeaders($headers)->put($this->getFormattedRequestUri($endpoint), $data);
    }

    public function patch(
        string $endpoint,
        array $data = [],
        array $headers = [],
        ?User $forUser = null,
    ): Response {
        return $this->getPendingRequest($forUser)->withHeaders($headers)->patch($this->getFormattedRequestUri($endpoint), $data);
    }

    public function delete(
        string $endpoint,
        array $data = [],
        array $headers = [],
        ?User $forUser = null,
    ): Response {
        return $this->getPendingRequest($forUser)->withHeaders($headers)->delete($this->getFormattedRequestUri($endpoint), $data);
    }

    private function getFormattedRequestUri(string $endpoint): string
    {
        return sprintf('%s/%s', $this->getBaseUri(), ltrim($endpoint, '/'));
    }
}
