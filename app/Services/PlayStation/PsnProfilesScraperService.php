<?php

declare(strict_types=1);

namespace App\Services\PlayStation;

use App\Models\PlayStationGame;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PsnProfilesScraperService
{
    private const BASE = 'https://psnprofiles.com';

    public function fetchAndStore(PlayStationGame $game): array
    {
        $username = config('services.psnprofiles.username');
        $slug     = $this->findGameSlug($game);

        // Try user-specific page first (includes earned status)
        $withUser = $username ? $this->tryUserPage($username, $slug, $game) : null;

        if ($withUser !== null) {
            return ['count' => $withUser, 'user_page' => true];
        }

        // Fallback: game-only page (trophy definitions, no earned status)
        $html = $this->get("/trophies/{$slug}");
        $trophies = $this->parseTrophies($html);

        if (empty($trophies)) {
            throw new RuntimeException(
                "No trophies parsed from PSNProfiles. ".
                "Probeer je profiel éénmalig op te zoeken op psnprofiles.com zodat het geïndexeerd wordt."
            );
        }

        foreach ($trophies as $trophy) {
            $game->trophyList()->updateOrCreate(
                ['trophy_id' => $trophy['id'], 'trophy_group_id' => 'default'],
                [
                    'name'      => $trophy['name'],
                    'detail'    => $trophy['detail'],
                    'icon_url'  => $trophy['icon_url'],
                    'type'      => $trophy['type'],
                    'is_earned' => false,
                    'earned_at' => null,
                ]
            );
        }

        return ['count' => count($trophies), 'user_page' => false];
    }

    private function tryUserPage(string $username, string $slug, PlayStationGame $game): ?int
    {
        $response = Http::withHeaders($this->headers())->get(self::BASE."/{$username}/trophies/{$slug}");

        if ($response->status() === 404) {
            return null;
        }

        if ($response->status() === 403 || $this->isCloudflareChallenge($response->body())) {
            throw new RuntimeException($this->cloudflarMessage());
        }

        if (! $response->successful()) {
            return null;
        }

        $trophies = $this->parseTrophies($response->body());

        foreach ($trophies as $trophy) {
            $game->trophyList()->updateOrCreate(
                ['trophy_id' => $trophy['id'], 'trophy_group_id' => 'default'],
                [
                    'name'      => $trophy['name'],
                    'detail'    => $trophy['detail'],
                    'icon_url'  => $trophy['icon_url'],
                    'type'      => $trophy['type'],
                    'is_earned' => $trophy['is_earned'],
                    'earned_at' => $trophy['earned_at'],
                ]
            );
        }

        return count($trophies);
    }

    private function findGameSlug(PlayStationGame $game): string
    {
        if ($game->psnprofiles_slug) {
            return $game->psnprofiles_slug;
        }

        $platform = match ($game->platform) {
            'PS5'    => 'ps5',
            'PS4'    => 'ps4',
            'PS3'    => 'ps3',
            'PSVITA' => 'vita',
            default  => 'ps4',
        };

        $html = $this->get('/search/games?q='.urlencode($game->name).'&platform='.$platform);
        $slug = $this->extractSlug($html, $game->name);

        if (! $slug) {
            $html = $this->get('/search/games?q='.urlencode($game->name));
            $slug = $this->extractSlug($html, $game->name);
        }

        if (! $slug) {
            throw new RuntimeException(
                "Could not find \"{$game->name}\" on PSNProfiles. ".
                'Ga naar Edit en vul de PSNProfiles-slug handmatig in (het stuk na /trophies/ in de URL op psnprofiles.com).'
            );
        }

        return $slug;
    }

    private function extractSlug(string $html, string $gameName): ?string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        $norm  = $this->normalize($gameName);
        $links = $xpath->query('//a[contains(@href, "/trophies/")]');

        $bestSlug  = null;
        $bestScore = 0.0;

        foreach ($links as $link) {
            $href = $link instanceof \DOMElement ? $link->getAttribute('href') : '';

            if (! preg_match('#/trophies/([\w-]+)$#', $href, $m)) {
                continue;
            }

            $slug      = $m[1];
            $linkTitle = $this->normalize(trim($link->textContent));
            $slugTitle = $this->normalize(str_replace('-', ' ', preg_replace('/^\d+-/', '', $slug)));

            similar_text($norm, $linkTitle, $pctTitle);
            similar_text($norm, $slugTitle, $pctSlug);
            $score = max($pctTitle, $pctSlug);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestSlug  = $slug;
            }
        }

        return $bestScore >= 70 ? $bestSlug : null;
    }

    private function parseTrophies(string $html): array
    {
        // Strip XHTML namespace — it causes XPath to treat elements as namespaced,
        // making //table//tr match nothing even when the tags are present.
        $html = str_replace(' xmlns="http://www.w3.org/1999/xhtml"', '', $html);

        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        $trophies = [];

        // Each trophy is a <tr> that contains an <a class="title"> pointing to /trophy/
        $rows = $xpath->query('//tr[.//a[@class="title" and contains(@href, "/trophy/")]]');

        foreach ($rows as $row) {
            $trophy = $this->parseRow($xpath, $row);

            if ($trophy) {
                $trophies[] = $trophy;
            }
        }

        return $trophies;
    }

    private function parseRow(\DOMXPath $xpath, \DOMNode $row): ?array
    {
        // Name comes from <a class="title" href="/trophy/{game-slug}/{id}-{name-slug}">
        $titleLink = $xpath->query('.//a[@class="title" and contains(@href, "/trophy/")]', $row)->item(0);

        if (! $titleLink) {
            return null;
        }

        $name = trim($titleLink->textContent);

        if (empty($name)) {
            return null;
        }

        $href = $titleLink instanceof \DOMElement ? $titleLink->getAttribute('href') : '';

        // ID is the number before the first hyphen in the last path segment:
        // /trophy/35529-call-of-duty-modern-warfare-ii/1-we-are-rtb → 1
        $trophyId = 0;

        if (preg_match('#/trophy/[^/]+/(\d+)-#', $href, $m)) {
            $trophyId = (int) $m[1];
        }

        if ($trophyId === 0) {
            return null;
        }

        // Description: text node directly after <br /> inside the title's parent <td>
        $detail   = null;
        $parentTd = $titleLink->parentNode;

        if ($parentTd) {
            $foundBr = false;

            foreach ($parentTd->childNodes as $node) {
                if ($node->nodeName === 'br') {
                    $foundBr = true;
                    continue;
                }

                if ($foundBr && $node->nodeType === XML_TEXT_NODE) {
                    $text = trim($node->nodeValue);

                    if ($text !== '') {
                        $detail = $text;
                        break;
                    }
                }
            }
        }

        // Type: <img title="Platinum|Gold|Silver|Bronze"> in the row
        $type     = 'bronze';
        $typeImgs = $xpath->query('.//img[@title]', $row);

        foreach ($typeImgs as $img) {
            $title = strtolower($img instanceof \DOMElement ? $img->getAttribute('title') : '');

            if (in_array($title, ['platinum', 'gold', 'silver', 'bronze'], true)) {
                $type = $title;
                break;
            }
        }

        // Icon: first img hosted on img.psnprofiles.com
        $iconUrl = null;
        $allImgs = $xpath->query('.//img', $row);

        foreach ($allImgs as $img) {
            $src = $img instanceof \DOMElement ? $img->getAttribute('src') : '';

            if (str_contains($src, 'img.psnprofiles.com')) {
                $iconUrl = $src;
                break;
            }
        }

        // Earned status: <picture class="trophy earned"> vs "trophy unearned unowned"
        $isEarned = false;
        $earnedAt = null;
        $picture  = $xpath->query('.//picture', $row)->item(0);

        if ($picture instanceof \DOMElement) {
            $picClass = $picture->getAttribute('class');
            $isEarned = str_contains($picClass, 'earned') && ! str_contains($picClass, 'unearned');
        }

        // Secondary: look for a date string in any <td> (user pages show earned dates)
        if (! $isEarned) {
            foreach ($xpath->query('.//td', $row) as $td) {
                $text = trim($td->textContent);

                if (strlen($text) < 60 && preg_match('/\d{1,2}\s+\w+\s+\d{4}|\w+\s+\d{1,2},?\s+\d{4}/i', $text)) {
                    $parsed = strtotime($text);

                    if ($parsed) {
                        $isEarned = true;
                        $earnedAt = date('Y-m-d H:i:s', $parsed);
                        break;
                    }
                }
            }
        }

        return [
            'id'        => $trophyId,
            'name'      => $name,
            'detail'    => $detail,
            'icon_url'  => $iconUrl,
            'type'      => $type,
            'is_earned' => $isEarned,
            'earned_at' => $earnedAt,
        ];
    }

    private function get(string $path): string
    {
        $response = Http::withHeaders($this->headers())->get(self::BASE.$path);

        if ($response->status() === 403 || $this->isCloudflareChallenge($response->body())) {
            throw new RuntimeException($this->cloudflarMessage());
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                "PSNProfiles returned HTTP {$response->status()} for {$path}"
            );
        }

        return $response->body();
    }

    private function isCloudflareChallenge(string $html): bool
    {
        return str_contains($html, 'challenges.cloudflare.com')
            || str_contains($html, 'Just a moment...')
            || str_contains($html, 'cf_chl_opt');
    }

    private function headers(): array
    {
        $cfClearance = config('services.psnprofiles.cf_clearance');
        $userAgent   = config('services.psnprofiles.user_agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
        );

        $headers = [
            'User-Agent'                => $userAgent,
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language'           => 'en-US,en;q=0.9',
            'DNT'                       => '1',
            'Upgrade-Insecure-Requests' => '1',
        ];

        if ($cfClearance) {
            $headers['Cookie'] = "cf_clearance={$cfClearance}";
        }

        return $headers;
    }

    private function cloudflarMessage(): string
    {
        return 'PSNProfiles blocked the request (Cloudflare 403). '.
            'Voeg PSNPROFILES_CF_CLEARANCE toe aan je .env: open psnprofiles.com in je browser, '.
            'ga naar DevTools → Application → Cookies → psnprofiles.com en kopieer de cf_clearance waarde. '.
            'Voeg ook PSNPROFILES_USER_AGENT toe met de User-Agent van je browser '.
            '(DevTools → Network → een request → Request Headers → User-Agent).';
    }

    private function normalize(string $s): string
    {
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', '', $s);

        return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    }
}
