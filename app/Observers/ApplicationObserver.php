<?php

namespace App\Observers;

use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationEventNotification;
use Illuminate\Support\Facades\Notification;

class ApplicationObserver
{
    /**
     * Handle the Application "created" event.
     */
    public function created(Application $application): void
    {
        // --- NEW DRIVER CREATED ---
        if ($application->application_type === 'New Driver') {
            $this->notifyRoles(['admin', 'evaluator'], $application, 'created');
        }

        // --- NEW FRANCHISE ---
        if ($application->application_type === 'New Franchise' && $application->status === 'Pending') {
            $this->notifyRoles(['admin', 'evaluator', 'inspector'], $application, 'created');
        }
        // Add future application types here...
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        // --- NEW DRIVER STATUS CHANGES ---
        if ($application->application_type === 'New Driver' && $application->wasChanged('status')) {
            
            if ($application->status === 'Approved') {
                $this->notifyRoles(['admin', 'encoder'], $application, 'approved');
            } 
            elseif ($application->status === 'Completed') {
                $this->notifyRoles(['admin'], $application, 'completed');
            }
            elseif ($application->status === 'Rejected') {
                if ($application->user) {
                    Notification::send($application->user, new ApplicationEventNotification($application, 'rejected'));
                }
            }
            elseif ($application->status === 'Returned') {
                if ($application->user) {
                    Notification::send($application->user, new ApplicationEventNotification($application, 'returned'));
                }
            }
        }

        // --- NEW FRANCHISE SEQUENTIAL WORKFLOW ---
        if ($application->application_type === 'New Franchise') {
            
            // 0. Applicant completes an "Initial" application -> Notify Admin, Evaluator, Inspector
            if ($application->wasChanged('status') && 
                $application->status === 'Pending' && 
                $application->getOriginal('status') === 'Initial') {
                
                $this->notifyRoles(['admin', 'evaluator', 'inspector'], $application, 'completed_initial');
            }

            // 1. Inspector Approves -> Notify CAPO
            if ($application->wasChanged('inspector_status') && $application->inspector_status === 'Approved') {
                $this->notifyRoles(['city_anti_pollution_officer'], $application, 'inspector_approved');
            }

            // 2. Pre-Reviewer Check (Triggered if Evaluator, Inspector, or CAPO status changes)
            if ($application->wasChanged('evaluator_status') || $application->wasChanged('inspector_status') || $application->wasChanged('capo_status')) {
                $this->checkAndNotifyReviewer($application);
            }

            // 3. Reviewer Approves -> Notify SP Approver
            if ($application->wasChanged('reviewer_status') && $application->reviewer_status === 'Approved') {
                $this->notifyRoles(['sp_approver'], $application, 'reviewer_approved');
            }

            // 4. SP Approves -> Notify TAB Approver
            if ($application->wasChanged('sp_status') && $application->sp_status === 'Approved') {
                $this->notifyRoles(['tab_approver'], $application, 'sp_approved');
            }

            // 5. TAB Approves -> Notify Admin & Encoder for Finalization
            if ($application->wasChanged('tab_status') && $application->tab_status === 'Approved') {
                $this->notifyRoles(['admin', 'encoder'], $application, 'tab_approved');
            }
        }
    }

    /**
     * Helper logic to check if Reviewer is ready to be notified
     */
    public function checkAndNotifyReviewer(Application $application): void
    {
        // Must have all three approvals
        if ($application->evaluator_status === 'Approved' &&
            $application->inspector_status === 'Approved' &&
            $application->capo_status === 'Approved') {
            
            $assessment = $application->assessment;

            // Check if assessment is cleared: 
            // If there is an assessment, balance must be <= 0. If NO assessment exists, consider it cleared (true).
            $isAssessmentCleared = $assessment ? ($assessment->balance <= 0) : true;

            if ($isAssessmentCleared) {
                // Ensure we haven't already notified them by checking if reviewer_status is still Pending/null
                if (in_array($application->reviewer_status, [null, 'Pending'])) {
                    $this->notifyRoles(['reviewer'], $application, 'ready_for_review');
                }
            }
        }
    }
    private function notifyRoles(array $rolesToNotify, Application $application, string $eventType): void
    {
        $usersToNotify = User::whereIn('role', $rolesToNotify)
            ->orWhereHas('temporaryRoles', function ($query) use ($rolesToNotify) {
                $query->whereIn('role', $rolesToNotify)
                      ->where('expires_at', '>', now());
            })->get();

        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new ApplicationEventNotification($application, $eventType));
        }
    }
}