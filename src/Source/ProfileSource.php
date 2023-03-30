<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Acquia\Api\SourceApi;
use Drutiny\Attribute\AsSource;
use Drutiny\LanguageManager;
use Drutiny\Policy\Severity;
use Drutiny\Profile;
use Drutiny\ProfileFactory;
use Drutiny\ProfileSource\AbstractProfileSource;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Load profiles from CSKB.
 */
#[AsSource(name: 'ACQUIA', weight: -80)]
#[Autoconfigure(tags: ['profile.source'])]
class ProfileSource extends AbstractProfileSource
{
    use SourceTrait;
    public const API_ENDPOINT = 'jsonapi/node/profile';

    public function __construct(
      protected SourceApi $client,
      protected LanguageManager $languageManager,
      AsSource $source,
      CacheInterface $cache,
      ProfileFactory $profileFactory
      )
    {
      parent::__construct(source: $source, cache: $cache, profileFactory: $profileFactory);
    }
  

    /**
     * {@inheritdoc}
     */
    protected function doGetList(LanguageManager $languageManager): array
    {
        $list = [];
        $query = $this->getRequestParams();
        $query['query']['fields[node--profile]'] = 'title,field_name';

        foreach ($this->client->getList($this->getApiPrefix().self::API_ENDPOINT, $query) as $item) {
          $list[$item['field_name']] = [
            'name' => $item['field_name'],
            'title' => $item['title'],
            'uuid' => $item['uuid'],
            'language' => $languageManager->getCurrentLanguage(),
          ];
        }
        return $list;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoad(array $definition): Profile
    {
        $query = $this->getRequestParams();
        $endpoint = $this->getApiPrefix().self::API_ENDPOINT.'/'.$definition['uuid'];
        $response = $this->client->get($endpoint, $query);

        $fields = $response['data']['attributes'];
        $policies = Yaml::parse($fields['field_policies']);

        foreach ($policies as $policy_name => $info) {
            $severity = isset($info['severity']) ? Severity::fromInt($info['severity']) : Severity::getDefault();
            $policies[$policy_name]['severity'] = $severity->value;
        }

        $definition = [
          'title' => $fields['title'],
          'name' => $fields['field_name'],
          'uuid' => $definition['uuid'],
          'description' => $fields['field_description'] ?? '',
          'policies' => $policies,
          'include' => $fields['field_include'],
          'format' => [
            'html' => [
              'template' => $fields['field_html_template'] ?? '',
              'content' => $fields['field_html_content'] ?? '',
            ]
          ]
        ];

        if (isset($fields['field_dependencies'])) {
            $dependencies = Yaml::parse($fields['field_dependencies']);
            foreach ($dependencies as $policy_name => $info) {
                $severity = $info['severity'] ?? Severity::getDefault()->value;
                $severity = is_numeric($severity) ? Severity::fromInt($severity)->value : Severity::from($severity)->value;
                $dependencies[$policy_name]['severity'] = $severity;
            }
            $definition['dependencies'] = $dependencies;
        }

        try {
            $definition['excluded_policies'] = !empty($fields['field_excluded_policies']) ? Yaml::parse($fields['field_excluded_policies']) : [];
        } catch (ParseException $e) {
        }

        return parent::doLoad($definition);
    }
}
