<?php

namespace SEKafkaLite\Queue\LowLevel;

use Pimple\Container;

class KafkaConnector
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * KafkaConnector constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function connect()
    {
        $config = $this->container['config'];
        $producer = $this->container['producer'];
        $producer->addBrokers($config->get('brokers'));

        $topicConf = $this->container['topic_conf'];
        $topicConf->set('request.required.acks', 0);

        $conf = $this->container['conf'];
        $conf->set('log_level', $config->get('log_level'));
        $conf->set('group.id', $config->get('group_id'));
        $conf->set('metadata.broker.list', $config->get('brokers'));
        if($config->get('enable.idempotence')){
            $conf->set('enable.idempotence', 'true');
        }
        if($config->get('debug')){
            $conf->set('debug', $config->get('debug'));
        }
        if (function_exists('pcntl_sigprocmask')) {
            pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
            $conf->set('internal.termination.signal', SIGIO);
        } else {
            $conf->set('queue.buffering.max.ms', $config->get('queue.buffering.max.ms', 1));
        }

        if($config->get('dr_msg_')){
            $conf->setDrMsgCb(function ($kafka, $message) use($config){
                if($config->get('dr_msg_log_path')){
                    file_put_contents($config->get('dr_msg_log_path') . 'kafka_dr_log-'. date('Y-m-d') .'.log'  , var_export($message, true) . PHP_EOL, FILE_APPEND);
                }
            });
        }
        if($config->get('error_log_path')){
            $conf->setErrorCb(
                function ($kafka, $err, $reason) use($config){
                    file_put_contents($config->get('error_log_path') . 'kafka_error_log-'. date('Y-m-d') .'.log', sprintf("Kafka error: %s (reason: %s)", rd_kafka_err2str($err), $reason) . PHP_EOL, FILE_APPEND);
                }
            );
        }

        $conf->setDefaultTopicConf($topicConf);

        $consumer = $this->container->raw('consumer');
        $consumer = call_user_func($consumer, $this->container, $conf);
        $consumer->addBrokers($config->get('brokers'));
        $topic = $consumer->newTopic($config->get('queue'), $topicConf);
        $topic->consumeStart($config->get('partition'), RD_KAFKA_OFFSET_STORED);

        return new SEKafkaQueue(
            $producer,
            $topic,
            $config
        );
    }
}