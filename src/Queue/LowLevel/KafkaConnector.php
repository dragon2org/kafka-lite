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
        if($config->get('debug')){
            $conf->set('debug', $config->get('debug'));
        }
        if (function_exists('pcntl_sigprocmask')) {
            pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
            $conf->set('internal.termination.signal', SIGIO);
        } else {
            $conf->set('queue.buffering.max.ms', $config->get('queue.buffering.max.ms', 1));
        }

        //other conf
        foreach($config->getCommonSet() as $item => $value){
            $conf->set($item, $value);
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