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
      <table class="widefat">
      <thead>
        <tr><th>Overview</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <p>This plugin was built with the intention of using BigQuery as its external data store from which to do all reporting and analysis. As such, you can find below some info and examples of how you can duplicate that setup.</p>
            <p>Using Google Cloud Platform, these resources should stay within the free usage limits, depending on your usage. You will need to add your credit card to your account, but as long as your usage isn&#39;t overly much, you shouldn&#39;t be billed for anything.</p>

          </td>
        </tr>
      </tbody>
      </table>
      <br>

      <table class="widefat" id="schemas-bq">
        <thead>
        <tr><th>Overview of Process</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p>To stay within free usage, we are going to save data to Cloud Storage and load those files into BigQuery instead of streaming data directly into BigQuery. Because of this, there are 2 different Cloud Functions that will be utilized.</p>
            <p>As an overview, these are the steps that will be taken:</p>
            <ol>
              <li><strong>Cloud Function (HTTP)</strong>: Receive JSON data from plugin. Save as JSON line-delimited file in Cloud Storage.</li>
              <li><strong>Cloud Storage</strong>: Bucket will temporarily hold generated data file.</li>
              <li><strong>Cloud Function (Storage trigger)</strong>: Function is triggered when a new file is uploaded to storage bucket. Meta data will be read to know what table to load the data into, and the file will be loaded into BigQuery.</li>
              <li><strong>BigQuery</strong>: Holds data ready for reporting. Easy to connect to various visualization tools.</li>
            </ol>

          </td>
        </tr>
        </tbody>
      </table>
      <br>

      <table class="widefat" id="schemas-bq">
        <thead>
        <tr><th>Account Setup</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p><em>Full details to come</em></p>

          </td>
        </tr>
        </tbody>
      </table>
      <br>

      <table class="widefat" id="schemas-bq">
        <thead>
        <tr><th>BigQuery Setup</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p><em>Full details to come</em></p>
          </td>
        </tr>
        </tbody>
      </table>
      <br>

      <table class="widefat" id="schemas-bq">
        <thead>
        <tr><th>Cloud Storage Setup</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p><em>Full details to come</em></p>
          </td>
        </tr>
        </tbody>
      </table>
      <br>

      <table class="widefat" id="schemas-bq">
        <thead>
        <tr><th>Cloud Function Setup - HTTP Endpoint</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p><em>Full details to come</em></p>
          </td>
        </tr>
        </tbody>
      </table>
      <br>

      <table class="widefat" id="schemas-bq">
        <thead>
        <tr><th>Cloud Function Setup - Storage Trigger</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p><em>Full details to come</em></p>
          </td>
        </tr>
      </tbody>
      </table>
      <br>

      <table class="widefat" id="schemas-bq">
      <thead>
        <tr><th>BigQuery Schemas</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <h2>Contacts</h2>
            <?php $this->print_schema('contacts') ?>
          </td>
        </tr>
      </tbody>
      </table>
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
