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

class Find extends Command {

    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'find';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'key', InputArgument::REQUIRED, $help->key );
        $this->addArgument( 'value', InputArgument::IS_ARRAY | InputArgument::REQUIRED, $help->value );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $key = $input->getArgument( 'key' );
        $value = $input->getArgument( 'value' );

        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        $arr = [];
        foreach ( $value as $n => $v ) {
            if ( preg_match( '@([^=]+)=([^=]+)@', $v, $mm ) ) {
                $arr[ $n ] = [ $mm[1], $mm[2] ];
            } else {
                $arr[ $n ] = $v;
            }
        }
        $value = $arr;

        $console = $this->console;
        $db = $console->db;

        if ( \is_array( $value ) ) {
            $aa = [];
            foreach ( $value as $num => $args ) {
                $results = $db->find( $key, $args );
                if ( false !== $results ) {
                    $aa[] = $results;
                }
            }

            $results = $aa;
        }

        if ( \count( $results ) === 1 ) {
            $results = $results[0];
        }

        $error = $console->db->last_error();
        if ( ! empty( $error ) ) {
            $console->output_raw( $output, $console->db->last_error() );
            return 1;
        }

        if ( empty( $results ) ) {
            $console->output_nil( $output );
            return 1;
        }

        if ( $is_raw ) {
            $console->output_raw( $output, $results );
            return 0;
        }

        $header = [];
        $row = [];
        $row2 = [];

        if ( \is_array( $results ) ) {

            if ( ! empty( $results[0] ) ) {
                if ( \count( $results ) === 1 ) {
                    $results = each( $results );
                    $header[] = ( 0 === $results[0] ? 'Match' : $results[0] );
                    $row[] = [ $results[1] ];
                } else {
                    foreach ( $results as $k => $arr ) {
                        if ( \is_array( $arr ) ) {
                            if ( empty( $header ) ) {
                                $header = array_keys( $arr );
                            }
                            $row2 = array_values( $arr );
                            foreach ( $row2 as $a => $b ) {
                                if ( \is_array( $b ) ) {
                                    $b = Func::export_var( $b );
                                }
                                $row2[ $a ] = $b;
                            }
                            $row[] = $row2;
                        } else {
                            if ( empty( $header ) ) {
                                $header = array_keys( $results );
                            }
                            if ( empty( $row2 ) ) {
                                $row2 = array_values( $results );
                                $row[] = $row2;
                            }
                        }
                    }
                }
            } else {
                $header = array_keys( $results );
                $row2 = array_values( $results );
                $r = [];
                foreach ( $row2 as $n => $k ) {
                    if ( \is_array( $k ) ) {
                        $k = array_map(
                            function ( $arr ) {
                                if ( ! \is_array( $arr ) ) {
                                    return $arr;
                                }
                                foreach ( $arr as $a => $b ) {
                                    if ( \is_string( $b ) ) {
                                        $arr[ $a ] = Func::cutstr( $b, 50 );
                                    }
                                    return $arr;
                                }
                            },
                            $k
                        );
                        $k = Func::cutstr( Func::export_var( $k ), 50 );
                    } elseif ( \is_string( $k ) ) {
                        $k = Func::cutstr( $k, 50 );
                    }

                    $r[ $n ] = $k;
                }

                $row[] = $r;
            }
        } else {
            $header[] = 'Match';
            $row[] = [ $results ];
        }

        $this->console->output_table( $output, $header, $row );
        return 0;
    }
}
