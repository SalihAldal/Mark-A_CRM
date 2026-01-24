<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Panel\AiController;
use App\Http\Controllers\Panel\CalendarController;
use App\Http\Controllers\Panel\ChatController;
use App\Http\Controllers\Panel\DashboardController as TenantDashboardController;
use App\Http\Controllers\Panel\HelpController;
use App\Http\Controllers\Panel\LeadController;
use App\Http\Controllers\Panel\ListsController;
use App\Http\Controllers\Panel\MailController;
use App\Http\Controllers\Panel\MessageController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\StatsController;
use App\Http\Controllers\Panel\SupportBotController;
use App\Http\Controllers\RealtimeTokenController;
use App\Http\Controllers\Super\DashboardController as SuperDashboardController;
use App\Http\Controllers\Webhooks\MetaWebhookController;
use App\Http\Controllers\Webhooks\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/login');

Route::middleware('web')->group(function () {
    // Webhooks (no auth, no CSRF)
    Route::match(['GET', 'POST'], '/webhooks/meta', [MetaWebhookController::class, 'handle']);
    Route::post('/webhooks/telegram', [TelegramWebhookController::class, 'handle']);

    Route::get('/login', [LoginController::class, 'show'])->middleware('guest')->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth');

    Route::middleware(['auth', 'panel.tenant'])->group(function () {
        Route::get('/panel', [TenantDashboardController::class, 'index'])
            ->middleware('role:tenant_admin|staff|customer')
            ->name('panel.dashboard');

        Route::get('/realtime/token', [RealtimeTokenController::class, 'issue'])
            ->middleware('role:tenant_admin|staff|customer');

        Route::post('/support/bot', [SupportBotController::class, 'ask'])
            ->middleware('role:tenant_admin|staff|customer');

        // Leads
        Route::get('/leads', [LeadController::class, 'index'])->middleware('role:tenant_admin|staff|customer');
        Route::get('/leads/create', [LeadController::class, 'create'])->middleware('role:tenant_admin|staff');
        Route::post('/leads', [LeadController::class, 'store'])->middleware('role:tenant_admin|staff');
        Route::get('/leads/kanban', [LeadController::class, 'kanban'])->middleware('role:tenant_admin|staff');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('role:tenant_admin|staff|customer');
        Route::post('/leads/{lead}/notes', [LeadController::class, 'addNote'])->middleware('role:tenant_admin|staff');
        Route::post('/leads/{lead}/move-stage', [LeadController::class, 'moveStage'])->middleware('role:tenant_admin|staff');

        // Chats
        Route::get('/chats', [ChatController::class, 'index'])->middleware('role:tenant_admin|staff|customer');
        Route::post('/chats/{thread}/messages/text', [MessageController::class, 'sendText'])->middleware('role:tenant_admin|staff');
        Route::post('/chats/{thread}/messages/file', [MessageController::class, 'sendFile'])->middleware('role:tenant_admin|staff');
        Route::post('/chats/{thread}/messages/voice', [MessageController::class, 'sendVoice'])->middleware('role:tenant_admin|staff');
        Route::post('/chats/{thread}/ai/suggest', [AiController::class, 'suggest'])->middleware('role:tenant_admin|staff');

        // Calendar
        Route::get('/calendar', [CalendarController::class, 'index'])->middleware('role:tenant_admin|staff|customer');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->middleware('role:tenant_admin|staff|customer');
        Route::post('/calendar', [CalendarController::class, 'store'])->middleware('role:tenant_admin|staff');
        Route::post('/calendar/{event}/delete', [CalendarController::class, 'destroy'])->middleware('role:tenant_admin|staff');

        // Lists
        Route::get('/lists', [ListsController::class, 'index'])->middleware('role:tenant_admin|staff');
        Route::post('/lists', [ListsController::class, 'store'])->middleware('role:tenant_admin|staff');

        // Mail
        Route::get('/mail', [MailController::class, 'index'])->middleware('role:tenant_admin|staff');
        Route::post('/mail', [MailController::class, 'store'])->middleware('role:tenant_admin|staff');

        Route::get('/stats', [StatsController::class, 'index'])->middleware('role:tenant_admin|staff|customer');

        // Settings (AI rules)
        Route::get('/settings', [SettingsController::class, 'index'])->middleware('role:tenant_admin');
        Route::post('/settings/ai-rules', [SettingsController::class, 'saveAiRules'])->middleware('role:tenant_admin');
        Route::post('/settings/ai-rules/{rule}', [SettingsController::class, 'updateAiRule'])->middleware('role:tenant_admin');
        Route::post('/settings/ai-rules/{rule}/delete', [SettingsController::class, 'deleteAiRule'])->middleware('role:tenant_admin');
        Route::post('/settings/stages', [SettingsController::class, 'storeStage'])->middleware('role:tenant_admin');
        Route::post('/settings/stages/{stage}', [SettingsController::class, 'updateStage'])->middleware('role:tenant_admin');
        Route::post('/settings/stages/{stage}/delete', [SettingsController::class, 'deleteStage'])->middleware('role:tenant_admin');
        Route::post('/settings/integrations', [SettingsController::class, 'storeIntegration'])->middleware('role:tenant_admin');
        Route::post('/settings/integrations/{acc}', [SettingsController::class, 'updateIntegration'])->middleware('role:tenant_admin');
        Route::post('/settings/integrations/{acc}/delete', [SettingsController::class, 'deleteIntegration'])->middleware('role:tenant_admin');
        Route::post('/settings/integrations/demo', [SettingsController::class, 'createIntegrationDemo'])->middleware('role:tenant_admin');
        Route::post('/settings/mail', [SettingsController::class, 'saveMailSettings'])->middleware('role:tenant_admin');
        Route::post('/settings/staff', [SettingsController::class, 'storeStaff'])->middleware('role:tenant_admin');

        // Help Center
        Route::get('/help', [HelpController::class, 'index'])->middleware('role:tenant_admin|staff|customer');
        Route::post('/help', [HelpController::class, 'store'])->middleware('role:tenant_admin');
    });

    Route::middleware(['auth', 'panel.super', 'role:superadmin'])->group(function () {
        Route::get('/super', [SuperDashboardController::class, 'index'])->name('super.dashboard');
        Route::view('/super/tenants', 'super.tenants.index');
        Route::view('/super/settings', 'super.settings.index');
    });
});
