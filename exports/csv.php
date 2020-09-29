<?php

if ( defined( 'ABSPATH' ) ) {
    return; // return unless accessed directly
}
if ( ! function_exists( 'dt_write_log' ) ) {
    /**
     * A function to assist development only.
     * This function allows you to post a string, array, or object to the WP_DEBUG log.
     *
     * @param $log
     */
    function dt_write_log( $log ) {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}
// @codingStandardsIgnoreLine
require( $_SERVER[ 'DOCUMENT_ROOT' ] . '/wp-load.php' ); // loads the wp framework when called
require_once( plugin_dir_path( __FILE__ ) . '../includes/data-tools.php' );

function get_post_activity( $post_type ) {
    global $wpdb;
    $post_settings = apply_filters( "dt_get_post_type_settings", array(), $post_type );
    $fields = $post_settings["fields"];
    $hidden_fields = array();
    foreach ( $fields as $field_key => $field ){
        if ( isset( $field["hidden"] ) && $field["hidden"] === true ){
            $hidden_fields[] = $field_key;
        }
    }
    $hidden_keys = dt_array_to_sql( $hidden_fields );
    // phpcs:disable
    // WordPress.WP.PreparedSQL.NotPrepared
    $activity = $wpdb->get_results( $wpdb->prepare(
        "SELECT
                *
            FROM
                `$wpdb->dt_activity_log`
            WHERE
                `object_type` = %s
                AND meta_key NOT IN ( $hidden_keys )
            ORDER BY hist_time DESC",
        $post_type
    ) );
    //@phpcs:enable
    $activity_simple = array();
    foreach ( $activity as $a ) {
        $a->object_note = DT_Posts::format_activity_message( $a, $post_settings );
        if ( !empty( $a->object_note ) ){
            $activity_simple[] = array(
                "meta_key" => $a->meta_key,
                "object_id" => $a->object_id,
                "user_id" => $a->user_id,
                "object_note" => $a->object_note,
                "hist_time" => $a->hist_time,
                "meta_id" => $a->meta_id,
                "histid" => $a->histid,
                "action" => $a->action,
            );
        }
    }

//    $paged = array_slice( $activity_simple, $args["offset"] ?? 0, $args["number"] ?? 1000 );
    return array(
        "activity" => $activity_simple,
        "total" => sizeof( $activity_simple )
    );
}
$data_type = isset( $_GET['type'] ) ? esc_url_raw( wp_unslash( $_GET['type'] ) ) : '';
switch ( $data_type ) {
    case 'contacts':
    default:
        [ $columns, $items ] = DT_Data_Reporting_Tools::get_contacts( true );
        $columns = array_map( function ( $column ) { return $column['name'];
        }, $columns );
        break;
    case 'contactactivity':
        [ $columns, $items ] = DT_Data_Reporting_Tools::get_contact_activity( true );
        $columns = array_map( function ( $column ) { return $column['name'];
        }, $columns );
        break;
    case 'cities':
        $places = array();
        /*$results = $wpdb->get_results("
            SELECT * FROM dt_geonames WHERE feature_code LIKE 'PP%' AND feature_class = 'P' AND population > 100000",
            ARRAY_A);
        foreach ($results as $index => $result ) {
            $places[] = [
                'geonamid' => $result['geonameid'],
                'name' => $result['name'],
                'population' => $result['population'],
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
            ];
            //    dt_write_log($value);
        }*/
        $columns = array( 'geonameid', 'name', 'population', 'latitude', 'longitude' );
        break;

    case 'list':
        $places = array();
        /*$results = $wpdb->get_results("
            SELECT meta_value
            FROM $wpdb->postmeta
            WHERE meta_key = 'zume_raw_record'
              AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'groups')",
            ARRAY_A);
        foreach ($results as $index => $result ) {
            $value = maybe_unserialize( $result['meta_value'] );
            $places[$index] = [
                'id' => $index,
                'name' => $value['group_name'],
                'address' => $value['address'],
                'type' => 'church',
                'lat' => $value['lat'],
                'lng' => $value['lng'],
            ];
            //    dt_write_log($value);
        }*/
        $columns = array( 'id', 'name', 'address', 'type', 'lat', 'lng' );
        break;
}


// output headers so that the file is downloaded rather than displayed
header( 'Content-Type: text/csv; charset=utf-8' );
header( 'Content-Disposition: attachment; filename=data.csv' );

// create a file pointer connected to the output stream
$output = fopen( 'php://output', 'w' );

// output the column headings
fputcsv( $output, $columns );

// fetch the data


// loop over the rows, outputting them
foreach ($items as $row ) {
    fputcsv( $output, $row );
}
