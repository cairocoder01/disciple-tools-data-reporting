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
      $endpoint_url = get_option( "dt_data_reporting_endpoint_url" );
      $configurations_str = get_option( "dt_data_reporting_configurations");
      $configurations = json_decode( $configurations_str, true );
      if ( empty( $configurations_str ) ) {
        $configurations = [[
            'url' => $endpoint_url,
        ]];
      }
      ?>
      <form method="POST" action="">
        <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
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
              </table>
            </td>
          </tr>
          </tbody>
        </table>
        <br>

        <table class="widefat striped">
          <thead>
          <th>Configurations</th>
          </thead>
          <tbody>
          <tr>
            <td>
            <?php foreach( $configurations as $idx => $config ): ?>
              <table class="form-table">
                <tr>
                  <th>
                    <label for="endpoint_url_<?php echo $idx ?>">Endpoint URL</label>
                  </th>
                  <td>
                    <input type="text"
                           name="configurations[<?php echo $idx ?>][url]"
                           id="endpoint_url_<?php echo $idx ?>"
                           value="<?php echo isset($config['url']) ? $config['url'] : "" ?>"
                           style="width: 100%;" />
                    <div class="muted">API endpoint that should receive your data in JSON format. With a Google Cloud setup, this would be the URL for an HTTP Cloud Function.</div>
                  </td>
                </tr>
                <tr>
                  <th>
                    <label for="endpoint_token_<?php echo $idx ?>">Token</label>
                  </th>
                  <td>
                    <input type="text"
                           name="configurations[<?php echo $idx ?>][token]"
                           id="endpoint_token_<?php echo $idx ?>"
                           value="<?php echo isset($config['token']) ? $config['token'] : "" ?>"
                           style="width: 100%;" />
                    <div class="muted">Optional, depending on required authentication for your endpoint. Token will be sent as an Authorization header to prevent public/anonymous access.</div>
                  </td>
                </tr>
                <tr>
                  <th>
                    <label for="active_<?php echo $idx ?>">Is Active</label>
                  </th>
                  <td>
                    <input type="checkbox"
                           name="configurations[<?php echo $idx ?>][active]"
                           id="endpoint_active_<?php echo $idx ?>"
                           value="1"
                           <?php echo isset($config['active']) && $config['active'] == 1 ? 'checked' : "" ?>
                            />
                    <span class="muted">When checked, this configuration is active and will be exported.</span>
                  </td>
                </tr>

              </table>
            <?php endforeach; ?>
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
          //configurations
          if ( isset( $_POST['configurations'] ) ) {
            update_option("dt_data_reporting_configurations", json_encode( $_POST['configurations'] ) );
          }

          //share_global
          update_option( "dt_data_reporting_share_global",
            isset( $_POST['share_global'] ) && $_POST['share_global'] === "1" ? "1" : "0" );

          echo '<div class="notice notice-success notice-dt-data-reporting is-dismissible" data-notice="dt-data-reporting"><p>Settings Saved</p></div>';
        }
      }
    }
}
