<?php

namespace App\Observers;

use App\Models\Application;
use App\Models\ApplicationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApplicationLoggingObserver
{
    public function created(Application $application): void
    {
        $user = Auth::user();
        $userName = $user ? $user->full_name : 'System';
        $appType = $application->application_type ? ucwords(str_replace('_', ' ', $application->application_type)) : 'Application';
        
        $this->logAction($application, 'Created', "{$userName} submitted a new {$appType}.");
    }

    public function updated(Application $application): void
    {
        $changes = $application->getDirty();
        $user = Auth::user();
        $userName = $user ? $user->full_name : 'System';
        $appType = $application->application_type ? ucwords(str_replace('_', ' ', $application->application_type)) : 'Application';

        $logged = false;

        $roles = [
            'evaluator_status' => 'Evaluator',
            'inspector_status' => 'Inspector',
            'capo_status' => 'CAPO',
            'reviewer_status' => 'Reviewer',
            'sp_status' => 'SP',
            'tab_status' => 'TAB'
        ];

        // Check for specific role status changes first
        foreach ($roles as $field => $roleName) {
            if (array_key_exists($field, $changes)) {
                $newStatus = $changes[$field];
                $action = "{$roleName} {$newStatus}"; 
                $details = "{$userName} updated the {$roleName} status to {$newStatus} for this {$appType}.";
                
                $this->logAction($application, $action, $details);
                $logged = true; // Mark as logged to prevent double-logging the overall status
            }
        }

        // Only log overall status change if NO specific role status was changed
        // This prevents 2 logs appearing when a role approves and the overall status updates simultaneously
        if (!$logged && array_key_exists('status', $changes)) {
            $newStatus = $changes['status'];
            $action = "Status: {$newStatus}";
            $details = "{$userName} marked the overall status of this {$appType} as {$newStatus}.";
            
            $this->logAction($application, $action, $details);
        }
    }

    public function deleted(Application $application): void
    {
        $this->logAction($application, 'Deleted', 'Application was deleted.');
    }

    protected function logAction(Application $application, string $action, string $details): void
    {
        // Generates format: AUDIT-YYYYMMDD-XXXX (e.g., AUDIT-20260408-A1B2)
        $logNo = 'AUDIT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        ApplicationLog::create([
            'log_no' => $logNo,
            'application_id' => $application->id,
            // Capture the user making the action, or leave null if system generated
            'user_id' => Auth::check() ? Auth::id() : null, 
            'action' => $action,
            'details' => $details,
        ]);
    }
}