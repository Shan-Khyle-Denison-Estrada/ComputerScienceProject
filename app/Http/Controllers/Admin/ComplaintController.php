<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Franchise;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    // Admin List View
public function index(Request $request)
    {
        $query = Complaint::with(['franchise.currentActiveUnit.newUnit']);

        // 1. Filter by Search (ID, Nature, Contact, Franchise ID)
        if ($request->input('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('nature_of_complaint', 'like', "%{$search}%")
                  ->orWhere('complainant_contact', 'like', "%{$search}%")
                  ->orWhereHas('franchise', function($fq) use ($search) {
                      $fq->where('id', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filter by Status
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        // 3. Filter by Nature
        if ($request->input('nature')) {
            $query->where('nature_of_complaint', $request->input('nature'));
        }

        // Get distinct natures for the dropdown filter
        $natures = Complaint::select('nature_of_complaint')
            ->distinct()
            ->orderBy('nature_of_complaint')
            ->pluck('nature_of_complaint');

        return Inertia::render('Admin/Complaints/Index', [
            'complaints' => $query->latest()->paginate(10)->withQueryString(),
            'natures' => $natures, // Pass distinct natures to view
            'filters' => $request->only(['search', 'status', 'nature'])
        ]);
    }

    // Store Complaint (Public or Admin)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'required|exists:franchises,id',
            'nature_of_complaint' => 'required|string',
            'remarks' => 'nullable|string',
            'fare_collected' => 'nullable|numeric',
            'pick_up_point' => 'nullable|string',
            'drop_off_point' => 'nullable|string',
            'complainant_contact' => 'required|string',
            'incident_date' => 'required|date',
            'incident_time' => 'required',
        ]);

        Complaint::create($validated);

        return back()->with('success', 'Complaint submitted successfully.');
    }
}