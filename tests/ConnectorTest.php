<?php


namespace SEKafkaLite\Tests;


use RdKafka\Message;
use SEKafkaLite\Queue\Application;

class ConnectorTest extends TestCase
{
    public function testPush()
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


    public function testPop()
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

    public function testJobDelete()
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

    public function testJobRelease()
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
}