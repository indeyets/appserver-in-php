<?php
namespace AiP\Runner;


use AiP\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class AppCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app')
            ->addArgument('config-path', InputArgument::OPTIONAL, 'Path to configuration file', getcwd())
            ->setDescription('Run application server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');

        $path = $input->getArgument('config-path');

        if (is_dir($path)) {
            $path = $path.'/aip.yaml';
        }

        if (!file_exists($path)) {
            $output->writeln("<error>Configuration file not found: {$path}</error>");
            exit(1);
        }

        $output->writeln('<info>Loading configuration…</info>');
        $config = Yaml::parse($path);

        $runner = new Runner(dirname($path));

        foreach ($config['servers'] as $server) {
            if (!isset($server['transport'])) {
                $server['transport'] = 'Socket';
            }

            $runner->addServer($server);

            $line = $formatter->formatSection('app+', $server['app']['class'].' server via '.$server['protocol'].' at '.$server['socket'].'. ('.$server['min-children'].'-'.$server['max-children'].' workers)');
            $output->writeln($line);
        }


        $output->writeln('<info>Starting workers…</info>');
        $runner->go();
    }
}
