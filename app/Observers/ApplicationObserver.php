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

        // Add future application types here...
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        // We only care if the 'status' column specifically was changed
        if ($application->isDirty('status')) {
            
            // --- NEW DRIVER STATUS CHANGES ---
            if ($application->application_type === 'New Driver') {
                
                if ($application->status === 'Approved') {
                    // Notify Encoder and Admin to finalize
                    $this->notifyRoles(['admin', 'encoder'], $application, 'approved');
                } 
                elseif ($application->status === 'Completed') {
                    $this->notifyRoles(['admin'], $application, 'completed');
                }
                elseif ($application->status === 'Rejected') {
                    // Notify the Franchise Owner (the user who made the application)
                    if ($application->user) {
                        Notification::send($application->user, new ApplicationEventNotification($application, 'rejected'));
                    }
                }
                elseif ($application->status === 'Returned') {
                    // Notify the Franchise Owner to fix their application
                    if ($application->user) {
                        Notification::send($application->user, new ApplicationEventNotification($application, 'returned'));
                    }
                }
            }

            // Add future application types here...
        }
    }

    /**
     * Helper method to fetch users (including temporary roles) and send the notification.
     */
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