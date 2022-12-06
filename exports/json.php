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

$limit = isset( $_GET['limit'] ) ? sanitize_key( wp_unslash( $_GET['limit'] ) ) : null;
$offset = isset( $_GET['offset'] ) ? sanitize_key( wp_unslash( $_GET['offset'] ) ) : null;
$data_type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
$root_type = str_replace( '_activity', 's', $data_type );
$is_activity = $root_type !== $data_type;
$data_filename = strcmp( $data_type, '' ) !== 0 ? $data_type : 'data';

$filter = [];
if ( isset( $limit ) && $limit > 0 ) {
    $filter['limit'] = $limit;
}
if ( isset( $offset ) && $offset > 0 ) {
    $filter['offset'] = $offset;
}

if ( $is_activity ) {
    [ $columns, $items ] = DT_Data_Reporting_Tools::get_post_activity( $root_type, $filter );
    $columns = array_map( function ( $column ) { return $column['name'];
    }, $columns );
} else {
    [ $columns, $items ] = DT_Data_Reporting_Tools::get_posts( $root_type, false, $filter );
    $columns = array_map( function ( $column ) { return $column['name'];
    }, $columns );
}


// output headers so that the file is downloaded rather than displayed
header( 'Content-Type: text/json; charset=utf-8' );
header( 'Content-Disposition: attachment; filename='.$data_filename.'.json' );

// create a file pointer connected to the output stream
$output = fopen( 'php://output', 'w' );

// loop over the rows, outputting them
foreach ( $items as $row ) {
    fwrite( $output, json_encode( $row ).PHP_EOL );
}
fclose( $output );

dt_activity_insert([
    'action' => 'export',
    'object_type' => $data_type,
    'object_subtype' => 'non-pii',
    'meta_key' => 'file',
    'meta_value' => 'json',
    'object_note' => 'disciple-tools-data-reporting'
]);
