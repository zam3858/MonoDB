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

use Monodb\Monodb;
use Monodb\Functions as Func;
use Monodb\Command\Set;
use Monodb\Command\Get;
use Monodb\Command\Keys;
use Monodb\Command\Incr;
use Monodb\Command\Decr;
use Monodb\Command\Del;
use Monodb\Command\Flush;
use Monodb\Command\Info;
use Monodb\Command\Exists;
use Monodb\Command\Find;
use Monodb\Command\Expire;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\Table;

class Console {
    public $options = [];
    public $db = null;
    private $input = null;
    private $output = null;

    public function __construct( $options = [] ) {
        $this->options = $options;
        $this->db = $this->db( $options );
    }

    public function __destruct() {
        return true;
    }

    public function io( $input, $output ) {
        $this->input = $input;
        $this->output = $output;
    }

    public function db( $options = [] ) {
        static $inst = null;
        if ( ! \is_object( $inst ) ) {
            $inst = new Monodb( $options );
        }
        return $inst;
    }

    public function confirm( string $text, bool $answer = false ) {
        $io = new SymfonyStyle( $this->input, $this->output );
        return $io->confirm( $text, $answer );
    }

    public function output_nil() {
        $this->output_raw( 'nil' );
    }

    public function output_raw( $data ) {
        $data = ( ! empty( $data ) && \is_array( $data ) ? Func::export_var( $data ) : ( ! empty( $data ) || 0 === (int) $data ? $data : 'nil' ) );
        $this->output->writeln( $data );
    }

    public function output_table( array $header, array $row, bool $horizontal = false ) {
        $header = array_map( 'strtoupper', $header );

        $table = new Table( $this->output );
        $table->setHeaders( $header );
        $table->setRows( $row );

        if ( $horizontal ) {
            $table->setHorizontal();
        }

        $table->render();
    }

    private function get_help_text( string $name, bool $asis = false ) {
        $file = __DIR__.'/Command/help/'.$name.'.txt';
        $data = '';
        if ( Func::is_file_readable( $file ) ) {
            $data = file_get_contents( $file );
            if ( ! $asis ) {
                $data = trim( $data );
                if ( ! empty( $data ) ) {
                    if ( ! empty( $_SERVER['argv'] ) && \in_array( '--format=md', $_SERVER['argv'], true ) ) {
                        $data = str_replace( 'Return value', '### Return value', $data );
                        $data = str_replace( 'Examples', '### Examples', $data );
                        $data = str_replace( 'Supported wildcard patterns', '### Supported wildcard patterns', $data );
                        $data = str_replace( 'Use \'--', '- Use \'--', $data );
                    }
                    if ( Func::end_with( $data, '</info>' ) ) {
                        return $data;
                    }
                    return $data.PHP_EOL;
                }
            }
        }
        return $data;
    }

    public function info( string $name ) {
        $data = [];

        $data['keys'] = [
            'desc' => 'Displays all keys matching pattern',
            'help' => $this->get_help_text( $name )
        ];

        $data['set'] = [
            'desc' => 'Set key to hold the string value',
            'help' => $this->get_help_text( $name )
        ];

        $data['get'] = [
            'desc' => 'Get the value of key',
            'help' => $this->get_help_text( $name )
        ];

        $data['incr'] = [
            'desc' => 'Increments the number stored at key by increment',
            'help' => $this->get_help_text( $name )
        ];

        $data['decr'] = [
            'desc' => 'Decrements the number stored at key by decrement',
            'help' => $this->get_help_text( $name )
        ];

        $data['del'] = [
            'desc' => 'Delete the specified keys',
            'help' => $this->get_help_text( $name )
        ];

        $data['flush'] = [
            'desc' => 'Delete all available keys',
            'help' => $this->get_help_text( $name )
        ];

        $data['info'] = [
            'desc' => 'Displays this application info',
            'help' => $this->get_help_text( $name )
        ];

        $data['exists'] = [
            'desc' => 'Check if the key exists',
            'help' => $this->get_help_text( $name )
        ];

        $data['find'] = [
            'desc' => 'Find the value of key',
            'help' => $this->get_help_text( $name )
        ];

        $data['expire'] = [
            'desc' => 'Set or reset a timeout on existing key',
            'help' => $this->get_help_text( $name )
        ];

        $data['args'] = [
            'key' => 'Key pattern',
            'value' => 'Value string',
            'expire' => 'Set a timeout on key. The expiry value in seconds',
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
            'tabletype' => 'Display as table type'
        ];

        return ( isset( $data[ $name ] ) ? (object) $data[ $name ] : '' );
    }

    public function run() {
        $banner = $this->get_help_text( 'banner', true );
        $app = new Application( $banner.'<info>'.$this->db->name().'</info> version <comment>'.$this->db->version().'</comment>' );
        $app->setCatchExceptions( true );
        $app->add( new Set( $this ) );
        $app->add( new Get( $this ) );
        $app->add( new Keys( $this ) );
        $app->add( new Find( $this ) );
        $app->add( new Incr( $this ) );
        $app->add( new Decr( $this ) );
        $app->add( new Del( $this ) );
        $app->add( new Flush( $this ) );
        $app->add( new Info( $this ) );
        $app->add( new Exists( $this ) );
        $app->add( new Expire( $this ) );
        $app->run();
    }
}
