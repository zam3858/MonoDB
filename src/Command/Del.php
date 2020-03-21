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

class Del extends Command {

    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'del';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'key', InputArgument::IS_ARRAY | InputArgument::REQUIRED, $help->key );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $keys = $input->getArgument( 'key' );
        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        $console = $this->console;

        $cnt = 0;
        foreach ( $keys as $n => $key ) {
            if ( Func::has_with( $key, '*' ) ) {
                $key_r = $console->db->keys( $key );
                if ( ! empty( $key_r ) ) {
                    $key = $key_r[0];
                }
            }

            if ( false !== $console->db->delete( $key ) ) {
                $cnt++;
            }
        }

        if ( 0 === $cnt ) {
            $console->output_raw( $output, $console->db->last_error() );
            return 1;
        }

        if ( $is_raw ) {
            $console->output_raw( $output, $cnt );
            return 0;
        }

        $header = [ 'Removed' ];
        $row[] = [ $cnt ];

        $console->output_table( $output, $header, $row );
        return 0;
    }
}
