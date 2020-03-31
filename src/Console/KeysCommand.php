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
 * Class KeysCommand.
 */
class KeysCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'keys';

    /**
     * @var object
     */
    private $console;

    /**
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
        $this->addArgument('key', InputArgument::OPTIONAL, $help->key, '');
        $this->addOption('meta', 'm', InputOption::VALUE_NONE, $help->meta);
        $this->addOption('raw', 'r', InputOption::VALUE_NONE, $help->raw);
        $this->addOption('dbname', 'b', InputOption::VALUE_OPTIONAL, $help->dbname);
        $this->addOption('dir', 'p', InputOption::VALUE_OPTIONAL, $help->dir);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->console->io($input, $output);

        $key = $input->getArgument('key');
        if (empty($key)) {
            $key = '';
        }

        $dbname = $input->getOption('dbname');
        $datadir = $input->getOption('dir');

        $isRaw = (!empty($input->getOption('raw')) ? true : false);
        $isMeta = (!empty($input->getOption('meta')) ? true : false);

        $db = $this->console->db;
        $dbChain = $db;

        if (!empty($datadir)) {
            $dbChain = $dbChain->select_dir($datadir);
        }

        if (!empty($dbname)) {
            $dbChain = $dbChain->select($dbname);
        }

        if ($isMeta) {
            $dbChain = $dbChain->meta();
        }

        $results = $dbChain->keys($key);

        $error = $db->lastError();
        if (!empty($error)) {
            $this->console->outputRaw($error);

            return 1;
        }

        if (empty($results)) {
            $this->console->outputNil();

            return 1;
        }

        if ($isRaw) {
            if ($isMeta) {
                $this->console->outputRaw(array_values($results));
            } else {
                foreach (array_values($results) as $k) {
                    $this->console->outputRaw($k);
                }
            }

            return 0;
        }

        $header = [];
        $row = [];

        if ($isMeta) {
            foreach ($results as $k => $arr) {
                if (empty($header)) {
                    $header = array_keys($arr);
                }
                $row[] = array_values($arr);
            }
        } else {
            $header = ['#', 'Keys'];
            foreach ($results as $a => $b) {
                ++$a;
                $row[] = [$a, $b];
            }
        }

        $this->console->outputTable($header, $row);

        return 0;
    }
}
