<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::first() ?? SystemSetting::create([]);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $settings = SystemSetting::first() ?? SystemSetting::create([]);

        $validated = $request->validate([
            'fiscal_year_end' => 'nullable|string',
            'surcharge_rate' => 'nullable|numeric',
            'interest_rate' => 'nullable|numeric',
            'theme_color' => 'nullable|string',
            'lgu_name' => 'nullable|string',
            'office_name' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'address' => 'nullable|string',
            'about_us' => 'nullable|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
            'faqs' => 'nullable|array',
            'ordinances' => 'nullable|array',
        ]);

        // Handle standalone image uploads
        if ($request->hasFile('lgu_logo')) {
            if ($settings->lgu_logo_path) Storage::disk('public')->delete($settings->lgu_logo_path);
            $validated['lgu_logo_path'] = $request->file('lgu_logo')->store('settings/logos', 'public');
        }

        if ($request->hasFile('office_logo')) {
            if ($settings->office_logo_path) Storage::disk('public')->delete($settings->office_logo_path);
            $validated['office_logo_path'] = $request->file('office_logo')->store('settings/logos', 'public');
        }

        if ($request->hasFile('hero_image')) {
            if ($settings->hero_image_path) Storage::disk('public')->delete($settings->hero_image_path);
            $validated['hero_image_path'] = $request->file('hero_image')->store('settings/hero', 'public');
        }

        // Handle dynamically uploaded PDFs inside the ordinances JSON array
        $ordinances = $request->input('ordinances', []);
        foreach ($ordinances as $index => &$ord) {
            // Check if there is an actual uploaded file for this specific index
            if ($request->hasFile("ordinances.{$index}.file")) {
                $path = $request->file("ordinances.{$index}.file")->store('ordinances', 'public');
                $ord['file'] = $path; // Replace File object with stored string path
            }
        }
        $validated['ordinances'] = $ordinances;

        $settings->update($validated);

        return redirect()->back()->with('success', 'System Settings successfully updated.');
    }
}