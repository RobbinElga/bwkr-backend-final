<?php

use App\Http\Controllers\Api\V1\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Api\V1\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Api\V1\Auth\AccountController;
use App\Http\Controllers\Api\V1\Auth\DonaturAuthController;
use App\Http\Controllers\Api\V1\Auth\StaffAuthController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorController;
use App\Http\Controllers\Api\V1\PublicProgramController;
use App\Http\Controllers\Api\V1\PublicProjectController;
use App\Http\Controllers\Api\V1\Admin\ProjectUpdateController as AdminProjectUpdateController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\BankAccountController as AdminBankAccountController;
use App\Http\Controllers\Api\V1\PublicBankAccountController;
use App\Http\Controllers\Api\V1\Admin\DonationInputController as AdminDonationInputController;
use App\Http\Controllers\Api\V1\PublicDonationController;
use App\Http\Controllers\Api\V1\Admin\DonationClaimController as AdminClaimController;
use App\Http\Controllers\Api\V1\Admin\ExpenseController as AdminExpenseController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Api\V1\PublicNewsController;
use App\Http\Controllers\Api\V1\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Api\V1\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Api\V1\PublicPartnerController;
use App\Http\Controllers\Api\V1\PublicTestimonialController;
use App\Http\Controllers\Api\V1\Admin\AchievementController as AdminAchievementController;
use App\Http\Controllers\Api\V1\Admin\ImpactVideoController as AdminImpactVideoController;
use App\Http\Controllers\Api\V1\PublicAchievementController;
use App\Http\Controllers\Api\V1\PublicImpactVideoController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Api\V1\Admin\UserManagementController as AdminUserController;
use App\Http\Controllers\Api\V1\Donatur\DonationHistoryController as DonaturDonationController;
use App\Http\Controllers\Api\V1\Donatur\ProfileController as DonaturProfileController;
use App\Http\Controllers\Api\V1\Admin\BroadcastController as AdminBroadcastController;
use App\Http\Controllers\Api\V1\Admin\BroadcastTemplateController as AdminBroadcastTemplateController;
use App\Http\Controllers\Api\V1\Admin\CrmDonorController as AdminCrmDonorController;
use App\Http\Controllers\Api\V1\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\V1\PublicReportController;
use App\Http\Controllers\Api\V1\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\V1\PublicSettingController;
use App\Http\Controllers\Api\V1\Admin\CashLedgerController as AdminCashLedgerController;
use App\Http\Controllers\Api\V1\KeuanganDashboardController;
use App\Http\Controllers\Api\V1\Admin\NewsCategoryController as AdminNewsCategoryController;

