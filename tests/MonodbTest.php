<?php
namespace Monodb;

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase {
    public function testConfig() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->info( 'config:dir' );
        $results = $config['dir'];
        $this->assertEquals( $input, $results );
    }

    public function testSet() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->set( 'greeting', 'hello world!' );
        $results = 'greeting';
        $this->assertEquals( $input, $results );
    }

    public function testGet() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->get( 'greeting' );
        $results = 'hello world!';
        $this->assertEquals( $input, $results );
    }

    public function testFind() {
           $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->find('greeting', 'hello world!');
        $results = 'hello world!';
        $this->assertEquals( $input, $results );
    }

    public function testIncr1() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->incr('incr');
        $results = 1;
        $this->assertEquals( $input, $results );
    }

    public function testIncr2() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->incr('incr', 10);
        $results = 11;
        $this->assertEquals( $input, $results );
    }

    public function testDecr1() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->decr('incr');
        $results = 10;
        $this->assertEquals( $input, $results );
    }

    public function testDecr2() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->decr('incr', 10);
        $results = 0;
        $this->assertEquals( $input, $results );
    }

    public function testKeys() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->keys();
        $results[0] = 'greeting';
        $results[1] = 'incr';
        $this->assertEquals( $input, $results );
    }

    /*public function testDelete() {
        $dir = realpath( __DIR__ ).'/';
        $db = new Monodb(
            [
                'dir' => $dir,
                'dbname' => 'phpunit'
            ]
        );

        $keys[0] = 'greeting';
        $keys[1] = 'incr';

        $input = $db->delete();

    }*/

    public function testFlush() {
        $config = include(__DIR__.'/config.php');
        $db = new Monodb($config);

        $input = $db->flush();
        $results = $input;
        $this->assertEquals( $input, $results );
    }
}
