<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_API
{
    public $type = 'contacts';

    public function __construct( $type )
    {
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
        switch ($this->type) {
            /*case 'contact_activity':
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contact_activity(false);
                $this->export_data($columns, $rows);
                break;*/
            case 'contacts':
            default:
                // This is just a preview, so get the first 25 contacts only
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, 10);
                // [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, 1000);
                $this->export_data($columns, $rows, $this->type);
                break;
        }
    }
    public function export_data($columns, $rows, $type ) {

      echo '<ul>';
      echo '<li>Starting export...';
      // todo: load this from a plugin setting
      $url = 'https://us-central1-maarifa-logging.cloudfunctions.net/dtDataLoad';
      $args = [
        'method' => 'POST',
        'headers' => array(
          'Content-Type' => 'application/json; charset=utf-8'
        ),
        'body'      => json_encode([
          'columns' => $columns,
          'items' => $rows,
          'type' => $type,
        ]),
      ];

      $result = wp_remote_post( $url, $args );
      if ( is_wp_error( $result ) ){
        $error_message = $result->get_error_message() ?? '';
        dt_write_log($error_message);
        echo "<li>Error: $error_message</li>";
      } else {
        $result_body = json_decode($result['body']);
        echo "<li><pre><code>".$result['body']."</code></pre>";
//        if (!empty($result_body) && $result_body === true) {
//          return [
//            "success" => true,
//            "message" => $linked,
//          ];
//        }
      }
      echo '<li>Done exporting.</li>';
      echo '</ul>';
    }
}
