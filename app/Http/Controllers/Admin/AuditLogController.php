<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest('created_at');

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('ip_address', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->whereLike('name', "%{$search}%"))
                    ->orWhereHas('user', fn ($uq) => $uq->whereLike('email', "%{$search}%"));
            });
        }

        $logs = $query->paginate(20)->withQueryString();
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs.index', compact('logs', 'actions'));
    }
}
