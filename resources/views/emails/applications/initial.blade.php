<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Application - TRICYSYS</title>
</head>
<body style="background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; margin: 0; padding: 0; -webkit-font-smoothing: antialiased;">
    
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <table width="100%" max-width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); max-width: 600px; margin: 0 auto;">
                    
                    <tr>
                        <td style="background-color: #0f172a; padding: 32px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 900; letter-spacing: 1px;">TRICYSYS</h1>
                            <p style="color: #94a3b8; margin: 8px 0 0 0; font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 1.5px;">Franchise Management</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #1e293b; margin: 0 0 20px 0; font-size: 22px; font-weight: 800;">Action Required: Complete Your Application</h2>
                            
                            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                Hello <strong style="color: #0f172a;">{{ $application->first_name }} {{ $application->last_name }}</strong>,
                            </p>
                            
                            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                An initial application for <strong style="color: #0f172a;">{{ $application->application_type }}</strong> has been successfully registered in our system on your behalf.
                            </p>

                            <div style="background-color: #f1f5f9; border-radius: 8px; padding: 16px; text-align: center; margin: 0 0 32px 0; border: 1px solid #e2e8f0;">
                                <p style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin: 0 0 4px 0; letter-spacing: 1px;">Application Reference Number</p>
                                <p style="color: #0f172a; font-size: 24px; font-weight: 700; font-family: monospace; margin: 0;">{{ $application->reference_number }}</p>
                            </div>

                            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 32px 0;">
                                To finalize the process, we need you to upload your required evaluation documents. Please click the secure link below to proceed. 
                                <br><span style="color: #ef4444; font-size: 14px; font-weight: 600;">Note: This secure link will expire in 7 days.</span>
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 40px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $signedUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 16px 32px; font-size: 16px; font-weight: bold; text-decoration: none; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
                                            Upload Documents & Submit
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0 0 24px 0;">

                            <p style="color: #64748b; font-size: 14px; line-height: 1.5; margin: 0;">
                                If you did not request this application or believe this was sent in error, please disregard this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #f1f5f9;">
                            <p style="color: #94a3b8; font-size: 13px; margin: 0;">
                                &copy; {{ date('Y') }} TRICYSYS Franchise Management. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>