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

class Find extends Command {
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
        $this->addArgument( 'value', InputArgument::IS_ARRAY | InputArgument::REQUIRED, $help->value );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
        $this->addOption( 'table-type', 't', InputOption::VALUE_OPTIONAL, $help->tabletype, 'vertical' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $this->console->io( $input, $output );

        $key = $input->getArgument( 'key' );
        $value = $input->getArgument( 'value' );
        $tabletype = $input->getOption( 'table-type' );

        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );
        $is_table_horizontal = ( ! empty( $tabletype ) && ( 'horizontal' === $tabletype || Func::start_with( $tabletype, 'h' ) ) ? true : false );

        $arr = [];
        foreach ( $value as $n => $v ) {
            if ( preg_match( '@([^=]+)=([^=]+)@', $v, $mm ) ) {
                $arr[ $n ] = [ $mm[1], $mm[2] ];
            } else {
                $arr[ $n ] = $v;
            }
        }
        $value = $arr;

        $this->console = $this->console;
        $db = $this->console->db;

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

        $error = $db->last_error();
        if ( ! empty( $error ) ) {
            $this->console->output_raw( $error );
            return 1;
        }

        if ( empty( $results ) ) {
            $this->console->output_nil();
            return 1;
        }

        if ( $is_raw ) {
            $this->console->output_raw( $results );
            return 0;
        }

        $header = [];
        $row = [];
        $row2 = [];

        if ( \is_array( $results ) ) {

            if ( Arr::is_numeric( $results ) ) {
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
                                    if ( ! Arr::is_numeric( $b ) ) {
                                        $tn = '';
                                        foreach ( $b as $bk => $bv ) {
                                            if ( \is_array( $bv ) ) {
                                                if ( ! Arr::is_numeric( $bv ) ) {
                                                    $tmn = '';
                                                    foreach ( $bv as $bvk => $bvv ) {
                                                        $tmn .= '<fg=cyan>'.ucwords( $bvk )."</>\n$bvv\n\n";
                                                    }
                                                    $bv = $tmn;
                                                } else {
                                                    $bv = Func::cutstr( Func::export_var( $bv ) );
                                                }
                                            }
                                            $tn .= '<comment>'.ucwords( $bk )."</comment>\n$bv\n\n";
                                        }
                                        $b = trim( $tn )."\n";
                                    } elseif ( ! Arr::is_multi( $b ) ) {
                                        $b = implode( "\n", $b );
                                        $b = trim( $b );
                                    } else {
                                        $b = Func::cutstr( Func::export_var( $b ) );
                                    }
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
                        if ( ! Arr::is_numeric( $k ) ) {
                            $tn = '';
                            foreach ( $k as $bk => $bv ) {
                                if ( \is_array( $bv ) ) {
                                    if ( ! Arr::is_numeric( $bv ) ) {
                                        $tmn = '';
                                        foreach ( $bv as $bvk => $bvv ) {
                                            $tmn .= '<fg=cyan>'.ucwords( $bvk )."</>\n$bvv\n\n";
                                        }
                                        $bv = $tmn;
                                    } else {
                                        $bv = Func::cutstr( Func::export_var( $bv ) );
                                    }
                                }
                                $tn .= '<comment>'.ucwords( $bk )."</comment>\n$bv\n\n";
                            }
                            $k = trim( $tn )."\n";
                        } elseif ( ! Arr::is_multi( $k ) ) {
                            $k = implode( "\n", $k );
                            $k = trim( $k );
                        } else {
                            $k = Func::cutstr( Func::export_var( $k ) );
                        }
                    } elseif ( \is_string( $k ) ) {
                        $k = Func::cutstr( $k );
                    }

                    $r[ $n ] = $k;
                }

                $row[] = $r;
            }
        } else {
            $header[] = 'Match';
            $row[] = [ $results ];
        }

        $this->console->output_table( $header, $row, $is_table_horizontal );
        return 0;
    }
}
