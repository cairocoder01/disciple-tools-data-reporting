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
      <script>
        jQuery(function($) {
          $('.table-config').on('change', '.provider', function (evt) {
            console.log($(this).val());
            $('tr[class^=provider-]').hide();
            $('tr.provider-' + $(this).val()).show();
          });
        });
      </script>
        <?php
    }

    public function main_column() {
//      $share_global = get_option( "dt_data_reporting_share_global", "0" ) === "1";
      $endpoint_url = get_option( "dt_data_reporting_endpoint_url" );
      $configurations_str = get_option( "dt_data_reporting_configurations");
      $configurations = json_decode( $configurations_str, true );
      if ( empty( $configurations_str ) ) {
        $configurations = [
            'default' => [
                'url' => $endpoint_url,
            ]
        ];
      } else if (array_keys($configurations) === range(0, count($configurations) - 1)) {
        // If not an associative array, convert it
        $configurations = array_reduce( $configurations, function ( $result, $config ) {
          $key = uniqid();
          $result[$key] = $config;
          return $result;
        });
      }

      $configurations_ext = apply_filters('dt_data_reporting_configurations', array());
      $providers = apply_filters('dt_data_reporting_providers', array());

      ?>
      <form method="POST" action="">
        <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
        <!--<table class="widefat striped">
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
                    <input type="checkbox" name="share_global" id="share_global" value="1" <?php /*echo $share_global ? 'checked' : '' */?> />
                    <span class="muted">Share anonymized data to global reporting database.</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          </tbody>
        </table>
        <br>-->

        <table class="widefat striped">
          <thead>
          <th>Configurations</th>
          </thead>
          <tbody>
          <tr>
            <td>
            <?php foreach( $configurations as $key => $config ): ?>
              <?php $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>
              <table class="form-table table-config" id="config_<?php echo $key ?>">
                <tr>
                  <th>
                    <label for="name_<?php echo $key ?>">Name</label>
                  </th>
                  <td>
                    <input type="text"
                           name="configurations[<?php echo $key ?>][name]"
                           id="name_<?php echo $key ?>"
                           value="<?php echo isset($config['name']) ? $config['name'] : "" ?>"
                           style="width: 100%;" />
                    <div class="muted">Label to identify this configuration. This can be anything that helps you understand or remember this configuration.</div>
                  </td>
                </tr>
                <tr>
                  <th>
                    <label for="provider_<?php echo $key ?>">Provider</label>
                  </th>
                  <td>
                    <select name="configurations[<?php echo $key ?>][provider]"
                            id="provider_<?php echo $key ?>"
                            class="provider">
                      <option value="api" <?php echo $config_provider == 'api' ? 'selected' : '' ?>>API</option>

                      <?php if( !empty($providers) ): ?>
                      <?php foreach( $providers as $provider_key => $provider ): ?>
                        <option
                            value="<?php echo $provider_key ?>"
                            <?php echo $config_provider == $provider_key ? 'selected' : '' ?>
                        >
                          <?php echo $provider['name'] ?>
                        </option>
                      <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                  </td>
                </tr>
                <tr class="provider-api <?php echo $config_provider == 'api' ? '' : 'hide' ?>">
                  <th>
                    <label for="endpoint_url_<?php echo $key ?>">Endpoint URL</label>
                  </th>
                  <td>
                    <input type="text"
                           name="configurations[<?php echo $key ?>][url]"
                           id="endpoint_url_<?php echo $key ?>"
                           value="<?php echo isset($config['url']) ? $config['url'] : "" ?>"
                           style="width: 100%;" />
                    <div class="muted">API endpoint that should receive your data in JSON format. With a Google Cloud setup, this would be the URL for an HTTP Cloud Function.</div>
                  </td>
                </tr>
                <tr class="provider-api <?php echo $config_provider == 'api' ? '' : 'hide' ?>">
                  <th>
                    <label for="token_<?php echo $key ?>">Token</label>
                  </th>
                  <td>
                    <input type="text"
                           name="configurations[<?php echo $key ?>][token]"
                           id="token_<?php echo $key ?>"
                           value="<?php echo isset($config['token']) ? $config['token'] : "" ?>"
                           style="width: 100%;" />
                    <div class="muted">Optional, depending on required authentication for your endpoint. Token will be sent as an Authorization header to prevent public/anonymous access.</div>
                  </td>
                </tr>

                <!-- Provider Fields -->
                <?php
                  if( !empty($providers) ) {
                    foreach( $providers as $provider_key => $provider ) {
                      if ( isset( $provider['fields'] ) && !empty( $provider['fields'] ) ) {
                        foreach ( $provider['fields'] as $field_key => $field ) {
                          ?>
                          <tr class="provider-<?php echo $provider_key ?>  <?php echo $provider_key == $config_provider ? '' : 'hide' ?>">
                            <th>
                              <label for="<?php echo $field_key ?>_<?php echo $key ?>"><?php echo $field['label'] ?></label>
                            </th>
                            <td>
                              <?php if( $field['type'] == 'text' ): ?>
                              <input type="text"
                                     name="configurations[<?php echo $key ?>][<?php echo $field_key ?>]"
                                     id="<?php echo $field_key ?>_<?php echo $key ?>"
                                     value="<?php echo isset($config[$field_key]) ? $config[$field_key] : "" ?>"
                              <?php endif; ?>

                              <?php if( isset( $field['helpText'] ) ): ?>
                                <div class="muted"><?php echo $field['helpText'] ?></div>
                              <?php endif; ?>
                            </td>
                          </tr>
                          <?php
                        }
                      }
                    }
                  }
                ?>

                <tr>
                  <th>
                    <label for="active_<?php echo $key ?>">Is Active</label>
                  </th>
                  <td>
                    <input type="checkbox"
                           name="configurations[<?php echo $key ?>][active]"
                           id="endpoint_active_<?php echo $key ?>"
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
        <table class="widefat striped">
          <thead>
          <th>External Configurations</th>
          </thead>
          <tbody>
          <tr>
            <td>
              <?php foreach( $configurations_ext as $key => $config ): ?>
                <table class="form-table table-config">
                  <tr>
                    <th>
                      <label>Name</label>
                    </th>
                    <td>
                      <?php echo isset($config['name']) ? $config['name'] : "" ?>
                    </td>
                  </tr>
                  <tr>
                    <th>
                      <label>Endpoint URL</label>
                    </th>
                    <td>
                      <?php echo isset($config['url']) ? $config['url'] : "" ?>
                    </td>
                  </tr>
                  <?php if ( isset($config['token']) ): ?>
                  <tr>
                    <th>
                      <label>Token</label>
                    </th>
                    <td>
                      <?php echo isset($config['token']) ? $config['token'] : "" ?>
                    </td>
                  </tr>
                  <?php endif; ?>
                  <tr>
                    <th>
                      <label>Is Active</label>
                    </th>
                    <td>
                      <?php echo isset($config['active']) && $config['active'] == 1 ? 'Yes' : 'No' ?>
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
