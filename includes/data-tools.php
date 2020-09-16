<?php


class DT_Data_Reporting_Tools
{
    /**
     * Fetch data by type
     * @param $data_type contacts|contact_activity
     * @param $config_key
     * @param bool $flatten
     * @param null $limit
     * @return array Columns, rows, and total count
     */
    public static function get_data( $data_type, $config_key, $flatten = false, $limit = null ) {
      $config = self::get_config_by_key( $config_key );
      $config_progress = self::get_config_progress_by_key( $config_key );

      // Get the settings for this data type from the config
      $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
      $type_config = isset($type_configs[$data_type]) ? $type_configs[$data_type] : [];
      $last_exported_value = isset($config_progress[$data_type]) ? $config_progress[$data_type] : null;
      $all_data = !isset($type_config['all_data']) || boolval($type_config['all_data']);
      $limit = $limit ?? (isset($type_config['limit']) ? intval($type_config['limit']) : 100);

      $result = null;
      switch ($data_type) {
        case 'contact_activity':
          $filter = $config && isset( $config['contacts_filter'] ) ? $config['contacts_filter'] : array();
          $filter['limit'] = $limit;
          $result = DT_Data_Reporting_Tools::get_contact_activity( false, $filter );
          break;
        case 'contacts':
        default:
          $filter = $config && isset( $config['contacts_filter'] ) ? $config['contacts_filter'] : null;

          if ( $limit ) {
            $filter['limit'] = $limit;
          }
          // If not exporting everything, add limit and filter for last value
          if (!$all_data && !empty($last_exported_value) ) {
            $filter['last_modified'] = [
              'start' => $last_exported_value,
            ];
          }

          // Fetch the data
          $result = DT_Data_Reporting_Tools::get_contacts( $flatten, $filter );
          break;
      }

      return $result;
    }

