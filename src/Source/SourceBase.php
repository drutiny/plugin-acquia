<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Acquia\Api\SourceApi;
use Drutiny\LanguageManager;
use Symfony\Contracts\Cache\CacheInterface;
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
      CacheInterface $cache,
      ContainerInterface $container,
      LanguageManager $languageManager
      )
  {
    $this->client = $client;
    $this->cache = $cache;
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
      // Only include content that contains a translations for the
      // specified language.
      'filter[langcode]' => $this->languageManager->getCurrentLanguage(),
    ]];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return '<fg=cyan>ACQUIA</>';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return -80;
  }

}
