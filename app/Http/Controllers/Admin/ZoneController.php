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

        // CHANGED: Get full collection (id, name) instead of just pluck('name')
        $allBarangays = Barangay::orderBy('name')->get(); 

        return Inertia::render('Admin/Zones/Index', [
            // CHANGED: Replaced get() with paginate(10)->withQueryString()
            'zones' => $query->latest()->paginate(6)->withQueryString(),
            'filters' => $request->only(['search']),
            'barangays' => $allBarangays,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'color' => 'required|string|max:50',
            'coverage' => 'array',
            'coverage.*' => 'string'
        ]);

        // Apply Sentence Case (e.g., "red" -> "Red", "downtown zone" -> "Downtown zone")
        // We lowercase first to ensure uniform formatting, then capitalize the first letter.
        $validated['description'] = Str::ucfirst(Str::lower($validated['description']));
        $validated['color'] = Str::ucfirst(Str::lower($validated['color']));

        Zone::create($validated);

        return Redirect::route('admin.zones.index');
    }

    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'color' => 'required|string|max:50',
            'coverage' => 'array',
            'coverage.*' => 'string'
        ]);

        // Apply Sentence Case
        $validated['description'] = Str::ucfirst(Str::lower($validated['description']));
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