    /**
     * Fetch contacts
     * @param bool $flatten
     * @param null $filter
     * @return array Columns, rows, and total count
     */
    public static function get_contacts( $flatten = false, $filter = null ) {
        $filter = $filter ?? array();

        // By default, sort by last updated date
        if ( !isset($filter['sort'] ) ) {
          $filter['sort'] = 'last_modified';
        }

        // Build contact generations
        // taken from [dt-theme]/dt-metrics/counters/counter-baptism.php::save_all_contact_generations
        $raw_baptism_generation_list = Disciple_Tools_Counter_Baptism::query_get_all_baptism_connections();
        $all_baptisms = Disciple_Tools_Counter_Baptism::build_baptism_generation_counts( $raw_baptism_generation_list );
        $contact_generations = array();
        foreach ( $all_baptisms as $baptism_generation ){
            $generation = $baptism_generation["generation"];
            $baptisms = $baptism_generation["ids"];
            foreach ( $baptisms as $contact ){
                $contact_generations[$contact] = $generation;
            }
        }

        $contacts = DT_Posts::list_posts( 'contacts', $filter );
        dt_write_log( sizeof( $contacts['posts'] ) . ' of ' . $contacts['total'] );
//        dt_write_log(json_encode($contacts['posts'][0]));
        if ( !isset( $filter['limit'] ) ) {
            // if total is greater than length, recursively get more
            while (sizeof( $contacts['posts'] ) < $contacts['total']) {
                $filter['offset'] = sizeof( $contacts['posts'] );
                $next_contacts = DT_Posts::list_posts( 'contacts', $filter );
                $contacts['posts'] = array_merge( $contacts['posts'], $next_contacts['posts'] );
                dt_write_log( 'adding ' . sizeof( $next_contacts['posts'] ) );
                dt_write_log( sizeof( $contacts['posts'] ) . ' of ' . $contacts['total'] );
            }
        }
        $items = array();

        $post_settings = apply_filters( "dt_get_post_type_settings", array(), 'contacts' );
        $fields = $post_settings["fields"];
        $excluded_fields = array( 'tasks', 'facebook_data' );
        $base_url = self::get_current_site_base_url();

        foreach ($contacts['posts'] as $index => $result) {
            $contact = array(
                'ID' => $result['ID'],
                'Created' => $result['post_date'],
            );
            foreach ( $fields as $field_key => $field ){
                // skip if field is hidden
                if ( isset( $field['hidden'] ) && $field['hidden'] == true ) {
                    continue;
                }
                // skip if in list of excluded fields
                if ( in_array( $field_key, $excluded_fields ) ) {
                    continue;
                }

                $type = $field['type'];
                $field_value = null;
                if (key_exists( $field_key, $result )) {
                    switch ($type) {
                        case 'key_select':
                            $field_value = self::get_label( $result, $field_key );
                            break;
                        case 'multi_select':
                            $field_value = $flatten ? implode( ",", $result[$field_key] ) : $result[$field_key];
                            break;
                        case 'user_select':
                            $field_value = $result[$field_key]['id'];
                            break;
                        case 'date':
                            $field_value = !empty( $result[$field_key]["timestamp"] ) ? gmdate( "Y-m-d H:i:s", $result[$field_key]['timestamp'] ) : "";
                            break;
                        case 'location':
                            $location_ids = array_map( function ( $location ) { return $location['label'];
                            }, $result[$field_key] );
                            $field_value = $flatten ? implode( ",", $location_ids ) : $location_ids;
                            break;
                        case 'connection':
                            $connection_ids = array_map( function ( $connection ) { return $connection['ID'];
                            }, $result[$field_key] );
                            $field_value = $flatten ? implode( ",", $connection_ids ) : $connection_ids;
                            break;
                        default:
                            $field_value = $result[$field_key];
                            if ( is_array( $field_value ) ) {
                                $field_value = json_encode( $field_value );
                            }
                            break;
                    }
                } else {
                    // Set default/blank value
                    switch ($type) {
                        case 'number':
                            $field_value = $field['default'] ?? 0;
                            break;
                        case 'key_select':
                            $field_value = null;
                            break;
                        case 'multi_select':
                            $field_value = $flatten ? null : array();
                            break;
                        case 'array':
                        case 'boolean':
                        case 'date':
                        case 'text':
                        case 'location':
                        default:
                            $field_value = $field['default'] ?? null;
                            break;
                    }
                }

                // if we calculated the baptism generation, set it here
                if ( $field_key == 'baptism_generation' && isset( $contact_generations[$result['ID']] ) ) {
                    $field_value = $contact_generations[$result['ID']];
                }

                $field_value = apply_filters( 'dt_data_reporting_field_output', $field_value, $type, $field_key, $flatten );
                $contact[$field_key] = $field_value;
            }
            $contact['site'] = $base_url;

            $items[] = $contact;
        }
        $columns = array();
        array_push( $columns, array(
            'key' => "id",
            'name' => "ID",
            'type' => 'number',
            'bq_type' => 'INTEGER',
            'bq_mode' => 'NULLABLE',
            ), array(
            'key' => "created",
            'name' => "Created",
            'type' => 'date',
            'bq_type' => 'TIMESTAMP',
            'bq_mode' => 'NULLABLE',
        ));

        foreach ( $fields as $field_key => $field ){
            // skip if field is hidden
            if ( isset( $field['hidden'] ) && $field['hidden'] == true ) {
                continue;
            }
            // skip if in list of excluded fields
            if ( in_array( $field_key, $excluded_fields ) ) {
                continue;
            }

            $column = array(
            'key' => $field_key,
            'name' => $field['name'],
            'type' => $field['type'],
            );
            switch ($field['type']) {
                case 'array':
                case 'location':
                case 'multi_select':
                    $column['bq_type'] = 'STRING';
                    $column['bq_mode'] = 'REPEATED';
                break;
                case 'connection':
                case 'user_select':
                    $column['bq_type'] = 'INTEGER';
                    $column['bq_mode'] = 'REPEATED';
                break;
                case 'date':
                    $column['bq_type'] = 'TIMESTAMP';
                    $column['bq_mode'] = 'NULLABLE';
                break;
                case 'number':
                    $column['bq_type'] = 'INTEGER';
                    $column['bq_mode'] = 'NULLABLE';
                break;
                case 'boolean':
                    $column['bq_type'] = 'BOOLEAN';
                    $column['bq_mode'] = 'NULLABLE';
                break;
                case 'key_select':
                case 'text':
                default:
                    $column['bq_type'] = 'STRING';
                    $column['bq_mode'] = 'NULLABLE';
                break;
            }
            if ( $field_key == 'last_modified' ) {
                $column['type'] = 'date';
                $column['bq_type'] = 'TIMESTAMP';
                $column['bq_mode'] = 'NULLABLE';

            }
            array_push( $columns, $column );
        }
        array_push( $columns, array(
            'key' => 'site',
            'name' => 'Site',
            'type' => 'text',
            'bq_type' => 'STRING',
            'bq_mode' => 'NULLABLE',
        ));
        return array( $columns, $items, $contacts['total'] );
    }

