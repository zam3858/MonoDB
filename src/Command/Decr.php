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

class Decr extends Command {

    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'decr';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'key', InputArgument::REQUIRED, $help->key );
        $this->addArgument( 'number', InputArgument::OPTIONAL, $help->decrnumber, '' );
        $this->addOption( 'meta', 'm', InputOption::VALUE_NONE, $help->meta );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $key = $input->getArgument( 'key' );
        $number = $input->getArgument( 'number' );

        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );
        $is_meta = ( ! empty( $input->getOption( 'meta' ) ) ? true : false );

        $console = $this->console;
        $results = ( $is_meta ? $console->db->meta()->decr( $key, $number ) : $console->db->decr( $key, $number ) );

        if ( false === $results ) {
            $console->output_raw( $output, $console->db->last_error() );
            return 1;
        }

        if ( $is_raw ) {
            $console->output_raw( $output, $results );
            return 0;
        }

        $header = [ 'Decrement' ];
        $row[] = [ $results ];

        $console->output_table( $output, $header, $row );
        return 0;
    }
}
