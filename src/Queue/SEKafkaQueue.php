<?php

namespace SEKafkaLite\Queue;

use SEKafkaLite\Kernel\Config;

class SEKafkaQueue
{
    protected $producer;

    protected $consumer;

    protected $config;

    protected $defaultQueue;

    public function __construct(\RdKafka\Producer $producer, \RdKafka\KafkaConsumer $consumer, Config $config)
    {
        $this->producer = $producer;
        $this->consumer = $consumer;
        $this->config = $config;

        $this->defaultQueue = $config->get('queue');
    }

    /**
     * @param string $queue
     *
     * @return string
     */
    private function getQueueName($queue)
    {
        return $queue ?: $this->defaultQueue;
    }

    /**
     * Return a Kafka Topic based on the name
     *
     * @param $queue
     *
     * @return \RdKafka\ProducerTopic
     */
    private function getTopic($queue)
    {
        return $this->producer->newTopic($this->getQueueName($queue));
    }

    public function push(array $payload)
    {
        try {
            $topic = $this->getTopic($this->getQueueName());

            $pushRawCorrelationId = $this->getCorrelationId();

            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $pushRawCorrelationId);
            echo 'pushRawCorrelationId' . $pushRawCorrelationId . PHP_EOL;
            return $pushRawCorrelationId;
        } catch (ErrorException $exception) {
            $this->reportConnectionError('pushRaw', $exception);
        }
    }
}