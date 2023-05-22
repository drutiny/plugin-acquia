<?php

namespace Drutiny\Acquia\Audit;

use AcquiaCloudApi\Connector\Client;
use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Attribute\UseService;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Sandbox\Sandbox;
use Psr\Cache\CacheItemInterface;

#[UseService(CloudApi::class, 'setCloudApi')]
class CloudApiAnalysis extends AbstractAnalysis {

    protected CloudApi $api;

    public function setCloudApi(CloudApi $api)
    {
        $this->api = $api;
    }

    public function configure():void
    {
        parent::configure();
        
        $this->addParameter(
            'is_legacy',
            static::PARAMETER_OPTIONAL,
            'A boolean flag indicating if the policy is legacy.',
            false
        );
        $this->addParameter(
            'calls',
            static::PARAMETER_IS_ARRAY | static::PARAMETER_OPTIONAL,
            'An array of API calls to make to the Cloud API.',
            []
        );
    }

    protected function gather(Sandbox $sandbox)
    {
        $this->set('drush', $this->target['drush']->export());
        $this->set('app', $this->target['acquia.cloud.application']->export());

        foreach ($this->getParameter('calls') as $name => $call) {
            if (!is_array($call) || !isset($call['path'])) {
                $this->logger->error("$name should be an array containing a path and optionally a verb (e.g. GET) or an options array. Skipping.");
                continue;
            }
            $response = $this->call(...$call);
            
            if ($this->getParameter('is_legacy')) {
                $response = ['_embedded' => ['items' => (array) $response]];
            }
            // Use the name of a call to set a token in the audit.
            $this->set($name, $response);
        }
    }

    /**
     * Make a call to Acquia Cloud API.
     */
    protected function call(string $path, string $verb = 'get', array $options = []) {
        // Allow variable replacement inside of path calls.
        $path = $this->interpolate($path);
        $call = new CloudApiAnalysisCall(verb: $verb, path: $path, options: $options);

        return $this->cache->get($call->getCacheKey(), function (CacheItemInterface $cache) use ($call) {
            $cache->expiresAfter(120);
            $client = $this->api->getApiClient();
            $call->addQuery($client);
            return $client->request($call->verb, $call->path, $call->options);
        });
    }
}

class CloudApiAnalysisCall {
    public function __construct(
        public readonly string $path,
        public readonly string $verb = 'get',
        public readonly array $options = []
    )
    {}

    public function addQuery(Client $client):void
    {
        if (!isset($this->options['query'])) {
            return;
        }
        foreach ($this->options['query'] as $key => $value) {
            $client->addQuery($key, $value);
        }
    }

    public function getCacheKey():string
    {
        return md5($this->verb.$this->path.print_r($this->options, 1));
    }
}