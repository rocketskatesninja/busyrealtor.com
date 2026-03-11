<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef0f3;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
@php
    $primaryColor = $settings->primary_color ?? '#2563eb';
    $siteTitle    = $settings->site_title ?? ($tenant->name ?? 'BusyRealtor');
    $contactEmail = $settings->contact_email ?? null;
    $address      = $settings->contact_address ?? null;
    $initial = strtoupper(mb_substr($siteTitle, 0, 1));

    function renderEmailBody(string $raw): string {
        $lines  = explode("\n", $raw);
        $output = '';
        $kvRows = [];

        $flushKv = function () use (&$kvRows, &$output) {
            if (empty($kvRows)) return;
            $output .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;border-collapse:collapse;">';
            foreach ($kvRows as [$k, $v]) {
                $output .= '<tr>'
                    . '<td style="padding:7px 12px 7px 0;color:#6b7280;font-size:13px;font-weight:600;white-space:nowrap;vertical-align:top;width:1%;border-bottom:1px solid #f3f4f6;">' . e($k) . '</td>'
                    . '<td style="padding:7px 0 7px 12px;color:#111827;font-size:14px;vertical-align:top;border-bottom:1px solid #f3f4f6;">' . formatBodyValue($v) . '</td>'
                    . '</tr>';
            }
            $output .= '</table>';
            $kvRows = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^[─\-=_]{4,}$/', $trimmed)) {
                $flushKv();
                $output .= '<hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0;">';
                continue;
            }

            if (preg_match('/^([A-Za-z][A-Za-z0-9 _\-]{1,24}):\s+(.+)$/', $trimmed, $m)) {
                $kvRows[] = [$m[1], $m[2]];
                continue;
            }

            $flushKv();

            if ($trimmed === '') {
                $output .= '<div style="height:12px;"></div>';
            } else {
                $output .= '<p style="margin:0 0 10px;color:#1f2937;font-size:15px;line-height:1.7;">' . formatBodyValue($trimmed) . '</p>';
            }
        }

        $flushKv();
        return $output;
    }

    function formatBodyValue(string $text): string {
        $text = e($text);
        $text = preg_replace(
            '/https?:\/\/[^\s<>"]+/',
            '<a href="$0" style="color:#2563eb;text-decoration:underline;">$0</a>',
            $text
        );
        return $text;
    }
@endphp

<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">{{ $subject }} &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0f3;">
  <tr>
    <td align="center" style="padding:32px 16px 40px;">

      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        {{-- Header --}}
        <tr>
          <td align="center" style="background-color:{{ $primaryColor }};padding:28px 40px 24px;border-radius:8px 8px 0 0;">
            <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 12px;">
              <tr>
                <td width="46" height="46" align="center"
                    style="width:46px;height:46px;border-radius:10px;background-color:rgba(255,255,255,0.2);font-size:22px;font-weight:700;color:#ffffff;line-height:46px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
                  {{ $initial }}
                </td>
              </tr>
            </table>
            <span style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.3px;display:block;line-height:1.2;">{{ $siteTitle }}</span>
          </td>
        </tr>

        {{-- Accent stripe --}}
        <tr>
          <td style="background-color:{{ $primaryColor }};height:3px;opacity:0.3;font-size:0;line-height:0;">&nbsp;</td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="background-color:#ffffff;padding:36px 44px 32px;">
            <h2 style="margin:0 0 22px;font-size:19px;font-weight:700;color:#111827;line-height:1.3;border-bottom:2px solid {{ $primaryColor }};padding-bottom:14px;">{{ $subject }}</h2>
            <div style="color:#374151;font-size:15px;line-height:1.75;">
              {!! renderEmailBody($body) !!}
            </div>
          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="background-color:#f8f9fb;border-top:1px solid #e5e7eb;border-radius:0 0 8px 8px;padding:20px 44px;">
            <p style="margin:0 0 4px;font-size:12px;color:#374151;font-weight:600;">{{ $siteTitle }}</p>
            @if($contactEmail)
            <p style="margin:0 0 2px;font-size:12px;color:#6b7280;">
              <a href="mailto:{{ $contactEmail }}" style="color:#6b7280;text-decoration:none;">{{ $contactEmail }}</a>
            </p>
            @endif
            @if($address)
            <p style="margin:0;font-size:12px;color:#6b7280;">{{ $address }}</p>
            @endif
            <p style="margin:14px 0 0;padding-top:12px;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af;">
              Powered by <a href="https://busyrealtor.com" style="color:#9ca3af;text-decoration:none;font-weight:600;">BusyRealtor</a>
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
