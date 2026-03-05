<?php

use App\Http\Controllers\Api\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->group(function (): void {
    Route::get('/whatsapp', [WhatsAppWebhookController::class, 'verify'])
        ->name('webhooks.whatsapp.verify');
    Route::post('/whatsapp', [WhatsAppWebhookController::class, 'receive'])
        ->name('webhooks.whatsapp.receive');
});
