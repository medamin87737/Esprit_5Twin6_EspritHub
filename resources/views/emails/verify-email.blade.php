@php
    $logoUrl = \App\Support\EmailTemplate::logoUrl();
    $homeUrl = rtrim((string) config('app.url'), '/');
@endphp

<x-emails.layout
    :logo-url="$logoUrl"
    :home-url="$homeUrl"
    title="Vérification de l'e-mail — NutriTrace"
    preheader="Confirmez votre adresse e-mail pour activer votre compte NutriTrace."
    eyebrow="Bienvenue sur NutriTrace"
    :heading="'Bienvenue '.$name.' !'"
>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#3A4A40;">
        Merci de rejoindre NutriTrace. Pour sécuriser votre compte et accéder à toutes les fonctionnalités,
        confirmez votre adresse <strong style="color:#17231C;">{{ $email }}</strong>.
    </p>

    <p style="margin:0 0 28px;font-size:15px;line-height:1.65;color:#3A4A40;">
        Le lien de vérification est valable <strong style="color:#064E35;">{{ $expire }} minutes</strong>.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px;">
        <tr>
            <td align="center" style="border-radius:12px;background-color:#087443;">
                <a href="{{ $url }}" target="_blank" style="display:inline-block;padding:15px 32px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:12px;letter-spacing:0.2px;">
                    Vérifier mon e-mail
                </a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:#EAF5EE;border:1px solid #CDE3D4;border-radius:12px;">
        <tr>
            <td style="padding:16px 18px;font-size:13px;line-height:1.6;color:#064E35;">
                <strong style="display:block;margin-bottom:8px;font-size:14px;">Après validation, vous pourrez :</strong>
                · accéder à votre espace (admin, pro ou consommateur)<br>
                · activer la double authentification (2FA)<br>
                · gérer votre profil et votre photo
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;line-height:1.55;color:#65756A;">
        Lien alternatif :
    </p>
    <p style="margin:0;font-size:12px;line-height:1.5;word-break:break-all;">
        <a href="{{ $url }}" target="_blank" style="color:#087443;text-decoration:underline;">{{ $url }}</a>
    </p>
</x-emails.layout>
