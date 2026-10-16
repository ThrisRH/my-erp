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
        'utensils' => '<path d="M18 2v20M21 2v4a3 3 0 0 1-3 3M6 2v7a3 3 0 0 0 3 3v10M9 2v4a3 3 0 0 1-3 3M3 2v4a3 3 0 0 0 3 3"/>',
        'cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.5 2.5h3l2.68 12.32a2 2 0 0 0 2 1.68h8.64a2 2 0 0 0 2-1.6l1.38-7.4H6.5"/>',
        'book' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 4.8 0 0 1 12 8a4.8 4.8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>',
        'plane' => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3.5c-.5-.5-2.5 0-4 1.5L13.5 8.5 5.3 6.7c-.8-.2-1.6.1-2 .8l-.8 1.4 6 4-3.5 3.5-2.5-.5-1.5 1 3 2 2 3 1-1.5-.5-2.5 3.5-3.5 4 6 1.4-.8c.7-.4 1-1.2.8-2z"/>',
        'phone' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
        'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8M8 11h8M8 15h4"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'piggy-bank' => '<path d="M19 12a7 7 0 1 0-14 0 7 7 0 0 0 4 6.3V21h3v-2h2v2h3v-2.7a7 7 0 0 0 2-4.3zM15 9h.01M16 6l2-2M2 10l3-1"/>',
        'dumbbell' => '<path d="m6.5 6.5 11 11M4 9l5-5M9 4l2 2-3 3-2-2ZM15 20l5-5M20 15l-2-2-3 3 2 2ZM2 11l2-2 3 3-2 2ZM17 22l2-2 3 3-2 2Z"/>',
        'paw' => '<circle cx="6" cy="7" r="2"/><circle cx="18" cy="7" r="2"/><circle cx="9" cy="4" r="2"/><circle cx="15" cy="4" r="2"/><path d="M12 11c-3.3 0-6 2.2-6 5a4 4 0 0 0 6 3 4 4 0 0 0 6-3c0-2.8-2.7-5-6-5z"/>',
        'film' => '<rect x="2" y="2" width="20" height="20" rx="2.18"/><path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5"/>',
        'tag' => '<path d="M12 2H2v10l10.29 10.29a2.41 2.41 0 0 0 3.4 0l6.6-6.6a2.41 2.41 0 0 0 0-3.4L12 2z"/><circle cx="7" cy="7" r="1.5"/>',
        'award' => '<circle cx="12" cy="8" r="6"/><path d="m15.48 13.93 2.52 7.07-6-3-6 3 2.52-7.07"/>',
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
