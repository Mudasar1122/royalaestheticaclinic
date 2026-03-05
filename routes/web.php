<?php

use App\Http\Controllers\AiapplicationController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\ComponentspageController;
use App\Http\Controllers\CryptocurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::redirect('/login', '/authentication/sign-in')->name('login');

Route::prefix('authentication')->controller(AuthenticationController::class)->group(function (): void {
    Route::get('/forgot-password', 'forgotPassword')->name('forgotPassword');
    Route::post('/forgot-password', 'forgotPasswordPost')->name('forgotPasswordPost');
    Route::get('/reset-password/{token}', 'resetPassword')->name('password.reset');
    Route::post('/reset-password', 'resetPasswordPost')->name('resetPasswordPost');
    Route::get('/sign-in', 'signin')->name('signin');
    Route::post('/sign-in', 'signinPost')->name('signinPost');
    Route::post('/logout', 'logout')->name('logout');
    Route::get('/sign-up', 'signup')->name('signup');
});

Route::middleware('auth')->group(function (): void {
    Route::controller(DashboardController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/dashboard/index', 'index')->name('dashboardIndex');
        Route::get('/dashboard/crm', 'crm')->name('crmDashboard');
        Route::get('/dashboard/index-3', 'index3')->name('index3');
        Route::get('/dashboard/index-4', 'index4')->name('index4');
        Route::get('/dashboard/index-5', 'index5')->name('index5');
        Route::get('/dashboard/index-6', 'index6')->name('index6');
        Route::get('/dashboard/index-7', 'index7')->name('index7');
        Route::get('/dashboard/index-8', 'index8')->name('index8');
        Route::get('/dashboard/index-9', 'index9')->name('index9');
    });

    Route::redirect('/dashboard/index-2', '/dashboard/crm')->name('index2');

    Route::prefix('clinic')->controller(ClinicController::class)->group(function (): void {
        Route::get('/leads', 'leads')->name('clinicLeads');
        Route::patch('/leads/{lead}/stage', 'updateLeadStage')->name('clinicLeadStageUpdate');
        Route::patch('/leads/{lead}', 'updateLead')->name('clinicLeadUpdate');
        Route::get('/manual-lead', 'createManualLead')->name('clinicManualLead');
        Route::post('/manual-lead', 'storeManualLead')->name('clinicManualLeadStore');
        Route::get('/appointments', 'appointments')->name('clinicAppointments');
        Route::patch('/follow-ups/{followUp}/status', 'updateFollowUpStatus')->name('clinicFollowUpStatusUpdate');
        Route::get('/consultations', 'consultations')->name('clinicConsultations');
        Route::get('/consultation-form', 'consultationForm')->name('clinicConsultationForm');
        Route::get('/treatments', 'treatments')->name('clinicTreatments');
        Route::get('/evidence', 'evidence')->name('clinicEvidence');
    });

    Route::controller(HomeController::class)->group(function (): void {
        Route::get('calendar-Main', 'calendarMain')->name('calendarMain');
        Route::get('chatempty', 'chatempty')->name('chatempty');
        Route::get('chat-message', 'chatMessage')->name('chatMessage');
        Route::get('chat-profile', 'chatProfile')->name('chatProfile');
        Route::get('email', 'email')->name('email');
        Route::post('email/campaign', 'sendCampaign')->name('emailCampaignSend');
        Route::get('faq', 'faq')->name('faq');
        Route::get('gallery', 'gallery')->name('gallery');
        Route::get('image-upload', 'imageUpload')->name('imageUpload');
        Route::get('kanban', 'kanban')->name('kanban');
        Route::get('page-error', 'pageError')->name('pageError');
        Route::get('pricing', 'pricing')->name('pricing');
        Route::get('starred', 'starred')->name('starred');
        Route::get('terms-condition', 'termsCondition')->name('termsCondition');
        Route::get('veiw-details', 'veiwDetails')->name('veiwDetails');
        Route::get('widgets', 'widgets')->name('widgets');
    });

    Route::prefix('aiapplication')->controller(AiapplicationController::class)->group(function (): void {
        Route::get('/code-generator', 'codeGenerator')->name('codeGenerator');
        Route::get('/code-generatornew', 'codeGeneratorNew')->name('codeGeneratorNew');
        Route::get('/image-generator', 'imageGenerator')->name('imageGenerator');
        Route::get('/text-generator', 'textGenerator')->name('textGenerator');
        Route::get('/text-generatornew', 'textGeneratorNew')->name('textGeneratorNew');
        Route::get('/video-generator', 'videoGenerator')->name('videoGenerator');
        Route::get('/voice-generator', 'voiceGenerator')->name('voiceGenerator');
    });

    Route::prefix('chart')->controller(ChartController::class)->group(function (): void {
        Route::get('/column-chart', 'columnChart')->name('columnChart');
        Route::get('/line-chart', 'lineChart')->name('lineChart');
        Route::get('/pie-chart', 'pieChart')->name('pieChart');
    });

    Route::prefix('componentspage')->controller(ComponentspageController::class)->group(function (): void {
        Route::get('/alert', 'alert')->name('alert');
        Route::get('/avatar', 'avatar')->name('avatar');
        Route::get('/badges', 'badges')->name('badges');
        Route::get('/button', 'button')->name('button');
        Route::get('/calendar', 'calendar')->name('calendar');
        Route::get('/card', 'card')->name('card');
        Route::get('/carousel', 'carousel')->name('carousel');
        Route::get('/colors', 'colors')->name('colors');
        Route::get('/dropdown', 'dropdown')->name('dropdown');
        Route::get('/imageupload', 'imageUpload')->name('componentsImageUpload');
        Route::get('/list', 'list')->name('list');
        Route::get('/pagination', 'pagination')->name('pagination');
        Route::get('/progress', 'progress')->name('progress');
        Route::get('/radio', 'radio')->name('radio');
        Route::get('/star-rating', 'starRating')->name('starRating');
        Route::get('/switch', 'switch')->name('switch');
        Route::get('/tabs', 'tabs')->name('tabs');
        Route::get('/tags', 'tags')->name('tags');
        Route::get('/tooltip', 'tooltip')->name('tooltip');
        Route::get('/typography', 'typography')->name('typography');
        Route::get('/videos', 'videos')->name('videos');
    });

    Route::prefix('cryptocurrency')->controller(CryptocurrencyController::class)->group(function (): void {
        Route::get('/wallet', 'wallet')->name('wallet');
    });

    Route::prefix('forms')->controller(FormsController::class)->group(function (): void {
        Route::get('/form', 'form')->name('form');
        Route::get('/form-layout', 'formLayout')->name('formLayout');
        Route::get('/form-validation', 'formValidation')->name('formValidation');
        Route::get('/wizard', 'wizard')->name('wizard');
    });

    Route::prefix('invoice')->controller(InvoiceController::class)->group(function (): void {
        Route::get('/invoice-add', 'invoiceAdd')->name('invoiceAdd');
        Route::get('/invoice-edit', 'invoiceEdit')->name('invoiceEdit');
        Route::get('/invoice-list', 'invoiceList')->name('invoiceList');
        Route::get('/invoice-preview', 'invoicePreview')->name('invoicePreview');
    });

    Route::prefix('settings')->controller(SettingsController::class)->group(function (): void {
        Route::get('/company', 'company')->name('company');
        Route::get('/currencies', 'currencies')->name('currencies');
        Route::get('/language', 'language')->name('language');
        Route::get('/notification', 'notification')->name('notification');
        Route::get('/notification-alert', 'notificationAlert')->name('notificationAlert');
        Route::get('/payment-gateway', 'paymentGateway')->name('paymentGateway');
        Route::get('/theme', 'theme')->name('theme');
    });

    Route::prefix('table')->controller(TableController::class)->group(function (): void {
        Route::get('/table-basic', 'tableBasic')->name('tableBasic');
        Route::get('/table-data', 'tableData')->name('tableData');
    });

    Route::prefix('users')->controller(UsersController::class)->group(function (): void {
        Route::get('/add-user', 'addUser')->name('addUser')->middleware('admin');
        Route::post('/add-user', 'storeUser')->name('storeUser')->middleware('admin');
        Route::get('/users-grid', 'usersGrid')->name('usersGrid')->middleware('admin');
        Route::get('/users-list', 'usersList')->name('usersList')->middleware('admin');
        Route::patch('/{user}/status', 'toggleStatus')->name('usersToggleStatus')->middleware('admin');
        Route::get('/view-profile', 'viewProfile')->name('viewProfile');
        Route::post('/view-profile', 'updateProfile')->name('updateProfile');
        Route::post('/view-profile/password', 'updatePassword')->name('updateProfilePassword');
    });
});
