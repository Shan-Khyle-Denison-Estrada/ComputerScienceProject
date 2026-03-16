<?php

namespace App\Http\Controllers;

use App\Models\FranchiseCertificateTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemSetting;

class CertificateTemplateController extends Controller
{
    /**
     * Show the single certificate editor.
     */
    public function edit()
    {
        $template = FranchiseCertificateTemplate::firstOrCreate(
            ['name' => 'Standard Franchise Certificate'],
            [
                'content' => '<p>Start formatting your franchise certificate here...</p>',
                'paper_size' => 'A4',
                'margins' => ['top' => 1, 'bottom' => 1, 'left' => 1, 'right' => 1],
                'author_id' => Auth::id(),
            ]
        );

        // Grab the first system setting row to get the logo
        $systemSetting = SystemSetting::first(); 

        return Inertia::render('CertificateEditor', [
            'template' => $template,
            'systemSetting' => $systemSetting // <-- Pass to Vue
        ]);
    }

    /**
     * Update the single template in the database.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'paper_size' => 'required|string',
            'margins' => 'required|array',
            'margins.top' => 'required|numeric',
            'margins.bottom' => 'required|numeric',
            'margins.left' => 'required|numeric',
            'margins.right' => 'required|numeric',
        ]);

        $validated['author_id'] = Auth::id();

        // Retrieve the first (and only) template
        $template = FranchiseCertificateTemplate::first();

        // Update it with the new layout/content
        if ($template) {
            $template->update($validated);
        }

        // Redirect back to the editor (or you could redirect to franchises.index)
        return redirect()->route('certificate-template.edit')
                         ->with('success', 'Certificate format updated successfully.');
    }
}