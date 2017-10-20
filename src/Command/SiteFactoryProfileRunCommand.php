<?php

namespace Drutiny\Acquia\Command;

use Drutiny\Command\ProfileRunCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SiteFactoryProfileRunCommand extends ProfileRunCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('site-factory:profile:run')
      ->addOption(
        'factory',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The URL to the site factory UI. e.g. https://www.demo.acsitefactory.com/'
      )
      ->addOption(
        'api-username',
        'u',
        InputOption::VALUE_REQUIRED,
        'Your Site Factory Platform Admin username'
      )
      ->addOption(
        'api-key',
        'k',
        InputOption::VALUE_REQUIRED,
        'Your Site Factory Platform Admin API key'
      )
      ->addOption(
        'primary-only',
        'p',
        InputOption::VALUE_NONE,
        'Allow audit primary sites in site collections'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $client = new \GuzzleHttp\Client([
      'base_uri' => $input->getOption('factory') . '/api/v1/',
      'auth' => [$input->getOption('api-username'), $input->getOption('api-key')],
    ]);

    $response = $client->request('GET', 'sites', ['query' => [
      'limit' => 1000,
    ]]);
    $json = $response->getBody();
    $sites = json_decode($json, TRUE);
    $sites = $sites['sites'];

    if ($input->getOption('primary-only')) {
      $sites = array_filter($sites, function ($site) {
        return $site['is_primary'];
      });
    }

    $uri = array_map(function ($site) {
      return $site['domain'];
    }, $sites);

    $input->setOption('uri', $uri);

    parent::execute($input, $output);
  }

}



 ?>
