<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use Inertia\Inertia;

class ApplicationChangeOfUnitShowController extends Controller
{
    public function show(Application $application)
    {
        // Optional but recommended safety check
        abort_if($application->application_type !== 'Change of Unit', 404);

        // Load all the necessary relationships for this specific view
        $application->load([
            'user',
            'franchise.currentActiveUnit.newUnit.make', 
            'franchise.zone', // Fetch the zone details for the franchise
            'franchise.assessments', // Required to calculate the 'status' attribute accurately
            'zone',
            'proposedUnits.make', 
            'evaluations.requirement' 
        ]);

        // Fetch all inspection items to rate against
        $inspectionItems = InspectionItem::all();

        return Inertia::render('Admin/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => $inspectionItems
        ]);
    }
}