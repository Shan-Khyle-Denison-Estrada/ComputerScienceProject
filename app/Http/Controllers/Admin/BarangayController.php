<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;

class BarangayController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:barangays,name|max:255',
        ]);

        Barangay::create([
            'name' => Str::ucfirst(Str::lower($request->name))
        ]);

        return back();
    }

    public function update(Request $request, Barangay $barangay)
    {
        $request->validate([
            'name' => 'required|string|unique:barangays,name,' . $barangay->id . '|max:255',
        ]);

        $barangay->update([
            'name' => Str::ucfirst(Str::lower($request->name))
        ]);

        return back();
    }

    public function destroy(Barangay $barangay)
    {
        // Optional: Check if used in any zones before deleting?
        // For now, we just delete it.
        $barangay->delete();
        return back();
    }
}