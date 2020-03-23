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

class Keys extends Command {

    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'keys';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'key', InputArgument::OPTIONAL, $help->key, '' );
        $this->addOption( 'meta', 'm', InputOption::VALUE_NONE, $help->meta );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $key = $input->getArgument( 'key' );
        if ( empty( $key ) ) {
            $key = '';
        }

        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );
        $is_meta = ( ! empty( $input->getOption( 'meta' ) ) ? true : false );

        $console = $this->console;
        $results = ( $is_meta ? $console->db->meta()->keys( $key ) : $console->db->keys( $key ) );

        $error = $console->db->last_error();
        if ( !empty($error) ) {
            $console->output_raw( $output, $error );
            return 1;
        }

        if ( empty($results) ) {
            $console->output_nil( $output );
            return 1;
        }

        if ( $is_raw ) {
            if ( $is_meta ) {
                $this->console->output_raw( $output, array_values( $results ) );
            } else {
                foreach ( array_values( $results ) as $k ) {
                    $this->console->output_raw( $output, $k );
                }
            }
            return 0;
        }

        $header = [];
        $row = [];

        if ( $is_meta ) {
            foreach ( $results as $k => $arr ) {
                if ( empty( $header ) ) {
                    $header = array_keys( $arr );
                }
                $row[] = array_values( $arr );
            }
        } else {
            $header = [ '#', 'Keys' ];
            foreach ( $results as $a => $b ) {
                $a++;
                $row[] = [ $a, $b ];
            }
        }

        $this->console->output_table( $output, $header, $row );
        return 0;
    }
}
