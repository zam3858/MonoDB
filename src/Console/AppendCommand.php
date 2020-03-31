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
 * Class AppendCommand.
 */
class AppendCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'append';

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
        $this->addOption('as-array', 'a', InputOption::VALUE_NONE, $help->asarray);
        $this->addOption('raw', 'r', InputOption::VALUE_NONE, $help->raw);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->console->io($input, $output);

        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $isAsArray = (!empty($input->getOption('as-array')) ? true : false);
        $isRaw = (!empty($input->getOption('raw')) ? true : false);

        if ($isAsArray) {
            $arr = [];
            $x = 0;
            foreach ($value as $n => $v) {
                if (preg_match('@([^=]+)=([^=]+)@', $v, $mm)) {
                    if (isset($arr[$x][$mm[1]])) {
                        ++$x;
                    }
                    $arr[$x][$mm[1]] = $mm[2];
                } else {
                    $arr[$x][$v] = $v;
                }
            }

            rsort($arr);
            $value = $arr;
        } else {
            $text = ' '.implode(' ', $value);
            $value = trim($text);
        }

        $db = $this->console->db;

        $results = $db->append($key, $value);

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

        $header = ['Key'];
        $row[] = [$results];

        $this->console->outputTable($header, $row);

        return 0;
    }
}
