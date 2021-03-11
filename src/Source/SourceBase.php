<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Acquia\Api\SourceApi;
use Drutiny\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load policies from CSKB.
 */
class SourceBase {

  protected $client;
  protected $cache;
  protected $container;
  protected $languageManager;

  public function __construct(
      SourceApi $client,
      ContainerInterface $container,
      LanguageManager $languageManager
      )
  {
    $this->client = $client;
    $this->container = $container;
    $this->languageManager = $languageManager;
  }

  public function getApiPrefix()
  {
      $lang_code = $this->languageManager->getCurrentLanguage();
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
      'filter[langcode]' => $this->languageManager->getCurrentLanguage(),
    ]];
  }

  /**
   * {@inheritdoc}
   */
  public function getName():string {
    return '<fg=cyan>ACQUIA</>';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight():int {
    return -80;
  }

}
