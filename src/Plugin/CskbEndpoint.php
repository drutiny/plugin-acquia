<?php

namespace Drutiny\Acquia\Plugin;

use Drutiny\Plugin;

class CskbEndpoint extends Plugin {

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cskb:endpoint';
    }

    public function configure()
    {
        $this->addField(
            'base_url',
            "Where to find CSKB (https://cskb.acquia.com)",
            static::FIELD_TYPE_CONFIG
          )
          ->addField(
            'share_key',
            "When using Remote IDE, you may provide a share key to access the Remote IDE environment from drutiny.",
            static::FIELD_TYPE_CREDENTIAL
          );
    }
}

 ?>
