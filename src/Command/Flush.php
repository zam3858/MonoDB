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
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Flush extends Command {

    private $console;
    public function __construct( $console ) {
        $this->console = $console;
        parent::__construct();
    }

    protected function configure() {
        $name = 'flush';
        $info = $this->console->info( $name );
        $this->setName( $name )->setDescription( $info->desc )->setHelp( $info->help );

        $help = $this->console->info( 'args' );
        $this->addOption( 'raw', 'r', InputOption::VALUE_NONE, $help->raw );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $is_raw = ( ! empty( $input->getOption( 'raw' ) ) ? true : false );

        $helper = $this->getHelper( 'question' );
        $question = new ConfirmationQuestion( 'This action will delete all available keys. Continue with this action? (Y/N): ', false );

        if ( ! $helper->ask( $input, $output, $question ) ) {
            $this->console->output_nil( $output );
            return 1;
        }

        $results = $this->console->db->flush();

        if ( false === $results ) {
            $this->console->output_raw( $output, $this->console->db->last_error() );
            return 1;
        }

        if ( $is_raw ) {
            $this->console->output_raw( $output, $results );
            return 0;
        }

        $header = [ 'Count' ];
        $row[] = [ $results ];

        $this->console->output_table( $output, $header, $row );

        return 0;
    }
}
