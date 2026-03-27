<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationOtpMail;
use App\Mail\ApplicationSubmittedMail;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\EvaluationRequirement;
use App\Models\ProposedUnit;
use App\Models\SystemSetting;
use App\Models\UnitMake;
use App\Models\Zone;
use App\Models\Assessment; // <-- ADDED
use App\Models\Particular; // <-- ADDED
use Carbon\Carbon;         // <-- ADDED
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NewFranchiseController extends Controller
{
    public function create()
    {
        $settings = SystemSetting::first();
        
        // Backend security check
        if (!$settings || !$settings->allow_new_applications) {
            abort(403, 'New franchise applications are currently closed by the administration.');
        }

        // ONLY load "New Franchise" requirements
        $relevantGroups = ['New Franchise'];
        
        $requirements = EvaluationRequirement::where('is_active', true)
            ->whereIn('group', $relevantGroups)
            ->orderBy('group')
            ->get()
            ->groupBy('group');

        return Inertia::render('NewFranchise', [
            'zones' => Zone::all(),
            'unitMakes' => UnitMake::orderBy('name')->get(),
            'requirements' => $requirements,
            'settings' => $settings,
        ]);
    }

public function store(Request $request)
    {
        $settings = SystemSetting::first();
        if (!$settings || !$settings->allow_new_applications) {
            abort(403, 'New franchise applications are currently closed by the administration.');
        }

        // ONLY validate against "New Franchise" requirements
        $relevantGroups = ['New Franchise'];
        $requiredDocs = EvaluationRequirement::where('is_active', true)
            ->whereIn('group', $relevantGroups)
            ->get();

        $rules = [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'tin_number' => 'nullable|string|max:50',
            'street_address' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            
            'units' => 'required|array|min:1',
            'units.*.zone_id' => 'required|exists:zones,id',
            'units.*.make_id' => 'required|exists:unit_makes,id',
            'units.*.model_year' => 'required|integer',
            'units.*.plate_number' => 'required|string',
            'units.*.cr_number' => 'required|string',
            'units.*.motor_number' => 'required|string',
            'units.*.chassis_number' => 'required|string',
            'units.*.unit_front_photo' => 'required|file|mimes:jpg,jpeg,png',
            'units.*.unit_back_photo' => 'required|file|mimes:jpg,jpeg,png',
            'units.*.unit_left_photo' => 'required|file|mimes:jpg,jpeg,png',
            'units.*.unit_right_photo' => 'required|file|mimes:jpg,jpeg,png',
            'units.*.cr_photo' => 'required|file|mimes:jpg,jpeg,png,pdf',
            'units.*.or_photo' => 'required|file|mimes:jpg,jpeg,png,pdf',
        ];

        // Ensure dynamically loaded requirements are validated
        foreach ($requiredDocs as $doc) {
            $rules['requirement_files.' . $doc->id] = 'required|file|mimes:jpg,jpeg,png,pdf';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $referenceNumber = 'APP-' . date('Y') . '-' . strtoupper(Str::random(6));

            $application = Application::create([
                'reference_number' => $referenceNumber,
                'application_type' => 'New Franchise',
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'],
                'tin_number' => $validated['tin_number'] ?? null,
                'street_address' => $validated['street_address'],
                'province' => $validated['province'],
                'city' => $validated['city'],
                'barangay' => $validated['barangay'],
                'status' => 'Pending',
            ]);

            foreach ($validated['units'] as $index => $unitData) {
                // Map the file uploads to match the ProposedUnit model exactly
                ProposedUnit::create([
                    'application_id' => $application->id,
                    'zone_id' => $unitData['zone_id'],
                    'make_id' => $unitData['make_id'],
                    'model_year' => $unitData['model_year'],
                    'plate_number' => $unitData['plate_number'],
                    'cr_number' => $unitData['cr_number'],
                    'motor_number' => $unitData['motor_number'],
                    'chassis_number' => $unitData['chassis_number'],
                    
                    // Fixed matching columns here:
                    'unit_front_photo' => $request->file("units.{$index}.unit_front_photo")->store('units/photos', 'public'),
                    'unit_back_photo' => $request->file("units.{$index}.unit_back_photo")->store('units/photos', 'public'),
                    'unit_left_photo' => $request->file("units.{$index}.unit_left_photo")->store('units/photos', 'public'),
                    'unit_right_photo' => $request->file("units.{$index}.unit_right_photo")->store('units/photos', 'public'),
                    'cr_photo' => $request->file("units.{$index}.cr_photo")->store('units/documents', 'public'),
                    'or_photo' => $request->file("units.{$index}.or_photo")->store('units/documents', 'public'),
                ]);
            }

            foreach ($requiredDocs as $doc) {
                $file = $request->file('requirement_files.' . $doc->id);
                ApplicationEvaluation::create([
                    'application_id' => $application->id,
                    'requirement_id' => $doc->id,
                    'file_path' => $file->store('applications/requirements', 'public'),
                    'status' => 'Pending',
                ]);
            }

            // --- AUTO-GENERATE ASSESSMENT FOR NEW FRANCHISE ---
            $particulars = Particular::where('group', 'New Franchise')->get();

            if ($particulars->isNotEmpty()) {
                $totalAmountDue = $particulars->sum('amount');
                
                // Set deadline to 15 days from submission date
                $deadlineDate = now()->addDays(15);

                $assessment = Assessment::create([
                    'application_id'    => $application->id,
                    'franchise_id'      => null, 
                    'assessment_date'   => now(),
                    'assessment_due'    => $deadlineDate, 
                    'total_amount_due'  => $totalAmountDue,
                    'assessment_status' => 'Pending',
                    'remarks'           => "Auto-generated assessment for new franchise application.",
                ]);

                foreach ($particulars as $particular) {
                    $assessment->particulars()->attach($particular->id, [
                        'quantity' => 1,
                        'subtotal' => $particular->amount
                    ]);
                }
            }
            // ---------------------------------------------------

            DB::commit();

            Mail::to($application->email)->send(
                new ApplicationSubmittedMail($referenceNumber, $application->first_name)
            );

            return back()->with('success', "Application submitted successfully! Ref No: $referenceNumber");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Submission Failed: ' . $e->getMessage()]);
        }
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $otp = rand(100000, 999999);
        Cache::put('new_franchise_otp_' . $request->email, $otp, now()->addMinutes(10));
        Mail::to($request->email)->send(new ApplicationOtpMail($otp));
        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric'
        ]);

        $cachedOtp = Cache::get('new_franchise_otp_' . $request->email);

        if ($cachedOtp && $cachedOtp == $request->otp) {
            Cache::forget('new_franchise_otp_' . $request->email);
            return response()->json(['message' => 'Email verified successfully']);
        }

        return response()->json(['message' => 'Invalid or expired verification code.'], 422);
    }
}