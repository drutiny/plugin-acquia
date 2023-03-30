<?php

namespace Drutiny\Acquia\DomainList;

use Drutiny\Attribute\Name;
use Drutiny\DomainList\AbstractDomainList;
use Drutiny\Target\TargetInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Param(
 *   name = "custom-only",
 *   description = "Boolean indicator to use only custom domains.",
 * )
 */
#[Name('acquia')]
class AcquiaCloudDomainList extends AbstractDomainList {

  protected ContainerInterface $container;

  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
    parent::__construct();
  }

  /**
   * @return array list of domains.
   */
  public function getDomains(TargetInterface $target, array $options = []):array
  {
    if (!$options['multisite']) {
      return [];
    }
    if (!$target->hasProperty('acquia.cloud.environment.domains')) {
      return [];
    }
    // Do not allow wildcards as they don't work with Drush.
    return array_filter($target['acquia.cloud.environment.domains'], function ($domain) {
      return strpos($domain, '*') === FALSE;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getInputOptions(): array
  {
    return [
      new InputOption(
        name: 'multisite',
        description: 'Set to true to load domains as a multisite config.'
      )
    ];
  }
}

 ?>
