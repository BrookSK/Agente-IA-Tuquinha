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

    public function addAttendeeToEvent(string $eventId, string $email, ?string $name = null): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $eventId = trim($eventId);
        $email = trim($email);
        if ($eventId === '' || $email === '') {
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $accessToken = $this->refreshAccessToken();
        if ($accessToken === null) {
            return false;
        }

        $getUrl = 'https://www.googleapis.com/calendar/v3/calendars/'
            . rawurlencode($this->calendarId)
            . '/events/' . rawurlencode($eventId)
            . '?fields=attendees';

        $ch = curl_init($getUrl);
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
            error_log('GoogleCalendarService addAttendeeToEvent GET attendees failed: HTTP ' . $httpCode . ' response=' . substr((string)$response, 0, 500));
            return false;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            $data = [];
        }

        $attendees = [];
        if (!empty($data['attendees']) && is_array($data['attendees'])) {
            $attendees = $data['attendees'];
        }

        $lowerEmail = strtolower($email);
        foreach ($attendees as $att) {
            if (!is_array($att)) {
                continue;
            }
            if (!empty($att['email']) && strtolower((string)$att['email']) === $lowerEmail) {
                return true;
            }
        }

        $newAttendee = ['email' => $email];
        if ($name !== null && $name !== '') {
            $newAttendee['displayName'] = $name;
        }
        $attendees[] = $newAttendee;

        $patchUrl = 'https://www.googleapis.com/calendar/v3/calendars/'
            . rawurlencode($this->calendarId)
            . '/events/' . rawurlencode($eventId)
            . '?sendUpdates=all';

        $payload = [
            'attendees' => $attendees,
        ];

        $ch = curl_init($patchUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $resp2 = curl_exec($ch);
        $code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code2 < 200 || $code2 >= 300 || !$resp2) {
            error_log('GoogleCalendarService addAttendeeToEvent PATCH failed: HTTP ' . $code2 . ' response=' . substr((string)$resp2, 0, 500));
            return false;
        }

        return true;
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

    /**
     * Tenta obter o link de gravação a partir do evento do Calendar (google_event_id).
     * O Google normalmente adiciona o link da gravação como anexo ou dentro da descrição
     * do evento após o processamento do vídeo no Drive.
     */
    public function findRecordingUrlByEventId(string $eventId): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $eventId = trim($eventId);
        if ($eventId === '') {
            return null;
        }

        $accessToken = $this->refreshAccessToken();
        if ($accessToken === null) {
            return null;
        }

        $url = 'https://www.googleapis.com/calendar/v3/calendars/'
            . rawurlencode($this->calendarId)
            . '/events/' . rawurlencode($eventId)
            . '?fields=description,attachments';

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
        if (!is_array($data)) {
            return null;
        }

        if (!empty($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $att) {
                if (!is_array($att)) {
                    continue;
                }
                $fileUrl = $att['fileUrl'] ?? '';
                if ($fileUrl !== '' && str_starts_with($fileUrl, 'https://drive.google.com')) {
                    return (string)$fileUrl;
                }
            }
        }

        $description = (string)($data['description'] ?? '');
        if ($description !== '') {
            if (preg_match('~https?://drive\\.google\\.com/[^\s<]+~i', $description, $m)) {
                return (string)$m[0];
            }
        }

        return null;
    }

    public function grantDriveFileAccessToEmails(string $fileUrl, array $emails): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $fileId = $this->extractDriveFileId($fileUrl);
        if ($fileId === null) {
            return;
        }

        $accessToken = $this->refreshAccessToken();
        if ($accessToken === null) {
            return;
        }

        $cleanEmails = [];
        foreach ($emails as $email) {
            $email = trim((string)$email);
            if ($email === '') {
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $lower = strtolower($email);
            $cleanEmails[$lower] = $lower;
        }

        if (empty($cleanEmails)) {
            return;
        }

        foreach ($cleanEmails as $email) {
            $payload = [
                'role' => 'reader',
                'type' => 'user',
                'emailAddress' => $email,
            ];

            $url = 'https://www.googleapis.com/drive/v3/files/'
                . rawurlencode($fileId)
                . '/permissions?supportsAllDrives=true&sendNotificationEmail=false';

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
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code < 200 || $code >= 300 || !$response) {
                continue;
            }
        }
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

    private function extractDriveFileId(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('~https?://drive\\.google\\.com/file/d/([^/]+)/~i', $url, $m)) {
            return $m[1];
        }

        if (preg_match('~https?://drive\\.google\\.com/open\\?id=([^&]+)~i', $url, $m)) {
            return $m[1];
        }

        if (preg_match('~https?://drive\\.google\\.com/uc\\?id=([^&]+)~i', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
