<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

/**
 * KecamatanLoginController
 *
 * Thin controller untuk route /kecamatan/login.
 * Semua logic ada di AuthenticatedSessionController::createKecamatan(),
 * storeKecamatan(), dan destroyKecamatan().
 *
 * Digunakan oleh route:
 *   GET  /kecamatan/login  → createKecamatan()
 *   POST /kecamatan/login  → storeKecamatan()
 *   POST /kecamatan/logout → destroyKecamatan()
 */
class KecamatanLoginController extends Controller
{
    //
}
