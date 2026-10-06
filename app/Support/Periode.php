<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Throwable;

/**
 * Période analysée par le tableau de bord, découpée en compartiments (jours, semaines ou mois)
 * et comparable à la période précédente de même durée.
 */
final class Periode
{
    public const CHOIX = [
        'aujourdhui' => "Aujourd'hui",
        '7j' => '7 derniers jours',
        '30j' => '30 derniers jours',
        '90j' => '3 derniers mois',
        '12m' => '12 derniers mois',
        'perso' => 'Personnalisée',
    ];

    public const DEFAUT = '90j';

    /** @var list<array{debut: CarbonImmutable, fin: CarbonImmutable, libelle: string, libelleLong: string}>|null */
    private ?array $compartiments = null;

    private function __construct(
        public readonly string $cle,
        public readonly CarbonImmutable $debut,
        public readonly CarbonImmutable $fin,
    ) {}

    public static function depuisRequete(Request $request): self
    {
        $aujourdhui = CarbonImmutable::today();
        $cle = (string) $request->query('periode', self::DEFAUT);

        if ($cle === 'perso') {
            $du = self::date($request->query('du'));
            $au = self::date($request->query('au'));

            if ($du && $au && $du->lte($au)) {
                return new self('perso', $du, $au->endOfDay());
            }
        }

        $cle = array_key_exists($cle, self::CHOIX) && $cle !== 'perso' ? $cle : self::DEFAUT;

        $debut = match ($cle) {
            'aujourdhui' => $aujourdhui,
            '7j' => $aujourdhui->subDays(6),
            '30j' => $aujourdhui->subDays(29),
            '12m' => $aujourdhui->subYear()->addDay(),
            default => $aujourdhui->subDays(89),
        };

        return new self($cle, $debut, $aujourdhui->endOfDay());
    }

    public function jours(): int
    {
        return (int) round(abs($this->debut->diffInDays($this->fin->startOfDay()))) + 1;
    }

    public function precedente(): self
    {
        return new self($this->cle, $this->debut->subDays($this->jours()), $this->debut->subDay()->endOfDay());
    }

    public function granularite(): string
    {
        return match (true) {
            $this->jours() <= 31 => 'jour',
            $this->jours() <= 186 => 'semaine',
            default => 'mois',
        };
    }

    public function granulariteLibelle(): string
    {
        return ['jour' => 'par jour', 'semaine' => 'par semaine', 'mois' => 'par mois'][$this->granularite()];
    }

    public function libelle(): string
    {
        return $this->jours() === 1
            ? $this->debut->translatedFormat('j M Y')
            : $this->debut->translatedFormat('j M Y').' – '.$this->fin->translatedFormat('j M Y');
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function bornes(): array
    {
        return [$this->debut->toDateTimeString(), $this->fin->toDateTimeString()];
    }

    /**
     * @return list<array{debut: CarbonImmutable, fin: CarbonImmutable, libelle: string, libelleLong: string}>
     */
    public function compartiments(): array
    {
        if ($this->compartiments !== null) {
            return $this->compartiments;
        }

        $granularite = $this->granularite();
        $compartiments = [];
        $curseur = $this->debut;

        while ($curseur->lte($this->fin)) {
            $suivant = match ($granularite) {
                'jour' => $curseur->addDay(),
                'semaine' => $curseur->addWeek(),
                default => $curseur->startOfMonth()->addMonth(),
            };
            $fin = $suivant->subSecond()->min($this->fin);

            $compartiments[] = [
                'debut' => $curseur,
                'fin' => $fin,
                'libelle' => $curseur->translatedFormat($granularite === 'mois' ? 'M Y' : 'j M'),
                'libelleLong' => match ($granularite) {
                    'jour' => $curseur->translatedFormat('l j F Y'),
                    'semaine' => 'Du '.$curseur->translatedFormat('j M').' au '.$fin->translatedFormat('j M Y'),
                    default => ucfirst($curseur->translatedFormat('F Y')),
                },
            ];
            $curseur = $suivant;
        }

        return $this->compartiments = $compartiments;
    }

    public function indexDe(string $jour): ?int
    {
        $date = CarbonImmutable::parse($jour);

        foreach ($this->compartiments() as $index => $compartiment) {
            if ($date->betweenIncluded($compartiment['debut'], $compartiment['fin'])) {
                return $index;
            }
        }

        return null;
    }

    private static function date(mixed $valeur): ?CarbonImmutable
    {
        if (! is_string($valeur) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valeur)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $valeur)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
