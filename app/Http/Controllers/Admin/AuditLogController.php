<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('module')) {
            $query->where('auditable_type', 'like', "%" . $request->module . "%");
        }

        $logs = $query->paginate(30)->withQueryString();
        
        $users = \App\Models\User::orderBy('full_name')->get(['id', 'full_name']);

        return view('admin.audit-logs.index', compact('logs', 'users'));
    }

    public function show(AuditLog $log)
    {
        return view('admin.audit-logs.show', compact('log'));
    }
}
