<?php
/*
 * This file is part of the MonoDB package.
 *
 * (c) Nawawi Jamili <nawawi@rutweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monodb\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DecrCommand.
 */
class DecrCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'decr';

    /**
     * @var object
     */
    private $console;

    /**
     * Init.
     *
     * @param object $parent Console object
     *
     * @return void
     */
    public function __construct(object $parent)
    {
        $this->console = $parent;
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $info = $this->console->getHelp(self::$defaultName);
        $this->setDescription($info->desc)->setHelp($info->help);

        $help = $this->console->getHelp('args');
        $this->addArgument('key', InputArgument::REQUIRED, $help->key);
        $this->addArgument('number', InputArgument::OPTIONAL, $help->decrnumber, '');
        $this->addOption('raw', 'r', InputOption::VALUE_NONE, $help->raw);
    }

    /**
     * Call to execute command.
     *
     * @param InputInterface  $input  Input Interface
     * @param OutputInterface $output Output Interface
     *
     * @return int Returns 0 if successful, 1 otherwise
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->console->io($input, $output);

        $key = $input->getArgument('key');
        $number = $input->getArgument('number');

        $isRaw = (!empty($input->getOption('raw')) ? true : false);

        $db = $this->console->db;

        $results = $db->decr($key, $number);

        $error = $db->lastError();
        if (!empty($error)) {
            $this->console->outputRaw($error);

            return 1;
        }

        if ($isRaw) {
            $this->console->outputRaw($results);

            return 0;
        }

        $header = ['Decrement'];
        $row[] = [$results];

        $this->console->outputTable($header, $row);

        return 0;
    }
}
