<?php

/**
 * Class DT_Data_Reporting_Snapshot_Tools
 */
class DT_Data_Reporting_Snapshot_Tools
{
  public static $table_name = 'dt_post_snapshots';

  private const INTERVAL_YEAR = 'year';
  private const INTERVAL_QUARTER = 'quarter';
  private const INTERVAL_MONTH = 'month';

  public static function run_snapshot_task()
  {
    $migration_success = self::check_migrations();
    if ( !$migration_success ) {
      return;
    }

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
            UNIQUE KEY `unique_post_period` (`post_id`, `period`),
            KEY `id` (`id`),
            KEY `post_id` (`post_id`),
            KEY `post_type` (`post_type`),
            KEY `period` (`period`),
            KEY `period_interval` (`period_interval`)
        ) $charset_collate;";

      $result = $wpdb->query($sql);
      if ($result === false) {
        dt_write_log('Failed to create table: ' . $table_name);
        return false;
      }
      dt_write_log('Created table: ' . $table_name);
    }

    return true;
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
      $next_expected = self::alter_date_by_interval( $period_end, $interval );
      dt_write_log( 'Next expected: ' . $next_expected->format('Y-m-d H:i:s') );

      if ($next_expected < $now) {
        // Found an interval that needs updating
        return self::get_period_by_date($interval, $next_expected);
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

  /**
   * Modify date by interval and multipler to add/remove year/quarter/month
   * @param DateTime $date
   * @param string $interval
   * @param int $multiplier
   * @return DateTime
   * @throws DateMalformedStringException
   */
  private static function alter_date_by_interval( DateTime $date, string $interval, int $multiplier = 1 )
  {
    $alter_string = '';
    switch ($interval) {
      case self::INTERVAL_YEAR:
        $alter_string = $multiplier . ' years';
        break;
      case self::INTERVAL_QUARTER:
        $alter_string = ($multiplier * 3) . ' months';
        break;
      case self::INTERVAL_MONTH:
        $alter_string = $multiplier . ' months';
        break;
    }

    $altered_date = clone $date;

    // when jumping months where the starting point is 31st day, we need to adjust the date.
    // because of February with 28 days, we go back 3 days from 31 and adjust end of month below
    $is_31 = $date->format('d') > 30;
    if ($is_31) {
      $altered_date->modify('-3 day');
    }

    $altered_date->modify( $alter_string );

    if ( $is_31 ) {
      $date->modify('last day of this month');
    }

    return $altered_date;
  }

  /**
   * Builds snapshots for the provided period.
   * @param array $period
   * @return void
   */
  private static function build_period_snapshots(array $period)
  {
    $field_settings_by_type = [];
    $dt_post_types = DT_Posts::get_post_types();

    $activity_logs = self::get_activity_logs( $period['start'], $period['end'] );
    dt_write_log( "Activity logs for {$period['name']}: " . sizeof( $activity_logs ) );

    $previous_period_date = self::alter_date_by_interval( $period['start'], $period['interval'], -1 );
    $previous_period = self::get_period_by_date( $period['interval'], $previous_period_date );

    $previous_snapshots = self::get_snapshots( $previous_period['name'] );

    // initialize snapshots with previous period data
    $snapshots = array_map( function( $snapshot ) use ($period) {
      $snapshot['period'] = $period['name'];
      $snapshot['period_start'] = $period['start'];
      $snapshot['period_end'] = $period['end'];
      $snapshot['period_interval'] = $period['interval'];
      return $snapshot;
    }, $previous_snapshots);
    dt_write_log( "Previous snapshots for {$period['name']}: " . sizeof( $previous_snapshots ) );

    // loop over activity logs
    if ( isset( $activity_logs ) && !empty( $activity_logs) ) {
      foreach ($activity_logs as $activity_log) {
        $post_type = $activity_log['object_type'];
        $post_id = $activity_log['object_id'];

        // skip if post type is not in dt_post_types (e.g. attachments)
        if ( !in_array( $post_type, $dt_post_types ) ) {
          continue;
        }

        // get previous/existing snapshot or create new
        $snapshot = $snapshots[$post_id] ?? [
          'post_id' => $post_id,
          'post_type' => $post_type,
          'period' => $period['name'],
          'period_start' => $period['start'],
          'period_end' => $period['end'],
          'period_interval' => $period['interval'],
          'post_content' => [
            'ID' => $post_id,
            'post_type' => $post_type,
          ]
        ];

        // make sure we have the field settings for this post type
        if ( !isset( $field_settings_by_type[$post_type] ) ) {
          $field_settings_by_type[$post_type] = DT_Posts::get_post_field_settings( $post_type );
        }

        $post = $snapshot['post_content'] ?? [];
        // update snapshot value based on field type
        self::update_snapshot_value($post, $field_settings_by_type[$post_type], $activity_log);

        $snapshot['post_content'] = $post;

        // set post_title
        if ( !isset( $snapshot['post_title'] ) ) {
          $snapshot['post_title'] = $activity_log['object_name'];
        }
        if ( !isset( $snapshot['post_content']['name'] ) ) {
          $snapshot['post_content']['name'] = $activity_log['object_name'];
        }

        $snapshots[$post_id] = $snapshot;
      }
    }
    dt_write_log( "Snapshots for {$period['name']}: " . sizeof( $snapshots ) );

    self::save_snapshots( $snapshots );
  }

  /**
   * Retrieves activity logs between the provided start and end dates.
   * @param DateTime $start
   * @param DateTime $end
   * @return array|object|stdClass[]|null
   */
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

  /**
   * Retrieves snapshots for a given period.
   * @param mixed $name
   * @return array
   */
  private static function get_snapshots(mixed $name)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . self::$table_name;

    dt_write_log( 'Getting snapshots for period: ' . $name );
    $snapshots = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM $table_name WHERE period = %s",
        $name
      ),
      ARRAY_A
    );
    dt_write_log( 'Found snapshots: ' . sizeof( $snapshots ) );

    // Index snapshots by post_id
    $indexed_snapshots = [];
    if ($snapshots) {
      foreach ($snapshots as $snapshot) {
        // Decode post_content if it's a JSON string
        if ( is_string( $snapshot['post_content'] ) ) {
          try {
            $snapshot['post_content'] = json_decode( $snapshot['post_content'], true );
          } catch (Exception $e) {
            dt_write_log( "Error decoding JSON: " . $e->getMessage() );
          }
        }

        // index snapshots by post_id
        $indexed_snapshots[$snapshot['post_id']] = $snapshot;
      }
    }
    dt_write_log( 'Indexed snapshots: ' . sizeof( $indexed_snapshots ) );

    return $indexed_snapshots;
  }

  /**
   * Updates a snapshot value based on the provided activity.
   * @param $snapshot
   * @param mixed $field_settings
   * @param $activity
   * @return void
   */
  private static function update_snapshot_value(&$post, mixed $field_settings, $activity)
  {
    $action = $activity['action'];
    $field_type = $activity['field_type'];
    $meta_key = $activity['meta_key'];
    $meta_value = $activity['meta_value'];

    $actions = [ 'field_update', 'connected to', 'disconnected from ' ];
    if ( in_array( $action, $actions ) ) {
      if ( !is_array( $post ) ) {
        dt_write_log( 'Post is not an array: ' . json_encode( $post ) );
      }
      if ( !isset( $post['post_date'] ) ) {
        $post['post_date'] = $activity['hist_time'];
      }
      switch ($field_type) {
        case 'connection':
          $field_setting = DT_Posts::get_post_field_settings_by_p2p($field_settings, $meta_key, ($action == 'disconnected from') ? ['from', 'to', 'any'] : ['to', 'from', 'any']);
          if (!empty($field_setting)) {
            $meta_key = $field_setting['key'];
          }
          // fall through to normal multi-value field update
        case 'link':
        case 'tags':
        case 'location':
        case 'location_meta':
        case 'multi_select':
          if ( !isset( $post[$meta_key] ) ) {
            $post[$meta_key] = [];
          } else if ( !is_array( $post[$meta_key] ) ) {
            dt_write_log( "Post key is not an array: $meta_key | {$post['ID']}" );
            dt_write_log( json_encode( $post ) );
          }

          if ( $meta_value === 'value_deleted' ) {
            $old_value = $activity['old_value'];
            $post[$meta_key] = array_values(array_filter($post[$meta_key], function ($value) use ($old_value) {
              return $value !== $old_value;
            }));
          } else {
            $post[$meta_key][] = $meta_value;
          }
          break;
        case 'user_select':
          if ( $meta_value === 'value_deleted') {
            $post[$meta_key] = null;
          } else {
            $meta_array = explode( '-', $meta_value ); // Separate the type and id
            $type = $meta_array[0]; // Build variables
            if ( isset( $meta_array[1] ) ) {
              $id = $meta_array[1];
              if ($type == 'user' && $id) {
                $post[$meta_key] = $id;
              }
            }
          }
          break;
        case 'communication_channel':
        case '':
          // some communication channel activity don't have a field_type for some reason.
          // The meta key is in the format contact_address_89a.
          // The actual field key is sometimes in object_subtype, but sometimes that also has a suffix.
          // So we'll just look for contact_* in the meta key and store it as an array field.
          $meta_key_parts = explode('_', $meta_key);
          if ( count($meta_key_parts) > 1 && $meta_key_parts[0] == 'contact' ) {
            // only process contact_* fields
            $meta_key = implode('_', [$meta_key_parts[0], $meta_key_parts[1]]);

            // Use the same logic as for multi-value fields above
            if (!isset($post[$meta_key])) {
              $post[$meta_key] = [];
            }

            if ($meta_value === 'value_deleted') {
              $old_value = $activity['old_value'];
              $post[$meta_key] = array_values(array_filter($post[$meta_key], function ($value) use ($old_value) {
                return $value !== $old_value;
              }));
            } else {
              $post[$meta_key][] = $meta_value;
            }
          }
          break;
        case 'details': // communication_channel fields add a details record that we don't need
        case 'hash': // magic links create hash fields that we don't need
          break;
        case 'key_select':
        case 'date':
        case 'datetime':
        case 'number':
        case 'boolean':
        case 'text':
        case 'textarea':
        default:
          if ( $meta_value === 'value_deleted') {
            $post[$meta_key] = null;
          } else {
            $post[$meta_key] = $meta_value;
          }
          break;
      }
    } else if ( $action == 'deleted' ) {
      $post['deleted'] = true;
    }
  }

  /**
   * Saves provided snapshots to the dt_posts_snapshots database table.
   * Filters out deleted posts before saving and ensures snapshots have valid required fields.
   *
   * @param array $snapshots An array of snapshot data to be saved. Each snapshot must include:
   *                         - 'post_id': The ID of the post.
   *                         - 'post_type': The type of the post.
   *                         - 'period': The period associated with the snapshot.
   *                         - 'period_start': The start period as a DateTime object.
   *                         - 'period_end': The end period as a DateTime object.
   *                         - 'period_interval': The interval of the period.
   *                         Optionally includes:
   *                         - 'post_title': The title of the post.
   *                         - 'post_content': An array of content data for the post.
   *
   * @return void
   */
  private static function save_snapshots(array $snapshots)
  {
    // save snapshots to dt_posts_snapshots
    if ( !empty( $snapshots ) ) {
      // remove deleted posts from snapshots before saving. We don't need future snapshots for deleted posts.
      $filtered_snapshots = array_filter( $snapshots, function( $snapshot ) {
        return !isset( $snapshot['post_content']['deleted'] );
      });
      dt_write_log( 'Saving snapshots: ' . sizeof( $filtered_snapshots ) );

      global $wpdb;
      $table_name = $wpdb->prefix . self::$table_name;
      $now = (new DateTime())->format('Y-m-d H:i:s');

      $logged_data = false;
      foreach ($filtered_snapshots as $snapshot) {
        if (empty($snapshot['post_id']) || empty($snapshot['post_type'])
          || empty($snapshot['period']) || empty($snapshot['period_start'])
          || empty($snapshot['period_end']) || empty($snapshot['period_interval'])) {
          continue;
        }

        $data = [
          'post_id' => $snapshot['post_id'],
          'post_type' => $snapshot['post_type'],
          'post_title' => $snapshot['post_title'] ?? '',
          'period' => $snapshot['period'],
          'period_start' => $snapshot['period_start']->format('Y-m-d H:i:s'),
          'period_end' => $snapshot['period_end']->format('Y-m-d H:i:s'),
          'period_interval' => $snapshot['period_interval'],
          'post_content' => wp_json_encode($snapshot['post_content'] ?? []),
          'snapshot_date' => $now
        ];
        if (!$logged_data) {
//          dt_write_log( 'Saving data: ' . json_encode( $data ) );
          $logged_data = true;
        }
        $format = [
          '%d',
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
          '%s'
        ];

        $wpdb->replace(
          $table_name,
          $data,
          $format
        );
      }
    }
  }
}
