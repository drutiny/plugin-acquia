<?php

namespace Drutiny\Acquia\Source;

/**
 * Load policies from CSKB.
 */
trait SourceTrait {

  public function getApiPrefix()
  {
      $lang_code = LanguageMap::fromLanguageManager($this->languageManager)->value;
      return $lang_code == 'en' ? '/' : $lang_code.'/';
  }

  protected function getRequestParams()
  {
    return ['query' => [
      'filter[status][value]' => 1,
      'filter[field_scope_visibility][value]' => 'external',
      'filter[field_compatibility][value]' => 'drutiny3',
      // Only include content that contains a translations for the
      // specified language.
      'filter[langcode]' => LanguageMap::fromLanguageManager($this->languageManager)->value,
    ]];
  }
}
