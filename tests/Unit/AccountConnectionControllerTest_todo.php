<?php

namespace ReactSmith\InstagramFeed\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ReactSmith\InstagramFeed\Controllers\AccountConnectionController;
use ReactSmith\InstagramFeed\Services\MetaOAuthService;
use ReactSmith\InstagramFeed\Core\Config;

class AccountConnectionControllerTest extends BaseTestCase {

    protected AccountConnectionController $controller;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // WP functions
        Functions\stubEscapeFunctions();
        Functions\when('admin_url')->justReturn('/admin');
        Functions\when('add_query_arg')->justReturn('/admin?page=test');
        Functions\when('wp_safe_redirect')->justReturn(true);
        Functions\when('wp_create_nonce')->justReturn('nonce');

        // Mock static Config class
        \Mockery::mock('alias:ReactSmith\InstagramFeed\Core\Config')
            ->shouldReceive('get_app_id')
            ->andReturn(null);

        \Mockery::mock('alias:ReactSmith\InstagramFeed\Core\Config')
            ->shouldReceive('get_app_secret')
            ->andReturn(null);

        \Mockery::mock('alias:ReactSmith\InstagramFeed\Core\Config')
            ->shouldReceive('get')
            ->andReturn(null);

        // Instantiate controller
//         $this->controller = new AccountConnectionController();
    }

    //todo: add tests
    public function test(){}

}
