<?php

namespace App\Http\Controllers;

use App\Services\CrmNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function codeGenerator()
    {
        return view('aiapplication/codeGenerator');
    }

    public function company()
    {
        return view('settings/company');
    }
    
    public function currencies()
    {
        return view('settings/currencies');
    }
    
    public function language()
    {
        return view('settings/language');
    }
    
    public function notification(Request $request, CrmNotificationService $notificationService)
    {
        return view('settings/notification', $notificationService->getNotificationPageViewData($request->user()));
    }

    public function notificationFeed(Request $request, CrmNotificationService $notificationService): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $payload = $notificationService->getDropdownFeedData(
            $request->user(),
            (int) ($validated['per_page'] ?? 4),
            (int) ($validated['page'] ?? 1)
        );

        return response()->json([
            'html' => view('components.notifications.dropdown-items', [
                'notifications' => $payload['notifications'],
            ])->render(),
            'current_page' => $payload['current_page'],
            'per_page' => $payload['per_page'],
            'has_more' => $payload['has_more'],
            'next_page' => $payload['next_page'],
            'total_count' => $payload['total_count'],
            'highlighted_count' => $payload['highlighted_count'],
        ]);
    }
    
    public function notificationAlert()
    {
        return view('settings/notificationAlert');
    }
    
    public function paymentGateway()
    {
        return view('settings/paymentGateway');
    }
    
    public function theme()
    {
        return view('settings/theme');
    }
    
}
