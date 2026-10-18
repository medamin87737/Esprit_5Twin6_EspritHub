<?php

namespace App\Services\Certifications;

use App\Models\Certification;
use App\Services\OpenRouter\OpenRouterClient;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Module 5 IA — Trois fonctionnalités :
 *  1. verifierDocument()  : lit le PDF/TXT du certificat et vérifie sa cohérence
 *  2. detecterIncoherences() : compare les champs DB avec le document, signale les anomalies
 *  3. chat()              : chatbot public "ce label est-il valide ?"
 */
class CertificationIaService
{
    public function __construct(private OpenRouterClient $client) {}

    public function disponible(): bool
    {
        return $this->client->configure();
    }

    // -------------------------------------------------------------------------
    // 1. Vérification du document PDF/TXT joint
    // -------------------------------------------------------------------------

    /**
     * @return array{valide: bool, confiance: float, resume: string, champs: array<string, string>}
     */
    public function verifierDocument(Certification $certification): array
    {
        $texte = $this->extraireTexteDocument($certification);

        if ($this->disponible()) {
            try {
                return $this->verifierViaOpenRouter($texte, $certification);
            } catch (Throwable) {
                // fallback local
            }
        }

        return $this->verifierLocal($texte, $certification);
    }

    /**
     * @return array{valide: bool, confiance: float, resume: string, champs: array<string, string>}
     */
    private function verifierViaOpenRouter(string $texte, Certification $certification): array
    {
        $reponse = $this->client->chat([
            [
                'role' => 'system',
                'content' => 'Tu es NutriTrace, expert en certifications alimentaires. Réponds UNIQUEMENT en JSON valide, sans markdown.',
            ],
            [
                'role' => 'user',
                'content' => <<<PROMPT
Analyse ce document de certification et réponds avec ce JSON exact :
{
  "valide": true/false,
  "confiance": 0.85,
  "resume": "2-3 phrases sur l'authenticité du document",
  "champs": {
    "numero": "numéro trouvé dans le document ou null",
    "type": "type de label trouvé (bio/local/equitable) ou null",
    "organisme": "nom de l'organisme trouvé ou null",
    "date_expiration": "date trouvée YYYY-MM-DD ou null"
  }
}

Données enregistrées dans NutriTrace :
- Numéro : {$certification->numero}
- Type : {$certification->type}
- Organisme : {$certification->organisme?->nom}
- Date expiration : {$certification->date_expiration?->format('Y-m-d')}
- Produit : {$certification->produit?->nom}

=== DOCUMENT ===
{$texte}
PROMPT,
            ],
        ], 0.1, null, true);

        $data = $this->client->decoderJson($reponse['content']);

        return [
            'valide'    => (bool) ($data['valide'] ?? false),
            'confiance' => round((float) ($data['confiance'] ?? 0.5), 2),
            'resume'    => mb_substr(trim((string) ($data['resume'] ?? '')), 0, 600),
            'champs'    => (array) ($data['champs'] ?? []),
        ];
    }

    /**
     * @return array{valide: bool, confiance: float, resume: string, champs: array<string, string>}
     */
    private function verifierLocal(string $texte, Certification $certification): array
    {
        $bas = mb_strtolower($texte);
        $score = 0;
        $champs = [];

        // Numéro
        if (str_contains($bas, mb_strtolower($certification->numero))) {
            $score++;
            $champs['numero'] = $certification->numero;
        }

        // Type
        $typeMots = ['bio' => ['bio', 'biologique', 'organic'], 'local' => ['local', 'région', 'terroir'], 'equitable' => ['équitable', 'equitable', 'fair trade']];
        foreach ($typeMots[$certification->type] ?? [] as $mot) {
            if (str_contains($bas, $mot)) {
                $score++;
                $champs['type'] = $certification->type;
                break;
            }
        }

        // Organisme
        if ($certification->organisme && str_contains($bas, mb_strtolower($certification->organisme->nom))) {
            $score++;
            $champs['organisme'] = $certification->organisme->nom;
        }

        $confiance = round(min(0.9, 0.3 + ($score * 0.2)), 2);
        $valide = $score >= 2;

        $resume = $valide
            ? "Le document semble cohérent : {$score} élément(s) correspondent aux données enregistrées (numéro, type, organisme)."
            : "Le document présente des incohérences : seulement {$score} élément(s) sur 3 correspondent. Vérification manuelle recommandée.";

        return compact('valide', 'confiance', 'resume', 'champs');
    }

