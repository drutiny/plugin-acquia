<?php

namespace Drutiny\Acquia\Plugin;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use Drutiny\Plugin;

class AcquiaCloudPlugin extends Plugin
{
    protected function configure()
    {
      if ($this->isInstalled()) {
        return;
      }
      $this->checkForAcquiaCLi();
    }

    /**
     * Get the Acquia CLoud connector.
     */
    public function getApiClient(): Client
    {
      $connector = new Connector([
        'key' => $this->key_id,
        'secret' => $this->secret
      ]);
      return Client::factory($connector);
    }

    /**
     * Use Acquia CLI credentials if they're available.
     */
    protected function checkForAcquiaCli():void
    {
        $homedir = $this->settings->get('user_home_dir');
        $cloud_api_conf = $homedir . '/.acquia/cloud_api.conf';

        if (!file_exists($cloud_api_conf) || !is_readable($cloud_api_conf)) {
            return;
        }

        $this->logger->notice("Found .acquia/cloud_api.conf. Cloud API keys will be used from this file.");

        // Re-use the values in cloud_api.conf.
        $conf = json_decode(file_get_contents($cloud_api_conf), true);

        if (isset($conf['keys'])) {
          $key = key($conf['keys']);
          $secret = $conf['keys'][$key]['secret'];
        }
        elseif (isset($conf['key']) && isset($conf['secret'])) {
          $key = $conf['key'];
          $secret = $conf['secret'];
        }
        if (!isset($key, $secret)) {
          return;
        }
        $this->saveAs([
          'key_id' => $key,
          'secrect' => $secret
        ]);
    }
}
