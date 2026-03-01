<?php
namespace ReactSmith\InstagramFeed\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ReactSmith\InstagramFeed\Core\Config;

class ConfigTest extends BaseTestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    public function test_it_gets_an_option() {
        Functions\expect('get_option')
            ->once()
            ->with(Config::OPT_APP_ID, null)
            ->andReturn('12345');

        $this->assertSame('12345', Config::get(Config::OPT_APP_ID));
    }

    public function test_it_sets_an_option() {
        Functions\expect('update_option')
            ->once()
            ->with(Config::OPT_APP_ID, 'new-value')
            ->andReturn(true);

        $this->assertTrue(Config::set(Config::OPT_APP_ID, 'new-value'));
    }
    
    public function test_it_deletes_an_option() {
        Functions\expect('delete_option')
            ->once()
            ->with(Config::OPT_APP_ID)
            ->andReturn(true);

        $this->assertTrue(Config::delete(Config::OPT_APP_ID));
    }
    
    public function test_it_clears_all_options_and_returns_status() {
        foreach (Config::OPT_LIST as $opt) {
            Functions\expect('delete_option')
                ->once()
                ->with($opt)
                ->andReturn(true);
        }

        $result = Config::clear_all(true);

        foreach (Config::OPT_LIST as $opt) {
            $this->assertArrayHasKey($opt, $result);
            $this->assertSame('deleted', $result[$opt]);
        }
    }
    
    public function test_it_gets_app_id() {
        Functions\expect('get_option')
            ->once()
            ->with(Config::OPT_APP_ID, null)
            ->andReturn('abc');

        $this->assertSame('abc', Config::get_app_id());
    }


    public function test_it_gets_app_secret_as_boolean_when_not_decrypted() {
        Functions\expect('get_option')
            ->once()
            ->with(Config::OPT_APP_SECRET, null)
            ->andReturn('secret-value');

        $this->assertTrue(Config::get_app_secret(false));
    }

    
    public function test_it_gets_app_secret_as_false_when_not_set_and_not_decrypted() {
        Functions\expect('get_option')
            ->once()
            ->with(Config::OPT_APP_SECRET, null)
            ->andReturn(null);

        $this->assertFalse(Config::get_app_secret(false));
    }

    
    public function test_it_gets_app_secret_decrypted() {
        Functions\expect('get_option')
            ->once()
            ->with(Config::OPT_APP_SECRET, null)
            ->andReturn('decrypted-secret');

        $this->assertSame('decrypted-secret', Config::get_app_secret(true));
    }

    
    public function test_it_returns_page_title() {
        Functions\expect('__')
            ->once()
            ->with('Instagram Feed', 'reactsmith')
            ->andReturn('Instagram Feed');

        $this->assertSame('Instagram Feed', Config::get_page_title());
    }
    
    public function test_it_returns_token_expiration_list() {
        $now = time();

        Functions\expect('get_option')
            ->once()
            ->with(Config::OPT_APP_TOKEN_EXPIRY, null)
            ->andReturn((string)($now + 100));

        Functions\expect('get_option')
            ->once()
            ->with(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY, null)
            ->andReturn('0');

        $result = Config::tokenExpirationList();

        $this->assertArrayHasKey(Config::OPT_APP_TOKEN_EXPIRY, $result);
        $this->assertArrayHasKey(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY, $result);

        $this->assertSame((string)($now + 100), $result[Config::OPT_APP_TOKEN_EXPIRY]['token_expiry_seconds']);
        $this->assertFalse($result[Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY]['is_token_expired']);
    }

}
