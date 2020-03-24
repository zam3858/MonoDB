<?php
/*
 * This file is part of the MonoDB package.
 *
 * (c) Nawawi Jamili <nawawi@rutweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Monodb\Command;

use Monodb\Monodb;
use Monodb\Functions as Func;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Set extends Command {

    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'set';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'key', InputArgument::REQUIRED, $help->key );
        $this->addArgument( 'value', InputArgument::IS_ARRAY | InputArgument::REQUIRED, $help->value );
        $this->addOption( 'as-array', 'a', InputOption::VALUE_NONE, $help->asarray );
        $this->addOption( 'encrypt', 'c', InputOption::VALUE_OPTIONAL, $help->encrypt );
        $this->addOption( 'expire', 'e', InputOption::VALUE_OPTIONAL, $help->expire, 0 );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $key = $input->getArgument( 'key' );
        $value = $input->getArgument( 'value' );

        $encrypt_key = $input->getOption( 'encrypt' );
        $expire = $input->getOption( 'expire' );

        $is_encrypt = ( ! empty( $encrypt_key ) ? true : false );
        $is_asarray = ( ! empty( $input->getOption( 'as-array' ) ) ? true : false );
        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        set_error_handler( function() {}, E_WARNING | E_NOTICE );

        if ( $is_asarray ) {
            $arr = [];
            $x = 0;
            foreach ( $value as $n => $v ) {
                if ( preg_match( '@([^=]+)=([^=]+)@', $v, $mm ) ) {
                    if ( isset($arr[$x][ $mm[1] ]) ) {
                        $x++;
                    }
                    $arr[$x][ $mm[1] ] = $mm[2];
                } else {
                    $arr[ $n ] = $v;
                }
            }
            $value = $arr;

        } else {

            $text = ' '.implode( ' ', $value );
            $value = trim( $text );
        }

        $console = $this->console;
        $db = $console->db;

        if ( $is_encrypt ) {
            $db = $db->encrypt( $encrypt_key );
        }

        $results = $db->set( $key, $value, $expire );

        if ( false === $results ) {
            $console->output_raw( $output, $console->db->last_error() );
            return 1;
        }

        if ( $is_raw ) {
            $console->output_raw( $output, $results );
            return 0;
        }

        $header = [ 'Key' ];
        $row[] = [ $results ];

        $console->output_table( $output, $header, $row );
        return 0;
    }
}
