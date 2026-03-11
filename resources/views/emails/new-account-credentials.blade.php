<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your TricySys Account Credentials</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; -webkit-font-smoothing: antialiased;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    
                    <tr>
                        <td style="background-color: #111827; padding: 30px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 800; letter-spacing: 1px;">
                                TRICY<span style="color: #3b82f6;">SYS</span>
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin-top: 0; font-size: 22px; color: #1f2937; margin-bottom: 16px;">Welcome to TricySys!</h2>
                            <p style="font-size: 16px; line-height: 1.6; color: #4b5563; margin-bottom: 28px;">
                                Dear <strong>{{ $user->first_name }}</strong>,<br><br>
                                An account has been successfully created for you on the Tricycle Franchise Management System.
                            </p>

                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 24px; margin-bottom: 28px;">
                                <p style="margin-top: 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700; margin-bottom: 16px;">
                                    Temporary Login Credentials
                                </p>
                                
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom: 16px;">
                                            <span style="display: block; font-size: 13px; color: #64748b; margin-bottom: 4px;">Email Address</span>
                                            <strong style="font-size: 16px; color: #0f172a;">{{ $user->email }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span style="display: block; font-size: 13px; color: #64748b; margin-bottom: 6px;">Temporary Password</span>
                                            <span style="background-color: #e2e8f0; padding: 6px 12px; border-radius: 4px; font-size: 18px; font-family: 'Courier New', Courier, monospace; font-weight: bold; color: #0f172a; letter-spacing: 2px;">{{ $password }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; margin-bottom: 36px;">
                                <p style="margin: 0; font-size: 14px; color: #991b1b; line-height: 1.5;">
                                    <strong style="display: block; margin-bottom: 4px;">⚠️ Security Requirement:</strong>
                                    You will be required to change this system-generated password immediately upon your first login.
                                </p>
                            </div>

                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ route('login') }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: 600; font-size: 16px; transition: background-color 0.2s;">
                                            Log In to Your Account
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 30px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 15px; color: #6b7280;">
                                Thank you,<br>
                                <strong style="color: #374151;">TricySys Management</strong>
                            </p>
                            <p style="margin: 20px 0 0 0; font-size: 12px; color: #9ca3af; line-height: 1.5;">
                                This is an automated email. Please do not reply directly to this message. If you did not request this account, please contact the system administrator.
                            </p>
                        </td>
                    </tr>

                </table>
                
                <p style="margin-top: 20px; font-size: 12px; color: #9ca3af; text-align: center;">
                    &copy; {{ date('Y') }} TricySys. All rights reserved.
                </p>
                
            </td>
        </tr>
    </table>
</body>
</html>