<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\UnitMake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $makeId = $request->input('make_id');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        $units = Unit::with('make')
            // 1. Handle Search
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'like', "%{$search}%")
                      ->orWhere('motor_number', 'like', "%{$search}%")
                      ->orWhere('chassis_number', 'like', "%{$search}%");
                });
            })
            // 2. Handle Filter
            ->when($makeId, function ($query, $makeId) {
                $query->where('make_id', $makeId);
            })
            // 3. Handle Sorting
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                if ($sortField === 'make') {
                    // This sorts alphabetically by the Make/Brand Name (e.g. Honda, Kawasaki)
                    $query->join('unit_makes', 'units.make_id', '=', 'unit_makes.id')
                          ->orderBy('unit_makes.name', $sortDirection)
                          ->select('units.*'); // Avoid ID collision
                } else {
                    // Added model_year to allowed sorts
                    $allowedSorts = ['plate_number', 'model_year'];
                    if (in_array($sortField, $allowedSorts)) {
                        $query->orderBy($sortField, $sortDirection);
                    }
                }
            }, function ($query) {
                // Default fallback
                $query->latest();
            })
            ->paginate(6)
            ->withQueryString();

        $unitMakes = UnitMake::orderBy('name')->get();

        return Inertia::render('Admin/Units/Index', [
            'units' => $units,
            'unitMakes' => $unitMakes,
            'filters' => $request->only(['search', 'make_id', 'sortField', 'sortDirection']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'make_id' => 'required|exists:unit_makes,id',
            'plate_number' => 'required|string|unique:units,plate_number',
            'motor_number' => 'required|string|unique:units,motor_number',
            'chassis_number' => 'required|string|unique:units,chassis_number',
            'model_year' => 'required|digits:4|integer',
            'unit_front_photo' => 'nullable|image|max:2048',
            'unit_back_photo' => 'nullable|image|max:2048',
            'unit_left_photo' => 'nullable|image|max:2048',
            'unit_right_photo' => 'nullable|image|max:2048',
        ]);

        $photos = ['unit_front_photo', 'unit_back_photo', 'unit_left_photo', 'unit_right_photo'];
        foreach ($photos as $photo) {
            if ($request->hasFile($photo)) {
                $validated[$photo] = $request->file($photo)->store('units', 'public');
            }
        }

        Unit::create($validated);

        return redirect()->back()->with('success', 'Unit registered successfully.');
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'make_id' => 'required|exists:unit_makes,id',
            'plate_number' => 'required|string|unique:units,plate_number,' . $unit->id,
            'motor_number' => 'required|string|unique:units,motor_number,' . $unit->id,
            'chassis_number' => 'required|string|unique:units,chassis_number,' . $unit->id,
            'model_year' => 'required|digits:4|integer',
            'unit_front_photo' => 'nullable|image|max:2048',
            'unit_back_photo' => 'nullable|image|max:2048',
            'unit_left_photo' => 'nullable|image|max:2048',
            'unit_right_photo' => 'nullable|image|max:2048',
        ]);

        // Handle File Replacements
        $photos = ['unit_front_photo', 'unit_back_photo', 'unit_left_photo', 'unit_right_photo'];
        foreach ($photos as $photo) {
            if ($request->hasFile($photo)) {
                // Delete old
                if ($unit->$photo) {
                    Storage::disk('public')->delete($unit->$photo);
                }
                $validated[$photo] = $request->file($photo)->store('units', 'public');
            } else {
                unset($validated[$photo]); // don't overwrite if null
            }
        }

        $unit->update($validated);

        return redirect()->back()->with('success', 'Unit updated successfully.');
    }
}