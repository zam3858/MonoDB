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
        $this->addArgument( 'section', InputArgument::OPTIONAL, $help->section, '' );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $section = $input->getArgument( 'section' );
        if ( empty( $section ) ) {
            $section = '';
        }

        $section = strtolower( $section );
        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        $results = $this->console->db->info( $section );

        if ( false === $results ) {
            $this->console->output_raw( $output, $this->console->db->last_error() );
            return 1;
        }

        if ( $is_raw ) {
            $this->console->output_raw( $output, $results );
            return 0;
        }

        $header = [];
        $row = [];

        if ( ! \is_array( $results ) ) {
            $section = ucfirst( $section );
            $header = [ $section ];
            $row[] = [ $results ];
        } else {
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
                    $k = Func::export_var( $k );
                } elseif ( \is_string( $k ) ) {
                    $k = Func::cutstr( $k, 50 );
                }

                $r[ $n ] = $k;
            }

            $row[] = $r;
        }

        $this->console->output_table( $output, $header, $row );

        return 0;
    }
}
