<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Idea;
use Illuminate\Database\Seeder;

final class PlayStationIdeasSeeder extends Seeder
{
    public function run(): void
    {
        $ideas = [

            // ── Stats page ──────────────────────────────────────────────

            [
                'title'       => 'Session length distribution histogram',
                'description' => 'Histogram met buckets: 0–30m / 30–60m / 1–2h / 2–4h / 4h+. Laat zien of je typisch korte sprints of marathons speelt en wat je meest voorkomende sessieduur is.',
                'module'      => 'playstation',
                'priority'    => 'medium',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Gaming velocity chart (rolling gemiddelde)',
                'description' => 'Rollend 4-weeksgemiddelde van uren over alle tijd. Maakt trends in engagement zichtbaar — pieken na nieuwe releases, dips tijdens drukke periodes.',
                'module'      => 'playstation',
                'priority'    => 'medium',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Completion funnel',
                'description' => 'Hoeveel games zitten op 0% / 1–25% / 25–75% / 75–99% / 100% completion? Visuele funnel die laat zien hoe ver je gemiddeld komt voordat je een game loslaat.',
                'module'      => 'playstation',
                'priority'    => 'medium',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Backlog graveyard',
                'description' => 'Lijst van games die 6+ maanden niet aangeraakt zijn en nog niet completed. Gesorteerd op speeltijd. De stille schuld in je bibliotheek.',
                'module'      => 'playstation',
                'priority'    => 'low',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Trophy velocity per game',
                'description' => 'Trophies verdiend per speeluur per game — welke games leveren de meeste trophies per geïnvesteerd uur op. Handig voor efficiënt trophy jagen.',
                'module'      => 'playstation',
                'priority'    => 'low',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Comeback games',
                'description' => 'Games die je hebt opgepakt na 30+ dagen stilte. Toont de gap ("Terugkeer na 47 dagen") en of je er daarna meer of minder in hebt gespeeld dan voor de pauze.',
                'module'      => 'playstation',
                'priority'    => 'low',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Categorie/genre breakdown op stats pagina',
                'description' => 'Uren per genre gebaseerd op de categorieën die aan games zijn gekoppeld. Welk genre domineert je speeltijd, en hoe is dat verdeeld over platforms.',
                'module'      => 'playstation',
                'priority'    => 'medium',
                'status'      => 'idea',
            ],

            // ── Wrapped ─────────────────────────────────────────────────

            [
                'title'       => 'Gaming persoonlijkheid archetype (Wrapped)',
                'description' => 'Ken een of meerdere archetypes toe op basis van speelpatronen: "Trophy Hunter" (hoge trophies/uur), "Marathon Gamer" (gemiddelde sessie >3h), "Night Owl" (60%+ sessies na 22:00), "Variety Seeker" (veel unieke games, weinig herhalingen). Tonen als badge(s) bovenaan de Wrapped.',
                'module'      => 'playstation',
                'priority'    => 'high',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Van 0 naar 100% dit jaar (Wrapped)',
                'description' => 'Welke games heb je dit jaar volledig voltooid? Toont startdatum, einddatum, totale uren en hoe lang het duurde van eerste sessie tot 100%. Alleen games die in dat jaar van 0% naar 100% zijn gegaan.',
                'module'      => 'playstation',
                'priority'    => 'high',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Meest verbeterd (Wrapped)',
                'description' => 'Game met de grootste stijging in completion percentage dit jaar. Bijv. een game die van 12% naar 87% ging. Toont voor- en nawaarde en de periode waarin de progressie plaatsvond.',
                'module'      => 'playstation',
                'priority'    => 'medium',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Trophy haul per maand chart (Wrapped)',
                'description' => 'Staafdiagram van het aantal trophies per maand binnen het geselecteerde jaar. Maakt zichtbaar in welke maanden je actief aan het jagen was en of er een piek zit rondom bepaalde games.',
                'module'      => 'playstation',
                'priority'    => 'medium',
                'status'      => 'idea',
            ],
            [
                'title'       => 'The one that got away (Wrapped)',
                'description' => 'De game met de meeste speeluren dit jaar die je niet hebt afgemaakt — hoogste uren gecombineerd met een completion percentage onder 100%. De game die het meeste tijd kostte maar onafgemaakt bleef.',
                'module'      => 'playstation',
                'priority'    => 'medium',
                'status'      => 'idea',
            ],
            [
                'title'       => 'Speeldagen calendar heatmap (Wrapped)',
                'description' => 'GitHub-achtige heatmap van alle dagen in het jaar, ingekleurd op intensiteit (minuten gespeeld). In één oogopslag zichtbaar hoe consistent en regelmatig je hebt gespeeld, met duidelijke pieken en gaten.',
                'module'      => 'playstation',
                'priority'    => 'high',
                'status'      => 'idea',
            ],

        ];

        foreach ($ideas as $idea) {
            Idea::firstOrCreate(
                ['title' => $idea['title'], 'module' => $idea['module']],
                $idea,
            );
        }
    }
}
