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

use Monodb\Arrays as Arr;
use Monodb\Functions as Func;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InfoCommand.
 */
class InfoCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'info';

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
        $this->addArgument('section', InputArgument::OPTIONAL, $help->section, '');
        $this->addOption('raw', 'r', InputOption::VALUE_NONE, $help->raw);
        $this->addOption('table-type', 't', InputOption::VALUE_OPTIONAL, $help->tabletype, 'vertical');
        $this->addOption('dbname', 'b', InputOption::VALUE_OPTIONAL, $help->dbname);
        $this->addOption('dir', 'p', InputOption::VALUE_OPTIONAL, $help->dir);
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

        $section = $input->getArgument('section');
        if (empty($section)) {
            $section = '';
        }

        $section = strtolower($section);
        $tableType = $input->getOption('table-type');

        $dbname = $input->getOption('dbname');
        $datadir = $input->getOption('dir');

        $isRaw = (!empty($input->getOption('raw')) ? true : false);
        $isTableHorizontal = (!empty($tableType) && ('horizontal' === $tableType || Func::startWith($tableType, 'h')) ? true : false);

        $db = $this->console->db;
        $dbChain = $db;

        if (!empty($datadir)) {
            $dbChain = $dbChain->select_dir($datadir);
        }

        if (!empty($dbname)) {
            $dbChain = $dbChain->select($dbname);
        }

        $results = $dbChain->info($section);

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
            if (Func::hasWith($section, 'config')) {
                $line = '';
                foreach ($results as $k => $v) {
                    $line .= $k.'='.$v."\n";
                }
                $results = trim($line);
            }
            $this->console->outputRaw($results);

            return 0;
        }

        $header = [];
        $row = [];
        $row2 = [];

        if (!\is_array($results)) {
            $section = ucfirst($section);
            $header = [$section];
            $row[] = [$results];
        } else {
            $header = array_keys($results);
            $row2 = array_values($results);
            $r = [];
            foreach ($row2 as $n => $k) {
                if (\is_array($k)) {
                    $k = array_map(
                        function ($arr) {
                            if (!\is_array($arr)) {
                                return $arr;
                            }
                            foreach ($arr as $a => $b) {
                                if (\is_string($b)) {
                                    $arr[$a] = Func::cutStr($b);
                                }
                            }

                            return $arr;
                        },
                        $k
                    );
                    if (!Arr::isNumeric($k)) {
                        $tn = '';
                        foreach ($k as $bk => $bv) {
                            if (\is_array($bv)) {
                                $bv = Func::exportVar($bv);
                            }
                            if ($isTableHorizontal) {
                                $tn .= '<comment>'.ucwords($bk).":</comment> $bv\n\n";
                            } else {
                                $tn .= '<comment>'.ucwords($bk)."</comment>\n$bv\n\n";
                            }
                        }
                        $k = trim($tn)."\n";
                    } else {
                        $k = Func::exportVar($k);
                    }
                } elseif (\is_string($k)) {
                    $k = Func::cutStr($k);
                }

                $r[$n] = $k;
            }

            $row[] = $r;
        }

        $this->console->outputTable($header, $row, $isTableHorizontal);

        return 0;
    }
}
