<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SEKafkaLite\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * class TestCase.
 */
class TestCase extends BaseTestCase
{
    public function getConfig()
    {
        $config = [
            'log_level' => LOG_DEBUG,
            'debug' => 'consumer',
            'group_id' => 'CID_alikafka_mini_program_publish_common_test',
            'brokers' => 'docker.for.mac.localhost:9092',
            'queue' => 'alikafka_mini_program_publish_test1_4',
        ];

        return $config;
    }

    /**
     * Tear down the test case.
     */
    public function tearDown()
    {
        $this->finish();
        parent::tearDown();
    }

    /**
     * Run extra tear down code.
     */
    protected function finish()
    {
        // call more tear down methods
    }
}
