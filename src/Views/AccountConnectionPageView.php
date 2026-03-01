<?php
namespace ReactSmith\InstagramFeed\Views;

use ReactSmith\InstagramFeed\Abstracts\AbstractPageView;
use ReactSmith\InstagramFeed\Core\Config;
use const ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT;

/**
 * View class for the Account Connection admin page.
 */
class AccountConnectionPageView extends AbstractPageView{

    /**
     * AccountConnectionPageView constructor.
     */
    public function __construct(){
        parent::__construct();
        $this->menu_slug = 'account-connection';
    }

    /**
     * Retrieves data required for rendering the view.
     *
     * @return array The view data.
     */
    public function get_view_data(): array {
        $data = [
//             'token_details' => Config::tokenExpirationList(), //debugging
            'is_editing_credentials' => isset($_GET['edit_credentials']),
        ];

        return array_merge($this->get_common_view_data(), $data);
    }

    /**
     * Renders the Account Connection page.
     *
     * @return void
     */
    public function render(): void {
            $data = $this->get_view_data();

            $actions = [
                'this_admin_page_url' => $this->this_admin_page_url(),
                'edit_credentials_url' => $this->this_admin_page_url() . '&edit_credentials',
                'clear_connection_url' => $this->generate_action_url('clear_connection'),
                'revoke_access_url' => $this->generate_action_url('revoke_access'),
                'meta_login_url' => $this->meta_service ? $this->meta_service->loginURL() : '',
            ];

            include_once "$this->templates_path/admin-account-connection.php";
    }



}