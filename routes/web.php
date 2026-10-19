<?php

use Illuminate\Support\Facades\Route;

// Controllers — Auth
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Controllers — Landing (publik)
use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\FaqController;
use App\Http\Controllers\Landing\PublicKlasifikasiController;

// Controllers — Admin
use App\Http\Controllers\Admin\DashboardController          as AdminDashboard;
use App\Http\Controllers\Admin\AssessmentController         as AdminAssessment;
use App\Http\Controllers\Admin\RubricController             as AdminRubric;
use App\Http\Controllers\Admin\UserController               as AdminUser;
use App\Http\Controllers\Admin\KlasifikasiBerdayaController as AdminKlasifikasi;
use App\Http\Controllers\Admin\LandingPageController        as AdminLandingPage;
use App\Http\Controllers\Admin\BackupController             as AdminBackup;
use App\Http\Controllers\Admin\AuditLogController           as AdminAuditLog;
use App\Http\Controllers\Admin\ExportController             as AdminExport;
use App\Http\Controllers\Admin\PanduanController            as AdminPanduan;
use App\Http\Controllers\Admin\WilayahController            as AdminWilayah;
use App\Http\Controllers\Admin\ManageAdminController;
use App\Http\Controllers\Admin\ManageKecamatanController;

// Controllers — Kecamatan
use App\Http\Controllers\Kecamatan\DashboardController      as KecamatanDashboard;
use App\Http\Controllers\Kecamatan\AssessmentController     as KecamatanAssessment;
use App\Http\Controllers\Kecamatan\StatistikController      as KecamatanStatistik;
use App\Http\Controllers\Kecamatan\HasilController          as KecamatanHasil;
use App\Http\Controllers\Kecamatan\LaporanController        as KecamatanLaporan;
use App\Http\Controllers\Kecamatan\PanduanController        as KecamatanPanduan;

// Controllers — Validator
use App\Http\Controllers\Validator\DashboardController      as ValidatorDashboard;
use App\Http\Controllers\Validator\ValidationController     as ValidatorValidation;

// ============================================================
//  LANDING — publik, tanpa auth
// ============================================================

Route::get('/', [HomeController::class, 'index'])->name('landing.home');
Route::get('/faq', [FaqController::class, 'index'])->name('landing.faq');

// Public API — dipanggil AJAX dari landing page (filter peta)
Route::get('/public/klasifikasi', [PublicKlasifikasiController::class, 'index'])
    ->name('public.klasifikasi');

// ============================================================
//  AUTH — Login / Logout
//  Gunakan RedirectIfAuthenticated agar user yang sudah login
//  tidak bisa mengakses halaman login lagi.
// ============================================================

// -- Admin & Validator login (/admin/login)
Route::middleware('guest_redirect')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'createAdmin'])
        ->name('admin.login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'storeAdmin'])
        ->name('admin.login.store');
});

// -- Kecamatan login (/kecamatan/login)
Route::middleware('guest_redirect')->group(function () {
    Route::get('/kecamatan/login', [AuthenticatedSessionController::class, 'createKecamatan'])
        ->name('kecamatan.login');
    Route::post('/kecamatan/login', [AuthenticatedSessionController::class, 'storeKecamatan'])
        ->name('kecamatan.login.store');
});

// -- Logout (keduanya memerlukan auth)
Route::middleware('auth')->group(function () {
    Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroyAdmin'])
        ->name('admin.logout');
    Route::post('/kecamatan/logout', [AuthenticatedSessionController::class, 'destroyKecamatan'])
        ->name('kecamatan.logout');
});

// ============================================================
//  ADMIN  (ADMIN + SUPER_ADMIN)
// ============================================================

