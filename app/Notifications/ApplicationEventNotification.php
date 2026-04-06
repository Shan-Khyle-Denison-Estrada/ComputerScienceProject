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
                default               => route('admin.applications.show', $this->application->id),
            };
        }

        // Fallback
        return route('dashboard');
    }
}