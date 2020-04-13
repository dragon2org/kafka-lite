<?php

namespace SEKafkaLite\Kernel;

class Config
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function __toArray()
    {
    }

    public function get($key, $default = null)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        if(isset($this->config['common'][$key])){
            return $this->config['common'][$key];
        }

        return $default;
    }

    public function getCommonSet()
    {
        return isset($this->config['common']) ? $this->config['common'] : [];
    }
}