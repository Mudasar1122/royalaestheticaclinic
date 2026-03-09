<?php

namespace App\Services\Messaging;

use RuntimeException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioWhatsAppService
{
    /**
     * @return array<string, mixed>
     */
    public function sendTextMessage(string $to, string $message): array
    {
        $trimmedMessage = trim($message);

        if ($trimmedMessage === '') {
            throw new RuntimeException('Message body cannot be empty.');
        }

        $recipient = $this->normalizeRecipient($to);
        $config = $this->resolveConfig();

        if ($recipient === 'whatsapp:+') {
            throw new RuntimeException('A valid recipient WhatsApp number is required.');
        }

        $payload = [
            'from' => $config['from'],
            'body' => $trimmedMessage,
        ];

        if ($config['status_callback'] !== '') {
            $payload['statusCallback'] = $config['status_callback'];
        }

        try {
            $client = new Client($config['account_sid'], $config['auth_token']);
            $response = $client->messages->create($recipient, $payload);
        } catch (TwilioException $exception) {
            throw new RuntimeException('Twilio WhatsApp API error: '.$exception->getMessage(), previous: $exception);
        }

        $messageSid = (string) ($response->sid ?? '');

        if ($messageSid === '') {
            throw new RuntimeException('Twilio WhatsApp API did not return a message sid.');
        }

        return [
            'to' => $recipient,
            'platform_message_id' => $messageSid,
            'response' => [
                'sid' => $messageSid,
                'status' => (string) ($response->status ?? ''),
                'from' => (string) ($response->from ?? ''),
                'to' => (string) ($response->to ?? ''),
            ],
        ];
    }

    /**
     * @return array{account_sid: string, auth_token: string, from: string, status_callback: string}
     */
    private function resolveConfig(): array
    {
        $accountSid = (string) config('services.twilio.account_sid', '');
        $authToken = (string) config('services.twilio.auth_token', '');
        $from = $this->normalizeRecipient((string) config('crm.whatsapp.twilio.from', ''));
        $statusCallback = trim((string) config('crm.whatsapp.twilio.status_callback', ''));

        if ($accountSid === '' || $authToken === '') {
            throw new RuntimeException('Missing Twilio config. Set TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN.');
        }

        if ($from === 'whatsapp:+') {
            throw new RuntimeException('Missing Twilio WhatsApp sender. Set TWILIO_WHATSAPP_FROM.');
        }

        return [
            'account_sid' => $accountSid,
            'auth_token' => $authToken,
            'from' => $from,
            'status_callback' => $statusCallback,
        ];
    }

    private function normalizeRecipient(string $value): string
    {
        $trimmed = trim($value);
        $trimmed = str_ireplace('whatsapp:', '', $trimmed);
        $digits = preg_replace('/\D+/', '', $trimmed);

        if ($digits === null || $digits === '') {
            return 'whatsapp:+';
        }

        return 'whatsapp:+'.$digits;
    }
}
