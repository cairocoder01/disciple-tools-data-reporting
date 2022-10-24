<?php


class DT_Data_Reporting_Tools
{
    // limit filtering to only those that are manually implemented for activity
    private static $filter_fields = [ 'tags', 'sources', 'type' ];
    private static $supported_filters = [
        'sort' => true,
        'limit' => true,
        'tags' => true,
        'sources' => true,
        'type' => true,
        'last_modified' => true,
        'date' => true,
    ];


    private static $excluded_fields = array(
        'contacts' => array( 'name', 'nickname', 'tasks', 'facebook_data' ),
        'default' => array( 'name' ),
    );

    private static function is_excluded_field( $type, $field_key ) {
        if ( array_key_exists( $type, self::$excluded_fields ) ) {
            return in_array( $field_key, self::$excluded_fields[$type] );
        }
        return in_array( $field_key, self::$excluded_fields['default'] );
    }
    private static $included_hidden_fields = array(
        'contacts' => array( 'accepted', 'source_details', 'type' ),
        'default' => array( 'type' )
    );
    private static function is_included_hidden_field( $type, $field_key ) {
        if ( array_key_exists( $type, self::$included_hidden_fields ) ) {
            return in_array( $field_key, self::$included_hidden_fields[$type] );
        }
        return in_array( $field_key, self::$included_hidden_fields['default'] );
    }

    /**
     * Fetch data by type
     * @param $data_type contacts|contact_activity|{post_type}|{post_type_singular}_activity
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
        $type_config = isset( $type_configs[$data_type] ) ? $type_configs[$data_type] : [];
        $last_exported_value = isset( $config_progress[$data_type] ) ? $config_progress[$data_type] : null;
        $all_data = !isset( $type_config['all_data'] ) || boolval( $type_config['all_data'] );
        // Use limit from config only if all_data is false
        $limit = $limit ?? ( !$all_data && isset( $type_config['limit'] ) ? intval( $type_config['limit'] ) : 100 );
        // dt_write_log(json_encode($type_config));

        // for activity types, convert `contact_activity` to `contacts` to get correct filter
        $root_type = str_replace( '_activity', 's', $data_type );
        $is_activity = $root_type !== $data_type;
        $filter_key = $root_type . '_filter';
        $filter = $config && isset( $config[$filter_key] ) ? $config[$filter_key] : [];

        if ( $limit ) {
            $filter['limit'] = $limit;
        }
        // If not exporting everything, add limit and filter for last value
        if ( !$all_data && !empty( $last_exported_value ) ) {
            $date_field = $is_activity ? 'date' : 'last_modified';
            $filter[$date_field] = [
                'start' => $last_exported_value,
            ];
        }

        // Fetch the data
        $result = null;
        if ( $is_activity ) {
            $result = self::get_post_activity( $root_type, $filter );
        } else {
            $result = self::get_posts( $data_type, $flatten, $filter );
        }

        return $result;
    }

    /**
     * Fetch post activity
     * @param string $post_type
     * @param null $filter
     * @return array Columns, rows, and total count
     */
    public static function get_post_activity( $post_type, $filter = null ) {
        $filter = $filter ? array_intersect_key( $filter, self::$supported_filters ) : array();

        $activities = self::query_post_activity( $post_type, $filter );
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
                'name' => "ID",
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "meta_id",
                'name' => 'Meta ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "post_id",
                'name' => 'Post ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "user_id",
                'name' => 'User ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "user_name",
                'name' => 'User',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_type",
                'name' => 'Action Type',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_field",
                'name' => 'Action Field',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value",
                'name' => 'Action Value',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value_friendly",
                'name' => 'Action Value (Friendly)',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value_order",
                'name' => 'Action Value Order',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_old_value",
                'name' => 'Action Old Value',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "note",
                'name' => 'Note',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "date",
                'name' => 'Date',
                'type' => 'date',
                'bq_type' => 'TIMESTAMP',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => 'site',
                'name' => 'Site',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
        );

