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

class Set extends Command {
    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'set';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addArgument( 'key', InputArgument::REQUIRED, $help->key );
        $this->addArgument( 'value', InputArgument::REQUIRED, $help->value );
        $this->addArgument( 'expiry', InputArgument::OPTIONAL, $help->expiry );
        $this->addOption( 'raw', 'rw', InputOption::VALUE_NONE, $help->raw );
        $this->addOption( 'no-box', 'nb', InputOption::VALUE_NONE, $help->nobox );
        $this->addOption( 'type', 'tp', InputOption::VALUE_OPTIONAL, $help->type, 'string' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $key = $input->getArgument( 'key' );
        $value = $input->getArgument( 'value' );
        $expiry = $input->getArgument( 'expiry' );

        $type = $input->getOption( 'type' );

        $is_box = ( ! empty( $input->getOption( 'no-box' ) ) ? false : true );
        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        if ( Func::start_with( $value, "'" ) && Func::end_with( $value, "'" ) ) {
            $value = trim( $value, "'" );
        }

        if ( 'array' === $type ) {
            if ( Func::is_var_json( $value ) ) {
                $value = json_decode( $value, true );
            }
        }

        $results = $this->console->db->set( $key, $value, $expiry );

        if ( false === $results ) {
            $this->console->output_raw( $output, $this->console->db->last_error() );
            return 1;
        }

        if ( $is_raw ) {
            $this->console->output_raw( $output, $results );
            return 0;
        }

        $table = new Table( $output );
        $header = [ 'Key', 'Type' ];
        $row[] = [ $results, ucfirst( $type ) ];

        if ( $is_box ) {
            $table->setStyle( 'box' );
        }
        $table->setHeaders( $header );
        $table->setRows( $row );
        $table->render();
        return 0;
    }
}
