<?php

namespace Drutiny\Acquia;

use Drutiny\Policy;
use Drutiny\PolicySource\PolicySourceInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Load policies from CSKB.
 */
class PolicySource implements PolicySourceInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return parse_url($this->getBaseUrl(), PHP_URL_HOST);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUrl() {
    return 'https://cskb.acquia.com/o/drutiny-api/';
  }

  /**
   * {@inheritdoc}
   */
  public function getList() {
    $api = new CskbApi($this->getBaseUrl());
    $list = [];
    foreach ($api->getPolicyList() as $listedPolicy) {
      $listedPolicy['filepath'] = $this->getBaseUrl() . 'policy/list';
      $list[$listedPolicy['name']] = $listedPolicy;
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition) {
    $schema = Policy::getSchema();

    $definition['chart'] = (array) Yaml::parse($definition['chart']);
    $definition['depends'] = Yaml::parse($definition['depends']);
    $definition['parameters'] = (array) Yaml::parse($definition['parameters']);
    foreach ($definition['parameters'] as $key => $value) {
      unset($definition['parameters'][$key]);
      if (empty($value)) {
        continue;
      }
      $definition['parameters'][$key]['default'] = $value;
    }
    if (!is_array($definition['tags'])) {
      $definition['tags'] = array_map('trim', explode(',', $definition['tags']));
    }

    // Remove the UUID as its not apart of the Policy schema.
    // This is just a schema fix. CSKB should do this.
    if (isset($definition['uuid'])) {
      $definition['signature'] = $definition['uuid'];
      unset($definition['uuid']);
    }

    $definition['filepath'] = 'https://' . $this->getName() . '/policy/' . $definition['signature'];

    // Remove state as its not valid metadata.
    if (isset($definition['state'])) {
      unset($definition['state']);
    }

    $values = $schema->getChild('severity')->getAttribute('values');

    if (!in_array($definition['severity'], $values)) {
      unset($definition['severity']);
    }

    return new Policy($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return -90;
  }

}
