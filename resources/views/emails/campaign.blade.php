<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $mailSubject }}</title>
    <!--[if mso]><style>table,td{font-family:Arial,sans-serif!important}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">

    {{-- Preheader (hidden preview text) --}}
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
        {{ Str::limit(strip_tags($mailBody), 120) }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#1e3a8a 0%,#1d4ed8 45%,#4338ca 100%);border-radius:16px 16px 0 0;padding:28px 32px;text-align:center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        {{-- Orange house icon (hosted PNG for email client compat) --}}
                                        <img src="{{ url('/img/email-logo.png') }}" alt="BusyRealtor" width="44" height="44" style="display:block;margin:0 auto 14px;border-radius:11px;border:0;" />
                                        <h1 style="margin:0;font-size:24px;font-weight:800;color:#ffffff;letter-spacing:-0.3px;">Busy<span style="color:#fdba74;">Realtor</span></h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Orange accent bar --}}
                    <tr>
                        <td style="background:linear-gradient(90deg,#f97316,#fb923c,#f97316);height:3px;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:36px 32px 32px;">
                            <div style="font-size:15px;line-height:1.7;color:#374151;">
                                {!! nl2br(e($mailBody)) !!}
                            </div>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 32px;">
                            <div style="border-top:2px solid #fed7aa;"></div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#ffffff;border-radius:0 0 16px 16px;padding:20px 32px 28px;text-align:center;">
                            <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">
                                BusyRealtor &mdash; The platform for busy realtors
                            </p>
                            <p style="margin:0;font-size:12px;color:#9ca3af;">
                                <a href="{{ url('/') }}" style="color:#f97316;text-decoration:none;font-weight:600;">busyrealtor.com</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Sub-footer --}}
                    <tr>
                        <td style="padding:20px 16px;text-align:center;">
                            <p style="margin:0;font-size:11px;color:#9ca3af;line-height:1.5;">
                                You're receiving this because you have an account on BusyRealtor.<br>
                                <a href="{{ url('/email/unsubscribe/' . $unsubscribeToken) }}" style="color:#9ca3af;text-decoration:underline;">Unsubscribe</a>
                                &middot; &copy; {{ date('Y') }} Punchlist Labs
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
