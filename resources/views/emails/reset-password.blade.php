@php
    $logoUrl = \App\Support\EmailTemplate::logoUrl();
    $homeUrl = rtrim((string) config('app.url'), '/');
@endphp

<x-emails.layout
    :logo-url="$logoUrl"
    :home-url="$homeUrl"
    title="Réinitialisation du mot de passe — NutriTrace"
    preheader="Réinitialisez votre mot de passe NutriTrace — lien valable {{ $expire }} minutes."
    eyebrow="Sécurité du compte"
    :heading="'Bonjour '.$name.','"
>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#3A4A40;">
        Nous avons reçu une demande de réinitialisation du mot de passe associé à
        <strong style="color:#17231C;">{{ $email }}</strong>.
    </p>

    <p style="margin:0 0 28px;font-size:15px;line-height:1.65;color:#3A4A40;">
        Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe sécurisé.
        Ce lien est personnel et expire dans <strong style="color:#064E35;">{{ $expire }} minutes</strong>.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px;">
        <tr>
            <td align="center" style="border-radius:12px;background-color:#087443;">
                <a href="{{ $url }}" target="_blank" style="display:inline-block;padding:15px 32px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:12px;letter-spacing:0.2px;">
                    Réinitialiser mon mot de passe
                </a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:#FFF8E8;border:1px solid #F5E2B8;border-radius:12px;">
        <tr>
            <td style="padding:14px 16px;font-size:13px;line-height:1.55;color:#7A5A10;">
                <strong style="color:#5C4308;">Astuce :</strong> utilisez un mot de passe unique d’au moins 8 caractères, avec lettres et chiffres.
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;line-height:1.55;color:#65756A;">
        Bouton inactif ? Copiez ce lien dans votre navigateur :
    </p>
    <p style="margin:0 0 22px;font-size:12px;line-height:1.5;word-break:break-all;">
        <a href="{{ $url }}" target="_blank" style="color:#087443;text-decoration:underline;">{{ $url }}</a>
    </p>

    <p style="margin:0;font-size:14px;line-height:1.6;color:#3A4A40;">
        Vous n’êtes pas à l’origine de cette demande ? Ignorez cet e-mail : votre mot de passe actuel reste inchangé.
    </p>
</x-emails.layout>
