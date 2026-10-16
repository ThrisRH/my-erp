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
