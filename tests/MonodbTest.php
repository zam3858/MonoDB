<?php

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    private $config;

    private function db()
    {
        $this->config = [
            'dbname' => 'phpunit',
        ];
        $db = new Monodb\Monodb($this->config);

        return $db;
    }

    public function testConfig()
    {
        $input = $this->db()->info('config:dbname');
        $results = $this->config['dbname'];
        static::assertSame($input, $results);
    }

    public function testSet()
    {
        $input = $this->db()->set('greeting', 'hello world!');
        $results = 'greeting';
        static::assertSame($input, $results);
    }

    public function testGet()
    {
        $input = $this->db()->get('greeting');
        $results = 'hello world!';
        static::assertSame($input, $results);
    }

    public function testFind()
    {
        $input = $this->db()->find('greeting', 'hello world!');
        $results = 'hello world!';
        static::assertSame($input, $results);
    }

    public function testIncr1()
    {
        $input = $this->db()->incr('incr');
        $results = 1;
        static::assertSame($input, $results);
    }

    public function testIncr2()
    {
        $input = $this->db()->incr('incr', 10);
        $results = 11;
        static::assertSame($input, $results);
    }

    public function testDecr1()
    {
        $input = $this->db()->decr('incr');
        $results = 10;
        static::assertSame($input, $results);
    }

    public function testDecr2()
    {
        $input = $this->db()->decr('incr', 10);
        $results = 0;
        static::assertSame($input, $results);
    }

    public function testKeys()
    {
        $input = $this->db()->keys();
        $results[0] = 'greeting';
        $results[1] = 'incr';
        static::assertSame($input, $results);
    }

    public function testDelete()
    {
        $key = 'key1';
        $this->db()->set($key, 1);
        $input = $this->db()->delete($key);
        $results = $key;
        static::assertSame($input, $results);
    }

    public function testMdelete()
    {
        $keys = [
            'key1',
            'key2',
            'key3',
        ];

        foreach ($keys as $key) {
            $this->db()->set($key, 1);
        }
        $input = $this->db()->mdelete($keys[0], $keys[1], $keys[2]);
        $results = $keys;
        static::assertSame($input, $results);
    }

    public function testSetExpire()
    {
        $timeout = 1;
        $this->db()->set('key', 1, $timeout);
        sleep(2);
        $input = false;
        $results = true;
        $this->db()->get('key', $debug);
        if (!empty($debug) && is_array($debug) && 'expired' === $debug['status']) {
            $results = false;
        }
        static::assertFalse($results);
    }

    public function testFlushDb()
    {
        $input = $this->db()->flushDb();
        $results = $input;
        static::assertSame($input, $results);
    }
}
