<?php

namespace Drutiny\Acquia\Command;

use Drutiny\Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class AcsfListCommand extends Command
{
    protected Config $credentials;

    public function __construct(ContainerInterface $container)
    {
        $this->credentials = $container->get('credentials')->load('acsf:api');
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('acsf:list')
        ->setDescription('List the Site Factory instances setup with Drutiny.');
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->table(['Factory', 'Username'], array_map(function ($key) {
            return [$key, $this->credentials->{$key}['username']];
        }, $this->credentials->keys()));
        return 0;
    }
}
