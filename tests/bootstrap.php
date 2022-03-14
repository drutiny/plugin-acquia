<?php

use Drutiny\Kernel;

const DRUTINY_LIB = '.';

set_time_limit(0);

require dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel('phpunit');
