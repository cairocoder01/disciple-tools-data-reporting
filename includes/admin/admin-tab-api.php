<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_API
{
    public $type = 'contacts';
    public $config;

    public function __construct( $token, $type, $config ) {
        $this->token = $token;
        $this->type = $type;
        $this->config = DT_Data_Reporting_Tools::get_config_by_key( $config );
    }

    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        $settings_link = 'admin.php?page='.$this->token.'&tab=settings';
        if ( empty( $this->config ) ) {
            echo "<p>Configuration could not be found. Please update in <a href='$settings_link'>Settings</a></p>";
        } else {
            $providers = apply_filters( 'dt_data_reporting_providers', array() );
            $provider = isset( $this->config['provider'] ) ? $this->config['provider'] : 'api';
            $flatten = false;
            echo '<ul class="api-log">';
            if ( $provider == 'api' && empty( $this->config['url'] ) ) {
                echo '<li>Configuration is missing endpoint URL</li>';
            }
            if ( $provider != 'api' ) {
                $provider_details = $providers[$provider];
                if ( !empty($provider_details) && isset($provider_details['flatten']) ) {
                    $flatten = boolval($provider_details['flatten']);
                }
            }
            echo '<li>Exporting to ' . $this->config['name'] . '</li>';

            switch ($this->type) {
                case 'contact_activity':
                    echo '<li>Fetching data...</li>';
                    $filter = isset( $this->config['contacts_filter'] ) ? $this->config['contacts_filter'] : null;
                    [ $columns, $rows, $total ] = DT_Data_Reporting_Tools::get_contact_activity( $flatten, $filter );
                break;
                case 'contacts':
                default:
                    echo '<li>Fetching data...</li>';
                    $filter = isset( $this->config['contacts_filter'] ) ? $this->config['contacts_filter'] : null;
                    [ $columns, $rows, $total ] = DT_Data_Reporting_Tools::get_contacts( $flatten, $filter );
                break;
            }

            echo '<li>Found ' . $total . ' items.</li>';

            echo '<li>Sending data to provider...</li>';
            $this->export_data( $columns, $rows, $this->type, $this->config );
            echo '<li>Done exporting.</li>';

            echo '</ul>';
        }
    }
    public function export_data( $columns, $rows, $type, $config ) {
        $provider = isset( $config['provider'] ) ? $config['provider'] : 'api';

        if ( $provider == 'api' ) {
            $args = array(
            'method' => 'POST',
            'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => json_encode(array(
                'columns' => $columns,
                'items' => $rows,
                'type' => $type,
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
                echo "<li class='error'>Error: $error_message</li>";
            } else {
                // Success
                $status_code = wp_remote_retrieve_response_code( $result );
                if ($status_code !== 200) {
                    echo "<li class='error'>Error: Status Code $status_code</li>";
                } else {
                    echo "<li class='success'>Success</li>";
                }
                // $result_body = json_decode($result['body']);
                echo "<li><pre><code>" . $result['body'] . "</code></pre>";
            }
        } else {
            do_action( "dt_data_reporting_export_provider_$provider", $columns, $rows, $type, $config );
        }
    }
}
