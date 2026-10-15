<?php

declare(strict_types=1);

namespace PersonalFinance\View\Helper;

final class CategoryIcons
{
    private const PATHS = [
        'folder' => '<path d="M3 7h6l2 2h10v10H3z"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M16 15h2"/>',
        'sparkles' => '<path d="m12 2 2.2 7.8L22 12l-7.8 2.2L12 22l-2.2-7.8L2 12l7.8-2.2z"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
        'chart' => '<path d="M3 20V4M3 20h18M7 16l4-5 3 2 5-7"/>',
        'coffee' => '<path d="M4 7h13v8a6 6 0 0 1-6 6H10a6 6 0 0 1-6-6zM17 9h2a3 3 0 0 1 0 6h-2M7 3v2M12 3v2"/>',
        'car' => '<path d="m5 11 2-5h10l2 5M4 11h16v8H4zM7 19v2M17 19v2M7 15h2M15 15h2"/>',
        'home' => '<path d="m3 11 9-8 9 8v10H3zM9 21v-7h6v7"/>',
        'bag' => '<path d="M4 8h16l-1 13H5zM9 8V6a3 3 0 0 1 6 0v2"/>',
        'bolt' => '<path d="m13 2-9 12h7l-1 8 10-13h-7z"/>',
        'heart' => '<path d="M20 9c0 5-8 11-8 11S4 14 4 9a4 4 0 0 1 8-2 4 4 0 0 1 8 2z"/>',
        'play' => '<circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4z"/>',
    ];

    public static function names(): array
    {
        return array_keys(self::PATHS);
    }

    public static function svg(string $name): string
    {
        $path = self::PATHS[$name] ?? self::PATHS['folder'];

        return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
    }
}
