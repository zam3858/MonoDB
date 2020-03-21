<?php
namespace Monodb;

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase {
    public function testConfig() {
        $dir = realpath( __DIR__ ).'/';
        $db = new Monodb(
            [
                'dir' => $dir
            ]
        );

        $input = $db->info( 'config:dir' );
        $results = $dir;
        $this->assertEquals( $input, $results );
    }

    public function testSet() {
        $dir = realpath( __DIR__ ).'/';
        $db = new Monodb(
            [
                'dir' => $dir
            ]
        );
        $input = $db->set( 'greeting', 'hello world!' );
        $results = 'greeting';
        $this->assertEquals( $input, $results );
    }

    public function testGet() {
        $dir = realpath( __DIR__ ).'/';
        $db = new Monodb(
            [
                'dir' => $dir
            ]
        );
        $input = $db->get( 'greeting' );
        $results = 'hello world!';
        $this->assertEquals( $input, $results );
    }
}
