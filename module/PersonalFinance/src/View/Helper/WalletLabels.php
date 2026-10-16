<?php

declare(strict_types=1);

namespace PersonalFinance\View\Helper;

final class WalletLabels
{
    private const PRESETS = [
        'Tiền mặt' => ['Cash', 'finance.walletCash'],
        'Cash' => ['Cash', 'finance.walletCash'],
        'Ngân hàng' => ['Bank', 'finance.walletBank'],
        'Bank' => ['Bank', 'finance.walletBank'],
        'Ví điện tử' => ['E-wallet', 'finance.walletEWallet'],
        'E-wallet' => ['E-wallet', 'finance.walletEWallet'],
    ];

    public static function english(string $name): string
    {
        return self::PRESETS[$name][0] ?? $name;
    }

    public static function key(string $name): ?string
    {
        return self::PRESETS[$name][1] ?? null;
    }
}
