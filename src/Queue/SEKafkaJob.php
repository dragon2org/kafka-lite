<?php


namespace SEKafkaLite\Queue;


class SEKafkaJob
{
    protected  $connector;

    public function __construct($connector)
    {
        $this->connector = $connector;
    }

    public function fire()
    {

    }
}