        return array( $columns, $items, $activities['total'] );
    }


    /**
     * Fetch post data to return
     * @param string $post_type
     * @param bool $flatten
     * @param null $filter
     * @return array Columns, rows, and total count
     */
    public static function get_posts( $post_type, $flatten = false, $filter = null ) {
        $is_dt_1_0 = version_compare( wp_get_theme()->version, '1.0.0', '>=' );

      // Fetch all post data
        try {
            $posts = self::query_posts( $post_type, $filter );
        } catch ( Exception $ex ) {
            dt_write_log( "Error fetching $post_type: {$ex->getMessage()}" );
            return array( null, null, 0 );
        }

        $contact_generations = array();
        if ( $post_type === 'contacts' ) {
          // Build contact generations
          // taken from [dt-theme]/dt-metrics/counters/counter-baptism.php::save_all_contact_generations
            $raw_baptism_generation_list = Disciple_Tools_Counter_Baptism::query_get_all_baptism_connections();
            $all_baptisms = Disciple_Tools_Counter_Baptism::build_baptism_generation_counts( $raw_baptism_generation_list );
            foreach ($all_baptisms as $baptism_generation) {
                $generation = $baptism_generation["generation"];
                $baptisms = $baptism_generation["ids"];
                foreach ($baptisms as $contact) {
                    $contact_generations[$contact] = $generation;
                }
            }
        }

        $items = array();

        $post_settings = apply_filters( "dt_get_post_type_settings", array(), $post_type );
        $fields = $post_settings["fields"];
        $base_url = self::get_current_site_base_url();
        $locations = self::get_location_data( $posts['posts'] );

      // process each post
        foreach ($posts['posts'] as $index => $result) {
            $post = array(
            'ID' => $result['ID'],
            'Created' => $result['post_date'],
            );

          // Theme v1.0.0 changes post_date to a proper date object we need to format
            if ( $is_dt_1_0 && isset( $result['post_date']['timestamp'] ) ) {
                $post['Created'] = !empty( $result['post_date']["timestamp"] ) ? gmdate( "Y-m-d H:i:s", $result['post_date']['timestamp'] ) : "";
            }

          // Loop over all fields to parse/format each
            foreach ( $fields as $field_key => $field ){
              // skip if field is hidden, unless marked as exception above
                if ( isset( $field['hidden'] ) && $field['hidden'] == true && !self::is_included_hidden_field( $post_type, $field_key ) ) {
                    continue;
                }
              // skip if in list of excluded fields
                if ( self::is_excluded_field( $post_type, $field_key ) ) {
                    continue;
                }

                $type = $field['type'];

              // skip communication_channel fields since they are all PII
                if ( $type == 'communication_channel' ) {
                    continue;
                }

                $field_value = self::get_field_value( $result, $field_key, $type, $flatten, $locations );


                if ( $post_type === 'contacts' ) {
                  // if we calculated the baptism generation, set it here
                    if ( $field_key == 'baptism_generation' && isset( $contact_generations[$result['ID']] ) ) {
                        if ( $fields[$field_key]['type'] === 'number' ) {
                            $generation = $contact_generations[$result['ID']];
                            $field_value = empty( $generation ) ? '' : intval( $generation );
                        } else {
                            $field_value = $contact_generations[$result['ID']];
                        }
                    }
                }

                $field_value = apply_filters( 'dt_data_reporting_field_output', $field_value, $type, $field_key, $flatten );
                $post[$field_key] = $field_value;
            }
            $post['site'] = $base_url;

            $items[] = $post;
        }
        $columns = self::build_columns( $fields, $post_type );
        return array( $columns, $items, $posts['total'] );
    }

    /**
     * Fetch post data by type
     * @param $post_type
     * @param null $filter
     * @return array|WP_Error
     * @throws Exception If DT_Posts::list_posts throws an error.
     */
    private static function query_posts( $post_type, $filter = null ) {
        // limit filtering to only those that are manually implemented for activity
        $filter = $filter ? array_intersect_key( $filter, self::$supported_filters ) : array();

        // By default, sort by last updated date
        if ( !isset( $filter['sort'] ) ) {
            $filter['sort'] = 'last_modified';
        }

        $posts = DT_Posts::list_posts( $post_type, $filter, false );
        if ( is_wp_error( $posts ) ) {
            $error_message = $posts->get_error_message() ?? '';
            throw new Exception( $error_message );
        }

        dt_write_log( sizeof( $posts['posts'] ) . ' of ' . $posts['total'] );
        if ( !isset( $filter['limit'] ) ) {
            // if total is greater than length, recursively get more
            $retrieved_posts = sizeof( $posts['posts'] );
            while ($retrieved_posts < $posts['total']) {
                $filter['offset'] = sizeof( $posts['posts'] );
                $next_posts = DT_Posts::list_posts( $post_type, $filter );
                $posts['posts'] = array_merge( $posts['posts'], $next_posts['posts'] );
                dt_write_log( 'adding ' . sizeof( $next_posts['posts'] ) );
                $retrieved_posts = sizeof( $posts['posts'] );
                dt_write_log( $retrieved_posts . ' of ' . $posts['total'] );
            }
        }

        return $posts;
    }

    private static function query_post_activity( $post_type, $filter ) {
        global $wpdb;

        // By default, sort by last updated date
        if ( !isset( $filter['sort'] ) ) {
            $filter['sort'] = 'last_modified';
        }

        $post_settings = apply_filters( "dt_get_post_type_settings", array(), $post_type );
        $fields = $post_settings["fields"];
        $excluded_fields = array_key_exists( $post_type, self::$excluded_fields )
          ? self::$excluded_fields[$post_type]
          : self::$excluded_fields['default'];
        $hidden_fields = array_merge( array( 'duplicate_of' ), $excluded_fields );

        foreach ( $fields as $field_key => $field ){
            if ( isset( $field["hidden"] ) && $field["hidden"] === true ){
                // if field is marked as exception to hidden fields, don't exclude it here
                if ( self::is_included_hidden_field( $post_type, $field_key ) ) {
                    continue;
                }
                $hidden_fields[] = $field_key;
            }
        }
        $hidden_keys = dt_array_to_sql( $hidden_fields );
        // phpcs:disable
        // WordPress.WP.PreparedSQL.NotPrepared

        // Subquery to filter by posts associated with the activity
        $post_filter_subquery = "SELECT 
            DISTINCT post_id
            FROM `$wpdb->postmeta`
            WHERE 1=1 ";
        foreach( self::$filter_fields as $filter_key ) {
            if ( in_array($filter_key, ['sort', 'limit']) ) {
                continue;
            }
            if (isset($filter[$filter_key])) {
                $post_filter_subquery .= "AND (meta_key='" . esc_sql($filter_key) . "' and meta_value in (" . dt_array_to_sql($filter[$filter_key]) . ")) ";
            }
        }

//        $charset = $wpdb->get_charset_collate();
        $charset = $wpdb->collate;
        $collate = !empty($charset) ? "COLLATE $charset" : "";

        // Query dt_activity_log table
        $query_activity_select = "SELECT
                CONCAT('A', histid) as id,
                meta_id,
                object_id,
                user_id,
                user_caps $collate as user_caps,
                action $collate as action,
                meta_key $collate as meta_key,
                meta_value $collate as meta_value,
                old_value $collate as old_value,
                object_type $collate as object_type,
                object_subtype $collate as object_subtype,
                field_type $collate as field_type,
                object_note $collate as object_note,
                FROM_UNIXTIME(hist_time) AS date ";
        $query_activity_from = "FROM `$wpdb->dt_activity_log` ";
        $query_activity_where = "
            WHERE `object_type` = %s
                 AND meta_key NOT IN ( $hidden_keys )
                 AND object_id IN ( $post_filter_subquery ) ";

        // Query wp_comments table
        $query_comments_select = "SELECT CONCAT('C', comment_ID) as id,
                comment_ID as meta_id,
                comment_post_ID as object_id,
                user_id,
                comment_author as user_caps,
                comment_type as action,
                NULL as meta_key,
                NULL as meta_value,
                NULL as old_value,
                post_type as object_type,
                NULL as object_subtype,
                NULL as field_type,
                comment_content as object_note,
                comment_date_gmt as date ";
        $query_comments_from = "FROM `$wpdb->comments` c
            LEFT JOIN `$wpdb->posts` p on c.comment_post_ID=p.ID ";
        $query_comments_where = "
            WHERE comment_type not in ('comment', 'duplicate')
                AND p.post_type=%s
                AND comment_post_ID IN ( $post_filter_subquery ) ";

        // If there is a last date value, find activity new than that
        if ( isset($filter['date']) && isset($filter['date']['start']) ) {
            $start = $filter['date']['start'];
            $since = strtotime($start);

            $query_activity_where .= "AND hist_time >= " . esc_sql( $since ) . " ";
            $query_comments_where .= "AND comment_date_gmt >= '" . esc_sql( $start ) . "' ";
        }

        // Set UTC as time zone for subsequent queries
        $wpdb->query(
            $wpdb->prepare("SET time_zone='+00:00';")
        );
        // Join 2 queries in a union
        $query = "
            $query_activity_select
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
        $prepared_sql = $wpdb->prepare(
            $query,
            $params
        );
        //dt_write_log($prepared_sql);
        $activity = $wpdb->get_results($prepared_sql);

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
                    case 'tags':
                        $keys = array_keys( $fields[$a->meta_key]["default"] );
                        $value_friendly = $fields[$a->meta_key]["default"][$a->meta_value]["label"] ?? $a->meta_value;
                        $value_order = array_search( $a->meta_value, $keys ) + 1;
                        break;
                    default:
                        break;
                }
            }
            $activity_simple[] = array(
                "id" => $a->id,
                "meta_id" => $a->meta_id,
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

        return array(
            "activity" => $activity_simple,
            "total" => $total_activities
        );
    }

    private static function get_label( $result, $key ) {
        return ( array_key_exists( $key, $result ) && is_array( $result[$key] ) && array_key_exists( 'label', $result[$key] ) ) ? $result[$key]['label'] : '';
    }

    /**
     * Fetch location grid data to get country code and admin level 1 for each
     * so we can filter out any more detailed data
     * @param $posts
     * @return mixed
     */
    private static function get_location_data( $posts ) {
        global $wpdb;

        // get all of the location IDs from each post's location_grid field
        $grid_ids = array_reduce( $posts, function ( $ids, $post ) {
            if ( isset( $post['location_grid'] ) ) {
                $location_ids = array_map(function ( $location) {
                    return $location['id'];
                }, $post['location_grid']);
                $ids = array_merge( $ids, $location_ids );
            }
            return $ids;
        }, []);

        // return empty if no posts have location data
        if ( count( $grid_ids ) == 0 ) {
            return array();
        }

        // Query to get country_code and admin1 name for each location
        $locations = $wpdb->get_results( $wpdb->prepare("
            select orig.grid_id, orig.country_code, a1.name
            from $wpdb->dt_location_grid orig
            left join $wpdb->dt_location_grid a1 on orig.admin1_grid_id=a1.grid_id
            where orig.grid_id in (" .
            implode( ',', array_fill( 0, count( $grid_ids ), '%d' ) ) .
            ")",
            $grid_ids
        ), ARRAY_A );

        // index results by grid_id for easy access without searching
        return array_reduce( $locations, function ( $map, $location ) {
            $map[$location['grid_id']] = $location;
            return $map;
        }, []);
    }

    /**
     * Get field value from result, taking in to account the field type
     * @param $result
     * @param $field_key
     * @param $type
     * @param $flatten
     * @return array|false|int|mixed|string
     */
    private static function get_field_value( $result, $field_key, $type, $flatten, $locations ) {
        if (key_exists( $field_key, $result )) {
            switch ($type) {
                case 'key_select':
                    $field_value = self::get_label( $result, $field_key );
                    break;
                case 'multi_select':
                case 'tags':
                    $field_value = $flatten ? implode( ",", $result[$field_key] ) : $result[$field_key];
                    break;
                case 'user_select':
                    $field_value = $result[$field_key]['id'];
                    break;
                case 'date':
                    $field_value = !empty( $result[$field_key]["timestamp"] ) ? gmdate( "Y-m-d H:i:s", $result[$field_key]['timestamp'] ) : "";
                    break;
                case 'location':
                    // Map country and admin1 data from location_grid table to restrict
                    // location to only admin level 1 (first level within a country, like states/provinces)
                    $location_names = array_map( function ( $location ) use ( $locations ) {
                        if ( isset( $locations[$location['id']] ) ) {
                            $grid_loc = $locations[$location['id']];
                            // Try to return "{2-letter-country-code}-{admin1-name}"
                            if ( !empty( $grid_loc['name'] ) ) {
                                return $grid_loc['country_code'] . "-" . $grid_loc['name'];
                            }
                            // fall back to just country code
                            return $grid_loc['country_code'];
                        }
                        // if no grid data, return null for safety of not exposing PII
                        return null;
                    }, $result[$field_key] );

                    // Remove null and duplicates
                    $location_names = array_unique( array_filter( $location_names ) );

                    $field_value = $flatten ? implode( ",", $location_names ) : $location_names;
                    break;
                case 'connection':
                    $connection_ids = array_map( function ( $connection ) { return $connection['ID'];
                    }, $result[$field_key] );
                    $field_value = $flatten ? implode( ",", $connection_ids ) : $connection_ids;
                    break;
                case 'number':
                    $field_value = empty( $result[$field_key] ) ? '' : intval( $result[$field_key] );
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
                case 'tags':
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

        return $field_value;
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
   *     'groups' => 'last-value-exported',
   *     'group_activity' => 'last-value-exported',
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

        update_option( "dt_data_reporting_configurations_progress", json_encode( $configurations ) );
    }

  /**
   * Set the last export value for a given data type in the given config
   * @param $data_type
   * @param $config_key
   * @param $item
   */
    public static function set_last_exported_value( $data_type, $config_key, $item ) {
        $value = null;

        $root_type = str_replace( '_activity', 's', $data_type );
        $is_activity = $root_type !== $data_type;

        // Which field do we use to determine last exported for each type
        if ( $is_activity ) {
            $value = $item['date'];
        } else {
            $value = $item['last_modified'];
        }

        // If value is not empty, save it
        if ( !empty( $value ) ) {
            $config_progress = self::get_config_progress_by_key( $config_key );
            $config_progress[$data_type] = $value;
            self::set_config_progress_by_key( $config_key, $config_progress );
        }
    }

    /**
     * Store last export results in option in case of issue to debug
     * @param $data_type - contacts, contact_activity, groups, group_activity, etc.
     * @param $config_key
     * @param $results
     */
    public static function store_export_logs( $data_type, $config_key, $results ) {
        $export_logs_str = get_option( "dt_data_reporting_export_logs" );
        $export_logs = json_decode( $export_logs_str, true );

        if ( !isset( $export_logs[$config_key] ) ) {
            $export_logs[$config_key] = array();
        }
        if ( !isset( $export_logs[$config_key][$data_type] ) ) {
            $export_logs[$config_key][$data_type] = array();
        }
        $export_logs[$config_key][$data_type] = $results;

        update_option( "dt_data_reporting_export_logs", json_encode( $export_logs ) );
    }

    /**
     * Send data to provider
     * @param $columns
     * @param $rows
     * @param $type
     * @param $config
     * @return array|void|WP_Error Object with success and messages keys
     */
    public static function send_data_to_provider( $columns, $rows, $type, $config ) {
        $provider = isset( $config['provider'] ) ? $config['provider'] : 'api';

        if ($provider == 'api') {
            // return list of log messages (with type: error, success)
            $export_result = [
                'success' => false,
                'messages' => array(),
            ];
            // Get the settings for this data type from the config
            $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
            $type_config = isset( $type_configs[$type] ) ? $type_configs[$type] : [];
            $all_data = !isset( $type_config['all_data'] ) || boolval( $type_config['all_data'] );

            $args = array(
                'method' => 'POST',
                'timeout'     => 45,
                'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8'
                ),
                'body' => json_encode(array(
                    'columns' => $columns,
                    'items' => $rows,
                    'type' => $type,
                    'truncate' => $all_data
                )),
            );

            // Add auth token if it is part of the config
            if (isset( $config['token'] )) {
                $args['headers']['Authorization'] = 'Bearer ' . $config['token'];
            }

            // POST the data to the endpoint
            $result = wp_remote_post( $config['url'], $args );

            if (is_wp_error( $result )) {
                // Handle endpoint error
                $error_message = $result->get_error_message() ?? '';
                dt_write_log( $error_message );
                $export_result['messages'][] = [
                    'type' => 'error',
                    'message' => "Error: $error_message",
                ];
            } else {
                // Success
                $status_code = wp_remote_retrieve_response_code( $result );
                $export_result['success'] = true;
                if ($status_code !== 200) {
                    $export_result['messages'][] = [
                        'type' => 'error',
                        'message' => "Error: Status Code $status_code",
                    ];
                } else {
                    $export_result['messages'][] = [
                        'type' => 'success',
                        'message' => "Success",
                    ];
                }
                // $result_body = json_decode($result['body']);
                $export_result['messages'][] = [
                    'message' => $result['body'],
                ];

                dt_activity_insert([
                    'action' => 'export',
                    'object_type' => $type,
                    'object_subtype' => 'non-pii',
                    'meta_key' => 'provider',
                    'meta_value' => $provider,
                    'object_note' => 'disciple-tools-data-reporting'
                ]);
            }
            return $export_result;
        } else {
            // fallback for using action with no return value. Filter is preferred to return success and log messages
            do_action( "dt_data_reporting_export_provider_$provider", $columns, $rows, $type, $config );

            // send data to provider to process and return success indicator and any log messages
            $provider_result = apply_filters( "dt_data_reporting_export_provider_$provider", $columns, $rows, $type, $config );
            // dt_write_log( 'provider_result: ' . json_encode( $provider_result ) );

            dt_activity_insert([
                'action' => 'export',
                'object_type' => $type,
                'object_subtype' => 'non-pii',
                'meta_key' => 'provider',
                'meta_value' => $provider,
                'object_note' => 'disciple-tools-data-reporting'
            ]);

            if ( is_bool( $provider_result ) ) {
                return [
                    'success' => $provider_result,
                ];
            }
            return $provider_result;
        }
    }

    /**
     * Run export to fetch data, send to provider, and log results
     * @param $config_key - ID of the saved configuration
     * @param $config - Saved configuration
     * @param $type - Data type to be exported
     * @param $provider_details
     * @return array|void|WP_Error
     */
    public static function run_export( $config_key, $config, $type, $provider_details ) {
        $provider = isset( $config['provider'] ) ? $config['provider'] : 'api';
        $flatten = false;
        $log_messages = array();
        if ( $provider == 'api' && empty( $config['url'] ) ) {
            $log_messages[] = [ 'message' => 'Configuration is missing endpoint URL' ];
        }
        if ( $provider != 'api' ) {
            if ( !empty( $provider_details ) && isset( $provider_details['flatten'] ) ) {
                $flatten = boolval( $provider_details['flatten'] );
            }
        }
        $log_messages[] = [ 'message' => 'Exporting to ' . $config['name'] ];

        // Run export based on the type of data requested
        $log_messages[] = [ 'message' => 'Fetching data...' ];
        [ $columns, $rows, $total ] = self::get_data( $type, $config_key, $flatten );
        $row_count = isset( $rows ) ? count( $rows ) : 0;
        $log_messages[] = [ 'message' => 'Exporting ' . $row_count . ' items from a total of ' . $total . '.' ];
        $log_messages[] = [ 'message' => 'Sending data to provider...' ];

        // Send data to provider
        $export_result = self::send_data_to_provider( $columns, $rows, $type, $config );
        // dt_write_log( json_encode( $export_result ) );

        // Merge log messages from above and from provider
        $export_result['messages'] = array_merge( $log_messages, isset( $export_result['messages'] ) ? $export_result['messages'] : [] );

        // If provider was successful, store the last value exported
        $success = isset( $export_result['success'] ) ? $export_result['success'] : boolval( $export_result );
        if ( $success && !empty( $rows ) ) {
            $last_item = array_slice( $rows, -1 )[0];
            self::set_last_exported_value( $type, $config_key, $last_item );
        }

        // Store the result of this export for debugging later
        self::store_export_logs( $type, $config_key, $export_result );

        return $export_result;
    }

    /**
     * Run all exports that are configured to be run automatically
     */
    public static function run_scheduled_exports() {
        dt_write_log( 'Running DT Data Reporting CRON task' );

        $configurations = self::get_configs();
        $providers = apply_filters( 'dt_data_reporting_providers', array() );

        // loop over configurations
        foreach ($configurations as $config_key => $config) {

            $provider = isset( $config['provider'] ) ? $config['provider'] : 'api';
            $provider_details = $provider != 'api' ? $providers[$provider] : array();
            $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];

            // loop over each data type in each config
            foreach ( $type_configs as $type => $type_config ) {
                $schedule = isset( $type_config ) && isset( $type_config['schedule'] ) ? $type_config['schedule'] : '';
                // if scheduled export enabled, run export (get data, send to provider)
                if ( $schedule == 'daily') {
                    self::run_export( $config_key, $config, $type, $provider_details );
                }
            }
        }
    }

    /**
     * @param $fields
     * @return array
     */
    private static function build_columns( $fields, $type ): array
    {
        $columns = array();
        array_push($columns, array(
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

        foreach ($fields as $field_key => $field) {
            // skip if field is hidden
            if (isset( $field['hidden'] ) && $field['hidden'] == true && !self::is_included_hidden_field( $type, $field_key ) ) {
                continue;
            }
            // skip if in list of excluded fields
            if (self::is_excluded_field( $type, $field_key ) ) {
                continue;
            }

            // skip communication_channel fields since they are all PII
            if ($field['type'] == 'communication_channel') {
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
                case 'tags':
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
            if ($field_key == 'last_modified') {
                $column['type'] = 'date';
                $column['bq_type'] = 'TIMESTAMP';
                $column['bq_mode'] = 'NULLABLE';

            }
            array_push( $columns, $column );
        }
        array_push($columns, array(
            'key' => 'site',
            'name' => 'Site',
            'type' => 'text',
            'bq_type' => 'STRING',
            'bq_mode' => 'NULLABLE',
        ));
        return $columns;
    }
}
