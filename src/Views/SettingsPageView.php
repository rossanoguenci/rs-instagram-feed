<?php
namespace ReactSmith\InstagramFeed\Views;

use ReactSmith\InstagramFeed\Abstracts\AbstractPageView;
use ReactSmith\InstagramFeed\Core\Config;
use ReactSmith\InstagramFeed\Core\CronManager;
use ReactSmith\InstagramFeed\Controllers\NotificationController;

/**
 * View class for the Settings admin page.
 */
class SettingsPageView extends AbstractPageView {

    /**
     * SettingsPageView constructor.
     */
    public function __construct(){
        parent::__construct();
        $this->menu_slug = 'settings';
    }

    /**
     * Renders the Settings page.
     *
     * @return void
     */
    public function render(): void {
        $data = $this->get_view_data();

        $actions = [
            'manual_sync_url' => $this->generate_action_url('manual_sync'),
            'pause_sync_url' => $this->generate_action_url('pause_sync'),
            'start_sync_url' => $this->generate_action_url('start_sync'),
            'drop_feed_url' => $this->generate_action_url('drop_feed'),
        ];

        include_once "$this->templates_path/admin-settings.php";
    }

    /**
     * Handles saving the plugin options.
     *
     * @return void
     */
    public function handle_save_options(): void{

        $value = isset($_POST[Config::OPT_MAX_POSTS_NUMBER]) ? intval(sanitize_text_field($_POST[Config::OPT_MAX_POSTS_NUMBER])) : null;

        if(empty($value)){
            NotificationController::add('Options were empty.', 'warning');
        }else{
            Config::set(Config::OPT_MAX_POSTS_NUMBER,$value);
            NotificationController::add('Options updated successfully.', 'success');
        }

        $this->redirect_back();
    }

    /**
     * Retrieves data required for rendering the view.
     *
     * @return array The view data.
     */
    public function get_view_data(): array {
        $data = [
            'last_sync' => CronManager::lastSync(),
            'next_sync' => CronManager::nextSync(),
            'is_scheduled' => CronManager::isScheduled(),
            'max_posts' => intval(Config::get(Config::OPT_MAX_POSTS_NUMBER)),
        ];

        return array_merge($this->get_common_view_data(),$data);
    }


}