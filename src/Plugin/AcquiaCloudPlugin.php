<?php

namespace Drutiny\Acquia\Plugin;

use Drutiny\Plugin;

class AcquiaCloudPlugin extends Plugin {

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
            "Your Key ID to connect to the Acquia Cloud API v2 with. To generate an\nAPI access token, login to https://cloud.acquia.com, then visit\nhttps://cloud.acquia.com/#/profile/tokens, and click **Create Token**:",
            static::FIELD_TYPE_CREDENTIAL
            )
          ->addField(
            'secret',
            'Your API secret to connect to the Acquia Cloud API v2 with:',
            static::FIELD_TYPE_CREDENTIAL
          );
    }
}

 ?>
