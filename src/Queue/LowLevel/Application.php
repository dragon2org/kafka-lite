<?php

namespace SEKafkaLite\Queue\LowLevel;

use SEKafkaLite\Kernel\Providers\KafkaConsumerLowLevelServiceProvider;
use SEKafkaLite\Kernel\ServiceContainer;

class Application extends ServiceContainer
{
    protected $providers = [
        KafkaQueueServiceProvider::class,
        KafkaConsumerLowLevelServiceProvider::class,
        KafkaQueueServiceProvider::class,
    ];
}