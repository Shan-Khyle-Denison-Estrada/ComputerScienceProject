<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your email for Franchise Application',
        );
    }

    public function content(): Content
    {
        $currentYear = date('Y');

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
                        
                        <table width='100%' cellpadding='0' cellspacing='0' style='max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin: 20px auto;'>
                            
                            <tr>
                                <td style='background-color: #2563eb; padding: 30px; text-align: center;'>
                                    <h2 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 0.5px;'>Franchise Application</h2>
                                </td>
                            </tr>
                            
                            <tr>
                                <td style='padding: 40px 30px; text-align: center;'>
                                    <h3 style='margin-top: 0; color: #1f2937; font-size: 20px;'>Verify Your Email Address</h3>
                                    
                                    <p style='color: #4b5563; font-size: 16px; line-height: 1.6; margin-bottom: 30px;'>
                                        You are receiving this email because you initiated a new franchise application. Please use the verification code below to proceed.
                                    </p>
                                    
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin: 0 auto 30px auto; max-width: 300px;'>
                                        <tr>
                                            <td style='background-color: #eff6ff; border: 2px dashed #93c5fd; border-radius: 8px; padding: 24px; text-align: center;'>
                                                <p style='margin: 0 0 10px 0; color: #1d4ed8; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>Your Verification Code</p>
                                                <h1 style='margin: 0; font-size: 42px; letter-spacing: 8px; color: #1e40af;'>{$this->otp}</h1>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <p style='color: #6b7280; font-size: 14px; margin-bottom: 0; line-height: 1.5;'>
                                        This code is valid for <strong>10 minutes</strong>. <br>If you did not request this, you can safely ignore and delete this email.
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <td style='background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;'>
                                    <p style='color: #9ca3af; font-size: 12px; margin: 0;'>
                                        &copy; {$currentYear} Franchise Management System. All rights reserved.
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