<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Attribute\AsSource;
use Drutiny\PolicySource\LocalFs;
use Drutiny\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSource(name: 'ACQUIA', weight: -80, cacheable: false)]
#[Autoconfigure(tags: ['policy.source'])]
class PolicySource extends LocalFs
{
    public function __construct(
        protected Finder $finder,
        protected Settings $settings,
        protected LoggerInterface $logger,
        protected CacheInterface $cache,
        protected AsSource $source
    ) {
        $this->name = $source->name;

        $policy_dir = realpath(__DIR__ . '/../../' . $settings->get('acquia.policy.library.fs'));

        if (!$policy_dir) {
          throw new \RuntimeException('Policy directory not found');
        }

        // Ensure the policy directory is available.

        $this->finder
            ->files()
            ->depth('<=1')
            ->in($policy_dir)
            ->name('*.policy.yml');
    }

}