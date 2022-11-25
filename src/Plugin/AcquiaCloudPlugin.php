<?php

namespace Drutiny\Acquia\Plugin;

use Drutiny\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AcquiaCloudPlugin extends Plugin
{
    public function __construct(ContainerInterface $container, InputInterface $input, OutputInterface $output)
    {
        parent::__construct($container, $input, $output);

        $homedir = $container->getParameter('user_home_dir');
        $cloud_api_conf = $homedir . '/.acquia/cloud_api.conf';

        if (!file_exists($cloud_api_conf) || !is_readable($cloud_api_conf)) {
            return;
        }

        $container->get('logger')->notice("Found .acquia/cloud_api.conf. Cloud API keys will be used from this file.");

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
        if (isset($key)) {
          $this->setField('key_id', $key);
          $this->setField('secret', $secret);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'acquia:cloud';
    }

    public function configure()
    {
        $this->addField(
            'key_id',
            "Your Key ID to connect to the Acquia Cloud API v2 with. To generate an\nAPI access token, login to https://cloud.acquia.com, then visit\nhttps://cloud.acquia.com/a/profile/tokens, and click **Create Token**:",
            static::FIELD_TYPE_CREDENTIAL
        )
          ->addField(
              'secret',
              'Your API secret to connect to the Acquia Cloud API v2 with:',
              static::FIELD_TYPE_CREDENTIAL
          );
    }
}
