<?php
namespace ReactSmith\InstagramFeed\Controllers;

use ReactSmith\InstagramFeed\Core\Config;
use ReactSmith\Core\Debug;

/**
 * Controller for managing and displaying admin notifications.
 */
class NotificationController {

    /**
     * Initializes the notification controller by adding the display action.
     *
     * @return void
     */
    public static function init(): void{
        \add_action('admin_notices', [self::class, 'display']);
    }

    /**
     * Adds a message to the persistent queue.
     *
     * @param string $message The message to display.
     * @param string $type The type of notification ('error', 'success', 'warning', 'info').
     * @param string $id Optional unique ID for the message.
     * @return void
     */
    public static function add(string $message, string $type = 'info', string $id = ''): void {
        $messages = Config::get(Config::OPT_MESSAGES, false, []);

        if(is_string($messages)) $messages = [];

        $date = date("Y-m-d H:i:s");

        $messages[] = [
            'id'      => $id ?: uniqid('msg_'),
            'message' => $message,
            'type'    => $type, // 'error', 'success', 'warning', 'info'
            'date'    => $date,
        ];
        Config::set(Config::OPT_MESSAGES, $messages);
    }

    /**
     * Renders queued messages and clears the queue.
     *
     * @return void
     */
    public static function display(): void {
        $messages = Config::get(Config::OPT_MESSAGES, false, []);

        if (empty($messages)) return;

        foreach ($messages as $msg) {
            \wp_admin_notice($msg['message'],[
                'type' => $msg['type'],
                'dismissable' => true,
                'id' => $msg['id'],
            ]);
        }

        // Clear queue after display so they don't reappear on refresh
        Config::delete(Config::OPT_MESSAGES);
    }
}