<?php


namespace SEKafkaLite\Tests;


use RdKafka\Message;
use SEKafkaLite\Queue\LowLevel\Application;

class ConnectorLowLevelTest extends TestCase
{
    public function testLowLevelPush()
    {
        $app = new Application($this->getConfig());
        $queue = $app['kafka.queue'];
        $rpos = [
            'aaa' => 11
        ];
        $payload = [
            'body' => [
                'params' => $rpos,
                'module' => 'DhbApi',
                'controller' => 'AlipayApi',
                'action' => 'alipayPayNotify',
                'noSkey' => 'noSkey',
            ],
        ];
        $queue->push($payload);
    }


    public function testLowLevelPop()
    {
        $app = new Application($this->getConfig());
        $queue = $app['kafka.queue'];
        while(true){
            $message = $queue->pop();
            if(!is_null($message)){
                $this->assertInstanceOf(Message::class, $message);
                break;
            }
        }
    }

    public function testLowLevelJobDelete()
    {
        $app = new Application($this->getConfig());
        $queue = $app['kafka.queue'];
        while(true){
            $message = $queue->pop();
            if(!is_null($message)){
                print_r(json_decode($message->payload, true));
                $result = $queue->delete($message);
            }
            sleep(1);
        }
    }

    public function testLowLevelJobRelease()
    {
        $app = new Application($this->getConfig());
        $queue = $app['kafka.queue'];

        $endTime = time() + 10;
        while(time() < $endTime){
            $message = $queue->pop();
            if(!is_null($message)){
                $payload = json_decode($message->payload, true);
                if(!isset($id)){
                    $id = $queue->release($message);
                    echo 'attempts:' . $payload['attempts'] . PHP_EOL;
                }else if(isset($id) && $payload['id'] != $id){
                    echo 'attempts:' . $payload['attempts'] . PHP_EOL;
                    $this->assertEquals(1, $payload['attempts']);
                    break;
                }
            }
        }
    }

    public function testFastPush()
    {
        $app = new Application($this->getConfig());
        $queue = $app['kafka.queue'];
        $rpos = [
            'aaa' => 11
        ];
        $payload = [
            'body' => [
                'params' => $rpos,
                'module' => 'DhbApi',
                'controller' => 'AlipayApi',
                'action' => 'alipayPayNotify',
                'noSkey' => 'noSkey',
            ],
        ];
        $queue->pushOne($payload);
    }

    public function testPushPoll()
    {
        $app = new Application($this->getConfig());
        $queue = $app['kafka.queue'];
        $rpos = [
            'aaa' => 11
        ];
        $payload = [
            'body' => [
                'params' => $rpos,
                'module' => 'DhbApi',
                'controller' => 'AlipayApi',
                'action' => 'alipayPayNotify',
                'noSkey' => 'noSkey',
            ],
        ];
        $queue->push($payload);
        $queue->poll();
    }

    public function testLowUsefulCommit()
    {
        $config = $this->getConfig();
        $config['common']['enable.auto.offset.store'] = 'false';
        $config['common']['enable.auto.commit'] = 'false';
        $app = new Application($config);

        $queue = $app['kafka.queue'];

        echo 'enable.auto.commit status: '. $app['conf']->dump()['enable.auto.commit'] . PHP_EOL;

        while (true){
            $message = $queue->pop();
            if (!is_null($message)) {
                print_r($message);
                $queue->delete($message);
                break;
            }
        }
    }

    public function testRawLowCommit()
    {
        $conf = new \RdKafka\Conf();
        $config = $this->getConfig();
        $config['common']['enable.auto.offset.store'] = 'false';
        $config['common']['enable.auto.commit'] = 'false';
        if (function_exists('pcntl_sigprocmask')) {
            pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
            $conf->set('internal.termination.signal', SIGIO);
        } else {
            $conf->set('queue.buffering.max.ms', 1);
        }
        $topicConf = new \RdKafka\TopicConf();

        $conf->set('group.id', $config['group_id']);
        $conf->set('metadata.broker.list',$config['brokers']);

        $conf->setDefaultTopicConf($topicConf);

        $consumer = new \RdKafka\Consumer($conf);
        $consumer->addBrokers($config['brokers']);

        $topic = $consumer->newTopic($config['queue'], $topicConf);
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        while(true){
            $msg = $topic->consume(0, 1000);
            if(!is_null($msg)){
                print_r($msg);die;
            }
        }
    }
}