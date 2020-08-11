<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_Settings
{
    public $type = 'contacts';

    public function __construct( )
    {
    }

    public function content() {
        $this->save_settings();
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
      $share_global = get_option( "dt_data_reporting_share_global", "0" ) === "1";
      $is_maarifa_active = is_plugin_active( "dt-maarifa/disciple-tools-maarifa.php" );
      $share_maarifa = get_option( "dt_data_reporting_share_maarifa", "1" ) === "1";
      $endpoint_url = get_option( "dt_data_reporting_endpoint_url" );
      ?>
      <form method="POST" action="">
        <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
        <table class="widefat striped">
          <thead>
          <th>Configuration</th>
          </thead>
          <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr>
                  <th>
                    <label for="share_global">Endpoint URL</label> <br>
                  </th>
                  <td>
                    <input type="text" name="endpoint_url" id="endpoint_url" value="<?php echo $endpoint_url ?>" style="width: 100%;" />
                    <div class="muted">API endpoint that should receive your data in JSON format. With a Google Cloud setup, this would be the URL for an HTTP Cloud Function.</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          </tbody>
        </table>
        <br>
        <table class="widefat striped">
          <thead>
          <th>External Sharing</th>
          </thead>
          <tbody>
          <tr>
            <td>
              <table class="form-table striped">
                <tr>
                  <th>
                    <label for="share_global">Share to Global Database</label>
                  </th>
                  <td>
                    <input type="checkbox" name="share_global" id="share_global" value="1" <?php echo $share_global ? 'checked' : '' ?> />
                    <span class="muted">Share anonymized data to global reporting database.</span>
                  </td>
                </tr>
                <?php if ( $is_maarifa_active ): ?>
                  <tr>
                    <th>
                      <label for="share_maarifa">Share Maarifa Data</label> <br>
                    </th>
                    <td>
                      <input type="checkbox" name="share_maarifa" id="share_maarifa" value="1" <?php echo $share_maarifa ? 'checked' : '' ?> />
                      <input type="hidden" name="maarifa_active" value="<?php echo $is_maarifa_active ? "1" : "0" ?>" />
                      <span class="muted">Share updates for contacts originating from Maarifa back to them for campaign evaluation.</span> <br>
                    </td>
                  </tr>
                <?php endif; ?>
              </table>
            </td>
          </tr>
          </tbody>
        </table>
        <br>
        <button type="submit" class="button right">Save Settings</button>
      </form>
      <?php
    }

    public function save_settings() {
      if ( !empty( $_POST ) ){
        if ( isset( $_POST['security_headers_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
          //endpoint_url
          update_option( "dt_data_reporting_endpoint_url",
            isset( $_POST['endpoint_url'] ) ? sanitize_text_field( $_POST['endpoint_url'] ) : "" );

          //share_global
          update_option( "dt_data_reporting_share_global",
            isset( $_POST['share_global'] ) && $_POST['share_global'] === "1" ? "1" : "0" );

          //share_maarifa
          if ( isset( $_POST['maarifa_active'] ) && $_POST['maarifa_active'] === "1" ) {
            update_option( "dt_data_reporting_share_maarifa",
              isset( $_POST['share_maarifa'] ) && $_POST['share_maarifa'] === "1" ? "1" : "0" );
          }
        }
      }
    }
}
