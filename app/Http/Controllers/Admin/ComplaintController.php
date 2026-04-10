<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Franchise;
use App\Models\NatureOfComplaint; // Import the new model
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    // Admin List View
    public function index(Request $request)
    {
        $query = Complaint::with(['franchise.currentActiveUnit.newUnit']);

        $search = $request->input('search');
        $status = $request->input('status');
        $nature = $request->input('nature');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        // 1. Filter by Search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('nature_of_complaint', 'like', "%{$search}%")
                  ->orWhere('complainant_contact', 'like', "%{$search}%")
                  ->orWhereHas('franchise', function($fq) use ($search) {
                      $fq->where('franchise_number', 'like', "%{$search}%"); // Better search accuracy than raw ID
                  });
            });
        }

        // 2. Filter by Status
        if ($status) {
            $query->where('status', $status);
        }

        // 3. Filter by Nature
        if ($nature) {
            $query->where('nature_of_complaint', $nature);
        }

        // 4. Handle Sorting
        if ($sortField) {
            $allowedSorts = ['id', 'nature_of_complaint', 'status', 'incident_date'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->latest();
        }

        $natures = NatureOfComplaint::orderBy('name')->get();

        return Inertia::render('Admin/Complaints/Index', [
            'complaints' => $query->paginate(5)->withQueryString(),
            'natures' => $natures, 
            'filters' => $request->only(['search', 'status', 'nature', 'sortField', 'sortDirection'])
        ]);
    }

    // [!code ++] New Method: Store Nature
    public function storeNature(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:nature_of_complaints,name|max:255'
        ]);

        NatureOfComplaint::create($validated);

        return back()->with('success', 'Complaint nature added successfully.');
    }

    // [!code ++] New Method: Delete Nature
    public function destroyNature(NatureOfComplaint $nature)
    {
        $nature->delete();
        return back()->with('success', 'Complaint nature removed successfully.');
    }

    // Store Complaint (Public or Admin)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'required|exists:franchises,id',
            'nature_of_complaint' => 'required|string',
            'remarks' => 'nullable|string',
            'fare_collected' => 'required|numeric',
            'pick_up_point' => 'required|string',
            'drop_off_point' => 'required|string',
            'complainant_contact' => 'required|string',
            'incident_date' => 'required|date',
            'incident_time' => 'required',
        ]);

        Complaint::create($validated);

        return back()->with('success', 'Complaint submitted successfully.');
    }
}