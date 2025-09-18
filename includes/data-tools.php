<?php


class DT_Data_Reporting_Tools
{
    // limit filtering to only those that are manually implemented for activity
    private static $filter_fields = [ 'tags', 'sources', 'type' ];
    private static $supported_filters = [
        'sort' => true,
        'limit' => true,
        'offset' => true,
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
    public static function get_data( $data_type, $config_key, $flatten = false, $limit = null, $offset = null ) {
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
        if ( $offset ) {
            $filter['offset'] = $offset;
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
                if ( is_wp_error( $next_posts ) ) {
                    $error_message = $posts->get_error_message() ?? '';
                    throw new Exception( $error_message );
                }
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
            if ( in_array($filter_key, ['sort', 'limit', 'offset']) ) {
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
        if (isset($filter['offset'])) {
          $query .= "OFFSET %d ";
          $params[] = $filter['offset'];
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
            select orig.grid_id, orig.country_code, orig.name, a1.name as admin1_name
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
                            if ( !empty( $grid_loc['admin1_name'] ) ) {
                                return $grid_loc['country_code'] . "-" . $grid_loc['admin1_name'];
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

    public static function get_snapshots( string $post_type, int $post_id, string $period ) {
      // Get post creation date
      $post = DT_Posts::get_post( $post_type, $post_id, true, false, true );
      if (!$post) {
        throw new \Exception("Post not found");
      }

      $created_date = new DateTime($post['post_date']['formatted']);
      $current_date = new DateTime();
      $snapshots = [];

      // Validate period
      if (!in_array($period, ['year', 'quarter', 'month'])) {
        throw new \InvalidArgumentException('Invalid period');
      }

      // Get interval based on period
      switch ($period) {
        case 'year':
          $interval = 'P1Y';
          $date_format = 'Y';
          // Move to end of current year
          $current_date->modify('last day of December ' . $current_date->format('Y') . ' 23:59:59');
          break;
        case 'quarter':
          $interval = 'P3M';
          $date_format = 'Y-Q';
          // Move to end of current quarter
          $month = $current_date->format('n'); // month as number
          $quarter_end_month = ceil($month / 3) * 3; // last month of quarter
          $current_date->setDate($current_date->format('Y'), $quarter_end_month, 1);
          $current_date->modify('last day of this month 23:59:59');
          break;
        case 'month':
          $interval = 'P1M';
          $date_format = 'Y-m';
          // Move to end of current month
          $current_date->modify('last day of this month 23:59:59');
          break;
      }

      // Create interval object
      $interval = new DateInterval($interval);

      // Loop through periods from creation to current
      $period_end = clone $current_date;
      while ($period_end >= $created_date) {
        // Get snapshot for period end date
        dt_write_log( "Creating snapshot for " . $period_end->format('Y-m-d H:i:s') );
        $snapshot = self::get_snapshot( $post_type, $post_id, [
          'ts_start' => $period_end->getTimestamp(),
        ]);

        // Add to snapshots array with proper period format
        $key = $period_end->format($date_format);
        if ($period === 'quarter') {
          $quarter = ceil($period_end->format('n') / 3);
          $key = $period_end->format('Y') . '-Q' . $quarter;
        }
//        $snapshots[$key] = $snapshot;
        $snapshot['period'] = $key;
        $snapshots[] = $snapshot;

        // Move to previous period
        $period_end->sub($interval);
      }

      $flatten = false;
      $post_settings = apply_filters( "dt_get_post_type_settings", array(), $post_type );
      $fields = $post_settings["fields"];
      $base_url = self::get_current_site_base_url();
      $locations = self::get_location_data( $snapshots );

      // process each post
      foreach ($snapshots as $index => $result) {
        $post = array(
          'ID' => $result['ID'],
          'Created' => $result['post_date'],
        );

        // Theme v1.0.0 changes post_date to a proper date object we need to format
        if ( isset( $result['post_date']['timestamp'] ) ) {
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
        $post['period'] = $result['period'];

        $items[] = $post;
      }
      $columns = self::build_columns( $fields, $post_type );
      $columns[] = [
        'key' => 'period',
        'name' => 'Period',
      ];
      return array( $columns, $items, count( $items ) );
//      return $snapshots;
    }

    private static function apply_revert_updates( $post, $updates ) {

      foreach ($updates as $field => $update) {
        if ( is_array( $update ) ) {
          if ( isset( $update['values'] ) ) {
            // Initialize array if doesn't exist
            if (!isset($post[$field]) || !is_array($post[$field])) {
              $post[$field] = [];
            }

            foreach ($update['values'] as $value) {
              if (isset($value['delete']) && $value['delete']) {
                // Remove value from array
                $post[$field] = array_filter($post[$field], function ($existing) use ($value) {
                  return !isset($existing['value']) || $existing['value'] !== $value['value'];
                });
              } else {
                // Add value to array
                $post[$field][] = $value;
              }
            }
          }
        } else {
          $post[$field] = $update;
        }
      }

      return $post;
    }
  public static function get_snapshot( string $post_type, int $post_id, array $args = [] )
  {
//    $post_type = $post['post_type'];
//    $post_id = $post['ID'];

    // duplicate of DT_Posts::revert_post_activity_history without final code to save the updates
    // but just the first half that builds the reverted post, not the $post_updates to save the updates.
    // Some additions in second loop to directly update $post object are marked by comment: update post directly

    // BEGIN COPIED CODE (self replaced with DT_Posts)
    /**
     * Fetch all associated activities from current time to specified revert
     * date. Ensure most recent activities are first in line.
     */

    $args['result_order'] = 'DESC';
    $activities = self::list_revert_post_activity_history($post_type, $post_id, $args);

    /**
     * March back in time to revert date, adjusting fields accordingly.
     */

    $reverted_start_ts_id = $args['ts_start_id'] ?? 0;
    $reverted_start_ts_found = false;

    $reverted_updates = [];
    $post_type_fields = DT_Posts::get_post_field_settings($post_type, false);
    foreach ($activities ?? [] as &$activity) {
      dt_write_log(json_encode($activity));
      $activity_id = $activity->histid;
      $field_action = $activity->action;
      $field_type = $activity->field_type;
      $field_key = $activity->meta_key;
      $field_value = $activity->meta_value;
      $field_old_value = $activity->old_value;
      $field_note_raw = $activity->object_note_raw;
      $is_deleted = strtolower(trim($field_value)) == 'value_deleted';

      // Ensure to accommodate special case field types.
      if (in_array($field_action, ['connected to', 'disconnected from'])) {

        // Determine actual field key to be used.
        $field_setting = DT_Posts::get_post_field_settings_by_p2p($post_type_fields, $field_key, ($field_action == 'disconnected from') ? ['from', 'to', 'any'] : ['to', 'from', 'any']);
        if (!empty($field_setting)) {
          $field_key = $field_setting['key'];
          $field_type = $field_action;

        } else {
          $field_key = null;
          $field_type = null;
        }
      } elseif ((empty($field_type) || $field_type === 'communication_channel') && substr($field_key, 0, strlen('contact_')) == 'contact_') {
        $field_type = 'communication_channel';

        // Determine actual field key.
        $determined_field_key = null;
        foreach (DT_Posts::get_field_settings_by_type($post_type, $field_type) ?? [] as $potential_field_key) {
          if (strpos($field_key, $potential_field_key) !== false) {
            $determined_field_key = $potential_field_key;
          }
        }
        $field_key = $determined_field_key ?? substr($field_key, 0, strpos($field_key, '_', strlen('contact_')));

        // Void if key is empty.
        if (empty($field_key)) {
          $field_key = null;
          $field_type = null;
        }
      } elseif ($field_type === 'link') {
        foreach (DT_Posts::get_field_settings_by_type($post_type, $field_type) ?? [] as $link_field) {
          if (strpos($field_key, $link_field) !== false) {
            $field_key = $link_field;
          }
        }
      }

      /**
       * Ensure processing is halted once target start activity id has
       * been found.
       */

      if ($reverted_start_ts_id === $activity_id) {
        $reverted_start_ts_found = true;
      }

      if (!$reverted_start_ts_found) {

        // If needed, prepare reverted updates array element.
        if (!empty($field_key) && !empty($field_type) && !isset($reverted_updates[$field_key])) {
          $reverted_updates[$field_key] = [
            'field_type' => $field_type,
            'values' => []
          ];
        }

        /**
         * As we walk back in time, need to operate in the inverse; so, delete is
         * actually an add and add, is actually, delete!
         * Also, ensure inverse logic is not carried out once we've reached our
         * specified revert start point.
         */

        switch ($field_type) {
          case 'connected to':
          case 'disconnected from':
            $is_deleted = strtolower(trim($field_action)) == 'disconnected from';

            $reverted_updates[$field_key]['values'][$field_value] = [
              'value' => $field_value,
              'keep' => $is_deleted
            ];
            break;
          case 'tags':
          case 'date':
          case 'datetime':
          case 'link':
          case 'location':
          case 'multi_select':
          case 'location_meta':
          case 'communication_channel':

            // Capture any additional metadata, by field type.
            $meta = [];
            if ($field_type === 'communication_channel') {
              $meta = [
                'meta_key' => $activity->meta_key,
                'value_key_prefix' => $activity->meta_key . '-'
              ];
            } elseif ($field_type === 'link') {
              $meta = [
                'meta_id' => $activity->meta_id,
                'value_key_prefix' => $activity->meta_id . '-'
              ];
            } elseif ($field_type === 'location') {
              $meta = [
                'meta_id' => $activity->meta_id,
                'value_key_prefix' => $activity->meta_id . '-'
              ];
            }

            // Proceed with capturing reverted updates.
            $value = $is_deleted ? $field_old_value : $field_value;
            $reverted_updates[$field_key]['values'][($meta['value_key_prefix'] ?? '') . $value] = [
              'value' => $value,
              'keep' => $is_deleted,
              'note' => $field_note_raw,
              'meta' => $meta
            ];

            // Ensure any detected old values are reinstated!
            if (!$is_deleted && !empty($field_old_value)) {
              unset($reverted_updates[$field_key]['values'][($meta['value_key_prefix'] ?? '') . $field_value]);

              $reverted_updates[$field_key]['values'][($meta['value_key_prefix'] ?? '') . $field_old_value] = [
                'value' => $field_old_value,
                'keep' => true,
                'note' => $field_note_raw,
                'meta' => $meta
              ];
            }
            break;
          case 'text':
          case 'number':
          case 'boolean':
          case 'textarea':
          case 'key_select':
          case 'user_select':
            $reverted_updates[$field_key]['values'][0] = $field_old_value;
            break;
        }
      }
    }

    /**
     * Package revert findings ahead of final post update; ensuring to remove any
     * field values not present within reverted updates.
     */

    $post_updates = [];
    $post = DT_Posts::get_post($post_type, $post_id, false);
    foreach ($reverted_updates as $field_key => $reverted) {
      switch ($reverted['field_type']) {
        case 'connected to':
        case 'disconnected from':
          $values = [];
          foreach ($reverted['values'] as $revert_key => $revert_obj) {

            // Keep existing values or add if needed.
            if ($revert_obj['keep']) {
              $found_existing_option = false;
              if (isset($post[$field_key]) && is_array($post[$field_key])) {
                foreach ($post[$field_key] as $option) {
                  if ($revert_key == $option['ID']) {
                    $found_existing_option = true;
                  }
                }
              }

              if (!$found_existing_option) {
                $values[] = [
                  'value' => $revert_obj['value']
                ];
                // update post directly
                $post[$field_key][] = [
                  'ID' => $revert_obj['value']
                ];
              }
            } elseif (isset($post[$field_key]) && is_array($post[$field_key])) {

              // Remove any flagged existing values.
              foreach ($post[$field_key] as $option) {
                dt_write_log(json_encode($option));
                $id = $option['ID'];
                if ($revert_key == $id) {
                  $values[] = [
                    'value' => $id,
                    'delete' => true
                  ];
                }
              }
              // update post directly
              $post[$field_key] = array_filter( $post[$field_key], function ($option) use ($revert_key) {
                return $option['ID'] !== $revert_key;
              });
            }
          }

          // Package any available values to be updated.
          if (!empty($values)) {
            $post_updates[$field_key] = [
              'values' => $values
            ];
          }
          break;
        case 'tags':
        case 'link':
        case 'location':
        case 'multi_select':
        case 'location_meta':
        case 'communication_channel':
          $values = [];
          foreach ($reverted['values'] as $revert_key => $revert_obj) {

            // Remove any detected revert value key prefixes.
            if (isset($revert_obj['meta'], $revert_obj['meta']['value_key_prefix']) && strpos($revert_key, $revert_obj['meta']['value_key_prefix']) !== false) {
              $revert_key = substr($revert_key, strlen($revert_obj['meta']['value_key_prefix']));
            }

            // Keep existing values or add if needed.
            if ($revert_obj['keep']) {
              $found_existing_option = false;
              if (isset($post[$field_key]) && is_array($post[$field_key])) {
                foreach ($post[$field_key] as $option) {

                  // Determine id to be used, based on field type
                  if ($reverted['field_type'] == 'location') {
                    $id = $option['id'];

                  } elseif ($reverted['field_type'] == 'location_meta') {
                    $id = $option['grid_meta_id'];

                  } elseif ($reverted['field_type'] == 'communication_channel') {
                    $id = $option['value'];

                  } elseif ($reverted['field_type'] == 'link') {
                    $id = $option['value'];

                  } else {
                    $id = $option;
                  }

                  if ($revert_key == $id) {
                    $found_existing_option = true;
                  }
                }
              }

              if (!$found_existing_option) {

                // Structure value accordingly based on field type.
                if ($reverted['field_type'] == 'location_meta') {

                  /**
                   * Assuming suitable mapping APIs are available, execute a lookup query, based on
                   * specified location. Construct update value package based on returned hits.
                   */

                  if (!empty($revert_obj['note'])) {
                    $note = $revert_obj['note'];

                    if (class_exists('Disciple_Tools_Google_Geocode_API') && !empty(Disciple_Tools_Google_Geocode_API::get_key()) && Disciple_Tools_Google_Geocode_API::get_key()) {
                      $location = Disciple_Tools_Google_Geocode_API::query_google_api($note, 'coordinates_only');
                      if (!empty($location)) {
                        $values[] = [
                          'lng' => $location['lng'],
                          'lat' => $location['lat'],
                          'label' => $note
                        ];
                        // update post directly
                        $post[$field_key][] = [
                          'lng' => $location['lng'],
                          'lat' => $location['lat'],
                          'label' => $note
                        ];
                      }
                    } elseif (class_exists('DT_Mapbox_API') && !empty(DT_Mapbox_API::get_key()) && DT_Mapbox_API::get_key()) {
                      $location = DT_Mapbox_API::lookup($note);
                      if (!empty($location)) {
                        $values[] = [
                          'lng' => DT_Mapbox_API::parse_raw_result($location, 'lng', true),
                          'lat' => DT_Mapbox_API::parse_raw_result($location, 'lat', true),
                          'label' => DT_Mapbox_API::parse_raw_result($location, 'place_name', true)
                        ];
                        // update post directly
                        $post[$field_key][] = [
                          'lng' => DT_Mapbox_API::parse_raw_result($location, 'lng', true),
                          'lat' => DT_Mapbox_API::parse_raw_result($location, 'lat', true),
                          'label' => DT_Mapbox_API::parse_raw_result($location, 'place_name', true)
                        ];
                      }
                    }
                  }
                } elseif ($reverted['field_type'] == 'communication_channel') {
                  $values[] = [
                    'key' => $revert_obj['meta']['meta_key'] ?? null,
                    'value' => $revert_obj['value']
                  ];
                  // update post directly
                  $post[$field_key][] = [
                    'key' => $revert_obj['meta']['meta_key'] ?? null,
                    'value' => $revert_obj['value']
                  ];
                } elseif ($reverted['field_type'] == 'link') {
                  $values[] = [
                    'type' => 'default',
                    'value' => $revert_obj['value']
                  ];
                  // update post directly
                  $post[$field_key][] = [
                    'type' => 'default',
                    'value' => $revert_obj['value']
                  ];
                } else {
                  $values[] = [
                    'value' => $revert_obj['value']
                  ];
                  // update post directly
                  $post[$field_key][] = [
                    'value' => $revert_obj['value']
                  ];
                }
              }
            } elseif (isset($post[$field_key]) && is_array($post[$field_key])) {

              // Remove any flagged existing values.
              foreach ($post[$field_key] as $arr_key => $option) {

                // Determine id to be used, based on field type
                if ($reverted['field_type'] == 'location') {
                  $id = $option['id'];

                } elseif ($reverted['field_type'] == 'location_meta') {
                  $id = $option['grid_meta_id'];

                } elseif ($reverted['field_type'] == 'communication_channel') {

                  // Force a match if key is found.
                  if ($option['key'] === ($revert_obj['meta']['meta_key'] ?? '')) {
                    $id = $revert_key;
                  } else {
                    $id = '';
                  }
                } elseif ($reverted['field_type'] == 'link') {

                  // Force a match if key is found.
                  if ($option['meta_id'] === ($revert_obj['meta']['meta_id'] ?? '')) {
                    $id = $revert_key;
                  } else {
                    $id = '';
                  }
                } else {
                  $id = $option;
                }

                if ($revert_key == $id) {

                  // Determine correct value label to be used.
                  if ($reverted['field_type'] == 'location_meta') {
                    $key = 'grid_meta_id';

                  } elseif ($reverted['field_type'] == 'communication_channel') {
                    $key = 'key';
                    $id = $revert_obj['meta']['meta_key'] ?? $revert_key;
                  } elseif ($reverted['field_type'] == 'link') {
                    $key = 'meta_id';
                    $id = $revert_obj['meta']['meta_id'] ?? $revert_key;
                  } else {
                    $key = 'value';
                  }

                  // Package....
                  $values[] = [
                    $key => $id,
                    'delete' => true
                  ];
                  // update post directly
                  unset($post[$field_key][$arr_key]);
                }
              }
              // update post directly
              $post[$field_key] = array_filter( $post[$field_key], function ($option) use ($revert_key) {
                return isset( $option );
              });
            }
          }

          // Package any available values to be updated, accordingly; by field type.
          if (!empty($values)) {
            if ($reverted['field_type'] == 'communication_channel') {
              $post_updates[$field_key] = $values;
            } else {
              $post_updates[$field_key] = [
                'values' => $values
              ];
            }
          }
          break;
        case 'date':
        case 'datetime':
          $revert_obj = array_values($reverted['values'])[0] ?? null;
          $post_updates[$field_key] = (!empty($revert_obj) && $revert_obj['keep']) ? $revert_obj['value'] : '';
          // update post directly
          $post[$field_key] = (!empty($revert_obj) && $revert_obj['keep']) ? $revert_obj['value'] : '';
          break;
        case 'number':
          $number_update_allowed = true;

          // Ensure to adhere with any min/max bounds, to avoid exceptions!
          $number = !empty($reverted['values'][0]) ? $reverted['values'][0] : 0;
          if (isset($post_type_fields[$field_key], $post_type_fields[$field_key]['min_option']) && $post_type_fields[$field_key]['min_option'] > $number) {
            $number_update_allowed = false;
          }
          if (isset($post_type_fields[$field_key], $post_type_fields[$field_key]['max_option']) && $post_type_fields[$field_key]['max_option'] < $number) {
            $number_update_allowed = false;
          }

          // Only update if number format is valid.
          if ($number_update_allowed) {
            $post_updates[$field_key] = $number;
            // update post directly
            $post[$field_key] = $number;
          }
          break;
        case 'user_select':
          $user_select_value = $reverted['values'][0];
          if ($user_select_value != 'user-') {
            $post_updates[$field_key] = $user_select_value;
            // update post directly
            $post[$field_key] = $user_select_value;
          } else {
            $post_updates[$field_key] = '';
            // update post directly
            $post[$field_key] = '';
          }
          break;
        case 'text':
        case 'boolean':
        case 'textarea':
        case 'key_select':
          $post_updates[$field_key] = $reverted['values'][0] ?? '';
          // update post directly
          $post[$field_key] = $reverted['values'][0] ?? '';
          break;
      }
    }
    // END COPIED CODE
    return $post;
//    return self::apply_revert_updates( $post, $post_updates );
//    return $post_updates;
  }

  /**
   * @param string $post_type
   * @param int $post_id
   * @param array $args
   *
   * @return array|null|object|WP_Error
   * @note Copied directly from DT_Posts::revert_post_activity_history since it's private, just replacing self with DT_Posts
   */
  private static function list_revert_post_activity_history( string $post_type, int $post_id, array $args = [] ) {
    global $wpdb;

    // Determine key query parameters
    $supported_actions         = ( ! empty( $args['actions'] ) ) ? $args['actions'] : [
      'field_update',
      'connected to',
      'disconnected from'
    ];
    $supported_actions_sql     = dt_array_to_sql( $supported_actions );
    $supported_field_types     = ( ! empty( $args['field_types'] ) ) ? $args['field_types'] : [
      'connection',
      'user_select',
      'multi_select',
      'tags',
      'link',
      'location',
      'location_meta',
      'key_select',
      'date',
      'datetime',
      'boolean',
      'communication_channel',
      'text',
      'textarea',
      'number',
      'connection to',
      'connection from',
      ''
    ];
    $supported_field_types_sql = dt_array_to_sql( $supported_field_types );
    $ts_start                  = ( ! empty( $args['ts_start'] ) ) ? $args['ts_start'] : 0;
    $ts_end                    = ( ! empty( $args['ts_end'] ) ) ? $args['ts_end'] : time();
    $result_order              = esc_sql( ( ! empty( $args['result_order'] ) ) ? $args['result_order'] : 'DESC' );
    $extra_meta                = ! empty( $args['extra_meta'] ) && $args['extra_meta'];

    // Fetch post activity history
    // phpcs:disable
    // WordPress.WP.PreparedSQL.NotPrepared
    $sql = $wpdb->prepare(
      "SELECT
                *
            FROM
                `$wpdb->dt_activity_log`
            WHERE
                `object_type` = %s
                AND `object_id` = %s
                AND `action` IN ( $supported_actions_sql )
                AND `field_type` IN ( $supported_field_types_sql )
                AND `hist_time` BETWEEN %d AND %d
            ORDER BY hist_time $result_order",
      $post_type,
      $post_id,
      $ts_start,
      $ts_end
    );
    dt_write_log( $sql );
    $activities = $wpdb->get_results($sql);
    //@phpcs:enable

    // Format activity message
    $post_settings = DT_Posts::get_post_settings( $post_type );
    foreach ( $activities as &$activity ) {
      $activity->object_note_raw = $activity->object_note;
      $activity->object_note = sanitize_text_field( DT_Posts::format_activity_message( $activity, $post_settings ) );
    }

    // Determine if extra metadata has been requested
    if ( $extra_meta ) {
      foreach ( $activities as &$activity ) {
        if ( isset( $activity->user_id ) && $activity->user_id > 0 ) {
          $user = get_user_by( 'id', $activity->user_id );
          if ( $user ) {
            $activity->name     = sanitize_text_field( $user->display_name );
            $activity->gravatar = get_avatar_url( $user->ID, [ 'size' => '16', 'scheme' => 'https' ] );
          }
        }
      }
    }

    return $activities;
  }
}
