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
        $this->addArgument( 'key', InputArgument::OPTIONAL, $help->key );
        $this->addOption( 'meta', '', InputOption::VALUE_NONE, $help->meta );
        $this->addOption( 'raw', '', InputOption::VALUE_NONE, $help->raw );
        $this->addOption( 'no-box', '', InputOption::VALUE_NONE, $help->nobox );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $key = $input->getArgument( 'key' );
        if ( empty( $key ) ) {
            $key = '';
        }

        $is_box = ( ! empty( $input->getOption( 'no-box' ) ) ? false : true );
        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );
        $is_meta = ( ! empty( $input->getOption( 'meta' ) ) ? true : false );
        $results = ( $is_meta ? $this->console->db->meta()->keys( $key ) : $this->console->db->keys( $key ) );

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

        $header = array_map( 'ucwords', $header );
        if ( $is_box ) {
            $table->setStyle( 'box' );
        }
        $table->setHeaders( $header );
        $table->setRows( $row );
        $table->render();
        return 0;
    }
}
