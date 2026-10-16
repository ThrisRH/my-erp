<?php

declare(strict_types=1);

namespace PersonalFinance\View\Helper;

final class UiIcons
{
    /** @var array<string, string> */
    private const PATHS = [
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/>',
    ];

    public static function svg(string $name): string
    {
        $path = self::PATHS[$name] ?? self::PATHS['chevron-down'];

        return sprintf(
            '<svg class="ui-icon ui-icon-%s" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            $path
        );
    }
}
