<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\SuperAdmin\ImpersonationController;
use App\Http\Controllers\SuperAdmin\SystemSettingsController;
use App\Http\Controllers\SuperAdmin\FeedbackController as SuperFeedbackController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\TestMailController as SuperTestMailController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SetupWizardController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\GoogleCalendarController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\TenantPageController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\DashboardOrderController;
use App\Http\Controllers\Api\StaffOrderController;
use App\Http\Controllers\Api\PropertyImagesController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\RestoreController;
use App\Http\Controllers\Api\TestEmailController;
use App\Http\Controllers\MarketingController;
use Illuminate\Support\Facades\Auth;


// Stripe webhook — must be outside auth/tenant middleware (no CSRF)
Route::post('/stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook']);

// Root — marketing page (authenticated users are redirected inside the controller)
Route::get('/', [MarketingController::class, 'index'])->name('root');
Route::get('/privacy-policy', [MarketingController::class, 'privacy'])->name('privacy');
Route::get('/terms', [MarketingController::class, 'terms'])->name('terms');
Route::get('/sitemap.xml', [MarketingController::class, 'sitemap'])->name('sitemap');
Route::get('/marketing-sitemap.xml', [MarketingController::class, 'sitemapPages'])->name('sitemap.pages');

// Email unsubscribe (signed URL, no auth needed)
Route::get('/email/unsubscribe/{user}', function (\App\Models\User $user) {
    if (!$user->unsubscribed_at) {
        $user->update(['unsubscribed_at' => now()]);
    }
    return view('emails.unsubscribed');
})->name('email.unsubscribe')->middleware('signed');

// Auth routes (no tenant)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.submit');
    Route::get('/forgot-password', [PasswordResetController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:password.email')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Email verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');


    Route::post('/email/resend', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');
});

// Email verification callback — no auth required (signed URL is sufficient)
Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Http\Request $request, $id, $hash) {
    $user = \App\Models\User::findOrFail($id);

    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Invalid verification link.');
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    if (!Auth::check()) {
        Auth::login($user);
    }

    return redirect()->route('tenant.admin.dashboard', ['account' => $user->tenant->slug]);
})->middleware('signed')->name('verification.verify');
Route::middleware(['registrations.enabled'])->group(function () {
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:register')->name('register.submit');
});

// OAuth
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Google Calendar OAuth callback (single URL for all tenants - slug stored in session)
Route::get('/auth/google-calendar/callback', [GoogleCalendarController::class, 'callback'])
    ->middleware('auth')->name('google-calendar.callback');
Route::middleware(['registrations.enabled'])->group(function () {
    Route::get('/register/complete', [GoogleAuthController::class, 'showComplete'])->name('register.complete');
    Route::post('/register/complete', [GoogleAuthController::class, 'completeRegistration'])->name('register.complete.submit');
});

