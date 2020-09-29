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
        $this->config_key = $config;
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
            // Fetch details for this provider
            $providers = apply_filters( 'dt_data_reporting_providers', array() );
            $provider = isset( $this->config['provider'] ) ? $this->config['provider'] : 'api';
            $log_messages = array();

            $flatten = false;
            echo '<ul class="api-log">';

            if ( $provider == 'api' && empty( $this->config['url'] ) ) {
                $log_messages[] = [ 'message' => 'Configuration is missing endpoint URL' ];
            }
            if ( $provider != 'api' ) {
                $provider_details = $providers[$provider];
                if ( !empty($provider_details) && isset($provider_details['flatten']) ) {
                    $flatten = boolval($provider_details['flatten']);
                }
            }
            $log_messages[] = [ 'message' => 'Exporting to ' . $this->config['name'] ];

            // Run export based on the type of data requested
            $log_messages[] = [ 'message' => 'Fetching data...' ];
            [ $columns, $rows, $total ] = DT_Data_Reporting_Tools::get_data( $this->type, $this->config_key, $flatten );
            $log_messages[] = [ 'message' => 'Exporting ' . count($rows) . ' items from a total of ' . $total . '.' ];
            $log_messages[] = [ 'message' => 'Sending data to provider...' ];

            // Send data to provider
            $export_result = DT_Data_Reporting_Tools::export_data( $columns, $rows, $this->type, $this->config );

            // Print out log messages from provider
            $export_result['messages'] = array_merge($log_messages, isset($export_result['messages']) ? $export_result['messages'] : []);
            if ( isset($export_result['messages']) ) {
                foreach ( $export_result['messages'] as $message ) {
                    $message_type = isset($message['type']) ? $message['type'] : '';
                    $content = isset($message['message']) ? $message['message'] : '';
                    echo "<li class='$message_type'>$content</li>";
                }
            }
            $success = $export_result['success'];

            // If provider was successful, store the last value exported
            if ( $success && !empty($rows) ) {
              $last_item = array_slice($rows, -1)[0];
              DT_Data_Reporting_Tools::set_last_exported_value($this->type, $this->config_key, $last_item);
            }

            // Store the result of this export for debugging later
            DT_Data_Reporting_Tools::store_export_logs($this->type, $this->config_key, $export_result);

            echo '<li>Done exporting.</li>';
            echo '</ul>';
        }
    }
}
