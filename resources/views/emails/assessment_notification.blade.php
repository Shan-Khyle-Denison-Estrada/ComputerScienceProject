<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Notice of Assessment</title>
    
    @php
        // Fetch settings directly from the database
        $settings = \App\Models\SystemSetting::first();
        
        // Fallbacks in case settings are empty
        $officeName = $settings->office_name ?? 'Franchising and Regulatory Office';
        $themeColor = $settings->theme_color ?? '#dc2626'; // Defaults to Red-600
        
        // Build Logo Paths (assuming they are stored in the public disk via storage)
        $lguLogo = $settings->lgu_logo ? asset('storage/' . $settings->lgu_logo) : null;
        $officeLogo = $settings->office_logo ? asset('storage/' . $settings->office_logo) : null;

        // Intelligently figure out the applicant's name
        $applicantName = 'Applicant/Operator';
        if ($assessment->application) {
            $applicantName = $assessment->application->first_name . ' ' . $assessment->application->last_name;
        } elseif ($assessment->franchise && $assessment->franchise->currentOwnership && $assessment->franchise->currentOwnership->newOwner && $assessment->franchise->currentOwnership->newOwner->user) {
            $user = $assessment->franchise->currentOwnership->newOwner->user;
            $applicantName = $user->name ?? ($user->first_name . ' ' . $user->last_name);
        }
    @endphp

    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333; line-height: 1.6; background-color: #f4f4f5; padding: 20px; }
        .wrapper { max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }
        
        /* Dynamic Header Color */
        .header { background-color: {{ $themeColor }}; color: #ffffff; padding: 20px; text-align: center; }
        .logos { display: block; text-align: center; margin-bottom: 15px; }
        .logos img { height: 70px; width: auto; margin: 0 10px; display: inline-block; vertical-align: middle; }
        
        .header h2 { margin: 0; font-size: 22px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0 0; font-size: 14px; opacity: 0.9; }
        
        .content { padding: 30px; }
        .greeting { font-size: 16px; font-weight: bold; margin-bottom: 15px; }
        
        /* Dynamic Info Box */
        .info-box { background-color: #f8fafc; border-left: 4px solid {{ $themeColor }}; padding: 15px; margin-bottom: 25px; border-radius: 0 4px 4px 0; }
        .info-box p { margin: 5px 0; font-size: 14px; }
        
        /* Table Styling */
        .particulars { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 25px; }
        .particulars th { background-color: #f1f5f9; color: #475569; padding: 12px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
        .particulars td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .particulars .total-row td { font-weight: bold; font-size: 16px; border-top: 2px solid #94a3b8; border-bottom: none; }
        
        .amount-highlight { color: #b45309; font-weight: bold; }
        .text-right { text-align: right; }
        
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logos">
                @if($lguLogo)
                    <img src="{{ $lguLogo }}" alt="LGU Logo">
                @endif
                @if($officeLogo)
                    <img src="{{ $officeLogo }}" alt="Office Logo">
                @endif
            </div>
            <h2>Notice of Assessment</h2>
            <p>{{ $officeName }}</p>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $applicantName }},</p>
            <p>This is an official notification that a new assessment has been generated for your transaction. Please find the details of your assessment below:</p>

            <div class="info-box">
                <p><strong>Assessment No:</strong> ASM-{{ str_pad($assessment->id, 6, '0', STR_PAD_LEFT) }}</p>
                @if($assessment->application)
                    <p><strong>Application Ref:</strong> {{ $assessment->application->reference_number }}</p>
                @endif
                <p><strong>Date Issued:</strong> {{ \Carbon\Carbon::parse($assessment->assessment_date)->format('F d, Y') }}</p>
            </div>

            <table class="particulars">
                <thead>
                    <tr>
                        <th>Particular / Description</th>
                        <th class="text-right">Amount (PHP)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assessment->particulars as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td class="text-right">{{ number_format($item->pivot->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td class="text-right">Total Amount Due:</td>
                        <td class="text-right amount-highlight">{{ number_format($assessment->total_amount_due, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <p>Please proceed to the designated payment center or treasury office to settle the total amount due to continue processing your transaction.</p>
            
            <p>Thank you for your prompt compliance.</p>
            <br>
            <p>Respectfully,<br><strong>{{ $officeName }}</strong></p>
        </div>

        <div class="footer">
            <p>This is an automatically generated message from the {{ $officeName }} System. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} {{ $officeName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>