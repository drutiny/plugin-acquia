<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Entity\Exception\DataNotFoundException;
use Drutiny\Helper\TextCleaner;
use Drutiny\Target\TargetInterface;
use InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;

class AcquiaCli implements CloudApiInterface {

    const DEFAULT_LOCATIONS = [
      'vendor/bin',
      '/usr/local/bin',
      '$HOME/.composer/vendor/bin',
      '/usr/bin',
      '/bin',
    ];

    protected string $bin;

    public function __construct(protected CacheInterface $cache) {
      // Look for a local version of ACLI.
      $bins = array_filter(self::DEFAULT_LOCATIONS, function ($dir) {
        return file_exists($dir . '/acli');
      });
      if (count($bins) > 1) {
        $bins = array_filter($bins, fn ($bin) => is_executable("$bin/acli"));
      }
      if (!empty($bins)) {
        $this->bin = reset($bins) . "/acli";
      }
    }

    public function isAvailable(): bool {
      return isset($this->bin) && !empty($this->bin);
    }

    /**
     * {@inheritdoc}
     */
    public function findApplication($realm, $site):array
    {
        $apps = $this->cache->get('acquia.cloud.applications', function (CacheItemInterface $item) {
          $item->expiresAfter(86400);
          $output = $this->prepareCommand('api:applications:list')->mustRun()->getOutput();
          return json_decode($output, TRUE);
        });

        foreach ($apps as $app) {
            if (empty($app['hosting'])) {
                continue;
            }
            list($stage, $sitegroup) = explode(':', $app['hosting']['id'], 2);

            if ($realm == $stage && $site == $sitegroup) {
                return $app;
            }
        }
        throw new DataNotFoundException("Cannot find Acquia application matching target criteria: $realm:$site.");
    }

    /**
     * {@inheritdoc}
     */
    public function findEnvironment($uuid, $env):array
    {
        foreach ($this->getEnvironments($uuid) as $environment) {
            if ($environment['name'] == $env) {
                return $environment;
            }
        }
        throw new DataNotFoundException("Cannot find Acquia application environment: $env.");
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironments(string $uuid): array {
        return $this->cache->get('acquia.cloud.'.$uuid.'.environments', function (CacheItemInterface $item) use ($uuid) {
            $item->expiresAfter(86400);
            return $this->runApiCommand('api:applications:environment-list', [$uuid]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment($uuid):array
    {
        return $this->cache->get('acquia.cloud.environment.'.$uuid, function (CacheItemInterface $item) use ($uuid) {
            $item->expiresAfter(86400);
            return $this->runApiCommand('api:environments:find', [$uuid]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getApplication($uuid):array
    {
        return $this->cache->get('acquia.cloud.application.'.$uuid, function (CacheItemInterface $item) use ($uuid) {
            $item->expiresAfter(86400);
            return $this->runApiCommand('api:applications:find', [$uuid]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function mapToTarget(array $data, TargetInterface $target, $namespace):void
    {
        foreach ($data as $key => $value) {
            if ('_' == substr($key, 0, 1)) {
                continue;
            }
            $target[$namespace.'.'.$key] = $value;
        }
    }

    /**
     * Run an Acquia CLI API command and return the json decoded response.
     */
    public function runApiCommand(string $command, array $args = [], array $options = []): array {
      if (!str_starts_with($command, 'api')) {
        throw new InvalidArgumentException("Command is not allowed. Must use and api: prefixed command.");
      }
      if (!$this->isAvailable()) {
        throw new AcquiaCliNotAvailableException();
      }
      $cid = md5(json_encode([$command, $args, $options]));
      $process = $this->prepareCommand($command, $args, $options);
      return $this->cache->get($cid, function (CacheItemInterface $item) use ($process) {
        $item->expiresAfter(3600);
        $output = $process->mustRun()->getOutput();
        return TextCleaner::decodeDirtyJson($output);
      });
    }

    /**
     * Prepare a command to run with Acquia CLI.
     */
    protected function prepareCommand(string $command, array $args = [], array $options = []):Process {
      $cmd = $this->bin . ' ' . $command . ' ' . implode(' ', $args);

      $opts = [];
      foreach ($options as $name => $value) {
        $opt_type = (strlen($name) > 1) ? '--' : '-';
        // Flag opts.
        if (is_null($value) || is_bool($value)) {
          $opts[] = $opt_type . $name;
          continue;
        }
        if ($opt_type == '--') {
          $opts[] = $opt_type . '=' . $value;
        }
        else {
          $opts[] = $opt_type . ' ' . $value;
        }
      }
      if (!empty($opts)) {
        $cmd .= ' ' . implode(' ', $opts);
      }

      return Process::fromShellCommandline($cmd);
    }
}
