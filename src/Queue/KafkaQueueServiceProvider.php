<?php

namespace SEKafkaLite\Queue;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class KafkaQueueServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['kafka.queue'] = function ($app) {
            $connector = new KafkaConnector($app);
            return $connector->connect();
        };
    }
}