Route::prefix('v1')->group(function () {

    // ===== AUTH =====
    Route::post('auth/masuk-sistem',     [StaffAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/donatur/register', [DonaturAuthController::class, 'register'])->middleware('throttle:login');
    Route::post('auth/donatur/login',    [DonaturAuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/masuk-sistem/2fa', [StaffAuthController::class, 'verify'])->middleware('throttle:login');
        Route::get('auth/2fa/setup',         [TwoFactorController::class, 'setup']);
        Route::post('auth/2fa/enable',       [TwoFactorController::class, 'enable']);

        Route::middleware('ability:staff,donatur')->group(function () {
            Route::get('auth/me',       [AccountController::class, 'me']);
            Route::post('auth/logout',  [AccountController::class, 'logout']);
            Route::put('auth/password', [AccountController::class, 'changePassword']);
        });
    });

    // ===== PUBLIK (tanpa auth) =====
    Route::get('programs',           [PublicProgramController::class, 'index']);
    Route::get('programs/{program}', [PublicProgramController::class, 'show']);
    Route::get('projects',           [PublicProjectController::class, 'index']);
    Route::get('projects/{project}', [PublicProjectController::class, 'show']);
    Route::get('reports',          [PublicReportController::class, 'index']);
    Route::get('reports/{report}', [PublicReportController::class, 'show']);
    Route::get('bank-accounts', [PublicBankAccountController::class, 'index']);
    Route::post('donations',                 [PublicDonationController::class, 'store']);
    Route::get('donations/{ref_no}/status',  [PublicDonationController::class, 'status']);
    Route::get('news',        [PublicNewsController::class, 'index']);
    Route::get('news/{news}', [PublicNewsController::class, 'show']);
    Route::post('news/{news}/like',   [PublicNewsController::class, 'like']);
    Route::post('news/{news}/unlike', [PublicNewsController::class, 'unlike']);
    Route::get('partners',     [PublicPartnerController::class, 'index']);
    Route::get('testimonials', [PublicTestimonialController::class, 'index']);
    Route::get('impact-videos', [PublicImpactVideoController::class, 'index']);
    Route::get('achievements',  [PublicAchievementController::class, 'index']);
    Route::get('settings', [PublicSettingController::class, 'index']);

    // ===== AREA ADMIN =====
    Route::middleware(['auth:sanctum', 'ability:staff'])->prefix('admin')->group(function () {

        // Notifikasi — semua staf (lihat & tandai milik sendiri)
        Route::get('notifications', [AdminNotificationController::class, 'index']);
        Route::post('notifications/read-all', [AdminNotificationController::class, 'markAllRead']);
        Route::post('notifications/{notification}/read', [AdminNotificationController::class, 'markRead']);

        Route::middleware('role:super_admin,admin')->group(function () {
            Route::get('ping', fn() => response()->json(['message' => 'Halo Admin/SuperAdmin!']));
            Route::apiResource('programs', AdminProgramController::class)->except(['destroy']);
            Route::get('projects/{project}/stats', [AdminProjectController::class, 'stats']);
            Route::apiResource('projects', AdminProjectController::class)->except(['destroy']);
            Route::apiResource('projects.updates', AdminProjectUpdateController::class)->scoped();
            Route::get('donations-input/export', [AdminDonationInputController::class, 'export']);
            Route::get('donations-claim/export', [AdminClaimController::class, 'export']);
            Route::get('expenses/export',        [AdminExpenseController::class, 'export']);
            Route::get('crm/donors/export', [AdminCrmDonorController::class, 'export']);
            Route::get('dashboard/export',  [AdminDashboardController::class, 'export']);
            Route::apiResource('bank-accounts', AdminBankAccountController::class)
                ->only(['store', 'update', 'destroy']);
            Route::post('donations-claim/{claim}/approve',  [AdminClaimController::class, 'approve']);
            Route::post('donations-claim/{claim}/reject',   [AdminClaimController::class, 'reject']);
            Route::get('expenses',                       [AdminExpenseController::class, 'index']);
            Route::post('expenses',                      [AdminExpenseController::class, 'store']);
            Route::get('expenses/{expense}',             [AdminExpenseController::class, 'show']);
            Route::post('expenses/{expense}/approve',    [AdminExpenseController::class, 'approve']);
            Route::post('expenses/{expense}/reject',     [AdminExpenseController::class, 'reject']);
            Route::get('expenses/{expense}/file/{type}', [AdminExpenseController::class, 'file']);
            Route::get('dashboard', [AdminDashboardController::class, 'index']);
            Route::apiResource('news', AdminNewsController::class);
            Route::apiResource('partners', AdminPartnerController::class);
            Route::apiResource('testimonials', AdminTestimonialController::class);
            Route::apiResource('impact-videos', AdminImpactVideoController::class);
            Route::apiResource('achievements', AdminAchievementController::class);
            Route::put('crm/donors/{hash}/tier',  [AdminCrmDonorController::class, 'updateTier']);
            Route::apiResource('reports', AdminReportController::class);
            Route::get('dashboard', [AdminDashboardController::class, 'index']);
            Route::get('dashboard/trends', [AdminDashboardController::class, 'trends']);
            Route::get('projects/{project}/donors', [AdminProjectController::class, 'donors']);
            Route::get('settings',  [AdminSettingController::class, 'index']);
            Route::post('settings', [AdminSettingController::class, 'update']);
            Route::get('keuangan/dashboard', [KeuanganDashboardController::class, 'index']);
            Route::get('news-categories', [AdminNewsCategoryController::class, 'index']);
            Route::post('news-categories', [AdminNewsCategoryController::class, 'store']);
            Route::delete('news-categories/{newsCategory}', [AdminNewsCategoryController::class, 'destroy']);
            Route::apiResource('news', AdminNewsController::class);
        });

        Route::middleware('role:super_admin,admin,cs')->group(function () {
            Route::get('donations-input',                  [AdminDonationInputController::class, 'index']);
            Route::post('donations-input',                 [AdminDonationInputController::class, 'store']);
            Route::get('donations-input/{donation}',       [AdminDonationInputController::class, 'show']);
            Route::get('donations-input/{donation}/proof', [AdminDonationInputController::class, 'proof']);
            Route::get('donations-claim',                   [AdminClaimController::class, 'index']);
            Route::post('donations-claim',                  [AdminClaimController::class, 'store']);
            Route::get('crm/donors',              [AdminCrmDonorController::class, 'index']);
            Route::get('crm/donors/{hash}',       [AdminCrmDonorController::class, 'show']);
            Route::apiResource('crm/templates', AdminBroadcastTemplateController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->parameters(['templates' => 'template']);
            Route::post('crm/broadcast', [AdminBroadcastController::class, 'send']);
            Route::get('crm/broadcasts', [AdminBroadcastController::class, 'index']);
            Route::apiResource('bank-accounts', AdminBankAccountController::class)
                ->only(['index', 'show']);
            Route::get('dashboard/recent-donations', [AdminDashboardController::class, 'recentDonations']);
        });

        Route::middleware('role:super_admin')->group(function () {
            Route::get('ping-super', fn() => response()->json(['message' => 'Halo SuperAdmin!']));

            Route::apiResource('users', AdminUserController::class);
            Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);
            Route::post('users/{user}/reset-2fa',      [AdminUserController::class, 'resetTwoFactor']);

            Route::get('audit-logs', [AdminAuditLogController::class, 'index']);
            Route::get('keuangan/ledger', [AdminCashLedgerController::class, 'index']);
            Route::get('programs/trashed',       [AdminProgramController::class, 'trashed']);
            Route::post('programs/{id}/restore', [AdminProgramController::class, 'restore']);
            Route::delete('programs/{id}/force', [AdminProgramController::class, 'forceDelete']);
            Route::delete('programs/{program}',  [AdminProgramController::class, 'destroy']);

            Route::get('projects/trashed',       [AdminProjectController::class, 'trashed']);
            Route::post('projects/{id}/restore', [AdminProjectController::class, 'restore']);
            Route::delete('projects/{id}/force', [AdminProjectController::class, 'forceDelete']);
            Route::delete('projects/{project}',  [AdminProjectController::class, 'destroy']);
        });
    });

    // ===== AREA DONATUR =====
    Route::middleware(['auth:sanctum', 'ability:donatur'])->prefix('donatur')->group(function () {
        Route::get('ping', fn() => response()->json(['message' => 'Halo Donatur!']));
        Route::get('profile', [DonaturProfileController::class, 'show']);
        Route::put('profile', [DonaturProfileController::class, 'update']);
        Route::get('donations',            [DonaturDonationController::class, 'index']);
        Route::get('donations/{ref_no}',   [DonaturDonationController::class, 'show']);
        Route::get('summary', [DonaturDonationController::class, 'summary']);
        Route::post('profile/avatar',   [DonaturProfileController::class, 'updateAvatar']);
        Route::delete('profile/avatar', [DonaturProfileController::class, 'deleteAvatar']);
    });
});
