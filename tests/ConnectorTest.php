<?php


namespace SEKafkaLite\Tests;


use SEKafkaLite\Queue\Application;

class ConnectorTest extends TestCase
{
    public function testNew()
    {
        $app = new Application($this->getConfig());
        $queue = $app['kafka.queue'];
        dd($queue);
    }
}