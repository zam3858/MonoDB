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

use Monodb\Functions as Func;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DelCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'del';

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
        $this->addArgument('key', InputArgument::IS_ARRAY | InputArgument::REQUIRED, $help->key);
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

        $keys = $input->getArgument('key');
        $isRaw = (!empty($input->getOption('raw')) ? true : false);

        $db = $this->console->db;
        $dbChain = $db;

        if (!empty($datadir)) {
            $dbChain = $dbChain->select_dir($datadir);
        }

        if (!empty($dbname)) {
            $dbChain = $dbChain->select($dbname);
        }

        $cnt = 0;

        foreach ($keys as $n => $key) {
            if (Func::hasWith($key, '*')) {
                $keyr = $dbChain->keys($key);
                if (!empty($keyr)) {
                    $key = $keyr[0];
                }
            }

            if (false !== $dbChain->delete($key)) {
                ++$cnt;
            }
        }

        $error = $db->lastError();
        if (!empty($error)) {
            $this->console->outputRaw($error);

            return 1;
        }

        if ($isRaw) {
            $this->console->outputRaw($cnt);

            return 0;
        }

        $header = ['Removed'];
        $row[] = [$cnt];

        $this->console->outputTable($header, $row);

        return 0;
    }
}
