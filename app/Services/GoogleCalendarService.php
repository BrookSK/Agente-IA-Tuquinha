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

    /**
     * Busca a gravação de uma reunião do Google Meet a partir do link/código da reunião.
     * Retorna a URL de visualização no Google Drive (exportUri) ou null se não encontrar.
     *
     * Requer escopos da API do Google Meet adequados no token (por exemplo,
     * meetings.space.readonly e meetings.space.recordings.readonly).
     */
    public function findRecordingExportUriByMeetLink(string $meetLinkOrCode): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $meetingCode = $this->extractMeetingCode($meetLinkOrCode);
        if ($meetingCode === null) {
            return null;
        }

        $accessToken = $this->refreshAccessToken();
        if ($accessToken === null) {
            return null;
        }

        $filter = 'space.meeting_code = "' . $meetingCode . '"';
        $url = 'https://meet.googleapis.com/v2/conferenceRecords?pageSize=1&filter=' . urlencode($filter);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['conferenceRecords'][0]['name'])) {
            return null;
        }

        $conferenceName = (string)$data['conferenceRecords'][0]['name'];
        $recUrl = 'https://meet.googleapis.com/v2/' . rawurlencode($conferenceName) . '/recordings';

        $ch = curl_init($recUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        $resp2 = curl_exec($ch);
        $code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code2 < 200 || $code2 >= 300 || !$resp2) {
            return null;
        }

        $rData = json_decode($resp2, true);
        if (!is_array($rData) || empty($rData['recordings']) || !is_array($rData['recordings'])) {
            return null;
        }

        // Tenta primeiro gravações com arquivo já gerado
        foreach ($rData['recordings'] as $rec) {
            if (!is_array($rec)) {
                continue;
            }
            $state = $rec['state'] ?? null;
            if ($state !== 'FILE_GENERATED' && $state !== 'ENDED') {
                continue;
            }
            if (!empty($rec['driveDestination']['exportUri'])) {
                return (string)$rec['driveDestination']['exportUri'];
            }
        }

        // Fallback: qualquer recording com exportUri
        foreach ($rData['recordings'] as $rec) {
            if (!is_array($rec)) {
                continue;
            }
            if (!empty($rec['driveDestination']['exportUri'])) {
                return (string)$rec['driveDestination']['exportUri'];
            }
        }

        return null;
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

    private function extractMeetingCode(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('~https?://meet\\.google\\.com/([a-z0-9-]+)~i', $value, $m)) {
            return strtolower($m[1]);
        }

        if (preg_match('~^[a-z0-9]{3}-[a-z0-9]{4}-[a-z0-9]{3}$~i', $value)) {
            return strtolower($value);
        }

        return null;
    }
}
