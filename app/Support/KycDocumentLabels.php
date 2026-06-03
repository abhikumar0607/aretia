<?php

namespace App\Support;

class KycDocumentLabels
{
    /**
     * @return array<string, string>
     */
    public static function governmentIdOptions(): array
    {
        return [
            'passport' => 'Passport (photo page)',
            'national_id_card' => 'National ID card',
            'driving_licence' => 'Driving licence',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function registrationOptions(): array
    {
        return [
            'certificate_of_incorporation' => 'Certificate of incorporation',
            'registration_extract' => 'Company registration extract',
            'memorandum_articles' => 'Memorandum & articles of association',
            'business_registration' => 'Business registration certificate',
        ];
    }

    /**
     * @return list<string>
     */
    public static function governmentIdKeys(): array
    {
        return array_keys(self::governmentIdOptions());
    }

    /**
     * @return list<string>
     */
    public static function registrationKeys(): array
    {
        return array_keys(self::registrationOptions());
    }

    public static function subtypeLabel(string $type, ?string $subtype): ?string
    {
        if ($subtype === null || $subtype === '') {
            return null;
        }

        return match ($type) {
            'national_id' => self::governmentIdOptions()[$subtype] ?? ucfirst(str_replace('_', ' ', $subtype)),
            'incorporation' => self::registrationOptions()[$subtype] ?? ucfirst(str_replace('_', ' ', $subtype)),
            default => null,
        };
    }
}
