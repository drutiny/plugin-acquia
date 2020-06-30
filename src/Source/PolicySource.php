<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Policy;
use Drutiny\PolicySource\PolicySourceInterface;
use Drutiny\Acquia\Api\SourceApi;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Load policies from CSKB.
 */
class PolicySource implements PolicySourceInterface {

  protected $client;
  protected $cache;

  public function __construct(SourceApi $client, CacheInterface $cache)
  {
    $this->client = $client;
    $this->cache = $cache;
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
  public function getList() {
    $list = [];
    foreach ($this->client->getPolicyList() as $policy) {
      $list[$policy['field_name']] = [
        'signature' => $policy['uuid'],
        'name' => $policy['field_name'],
        'class' => $policy['class'],
        'title' => $policy['title'],
      ];
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition) {
    $response = $this->client->getPolicy($definition['signature']);

    $definition['chart'] = (array) Yaml::parse($response['data']['attributes']['field_chart']);
    $definition['depends'] = Yaml::parse($response['data']['attributes']['field_depends']);
    $definition['parameters'] = (array) Yaml::parse($response['data']['attributes']['field_parameters']);

    foreach ($response['included'] as $include) {
        if ($include['id'] == $response['data']['relationships']['field_class']['data']['id']) {
            $definition['class'] = $include['attributes']['name'];
            break;
        }
    }

    $tags = [];
    foreach ($response['data']['relationships']['field_tags']['data'] as $relationship) {
        foreach ($response['included'] as $include) {
            if ($include['id'] == $relationship['id'] && $include['type'] == $relationship['type']) {
                $tags[] = $include['attributes']['name'];
            }
        }
    }

    $definition['tags'] = $tags;

    $definition['severity'] = $response['data']['attributes']['field_severity'];
    $definition['description'] = $response['data']['attributes']['field_description'];
    $definition['success'] = $response['data']['attributes']['field_success'];
    $definition['remediation'] = $response['data']['attributes']['field_remediation'];
    $definition['failure'] = $response['data']['attributes']['field_failure'];
    $definition['type'] = $response['data']['attributes']['field_type'];
    $definition['warning'] = $response['data']['attributes']['field_warning'];
    $definition['uuid'] = $definition['signature'];

    unset($definition['signature'], $definition['source']);

    $policy = new Policy();
    return $policy->setProperties($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return -80;
  }

}
