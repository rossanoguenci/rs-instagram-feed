<?php
namespace ReactSmith\InstagramFeed\Views;

use ReactSmith\InstagramFeed\Abstracts\AbstractPageView;
use ReactSmith\InstagramFeed\Core;

/**
 * View class for the Onboarding admin page.
 */
class OnboardPageView extends AbstractPageView {

    /**
     * OnboardPageView constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->menu_slug = 'onboard';
    }

    /**
     * Renders the Onboarding page.
     *
     * @return void
     */
    public function render(): void {
        $data = [
            'suggested_key' => Core\SecretManager::generateEncryptionKey()
        ];

        $actions = [
            'refresh_url' => \admin_url('edit.php?post_type=' . Core\Config::POST_TYPE), //todo: make it better
        ];

        include_once "$this->templates_path/admin-onboard.php";
    }
}