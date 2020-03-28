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

class Incr extends Command {
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
        $this->addArgument( 'number', InputArgument::OPTIONAL, $help->incrnumber, '' );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $this->console->io( $input, $output );

        $key = $input->getArgument( 'key' );
        $number = $input->getArgument( 'number' );

        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        $db = $this->console->db;
        $results = $db->incr( $key, $number );

        $error = $db->last_error();
        if ( ! empty( $error ) ) {
            $this->console->output_raw( $error );
            return 1;
        }

        if ( $is_raw ) {
            $this->console->output_raw( $results );
            return 0;
        }

        $header = [ 'Increment' ];
        $row[] = [ $results ];

        $this->console->output_table( $header, $row );
        return 0;
    }
}
