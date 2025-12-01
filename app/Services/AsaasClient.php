<?php

namespace App\Services;

use App\Models\AsaasConfig;

class AsaasClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $config = AsaasConfig::getActive();
        if (!$config) {
            throw new \RuntimeException('Configuração do Asaas não encontrada.');
        }

        $environment = $config['environment'] ?? 'sandbox';
        if ($environment === 'production') {
            $this->baseUrl = 'https://api.asaas.com/v3';
            $this->apiKey = $config['production_api_key'];
        } else {
            $this->baseUrl = 'https://sandbox.asaas.com/api/v3';
            $this->apiKey = $config['sandbox_api_key'];
        }
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $ch = curl_init();
        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];

        if (strtoupper($method) === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);

        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Erro na chamada Asaas: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($result, true) ?: [];

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Erro Asaas HTTP ' . $httpCode . ': ' . ($data['errors'][0]['description'] ?? '')); 
        }

        return $data;
    }

    public function createOrUpdateCustomer(array $customer): array
    {
        return $this->request('POST', '/customers', $customer);
    }

    public function createSubscription(array $payload): array
    {
        return $this->request('POST', '/subscriptions', $payload);
    }
}
