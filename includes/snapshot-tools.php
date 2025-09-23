<?php


class DT_Data_Reporting_Snapshot_Tools
{
  public static $table_name = 'dt_post_snapshots';

  private const INTERVAL_YEAR = 'year';
  private const INTERVAL_QUARTER = 'quarter';
  private const INTERVAL_MONTH = 'month';

  public static function run_snapshot_task()
  {
    self::check_migrations();

    $period = self::get_next_period();

    dt_write_log( 'Next period: ' . json_encode( $period ) );

    if ( $period !== null ) {
      self::build_period_snapshots( $period );
    }
  }

  /**
   * Checks if the database table exists and creates it if it doesn't.
   * @return bool
   */
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

  /**
   * Checks the database for the next period to be updated.
   * @return array|null
   * @throws DateMalformedIntervalStringException
   * @throws DateMalformedStringException
   */
  private static function get_next_period()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . self::$table_name;

    $max_periods = $wpdb->get_results(
      "SELECT period_interval, MAX(period_end) as max_period_end
     FROM $table_name
     GROUP BY period_interval",
      ARRAY_A
    );

    $intervals = [self::INTERVAL_YEAR, self::INTERVAL_QUARTER, self::INTERVAL_MONTH];
    $now = new DateTime();

    foreach ($intervals as $interval) {
      dt_write_log('Checking interval: ' . $interval);
      $period_end = null;
      foreach ($max_periods as $max_period) {
        if ($max_period['period_interval'] === $interval) {
          $period_end = new DateTime($max_period['max_period_end']);
          break;
        }
      }

      dt_write_log('Period end: ' . ($period_end ? $period_end->format('Y-m-d H:i:s') : 'null'));
      if ($period_end === null) {
        // No snapshots exist for this interval, start from earliest activity date
        $min_date = $wpdb->get_var("SELECT MIN(hist_time) FROM $wpdb->dt_activity_log");
        if ($min_date) {
          dt_write_log( 'Min date: ' . $min_date );
          return self::get_period_by_date($interval, DateTime::createFromFormat('U', $min_date));
        }
        return self::get_period_by_date($interval, $now);
      }

      // Check if the next expected period is in the past by adding the interval duration
      $interval_duration = new DateInterval(match ($interval) {
        self::INTERVAL_YEAR => 'P1Y',
        self::INTERVAL_QUARTER => 'P3M',
        self::INTERVAL_MONTH => 'P1M',
      });

      $next_expected = clone $period_end;
      $next_expected->add($interval_duration);

      if ($next_expected < $now) {
        // Found an interval that needs updating
        return self::get_period_by_date($interval, $period_end);
      }
    }

    return null; // All periods are up to date
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
    $start = clone $date;
    $end = clone $date;

    switch ($interval) {
      case self::INTERVAL_YEAR:
        $start->modify('first day of January ' . $date->format('Y'));
        $end->modify('last day of December ' . $date->format('Y'));
        $name = $date->format('Y');
        break;

      case self::INTERVAL_QUARTER:
        $quarter = ceil($date->format('n') / 3);
        $start_month = ($quarter - 1) * 3 + 1;
        $end_month = $quarter * 3;
        $start->setDate($date->format('Y'), $start_month, 1);
        $end->setDate($date->format('Y'), $end_month, 1)->modify('last day of this month');
        $name = $date->format('Y') . '-Q' . $quarter;
        break;

      case self::INTERVAL_MONTH:
        $start->modify('first day of this month');
        $end->modify('last day of this month');
        $name = $date->format('Y-m');
        break;

      default:
        throw new InvalidArgumentException('Invalid interval type');
    }

    $start->setTime(0, 0, 0);
    $end->setTime(23, 59, 59);

    return [
      'name' => $name,
      'interval' => $interval,
      'start' => $start,
      'end' => $end,
    ];
  }

  private static function build_period_snapshots(array $period)
  {
    $field_settings_by_type = [];

    $activity_logs = self::get_activity_logs( $period['start'], $period['end'] );
    dt_write_log( "Activity logs for {$period['name']}: " . sizeof( $activity_logs ) );

    $previous_period_date = new DateTime(); // $period['start'] - $period['interval']
    $previous_period = self::get_period_by_date( $period['interval'], $previous_period_date );

    $previous_snapshots = self::get_snapshots( $previous_period['name'] );

    $snapshots = [];
    // loop over activity logs
    if ( isset( $activity_logs ) && !empty( $activity_logs) ) {
      foreach ($activity_logs as $activity_log) {
        $post_type = $activity_log['object_type'];
        $post_id = $activity_log['object_id'];
        $snapshot = $snapshots[$post_id] ?? ['ID' => $post_id, 'post_type' => $post_type];

        if ( !isset( $field_settings_by_type[$post_type] ) ) {
          $field_settings_by_type[$post_type] = DT_Posts::get_post_field_settings( $post_type );
        }

        self::update_snapshot_value($snapshot, $field_settings_by_type[$post_type], $activity_log);

        $snapshots[$post_id] = $snapshot;
      }
    }

    self::save_snapshots( $snapshots );
  }

  private static function get_activity_logs(DateTime $start, DateTime $end)
  {
    global $wpdb;

    $sql = $wpdb->prepare(
      "SELECT *
     FROM $wpdb->dt_activity_log
     WHERE hist_time BETWEEN %d AND %d
     ORDER BY hist_time ASC",
      $start->getTimestamp(),
      $end->getTimestamp()
    );


    return $wpdb->get_results($sql, ARRAY_A);
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
