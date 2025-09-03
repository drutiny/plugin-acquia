<?php

namespace Drutiny\Acquia\Source;

use Drutiny\Attribute\AsSource;
use Drutiny\ProfileFactory;
use Drutiny\ProfileSource\ProfileSourceLocalFs;
use Drutiny\Settings;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Load profiles from CSKB.
 */
#[AsSource(name: 'ACQUIA', weight: -80)]
#[Autoconfigure(tags: ['profile.source'])]
class ProfileSource extends ProfileSourceLocalFs
{
    use SourceTrait;
    public const API_ENDPOINT = 'jsonapi/node/profile';
    protected string $baseUrl;

    public function __construct(
      protected Finder $finder, 
      protected Settings $settings, 
      protected AsSource $source, 
      protected CacheInterface $cache, 
      protected ProfileFactory $profileFactory
    )
    {
        $profile_dir = realpath(__DIR__ . '/../../' . $settings->get('acquia.profile.library.fs'));

        if (!$profile_dir) {
          throw new \RuntimeException('Profile directory not found');
        }

        // Ensure the profile directory is available.

        $this->finder
            ->files()
            ->depth('<=1')
            ->in($profile_dir)
            ->name('*.profile.yml');
    }
  
}
