<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Acquia\Api\SourceApi;
use Drutiny\Attribute\AsSource;
use Drutiny\Policy;
use Drutiny\LanguageManager;
use Drutiny\Policy\Severity;
use Drutiny\PolicySource\AbstractPolicySource;
use Drutiny\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Load policies from CSKB.
 */
#[AsSource(name: 'ACQUIA', weight: -80)]
#[Autoconfigure(tags: ['policy.source'])]
class PolicySource extends AbstractPolicySource {
  use SourceTrait;

  protected string $baseUrl;

  const API_ENDPOINT = 'jsonapi/node/policy';

  public function __construct(
    protected SourceApi $client,
    protected LanguageManager $languageManager,
    AsSource $source,
    CacheInterface $cache,
    protected LoggerInterface $logger,
    Settings $settings,
    )
  {
    $this->baseUrl = $settings->get('acquia.api.base_uri');
    parent::__construct(source: $source, cache: $cache);
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetList(LanguageManager $languageManager):array
  {
    $list = [];
    $params = $this->getRequestParams();
    $params['query']['fields[node--policy]'] = 'field_name,field_class,title,drupal_internal__nid';
    $params['query']['fields[taxonomy_term--drutiny_audit_classes]'] = 'name';
    $params['query']['include'] = 'field_class';

    $langcode = LanguageMap::fromLanguageManager($languageManager)->value;

    foreach ($this->client->getList($this->getApiPrefix().self::API_ENDPOINT, $params) as $item) {
      $list[$item['field_name']] = [
        'uuid' => $item['uuid'],
        'name' => $item['field_name'],
        'class' => $item['field_class'][0]['attributes']['name'],
        'title' => $item['title'],
        'language' => $langcode,
        'uri' => $this->baseUrl . 'node/' . $item['drupal_internal__nid'],
      ];
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoad(array $definition):Policy
  {
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
    // Clean up chart definitions.
    foreach ($definition['chart'] as $key => $chart) {
      // Remove empty properties allowing defaults to take place.
      $definition['chart'][$key] = array_filter($chart, function($v) {
        // Booleans remain but empty strings and null values go.
        return !((is_string($v) && empty($v)) || is_null($v));
      });
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
    $definition['remediation'] = $response['data']['attributes']['field_remediation'] ?? '';
    $definition['failure'] = $response['data']['attributes']['field_failure'];
    $definition['type'] = $response['data']['attributes']['field_type'];
    $definition['warning'] = $response['data']['attributes']['field_warning'] ?? '';

    unset($definition['source']);

    // If severity isn't set, set it to the default value.
    $definition['severity'] ??= Severity::getDefault()->value;

    if (!is_string($definition['severity'])) {
      $definition['severity'] = Severity::fromInt($definition['severity'])->value;
    }
    elseif (!Severity::has($definition['severity'])) {
      $this->logger->warning("Severity of '{$definition['severity']}' is not an allowed value in Policy '{$definition['name']}' from {$this->source->name}.");
      $definition['severity']  = Severity::getDefault()->value;
    }

    return parent::doLoad($definition);
  }

}
