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
$wordpress_root_path = preg_replace( '/wp-content(?!.*wp-content).*/', '', __DIR__ );
require_once( $wordpress_root_path . 'wp-load.php' );
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
$data_type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
$root_type = str_replace( '_activity', 's', $data_type );
$is_activity = $root_type !== $data_type;
$data_filename = strcmp( $data_type, '' ) !== 0 ? $data_type : 'data';

if ( $is_activity ) {
    [ $columns, $items ] = DT_Data_Reporting_Tools::get_post_activity( $root_type );
    $columns = array_map( function ( $column ) { return $column['name'];
    }, $columns );
} else {
    [ $columns, $items ] = DT_Data_Reporting_Tools::get_posts( $root_type, true );
    $columns = array_map( function ( $column ) { return $column['name'];
    }, $columns );
}


// output headers so that the file is downloaded rather than displayed
header( 'Content-Type: text/csv; charset=utf-8' );
header( 'Content-Disposition: attachment; filename='.$data_filename.'.csv' );

// create a file pointer connected to the output stream
$output = fopen( 'php://output', 'w' );

// output the column headings
fputcsv( $output, $columns );

// fetch the data


// loop over the rows, outputting them
foreach ($items as $row ) {
    fputcsv( $output, $row );
}

dt_activity_insert([
    'action' => 'export',
    'object_type' => $data_type,
    'object_subtype' => 'non-pii',
    'meta_key' => 'file',
    'meta_value' => 'json',
    'object_note' => 'disciple-tools-data-reporting::csv'
]);
