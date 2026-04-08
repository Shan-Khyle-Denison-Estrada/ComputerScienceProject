<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApplicationLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ApplicationLog::with(['application', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(6)
            ->through(function ($log) {
                return [
                    'id' => $log->id,
                    'log_no' => $log->log_no,
                    'application_id' => $log->application_id,
                    'reference_number' => $log->application->reference_number ?? 'N/A',
                    'application_type' => $log->application && $log->application->application_type 
                                            ? ucwords(str_replace('_', ' ', $log->application->application_type)) 
                                            : 'N/A',
                    'user_name' => $log->user ? $log->user->full_name : 'System',
                    'user_role' => $log->user ? ucfirst(str_replace('_', ' ', is_object($log->user->role) ? $log->user->role->value : $log->user->role)) : 'System',
                    'action' => $log->action,
                    'details' => $log->details,
                    'created_at' => $log->created_at->format('M d, Y h:i A'),
                ];
            });

        return Inertia::render('Admin/ApplicationLogs/Index', [
            'logs' => $logs,
        ]);
    }
}