<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationSubmittedNotification extends Notification
{
    use Queueable;

    public $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        // 1. Determine the title and message dynamically based on application_type
        $type = $this->application->application_type;
        $applicantName = "{$this->application->first_name} {$this->application->last_name}";
        $identifier = $this->application->franchise ? $this->application->franchise->franchise_number : $applicantName;

        $title = "New {$type} Application";
        
        $message = match($type) {
            'Franchise Owner Account' => "{$applicantName} has applied for a new account.",
            'Change of Unit' => "A Change of Unit request was submitted for Franchise: {$identifier}.",
            'Change of Ownership' => "A Change of Ownership request was submitted for Franchise: {$identifier}.",
            'Renewal' => "A Renewal request was submitted for Franchise: {$identifier}.",
            default => "A new {$type} application has been submitted by {$applicantName}.",
        };

        // 2. Return the payload to be saved in the database
        return [
            'application_id' => $this->application->id,
            'reference_number' => $this->application->reference_number,
            'title' => $title,
            'message' => $message,
            // Adjust this URL to point to your main application viewing route
            'url' => route('admin.applications.show', $this->application->id), 
        ];
    }
}