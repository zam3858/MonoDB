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
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Get extends Command {

    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'get';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'key', InputArgument::REQUIRED, $help->key );
        $this->addOption( 'decrypt', 'd', InputOption::VALUE_OPTIONAL, $help->decrypt, '' );
        $this->addOption( 'meta', 'm', InputOption::VALUE_NONE, $help->meta );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
        $this->addOption( 'save-to', 's', InputOption::VALUE_OPTIONAL, $help->saveto, '' );
    }


    protected function execute( InputInterface $input, OutputInterface $output ) {
        $key = $input->getArgument( 'key' );

        $decrypt_key = $input->getOption( 'decrypt' );
        $saveto = $input->getOption( 'save-to' );

        $is_decrypt = ( ! empty( $decrypt_key ) ? true : false );
        $is_saveto = ( ! empty( $saveto ) ? true : false );

        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );
        $is_meta = ( ! empty( $input->getOption( 'meta' ) ) ? true : false );

        if ( Func::has_with( $key, '*' ) ) {
            $key_r = $this->console->db->keys( $key );
            if ( ! empty( $key_r ) ) {
                $key = $key_r[0];
            }
        }

        $db = $this->console->db;
        if ( $is_decrypt ) {
            $db = $db->decrypt( $decrypt_key );
        }

        if ( $is_saveto ) {
            $db = $db->blob();
        }

        if ( $is_meta ) {
            $db = $db->meta();
        }

        $results = $db->get( $key );

        if ( false === $results ) {
            $this->console->output_raw( $output, $this->console->db->last_error() );
            return 1;
        }

        if ( $is_saveto && ! Func::ctype_print( $results ) ) {
            if ( Func::is_file_writable( $saveto ) ) {
                $helper = $this->getHelper( 'question' );
                $question = new ConfirmationQuestion( "File '.$saveto.' already exists. Continue with this action? (Y/N): ", false );

                if ( ! $helper->ask( $input, $output, $question ) ) {
                    $this->console->output_nil( $output );
                    return 1;
                }
            }

            if ( '/' !== $saveto && '.' !== $saveto && ! is_dir( $saveto ) && file_put_contents( $saveto, $results ) ) {
                $this->console->output_raw( $output, $saveto );
                return 0;
            }

            $this->console->output_nil( $output );
            return 1;
        }

        $results = Func::object_to_array( $results );

        if ( $is_raw ) {
            $this->console->output_raw( $output, $results );
            return 0;
        }

        $header = [];
        $row = [];
        $row2 = [];

        if ( ! \is_array( $results ) && ! \is_object( $results ) ) {
            $header = [ 'Key', 'Value' ];
            $size = Func::get_size( $results );
            if ( \is_string( $results ) ) {
                $results = Func::cutstr( $results, 50 );
            }
            $row[] = [ $key, $results ];
        } else {
            if ( \is_object( $results ) ) {
                $results = Func::object_to_array( $results );
            }

            if ( $is_meta ) {
                $header = array_keys( $results );
                $row2 = array_values( $results );

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
            } else {

                if ( \count( $results ) === 1 ) {
                    $results = each( $results );
                    $header[] = ( 0 === $results[0] ? 'Value' : $results[0] );
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
            }

            $header = array_map( 'ucwords', $header );
        }

        $this->console->output_table( $output, $header, $row );
        return 0;
    }
}
