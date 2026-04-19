<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str; // Import Str helper

class ZoneController extends Controller
{
    public function index(Request $request)
    {
        $query = Zone::query();

        if ($request->search) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Fetch the system settings to get the configured office address
        $settings = \App\Models\SystemSetting::first();
        $adminAddress = $settings ? $settings->address : '';

        // Fetch used colors to exclude them from frontend suggestions
        $usedColors = Zone::pluck('color')->map(fn($color) => Str::ucfirst(Str::lower($color)))->toArray();

        return Inertia::render('Admin/Zones/Index', [
            'zones' => $query->latest()->paginate(6)->withQueryString(),
            'filters' => $request->only(['search']),
            'adminAddress' => $adminAddress, // Pass the configured address instead
            'usedColors' => $usedColors, // Pass the used colors array
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^(?=[MDCLXVI])M*(C[MD]|D?C{0,3})(X[CL]|L?X{0,3})(I[XV]|V?I{0,3})$/i',
                'unique:zones,description'
            ],
            'color' => 'required|string|max:50|unique:zones,color',
            'coverage' => 'array',
            'coverage.*' => 'string'
        ], [
            'description.regex' => 'The zone description must be a valid Roman numeral (e.g., I, II, III, IV).',
            'description.unique' => 'This Zone description is already taken. It must be unique.'
        ]);

        // Apply Sentence Case (e.g., "red" -> "Red", "downtown zone" -> "Downtown zone")
        // We lowercase first to ensure uniform formatting, then capitalize the first letter.
        $validated['description'] = Str::upper($validated['description']);
        $validated['color'] = Str::ucfirst(Str::lower($validated['color']));

        Zone::create($validated);

        return Redirect::route('admin.zones.index');
    }

    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'description' => [
                'required', 
                'string', 
                'max:255', 
                'regex:/^(?=[MDCLXVI])M*(C[MD]|D?C{0,3})(X[CL]|L?X{0,3})(I[XV]|V?I{0,3})$/i',
                'unique:zones,description,' . $zone->id
            ],
            'color' => 'required|string|max:50|unique:zones,color,' . $zone->id,
            'coverage' => 'array',
            'coverage.*' => 'string'
        ], [
            'description.regex' => 'The zone description must be a valid Roman numeral (e.g., I, II, III, IV).',
            'description.unique' => 'This Zone description is already taken. It must be unique.'
        ]);

        // Apply Sentence Case
        // Use this for ALL CAPS (e.g., "DOWNTOWN ZONE")
        $validated['description'] = \Illuminate\Support\Str::upper($validated['description']);
        $validated['color'] = Str::ucfirst(Str::lower($validated['color']));

        $zone->update($validated);

        return Redirect::route('admin.zones.index');
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();
        return Redirect::route('admin.zones.index');
    }
}