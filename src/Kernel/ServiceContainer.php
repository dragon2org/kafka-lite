<?php

namespace SEKafkaLite\Kernel;

use Pimple\Container;
use SEKafkaLite\Kernel\Providers\ConfigServiceProvider;
use SEKafkaLite\Kernel\Providers\KafkaConfServiceProvider;
use SEKafkaLite\Kernel\Providers\KafkaConsumerServiceProvider;
use SEKafkaLite\Kernel\Providers\KafkaProducerServiceProvider;
use SEKafkaLite\Kernel\Providers\KafkaTopicConfServiceProvider;

class ServiceContainer extends Container
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $defaultConfig = [];

    /**
     * @var array
     */
    protected $userConfig = [];

    public function __construct(array $config = [], array $prepends = [], string $id = null)
    {
        $this->registerProviders($this->getProviders());

        parent::__construct($prepends);

        $this->userConfig = $config;

        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id ? $this->id : $this->id = md5(json_encode($this->userConfig));
    }

    public function getConfig()
    {
        $base = [
        ];

        return array_replace_recursive($base, $this->defaultConfig, $this->userConfig);
    }


    /**
     * Return all providers.
     *
     * @return array
     */
    public function getProviders()
    {
        return array_merge([
            ConfigServiceProvider::class,
            KafkaConfServiceProvider::class,
            KafkaTopicConfServiceProvider::class,
            KafkaProducerServiceProvider::class,
            KafkaConsumerServiceProvider::class,
        ], $this->providers);
    }

    /**
     * @param string $id
     * @param mixed  $value
     */
    public function rebind($id, $value)
    {
        $this->offsetUnset($id);
        $this->offsetSet($id, $value);
    }

    /**
     * Magic get access.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function __get($id)
    {
//        if ($this->shouldDelegate($id)) {
//            return $this->delegateTo($id);
//        }

        return $this->offsetGet($id);
    }

    /**
     * Magic set access.
     *
     * @param string $id
     * @param mixed  $value
     */
    public function __set($id, $value)
    {
        $this->offsetSet($id, $value);
    }

    /**
     * @param array $providers
     */
    public function registerProviders(array $providers)
    {
        foreach ($providers as $provider) {
            parent::register(new $provider());
        }
    }
}