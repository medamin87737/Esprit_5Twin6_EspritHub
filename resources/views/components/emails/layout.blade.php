@props([
    'logoUrl',
    'homeUrl',
    'title' => 'NutriTrace',
    'preheader' => '',
    'eyebrow' => null,
    'heading' => '',
])
<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title }}</title>
    <!--[if mso]>
    <style type="text/css">
        table, td { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#F5F8F6;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#17231C;-webkit-font-smoothing:antialiased;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">{{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F5F8F6;padding:36px 12px;">
        <tr>
            <td align="center">
                <!--[if mso]>
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"><tr><td>
                <![endif]-->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background-color:#FFFFFF;border-radius:18px;overflow:hidden;border:1px solid #DCE6DF;box-shadow:0 12px 32px rgba(6,78,53,0.10);">

                    {{-- Header marque --}}
                    <tr>
                        <td style="background-color:#064E35;padding:0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="background-color:#064E35;padding:30px 36px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td valign="middle" width="56" style="padding-right:14px;">
                                                    <img src="{{ $logoUrl }}" width="52" height="52" alt="NutriTrace" style="display:block;border:0;border-radius:14px;width:52px;height:52px;">
                                                </td>
                                                <td valign="middle">
                                                    <div style="font-size:22px;font-weight:700;color:#FFFFFF;letter-spacing:0.3px;line-height:1.2;">NutriTrace</div>
                                                    <div style="font-size:12px;color:#FFB627;font-weight:600;margin-top:4px;letter-spacing:0.4px;">De la ferme à l'assiette</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:5px;background-color:#FFB627;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td style="padding:40px 36px 32px;">
                            @if ($eyebrow)
                                <div style="display:inline-block;background-color:#EAF5EE;color:#064E35;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;padding:7px 14px;border-radius:999px;margin-bottom:20px;border:1px solid #CDE3D4;">
                                    {{ $eyebrow }}
                                </div>
                            @endif

                            <h1 style="margin:0 0 18px;font-size:26px;line-height:1.28;color:#064E35;font-weight:700;">
                                {{ $heading }}
                            </h1>

                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Bandeau sécurité --}}
                    <tr>
                        <td style="padding:0 36px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F7F9F6;border:1px solid #E1E9E3;border-radius:12px;">
                                <tr>
                                    <td width="44" valign="top" style="padding:16px 0 16px 16px;">
                                        <div style="width:32px;height:32px;border-radius:8px;background-color:#EAF5EE;text-align:center;line-height:32px;font-size:16px;color:#064E35;font-weight:700;">✓</div>
                                    </td>
                                    <td style="padding:16px 18px 16px 10px;font-size:13px;line-height:1.55;color:#65756A;">
                                        <strong style="color:#17231C;display:block;margin-bottom:2px;">Sécurité NutriTrace</strong>
                                        Ne partagez jamais ce lien. L'équipe NutriTrace ne vous demandera jamais votre mot de passe par e-mail.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#064E35;padding:26px 36px;text-align:center;">
                            <div style="font-size:14px;font-weight:700;color:#FFFFFF;margin-bottom:8px;letter-spacing:0.2px;">NutriTrace</div>
                            <div style="font-size:12px;color:#B7DCC8;line-height:1.6;margin-bottom:12px;">
                                Traçabilité alimentaire · Analyses qualité · Empreinte carbone
                            </div>
                            <a href="{{ $homeUrl }}" target="_blank" style="display:inline-block;font-size:12px;font-weight:600;color:#FFB627;text-decoration:none;border-bottom:1px solid #FFB627;padding-bottom:1px;">
                                {{ parse_url($homeUrl, PHP_URL_HOST) ?: $homeUrl }}
                            </a>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->

                <p style="margin:20px 0 0;font-size:11px;color:#8A9A8F;text-align:center;line-height:1.5;">
                    © {{ date('Y') }} NutriTrace · Esprit Hub<br>
                    Message automatique — merci de ne pas répondre à cet e-mail.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