// Super admin routes
Route::prefix('super-admin')->middleware(['auth', 'super.admin', 'no.cache'])->name('super.')->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/tenants', [SuperAdminController::class, 'tenants'])->name('tenants');
    Route::get('/tenants/{tenant}', [SuperAdminController::class, 'showTenant'])->name('tenants.show');
    Route::put('/tenants/{tenant}', [SuperAdminController::class, 'updateTenant'])->name('tenants.update');
    Route::delete('/tenants/{tenant}', [SuperAdminController::class, 'destroyTenant'])->name('tenants.destroy');
    Route::put('/tenants/{tenant}/owner', [SuperAdminController::class, 'updateTenantOwner'])->name('tenants.owner.update');
    Route::post('/tenants/{tenant}/verify', [SuperAdminController::class, 'verifyTenantOwner'])->name('tenants.owner.verify');
    Route::post('/tenants/{tenant}/resend-verification', [SuperAdminController::class, 'resendTenantVerification'])->name('tenants.owner.resend');
    Route::post('/impersonate/{tenant}', [ImpersonationController::class, 'impersonate'])->name('impersonate');
    Route::post('/stop-impersonate', [ImpersonationController::class, 'stop'])->name('stop-impersonate');
    Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/email', [SystemSettingsController::class, 'updateEmail'])->name('settings.email');
    // Send a test email through the saved platform SMTP — surfaces the
    // raw SMTP error so the operator can diagnose without trawling logs.
    Route::post('/api/test-mail', [SuperTestMailController::class, 'send'])->middleware('throttle:10,1')->name('api.test-mail');
    Route::get('/feedback', [SuperFeedbackController::class, 'index'])->name('feedback');
    Route::get('/feedback/{id}', [SuperFeedbackController::class, 'show'])->name('feedback.show');
    Route::get('/feedback/{id}/screenshot/{index?}', [SuperFeedbackController::class, 'screenshot'])->name('feedback.screenshot');
    Route::delete('/feedback/{id}', [SuperFeedbackController::class, 'destroy'])->name('feedback.destroy');
    Route::get('/mailer', [SuperAdminController::class, 'mailer'])->name('mailer');
    Route::post('/mailer/send', [SuperAdminController::class, 'sendMail'])->name('mailer.send');
    Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity');
    Route::delete('/activity', [ActivityLogController::class, 'clear'])->name('activity.clear');
    Route::get('/activity/export', [ActivityLogController::class, 'export'])->name('activity.export');
    Route::get('/api/tenants/search', [SuperAdminController::class, 'searchTenants'])->name('api.tenants.search');
});

