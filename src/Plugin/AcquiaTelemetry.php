<?php

namespace Drutiny\Acquia\Plugin;

use Drutiny\Plugin;

class AcquiaTelemetry extends Plugin {

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'acquia:telemetry';
    }

    public function configure()
    {
        $this->addField(
            'consent',
            "Enable anonymous sharing of usage and performance data with Acquia",
            static::FIELD_TYPE_CONFIG,
            null,
            null,
            static::FIELD_CONFIRMATION_QUESTION
          );
    }
}

 ?>
