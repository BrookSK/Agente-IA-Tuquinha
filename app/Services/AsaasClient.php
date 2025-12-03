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
            'User-Agent: TuquinhaApp/1.0',
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
            // Log geral da resposta crua do Asaas para depuração
            error_log('AsaasClient HTTP ' . $httpCode . ' raw response: ' . $result);

            $desc = '';
            if (isset($data['errors']) && is_array($data['errors']) && !empty($data['errors'][0]['description'])) {
                $desc = $data['errors'][0]['description'];
            } elseif (!empty($data['message'])) {
                $desc = $data['message'];
            } else {
                $desc = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            throw new \RuntimeException('Erro Asaas HTTP ' . $httpCode . ': ' . $desc);
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

    public function getSubscription(string $id): array
    {
        return $this->request('GET', '/subscriptions/' . urlencode($id));
    }

    public function cancelSubscription(string $id): array
    {
        return $this->request('POST', '/subscriptions/' . urlencode($id) . '/cancel');
    }

    public function createPayment(array $payload): array
    {
        return $this->request('POST', '/payments', $payload);
    }
}
