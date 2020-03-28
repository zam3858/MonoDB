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

class Info extends Command {
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
        $this->addArgument( 'section', InputArgument::OPTIONAL, $help->section, '' );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $this->console->io( $input, $output );

        $section = $input->getArgument( 'section' );
        if ( empty( $section ) ) {
            $section = '';
        }

        $section = strtolower( $section );
        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        $db = $this->console->db;
        $results = $db->info( $section );

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
            if ( Func::has_with( $section, 'config' ) ) {
                $line = '';
                foreach ( $results as $k => $v ) {
                    $line .= $k.'='.$v."\n";
                }
                $results = trim( $line );
            }
            $this->console->output_raw( $results );
            return 0;
        }

        $header = [];
        $row = [];
        $row2 = [];

        if ( ! \is_array( $results ) ) {
            $section = ucfirst( $section );
            $header = [ $section ];
            $row[] = [ $results ];
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
                                    $arr[ $a ] = Func::cutstr( $b );
                                }
                            }
                            return $arr;
                        },
                        $k
                    );
                    if ( ! Arr::is_numeric( $k ) ) {
                        $tn = '';
                        foreach ( $k as $bk => $bv ) {
                            if ( \is_array( $bv ) ) {
                                $bv = Func::export_var( $bv );
                            }
                            $tn .= '<comment>'.ucwords( $bk )."</comment>\n$bv\n\n";
                        }
                        $k = trim( $tn )."\n";
                    } else {
                        $k = Func::export_var( $k );
                    }
                } elseif ( \is_string( $k ) ) {
                    $k = Func::cutstr( $k );
                }

                $r[ $n ] = $k;
            }

            $row[] = $r;
        }

        $this->console->output_table( $header, $row );

        return 0;
    }
}
