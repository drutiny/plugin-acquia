<?php

namespace Drutiny\Acquia\Plugin;

use Drutiny\Plugin;

class AcquiaLiftPlugin extends Plugin {

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'acquia:lift';
    }

    public function configure()
    {
        $this->addField(
            'access_key_id',
            "Your Acquia Lift Access Key ID. Acquia will provide you with your keys\nafter you subscribe to Omnichannel. See https://docs.acquia.com/lift/omni/rest_api/",
            static::FIELD_TYPE_CREDENTIAL
            )
          ->addField(
            'secret_access_key',
            "Your Acquia Lift Secret Access Key. Acquia will provide you with your\nkeys after you subscribe to Omnichannel. See https://docs.acquia.com/lift/omni/rest_api/",
            static::FIELD_TYPE_CREDENTIAL
          )
          ->addField(
            'api_endpoint',
            "The API server URL of the Acquia Lift Admin. This information may be\nprovided to you with your keys, and is available from your Insight page.\nThis varies based on your assigned API server.",
          );
    }
}

 ?>
