<?php
/*
 * This file is part of the MonoDB package.
 *
 * (c) Nawawi Jamili <nawawi@rutweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monodb;

use Monodb\Console\AppendCommand;
use Monodb\Console\DecrCommand;
use Monodb\Console\DelCommand;
use Monodb\Console\ExistsCommand;
use Monodb\Console\ExpireCommand;
use Monodb\Console\FindCommand;
use Monodb\Console\FlushdbCommand;
use Monodb\Console\GetCommand;
use Monodb\Console\IncrCommand;
use Monodb\Console\InfoCommand;
use Monodb\Console\KeysCommand;
use Monodb\Console\SetCommand;
use Monodb\Functions as Func;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\Table;

/**
 * Class Console.
 */
class Console
{
    /**
     * @var array
     */
    public $options = [];

    /**
     * @var null
     */
    public $db = null;

    /**
     * @var null
     */
    private $input = null;

    /**
     * @var null
     */
    private $output = null;

    /**
     * @param array $options
     *
     * @return void
     */
    public function __construct($options = [])
    {
        $this->options = $options;
        $this->db = $this->db($options);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
    }

    /**
     * @param mixed $input
     * @param mixed $output
     *
     * @return void
     */
    public function io($input, $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param array $options
     */
    public function db($options = []): object
    {
        static $inst = null;
        if (!\is_object($inst)) {
            $inst = new Monodb($options);
        }

        return $inst;
    }

    public function confirm(string $text, bool $answer = false): object
    {
        $io = new SymfonyStyle($this->input, $this->output);

        return $io->confirm($text, $answer);
    }

    /**
     * @return void
     */
    public function outputNil()
    {
        $this->outputRaw('nil');
    }

    /**
     * @return void
     */
    public function outputFalse()
    {
        $this->outputRaw('false');
    }

    /**
     * @param mixed $data
     *
     * @return void
     */
    public function outputRaw($data)
    {
        $data = (!empty($data) && \is_array($data) ? Func::exportVar($data) : (!empty($data) || 0 === (int) $data ? $data : 'nil'));
        $this->output->writeln($data);
    }

    /**
     * @return void
     */
    public function outputTable(array $header, array $row, bool $horizontal = false)
    {
        $header = array_map('strtoupper', $header);

        $table = new Table($this->output);
        $table->setHeaders($header);
        $table->setRows($row);

        if ($horizontal) {
            $table->setHorizontal();
        }

        $table->render();
    }

    private function getHelpText(string $name, bool $asis = false): string
    {
        $file = __DIR__.'/Console/help/'.$name.'.txt';
        $data = '';
        if (Func::isFileReadable($file)) {
            $data = file_get_contents($file);
            if (!$asis) {
                $data = trim($data);
                if (!empty($data)) {
                    if (!empty($_SERVER['argv']) && \in_array('--format=md', $_SERVER['argv'], true)) {
                        $data = str_replace('Return value', '### Return value', $data);
                        $data = str_replace('Examples', '### Examples', $data);
                        $data = str_replace('Supported wildcard patterns', '### Supported wildcard patterns', $data);
                        $data = str_replace('Use \'--', '- Use \'--', $data);
                        $data = str_replace('<info>', '```', $data);
                        $data = str_replace('</info>', '```', $data);
                    }
                    if (Func::endWith($data, '</info>')) {
                        return $data;
                    }

                    return $data."\n";
                }
            }
        }

        return $data;
    }

    /**
     * @return mixed
     */
    public function getHelp(string $name)
    {
        $data = [];

        $data['keys'] = [
            'desc' => 'Displays all keys matching pattern',
            'help' => $this->getHelpText($name),
        ];

        $data['set'] = [
            'desc' => 'Set the string value of key',
            'help' => $this->getHelpText($name),
        ];

        $data['get'] = [
            'desc' => 'Get the value of key',
            'help' => $this->getHelpText($name),
        ];

        $data['incr'] = [
            'desc' => 'Increment the integer value of a key by one',
            'help' => $this->getHelpText($name),
        ];

        $data['decr'] = [
            'desc' => 'Decrement the integer value of a key by one',
            'help' => $this->getHelpText($name),
        ];

        $data['del'] = [
            'desc' => 'Delete a key',
            'help' => $this->getHelpText($name),
        ];

        $data['flushdb'] = [
            'desc' => 'Remove all keys from the current database',
            'help' => $this->getHelpText($name),
        ];

        $data['info'] = [
            'desc' => 'Displays this application info',
            'help' => $this->getHelpText($name),
        ];

        $data['exists'] = [
            'desc' => 'Determine if a key exists',
            'help' => $this->getHelpText($name),
        ];

        $data['find'] = [
            'desc' => 'Searches the value for a given key',
            'help' => $this->getHelpText($name),
        ];

        $data['expire'] = [
            'desc' => 'Set a key\'s time to live in seconds',
            'help' => $this->getHelpText($name),
        ];

        $data['append'] = [
            'desc' => 'Append a value to key',
            'help' => $this->getHelpText($name),
        ];

        $data['args'] = [
            'key' => 'Key pattern',
            'value' => 'Value string',
            'expire' => 'Set a key\'s time to live in seconds',
            'timeout' => 'The timeout value in seconds',
            'raw' => 'To output raw data',
            'meta' => 'To output meta data',
            'section' => 'Display section info',
            'asarray' => 'Set a value as Array string',
            'encrypt' => 'Encrypt string value',
            'decrypt' => 'Decrypt string value',
            'saveto' => 'Output data to file',
            'incrnumber' => 'Increment number',
            'decrnumber' => 'Decrement number',
            'tabletype' => 'Display as table type',
            'dbname' => 'Database name',
            'dir' => 'Data directory',
        ];

        return isset($data[$name]) ? (object) $data[$name] : '';
    }

    /**
     * @return void
     */
    public function run()
    {
        $banner = $this->getHelpText('banner', true);
        $app = new Application($banner.'<info>'.$this->db->name().'</info> version <comment>'.$this->db->version().'</comment>');
        $app->setCatchExceptions(true);
        $app->add(new SetCommand($this));
        $app->add(new GetCommand($this));
        $app->add(new KeysCommand($this));
        $app->add(new FindCommand($this));
        $app->add(new IncrCommand($this));
        $app->add(new DecrCommand($this));
        $app->add(new DelCommand($this));
        $app->add(new FlushdbCommand($this));
        $app->add(new InfoCommand($this));
        $app->add(new ExistsCommand($this));
        $app->add(new ExpireCommand($this));
        $app->add(new AppendCommand($this));
        $app->run();
    }
}
