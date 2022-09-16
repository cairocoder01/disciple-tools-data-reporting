<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_Settings
{
    public $type = 'contacts';

    public function __construct() {
      // add_action( 'wp_ajax_dtdr_enable_config', [$this, 'enable_config'] );
      add_action( 'admin_footer', [ $this, 'scripts' ] );
    }

    public static function ajax_enable_config() {
      $key = $_POST['key'];
      $enabled = filter_var( $_POST['enabled'], FILTER_VALIDATE_BOOLEAN );

      $response_code = 400;
      $response = [
        'success' => false,
        'message' => '',
      ];
      if ( empty($key) ) {
        $response['message'] = 'Missing config key';
      } else {

        $configurations_str = get_option("dt_data_reporting_configurations");
        $configurations = json_decode($configurations_str, true);
        if (isset($configurations[$key])) {
          $configurations[$key]['active'] = $enabled ? 1 : 0;
          update_option("dt_data_reporting_configurations", json_encode($configurations));

          $response_code = 200;
          $response['success'] = true;
          $response['message'] = 'Config updated';
        } else {
          $response['message'] = 'Config key does not exist';
        }
      }
      wp_send_json( $response, $response_code );

      wp_die(); // this is required to terminate immediately and return a proper response
    }

    public static function ajax_save_config() {
      $key = $_POST['key'];

      $excluded_fields = ['key', 'action', '_wp_http_referer', 'security_headers_nonce', 'enabled'];
      $response_code = 400;
      $response = [
        'success' => false,
        'message' => '',
      ];

      if ( empty($key) ) {
        $response['message'] = 'Missing config key';
      } else {

        $configurations_str = get_option("dt_data_reporting_configurations");
        $configurations = json_decode($configurations_str, true);
        if (isset($configurations[$key])) {
          $enabled = filter_var( $_POST['enabled'], FILTER_VALIDATE_BOOLEAN );
          $configurations[$key]['active'] = $enabled ? 1 : 0;


          foreach ( $_POST as $field => $value ) {
            // skip system fields so they don't get saved
            if ( array_key_exists( $field, $excluded_fields ) ) {
              continue;
            }

            $configurations[$key][$field] = $value;
          }

          update_option("dt_data_reporting_configurations", json_encode($configurations));

          $response_code = 200;
          $response['success'] = true;
          $response['message'] = 'Config updated';
        } else {
          $response['message'] = 'Config key does not exist';
        }
      }

      wp_send_json( $response, $response_code );

      wp_die(); // this is required to terminate immediately and return a proper response
    }

    public function styles() {
        ?>
        <style>
            /** switch **/
            .switch [type="checkbox"] {
                position: absolute;
                left: -9999px;
            }

            .switch {
                position: relative;
            }
            .switch label {
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }
            .switch label span:last-child {
                position: relative;
                width: 50px;
                height: 26px;
                border-radius: 15px;
                box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.4);
                background: #eee;
                transition: all 0.3s;
            }
            .switch label span:last-child::before,
            .switch label span:last-child::after {
                content: "";
                position: absolute;
            }
            .switch label span:last-child::before {
                left: 1px;
                top: 1px;
                width: 24px;
                height: 24px;
                background: #fff;
                border-radius: 50%;
                z-index: 1;
                transition: transform 0.3s;
            }
            .switch [type="checkbox"]:checked + label span:last-child {
                background: #46b450;
            }
            .switch [type="checkbox"]:checked + label span:last-child::before {
                transform: translateX(24px);
            }
        </style>
        <?php
    }

    public function scripts() {
      ?>
      <script type="text/javascript" >
        jQuery(document).ready(function($) {
          $( ".dialog" ).dialog({
            autoOpen: false,
            width: 'auto',
            modal: true,
            resizable: true,
            closeOnEscape: true,
            position: {
              my: "center",
              at: "center",
              of: window
            },
            create: function () {
              // style fix for WordPress admin
              $('.ui-dialog-titlebar-close').addClass('ui-button');
            },
          });

          // Dialog tabs
          $('.dialog').on('click', '.nav-tab', function (evt) {
            if (evt) {
              evt.preventDefault();
            }
            $('.dlg-tab-content').hide();
            var selector = this.getAttribute('href');
            $(selector).show();
          });

          // Open config dialog
          $('.edit-trigger').on('click', function () {
            var key = $(this).data('key');
            $('#dialog-' + key).dialog('open');
          });

          $('.config-list-table').on('change', '.config-enable-checkbox', function () {
            var self = this;
            var data = {
              'action': 'dtdr_enable_config',
              'key': this.value,
              'enabled': this.checked,
            };

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(ajaxurl, data).fail(function(xhr) {
              var response = xhr.responseJSON;
              if (!response || !response.success) {
                console.error('Error saving active state of config.', response);
                self.checked = !self.checked;
              }
            });
          });

          $('.dialog form').on('submit', function (evt) {
            if (evt) {
              evt.preventDefault();
            }
            const form = evt.target;
            const formdata = new FormData(form);

            const key = formdata.get('key');

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            fetch(ajaxurl, {
              method: 'POST',
              body: formdata,
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.success) {
                  // if successful, update the main table
                  const row = $('#config-list-row-' + key);
                  row.find('.name').text(formdata.get('name'));
                  // row.find('.provider').text(formdata.get('provider'));
                  row.find('.enabled input').prop('checked', formdata.get('enabled') === 'on');

                  // close the dialog
                  $('#dialog-' + key).dialog('close');
                } else {
                  console.error('Error saving state of config:', data.message);
                }
              })
              .catch((error) => {
                console.error('Error saving state of config:', error);
              });
          });

          // todo: remove this code for old UI
          $('.table-config').on('change', '.provider', function (evt) {
            $('tr[class^=provider-]').hide();
            $('tr.provider-' + $(this).val()).show();
          });
          $('.table-config').on('change', '.data-type-all-data', function (evt) {
            var val = $(this).val();
            var textInput = $(this).closest('td').find('.data-type-limit');
            if (val == 1) {
              textInput.hide();
            } else {
              textInput.show();
            }
          });

          $('.table-config').on('click', '.last-exported-value button', function () {
            var btn = $(this);
            var configKey = btn.data('configKey');
            var dataType = btn.data('dataType');
            $(this).parent().hide();
            $.post(location.href, {
              security_headers_nonce: $('#security_headers_nonce').val(),
              action: 'resetprogress',
              configKey: configKey,
              dataType: dataType,
            })
          });

          $('.table-config').on('click', '.export-logs button', function () {
            var btn = $(this);
            btn.hide();
            btn.siblings('.log-messages').show();
          });
        });
      </script>
      <?php
    }

    public function content() {
        $this->save_settings();

        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );

        $this->styles();
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
//      $share_global = get_option( "dt_data_reporting_share_global", "0" ) === "1";
        $configurations_str = get_option( "dt_data_reporting_configurations" );
        $configurations = json_decode( $configurations_str, true );
        $export_logs_str = get_option( "dt_data_reporting_export_logs" );
        $export_logs = json_decode( $export_logs_str, true );

        if ( empty( $configurations_str ) || !is_array( $configurations ) ) {
            $configurations = [
                'default' => array(),
            ];
        }

        $configurations_ext = apply_filters( 'dt_data_reporting_configurations', array() );
        $providers = apply_filters( 'dt_data_reporting_providers', array() );
        $config_progress = json_decode( get_option( "dt_data_reporting_configurations_progress" ), true );

        $allowed_html = array(
            'a' => array(
                'href' => array(),
                'title' => array()
            ),
        );
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
        <table class="wp-list-table widefat striped config-list-table">
            <thead>
            <tr>
                <th scope="col" class="column-enabled">Enabled</th>
                <th scope="col" class="column-name">Name</th>
                <th scope="col" class="column-provider">Provider</th>
                <th scope="col" class="column-schedule"></th>
                <th scope="col" class="column-actions"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $configurations as $key => $config ): ?>
              <?php $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>
              <tr id="config-list-row-<?php echo esc_attr( $key ) ?>">
                  <td class="enabled">
                    <span class="switch">
                      <input type="checkbox"
                             class="config-enable-checkbox"
                             id="config_enabled_<?php echo esc_attr( $key ) ?>"
                             name="configs[<?php echo esc_attr( $key ) ?>][enabled]"
                             value="<?php echo esc_attr( $key ) ?>"
                             <?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'checked' : "" ?>
                      />
                      <label for="config_enabled_<?php echo esc_attr( $key ) ?>">
                        <span></span>
                      </label>
                    </span>
                  </td>
                  <td class="name"><?php echo esc_html( isset( $config['name'] ) ? $config['name'] : '(new config)' ) ?></td>
                  <td class="provider"><?php echo esc_html( $config_provider ) ?></td>
                  <td></td>
                  <td><a href="javascript:;" class="edit-trigger" data-key="<?php echo esc_attr( $key ) ?>">Edit</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( !empty( $configurations_ext ) ): ?>
        <h3>External Configurations</h3>
        <table class="wp-list-table widefat striped itsec-log-entries itsec-logs-color">
          <thead>
          <tr>
            <th scope="col" class="column-name">Name</th>
            <th scope="col" class="column-provider">Provider</th>
            <th scope="col" class="column-enabled">Enabled</th>
            <th scope="col" class="column-schedule"></th>
            <th scope="col" class="column-actions"></th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ( $configurations_ext as $key => $config ): ?>
            <?php $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>
            <tr>
              <td><?php echo esc_html( $config['name'] ) ?></td>
              <td><?php echo esc_html( $config_provider ) ?></td>
              <td><?php echo isset( $config['active'] ) && $config['active'] == 1 ? '&check; Yes' : '&cross; No' ?></td>
              <td></td>
              <td><a href="javascript:;">View Details</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <br>
      <?php endif; ?>

        <table class="widefat striped">
          <thead>
          <th>Configurations</th>
          </thead>
          <tbody>
          <tr>
            <td>
            <?php foreach ( $configurations as $key => $config ): ?>
                <?php $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>
              <table class="form-table table-config" id="config_<?php echo esc_attr( $key ) ?>">
                <tr>
                  <th>
                    <label for="name_<?php echo esc_attr( $key ) ?>">Name</label>
                  </th>
                  <td>
                    <input type="text"
                           name="configurations[<?php echo esc_attr( $key ) ?>][name]"
                           id="name_<?php echo esc_attr( $key ) ?>"
                           value="<?php echo esc_attr( isset( $config['name'] ) ? $config['name'] : "" ) ?>"
                           style="width: 100%;" />
                    <div class="muted">Label to identify this configuration. This can be anything that helps you understand or remember this configuration.</div>
                  </td>
                </tr>
                <tr>
                  <th>
                    <label for="provider_<?php echo esc_attr( $key ) ?>">Provider</label>
                  </th>
                  <td>
                    <select name="configurations[<?php echo esc_attr( $key ) ?>][provider]"
                            id="provider_<?php echo esc_attr( $key ) ?>"
                            class="provider">
                      <option value="api" <?php echo $config_provider == 'api' ? 'selected' : '' ?>>API</option>

                      <?php if ( !empty( $providers ) ): ?>
                            <?php foreach ( $providers as $provider_key => $provider ): ?>
                        <option
                            value="<?php echo esc_attr( $provider_key ) ?>"
                                <?php echo $config_provider == $provider_key ? 'selected' : '' ?>
                        >
                                <?php echo esc_html( $provider['name'] ) ?>
                        </option>
                      <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                  </td>
                </tr>
                <tr class="provider-api <?php echo $config_provider == 'api' ? '' : 'hide' ?>">
                  <th>
                    <label for="endpoint_url_<?php echo esc_attr( $key ) ?>">Endpoint URL</label>
                  </th>
                  <td>
                    <input type="text"
                           name="configurations[<?php echo esc_attr( $key ) ?>][url]"
                           id="endpoint_url_<?php echo esc_attr( $key ) ?>"
                           value="<?php echo esc_attr( isset( $config['url'] ) ? $config['url'] : "" ) ?>"
                           style="width: 100%;" />
                    <div class="muted">API endpoint that should receive your data in JSON format. With a Google Cloud setup, this would be the URL for an HTTP Cloud Function.</div>
                  </td>
                </tr>
                <tr class="provider-api <?php echo $config_provider == 'api' ? '' : 'hide' ?>">
                  <th>
                    <label for="token_<?php echo esc_attr( $key ) ?>">Token</label>
                  </th>
                  <td>
                    <input type="text"
                           name="configurations[<?php echo esc_attr( $key ) ?>][token]"
                           id="token_<?php echo esc_attr( $key ) ?>"
                           value="<?php echo esc_attr( isset( $config['token'] ) ? $config['token'] : "" ) ?>"
                           style="width: 100%;" />
                    <div class="muted">Optional, depending on required authentication for your endpoint. Token will be sent as an Authorization header to prevent public/anonymous access.</div>
                  </td>
                </tr>

                <!-- Provider Fields -->
                <?php
                if ( !empty( $providers ) ) {
                    foreach ( $providers as $provider_key => $provider ) {
                        if ( isset( $provider['fields'] ) && !empty( $provider['fields'] ) ) {
                            foreach ( $provider['fields'] as $field_key => $field ) {
                                ?>
                          <tr class="provider-<?php echo esc_attr( $provider_key ) ?>  <?php echo $provider_key == $config_provider ? '' : 'hide' ?>">
                            <th>
                              <label for="<?php echo esc_attr( $field_key ) ?>_<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $field['label'] ) ?></label>
                            </th>
                            <td>
                                <?php if ( $field['type'] == 'text' ): ?>
                              <input type="text"
                                     name="configurations[<?php echo esc_attr( $key ) ?>][<?php echo esc_attr( $field_key ) ?>]"
                                     id="<?php echo esc_attr( $field_key ) ?>_<?php echo esc_attr( $key ) ?>"
                                     value="<?php echo esc_attr( isset( $config[$field_key] ) ? $config[$field_key] : "" ) ?>"
                              <?php endif; ?>

                                <?php if ( isset( $field['helpText'] ) ): ?>
                                <div class="muted"><?php echo esc_html( $field['helpText'] ) ?></div>
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
                    <label for="data_types_<?php echo esc_attr( $key ) ?>">Data Types</label>
                  </th>
                  <td>
                    <?php
                    $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
                    $default_type_config = [
                    'all_data' => 0,
                    'limit' => 500
                    ];
                    ?>
                    <table class="form-table striped">
                    <?php foreach ( DT_Data_Reporting_Tools::$data_types as $data_type => $type_name ) {
                        $type_config =isset( $type_configs[$data_type] ) ? $type_configs[$data_type] : $default_type_config;
                        ?>
                      <tr>
                        <th><?php echo esc_html( $type_name ) ?></th>
                        <td>
                          <label>
                            <input type="radio"
                                   name="configurations[<?php echo esc_attr( $key ) ?>][data_types][<?php echo esc_attr( $data_type ) ?>][all_data]"
                                   value="1"
                                   class="data-type-all-data"
                                   <?php echo $type_config['all_data'] == 1 ? 'checked' : '' ?>
                                   />
                            All Data
                          </label>
                          <label>
                            <input type="radio"
                                   name="configurations[<?php echo esc_attr( $key ) ?>][data_types][<?php echo esc_attr( $data_type ) ?>][all_data]"
                                   value="0"
                                   class="data-type-all-data"
                                   <?php echo $type_config['all_data'] == 0 ? 'checked' : '' ?>
                                   />
                            Last Updated
                          </label>

                          <input type="text"
                                 placeholder="Max records"
                                 name="configurations[<?php echo esc_attr( $key ) ?>][data_types][<?php echo esc_attr( $data_type ) ?>][limit]"
                                 class="data-type-limit <?php echo esc_attr( $type_config['all_data'] == 1 ? 'hide' : '' ) ?>"
                                 value="<?php echo esc_attr( isset( $type_config['limit'] ) ? $type_config['limit'] : $default_type_config['limit'] ) ?>"
                                 />

                          <?php if ( isset( $config_progress[$key] ) && isset( $config_progress[$key][$data_type] )): ?>
                            <div class="last-exported-value">
                              Exported Until: <?php echo esc_html( $config_progress[$key][$data_type] ) ?>
                              <button type="button"
                                      data-config-key="<?php echo esc_attr( $key ) ?>"
                                      data-data-type="<?php echo esc_attr( $data_type ) ?>">Reset</button>
                            </div>
                          <?php endif; ?>

                            <div>
                                <label>
                                    <input type="checkbox"
                                           name="configurations[<?php echo esc_attr( $key ) ?>][data_types][<?php echo esc_attr( $data_type ) ?>][schedule]"
                                           <?php echo isset( $type_config['schedule'] ) && $type_config['schedule'] == 'daily' ? 'checked' : '' ?>
                                           value="daily" />
                                    Enable automatic daily export
                                </label>
                            </div>

                            <?php if ( isset( $export_logs[$key] ) && isset( $export_logs[$key][$data_type] ) ): ?>
                            <div class="export-logs">
                                <button type="button">View Last Export Logs</button>
                                <div class="log-messages" style="display: none;">
                                    <div class="result">Result: <?php echo $export_logs[$key][$data_type]['success'] ? 'Success' : 'Fail' ?></div>
                                    <ul class="api-log">
                                        <?php foreach ( $export_logs[$key][$data_type]['messages'] as $message ) {
                                            $message_type = isset( $message['type'] ) ? $message['type'] : '';
                                            $content = isset( $message['message'] ) ? $message['message'] : '';
                                            echo "<li class='" . esc_attr( $message_type ) . "'>" . wp_kses( $content, $allowed_html ) . "</li>";
                                        } ?>
                                    </ul>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                      </tr>
                    <?php } ?>
                    </table>
                  </td>
                </tr>
                <tr>
                  <th>
                    <label for="active_<?php echo esc_attr( $key ) ?>">Is Active</label>
                  </th>
                  <td>
                    <input type="checkbox"
                           name="configurations[<?php echo esc_attr( $key ) ?>][active]"
                           id="endpoint_active_<?php echo esc_attr( $key ) ?>"
                           value="1"
                           <?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'checked' : "" ?>
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
        <br>
        <table class="widefat striped">
          <thead>
          <th>External Configurations</th>
          </thead>
          <tbody>
          <tr>
            <td>
              <?php if ( !empty( $configurations_ext ) ): ?>
                    <?php foreach ( $configurations_ext as $key => $config ): ?>
                <table class="form-table table-config">
                  <tr>
                    <th>
                      <label>Name</label>
                    </th>
                    <td>
                        <?php echo esc_html( isset( $config['name'] ) ? $config['name'] : "" ) ?>
                    </td>
                  </tr>
                  <tr>
                    <th>
                      <label>Endpoint URL</label>
                    </th>
                    <td>
                        <?php echo esc_html( isset( $config['url'] ) ? $config['url'] : "" ) ?>
                    </td>
                  </tr>
                        <?php if ( isset( $config['token'] ) ): ?>
                  <tr>
                    <th>
                      <label>Token</label>
                    </th>
                    <td>
                            <?php echo esc_html( isset( $config['token'] ) ? $config['token'] : "" ) ?>
                    </td>
                  </tr>
                  <?php endif; ?>
                        <?php if ( isset( $config['data_types'] ) ): ?>
                  <tr>
                      <th>
                          <label>Data Types</label>
                      </th>
                      <td>
                            <?php
                            $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
                            $default_type_config = [
                            'all_data' => 0,
                            'limit' => 500
                            ];
                            ?>
                          <table class="form-table">
                              <?php foreach ( DT_Data_Reporting_Tools::$data_types as $data_type => $type_name ) {
                                    $type_config =isset( $type_configs[$data_type] ) ? $type_configs[$data_type] : $default_type_config;
                                    ?>
                                  <tr>
                                      <th><?php echo esc_html( $type_name ) ?></th>
                                      <td>
                                          <?php if ( $type_config['all_data'] == 1 ): ?>
                                              All Data
                                          <?php else : ?>
                                              Last Updated
                                              (Max records: <?php echo esc_html( isset( $type_config['limit'] ) ? $type_config['limit'] : $default_type_config['limit'] ) ?>)
                                              <?php if ( isset( $config_progress[$key] ) && isset( $config_progress[$key][$data_type] )): ?>
                                                  <div class="last-exported-value">
                                                      Exported Until: <?php echo esc_html( $config_progress[$key][$data_type] ) ?>
                                                      <button type="button"
                                                              data-config-key="<?php echo esc_attr( $key ) ?>"
                                                              data-data-type="<?php echo esc_attr( $data_type ) ?>">Reset</button>
                                                  </div>
                                              <?php endif; ?>
                                          <?php endif; ?>

                                          <?php if ( isset( $type_config['schedule'] ) && $type_config['schedule'] == 'daily'): ?>
                                          <p>&check; Automatic daily export</p>
                                          <?php endif; ?>

                                          <?php if ( isset( $export_logs[$key] ) && isset( $export_logs[$key][$data_type] ) ): ?>
                                              <div class="export-logs">
                                                  <button type="button">View Last Export Logs</button>
                                                  <div class="log-messages" style="display: none;">
                                                      <div class="result">Result: <?php echo $export_logs[$key][$data_type]['success'] ? 'Success' : 'Fail' ?></div>
                                                      <ul class="api-log">
                                                          <?php foreach ( $export_logs[$key][$data_type]['messages'] as $message ) {
                                                                $message_type = isset( $message['type'] ) ? $message['type'] : '';
                                                                $content = isset( $message['message'] ) ? $message['message'] : '';
                                                                echo "<li class='" . esc_attr( $message_type ) . "'>" . wp_kses( $content, $allowed_html ) . "</li>";
                                                          } ?>
                                                      </ul>
                                                  </div>
                                              </div>
                                          <?php endif; ?>
                                      </td>
                                  </tr>
                              <?php } ?>
                          </table>
                      </td>
                  </tr>
                  <?php endif; ?>
                  <tr>
                    <th>
                      <label>Is Active</label>
                    </th>
                    <td>
                        <?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'Yes' : 'No' ?>
                    </td>
                  </tr>

                </table>
              <?php endforeach; ?>
              <?php else : ?>
                No external configurations
              <?php endif; ?>
            </td>
          </tr>
          </tbody>
        </table>
      </form>

      <?php $this->edit_dialogs( $configurations ); ?>
        <?php
    }

    public function edit_dialogs( $configurations ) {
      echo "<div style='display:none;'>";
      foreach ( $configurations as $key => $config ):
        $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>

        <div class="dialog" id="dialog-<?php echo esc_attr( $key ) ?>">
          <form method="POST" action="">
            <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
            <input type="hidden" name="action" value="save_config" />
            <input type="hidden" name="key" value="<?php echo esc_attr( $key ) ?>" />
            <h2 class="nav-tab-wrapper">
              <a href="#dlg-tab-general-<?php echo esc_attr( $key )?>" class="nav-tab">General</a>
              <a href="#dlg-tab-provider-<?php echo esc_attr( $key )?>" class="nav-tab">Provider</a>
              <a href="#dlg-tab-data-types-<?php echo esc_attr( $key )?>" class="nav-tab">Data Types</a>
            </h2>
            <div class="wrap">
              <div id="dlg-tab-general-<?php echo esc_attr( $key ) ?>" class="dlg-tab-content">
                <table class="form-table table-config" id="config_<?php echo esc_attr( $key ) ?>">
                  <tr>
                    <th>
                      <label for="dlg_name_<?php echo esc_attr( $key ) ?>">Name</label>
                    </th>
                    <td>
                      <input type="text"
                             name="name"
                             id="dlg_name_<?php echo esc_attr( $key ) ?>"
                             value="<?php echo esc_attr( isset( $config['name'] ) ? $config['name'] : "" ) ?>"
                             style="width: 100%;" />
                      <div class="muted">Label to identify this configuration. This can be anything that helps you understand or remember this configuration.</div>
                    </td>
                  </tr>
                  <tr>
                    <th>
                      <label for="dlg_enabled_<?php echo esc_attr( $key ) ?>">Enabled</label>
                    </th>
                    <td>
                      <span class="switch">
                        <input type="checkbox"
                               class="config-enable-checkbox"
                               id="dlg_enabled_<?php echo esc_attr( $key ) ?>"
                               name="enabled"
                               <?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'checked' : "" ?>
                        />
                        <label for="dlg_enabled_<?php echo esc_attr( $key ) ?>">
                          <span></span>
                        </label>
                      </span>
                      <div class="muted">When checked, this configuration is active and will be exported.</div>
                    </td>
                  </tr>
                </table>
              </div>
              <div id="dlg-tab-provider-<?php echo esc_attr( $key ) ?>" class="dlg-tab-content" style="display: none;">
                <h3>Provider</h3>

              </div>
              <div id="dlg-tab-data-types-<?php echo esc_attr( $key ) ?>" class="dlg-tab-content" style="display: none;">
                <h3>Data Types</h3>
              </div>
            </div>

            <br>
            <button type="submit" class="button right">Save Settings</button>
          </form>
        </div>
      <?php endforeach;
      echo "</div>";
    }

    public function save_settings() {
      if ( empty( $_POST ) ) {
        return;
      }
      if ( !isset( $_POST['security_headers_nonce'] ) || !wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
        return;
      }

      $is_dialog = isset( $_POST['dialog'] );

      if ( $is_dialog ) {
        // new saving workflow
      } else {
        $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : null;
        if ($action == 'resetprogress') {
          if (isset($_POST['configKey']) && isset($_POST['dataType'])) {
            $config_progress = json_decode(get_option("dt_data_reporting_configurations_progress"), true);
            $config_key = sanitize_key(wp_unslash($_POST['configKey']));
            $data_type = sanitize_key(wp_unslash($_POST['dataType']));
            if (isset($config_progress[$config_key])) {
              unset($config_progress[$config_key][$data_type]);
              update_option("dt_data_reporting_configurations_progress", json_encode($config_progress));
            }
          }
        } else {
          //configurations
          if (isset($_POST['configurations'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            update_option("dt_data_reporting_configurations", json_encode($this->sanitize_text_or_array_field(wp_unslash($_POST['configurations']))));
          }

          //share_global
          update_option("dt_data_reporting_share_global",
            isset($_POST['share_global']) && $_POST['share_global'] === "1" ? "1" : "0");

          echo '<div class="notice notice-success notice-dt-data-reporting is-dismissible" data-notice="dt-data-reporting"><p>Settings Saved</p></div>';
        }
      }
    }

    private function sanitize_text_or_array_field( $array_or_string) {
        if (is_string( $array_or_string )) {
            $array_or_string = sanitize_text_field( $array_or_string );
        } elseif (is_array( $array_or_string )) {
            foreach ($array_or_string as $key => &$value) {
                if (is_array( $value )) {
                    $value = $this->sanitize_text_or_array_field( $value );
                } else {
                    $value = sanitize_text_field( $value );
                }
            }
        }

        return $array_or_string;
    }
}
