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
 * Class FindCommand.
 */
class FindCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'find';

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
        $this->addArgument('key', InputArgument::REQUIRED, $help->key);
        $this->addArgument('value', InputArgument::IS_ARRAY | InputArgument::REQUIRED, $help->value);
        $this->addOption('raw', 'r', InputOption::VALUE_NONE, $help->raw);
        $this->addOption('table-type', 't', InputOption::VALUE_OPTIONAL, $help->tabletype, 'vertical');
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
        $value = $input->getArgument('value');
        $tableType = $input->getOption('table-type');

        $dbname = $input->getOption('dbname');
        $datadir = $input->getOption('dir');

        $isRaw = (!empty($input->getOption('raw')) ? true : false);
        $isTableHorizontal = (!empty($tableType) && ('horizontal' === $tableType || Func::startWith($tableType, 'h')) ? true : false);

        $arr = [];
        foreach ($value as $n => $v) {
            if (preg_match('@([^=]+)=([^=]+)@', $v, $mm)) {
                $arr[$n] = [$mm[1], $mm[2]];
            } else {
                $arr[$n] = $v;
            }
        }
        $value = $arr;

        $db = $this->console->db;
        $dbChain = $db;

        if (!empty($datadir)) {
            $dbChain = $dbChain->select_dir($datadir);
        }

        if (!empty($dbname)) {
            $dbChain = $dbChain->select($dbname);
        }

        if (\is_array($value)) {
            $aa = [];
            foreach ($value as $num => $args) {
                $results = $dbChain->find($key, $args);
                if (false !== $results) {
                    $aa[] = $results;
                }
            }

            $results = $aa;
        }

        if (1 === \count($results)) {
            $results = $results[0];
        }

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
            $this->console->outputRaw($results);

            return 0;
        }

        $header = [];
        $row = [];
        $row2 = [];

        if (\is_array($results)) {
            if (Arr::isNumeric($results)) {
                if (1 === \count($results)) {
                    $results = each($results);
                    $header[] = (0 === $results[0] ? 'Match' : $results[0]);
                    $row[] = [$results[1]];
                } else {
                    foreach ($results as $k => $arr) {
                        if (\is_array($arr)) {
                            if (empty($header)) {
                                $header = array_keys($arr);
                            }
                            $row2 = array_values($arr);
                            foreach ($row2 as $a => $b) {
                                if (\is_array($b)) {
                                    if (!Arr::isNumeric($b)) {
                                        $tn = '';
                                        foreach ($b as $bk => $bv) {
                                            if (\is_array($bv)) {
                                                if (!Arr::isNumeric($bv)) {
                                                    $tmn = '';
                                                    foreach ($bv as $bvk => $bvv) {
                                                        $tmn .= '<fg=cyan>'.ucwords($bvk)."</>\n$bvv\n\n";
                                                    }
                                                    $bv = $tmn;
                                                } else {
                                                    $bv = Func::cutStr(Func::exportVar($bv));
                                                }
                                            }
                                            $tn .= '<comment>'.ucwords($bk)."</comment>\n$bv\n\n";
                                        }
                                        $b = trim($tn)."\n";
                                    } elseif (!Arr::isMulti($b)) {
                                        $b = implode("\n", $b);
                                        $b = trim($b);
                                    } else {
                                        $b = Func::cutStr(Func::exportVar($b));
                                    }
                                }
                                $row2[$a] = $b;
                            }
                            $row[] = $row2;
                        } else {
                            if (empty($header)) {
                                $header = array_keys($results);
                            }
                            if (empty($row2)) {
                                $row2 = array_values($results);
                                $row[] = $row2;
                            }
                        }
                    }
                }
            } else {
                $header = array_keys($results);
                $row2 = array_values($results);
                $r = [];
                foreach ($row2 as $n => $k) {
                    if (\is_array($k)) {
                        if (!Arr::isNumeric($k)) {
                            $tn = '';
                            foreach ($k as $bk => $bv) {
                                if (\is_array($bv)) {
                                    if (!Arr::isNumeric($bv)) {
                                        $tmn = '';
                                        foreach ($bv as $bvk => $bvv) {
                                            $tmn .= '<fg=cyan>'.ucwords($bvk)."</>\n$bvv\n\n";
                                        }
                                        $bv = $tmn;
                                    } else {
                                        $bv = Func::cutStr(Func::exportVar($bv));
                                    }
                                }
                                $tn .= '<comment>'.ucwords($bk)."</comment>\n$bv\n\n";
                            }
                            $k = trim($tn)."\n";
                        } elseif (!Arr::isMulti($k)) {
                            $k = implode("\n", $k);
                            $k = trim($k);
                        } else {
                            $k = Func::cutStr(Func::exportVar($k));
                        }
                    } elseif (\is_string($k)) {
                        $k = Func::cutStr($k);
                    }

                    $r[$n] = $k;
                }

                $row[] = $r;
            }
        } else {
            $header[] = 'Match';
            $row[] = [$results];
        }

        $this->console->outputTable($header, $row, $isTableHorizontal);

        return 0;
    }
}
