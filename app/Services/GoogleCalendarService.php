<?php

namespace App\Services;

use App\Models\Setting;

class GoogleCalendarService
{
    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $calendarId;

    public function __construct()
    {
        $this->clientId = trim(Setting::get('google_calendar_client_id', ''));
        $this->clientSecret = trim(Setting::get('google_calendar_client_secret', ''));
        $this->refreshToken = trim(Setting::get('google_calendar_refresh_token', ''));
        $this->calendarId = trim(Setting::get('google_calendar_calendar_id', 'primary'));
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '' && $this->refreshToken !== '' && $this->calendarId !== '';
    }

    /**
     * Cria um evento no Google Calendar com conferência Meet e retorna array com:
     * - 'event_id'
     * - 'meet_link'
     */
    public function createLiveEvent(string $summary, string $description, string $startDateTime, string $endDateTime, string $timeZone = 'America/Sao_Paulo'): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $accessToken = $this->refreshAccessToken();
        if ($accessToken === null) {
            return null;
        }

        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($this->calendarId) . '/events?conferenceDataVersion=1';

        $payload = [
            'summary' => $summary,
            'description' => $description,
            'start' => [
                'dateTime' => $startDateTime,
                'timeZone' => $timeZone,
            ],
            'end' => [
                'dateTime' => $endDateTime,
                'timeZone' => $timeZone,
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid('tuquinha-live-', true),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        $eventId = $data['id'] ?? null;
        $meetLink = null;

        if (!empty($data['hangoutLink'])) {
            $meetLink = (string)$data['hangoutLink'];
        } elseif (!empty($data['conferenceData']['entryPoints'])) {
            foreach ($data['conferenceData']['entryPoints'] as $entry) {
                if (($entry['entryPointType'] ?? '') === 'video' && !empty($entry['uri'])) {
                    $meetLink = (string)$entry['uri'];
                    break;
                }
            }
        }

        if (!$eventId || !$meetLink) {
            return null;
        }

        return [
            'event_id' => $eventId,
            'meet_link' => $meetLink,
        ];
    }

    private function refreshAccessToken(): ?string
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        $postFields = http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['access_token'])) {
            return null;
        }

        return (string)$data['access_token'];
    }
}
