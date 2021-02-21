<?php

namespace Drutiny\Acquia\DomainList;

use Drutiny\DomainList\AbstractDomainList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Param(
 *   name = "custom-only",
 *   description = "Boolean indicator to use only custom domains.",
 * )
 */
class AcquiaCloudDomainList extends AbstractDomainList {

  protected ContainerInterface $container;

  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function configure()
  {
      $this->addOption('multisite', 'Set to true to load domains as a multisite config.');
  }

  /**
   * @return array list of domains.
   */
  public function getDomains(array $options = [])
  {
    // Do not allow wildcards as they don't work with Drush.
    return array_filter($this->container->get('target')['acquia.cloud.environment.domains'], function ($domain) {
      return strpos($domain, '*') === FALSE;
    });
  }
}

 ?>
