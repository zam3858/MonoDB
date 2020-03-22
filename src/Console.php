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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\Table;
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

class Console {
    public $options = [];
    public $db = null;

    public function __construct( $options = [] ) {
        $this->options = $options;
        $this->db = $this->db( $options );
    }

    public function db( $options = [] ) {
        static $inst = null;
        if ( ! \is_object( $inst ) ) {
            $inst = new Monodb( $options );
        }
        return $inst;
    }

    public function output_nil( $output ) {
        self::output_raw( $output, 'nil' );
    }

    public function output_raw( $output, $data ) {
        $data = ( ! empty( $data ) && \is_array( $data ) ? Func::export_var( $data ) : ( ! empty( $data ) ? $data : 'nil' ) );
        $output->writeln( $data );
    }

    public static function output_table( $output, $header, $row ) {
        $header = array_map( 'ucwords', $header );

        $table = new Table( $output );
        $table->setHeaders( $header );
        $table->setRows( $row );
        $table->render();
    }

    private function get_help_text( $name ) {
        $file = __DIR__.'/Command/help/'.$name.'.txt';
        if ( Func::is_file_readable( $file ) ) {
            $data = file_get_contents( $file );
            $data = trim( $data );
            if ( ! empty( $data ) ) {
                if ( Func::end_with( $data, '</info>' ) ) {
                    return $data;
                }
                return $data.PHP_EOL;
            }
        }
        return '';
    }

    public function info( $name ) {
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

        $data['args'] = [
            'key' => 'Key pattern',
            'value' => 'Value string',
            'expire' => 'Set a timeout on key. The expiry value in seconds',
            'raw' => 'To output raw data',
            'meta' => 'To output meta data',
            'section' => 'Display section info',
            'asarray' => 'Set a value as Array string',
            'encrypt' => 'Encrypt string value',
            'decrypt' => 'Decrypt string value',
            'saveto' => 'Save binary data to file',
            'incrnumber' => 'Increment number',
            'decrnumber' => 'Decrement number'
        ];

        return ( isset( $data[ $name ] ) ? (object) $data[ $name ] : '' );
    }

    public function run() {
        $ascii = "\n";
        $ascii .= "  __  __                   ____  ____  \n";
        $ascii .= " |  \/  | ___  _ __   ___ |  _ \| __ ) \n";
        $ascii .= " | |\/| |/ _ \| '_ \ / _ \| | | |  _ \ \n";
        $ascii .= " | |  | | (_) | | | | (_) | |_| | |_) |\n";
        $ascii .= " |_|  |_|\___/|_| |_|\___/|____/|____/ \n\n";
        $app = new Application( $ascii.'<info>'.$this->db->name().'</info> version <comment>'.$this->db->version().'</comment>' );
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
        $app->run();
    }
}
