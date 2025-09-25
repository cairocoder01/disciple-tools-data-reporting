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
        $post_types = DT_Posts::get_post_types();
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
        <?php foreach ( $post_types as $post_type ):
            $post_type_settings = DT_Posts::get_post_settings( $post_type );
            $activity_type = rtrim( $post_type, 's' ) . '_activity';
            $snapshot_type = rtrim( $post_type, 's' ) . '_snapshots';
            ?>
          <tr>
            <td>
              <table class="widefat schema accordion collapsed">
                <thead>
                <tr><th>
                    <a href="javascript:;" class="toggle">
                      <?php esc_html_e( $post_type_settings['label_plural'] ) ?>
                      <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>" class="icon closed"/>
                      <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>" class="icon open"/>
                    </a>
                  </th></tr>
                </thead>
                <tbody>
                  <tr><td>
                    Table Name: <code><?php echo esc_html( $post_type ) ?></code>
                    <br>
                    <?php $this->print_schema( $post_type ) ?>
                    </td></tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td>
              <table class="widefat schema accordion collapsed">
                <thead>
                <tr><th><a href="javascript:;" class="toggle">
                      <?php esc_html_e( $post_type_settings['label_singular'] ) ?> Activity
                      <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>" class="icon closed"/>
                      <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>" class="icon open"/>
                    </a></th></tr>
                </thead>
                <tbody>
                  <tr><td>
                    Table Name: <code><?php echo esc_html( $activity_type ) ?></code>
                    <br>
                    <?php $this->print_schema( $activity_type ) ?>
                    </td></tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td>
              <table class="widefat schema accordion collapsed">
                <thead>
                <tr><th><a href="javascript:;" class="toggle">
                      <?php esc_html_e( $post_type_settings['label_singular'] ) ?> Snapshots
                      <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>" class="icon closed"/>
                      <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>" class="icon open"/>
                    </a></th></tr>
                </thead>
                <tbody>
                  <tr><td>
                    Table Name: <code><?php echo esc_html( $snapshot_type ) ?></code>
                    <br>
                    <?php $this->print_schema( $snapshot_type ) ?>
                    </td></tr>
                </tbody>
              </table>
            </td>
          </tr>
      <?php endforeach; ?>
      </tbody>
      </table>
        <?php
    }

    public function print_schema( $type ) {
        $root_type = str_replace( '_activity', 's', $type );
        $root_type = str_replace( '_snapshots', 's', $root_type );
        $is_activity = $root_type !== $type;
        $is_activity = str_contains( $type, '_activity' );
        $is_snapshots = str_contains( $type, '_snapshots' );

        if ( $is_activity ) {
          [ $columns, ] = DT_Data_Reporting_Tools::get_post_activity( $root_type, array( 'limit' => 1 ) );
        } else if ( $is_snapshots ) {
          [ $columns, ] = DT_Data_Reporting_Tools::get_post_snapshots( $root_type, array( 'limit' => 1 ) );
        } else {
          [ $columns, ] = DT_Data_Reporting_Tools::get_posts( $type, false, array( 'limit' => 1 ) );
        }
        echo "<pre><code style='display:block;'>";
        $bq_columns = array_map(function ( $col) {
            return array(
                'name' => $col['key'],
                'type' => $col['bq_type'],
                'mode' => $col['bq_mode'],
            );
        }, $columns);
        echo json_encode( $bq_columns, JSON_PRETTY_PRINT );
        echo '</code></pre>';
    }
}
