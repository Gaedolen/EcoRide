<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('base64', [$this, 'base64Encode']),
            new TwigFilter('statut_label', [$this, 'statutLabel']),
        ];
    }

    public function base64Encode($data): string
    {
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        return base64_encode($data);
    }

    public function statutLabel(string $statut): string
    {
        return match ($statut) {
            'a_venir' => 'À venir',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            default => ucfirst($statut),
        };
    }
}
