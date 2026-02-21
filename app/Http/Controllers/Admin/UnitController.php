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

        $units = Unit::with('make')
            ->when($search, function ($query, $search) {
                $query->where('plate_number', 'like', "%{$search}%")
                      ->orWhere('motor_number', 'like', "%{$search}%")
                      ->orWhere('chassis_number', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(4)
            ->withQueryString();

        $unitMakes = UnitMake::orderBy('name')->get();

        return Inertia::render('Admin/Units/Index', [
            'units' => $units,
            'unitMakes' => $unitMakes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'make_id' => 'required|exists:unit_makes,id',
            'plate_number' => 'required|string|unique:units,plate_number',
            'motor_number' => 'required|string|unique:units,motor_number',
            'chassis_number' => 'required|string|unique:units,chassis_number',
            'model_year' => 'required|digits:4|integer|min:1900|max:'.(date('Y')+1),
            'cr_number' => 'nullable|string',
            'unit_front_photo' => 'nullable|image|max:2048',
            'unit_back_photo' => 'nullable|image|max:2048',
            'unit_left_photo' => 'nullable|image|max:2048',
            'unit_right_photo' => 'nullable|image|max:2048',
        ]);

        // Handle File Uploads
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
            'cr_number' => 'nullable|string',
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
                unset($validated[$photo]); // Don't overwrite if no new file
            }
        }

        $unit->update($validated);

        return redirect()->back()->with('success', 'Unit updated successfully.');
    }
}