<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_BigQuery
{
    public $token;
    public function __construct( $token ) {
        $this->token = $token;
      require_once( plugin_dir_path( __FILE__ ) . '../data-tools.php' );
    }

    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
      <p>If you are using BigQuery as your database storage, you can paste the below as the schema when creating the database tables.</p>
      <h2>Contacts</h2>
      <?php $this->print_schema('contacts') ?>
        <?php
    }

  public function print_schema( $type ) {
    switch ($type) {
      /*case 'contact_activity':
          [$columns, $rows] = DT_Data_Reporting_Tools::get_contact_activity(false);
          $this->export_data($columns, $rows);
          break;*/
      case 'contacts':
      default:
        // This is just a preview, so get the first 25 contacts only
        [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, 1);
        // [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, 1000);
        echo "<pre><code style='display:block;'>";
        $bqColumns = array_map(function ($col) {
          return [
              'name' => $col['key'],
              'type' => $col['bq_type'],
              'mode' => $col['bq_mode'],
          ];
        }, $columns);
        echo json_encode($bqColumns, JSON_PRETTY_PRINT);
        echo '</code></pre>';
        break;
    }
  }
}
