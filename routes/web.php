<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Welcome/Landing page
Route::get('/', function () {
    return view('welcome');
});

// Webhook (no auth - called by Zernio)
Route::post('/webhook/zernio', [WebhookController::class, 'handle'])->name('webhook.zernio');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Social Accounts
    Route::prefix('social-accounts')->name('social-accounts.')->group(function () {
        Route::get('/', [SocialAccountController::class, 'index'])->name('index');
        Route::post('/connect', [SocialAccountController::class, 'connect'])->name('connect');
        Route::get('/oauth-redirect', [SocialAccountController::class, 'oauthRedirect'])->name('oauth-redirect');
        Route::post('/oauth-callback', [SocialAccountController::class, 'oauthCallback'])->name('oauth-callback');
        Route::patch('/{socialAccount}/disconnect', [SocialAccountController::class, 'disconnect'])->name('disconnect');
        Route::delete('/{socialAccount}', [SocialAccountController::class, 'destroy'])->name('destroy');
    });

    // Posts
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/create', [PostController::class, 'create'])->name('create');
        Route::post('/', [PostController::class, 'store'])->name('store');
        Route::get('/{post}', [PostController::class, 'show'])->name('show');
    });

    // Content Calendar
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('/{scheduledPost}/edit', [CalendarController::class, 'edit'])->name('edit');
        Route::patch('/{scheduledPost}/reschedule', [CalendarController::class, 'reschedule'])->name('reschedule');
        Route::patch('/{scheduledPost}/update', [CalendarController::class, 'update'])->name('update');
        Route::delete('/{scheduledPost}', [CalendarController::class, 'destroy'])->name('destroy');
    });

    // Inbox
    Route::prefix('inbox')->name('inbox.')->group(function () {
        Route::get('/', [InboxController::class, 'index'])->name('index');
        Route::post('/comments/{comment}/reply', [InboxController::class, 'replyComment'])->name('reply-comment');
        Route::patch('/messages/{message}/read', [InboxController::class, 'markRead'])->name('mark-read');
    });

    // AI Settings
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/settings', [AiController::class, 'settings'])->name('settings');
        Route::post('/settings', [AiController::class, 'saveSettings'])->name('save-settings');
        Route::post('/knowledge', [AiController::class, 'storeKnowledge'])->name('store-knowledge');
        Route::delete('/knowledge/{knowledgeBase}', [AiController::class, 'destroyKnowledge'])->name('destroy-knowledge');
        Route::post('/generate-reply/{comment}', [AiController::class, 'generateReply'])->name('generate-reply');
        Route::post('/save-reply/{comment}', [AiController::class, 'saveAiReply'])->name('save-reply');
    });

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Subscription
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::post('/checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('/simulate-payment', [SubscriptionController::class, 'simulatePayment'])->name('simulate-payment');
    });
});

require __DIR__ . '/auth.php';
