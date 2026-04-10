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
        $status = $request->input('status');
        $natureId = $request->input('nature_id');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        $redFlags = RedFlag::with(['franchise', 'nature'])
            // 1. Search by Franchise Number
            ->when($search, function ($query, $search) {
                $query->whereHas('franchise', function ($q) use ($search) {
                    $q->where('franchise_number', 'like', "%{$search}%");
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
            // 4. Handle Sorting
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                if ($sortField === 'franchise') {
                    $query->join('franchises', 'red_flags.franchise_id', '=', 'franchises.id')
                          ->orderBy('franchises.franchise_number', $sortDirection)
                          ->select('red_flags.*');
                } elseif ($sortField === 'nature') {
                    $query->join('nature_of_red_flags', 'red_flags.nature_id', '=', 'nature_of_red_flags.id')
                          ->orderBy('nature_of_red_flags.name', $sortDirection)
                          ->select('red_flags.*');
                } else {
                    $allowedSorts = ['status', 'created_at'];
                    if (in_array($sortField, $allowedSorts)) {
                        $query->orderBy($sortField, $sortDirection);
                    }
                }
            }, function ($query) {
                // Default fallback
                $query->latest();
            })
            ->paginate(7)
            ->withQueryString();

        $natures = NatureOfRedFlag::all();

        return Inertia::render('Admin/RedFlags/Index', [
            'redFlags' => $redFlags,
            'natures' => $natures,
            'filters' => $request->only(['search', 'status', 'nature_id', 'sortField', 'sortDirection']),
        ]);
    }

    // Store a new Red Flag (from Franchise Show page)
    public function store(Request $request, Franchise $franchise)
    {
        // 1. Intercept "Other" and map it to a valid Nature ID
        if ($request->nature_id === 'Other') {
            $otherNature = NatureOfRedFlag::firstOrCreate(['name' => 'Other']);
            $request->merge(['nature_id' => $otherNature->id]);
        }

        // 2. Validate normally now that nature_id is guaranteed to be a valid ID
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