<?php

namespace Drutiny\Acquia\Audit\Multisite;

use Drutiny\Attribute\DataProvider;
use Drutiny\Attribute\Parameter;
use Drutiny\Attribute\Type;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\AuditFactory;
use Drutiny\Policy\Dependency;
use Drutiny\Settings;
use Generator;

#[Parameter(name: 'class.name', description: 'The class to run over each site as a target', type: Type::STRING, mode: Parameter::REQUIRED)]
#[Parameter(name: 'class.parameters', description: 'The parameters to pass to the audit class', type: Type::HASH, default: [])]
#[Parameter(name: 'target.limit', description: 'Limit the number of targets to assess', type: Type::INTEGER)]
#[Dependency('Acquia.isCloudEnvironment')]
class MultiTargetAnalysis extends AbstractAnalysis {
    #[DataProvider]
    protected function auditEachSiteAsTarget(AuditFactory $auditFactory, Settings $settings):void {
        $results = $errors = [];

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

        $max = $this->getParameter('target.limit', count($this->target['acquia.cloud.environment.domains']));
        $domains = array_slice($this->target['acquia.cloud.environment.domains'], 0, $max);

        foreach ($domains as $domain) {
            $target = clone $this->target;
            $target->setUri($domain);

            // Run audits once per site as defined DB and not
            // by domain.
            if (in_array($target['drush.db-name'], array_keys($dbs))) {
                $map[$domain] = $dbs[$target['drush.db-name']];
                continue;
            }
            $dbs[$target['drush.db-name']] = $domain;
            $map[$domain] = $dbs[$target['drush.db-name']];
            yield $target;
        }
        $this->set('map', $map);
    }
}

