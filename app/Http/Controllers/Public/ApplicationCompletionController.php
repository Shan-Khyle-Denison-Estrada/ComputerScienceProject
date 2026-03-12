<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\EvaluationRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApplicationCompletionController extends Controller
{
    /**
     * Display the document upload page for the applicant.
     */
    public function edit(Application $application)
    {
        // Prevent accessing if already submitted
        if ($application->status !== 'Initial') {
            abort(403, 'This application has already been submitted or is currently being processed.');
        }

        // Fetch requirements specific to this application type
        $requirements = EvaluationRequirement::where('group', $application->application_type)
            ->where('is_active', true)
            ->get();

        return Inertia::render('CompleteApplication', [
            'application' => $application,
            'requirements' => $requirements,
        ]);
    }

    /**
     * Handle the file uploads and finalize the application.
     */
    public function update(Request $request, Application $application)
    {
        if ($application->status !== 'Initial') {
            abort(403, 'This application has already been submitted.');
        }

        // Validate that documents were uploaded
        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB per file
        ]);

        DB::beginTransaction();
        try {
            // Save each uploaded document
            foreach ($request->file('documents', []) as $requirementId => $file) {
                $path = $file->store('applications/requirements', 'public');
                
                // Assuming your ApplicationEvaluation model has these columns
                $application->evaluations()->create([
                    'requirement_id' => $requirementId,
                    'file_path' => $path,
                    'remarks' => null
                ]);
            }

            // Update application status to kick off the internal evaluation process
            $application->update([
                'status' => 'Pending',
                'submitted_at' => now(),
            ]);

            DB::commit();

            // Redirect them to a success page (or back to your main public page)
            return redirect('/')->with('success', 'Your application requirements have been submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to upload documents: ' . $e->getMessage()]);
        }
    }
}