<?php
namespace ReactSmith\InstagramFeed\Tests;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;

class BaseTestCase extends TestCase {

    use MockeryPHPUnitIntegration;

    protected function tearDown(): void{
        Monkey\tearDown();
        parent::tearDown();
    }
}
