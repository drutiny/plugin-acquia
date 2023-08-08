<?php

namespace Drutiny\Acquia\Source;

use Drutiny\LanguageManager;

enum LanguageMap:string {
    // Portuguese
    case PT = 'pt-br';

    // English (default)
    case EN = 'en';

    // Japanese
    case JA = 'ja';

    // Spanish
    case ES = 'es';

    /**
     * Get the Acquia supported language code.
     */
    public static function fromLanguageManager(LanguageManager $languageManager):static {
        return static::fromDrutinyLangCode($languageManager->getCurrentLanguage());
    }

    /**
     * Get the Acquia supported language code.
     */
    public static function fromDrutinyLangCode(string $code): static {
        return match ($code) {
            'pt' => static::PT,
            default => static::from($code)
        };
    }

    /**
     * Get the language code used by Drutiny.
     */
    public function toDrutinyLangCode(): string {
        return strtolower($this->name);
    }

    /**
     * Return a path prefix if applicable for the provided language.
     */
    public function getPathPrefix(): string {
        return $this->value == 'en' ? '' : $this->value.'/';
    }

    public function isTranslation(): bool {
        return $this != static::EN;
    }
}