<?php

/**
 * @file
 * Stub file for the phar build.
 */

use Symfony\Component\Console\Application;
use Drutiny\CommandDiscovery;
use Doctrine\Common\Annotations\AnnotationRegistry;

ini_set('memory_limit', '-1');

const DRUTINY_LIB = __DIR__;

$timezone = 'UTC';

// Set the timezone to the local OS if supported.
if (file_exists('/etc/localtime')) {
  $systemZoneName = readlink('/etc/localtime');
  if (strpos($systemZoneName, 'zoneinfo') !== FALSE) {
    $timezone = substr($systemZoneName, strpos($systemZoneName, 'zoneinfo') + 9);
  }
}

date_default_timezone_set($timezone);

$loader = require DRUTINY_LIB . '/vendor/autoload.php';
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

$version_filename = DRUTINY_LIB . '/VERSION';
$version = 'unknown';
if (file_exists($version_filename)) {
  $version = file_get_contents($version_filename);
}

$application = new Application('Drutiny by Acquia', $version);
$application->addCommands(CommandDiscovery::findCommands());
$application->run();
