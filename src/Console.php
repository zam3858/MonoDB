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
use Monodb\Command\Set;
use Monodb\Command\Get;
use Monodb\Command\Keys;
use Monodb\Command\Info;

class Console {
    public $options = [];
    public $db = null;
    public function __construct( $options = [] ) {
        $this->options = $options;
        $this->db = $this->db( $options );
    }

    public function db( $options = [] ) {
        static $inst = null;
        if ( ! is_object( $inst ) ) {
            $inst = new Monodb( $options );
        }
        return $inst;
    }

    public function output_raw( $output, $data ) {
        $data = ( ! empty( $data ) && is_array( $data ) ? Func::export_var( $data ) : ( ! empty( $data ) ? $data : 'null' ) );
        $output->writeln( $data );
    }

    public function info( $name ) {
        $data = [];

        $data['keys'] = [
            'desc' => 'Displays all keys matching pattern',
            'help' => ''
        ];

        $data['set'] = [
            'desc' => 'Set key to hold the string value',
            'help' => "Set key to hold the string value.\nIf key already holds a value, it is overwritten, regardless of its type.\n"
        ];

        $data['get'] = [
            'desc' => 'Get the value of key.',
            'help' => 'Get the value of key. If the key does not exist empty is returned.'
        ];

        $data['info'] = [
            'desc' => 'Displays information about this App',
            'help' => 'For Config info, the section can combine config:key, eg; info config:dbname'
        ];

        $data['args'] = [
            'key' => 'Key string',
            'value' => 'Value string',
            'expiry' => 'Expiry in seconds',
            'raw' => 'Display raw data',
            'meta' => 'Display meta data',
            'nobox' => 'Disable box display',
            'section' => 'Display section info',
            'type' => 'Set data type. (string, integer, array, json)',
            'savebinary' => 'Save binary data to file'
        ];

        return ( isset( $data[ $name ] ) ? (object) $data[ $name ] : '' );
    }

    public function run() {
        $app = new Application( $this->db->name(), $this->db->version() );
        $app->setCatchExceptions( true );
        $app->add( new Set( $this ) );
        $app->add( new Get( $this ) );
        $app->add( new Keys( $this ) );
        $app->add( new Info( $this ) );
        $app->run();
    }
}
