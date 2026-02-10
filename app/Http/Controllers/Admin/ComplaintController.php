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

        if ($request->input('search')) {
            $search = $request->input('search');
            $query->where('id', 'like', "%{$search}%")
                  ->orWhere('nature_of_complaint', 'like', "%{$search}%")
                  ->orWhere('complainant_contact', 'like', "%{$search}%")
                  ->orWhereHas('franchise', function($q) use ($search) {
                      $q->where('id', 'like', "%{$search}%");
                  });
        }

        return Inertia::render('Admin/Complaints/Index', [
            'complaints' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search'])
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