<?php

namespace Drutiny\Acquia\Command;

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
class AcsfSetupCommand extends Command
{
    protected $container;
    protected $credentials;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->credentials = $container->get('credentials')->setNamespace('acsf:api');
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('acsf:setup')
        ->setDescription('Register API credentials for a site factory.')
        ->addArgument(
            'sitefactory',
            InputArgument::REQUIRED,
            'The domain name for the site factory console.',
        )
        ->addOption(
            'username',
            'u',
            InputOption::VALUE_OPTIONAL,
            'A username to connect to the site factory API with.'
        )
        ->addOption(
            'key',
            'k',
            InputOption::VALUE_OPTIONAL,
            'An API key associated with the username to access the site factory API with.'
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->credentials->{$input->getArgument('sitefactory')} = [
          'username' => $input->getOption('username') ?? $io->ask("Username"),
          'key' => $input->getOption('key') ?? $io->ask("Key"),
        ];

        $this->credentials->doWrite();

        $io->success("ACSF credentials for ".$input->getArgument('sitefactory')." have been saved.");
        return 0;
    }
}
