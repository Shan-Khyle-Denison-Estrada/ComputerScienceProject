<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Report - FY {{ $fiscal_year }}</title>
    <style>
        /* Base Styles */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            margin: 0;
            padding: 0;
            font-size: 13px;
        }

        /* Page Settings */
        @page {
            margin: 40px 50px;
        }

        /* Header Layout */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-cell {
            width: 80px;
            text-align: left;
        }
        .logo-placeholder {
            width: 60px;
            height: 60px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            text-align: center;
            line-height: 60px;
            font-weight: bold;
            color: #64748b;
            font-size: 11px;
        }
        .system-logo {
            max-width: 70px;
            max-height: 70px;
            object-fit: contain;
        }
        .title-cell {
            text-align: right;
        }
        .title-cell h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
        }
        .title-cell p {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Meta Information */
        .document-meta {
            width: 100%;
            margin-bottom: 30px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .document-meta td {
            padding: 12px 15px;
            border: none;
            font-size: 12px;
        }
        .meta-label {
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            display: block;
            margin-bottom: 3px;
        }
        .meta-value {
            color: #0f172a;
            font-weight: bold;
            font-size: 14px;
        }

        /* Section Titles */
        .section-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 6px;
            margin-top: 35px;
            margin-bottom: 15px;
        }

        /* Stats Grid */
        .stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-left: -10px; 
            margin-bottom: 10px;
        }
        .stats-table td {
            width: 50%;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6; 
            padding: 15px;
            border-radius: 4px;
        }
        
        /* Specific borders for different metric types */
        .border-emerald { border-left-color: #10b981 !important; }
        .border-rose { border-left-color: #f43f5e !important; }
        .border-amber { border-left-color: #f59e0b !important; }

        .stat-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 5px;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
            border-top: 1px solid #e2e8f0;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #fbfbfc;
        }
        .data-table .text-right {
            text-align: right;
        }
        .data-table tfoot th, .data-table tfoot td {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
            font-size: 14px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 10px;
            color: #94a3b8;
        }
        .footer-table {
            width: 100%;
        }
        .footer-table td {
            border: none;
            padding: 0;
        }
        .page-number:before {
            content: counter(page);
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($logo_base64)
                    <img src="{{ $logo_base64 }}" class="system-logo" alt="Logo">
                @else
                    <div class="logo-placeholder">LOGO</div>
                @endif
            </td>
            <td class="title-cell">
                <h1>Annual Status Report</h1>
                <p>{{ $system_name }}</p>
            </td>
        </tr>
    </table>

    <table class="document-meta">
        <tr>
            <td>
                <span class="meta-label">Fiscal Year</span>
                <span class="meta-value">{{ $fiscal_year }}</span>
            </td>
            <td>
                <span class="meta-label">Coverage Period</span>
                <span class="meta-value">{{ $date_range }}</span>
            </td>
            <td style="text-align: right;">
                <span class="meta-label">Date Generated</span>
                <span class="meta-value">{{ $report_date }}</span>
            </td>
        </tr>
    </table>

    <h2 class="section-title">Executive Summary</h2>
    
    <table class="stats-table">
        <tr>
            <td class="border-emerald">
                <div class="stat-label">Total Collections</div>
                <div class="stat-value">PHP {{ number_format($stats['revenue'], 2) }}</div>
            </td>
            <td>
                <div class="stat-label">New Franchises Created</div>
                <div class="stat-value">{{ $stats['franchises'] }}</div>
            </td>
        </tr>
    </table>
    
    <table class="stats-table" style="margin-top: 15px;">
        <tr>
            <td>
                <div class="stat-label">New Operators Added</div>
                <div class="stat-value">{{ $stats['operators'] }}</div>
            </td>
            <td class="border-rose">
                <div class="stat-label">Total Complaints Registered</div>
                <div class="stat-value">{{ $stats['complaints'] }}</div>
            </td>
        </tr>
    </table>

    <table class="stats-table" style="margin-top: 15px;">
        <tr>
            <td class="border-emerald">
                <div class="stat-label">Complaints Resolved</div>
                <div class="stat-value">{{ $stats['resolved_complaints'] }}</div>
            </td>
            <td class="border-amber">
                <div class="stat-label">Pending / Unresolved Complaints</div>
                <div class="stat-value">{{ $stats['complaints'] - $stats['resolved_complaints'] }}</div>
            </td>
        </tr>
    </table>

    <h2 class="section-title">Monthly Collection Breakdown</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Month / Year</th>
                <th class="text-right">Collection Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthly_revenue as $row)
            <tr>
                <td>{{ $row->month }}</td>
                <td class="text-right">PHP {{ number_format($row->total, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" style="text-align: center; font-style: italic; color: #94a3b8; padding: 30px;">
                    No revenue recorded for this fiscal year.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($monthly_revenue) > 0)
        <tfoot>
            <tr>
                <td style="text-transform: uppercase;">Total Collections</td>
                <td class="text-right">
                    PHP {{ number_format($stats['revenue'], 2) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>System Generated Report &bull; {{ $system_name }}</td>
                <td style="text-align: right;">Page <span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>
</html>