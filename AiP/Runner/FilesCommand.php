<?php
namespace AiP\Runner;


use AiP\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class FilesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('files')
            ->addArgument('directory', InputArgument::OPTIONAL, 'Serve files from this directory', getcwd())
            ->setDescription('Run file server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');

        $path = $input->getArgument('directory');

        if (!is_dir($path)) {
            $output->writeln("<error>{$path} is not a valid directory</error>");
            exit(1);
        }

        $server = array(
            'protocol' => 'HTTP',
            'transport' => 'Socket',
            'socket' => 'tcp://127.0.0.1:8080',
            'min-children' => 1,
            'max-children' => 1,
            'app' => array(
                'class' => 'AiP\App\FileServe',
                'parameters' => array($path),
                'file' => '',
                'middlewares' => array(
                    'Logger',
                    array('class' => 'AiP\Middleware\Directory', 'parameters' => array($path, true)),
                    'ConditionalGet',
                ),
            ),
        );

        $runner = new Runner($path);
        $runner->addServer($server);

        $line = $formatter->formatSection('app+', 'Serving files from "'.$path.'" via '.$server['protocol'].' at '.$server['socket'].'. ('.$server['min-children'].'-'.$server['max-children'].' workers)');
        $output->writeln($line);

        $output->writeln('<info>Starting workers…</info>');
        $runner->go();
    }
}