Route::prefix('admin')
    ->middleware(['auth', 'role:admin,super_admin'])
    ->name('admin.')
    ->group(function () {

        // Redirect /admin → /admin/dashboard
        Route::get('/', fn () => redirect()->route('admin.dashboard'));

        // Dashboard
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

        // ── Assessment ────────────────────────────────────────────────
        Route::prefix('assessment')->name('assessment.')->group(function () {
            // Static routes harus didefinisikan SEBELUM wildcard {id}
            Route::get('/create',     [AdminAssessment::class, 'create'])->name('create');
            Route::post('/',          [AdminAssessment::class, 'store'])->name('store');
            Route::get('/create/new', [AdminAssessment::class, 'createNew'])->name('create.new');

            // Results — harus sebelum /{id} agar tidak tertangkap wildcard
            Route::prefix('results')->name('results.')->group(function () {
                Route::get('/',                        [AdminAssessment::class, 'results'])->name('index');
                Route::get('/{userId}',                [AdminAssessment::class, 'resultsByUser'])->name('user');
                Route::get('/{userId}/{assessmentId}', [AdminAssessment::class, 'resultDetail'])->name('detail');
            });

            // Wildcard {id} — harus setelah semua static routes
            Route::get('/{id}',          [AdminAssessment::class, 'show'])->name('show');
            Route::get('/{id}/edit',     [AdminAssessment::class, 'edit'])->name('edit');
            Route::put('/{id}',          [AdminAssessment::class, 'update'])->name('update');
            Route::post('/{id}/version', [AdminAssessment::class, 'publishVersion'])->name('publish-version');
            Route::delete('/{id}',       [AdminAssessment::class, 'destroy'])->name('destroy');
        });

        // ── Rubric ────────────────────────────────────────────────────
        Route::prefix('rubric')->name('rubric.')->group(function () {
            Route::get('/',          [AdminRubric::class, 'index'])->name('index');
            Route::get('/create',    [AdminRubric::class, 'create'])->name('create');
            Route::post('/',         [AdminRubric::class, 'store'])->name('store');
            Route::get('/{id}',      [AdminRubric::class, 'show'])->name('show');
            Route::get('/{id}/edit', [AdminRubric::class, 'edit'])->name('edit');
            Route::put('/{id}',      [AdminRubric::class, 'update'])->name('update');
            Route::delete('/{id}',   [AdminRubric::class, 'destroy'])->name('destroy');
        });

        // ── Users (kecamatan accounts) ────────────────────────────────
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',                    [AdminUser::class, 'index'])->name('index');
            Route::get('/create',              [AdminUser::class, 'create'])->name('create');
            Route::post('/',                   [AdminUser::class, 'store'])->name('store');
            Route::get('/kecamatan-by-kabupaten', [AdminUser::class, 'kecamatanByKabupaten'])->name('kecamatan-by-kabupaten');
            Route::get('/{id}',                [AdminUser::class, 'show'])->name('show');
            Route::get('/{id}/edit',           [AdminUser::class, 'edit'])->name('edit');
            Route::put('/{id}',                [AdminUser::class, 'update'])->name('update');
            Route::delete('/{id}',             [AdminUser::class, 'destroy'])->name('destroy');
        });

        // ── Manage Kecamatan ──────────────────────────────────────────
        Route::get('/manage-kecamatan', [ManageKecamatanController::class, 'index'])
            ->name('manage-kecamatan');

        // ── Manage Admin (SUPER_ADMIN only) ───────────────────────────
        Route::prefix('manage-admin')
            ->middleware('role:super_admin')
            ->name('manage-admin.')
            ->group(function () {
                Route::get('/',            [ManageAdminController::class, 'index'])->name('index');
                Route::get('/create',      [ManageAdminController::class, 'create'])->name('create');
                Route::post('/',           [ManageAdminController::class, 'store'])->name('store');
                Route::get('/{id}/edit',   [ManageAdminController::class, 'edit'])->name('edit');
                Route::put('/{id}',        [ManageAdminController::class, 'update'])->name('update');
                Route::delete('/{id}',     [ManageAdminController::class, 'destroy'])->name('destroy');
            });

        // ── Klasifikasi Berdaya ───────────────────────────────────────
        Route::get('/klasifikasi-berdaya', [AdminKlasifikasi::class, 'index'])
            ->name('klasifikasi-berdaya');

        // ── Landing Page settings ─────────────────────────────────────
        Route::prefix('landing-page')->name('landing-page.')->group(function () {
            Route::get('/',       [AdminLandingPage::class, 'index'])->name('index');
            Route::put('/',       [AdminLandingPage::class, 'update'])->name('update');
            Route::post('/upload',[AdminLandingPage::class, 'upload'])->name('upload');
        });

        // ── Panduan ───────────────────────────────────────────────────
        Route::prefix('panduan')->name('panduan.')->group(function () {
            Route::get('/',          [AdminPanduan::class, 'index'])->name('index');
            Route::get('/new',       [AdminPanduan::class, 'create'])->name('create');
            Route::post('/',         [AdminPanduan::class, 'store'])->name('store');
            Route::get('/{id}',      [AdminPanduan::class, 'show'])->name('show');
            Route::get('/{id}/edit', [AdminPanduan::class, 'edit'])->name('edit');
            Route::put('/{id}',      [AdminPanduan::class, 'update'])->name('update');
            Route::delete('/{id}',   [AdminPanduan::class, 'destroy'])->name('destroy');
        });

        // ── Wilayah ───────────────────────────────────────────────────
        Route::get('/wilayah', [AdminWilayah::class, 'index'])->name('wilayah');

        // ── Backup (SUPER_ADMIN only) ─────────────────────────────────
        Route::prefix('backup')->name('backup.')->middleware('role:super_admin')->group(function () {
            Route::get('/',        [AdminBackup::class, 'index'])->name('index');
            Route::post('/export', [AdminBackup::class, 'export'])->name('export');
            Route::post('/import', [AdminBackup::class, 'import'])->name('import');
        });

        // ── Audit Log ─────────────────────────────────────────────────
        Route::get('/audit-log', [AdminAuditLog::class, 'index'])->name('audit-log');

        // ── Export ────────────────────────────────────────────────────
        Route::prefix('export')->name('export.')->group(function () {
            // Halaman UI export (filter periode + kecamatan)
            Route::get('/',                [AdminExport::class, 'index'])->name('index');
            // Alias: /admin/export/assessment → halaman UI yang sama
            Route::get('/assessment',      [AdminExport::class, 'index'])->name('assessment');
            // Download template assessment (dengan query param ?assessment_id=)
            Route::get('/template',        [AdminExport::class, 'downloadTemplate'])->name('template');
            // Download backup data lengkap (snapshot per kecamatan)
            Route::get('/snapshot',        [AdminExport::class, 'snapshot'])->name('snapshot');
            // Download rekap status akhir semua kecamatan
            Route::get('/self-assessment', [AdminExport::class, 'selfAssessment'])->name('self-assessment');
            // Backfill: generate AssessmentBackup dari semua data yang sudah VALIDATED
            Route::post('/backfill-snapshots', [AdminExport::class, 'backfillSnapshots'])->name('backfill-snapshots');
        });
    });

