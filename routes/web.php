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

// OAuth callback from Zernio — outside auth middleware, no signature required.
// Security is handled by a short-lived token stored in the cache (set during connect).
Route::get('/social-accounts/oauth-callback/{platform}', [SocialAccountController::class, 'callback'])
    ->name('social-accounts.oauth-callback');

// Authenticated routes
Route::middleware(['auth', 'verified', 'tenant.active'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/zernio', [ProfileController::class, 'storeZernioKey'])->name('profile.zernio.store');
    Route::delete('/profile/zernio/{zernioApiKey}', [ProfileController::class, 'destroyZernioKey'])->name('profile.zernio.destroy');
    Route::post('/profile/zernio/{zernioApiKey}/regenerate-secret', [ProfileController::class, 'regenerateZernioSecret'])->name('profile.zernio.regenerate-secret');

    // Social Accounts
    Route::prefix('social-accounts')->name('social-accounts.')->group(function () {
        Route::get('/', [SocialAccountController::class, 'index'])->name('index');
        Route::get('/connect/{platform}', [SocialAccountController::class, 'connect'])->name('connect');
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
        Route::get('/messages', [InboxController::class, 'messages'])->name('messages');
        Route::get('/conversations/json', [InboxController::class, 'conversationsJson'])->name('conversations.json');
        Route::get('/events', [InboxController::class, 'inboxEvents'])->name('events');
        Route::get('/comments', [InboxController::class, 'comments'])->name('comments');
        Route::post('/comments/{comment}/reply', [InboxController::class, 'replyComment'])->name('reply-comment');
        Route::patch('/messages/{message}/read', [InboxController::class, 'markRead'])->name('mark-read');
        Route::get('/messages/{id}', [InboxController::class, 'conversationMessages'])->name('conversation.messages');
        Route::post('/messages/{conversationId}/reply', [InboxController::class, 'sendConversationReply'])->name('conversation.reply');
        Route::post('/messages/{conversationId}/mark-read', [InboxController::class, 'markConversationRead'])->name('conversation.mark-read');
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
