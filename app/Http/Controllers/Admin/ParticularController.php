<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Particular;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParticularController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'group' => ['nullable', 'string', Rule::in(['renewal', 'change_of_unit', 'change_of_owner', 'penalty'])],
        ]);

        // New user created particulars are never system/locked
        $validated['is_system'] = false;
        $validated['code'] = null; 

        Particular::create($validated);

        return redirect()->back()->with('success', 'Particular added successfully.');
    }

    public function update(Request $request, Particular $particular)
    {
        // PROTECTION: Prevent editing critical fields of system particulars
        if ($particular->is_system) {
             // System particulars: only allow editing name/description, NOT amount (calculated) or code
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
            ]);
        } else {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:255',
                'group' => ['nullable', 'string', Rule::in(['renewal', 'change_of_unit', 'change_of_owner', 'penalty'])],
            ]);
        }

        $particular->update($validated);

        return redirect()->back()->with('success', 'Particular updated successfully.');
    }

    public function destroy(Particular $particular)
    {
        // PROTECTION: Prevent deleting system particulars
        if ($particular->is_system) {
            return redirect()->back()->with('error', 'Cannot delete a system-defined particular.');
        }

        $particular->delete();
        return redirect()->back()->with('success', 'Particular deleted successfully.');
    }
}