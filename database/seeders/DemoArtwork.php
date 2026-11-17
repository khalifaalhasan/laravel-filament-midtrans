<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Generates simple SVG posters, banners and organizer logos so the demo looks complete without external assets. */
class DemoArtwork
{
    private const PALETTES = [
        ['#152c8c', '#3c5fec'], ['#7c2d12', '#f97316'], ['#14532d', '#22c55e'], ['#581c87', '#d946ef'],
        ['#0c4a6e', '#06b6d4'], ['#7f1d1d', '#ef4444'], ['#1e293b', '#64748b'], ['#713f12', '#eab308'],
    ];

    public static function poster(string $title, int $seed): string
    {
        return self::store('posters', $title, self::art($title, 1000, 400, $seed, 64));
    }

    public static function banner(string $title, int $seed): string
    {
        return self::store('banners', $title, self::art($title, 1200, 360, $seed, 84));
    }

    public static function logo(string $organizer, int $seed): string
    {
        [$from, $to] = self::PALETTES[$seed % count(self::PALETTES)];
        $initials = e(Str::upper(Str::substr(preg_replace('/[^A-Za-z ]/', '', $organizer), 0, 2)));
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="32" fill="{$from}"/><circle cx="32" cy="32" r="24" fill="{$to}" opacity=".35"/><text x="32" y="39" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#fff" text-anchor="middle">{$initials}</text></svg>
SVG;

        return self::store('organizers', $organizer, $svg);
    }

    private static function art(string $title, int $w, int $h, int $seed, int $fontSize): string
    {
        [$from, $to] = self::PALETTES[$seed % count(self::PALETTES)];
        $text = e(Str::upper($title));
        $fontSize = min($fontSize, (int) ($w * 1.55 / max(1, mb_strlen($title)))); // keep long titles inside the image
        $r1 = (int) ($h * 0.9);
        $r2 = (int) ($h * 0.6);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}">
<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$from}"/><stop offset="1" stop-color="{$to}"/></linearGradient></defs>
<rect width="{$w}" height="{$h}" fill="url(#g)"/>
<circle cx="{$w}" cy="0" r="{$r1}" fill="#fff" opacity=".08"/><circle cx="0" cy="{$h}" r="{$r2}" fill="#fff" opacity=".1"/>
<text x="50%" y="54%" font-family="Arial Black, Arial, sans-serif" font-size="{$fontSize}" font-weight="900" fill="#fff" text-anchor="middle" dominant-baseline="middle">{$text}</text>
</svg>
SVG;
    }

    private static function store(string $dir, string $name, string $svg): string
    {
        $path = $dir.'/'.Str::slug($name).'.svg';
        Storage::disk('public')->put($path, $svg);

        return $path;
    }
}
