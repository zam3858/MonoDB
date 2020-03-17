<?php
namespace MonoDB;

use PHPUnit\Framework\TestCase;

class MonoDBTest extends TestCase {
    public function testConfig() {
        $data_path = dirname( realpath( __FILE__ ) ).'/';
        $db = new MonoDB(
            [
                'path' => $data_path
            ]
        );

        $input = $db->info()['data_path'];
        $results = $data_path;
        $this->assertEquals( $input, $results );
    }

    public function testSet() {
        $data_path = dirname( realpath( __FILE__ ) ).'/';
        $db = new MonoDB(
            [
                'path' => $data_path
            ]
        );
        $input = $db->set( 'greeting', 'hello world!' );
        $results = 'greeting';
        $this->assertEquals( $input, $results );
    }

    public function testGet() {
        $data_path = dirname( realpath( __FILE__ ) ).'/';
        $db = new MonoDB(
            [
                'path' => $data_path
            ]
        );
        $input = $db->get( 'greeting' );
        $results = 'hello world!';
        $this->assertEquals( $input, $results );
    }
}
