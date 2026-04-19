<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class ApplicationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $referenceNumber;
    public $applicantName;
    public $assessment;

    // Added $assessment as an optional parameter
    public function __construct($referenceNumber, $applicantName, $assessment = null)
    {
        $this->referenceNumber = $referenceNumber;
        $this->applicantName = $applicantName;
        $this->assessment = $assessment;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application Submitted - Reference No: ' . $this->referenceNumber,
        );
    }

    public function content(): Content
    {
        $currentYear = date('Y');
        
        // Generate Assessment HTML Block if assessment exists
        $assessmentHtml = '';
        if ($this->assessment) {
            $amountDue = number_format($this->assessment->total_amount_due, 2);
            $dueDate = Carbon::parse($this->assessment->assessment_due)->format('F d, Y');
            
            $assessmentHtml = "
            <div style='background-color: #eff6ff; padding: 20px; border-radius: 8px; margin-bottom: 24px; text-align: left; border-left: 4px solid #1e40af;'>
                <h3 style='margin-top: 0; color: #1e3a8a; font-size: 16px; margin-bottom: 12px;'>Assessment Details</h3>
                <p style='margin: 0 0 8px 0; color: #374151; font-size: 14px;'><strong>Total Amount Due:</strong> ₱{$amountDue}</p>
                <p style='margin: 0 0 12px 0; color: #374151; font-size: 14px;'><strong>Due Date:</strong> {$dueDate}</p>
                <p style='margin: 0; color: #dc2626; font-size: 12px; font-style: italic;'>
                    * Please settle this assessment before the due date to proceed with your application.
                </p>
            </div>
            ";
        }

        // Professional, mobile-responsive HTML with inline CSS
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='background-color: #f3f4f6; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; margin: 0; padding: 20px 0;'>
            
            <table width='100%' cellpadding='0' cellspacing='0' style='margin: 0; padding: 0; width: 100%;'>
                <tr>
                    <td align='center'>
                        
                        <table width='100%' cellpadding='0' cellspacing='0' style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);'>
                            
                            <tr>
                                <td style='background-color: #1e40af; padding: 30px 20px; text-align: center;'>
                                    <h2 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;'>Application Received</h2>
                                </td>
                            </tr>
                            
                            <tr>
                                <td style='padding: 40px 30px; text-align: center;'>
                                    <p style='color: #374151; font-size: 16px; margin-top: 0; margin-bottom: 24px; line-height: 1.5;'>
                                        Hi <strong>{$this->applicantName}</strong>,<br><br>
                                        Thank you for submitting your application. Your application reference number is:
                                    </p>
                                    
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 24px;'>
                                        <tr>
                                            <td align='center'>
                                                <h1 style='margin: 0; font-size: 34px; letter-spacing: 4px; color: #1e40af;'>{$this->referenceNumber}</h1>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    {$assessmentHtml}
                                    
                                    <p style='color: #6b7280; font-size: 14px; margin-bottom: 0; line-height: 1.5;'>
                                        Please save this reference number. Present this when requested.
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <td style='background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;'>
                                    <p style='color: #9ca3af; font-size: 12px; margin: 0;'>
                                        &copy; {$currentYear} TRICYSYS. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                            
                        </table>
                        
                    </td>
                </tr>
            </table>

        </body>
        </html>
        ";

        return new Content(
            htmlString: $html
        );
    }
}