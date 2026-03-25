<?php

namespace App\Providers;

use App\Services\CrmNotificationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.navbar', function ($view): void {
            $notificationService = app(CrmNotificationService::class);

            $view->with($notificationService->getDropdownViewData(auth()->user()));
        });
    }
}
