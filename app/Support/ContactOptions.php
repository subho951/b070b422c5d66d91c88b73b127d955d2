<?php

namespace App\Support;

class ContactOptions
{
    public static function currency(?string $country): ?string
    {
        return config('countries')[$country ?? ''] ?? null;
    }

    /** @return array<string, string> */
    public static function budgets(?string $country): array
    {
        $currency = self::currency($country);
        if ($currency === null) {
            return [];
        }

        return array_map(fn (string $label): string => str_replace('{currency}', $currency, $label), self::budgetRanges());
    }

    /** @return array<string, string> */
    public static function budgetRanges(): array
    {
        return [
            'under_5000' => '{currency} - Under 5,000',
            '5000_15000' => '{currency} - 5,000 to 15,000',
            '15000_50000' => '{currency} - 15,000 to 50,000',
            '50000_plus' => '{currency} - 50,000+',
            'discuss' => 'To be discussed',
        ];
    }
}
