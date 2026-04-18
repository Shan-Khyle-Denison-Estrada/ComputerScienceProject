<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Inertia\Inertia;

class ApplicationNewDriverShowController extends Controller
{
    public function show(Application $application)
    {
        // Clear unread notifications for this specific application for the current user
        $notifications = auth()->user()->unreadNotifications
            ->where('data.application_id', $application->id);

        if ($notifications->isNotEmpty()) {
            $notifications->markAsRead();
        }

        // Load the evaluations, franchise, and zone relationships
        $application->load([
            'user', 
            'franchise.zone', 
            'zone',
            'evaluations.requirement'
        ]);

        return Inertia::render('Admin/Applications/NewDriverShow', [
            'application' => $application,
        ]);
    }
}