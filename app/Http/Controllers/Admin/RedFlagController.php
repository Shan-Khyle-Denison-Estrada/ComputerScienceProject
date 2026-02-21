<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RedFlag;
use App\Models\NatureOfRedFlag;
use App\Models\Franchise;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RedFlagController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');      // <--- NEW
        $natureId = $request->input('nature_id'); // <--- NEW

        $redFlags = RedFlag::with(['franchise', 'nature'])
            // 1. Search by Franchise ID
            ->when($search, function ($query, $search) {
                $query->whereHas('franchise', function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%");
                });
            })
            // 2. Filter by Status
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            // 3. Filter by Nature (Type)
            ->when($natureId, function ($query, $natureId) {
                $query->where('nature_id', $natureId);
            })
            ->latest()
            ->paginate(7)
            ->withQueryString();

        $natures = NatureOfRedFlag::all();

        return Inertia::render('Admin/RedFlags/Index', [
            'redFlags' => $redFlags,
            'natures' => $natures,
            // Pass current filters back to the view so dropdowns stay selected
            'filters' => $request->only(['search', 'status', 'nature_id']), 
        ]);
    }

    // Store a new Red Flag (from Franchise Show page)
    public function store(Request $request, Franchise $franchise)
    {
        $validated = $request->validate([
            'nature_id' => 'required|exists:nature_of_red_flags,id',
            'remarks' => 'nullable|string',
        ]);

        $franchise->redFlags()->create($validated);

        return redirect()->back()->with('success', 'Red Flag added successfully.');
    }

    // Resolve a Red Flag
    public function resolve(RedFlag $redFlag)
    {
        $redFlag->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Red Flag resolved.');
    }

    // Store a new Nature Type
    public function storeNature(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        NatureOfRedFlag::create($validated);
        return redirect()->back()->with('success', 'Nature added.');
    }

    // Delete a Nature Type
    public function destroyNature(NatureOfRedFlag $nature)
    {
        $nature->delete();
        return redirect()->back()->with('success', 'Nature deleted.');
    }
}