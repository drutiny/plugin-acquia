<?php

namespace Drutiny\Acquia\Audit\Multisite;

use Drutiny\Attribute\DataProvider;
use Drutiny\Attribute\Parameter;
use Drutiny\Attribute\Type;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\AuditFactory;
use Drutiny\Policy\Dependency;
use Generator;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[Parameter(name: 'class.name', description: 'The class to run over each site as a target', type: Type::STRING, mode: Parameter::REQUIRED)]
#[Parameter(name: 'class.parameters', description: 'The parameters to pass to the audit class', type: Type::HASH, default: [])]
#[Parameter(name: 'target.limit', description: 'Limit the number of targets to assess', type: Type::INTEGER)]
#[Dependency('Acquia.isCloudEnvironment')]
class MultiTargetAnalysis extends AbstractAnalysis {
    #[DataProvider]
    protected function auditEachSiteAsTarget(AuditFactory $auditFactory):void {
        $results = [];

        $policy = $this->policy->with(
            class: $this->getParameter('class.name'),
            parameters: $this->getParameter('class.parameters'),
        );

        // Run the audit for each site in the multisite.
        foreach ($this->getTargets() as $target) {
            $audit = $auditFactory->get($policy, $target);
            // Collect just the tokens for each target by URI.
            $results[$target->getUri()] = $audit->execute($policy)->tokens;
        }
        $this->set('results', $results);
    }

    /**
     * @return \Drutiny\Target\TargetInterface[]
     */
    protected function getTargets(): Generator {
        $dbs = [];
        $map = [];

        $limit = $this->getParameter('target.limit', count($this->target['acquia.cloud.environment.domains']));
        $domains = $this->target['acquia.cloud.environment.domains'];

        foreach ($domains as $domain) {
            // Wildcards won't bootstrap Drupal so ignore them.
            if (strpos($domain, '*') !== false) {
                continue;
            }
            try {
                $target = clone $this->target;
                $target->setUri($domain);
            }
            // Failed to load target. Ignore.
            catch (ProcessFailedException $e) {
                continue;
            }

            // Run audits once per site as defined DB and not
            // by domain.
            if (in_array($target['drush.db-name'], array_keys($dbs))) {
                $map[$domain] = $dbs[$target['drush.db-name']];
                continue;
            }
            $dbs[$target['drush.db-name']] = $domain;
            $map[$domain] = $dbs[$target['drush.db-name']];
            yield $target;

            if (count($dbs) >= $limit) {
                break;
            }
        }
        $this->set('map', $map);
    }
}

