<?php
namespace MonoDB;

use PHPUnit\Framework\TestCase;

class MonoDBTest extends TestCase {
    public function testConfig() {
        $data_path = dirname( realpath( __FILE__ ) ).'/';
        $db = new MonoDB([
            'path' => $data_path
        ]);

        $response = $db->info()['data_path'];
        $f = $data_path;
        $this->assertEquals($f, $response);
    }

    public function testSet() {
        $data_path = dirname( realpath( __FILE__ ) ).'/';
        $db = new MonoDB([
            'path' => $data_path
        ]);
        $response = $db->set( 'greeting', 'hello world!' );
        $f = 'greeting';
        $this->assertEquals($f, $response);
    }

    public function testGet() {
        $data_path = dirname( realpath( __FILE__ ) ).'/';
        $db = new MonoDB([
            'path' => $data_path
        ]);
        $response = $db->get( 'greeting' );
        $f = 'hello world!';
        $this->assertEquals($f, $response);
    }
}