    /**
     * Fetch contact activity
     * @param bool $flatten
     * @param null $filter
     * @return array Columns, rows, and total count
     */
    public static function get_contact_activity( $flatten = false, $filter = null ) {
        $filter = $filter ?? array();

        $activities = self::get_post_activity( 'contacts', $filter );
//        $contacts = DT_Posts::list_posts('contacts', $filter);
        // todo: if total is greater than length, recursively get more
        dt_write_log( sizeof( $activities['activity'] ) . ' of ' . $activities['total'] );
        $items = array();

        $base_url = self::get_current_site_base_url();

        foreach ($activities['activity'] as $index => $result) {
            $activity = $result;
            $activity['site'] = $base_url;

            $items[] = $activity;
        }

        $columns = array(
            array(
                'key' => "id",
                'name' => 'ID',
            ),
            array(
                'key' => "post_id",
                'name' => 'Contact ID',
            ),
            array(
                'key' => "user_id",
                'name' => 'User ID',
            ),
            array(
                'key' => "user_name",
                'name' => 'User',
            ),
            array(
                'key' => "action_type",
                'name' => 'Action Type',
            ),
            array(
                'key' => "action_field",
                'name' => 'Action Field',
            ),
            array(
                'key' => "action_value",
                'name' => 'Action Value',
            ),
            array(
                'key' => "action_value_friendly",
                'name' => 'Action Value (Friendly)',
            ),
            array(
                'key' => "action_value_order",
                'name' => 'Action Value Order',
            ),
            array(
                'key' => "action_old_value",
                'name' => 'Action Old Value',
            ),
            array(
                'key' => "note",
                'name' => 'Note',
            ),
            array(
                'key' => "date",
                'name' => 'Date'
            ),
            array(
                'key' => 'site',
                'name' => 'Site'
            ),
        );

        return array( $columns, $items, $activities['total'] );
    }

    private static function get_post_activity( $post_type, $filter ) {
        global $wpdb;

        // By default, sort by last updated date
        if ( !isset($filter['sort'] ) ) {
          $filter['sort'] = 'last_modified';
        }

        $post_filter = $filter;
        $post_filter['limit'] = 1000; //todo: this is liable to break. We need a way of getting all contact IDs
        $data = DT_Posts::search_viewable_post( $post_type, $post_filter );
//        dt_write_log( json_encode( $data ) ); // FOR DEBUGGING
        $post_ids = dt_array_to_sql( array_map( function ( $post) { return $post->ID;
        }, $data['posts'] ) );

        $post_settings = apply_filters( "dt_get_post_type_settings", array(), $post_type );
        $fields = $post_settings["fields"];
        $hidden_fields = array( 'duplicate_of' );
        foreach ( $fields as $field_key => $field ){
            if ( isset( $field["hidden"] ) && $field["hidden"] === true ){
                $hidden_fields[] = $field_key;
            }
        }
        $hidden_keys = dt_array_to_sql( $hidden_fields );
        // phpcs:disable
        // WordPress.WP.PreparedSQL.NotPrepared
        $query_activity_select = "SELECT
                meta_id,
                object_id,
                user_id,
                user_caps,
                action,
                meta_key,
                meta_value,
                old_value,
                object_subtype,
                field_type,
                object_note,
                FROM_UNIXTIME(hist_time) AS date ";
        $query_activity_from = "FROM `$wpdb->dt_activity_log` ";
        $query_activity_where = "
            WHERE `object_type` = %s
                 AND meta_key NOT IN ( $hidden_keys )
                 AND object_id IN ( $post_ids ) ";

        $query_comments_select = "SELECT comment_ID as meta_id,
                comment_post_ID as object_id,
                user_id,
                comment_author as user_caps,
                comment_type as action,
                NULL as meta_key,
                NULL as meta_value,
                NULL as old_value,
                NULL as object_subtype,
                NULL as field_type,
                comment_content as object_note,
                comment_date_gmt as date ";
        $query_comments_from = "FROM wp_comments c
            LEFT JOIN wp_posts p on c.comment_post_ID=p.ID ";
        $query_comments_where = "
            WHERE comment_type not in ('comment', 'duplicate')
                AND p.post_type=%s
                AND comment_post_ID IN ( $post_ids ) ";

        $query = "$query_activity_select
            $query_activity_from
            $query_activity_where
            UNION
            $query_comments_select
            $query_comments_from
            $query_comments_where
            ORDER BY date ASC ";
        $params = array($post_type, $post_type);

        $total_activities = $wpdb->get_var($wpdb->prepare(
          "SELECT count(*) from ($query) as temp",
          $params
        ));
        if (isset($filter['limit'])) {
            $query .= "LIMIT %d ";
            $params[] = $filter['limit'];
        }
        $activity = $wpdb->get_results( $wpdb->prepare(
            $query,
            $params
        ) );

        //@phpcs:enable
        $activity_simple = array();
        foreach ( $activity as $a ) {
            $a->object_note = DT_Posts::format_activity_message( $a, $post_settings );

            $value_friendly = $a->meta_value;
            $value_order = 0;
            if (isset( $fields[$a->meta_key] )) {
                switch ($fields[$a->meta_key]["type"]) {
                    case 'key_select':
                    case 'multi_select':
                        $keys = array_keys( $fields[$a->meta_key]["default"] );
                        $value_friendly = $fields[$a->meta_key]["default"][$a->meta_value]["label"] ?? $a->meta_value;
                        $value_order = array_search( $a->meta_value, $keys ) + 1;
                        break;
                    default:
                        break;
                }
            }
            $activity_simple[] = array(
                "id" => $a->meta_id,
                "post_id" => $a->object_id,
                "user_id" => $a->user_id,
                "user_name" => $a->user_caps,
                "action_type" => $a->action,
                "action_field" => $a->meta_key,
                "action_value" => $a->meta_value,
                "action_value_friendly" => $value_friendly,
                "action_value_order" => $value_order,
                "action_old_value" => $a->old_value,
                "note" => $a->object_note,
                "date" => $a->date,
            );
        }

//    $paged = array_slice( $activity_simple, $args["offset"] ?? 0, $args["number"] ?? 1000 );
        //todo: get the real total apart from limit
        return array(
            "activity" => $activity_simple,
            "total" => $total_activities
        );
    }

