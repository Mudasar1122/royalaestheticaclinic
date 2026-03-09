<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppCloudApiService
{
    /**
     * @return array<string, mixed>
     */
    public function sendTextMessage(string $to, string $message, bool $previewUrl = false): array
    {
        $recipient = $this->normalizeRecipient($to);
        $config = $this->resolveConfig();

        $response = Http::withToken($config['access_token'])
            ->acceptJson()
            ->post(
                rtrim($config['api_base_url'], '/').'/'.trim($config['api_version'], '/').'/'.$config['phone_number_id'].'/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => $previewUrl,
                        'body' => $message,
                    ],
                ]
            );

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        if ($response->failed()) {
            $errorMessage = (string) data_get($payload, 'error.message', 'WhatsApp API request failed.');

            throw new RuntimeException('WhatsApp API error: '.$errorMessage);
        }

        $platformMessageId = (string) data_get($payload, 'messages.0.id', '');

        if ($platformMessageId === '') {
            throw new RuntimeException('WhatsApp API did not return a message id.');
        }

        return [
            'to' => $recipient,
            'platform_message_id' => $platformMessageId,
            'response' => $payload,
        ];
    }

    /**
     * @return array{api_base_url: string, api_version: string, phone_number_id: string, access_token: string}
     */
    private function resolveConfig(): array
    {
        $apiBaseUrl = (string) config('crm.whatsapp.api_base_url', '');
        $apiVersion = (string) config('crm.whatsapp.api_version', '');
        $phoneNumberId = (string) config('crm.whatsapp.phone_number_id', '');
        $accessToken = (string) config('crm.whatsapp.access_token', '');

        if ($apiBaseUrl === '' || $apiVersion === '' || $phoneNumberId === '' || $accessToken === '') {
            throw new RuntimeException('Missing WhatsApp Cloud API config. Set WHATSAPP_API_BASE_URL, WHATSAPP_API_VERSION, WHATSAPP_PHONE_NUMBER_ID and WHATSAPP_ACCESS_TOKEN.');
        }

        return [
            'api_base_url' => $apiBaseUrl,
            'api_version' => $apiVersion,
            'phone_number_id' => $phoneNumberId,
            'access_token' => $accessToken,
        ];
    }

    private function normalizeRecipient(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || $digits === '') {
            throw new RuntimeException('A valid recipient phone number is required.');
        }

        return $digits;
    }
}