    // -------------------------------------------------------------------------
    // 2. Détection d'incohérences entre le document et la base de données
    // -------------------------------------------------------------------------

    /**
     * @return array{incoherences: list<string>, niveau: string, justification: string}
     */
    public function detecterIncoherences(Certification $certification): array
    {
        $texte = $this->extraireTexteDocument($certification);

        if ($this->disponible()) {
            try {
                return $this->incoherencesViaOpenRouter($texte, $certification);
            } catch (Throwable) {
            }
        }

        return $this->incoherencesLocal($texte, $certification);
    }

    /**
     * @return array{incoherences: list<string>, niveau: string, justification: string}
     */
    private function incoherencesViaOpenRouter(string $texte, Certification $certification): array
    {
        $reponse = $this->client->chat([
            [
                'role' => 'system',
                'content' => 'Tu es un auditeur de certifications alimentaires. Réponds UNIQUEMENT en JSON valide.',
            ],
            [
                'role' => 'user',
                'content' => <<<PROMPT
Compare les données NutriTrace avec le document joint. Détecte toute incohérence (numéro différent, dates erronées, organisme inconnu, type de label non mentionné, produit absent...).

Données NutriTrace :
- Numéro : {$certification->numero}
- Type : {$certification->typeLabel()}
- Organisme : {$certification->organisme?->nom} ({$certification->organisme?->pays})
- Accréditation : {$certification->organisme?->accreditation}
- Produit : {$certification->produit?->nom}
- Date obtention : {$certification->date_obtention?->format('d/m/Y')}
- Date expiration : {$certification->date_expiration?->format('d/m/Y')}
- Statut : {$certification->statutLabel()}

Réponds avec ce JSON :
{
  "incoherences": ["description précise de chaque problème trouvé"],
  "niveau": "aucun|mineur|majeur|critique",
  "justification": "1-2 phrases de conclusion sur la fiabilité"
}

=== DOCUMENT ===
{$texte}
PROMPT,
            ],
        ], 0.1, null, true);

        $data = $this->client->decoderJson($reponse['content']);

        return [
            'incoherences' => array_values(array_filter((array) ($data['incoherences'] ?? []), 'is_string')),
            'niveau'        => in_array($data['niveau'] ?? '', ['aucun', 'mineur', 'majeur', 'critique']) ? $data['niveau'] : 'mineur',
            'justification' => mb_substr(trim((string) ($data['justification'] ?? '')), 0, 500),
        ];
    }

    /**
     * @return array{incoherences: list<string>, niveau: string, justification: string}
     */
    private function incoherencesLocal(string $texte, Certification $certification): array
    {
        $bas = mb_strtolower($texte);
        $problemes = [];

        if (! str_contains($bas, mb_strtolower($certification->numero))) {
            $problemes[] = "Le numéro « {$certification->numero} » n'est pas trouvé dans le document.";
        }

        if ($certification->organisme && ! str_contains($bas, mb_strtolower($certification->organisme->nom))) {
            $problemes[] = "L'organisme « {$certification->organisme->nom} » n'est pas mentionné dans le document.";
        }

        if ($certification->date_expiration) {
            $dateStr = $certification->date_expiration->format('d/m/Y');
            $dateAlt = $certification->date_expiration->format('Y-m-d');
            if (! str_contains($bas, $dateStr) && ! str_contains($bas, $dateAlt)) {
                $problemes[] = "La date d'expiration ({$dateStr}) ne figure pas dans le document.";
            }
        }

        $niveau = match (count($problemes)) {
            0 => 'aucun',
            1 => 'mineur',
            2 => 'majeur',
            default => 'critique',
        };

        $justification = $problemes === []
            ? 'Aucune incohérence détectée. Le document semble conforme aux données enregistrées.'
            : count($problemes).' incohérence(s) détectée(s). Une vérification manuelle est recommandée.';

        return ['incoherences' => $problemes, 'niveau' => $niveau, 'justification' => $justification];
    }

