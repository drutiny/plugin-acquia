<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Acquia\Api\SourceApi;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load policies from CSKB.
 */
class SourceBase {

  protected $client;
  protected $cache;
  protected $container;

  public function __construct(SourceApi $client, CacheInterface $cache, ContainerInterface $container)
  {
    $this->client = $client;
    $this->cache = $cache;
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return '<notice>ACQUIA</notice>';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return -80;
  }

}
