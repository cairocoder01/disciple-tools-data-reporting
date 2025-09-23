<?php


class DT_Data_Reporting_Snapshot_Tools
{
  public static $table_name = 'dt_post_snapshots';

  public static function run_snapshot_task()
  {
    self::check_migrations();
return;
    $period = self::get_next_period();

    self::build_period_snapshots( $period );
  }

  private static function check_migrations()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . self::$table_name;
    $table_exists = (bool)$wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
      DB_NAME,
      $table_name
    ));

    dt_write_log('Check table exists: ' . ($table_exists ? 'yes' : 'no'));
    /*
     * dt_posts_snapshots
     * - id bigint(20) unsigned Auto Increment
     * - post_id bigint(20) unsigned
     * - post_type varchar(20)
     * - post_title text
     * - period varchar(50)
     * - period_start datetime
     * - period_end datetime
     * - period_interval varchar(20)
     * - post_content json
     * - snapshot_date datetime
     */
    if (!$table_exists) {
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` bigint(20) unsigned NOT NULL,
            `post_type` varchar(20) NOT NULL,
            `post_title` text NOT NULL,
            `period` varchar(50) NOT NULL,
            `period_start` datetime NOT NULL,
            `period_end` datetime NOT NULL,
            `period_interval` varchar(20) NOT NULL,
            `post_content` json NOT NULL,
            `snapshot_date` datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY `post_id` (`post_id`),
            KEY `post_type` (`post_type`),
            KEY `period` (`period`),
            KEY `period_interval` (`period_interval`)
        ) $charset_collate;";

      $wpdb->query( $sql );
      dt_write_log('Created table: ' . $table_name);
    }

    return $table_exists;
  }

  private static function get_next_period()
  {
    // get next period
    // - get max period_end by interval
    // - try year
    // - try quarter
    // - try month
    // - if none, get min from dt_activity_log
    $interval = 'year';

    $date = new DateTime(); // today - interval
    return self::get_period_by_date( $interval, $date );
  }

  /**
   * Retrieves the period details based on the provided interval and date.
   *
   * @param string $interval The duration or period type (e.g., day, week, month).
   * @param DateTime $date The date serving as the reference point for determining the period.
   * @return array An associative array containing:
   *               - 'name': The name of the period.
   *               - 'interval': The specified interval type.
   *               - 'start': The starting DateTime of the period.
   *               - 'end': The ending DateTime of the period.
   */
  private static function get_period_by_date( string $interval, DateTime $date )
  {
    return [
      'name' => '',
      'interval' => '',
      'start' => new DateTime(),
      'end' => new DateTime(),
    ];
  }

  private static function build_period_snapshots(array $period)
  {
    $field_settings_by_type = [];

    $activity_logs = self::get_activity_logs( $period['start'], $period['end'] );

    $previous_period_date = new DateTime(); // $period['start'] - $period['interval']
    $previous_period = self::get_period_by_date( $period['interval'], $previous_period_date );

    $previous_snapshots = self::get_snapshots( $previous_period['name'] );

    $snapshots = [];
    // loop over activity logs
    foreach ($activity_logs as $activity_log) {
      $post_type = $activity_log['object_type'];
      $post_id = $activity_log['object_id'];
      $snapshot = $snapshots[$post_id] ?? [ 'ID' => $post_id, 'post_type' => $post_type];

      self::update_snapshot_value( $snapshot, $field_settings_by_type[$post_type], $activity_log );

      $snapshots[$post_id] = $snapshot;
    }

    self::save_snapshots( $snapshots );
  }

  private static function get_activity_logs(mixed $start, mixed $end)
  {
    // query all dt_activity_log records between start and end
    // sort by hist_time ASC
  }

  private static function get_snapshots(mixed $name)
  {
    // get by name and index by post_id
  }

  private static function update_snapshot_value(&$snapshot, mixed $post_type, $activity_log)
  {
    // based on field_type, update snapshot value
  }

  private static function save_snapshots(array $snapshots)
  {
    // save snapshots to dt_posts_snapshots
  }
}
