<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function index(): View
    {
        return view('admin.backup.index');
    }

    public function export(Request $request): RedirectResponse
    {
        // TODO: generate & stream backup snapshot
        return back()->with('success', 'Backup berhasil diekspor.');
    }

    public function import(Request $request): RedirectResponse
    {
        // TODO: import backup from uploaded file
        return back()->with('success', 'Backup berhasil diimpor.');
    }
}