// ============================================================
//  VALIDATOR
// ============================================================

Route::prefix('validator')
    ->middleware(['auth', 'role:validator'])
    ->name('validator.')
    ->group(function () {

        // Redirect /validator → /validator/dashboard
        Route::get('/', fn () => redirect()->route('validator.dashboard'));

        Route::get('/dashboard', [ValidatorDashboard::class, 'index'])->name('dashboard');

        // Validasi self-assessment kecamatan
        Route::prefix('validasi')->name('validasi.')->group(function () {
            Route::get('/',          [ValidatorValidation::class, 'index'])->name('index');
            Route::get('/{id}',      [ValidatorValidation::class, 'show'])->name('show');
            Route::post('/{id}',     [ValidatorValidation::class, 'store'])->name('store');
            Route::put('/{id}',      [ValidatorValidation::class, 'update'])->name('update');
        });
    });

// ============================================================
//  KECAMATAN  (role USER di DB)
// ============================================================

Route::prefix('kecamatan')
    ->middleware(['auth', 'role:kecamatan'])
    ->name('kecamatan.')
    ->group(function () {

        // Redirect /kecamatan → /kecamatan/dashboard
        Route::get('/', fn () => redirect()->route('kecamatan.dashboard'));

        Route::get('/dashboard', [KecamatanDashboard::class, 'index'])->name('dashboard');

        // ── Assessment ────────────────────────────────────────────────
        Route::prefix('assessment')->name('assessment.')->group(function () {
            Route::get('/',          [KecamatanAssessment::class, 'index'])->name('index');
            Route::get('/{id}',      [KecamatanAssessment::class, 'show'])->name('show');
            Route::post('/{id}',     [KecamatanAssessment::class, 'submit'])->name('submit');
            Route::put('/{id}',      [KecamatanAssessment::class, 'update'])->name('update');
            // Draft auto-save
            Route::post('/{id}/draft', [KecamatanAssessment::class, 'saveDraft'])->name('draft');
        });

        // ── Hasil ─────────────────────────────────────────────────────
        Route::get('/hasil', [KecamatanHasil::class, 'index'])->name('hasil');

        // ── Statistik ─────────────────────────────────────────────────
        Route::get('/statistik', [KecamatanStatistik::class, 'index'])->name('statistik');

        // ── Laporan ───────────────────────────────────────────────────
        Route::get('/laporan', [KecamatanLaporan::class, 'index'])->name('laporan');

        // ── Panduan ───────────────────────────────────────────────────
        Route::get('/panduan', [KecamatanPanduan::class, 'index'])->name('panduan');
    });