    private static function get_label( $result, $key ) {
        return ( array_key_exists( $key, $result ) && is_array( $result[$key] ) && array_key_exists( 'label', $result[$key] ) ) ? $result[$key]['label'] : '';
    }

    protected static function get_current_site_base_url() {
        $url = str_replace( 'http://', '', home_url() );
        $url = str_replace( 'https://', '', $url );

        return trim( $url );
    }

    /**
     * Get all configurations
     * @return array
     */
    public static function get_configs() {
        $configurations_str = get_option( "dt_data_reporting_configurations" );
        $configurations_int = json_decode( $configurations_str, true );
        $configurations_ext = apply_filters( 'dt_data_reporting_configurations', array() );

      // Merge locally-created and external configurations
        $configurations = array_merge( $configurations_int ?? [], $configurations_ext );

      // Filter out disabled configurations
        $configurations = array_filter($configurations, function ( $config) {
            return isset( $config['active'] ) && $config['active'] == 1;
        });
        return $configurations;
    }

    /**
     * Get configuration by key
     * @param $config_key
     * @return mixed|null configuration
     */
    public static function get_config_by_key( $config_key ) {
        $configurations = self::get_configs();

        if ( isset( $configurations[$config_key] ) ) {
            return $configurations[$config_key];
        }

        return null;
    }

  /**
   * Get the last exported values for the given config.
   * [
   *   'config-key-1' => [
   *     'contacts' => 'last-value-exported',
   *     'contact_activity' => 'last-value-exported',
   *   ]
   * ]
   * @param $config_key
   * @return |null
   */
    public static function get_config_progress_by_key( $config_key ) {
      $configurations_str = get_option( "dt_data_reporting_configurations_progress" );
      $configurations = json_decode( $configurations_str, true );

      if ( isset( $configurations[$config_key] ) ) {
        return $configurations[$config_key];
      }

      return [];
    }

  /**
   * Set last exported values for the given config
   * @param $config_key
   * @param $config_progress
   */
    public static function set_config_progress_by_key( $config_key, $config_progress ) {
      $configurations_str = get_option( "dt_data_reporting_configurations_progress" );
      $configurations = json_decode( $configurations_str, true );

      $configurations[$config_key] = $config_progress;

      update_option( "dt_data_reporting_configurations_progress", json_encode($configurations) );
    }

  /**
   * Set the last export value for a given data type in the given config
   * @param $data_type
   * @param $config_key
   * @param $item
   */
  public static function set_last_exported_value( $data_type, $config_key, $item ) {
    $value = null;

    // Which field do we use to determine last exported for each type
    switch ($data_type) {
      case 'contacts':
      default:
        $value = $item['last_modified'];
        break;
    }

    // If value is not empty, save it
    if ( !empty($value) ) {
      $config_progress = self::get_config_progress_by_key( $config_key );
      $config_progress[$data_type] = $value;
      self::set_config_progress_by_key($config_key, $config_progress);
    }
  }
}
