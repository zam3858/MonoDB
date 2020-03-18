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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Info extends Command {
    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'info';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'section', InputArgument::OPTIONAL, $help->section );
        $this->addOption( 'raw', 'rw', InputOption::VALUE_NONE, $help->raw );
        $this->addOption( 'no-box', 'nb', InputOption::VALUE_NONE, $help->nobox );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $section = $input->getArgument( 'section' );
        if ( empty( $section ) ) {
            $section = '';
        }

        $results = $this->console->db->info( $section );

        $is_box = ( ! empty( $input->getOption( 'no-box' ) ) ? false : true );
        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        if ( false === $results ) {
            $this->console->output_raw( $output, $this->console->db->last_error() );
            return 1;
        }

        if ( $is_raw ) {
            $this->console->output_raw( $output, $results );
            return 0;
        }

        $table = new Table( $output );
        $header = [];
        $row = [];

        if ( ! is_array( $results ) ) {
            $section = ucfirst( $section );
            $header = [ $section ];
            $row[] = [ $results ];

        } else {

            $header = array_keys( $results );

            $row2 = array_values( $results );
            foreach ( $row2 as $n => $k ) {
                if ( is_array( $k ) ) {
                    $k = array_map(
                        function( $arr ) {
                            if ( ! is_array( $arr ) ) {
                                return $arr;
                            }
                            foreach ( $arr as $a => $b ) {
                                if ( is_string( $b ) && strlen( $b ) > 50 ) {
                                    $b_r = substr( $b, 0, 50 );
                                    if ( $b_r !== $b ) {
                                        $b = $b_r.'...';
                                    }
                                    $arr[ $a ] = $b;
                                }
                                return $arr;
                            }
                        },
                        $k
                    );
                    $k = Func::export_var( $k );
                } elseif ( is_string( $k ) && strlen( $k ) > 50 ) {
                    $k_r = substr( $k, 0, 50 );
                    if ( $k_r !== $k ) {
                        $k = $k_r.'...';
                    }
                }

                $r[ $n ] = $k;
            }

            $row[] = $r;

            $header = array_map( 'ucwords', $header );
            if ( $is_box ) {
                $table->setStyle( 'box' );
            }
        }

        $table->setHeaders( $header );
        $table->setRows( $row );
        $table->render();

        return 0;
    }
}
