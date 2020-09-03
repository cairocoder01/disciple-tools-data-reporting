<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_API
{
    public $type = 'contacts';

    public function __construct( $token, $type )
    {
        $this->token = $token;
        $this->type = $type;
        require_once( plugin_dir_path( __FILE__ ) . '../data-tools.php' );
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
        $configurations = json_decode( get_option( "dt_data_reporting_configurations", "[]" ), true );

        $configurations = apply_filters('dt_data_reporting_configurations', $configurations);

        // Filter out disabled configurations
        $configurations = array_filter($configurations, function ($config) {
          return isset($config['active']) && $config['active'] == 1;
        });

        $settings_link = 'admin.php?page='.$this->token.'&tab=settings';
        if ( empty( $configurations ) ) {
            echo "<p>No endpoints configured. Please update in <a href='$settings_link'>Settings</a></p>";
        } else {

            echo '<ul>';
            foreach( $configurations as $config ) {
              if ( empty( $config['url'] ) ) {
                echo '<li>Configuration is missing endpoint URL</li>';
                continue;
              }
              echo '<li>Exporting to ' . $config['url'] . '</li>';

              switch ($this->type) {
                /*case 'contact_activity':
                    [$columns, $rows] = DT_Data_Reporting_Tools::get_contact_activity(false);
                    $this->export_data($columns, $rows);
                    break;*/
                case 'contacts':
                default:
                  echo '<li>Fetching data...</li>';
                  $filter = isset( $config['contacts_filter'] ) ? $config['contacts_filter'] : null;
                  [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, $filter);
                  echo '<li>Found ' . sizeof($rows) . ' contacts.</li>';
                  $this->export_data($columns, $rows, $this->type, $config);
                  break;
              }
            }
            echo '</ul>';
        }
    }
    public function export_data($columns, $rows, $type, $config ) {

        echo '<li>Sending data to endpoint...</li>';
        $args = [
          'method' => 'POST',
          'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8'
          ),
          'body' => json_encode([
            'columns' => $columns,
            'items' => $rows,
            'type' => $type,
          ]),
        ];

        // Add auth token if it is part of the config
        if ( isset( $config['token'] ) ) {
          $args['headers']['Authorization'] = 'Bearer ' . $config['token'];
        }

        // POST the data to the endpoint
        $result = wp_remote_post($config['url'], $args);

        if (is_wp_error($result)) {
          // Handle endpoint error
          $error_message = $result->get_error_message() ?? '';
          dt_write_log($error_message);
          echo "<li>Error: $error_message</li>";
        } else {
          // Success
          $status_code = wp_remote_retrieve_response_code( $result );
          if ( $status_code !== 200 ) {
            echo '<li>Status: ' . $status_code . '</li>';
          }
//            $result_body = json_decode($result['body']);
          echo "<li><pre><code>" . $result['body'] . "</code></pre>";
        }
        echo '<li>Done exporting.</li>';
    }
}
