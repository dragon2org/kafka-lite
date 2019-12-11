<?php


namespace SEKafkaLite\Kernel\Providers;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RdKafka\Consumer;
use RdKafka\KafkaConsumer;

class KafkaConsumerLowLevelServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['consumer'] = function ($app, $conf) {
            return new Consumer($conf);
        };
    }
}