<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef0f3;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
@php
    if (!function_exists('renderPlatformEmailBody')) {
    function renderPlatformEmailBody(string $raw): string {
        $lines  = explode("\n", $raw);
        $output = '';
        $kvRows = [];

        $flushKv = function () use (&$kvRows, &$output) {
            if (empty($kvRows)) return;
            $output .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;border-collapse:collapse;">';
            foreach ($kvRows as [$k, $v]) {
                $output .= '<tr>'
                    . '<td style="padding:7px 12px 7px 0;color:#6b7280;font-size:13px;font-weight:600;white-space:nowrap;vertical-align:top;width:1%;border-bottom:1px solid #f3f4f6;">' . e($k) . '</td>'
                    . '<td style="padding:7px 0 7px 12px;color:#111827;font-size:14px;vertical-align:top;border-bottom:1px solid #f3f4f6;">' . platformFormatValue($v) . '</td>'
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
                $output .= '<p style="margin:0 0 10px;color:#1f2937;font-size:15px;line-height:1.7;">' . platformFormatValue($trimmed) . '</p>';
            }
        }

        $flushKv();
        return $output;
    }

    function platformFormatValue(string $text): string {
        $text = e($text);
        $text = preg_replace(
            '/https?:\/\/[^\s<>"]+/',
            '<a href="$0" style="color:#2563eb;text-decoration:underline;">$0</a>',
            $text
        );
        return $text;
    }
    } // end function_exists
@endphp

{{-- Preheader --}}
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">{{ $subject }} &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0f3;">
  <tr>
    <td align="center" style="padding:32px 16px 40px;">

      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        {{-- Header --}}
        <tr>
          <td align="center" style="background:linear-gradient(135deg,#0f2744 0%,#1e3a5f 40%,#1d4ed8 100%);padding:34px 40px 28px;border-radius:8px 8px 0 0;">
            <span style="font-size:26px;font-weight:800;color:#ffffff;letter-spacing:0.5px;display:block;line-height:1;">BusyRealtor</span>
            <span style="font-size:11px;color:#93c5fd;letter-spacing:2px;text-transform:uppercase;display:block;margin-top:6px;">Real Estate Platform</span>
          </td>
        </tr>

        {{-- Accent stripe (orange → blue gradient) --}}
        <tr>
          <td style="height:4px;font-size:0;line-height:0;background:linear-gradient(90deg,#f97316 0%,#fb923c 30%,#3b82f6 70%,#2563eb 100%);">&nbsp;</td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="background-color:#ffffff;padding:36px 44px 32px;">

            {{-- Subject heading --}}
            <h2 style="margin:0 0 24px;font-size:19px;font-weight:700;color:#111827;line-height:1.3;border-bottom:2px solid #f97316;padding-bottom:14px;">
              {{ $subject }}
            </h2>

            {{-- Body content --}}
            <div style="color:#374151;font-size:15px;line-height:1.75;">
              {!! renderPlatformEmailBody($body) !!}
            </div>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="background-color:#f8f9fb;border-top:1px solid #e5e7eb;border-radius:0 0 8px 8px;padding:22px 44px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="color:#6b7280;font-size:12px;line-height:1.7;">
                  <strong style="color:#374151;">The BusyRealtor Team</strong>
                  <br><a href="https://busyrealtor.com" style="color:#6b7280;text-decoration:none;">busyrealtor.com</a>
                </td>
                <td align="right" style="color:#9ca3af;font-size:11px;vertical-align:bottom;">
                  &copy; {{ date('Y') }} BusyRealtor<br>
                  <span style="font-size:10px;">All rights reserved</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