    // -------------------------------------------------------------------------
    // 3. Chatbot "ce label est-il valide ?"
    // -------------------------------------------------------------------------

    /**
     * @param  list<array{role: string, content: string}>  $historique
     * @return array{reponse: string, certification: array<string, mixed>|null}
     */
    public function chat(string $message, array $historique = [], ?string $numero = null): array
    {
        $message = trim($message);

        if ($message === '') {
            throw new RuntimeException('Posez une question sur un label ou une certification.');
        }

        $certification = $numero ? Certification::with(['organisme', 'produit.categorie'])
            ->where('numero', strtoupper(trim($numero)))->first() : null;

        if ($this->disponible()) {
            try {
                return $this->chatViaOpenRouter($message, $historique, $certification);
            } catch (Throwable) {
            }
        }

        return $this->chatLocal($message, $certification);
    }

    /**
     * @param  list<array{role: string, content: string}>  $historique
     * @return array{reponse: string, certification: array<string, mixed>|null}
     */
    private function chatViaOpenRouter(string $message, array $historique, ?Certification $certification): array
    {
        $contexte = $certification
            ? $this->contexteLabel($certification)
            : "Aucun label sélectionné. L'utilisateur peut demander la vérification d'un numéro.";

        $systeme = <<<PROMPT
Tu es l'assistant certification NutriTrace. Tu réponds UNIQUEMENT aux questions sur :
- La validité d'un label (bio, local, équitable)
- L'organisme certificateur et son accréditation
- Les dates de validité et d'expiration
- Le produit certifié

Refuse poliment tout autre sujet en 1 phrase.
Style : français clair, professionnel, rassurant. 1 emoji discret autorisé (✅ ❌ 🏷️ 🌿).
Réponds en JSON strict : {"reponse":"texte"}

{$contexte}
PROMPT;

        $messages = [['role' => 'system', 'content' => $systeme]];
        foreach (array_slice($historique, -6) as $h) {
            $role = ($h['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $contenu = trim((string) ($h['content'] ?? ''));
            if ($contenu !== '') {
                $messages[] = ['role' => $role, 'content' => mb_substr($contenu, 0, 600)];
            }
        }
        $messages[] = ['role' => 'user', 'content' => mb_substr($message, 0, 600)];

        $brut = $this->client->chat($messages, 0.35, null, true);
        $data = $this->client->decoderJson($brut['content']);

        $texte = trim((string) ($data['reponse'] ?? $data['message'] ?? ''));
        if ($texte === '') {
            throw new RuntimeException('Réponse IA vide.');
        }

        return [
            'reponse'       => $texte,
            'certification' => $certification ? $this->carteCertification($certification) : null,
        ];
    }

    /**
     * @return array{reponse: string, certification: array<string, mixed>|null}
     */
    private function chatLocal(string $message, ?Certification $certification): array
    {
        $q = mb_strtolower($message);

        if ($certification) {
            $valide = $certification->estValide();
            $reponse = $valide
                ? "✅ Le label **{$certification->typeLabel()}** n° {$certification->numero} est **valide**. Il couvre le produit « {$certification->produit?->nom} », délivré par {$certification->organisme?->nom}. Il expire le {$certification->date_expiration?->format('d/m/Y')} ({$certification->joursRestants()} jours restants)."
                : "❌ Le label n° {$certification->numero} n'est **pas valide** actuellement (statut : {$certification->statutLabel()}).";

            return ['reponse' => $reponse, 'certification' => $this->carteCertification($certification)];
        }

        if (preg_match('/\b([A-Z0-9][A-Z0-9\-]{3,39})\b/i', $message, $m)) {
            $numero = strtoupper($m[1]);
            $cert = Certification::with(['organisme', 'produit'])->where('numero', $numero)->first();
            if ($cert) {
                $valide = $cert->estValide();

                return [
                    'reponse' => $valide
                        ? "✅ La certification **{$numero}** est valide jusqu'au {$cert->date_expiration?->format('d/m/Y')}."
                        : "❌ La certification **{$numero}** n'est pas valide (statut : {$cert->statutLabel()}).",
                    'certification' => $this->carteCertification($cert),
                ];
            }

            return ['reponse' => "🏷️ Aucune certification portant le numéro **{$numero}** n'a été trouvée dans NutriTrace.", 'certification' => null];
        }

        if (preg_match('/valide|expire|actif|reconnu/u', $q)) {
            return ['reponse' => "Pour vérifier un label, donnez-moi son numéro (ex. BIO-TN-2026-001) ou utilisez la barre de recherche. 🏷️", 'certification' => null];
        }

        return ['reponse' => "Je suis spécialisé dans la vérification des certifications alimentaires. Posez-moi une question sur un numéro de label ou sa validité 😊", 'certification' => null];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function extraireTexteDocument(Certification $certification): string
    {
        if (! $certification->document_pdf) {
            throw new RuntimeException('Aucun document PDF joint à cette certification.');
        }

        if (! Storage::disk(Certification::DISQUE)->exists($certification->document_pdf)) {
            throw new RuntimeException('Le document est introuvable sur le serveur.');
        }

        $binaire = (string) Storage::disk(Certification::DISQUE)->get($certification->document_pdf);
        $ext = strtolower(pathinfo($certification->document_pdf, PATHINFO_EXTENSION));

        if ($ext === 'txt') {
            return mb_substr($this->versUtf8($binaire), 0, 5000);
        }

        // DOCX
        if ($ext === 'docx') {
            $tmp = tempnam(sys_get_temp_dir(), 'nt-cert-');
            if ($tmp === false) {
                throw new RuntimeException('Impossible de lire le DOCX.');
            }
            try {
                file_put_contents($tmp, $binaire);
                $zip = new \ZipArchive;
                if ($zip->open($tmp) !== true) {
                    throw new RuntimeException('DOCX invalide.');
                }
                $xml = $zip->getFromName('word/document.xml') ?: '';
                $zip->close();
            } finally {
                @unlink($tmp);
            }
            $xml = str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xml);

            return mb_substr(strip_tags($xml), 0, 5000);
        }

        // Fallback : traiter comme texte brut
        return mb_substr($this->versUtf8($binaire), 0, 5000);
    }

    private function contexteLabel(Certification $certification): string
    {
        $valide = $certification->estValide() ? 'OUI' : 'NON';

        return "Label en contexte :\n"
            ."- Numéro : {$certification->numero}\n"
            ."- Type : {$certification->typeLabel()}\n"
            ."- Statut : {$certification->statutLabel()} (valide : {$valide})\n"
            ."- Organisme : {$certification->organisme?->nom} ({$certification->organisme?->pays})\n"
            ."- Accréditation : {$certification->organisme?->accreditation}\n"
            ."- Produit : {$certification->produit?->nom}\n"
            ."- Obtenu le : {$certification->date_obtention?->format('d/m/Y')}\n"
            ."- Expire le : {$certification->date_expiration?->format('d/m/Y')}\n"
            .($certification->estValide() ? "- Jours restants : {$certification->joursRestants()}" : '');
    }

    /**
     * @return array<string, mixed>
     */
    private function carteCertification(Certification $certification): array
    {
        return [
            'id'             => $certification->id,
            'numero'         => $certification->numero,
            'type'           => $certification->typeLabel(),
            'statut'         => $certification->statutLabel(),
            'statut_classe'  => $certification->statutClasse(),
            'valide'         => $certification->estValide(),
            'jours_restants' => $certification->joursRestants(),
            'organisme'      => $certification->organisme?->nom,
            'produit'        => $certification->produit?->nom,
            'date_expiration' => $certification->date_expiration?->format('d/m/Y'),
        ];
    }

    private function versUtf8(string $v): string
    {
        if (! mb_check_encoding($v, 'UTF-8')) {
            $c = @mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
            if (is_string($c)) {
                $v = $c;
            }
        }

        return $v;
    }
}
