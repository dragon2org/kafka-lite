<?php

namespace SEKafkaLite\Queue;

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
        $topicConf->set('auto.offset.reset', 'largest');
        $conf = $this->container['conf'];
        $conf->set('log_level', $config->get('log_level'));
        $conf->set('group.id', $config->get('group_id'));
        $conf->set('metadata.broker.list', $config->get('brokers'));
        $conf->set('enable.auto.commit', 'false');
        if($config->get('debug')){
            $conf->set('debug', $config->get('debug'));
        }
        $conf->set('socket.timeout.ms', 50); // or socket.blocking.max.ms, depending on librdkafka version
        if (function_exists('pcntl_sigprocmask')) {
            pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
            $conf->set('internal.termination.signal', SIGIO);
        } else {
            $conf->set('queue.buffering.max.ms', $config->get('queue.buffering.max.ms', 1));
        }
        $conf->setDefaultTopicConf($topicConf);

        $consumer = $this->container->raw('consumer');
        $consumer = call_user_func($consumer, $this->container, $conf);

        return new SEKafkaQueue(
            $producer,
            $consumer,
            $config
        );
    }
}