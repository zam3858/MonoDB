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
use Monodb\Arrays as Arr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Get extends Command {
    private $console;

    public function __construct( $parent ) {
        $this->console = $parent;
        parent::__construct();
    }

    protected function configure() {
        $name = basename( str_replace( '\\', '/', strtolower( __CLASS__ ) ) );
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'key', InputArgument::REQUIRED, $help->key );
        $this->addOption( 'decrypt', 'd', InputOption::VALUE_OPTIONAL, $help->decrypt, '' );
        $this->addOption( 'meta', 'm', InputOption::VALUE_NONE, $help->meta );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
        $this->addOption( 'table-type', 't', InputOption::VALUE_OPTIONAL, $help->tabletype, 'vertical' );
        $this->addOption( 'save-to', 's', InputOption::VALUE_OPTIONAL, $help->saveto, '' );
    }


    protected function execute( InputInterface $input, OutputInterface $output ) {
        $this->console->io( $input, $output );

        $key = $input->getArgument( 'key' );

        $decrypt_key = $input->getOption( 'decrypt' );
        $saveto = $input->getOption( 'save-to' );
        $tabletype = $input->getOption( 'table-type' );

        $is_decrypt = ( ! empty( $decrypt_key ) ? true : false );
        $is_saveto = ( ! empty( $saveto ) ? true : false );

        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );
        $is_meta = ( ! empty( $input->getOption( 'meta' ) ) ? true : false );

        $is_table_horizontal = ( ! empty( $tabletype ) && ( 'horizontal' === $tabletype || Func::start_with( $tabletype, 'h' ) ) ? true : false );

        if ( Func::has_with( $key, '*' ) ) {
            $key_r = $this->console->db->keys( $key );
            if ( ! empty( $key_r ) ) {
                $key = $key_r[0];
            }
        }

        $db = $this->console->db;
        $chain_db = $db;
        if ( $is_decrypt ) {
            $chain_db = $chain_db->decrypt( $decrypt_key );
        }

        if ( $is_saveto ) {
            $chain_db = $chain_db->blob();
        }

        if ( $is_meta ) {
            $chain_db = $chain_db->meta();
        }

        $results = $chain_db->get( $key );

        $error = $db->last_error();
        if ( ! empty( $error ) ) {
            $this->console->output_raw( $error );
            return 1;
        }

        if ( empty( $results ) ) {
            $this->console->output_nil();
            return 1;
        }

        if ( $is_saveto ) {
            if ( Func::is_file_writable( $saveto ) ) {

                if ( ! $this->console->confirm( "File '.$saveto.' already exists. Continue with this action?", false ) ) {
                    return 1;
                }
            }

            if ( \is_array( $results ) ) {
                $results = Func::export_var( $results );
            }

            if ( '/' !== $saveto && '.' !== $saveto && ! is_dir( $saveto ) && file_put_contents( $saveto, $results ) ) {
                $this->console->output_raw( $saveto );
                return 0;
            }

            $this->console->output_nil();
            return 1;
        }

        $results = Arr::convert_object( $results );

        if ( $is_raw ) {
            $this->console->output_raw( $results );
            return 0;
        }

        $header = [];
        $row = [];
        $row2 = [];

        if ( ! \is_array( $results ) && ! \is_object( $results ) ) {
            $header = [ 'Value' ];
            if ( \is_string( $results ) ) {
                $results = Func::cutstr( $results );
            }
            $row[] = [ $results ];

        } else {

            if ( \is_object( $results ) ) {
                $results = Arr::convert_object( $results );
            }

            if ( $is_meta ) {
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
                                        $arr[ $a ] = Func::cutstr( $b );
                                    }
                                    return $arr;
                                }
                            },
                            $k
                        );
                        $k = Func::cutstr( Func::export_var( $k ) );
                    } elseif ( \is_string( $k ) ) {
                        $k = Func::cutstr( $k );
                    }

                    $r[ $n ] = $k;
                }

                $row[] = $r;

            } else {

                if ( ! Arr::is_multi( $results ) ) {
                    $header = array_keys( $results );
                    $row[] = array_values( $results );

                } else {
                    $h = [];
                    foreach ( $results as $n => $v ) {
                        $h = array_merge( $h, array_keys( $v ) );
                    }
                    $h = array_unique( $h );

                    $dosort = false;
                    foreach ( $results as $n => $v ) {
                        if ( \is_array( $v ) ) {
                            foreach ( $h as $a ) {
                                if ( ! isset( $v[ $a ] ) ) {
                                    $results[ $n ][ $a ] = '';
                                    $dosort = true;
                                }
                            }
                        }
                    }

                    if ( $dosort ) {
                        foreach ( $results as $n => $v ) {
                            ksort( $results[ $n ] );
                        }
                    }

                    foreach ( $results as $n => $v ) {
                        if ( \is_array( $v ) ) {
                            $row2 = array_values( $v );
                            foreach ( $row2 as $a => $b ) {
                                if ( \is_array( $b ) ) {
                                    if ( ! Arr::is_numeric( $b ) ) {
                                        $tn = '';
                                        foreach ( $b as $bk => $bv ) {
                                            if ( \is_array( $bv ) ) {
                                                $bv = Func::cutstr( Func::export_var( $bv ) );
                                            }
                                            $tn .= '<comment>'.ucwords( $bk )."</comment>\n$bv\n\n";
                                        }
                                        $b = trim( $tn )."\n";
                                    } else {
                                        $b = Func::cutstr( Func::export_var( $b ) );
                                    }
                                }
                                $row2[ $a ] = $b;
                            }
                            $row[] = $row2;
                        }
                    }

                    $header = $h;
                }
            }
        }

        if ( empty( $row ) ) {
            $this->console->output_nil();
            return 1;
        }

        $this->console->output_table( $header, $row, $is_table_horizontal );
        return 0;
    }
}
