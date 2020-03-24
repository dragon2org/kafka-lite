<?php

namespace SEKafkaLite\Queue;

use ErrorException;
use RdKafka\Message;
use SEKafkaLite\Kernel\Config;
use SEKafkaLite\Kernel\Exceptions\InvalidPayloadException;
use SEKafkaLite\Queue\Exceptions\QueueKafkaException;

class SEKafkaQueue
{
    protected $producer;

    protected $consumer;

    protected $config;

    protected $defaultQueue;

    protected $subscribedQueueNames = [];

    protected $correlationId;

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
    private function getQueueName()
    {
        return $this->defaultQueue;
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
        return $this->producer->newTopic($this->getQueueName());
    }

    public function push(array $data)
    {
        return $this->pushRaw($this->getPayload($data));
    }

    public function pushOne(array $data)
    {
        try {
            $payload = $this->getPayload($data);

            $topic = $this->getTopic($this->getQueueName());

            $pushRawCorrelationId = $this->getCorrelationId();

            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $pushRawCorrelationId);

            $this->poll(1);

            return $pushRawCorrelationId;
        } catch (ErrorException $exception) {
            $this->reportConnectionError('pushRaw', $exception);
        }
    }

    protected function getPayload(array $data)
    {
        $payload = [
            'data' => $data,
            'id' => $this->setCorrelationId(),
            'maxTries' => 3,
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error code: '.json_last_error()
            );
        }
        return $payload;
    }

    public function pushRaw($payload)
    {
        try {
            $topic = $this->getTopic($this->getQueueName());

            $pushRawCorrelationId = $this->getCorrelationId();

            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $pushRawCorrelationId);
            return $pushRawCorrelationId;
        } catch (ErrorException $exception) {
            $this->reportConnectionError('pushRaw', $exception);
        }
    }

    /**
     * Retrieves the correlation id, or a unique id.
     *
     * @return string
     */
    public function setCorrelationId()
    {
        return $this->correlationId = uniqid('', true);
    }

    /**
     * Retrieves the correlation id, or a unique id.
     *
     * @return string
     */
    public function getCorrelationId()
    {
        return $this->correlationId ? : uniqid('', true);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     *
     * @throws QueueKafkaException
     *
     * @return \Illuminate\Queue\Jobs\Job|null
     */
    public function pop()
    {
        try {
            $queue = $this->getQueueName();
            if (!in_array($queue, $this->subscribedQueueNames)) {
                $this->subscribedQueueNames[] = $queue;

                $this->consumer->subscribe($this->subscribedQueueNames);
            }

            $message = $this->consumer->consume(1000);
            if ($message === null) {
                return null;
            }

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    return $message;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;
                default:
                    throw new QueueKafkaException($message->errstr(), $message->err);
            }
        } catch (\RdKafka\Exception $exception) {
            throw new QueueKafkaException('Could not pop from the queue', 0, $exception);
        }
    }

    public function delete(\RdKafka\Message $message)
    {
        try {
            $this->consumer->commitAsync($message);
        } catch (\RdKafka\Exception $exception) {
            throw new QueueKafkaException('Could not delete job from the queue', 0, $exception);
        }
    }

    public function release(\RdKafka\Message $message)
    {
       $this->delete($message);
       $payload = json_decode($message->payload, true);
       $payload['attempts']+=1;
       $payload['updated_at'] = date('Y-m-d H:i:s');
       $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
       if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error code: '.json_last_error()
            );
       }
       return $this->pushRaw($payload);
    }

    public function poll($limit = 1)
    {
        $limit = $limit <= 1 ? 1 : $limit;
        while ($this->producer->getOutQLen() >= $limit) {
            $this->producer->poll(1);
        }
    }
}