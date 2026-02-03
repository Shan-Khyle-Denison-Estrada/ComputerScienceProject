<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitMake;
use Illuminate\Http\Request;

class UnitMakeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:unit_makes,name',
            'description' => 'nullable|string|max:255',
        ]);

        UnitMake::create($request->all());

        return redirect()->back()->with('success', 'Unit Make added successfully.');
    }

    public function update(Request $request, UnitMake $unitMake)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:unit_makes,name,' . $unitMake->id,
            'description' => 'nullable|string|max:255',
        ]);

        $unitMake->update($request->all());

        return redirect()->back()->with('success', 'Unit Make updated successfully.');
    }

    public function destroy(UnitMake $unitMake)
    {
        // Optional: Check if unitMake is used by any Unit before deleting
        // For now, we rely on database constraints or cascade
        $unitMake->delete();

        return redirect()->back()->with('success', 'Unit Make deleted successfully.');
    }
}