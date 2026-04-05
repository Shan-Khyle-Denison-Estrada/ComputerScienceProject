<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Inertia\Inertia;

class ApplicationNewDriverShowController extends Controller
{
    public function show(Application $application)
    {
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