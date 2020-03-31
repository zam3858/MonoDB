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
 * Class GetCommand.
 */
class GetCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'get';

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
        $this->addOption('decrypt', 'd', InputOption::VALUE_OPTIONAL, $help->decrypt, '');
        $this->addOption('meta', 'm', InputOption::VALUE_NONE, $help->meta);
        $this->addOption('raw', 'r', InputOption::VALUE_NONE, $help->raw);
        $this->addOption('table-type', 't', InputOption::VALUE_OPTIONAL, $help->tabletype, 'vertical');
        $this->addOption('save-to', 's', InputOption::VALUE_OPTIONAL, $help->saveto, '');
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

        $decryptKey = $input->getOption('decrypt');
        $saveToFile = $input->getOption('save-to');
        $tableType = $input->getOption('table-type');

        $dbname = $input->getOption('dbname');
        $datadir = $input->getOption('dir');

        $isSaveTo = (!empty($saveToFile) ? true : false);

        $isRaw = (!empty($input->getOption('raw')) ? true : false);
        $isMeta = (!empty($input->getOption('meta')) ? true : false);

        $isTableHorizontal = (!empty($tableType) && ('horizontal' === $tableType || Func::startWith($tableType, 'h')) ? true : false);

        if (Func::hasWith($key, '*')) {
            $keyr = $this->console->db->keys($key);
            if (!empty($keyr)) {
                $key = $keyr[0];
            }
        }

        $db = $this->console->db;
        $dbChain = $db;

        if (!empty($datadir)) {
            $dbChain = $dbChain->select_dir($datadir);
        }

        if (!empty($dbname)) {
            $dbChain = $dbChain->select($dbname);
        }

        if (!empty($decryptKey)) {
            $dbChain = $dbChain->decrypt($decryptKey);
        }

        if ($isSaveTo) {
            $dbChain = $dbChain->blob();
        }

        if ($isMeta) {
            $dbChain = $dbChain->meta();
        }

        $results = $dbChain->get($key);

        $error = $db->lastError();
        if (!empty($error)) {
            $this->console->outputRaw($error);

            return 1;
        }

        if (empty($results)) {
            $this->console->outputNil();

            return 1;
        }

        if ($isSaveTo) {
            if (Func::isFileWritable($saveToFile)) {
                if (!$this->console->confirm("File '.$saveToFile.' already exists. Continue with this action?", false)) {
                    return 1;
                }
            }

            if (\is_array($results)) {
                $results = Func::exportVar($results);
            }

            if ('/' !== $saveToFile && '.' !== $saveToFile && !is_dir($saveToFile) && file_put_contents($saveToFile, $results)) {
                $this->console->outputRaw($saveToFile);

                return 0;
            }

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

        if (!\is_array($results) && !\is_object($results)) {
            $header = ['Value'];
            if (\is_string($results)) {
                $results = Func::cutStr($results);
            }
            $row[] = [$results];
        } else {
            if (\is_object($results)) {
                $results = Arr::convertObject($results);
            }

            if ($isMeta) {
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

                                    return $arr;
                                }
                            },
                            $k
                        );
                        $k = Func::cutStr(Func::exportVar($k));
                    } elseif (\is_string($k)) {
                        $k = Func::cutStr($k);
                    }

                    $r[$n] = $k;
                }

                $row[] = $r;
            } else {
                if (!Arr::isMulti($results)) {
                    $header = array_keys($results);
                    $row[] = array_values($results);
                } else {
                    $results = Arr::keysEqualize($results, '', $header);

                    foreach ($results as $n => $v) {
                        if (\is_array($v)) {
                            $row2 = array_values($v);
                            foreach ($row2 as $a => $b) {
                                if (\is_array($b)) {
                                    if (!Arr::isNumeric($b)) {
                                        $tn = '';
                                        foreach ($b as $bk => $bv) {
                                            if (\is_array($bv)) {
                                                $bv = Func::cutStr(Func::exportVar($bv));
                                            }
                                            $tn .= '<comment>'.ucwords($bk)."</comment>\n$bv\n\n";
                                        }
                                        $b = trim($tn)."\n";
                                    } else {
                                        $b = Func::cutStr(Func::exportVar($b));
                                    }
                                }
                                $row2[$a] = $b;
                            }
                            $row[] = $row2;
                        }
                    }
                }
            }
        }

        if (empty($row)) {
            $this->console->outputNil();

            return 1;
        }

        $this->console->outputTable($header, $row, $isTableHorizontal);

        return 0;
    }
}
