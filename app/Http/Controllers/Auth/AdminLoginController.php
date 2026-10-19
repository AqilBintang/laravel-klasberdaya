<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

/**
 * AdminLoginController
 *
 * Thin controller untuk route /admin/login.
 * Semua logic ada di AuthenticatedSessionController::createAdmin(),
 * storeAdmin(), dan destroyAdmin().
 *
 * Digunakan oleh route:
 *   GET  /admin/login  → createAdmin()
 *   POST /admin/login  → storeAdmin()
 *   POST /admin/logout → destroyAdmin()
 */
class AdminLoginController extends Controller
{
    //
}