// Tenant routes
Route::prefix('{account}')->middleware(['tenant', 'impersonate'])->name('tenant.')->group(function () {

    // Public pages (behind site.lock middleware)
    Route::middleware(['site.lock'])->group(function () {
        Route::get('/', [TenantPageController::class, 'home'])->name('home');
        Route::get('/gallery', [TenantPageController::class, 'gallery'])->name('gallery');
        Route::get('/map', [TenantPageController::class, 'map'])->name('map');
        Route::get('/property/{id}', [TenantPageController::class, 'property'])->name('property');
        Route::get('/privacy-policy', [TenantPageController::class, 'privacy'])->name('privacy');
        Route::get('/terms', [TenantPageController::class, 'terms'])->name('terms');
        Route::get('/contact', [TenantPageController::class, 'contact'])->name('contact');
        Route::get('/chat', [TenantPageController::class, 'chat'])->name('chat');
        Route::get('/sitemap.xml', [TenantPageController::class, 'sitemap'])->name('sitemap');
        Route::get('/llms.txt', [TenantPageController::class, 'llms'])->name('llms');
        Route::get('/favicon.svg', [TenantPageController::class, 'favicon'])->name('favicon');
    });

    // Public APIs
    Route::post('/api/contact', [ContactController::class, 'submit'])->middleware('throttle:10,1')->name('api.contact');
    Route::post('/api/chatbot', [ChatbotController::class, 'chat'])->middleware('throttle:5,1')->name('api.chatbot');
    Route::get('/api/properties', [PropertyApiController::class, 'index'])->middleware('throttle:60,1')->name('api.properties');

    // Appointment booking (public, no auth)
    Route::post('/appointments', [AppointmentController::class, 'storePublic'])->middleware('throttle:10,1')->name('appointments.store');

    // Admin routes
    Route::prefix('admin')->middleware(['auth', 'verified', 'tenant.active', 'tenant.admin', 'no.cache'])->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/setup', [SetupWizardController::class, 'show'])->name('setup');
        Route::post('/setup', [SetupWizardController::class, 'save'])->name('setup.save');
        Route::post('/setup/skip', [SetupWizardController::class, 'skip'])->name('setup.skip');
        Route::post('/dashboard-order', [DashboardOrderController::class, 'save'])->name('dashboard.order');

        // Properties
        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
        Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
        Route::get('/properties/{id}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
        Route::put('/properties/{id}', [PropertyController::class, 'update'])->name('properties.update');
        Route::delete('/properties/{id}', [PropertyController::class, 'destroy'])->name('properties.destroy');

        // Property image API
        Route::get('/api/property-images/{id}', [PropertyImagesController::class, 'index'])->name('api.property-images');
        Route::post('/api/property-images', [PropertyImagesController::class, 'store'])->name('api.property-images.store');
        Route::post('/api/property-images/{id}/primary', [PropertyImagesController::class, 'setPrimary'])->name('api.property-images.primary');
        Route::post('/api/property-images/{id}/reorder', [PropertyImagesController::class, 'reorder'])->name('api.property-images.reorder');
        Route::delete('/api/property-images/{id}', [PropertyImagesController::class, 'destroy'])->name('api.property-images.destroy');

        // Staff — Pro plan only
        Route::middleware('plan.pro')->defaults('pro_feature', 'Staff management')->group(function () {
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::put('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
            Route::post('/api/staff-order', [StaffOrderController::class, 'save'])->name('api.staff-order');
        });

        // Messages
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/messages/action', [MessageController::class, 'action'])->name('messages.action');
        Route::post('/messages/bulk', [MessageController::class, 'bulk'])->name('messages.bulk');

        // Appointments (admin) — Pro plan only
        Route::middleware('plan.pro')->defaults('pro_feature', 'Appointment management')->group(function () {
            Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
            Route::post('/appointments/{id}/action', [AppointmentController::class, 'action'])->name('appointments.action');
            Route::post('/appointments/bulk', [AppointmentController::class, 'bulk'])->name('appointments.bulk');
            Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        });

        // Google Calendar OAuth — Pro plan only
        Route::middleware('plan.pro')->defaults('pro_feature', 'Google Calendar sync')->group(function () {
            Route::get('/google-calendar/connect', [GoogleCalendarController::class, 'redirect'])->name('google-calendar.connect');
            Route::post('/google-calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google-calendar.disconnect');
        });

        // AI Assistant — Pro plan only
        Route::middleware('plan.pro')->defaults('pro_feature', 'The AI assistant')->group(function () {
            Route::get('/assistant', [AdminChatController::class, 'index'])->name('assistant');
            Route::post('/api/assistant', [AdminChatController::class, 'chat'])->middleware('throttle:30,1')->name('api.assistant');
        });

        // Settings
        Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Export/Backup/Test Email
        Route::get('/api/export/{type}', [ExportController::class, 'export'])->name('api.export');
        Route::post('/api/backup', [BackupController::class, 'create'])->middleware('throttle:5,1')->name('api.backup');
        Route::post('/api/restore', [RestoreController::class, 'restore'])->middleware('throttle:5,1')->name('api.restore');
        Route::post('/api/test-email', [TestEmailController::class, 'send'])->middleware('throttle:10,1')->name('api.test-email');

    });

    // Billing routes — accessible even with expired trial (no tenant.active check
    // so a deactivated tenant can still reach billing to re-subscribe).
    //
    // SECURITY: tenant.admin middleware is REQUIRED here. Without it, any
    // authenticated user could hit /tenant-b/admin/billing and see another
    // tenant's invoices, open their Stripe portal, swap their plan, etc.
    // The tenant.admin middleware (EnsureTenantAdmin) verifies that the
    // logged-in user actually belongs to the URL-slug tenant — it does NOT
    // check is_active, so the "deactivated tenant reaches billing" path
    // still works.
    Route::prefix('admin')->middleware(['auth', 'tenant.admin'])->name('admin.')->group(function () {
        Route::get('/billing', [BillingController::class, 'show'])->name('billing');
        Route::get('/billing/subscribed', [BillingController::class, 'subscribed'])->name('billing.subscribed');
        Route::post('/billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');
        Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
        Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
        Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
        Route::get('/billing/invoice/{invoice}', [BillingController::class, 'downloadInvoice'])->name('billing.invoice');
        Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback');
        Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
        Route::get('/feedback/thanks', [FeedbackController::class, 'thanks'])->name('feedback.thanks');
    });
});
