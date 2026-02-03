<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Particular;
use Illuminate\Http\Request;

class ParticularController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        Particular::create($validated);

        return redirect()->back()->with('success', 'Particular added successfully.');
    }

    public function update(Request $request, Particular $particular)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $particular->update($validated);

        return redirect()->back()->with('success', 'Particular updated successfully.');
    }

    public function destroy(Particular $particular)
    {
        $particular->delete();
        return redirect()->back()->with('success', 'Particular deleted successfully.');
    }
}