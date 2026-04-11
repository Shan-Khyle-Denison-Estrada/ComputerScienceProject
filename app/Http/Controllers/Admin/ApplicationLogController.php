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
        $search = $request->input('search');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        $logs = ApplicationLog::query()
            ->select('application_logs.*') // Explicitly select to prevent ID clashes during joins
            ->with(['application', 'user'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('application_logs.log_no', 'like', "%{$search}%")
                      ->orWhere('application_logs.action', 'like', "%{$search}%")
                      ->orWhere('application_logs.details', 'like', "%{$search}%")
                      ->orWhereHas('application', function ($aq) use ($search) {
                          $aq->where('reference_number', 'like', "%{$search}%")
                             ->orWhere('application_type', 'like', "%{$search}%");
                      })
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                      });
                });
            })
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                if ($sortField === 'reference_number' || $sortField === 'application_type') {
                    $query->leftJoin('applications', 'application_logs.application_id', '=', 'applications.id')
                          ->orderBy('applications.' . $sortField, $sortDirection);
                } elseif ($sortField === 'user_name') {
                    $query->leftJoin('users', 'application_logs.user_id', '=', 'users.id')
                          ->orderBy('users.first_name', $sortDirection)
                          ->orderBy('users.last_name', $sortDirection);
                } else {
                    $allowedSorts = ['log_no', 'created_at', 'action'];
                    if (in_array($sortField, $allowedSorts)) {
                        $query->orderBy('application_logs.' . $sortField, $sortDirection);
                    }
                }
            }, function ($query) {
                $query->orderBy('application_logs.created_at', 'desc');
            })
            ->paginate(6)
            ->withQueryString()
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
            'filters' => $request->only(['search', 'sortField', 'sortDirection']),
        ]);
    }
}