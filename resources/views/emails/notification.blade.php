@php
    $appUrl = rtrim(config('app.url'), '/');
    $logoUrl = $appUrl.'/images/aretia-logo.png';
    $accent = $accent ?? 'primary';

    $accentMap = [
        'primary' => ['#4f46e5', '#6366f1', '#eef2ff', '#4338ca'],
        'success' => ['#059669', '#10b981', '#ecfdf5', '#047857'],
        'danger'  => ['#dc2626', '#ef4444', '#fef2f2', '#b91c1c'],
        'warning' => ['#d97706', '#f59e0b', '#fffbeb', '#b45309'],
        'info'    => ['#2563eb', '#3b82f6', '#eff6ff', '#1d4ed8'],
    ];
    [$accentColor, $accentLight, $accentSoft, $accentDark] = $accentMap[$accent] ?? $accentMap['primary'];

    $year = date('Y');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $subject ?? 'Aretia' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5fb;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;-webkit-font-smoothing:antialiased;">
    <div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:#f4f5fb;">
        {{ $preheader ?? ($intro ?? 'Update from Aretia') }}
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f5fb;background:linear-gradient(135deg,#f4f5fb 0%,#eef2ff 100%);">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;">
                    {{-- Top accent bar --}}
                    <tr>
                        <td style="height:4px;background:linear-gradient(90deg,{{ $accentColor }} 0%,{{ $accentLight }} 100%);border-radius:14px 14px 0 0;line-height:4px;font-size:0;">&nbsp;</td>
                    </tr>

                    {{-- Header / Brand --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:28px 36px 20px;border-left:1px solid #e8ecf4;border-right:1px solid #e8ecf4;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        <a href="{{ $appUrl }}" style="text-decoration:none;display:inline-block;">
                                            <img src="{{ $logoUrl }}" alt="Aretia" width="140" style="display:block;border:0;outline:none;max-width:140px;height:auto;">
                                        </a>
                                    </td>
                                    <td align="right" style="vertical-align:middle;font-size:12px;color:#64748b;font-weight:500;letter-spacing:0.04em;text-transform:uppercase;">
                                        Due Diligence Portal
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:8px 36px 8px;border-left:1px solid #e8ecf4;border-right:1px solid #e8ecf4;">
                            @isset($eyebrow)
                                <div style="display:inline-block;padding:5px 12px;background-color:{{ $accentSoft }};color:{{ $accentDark }};border-radius:999px;font-size:11px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:14px;">
                                    {{ $eyebrow }}
                                </div>
                            @endisset

                            @isset($title)
                                <h1 style="margin:0 0 12px;font-size:24px;line-height:1.3;font-weight:700;color:#0f172a;letter-spacing:-0.01em;">{{ $title }}</h1>
                            @endisset

                            @isset($greeting)
                                <p style="margin:0 0 14px;font-size:15px;line-height:1.55;color:#0f172a;">{{ $greeting }}</p>
                            @endisset

                            @isset($intro)
                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">{!! $intro !!}</p>
                            @endisset

                            @isset($lines)
                                @foreach($lines as $line)
                                    <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;">{!! $line !!}</p>
                                @endforeach
                            @endisset

                            @isset($highlights)
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:18px 0 22px;background-color:{{ $accentSoft }};border:1px solid {{ $accentColor }}1f;border-radius:12px;">
                                    <tr>
                                        <td style="padding:16px 20px;">
                                            @foreach($highlights as $label => $value)
                                                <div style="display:block;margin-bottom:8px;">
                                                    <span style="display:block;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:{{ $accentDark }};margin-bottom:2px;">{{ $label }}</span>
                                                    <span style="display:block;font-size:14px;font-weight:600;color:#0f172a;">{!! $value !!}</span>
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                </table>
                            @endisset

                            @isset($cta_url)
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 8px;">
                                    <tr>
                                        <td align="center" style="background-color:{{ $accentColor }};background:linear-gradient(135deg,{{ $accentColor }} 0%,{{ $accentLight }} 100%);border-radius:10px;box-shadow:0 6px 18px {{ $accentColor }}33;">
                                            <a href="{{ $cta_url }}" target="_blank" style="display:inline-block;padding:13px 28px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;letter-spacing:0.01em;line-height:1;">
                                                {{ $cta_label ?? 'Open Portal' }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:6px 0 0;font-size:12px;color:#94a3b8;line-height:1.5;">
                                    If the button does not work, copy this link into your browser:<br>
                                    <a href="{{ $cta_url }}" style="color:{{ $accentColor }};word-break:break-all;text-decoration:none;">{{ $cta_url }}</a>
                                </p>
                            @endisset

                            @isset($outro)
                                <p style="margin:22px 0 0;font-size:14px;line-height:1.6;color:#475569;">{!! $outro !!}</p>
                            @endisset
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:22px 36px 0;border-left:1px solid #e8ecf4;border-right:1px solid #e8ecf4;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr><td style="border-top:1px solid #e8ecf4;line-height:1px;font-size:0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Signature --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:18px 36px 24px;border-left:1px solid #e8ecf4;border-right:1px solid #e8ecf4;border-radius:0 0 14px 14px;">
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                                Warm regards,<br>
                                <strong style="color:#0f172a;">The Aretia Team</strong>
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:22px 16px 8px;">
                            <p style="margin:0 0 6px;font-size:12px;color:#64748b;line-height:1.55;">
                                You received this email because you have an Aretia account.
                            </p>
                            <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.55;">
                                &copy; {{ $year }} Aretia &middot; Confidential due diligence platform
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
