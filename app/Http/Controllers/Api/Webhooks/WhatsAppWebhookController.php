<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\LeadIngestion\WhatsAppWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppWebhookService $whatsAppWebhookService
    ) {
    }

    public function verify(Request $request): Response
    {
        $mode = (string) $request->query->get('hub.mode');
        $verifyToken = (string) $request->query->get('hub.verify_token');
        $challenge = (string) $request->query->get('hub.challenge');
        $expectedToken = (string) config('crm.whatsapp.verify_token', '');

        if (
            $mode === 'subscribe'
            && $expectedToken !== ''
            && hash_equals($expectedToken, $verifyToken)
        ) {
            return response($challenge, 200, ['Content-Type' => 'text/plain']);
        }

        return response('Forbidden', 403, ['Content-Type' => 'text/plain']);
    }

    public function receive(Request $request): JsonResponse
    {
        if (!$this->hasValidSignature($request)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        try {
            $result = $this->whatsAppWebhookService->ingest($request->all());

            $statusCode = match ($result['status'] ?? 'processed') {
                'failed' => 500,
                'ignored' => 202,
                default => 200,
            };

            return response()->json($result, $statusCode);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'failed',
                'message' => 'Unexpected webhook error.',
            ], 500);
        }
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('crm.whatsapp.app_secret', '');

        if ($secret === '') {
            return true;
        }

        $headerValue = (string) $request->header('X-Hub-Signature-256', '');

        if ($headerValue === '' || !str_starts_with($headerValue, 'sha256=')) {
            return false;
        }

        $incomingHash = substr($headerValue, 7);
        $expectedHash = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expectedHash, $incomingHash);
    }
}
