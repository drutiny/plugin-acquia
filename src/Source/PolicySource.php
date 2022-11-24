<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Policy;
use Drutiny\PolicySource\PolicySourceInterface;
use Drutiny\Acquia\Api\SourceApi;
use Drutiny\LanguageManager;
use Symfony\Component\Yaml\Yaml;

/**
 * Load policies from CSKB.
 */
class PolicySource extends SourceBase implements PolicySourceInterface {

  const API_ENDPOINT = 'jsonapi/node/policy';

  /**
   * {@inheritdoc}
   */
  public function getList(LanguageManager $languageManager) {
    $list = [];
    $params = $this->getRequestParams();
    $params['query']['fields[node--policy]'] = 'field_name,field_class,title';
    $params['query']['fields[taxonomy_term--drutiny_audit_classes]'] = 'name';
    $params['query']['include'] = 'field_class';

    foreach ($this->client->getList($this->getApiPrefix().self::API_ENDPOINT, $params) as $item) {
      $list[$item['field_name']] = [
        'uuid' => $item['uuid'],
        'name' => $item['field_name'],
        'class' => $item['field_class'][0]['attributes']['name'],
        'title' => $item['title'],
        'language' => $languageManager->getCurrentLanguage(),
      ];
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition) {
    $query = $this->getRequestParams();
    $query['query']['include'] = 'field_tags';
    $endpoint = $this->getApiPrefix().self::API_ENDPOINT.'/'.$definition['uuid'];
    $response = $this->client->get($endpoint, $query);

    $fields = $response['data']['attributes'];
    $definition['chart'] = !empty($fields['field_chart']) ? Yaml::parse($fields['field_chart']) : [];
    $definition['depends'] = !empty($fields['field_depends']) ? Yaml::parse($fields['field_depends']) : [];
    $definition['parameters'] = !empty($fields['field_parameters']) ? Yaml::parse($fields['field_parameters']) : [];
    $definition['build_parameters'] = !empty($fields['field_build_parameters']) ? Yaml::parse($fields['field_build_parameters']['value']) : [];

    if (isset($definition['parameters']['_chart'])) {
      unset($definition['parameters']['_chart']);
    }

    foreach ($response['included'] ?? [] as $include) {
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

    unset($definition['source']);

    switch ($definition['severity'] ?? false) {
        case Policy::SEVERITY_LOW:
        case 'low':
            $definition['severity'] = 'low';
            break;
        case Policy::SEVERITY_NORMAL:
        case 'normal':
            $definition['severity'] = 'normal';
            break;
        case Policy::SEVERITY_HIGH:
        case 'high':
            $definition['severity'] = 'high';
            break;
        case Policy::SEVERITY_CRITICAL:
        case 'critical':
            $definition['severity'] = 'critical';
            break;
        default:
            $definition['severity'] = 'normal';
            break;
    }

    $policy = new Policy();
    return $policy->setProperties($definition);
  }

}
