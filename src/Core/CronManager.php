<?php
namespace ReactSmith\InstagramFeed\Core;

use ReactSmith\InstagramFeed\Controllers;
use ReactSmith\InstagramFeed\Core\Config;
use ReactSmith\Core\Debug;

/**
 * Manager for handling cron jobs and synchronization schedules.
 */
class CronManager {

    /**
     * Formats a timestamp into a RFC2822 date string.
     *
     * @param int|null $timestamp The timestamp to format.
     * @return string The formatted date.
     */
    private static function date_format(?int $timestamp): string{
        $format = '';
        return date(DATE_RFC2822, $timestamp);
    }

    /**
     * Checks if the Meta connection is established.
     *
     * @return bool True if connected, false otherwise.
     */
    private static function has_connection(): bool {
        return (new Controllers\FeedController())->hasConnection();
    }

    /**
     * Initializes the cron manager by adding actions for sync and cleaning.
     *
     * @return void
     */
    public static function init(): void{
        if (!Config::get(Config::OPT_CRON_AUTO_SYNC) && !self::has_connection() && self::isScheduled()) {
            self::stopSync();
            return;
        }

        \add_action(Config::OPT_CRON_HOOK, [new Controllers\FeedController(), 'updateFeed']);
        \add_action(Config::OPT_CLEAN_CRON_HOOK, [new Controllers\FeedController(), 'cleanOldPosts']);
    }

    /**
     * Initializes default configuration options for cron and sync.
     *
     * @return void
     */
    public static function initScheduleCronOptions(): void {
        if(Config::get(Config::OPT_CRON_AUTO_SYNC) === null) {
            self::startSync();
        }

        if(Config::get(Config::OPT_MAX_POSTS_NUMBER) === null) {
            Config::set(Config::OPT_MAX_POSTS_NUMBER, 6);
        }
    }

    /**
     * Gets the formatted date of the last successful synchronization.
     *
     * @return string The last sync date or 'Never'.
     */
    public static function lastSync(): string{
        $last_sync = Config::get(Config::OPT_CRON_LAST_SYNC);
        return empty($last_sync)? __('Never','reactsmith') : self::date_format($last_sync);
    }


    /**
     * Gets the formatted date of the next scheduled synchronization.
     *
     * @return string The next sync date or 'Never'.
     */
    public static function nextSync(): string{
        $next_sync = \wp_next_scheduled(Config::OPT_CRON_HOOK);
        return $next_sync ? self::date_format($next_sync) : __('Never','reactsmith');
    }

    /**
     * Checks if a synchronization job is currently scheduled.
     *
     * @return bool True if scheduled, false otherwise.
     */
    public static function isScheduled(): bool{
        return \wp_next_scheduled(Config::OPT_CRON_HOOK) ? true : false;
    }

    /**
     * Schedules a single synchronization event to run almost immediately.
     *
     * @return void
     */
    public static function scheduleSingleEvent(){
        //Guard: check if the next event is imminent within 5 minutes
        $next_sync = \wp_next_scheduled(Config::OPT_CRON_HOOK);
        $threshold = 5 * MINUTE_IN_SECONDS;

        if ($next_sync && ($next_sync - time()) < $threshold) {
            $message = 'Manual sync skipped: A scheduled sync is starting in less than ' . \human_time_diff(time(), $next_sync) . '.';
            Controllers\NotificationController::add($message, 'info');
            return;
        }

        $in_seconds = 10;

        $flag = \wp_schedule_single_event(time()+$in_seconds, Config::OPT_CRON_HOOK);
        $message = $flag ? "Manual sync will start in $in_seconds seconds." : 'Error: Manual sync was not set.';
        $type = $flag ? 'success' : 'error';

        Controllers\NotificationController::add($message, $type);
    }

    /**
     * Starts the automatic synchronization job (hourly).
     *
     * @return void
     */
    public static function startSync(): void {
        if (\wp_next_scheduled(Config::OPT_CRON_HOOK)) {return;}

        Config::set(Config::OPT_CRON_AUTO_SYNC, true);
        \wp_schedule_event(time(), 'hourly', Config::OPT_CRON_HOOK);

        Controllers\NotificationController::add('Automatic sync started (Hourly).', 'success');

        self::startCleaningJob();
    }

    /**
     * Stops the automatic synchronization job.
     *
     * @return void
     */
    public static function stopSync(): void {
        $timestamp = \wp_next_scheduled(Config::OPT_CRON_HOOK);

        if ($timestamp === false) {return;}

        Config::set(Config::OPT_CRON_AUTO_SYNC, false);
        \wp_unschedule_event($timestamp, Config::OPT_CRON_HOOK);

        Controllers\NotificationController::add('Automatic sync paused.', 'success');

        self::stopCleaningJob();
    }

    /**
     * Starts the automatic cleaning job (weekly).
     *
     * @return void
     */
    public static function startCleaningJob(): void {
        if (\wp_next_scheduled(Config::OPT_CLEAN_CRON_HOOK)) { return; }

        \wp_schedule_event(time(), 'weekly', Config::OPT_CLEAN_CRON_HOOK);
    }

    /**
     * Stops the automatic cleaning job.
     *
     * @return void
     */
    public static function stopCleaningJob(): void {
        $clean_timestamp = \wp_next_scheduled(Config::OPT_CLEAN_CRON_HOOK);

        if ($clean_timestamp === false) { return; }

        \wp_unschedule_event($clean_timestamp, Config::OPT_CLEAN_CRON_HOOK);
    }

}