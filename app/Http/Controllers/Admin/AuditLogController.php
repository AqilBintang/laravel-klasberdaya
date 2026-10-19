<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::with('user:id,name,email,role,kecamatan,kabupaten')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.audit-log', compact('logs'));
    }
}
