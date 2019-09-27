<?php

namespace SEKafkaLite\Queue;

use SEKafkaLite\Kernel\ServiceContainer;

class Application extends ServiceContainer
{
    protected $providers = [
        KafkaQueueServiceProvider::class
    ];
}