<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationEventNotification extends Notification
{
    use Queueable;

    public $application;
    public $eventType;

    // We now pass the event type (e.g., 'created', 'approved') along with the application
    public function __construct(Application $application, string $eventType)
    {
        $this->application = $application;
        $this->eventType = $eventType;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $type = $this->application->application_type;
        $applicantName = "{$this->application->first_name} {$this->application->last_name}";
        
        // Default values
        $title = "Application Update: {$type}";
        $message = "There is an update on {$applicantName}'s {$type} application.";

        // --- NEW DRIVER MESSAGES ---
        if ($type === 'New Driver') {
            if ($this->eventType === 'created') {
                $title = "New Driver Application";
                $message = "{$applicantName} has submitted a new driver application.";
            } elseif ($this->eventType === 'approved') {
                $title = "Driver Application Approved";
                $message = "{$applicantName}'s application has been approved and is ready for encoding/finalization.";
            } elseif ($this->eventType === 'completed') {
                $title = "Driver Application Completed";
                $message = "{$applicantName}'s driver application has been finalized.";
            } elseif ($this->eventType === 'rejected') {
                $title = "Driver Application Rejected";
                $message = "Your driver application for {$applicantName} has been rejected.";
            } elseif ($this->eventType === 'returned') {
                $title = "Driver Application Returned";
                $message = "Your driver application for {$applicantName} has been returned. Please check the remarks and update.";
            }
        }

        // --- NEW FRANCHISE MESSAGES ---
        if ($type === 'New Franchise') {
            if ($this->eventType === 'created') {
                $title = "New Franchise Application";
                $message = "{$applicantName} has submitted a new franchise application.";
            } elseif ($this->eventType === 'completed_initial') { // <-- ADD THIS BLOCK
                $title = "Franchise Application Completed";
                $message = "{$applicantName} has completed and submitted their initial franchise application.";
            } elseif ($this->eventType === 'inspector_approved') {
                $title = "CAPO Approval Needed";
                $message = "Inspector has approved {$applicantName}'s application. CAPO review is now required.";
            } elseif ($this->eventType === 'ready_for_review') {
                $title = "Application Ready for Review";
                $message = "{$applicantName}'s application is fully evaluated, inspected, and CAPO-approved. Review needed.";
            } elseif ($this->eventType === 'reviewer_approved') {
                $title = "SP Approval Needed";
                $message = "Reviewer has approved {$applicantName}'s application. SP Approver action required.";
            } elseif ($this->eventType === 'sp_approved') {
                $title = "TAB Approval Needed";
                $message = "SP Approver has approved {$applicantName}'s application. TAB Approver action required.";
            } elseif ($this->eventType === 'tab_approved') {
                $title = "Application Ready for Finalization";
                $message = "TAB Approver has approved {$applicantName}'s application. It is ready to be finalized.";
            }
        }

        return [
            'application_id' => $this->application->id,
            'reference_number' => $this->application->reference_number,
            'title' => $title,
            'message' => $message,
            'url' => $this->determineUrl($notifiable, $type),
        ];
    }

    private function determineUrl($user, $type)
    {
        $roles = $user->active_roles ?? [];

        // 1. Route Encoders
        if (in_array('encoder', $roles)) {
            return match($type) {
                'New Driver' => route('admin.applications.new-driver.show', $this->application->id), // Adjust route name as needed
                default      => route('admin.applications.show', $this->application->id),
            };
        }

        // 2. Route Evaluators
        if (in_array('evaluator', $roles)) {
            return match($type) {
                'New Driver'          => route('evaluator.applications.show-new-driver', $this->application->id), // Adjust route name
                'Change of Unit'      => route('evaluator.applications.change-of-unit.show', $this->application->id), // Example
                default               => route('evaluator.applications.show', $this->application->id),
            };
        }

        // 3. Route Admins
        if (in_array('admin', $roles)) {
            return match($type) {
                'New Driver'          => route('admin.applications.new-driver.show', $this->application->id), // Adjust route name
                'Change of Unit'      => route('admin.applications.change-of-unit.show', $this->application->id),
                'Change of Ownership' => route('admin.applications.change-of-owner.show', $this->application->id),
                'Renewal'             => route('admin.applications.renewal.show', $this->application->id),
                'New Franchise' => route('admin.applications.show', $this->application->id),
                default               => route('admin.applications.show', $this->application->id),
            };
        }

        // Fallback
        return route('dashboard');
    }
}