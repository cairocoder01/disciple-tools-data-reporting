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
            echo "<p>Configuration could not be found. Please update in <a href='" . esc_attr( $settings_link ) . "'>Settings</a></p>";
        } else {
            // Fetch details for this provider
            $providers = apply_filters( 'dt_data_reporting_providers', array() );
            $provider = isset( $this->config['provider'] ) ? $this->config['provider'] : 'api';
            $provider_details = $provider != 'api' ? $providers[$provider] : array();

            echo '<ul class="api-log">';

            $export_result = DT_Data_Reporting_Tools::run_export( $this->config_key, $this->config, $this->type, $provider_details );

            // Print out log messages from export process
            if ( isset( $export_result['messages'] ) ) {
                $allowed_html = array(
                    'a' => array(
                        'href' => array(),
                        'title' => array()
                    ),
                );
                foreach ( $export_result['messages'] as $message ) {
                    $message_type = isset( $message['type'] ) ? $message['type'] : '';
                    $content = isset( $message['message'] ) ? $message['message'] : '';
                    echo "<li class='" . esc_attr( $message_type ) . "'>" . wp_kses( $content, $allowed_html ) . '</li>';
                }
            }

            echo '<li>Done exporting.</li>';
            echo '</ul>';
        }
    }
}
