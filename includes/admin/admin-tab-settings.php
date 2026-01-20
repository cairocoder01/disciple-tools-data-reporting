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
        $key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : null;
        $enabled = isset( $_POST['enabled'] ) ? sanitize_key( wp_unslash( $_POST['enabled'] ) ) : null;
        $enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

        $response_code = 400;
        $response = [
            'success' => false,
            'message' => '',
        ];
        if ( !isset( $_POST['security_headers_nonce'] ) || !wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
            $response['message'] = 'Insecure request';
        } else if ( empty( $key ) ) {
            $response['message'] = 'Missing config key';
        } else {

            $configurations_str = get_option( 'dt_data_reporting_configurations' );
            $configurations = json_decode( $configurations_str, true );
            if ( isset( $configurations[$key] ) ) {
                $configurations[$key]['active'] = $enabled ? 1 : 0;
                update_option( 'dt_data_reporting_configurations', json_encode( $configurations ) );

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
        $key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : 'default';
        $excluded_fields = [ 'key', 'action', '_wp_http_referer', 'security_headers_nonce', 'enabled' ];
        $response_code = 400;
        $response = [
            'success' => false,
            'message' => '',
        ];


        if ( !isset( $_POST['security_headers_nonce'] ) || !wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
            $response['message'] = 'Insecure request';
        } else if ( empty( $key ) ) {
            $response['message'] = 'Missing config key';
        } else {

            $configurations_str = get_option( 'dt_data_reporting_configurations' );
            $configurations = json_decode( $configurations_str, true );

            if ( !isset( $configurations[$key] ) ) {
                $configurations[$key] = [];
            }
            $enabled = isset( $_POST['enabled'] ) ? sanitize_key( wp_unslash( $_POST['enabled'] ) ) : null;
            $enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );
            $configurations[$key]['active'] = $enabled ? 1 : 0;


            foreach ( $_POST as $field => $value ) {
                // skip system fields so they don't get saved
                if ( array_key_exists( $field, $excluded_fields ) ) {
                    continue;
                }

                $configurations[$key][$field] = $value;
            }

            update_option( 'dt_data_reporting_configurations', json_encode( $configurations ) );

            $response_code = 200;
            $response['success'] = true;
            $response['message'] = 'Config updated';
        }

        wp_send_json( $response, $response_code );

        wp_die(); // this is required to terminate immediately and return a proper response
    }

    public static function ajax_reset_progress() {
        $key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : null;

        $response_code = 400;
        $response = [
            'success' => false,
            'message' => '',
        ];
        if ( !isset( $_POST['security_headers_nonce'] ) || !wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
            $response['message'] = 'Insecure request';
        } else if ( empty( $key ) ) {
            $response['message'] = 'Missing config key';
        } else {
            $response['message'] = 'Error resetting progress';
            if ( isset( $_POST['dataType'] ) ) {
                $config_progress = json_decode( get_option( 'dt_data_reporting_configurations_progress' ), true );
                $data_type = sanitize_key( wp_unslash( $_POST['dataType'] ) );
                if ( isset( $config_progress[$key] ) ) {
                    unset( $config_progress[$key][$data_type] );
                    update_option( 'dt_data_reporting_configurations_progress', json_encode( $config_progress ) );
                    $response_code = 200;
                    $response['success'] = true;
                    $response['message'] = 'Progress reset';
                }
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

          .table-config td { max-width: 50vw; }

          .dialog { min-width: 50vw; }
          .dialog .nav-tab-wrapper + .wrap,
          .dialog .nav-tab-wrapper + .wrap table { margin-top: 0; }
          .dialog table.accordion thead td { background: #dcdcde; }

          /** Data Types Tab **/
          .data-type-config-table label.label { display: block; font-weight: 600; }
          .data-type-config-table .muted { opacity: 0.65; }

          .data-type-config-table label.radio {
            display: block;
            padding-left: 2rem;
            position: relative;
            margin-top: 0.5rem;
          }

          .data-type-config-table label.radio input[type=radio] {
            position: absolute;
            left: 0;
            top: 0.5rem;
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
            var tabNav = $(this).closest('.nav-tab-wrapper');
            var tabContainer = tabNav.next('.wrap');
            tabContainer.find('.dlg-tab-content').hide();
            var selector = this.getAttribute('href');
            $(selector).show();
          });

          // Open config dialog
          $('.edit-trigger').on('click', function () {
            var key = $(this).data('key');
            $('#dialog-' + key).dialog('open');
          });

          // Toggle Enabled option from main list table
          $('.config-list-table').on('change', '.config-enable-checkbox', function () {
              var self = this;
              var data = new FormData();
              data.append('security_headers_nonce', $('#security_headers_nonce').val());
              data.append('action', 'dtdr_enable_config');
              data.append('key', this.value);
              data.append('enabled', this.checked);

              // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
              fetch(ajaxurl, {
                  method: 'POST',
                  body: data,
              }).then((response) => response.json())
                  .then((data) => {
                      if (!data || !data.success) {
                          console.error('Error saving active state of config.', data);
                          self.checked = !self.checked;
                      }
                  })
                  .catch((error) => {
                      console.error('Error saving active state of config.', error);
                  });
          });

          // Dialog form submission
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
                  row.find('.provider').text(formdata.get('provider'));
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

          // Toggle appropriate fields for each provider
          $('.table-config').on('change', '.provider', function () {
            $('tr[class^=provider-]').hide();
            $('tr.provider-' + $(this).val()).show();
          });

          // Reset last exported progress
          $('.table-config').on('click', '.last-exported-value button', function () {
              var btn = $(this);
              var configKey = btn.data('configKey');
              var dataType = btn.data('dataType');
              var data = new FormData();
              data.append('security_headers_nonce', $('#security_headers_nonce').val());
              data.append('action', 'dtdr_reset_progress');
              data.append('key', configKey);
              data.append('dataType', dataType);
              $(this).parent().hide();

              fetch(ajaxurl, {
                  method: 'POST',
                  body: data,
              }).then((response) => response.json())
                  .then(() => {
                  })
                  .catch((error) => {
                      console.error('Error resetting progress:', error);
                  });
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
        $configurations_str = get_option( 'dt_data_reporting_configurations' );
        $configurations = json_decode( $configurations_str, true );
        $snapshots_str = get_option( 'dt_data_reporting_snapshots' );
        $snapshots = [];
        if ( !empty( $snapshots_str ) ) {
            $snapshots = json_decode( $snapshots_str );
        }

        if ( empty( $configurations_str ) || !is_array( $configurations ) ) {
            $configurations = [
            'default' => array(),
            ];
        }

          $configurations_ext = apply_filters( 'dt_data_reporting_configurations', array() );

        ?>

        <table class="widefat">
          <thead>
          <tr><th>General Settings</th></tr>
          </thead>
          <tbody>
          <tr>
            <td>
              <form method="POST" action="">
                <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
                <input type="hidden" name="action" value="dtdr_save_settings" />

                <div>
                  <h3>Enable Snapshot Generation</h3>
                  <input type="checkbox" id="interval_month" name="interval[]" value="month"
                    <?php checked( in_array( 'month', $snapshots ) ); ?>>
                  <label for="interval_month">Month</label><br>

                  <input type="checkbox" id="interval_quarter" name="interval[]" value="quarter"
                    <?php checked( in_array( 'quarter', $snapshots ) ); ?>>
                  <label for="interval_quarter">Quarter</label><br>

                  <input type="checkbox" id="interval_year" name="interval[]" value="year"
                    <?php checked( in_array( 'year', $snapshots ) ); ?>>
                  <label for="interval_year">Year</label><br>
                </div>

                <button type="submit" class="button button-primary">Save Settings</button>
              </form>
            </td>
          </tr>
          </tbody>
        </table>
        <br/>

        <!-- Custom Configuration -->
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
                             <?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'checked' : '' ?>
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

        <!-- External Configurations -->
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
              <td><a href="javascript:;" class="edit-trigger" data-key="<?php echo esc_attr( $key ) ?>">View Details</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <br>
      <?php endif; ?>

        <?php $this->edit_dialogs( $configurations ); ?>
        <?php $this->external_config_dialogs( $configurations_ext ); ?>
        <?php
    }

    public function edit_dialogs( $configurations ) {
          $providers = apply_filters( 'dt_data_reporting_providers', array() );

          $post_types = DT_Posts::get_post_types();

          echo "<div style='display:none;'>";
        foreach ( $configurations as $key => $config ):
            $config['key'] = $key;
            $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>

        <div class="dialog" id="dialog-<?php echo esc_attr( $key ) ?>">
          <form method="POST" action="">
                  <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
            <input type="hidden" name="action" value="dtdr_save_config" />
            <input type="hidden" name="key" value="<?php echo esc_attr( $key ) ?>" />
            <h2 class="nav-tab-wrapper">
              <a href="#dlg-tab-general-<?php echo esc_attr( $key )?>" class="nav-tab">General</a>
              <a href="#dlg-tab-provider-<?php echo esc_attr( $key )?>" class="nav-tab">Provider</a>
              <a href="#dlg-tab-data-types-<?php echo esc_attr( $key )?>" class="nav-tab">Data Types</a>
            </h2>
            <div class="wrap">
              <!-- General Tab -->
              <div id="dlg-tab-general-<?php echo esc_attr( $key ) ?>" class="dlg-tab-content">
                <table class="form-table table-config">
                  <tr>
                    <th>
                      <label for="dlg_name_<?php echo esc_attr( $key ) ?>">Name</label>
                    </th>
                    <td>
                      <input type="text"
                             name="name"
                             id="dlg_name_<?php echo esc_attr( $key ) ?>"
                             value="<?php echo esc_attr( isset( $config['name'] ) ? $config['name'] : '' ) ?>"
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
                                     <?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'checked' : '' ?>
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
              <!-- Provider Tab -->
              <div id="dlg-tab-provider-<?php echo esc_attr( $key ) ?>" class="dlg-tab-content" style="display: none;">
                <table class="form-table table-config">
                  <tr>
                    <th>
                      <label for="dlg_provider_<?php echo esc_attr( $key ) ?>">Provider</label>
                    </th>
                    <td>
                      <select name="provider"
                              id="dlg_provider_<?php echo esc_attr( $key ) ?>"
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
                      <label for="dlg_endpoint_url_<?php echo esc_attr( $key ) ?>">Endpoint URL</label>
                    </th>
                    <td>
                      <input type="text"
                             name="url"
                             id="dlg_endpoint_url_<?php echo esc_attr( $key ) ?>"
                             value="<?php echo esc_attr( isset( $config['url'] ) ? $config['url'] : '' ) ?>"
                             style="width: 100%;" />
                      <div class="muted">API endpoint that should receive your data in JSON format. With a Google Cloud setup, this would be the URL for an HTTP Cloud Function.</div>
                    </td>
                  </tr>
                  <tr class="provider-api <?php echo $config_provider == 'api' ? '' : 'hide' ?>">
                    <th>
                      <label for="dlg_token_<?php echo esc_attr( $key ) ?>">Token</label>
                    </th>
                    <td>
                      <input type="text"
                             name="token"
                             id="dlg_token_<?php echo esc_attr( $key ) ?>"
                             value="<?php echo esc_attr( isset( $config['token'] ) ? $config['token'] : '' ) ?>"
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
                              <label for="dlg_<?php echo esc_attr( $field_key ) ?>_<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $field['label'] ) ?></label>
                            </th>
                            <td>
                                        <?php if ( $field['type'] == 'text' ): ?>
                                <input type="text"
                                       name="<?php echo esc_attr( $field_key ) ?>"
                                       id="dlg_<?php echo esc_attr( $field_key ) ?>_<?php echo esc_attr( $key ) ?>"
                                       value="<?php echo esc_attr( isset( $config[$field_key] ) ? $config[$field_key] : '' ) ?>"
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
                </table>
              </div>
              <!-- Data Types Tab -->
              <div id="dlg-tab-data-types-<?php echo esc_attr( $key ) ?>" class="dlg-tab-content" style="display: none;">
                      <?php foreach ( $post_types as $post_type ): ?>
                            <?php
                            $post_type_settings = DT_Posts::get_post_settings( $post_type );
                            $post_type_label = $post_type_settings['label_plural'];
                            $activity_type = rtrim( $post_type, 's' ) . '_activity';
                            $snapshot_type = rtrim( $post_type, 's' ) . '_snapshots';
                            $post_schedule_enabled = isset( $config['data_types'][$post_type] ) && isset( $config['data_types'][$post_type]['schedule'] ) && $config['data_types'][$post_type]['schedule'] == 'daily';
                            $activity_schedule_enabled = isset( $config['data_types'][$activity_type] ) && isset( $config['data_types'][$activity_type]['schedule'] ) && $config['data_types'][$activity_type]['schedule'] == 'daily';
                            $snapshot_schedule_enabled = isset( $config['data_types'][$snapshot_type] ) && isset( $config['data_types'][$snapshot_type]['schedule'] ) && $config['data_types'][$snapshot_type]['schedule'] == 'daily';
                            ?>
                  <table class="widefat striped table-config accordion data-type-config-table <?php echo $post_schedule_enabled ? '' : 'collapsed' ?>">
                    <thead>
                    <tr>
                      <td><a href="javascript:;" class="toggle">
                            <?php echo esc_html( $post_type_label ) ?>
                          <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>" class="icon closed"/>
                          <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>" class="icon open"/>
                        </a></td>
                    </tr>
                    </thead>
                    <tbody>
                            <?php $this->post_type_config_settings( $config, $post_type ) ?>
                    </tbody>
                  </table>
                  <table class="widefat striped table-config accordion data-type-config-table <?php echo $activity_schedule_enabled ? '' : 'collapsed' ?>">
                    <thead>
                    <tr>
                      <td><a href="javascript:;" class="toggle">
                            <?php echo esc_html( $post_type_label ) ?> Activity
                          <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>" class="icon closed"/>
                          <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>" class="icon open"/>
                        </a></td>
                    </tr>
                    </thead>
                    <tbody>
                            <?php $this->post_type_config_settings( $config, $activity_type ) ?>
                    </tbody>
                  </table>
                  <table class="widefat striped table-config accordion data-type-config-table <?php echo $snapshot_schedule_enabled ? '' : 'collapsed' ?>">
                    <thead>
                    <tr>
                      <td><a href="javascript:;" class="toggle">
                            <?php echo esc_html( $post_type_label ) ?> Snapshots
                          <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>" class="icon closed"/>
                          <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>" class="icon open"/>
                        </a></td>
                    </tr>
                    </thead>
                    <tbody>
                            <?php $this->post_type_config_settings( $config, $snapshot_type ) ?>
                    </tbody>
                  </table>
                <?php endforeach; ?>
              </div>
            </div>

            <br>
            <button type="submit" class="button right">Save Settings</button>
          </form>
        </div>
            <?php endforeach;
          echo '</div>';
    }

    public function external_config_dialogs( $configurations ) {
        if ( empty( $configurations ) ) {
            return;
        }
          $providers = apply_filters( 'dt_data_reporting_providers', array() );

          $post_types = DT_Posts::get_post_types();

          echo "<div style='display:none;'>";

        foreach ( $configurations as $key => $config ):
            $config['key'] = $key;

            $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
            $default_type_config = [
            'all_data' => 0,
            'limit' => 500
            ];
            $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>

      <div class="dialog" id="dialog-<?php echo esc_attr( $key ) ?>">
                <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
        <input type="hidden" name="action" value="dtdr_save_config" />
        <input type="hidden" name="key" value="<?php echo esc_attr( $key ) ?>" />
        <h2 class="nav-tab-wrapper">
          <a href="#dlg-tab-general-<?php echo esc_attr( $key )?>" class="nav-tab">General</a>
          <a href="#dlg-tab-data-types-<?php echo esc_attr( $key )?>" class="nav-tab">Data Types</a>
        </h2>
        <div class="wrap">
          <!-- General Tab -->
          <div id="dlg-tab-general-<?php echo esc_attr( $key ) ?>" class="dlg-tab-content">
            <table class="form-table table-config">
              <tr>
                <th>
                  <label>Name</label>
                </th>
                <td>
                        <?php echo esc_html( isset( $config['name'] ) ? $config['name'] : '' ) ?>
                </td>
              </tr>
              <tr>
                <th>
                  <label>Endpoint URL</label>
                </th>
                <td>
                        <?php echo esc_html( isset( $config['url'] ) ? $config['url'] : '' ) ?>
                </td>
              </tr>

                    <?php if ( isset( $config['token'] ) ): ?>
                <tr>
                  <th>
                    <label>Token</label>
                  </th>
                  <td>
                        <?php echo esc_html( isset( $config['token'] ) ? $config['token'] : '' ) ?>
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
          </div>
          <!-- Data Types Tab -->
          <div id="dlg-tab-data-types-<?php echo esc_attr( $key ) ?>" class="dlg-tab-content" style="display: none;">
                  <?php foreach ( $post_types as $post_type ): ?>
                        <?php
                        $post_type_settings = DT_Posts::get_post_settings( $post_type );
                        $post_type_label = $post_type_settings['label_plural'];
                        $activity_type = rtrim( $post_type, 's' ) . '_activity';
                        $post_schedule_enabled = isset( $config['data_types'][$post_type] ) && isset( $config['data_types'][$post_type]['schedule'] ) && $config['data_types'][$post_type]['schedule'] == 'daily';
                        $activity_schedule_enabled = isset( $config['data_types'][$activity_type] ) && isset( $config['data_types'][$activity_type]['schedule'] ) && $config['data_types'][$activity_type]['schedule'] == 'daily';
                        ?>
              <table class="widefat striped table-config accordion data-type-config-table <?php echo $post_schedule_enabled ? '' : 'collapsed' ?>">
                <thead>
                <tr>
                  <td><a href="javascript:;" class="toggle">
                        <?php echo esc_html( $post_type_label ) ?>
                    </a></td>
                </tr>
                </thead>
                <tbody>
                        <?php $this->post_type_config_settings_external( $config, $post_type ) ?>
                </tbody>
              </table>
              <table class="widefat striped table-config accordion data-type-config-table <?php echo $activity_schedule_enabled ? '' : 'collapsed' ?>">
                <thead>
                <tr>
                  <td><a href="javascript:;" class="toggle">
                        <?php echo esc_html( $post_type_label ) ?> Activity
                    </a></td>
                </tr>
                </thead>
                <tbody>
                        <?php $this->post_type_config_settings_external( $config, $activity_type ) ?>
                </tbody>
              </table>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
            <?php endforeach;

          echo '</div>';
    }

    public function post_type_config_settings( $config, $data_type ) {
          $key = $config['key'];
          $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
          $default_type_config = [
          'all_data' => 0,
          'limit' => 500
          ];
          $type_config =isset( $type_configs[$data_type] ) ? $type_configs[$data_type] : $default_type_config;

          $config_progress = json_decode( get_option( 'dt_data_reporting_configurations_progress' ), true );

          $export_logs_str = get_option( 'dt_data_reporting_export_logs' );
          $export_logs = json_decode( $export_logs_str, true );

          $allowed_html = array(
          'a' => array(
            'href' => array(),
            'title' => array()
          ),
          );
            ?>
        <tr>
          <td>
            <label class="label">Export Style</label>
            <label class="radio">
              <input type="radio"
                     name="data_types[<?php echo esc_attr( $data_type ) ?>][all_data]"
                     value="1"
                     class="data-type-all-data"
                   <?php echo $type_config['all_data'] == 1 ? 'checked' : '' ?>
                     />
              All Data
              <div class="muted">Sends all data whenever an export is run.</div>
            </label>
            <label class="radio">
              <input type="radio"
                     name="data_types[<?php echo esc_attr( $data_type ) ?>][all_data]"
                     value="0"
                     class="data-type-all-data"
                   <?php echo $type_config['all_data'] == 0 ? 'checked' : '' ?>
                     />
              Last Updated
              <div class="muted">Only sends the data that has changed since the last export with a maximum number of records configured below.</div>
            </label>


          <?php if ( isset( $config_progress[$key] ) && isset( $config_progress[$key][$data_type] ) ): ?>
              <div class="last-exported-value">
                Exported Until: <?php echo esc_html( $config_progress[$key][$data_type] ) ?>
                <button type="button"
                        data-config-key="<?php echo esc_attr( $key ) ?>"
                        data-data-type="<?php echo esc_attr( $data_type ) ?>">Reset</button>
              </div>
            <?php endif;  ?>
          </td>
        </tr>
        <tr>
          <td>
            <label class="label">Max Records</label>
            <input type="text"
                   placeholder="Max records"
                   name="data_types[<?php echo esc_attr( $data_type ) ?>][limit]"
                   class="data-type-limit"
                   value="<?php echo esc_attr( isset( $type_config['limit'] ) ? $type_config['limit'] : $default_type_config['limit'] ) ?>"
            />
            <div class="muted">When exporting only Last Updated records, this is the max number of records that will be sent at one time.</div>

          </td>
        </tr>

        <tr>
          <td>
            <label class="label">Export Daily</label>
            <span class="switch">
              <input type="checkbox"
                     class="config-enable-checkbox"
                     id="dlg_data_types_<?php echo esc_attr( $data_type ) ?>_schedule_<?php echo esc_attr( $key ) ?>"
                     name="data_types[<?php echo esc_attr( $data_type ) ?>][schedule]"
                     value="daily"
                   <?php echo isset( $type_config['schedule'] ) && $type_config['schedule'] == 'daily' ? 'checked' : '' ?>
              />
              <label for="dlg_data_types_<?php echo esc_attr( $data_type ) ?>_schedule_<?php echo esc_attr( $key ) ?>">
                <span></span>
              </label>
            </span>
            <div class="muted">Enable scheduled exports for this type to be run on a daily basis. Data will automatically be sent to your provider without manual triggering it.</div>

          </td>
        </tr>

        <?php if ( isset( $export_logs[$key] ) && isset( $export_logs[$key][$data_type] ) ): ?>
        <tr>
          <td>
            <div class="export-logs">
              <button type="button">View Last Export Logs</button>
              <div class="log-messages" style="display: none;">
                <div class="result">Result: <?php echo esc_html( $export_logs[$key][$data_type]['success'] ? 'Success' : 'Fail' ) ?></div>
                <div class="date">Date: <?php echo esc_html( isset( $export_logs[$key][$data_type]['date'] ) ? $export_logs[$key][$data_type]['date'] : 'Unknown' ) ?></div>
                <ul class="api-log">
                  <?php foreach ( $export_logs[$key][$data_type]['messages'] as $message ) {
                        $message_type = isset( $message['type'] ) ? $message['type'] : '';
                        $content = isset( $message['message'] ) ? $message['message'] : '';
                        echo "<li class='" . esc_attr( $message_type ) . "'>" . wp_kses( $content, $allowed_html ) . '</li>';
                  } ?>
                </ul>
              </div>
            </div>
          </td>
        </tr>
        <?php endif; ?>
                    <?php
    }

    public function post_type_config_settings_external( $config, $data_type ) {
          $key = $config['key'];
          $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
          $default_type_config = [
          'all_data' => 0,
          'limit' => 500
          ];
          $type_config =isset( $type_configs[$data_type] ) ? $type_configs[$data_type] : $default_type_config;

          $config_progress = json_decode( get_option( 'dt_data_reporting_configurations_progress' ), true );

          $export_logs_str = get_option( 'dt_data_reporting_export_logs' );
          $export_logs = json_decode( $export_logs_str, true );

          $allowed_html = array(
          'a' => array(
            'href' => array(),
            'title' => array()
          ),
          );
            ?>
        <tr>
          <td>
            <label class="label">Export Style</label>
          <?php if ( $type_config['all_data'] == 1 ): ?>
              All Data
            <?php else : ?>
              Last Updated
              (Max records: <?php echo esc_html( isset( $type_config['limit'] ) ? $type_config['limit'] : $default_type_config['limit'] ) ?>)

                <?php if ( isset( $config_progress[$key] ) && isset( $config_progress[$key][$data_type] ) ): ?>
                <div class="last-exported-value">
                  Exported Until: <?php echo esc_html( $config_progress[$key][$data_type] ) ?>
                  <button type="button"
                          data-config-key="<?php echo esc_attr( $key ) ?>"
                          data-data-type="<?php echo esc_attr( $data_type ) ?>">Reset</button>
                </div>
              <?php endif; ?>
            <?php endif; ?>


          <?php if ( isset( $config_progress[$key] ) && isset( $config_progress[$key][$data_type] ) ): ?>
              <div class="last-exported-value">
                Exported Until: <?php echo esc_html( $config_progress[$key][$data_type] ) ?>
                <button type="button"
                        data-config-key="<?php echo esc_attr( $key ) ?>"
                        data-data-type="<?php echo esc_attr( $data_type ) ?>">Reset</button>
              </div>
            <?php endif;  ?>
          </td>
        </tr>

        <?php if ( isset( $type_config['schedule'] ) && $type_config['schedule'] == 'daily' ): ?>
          <tr>
            <td>
                <p>&check; Automatic daily export</p>
            </td>
          </tr>
        <?php endif; ?>

        <?php if ( isset( $export_logs[$key] ) && isset( $export_logs[$key][$data_type] ) ): ?>
        <tr>
          <td>
            <div class="export-logs">
              <button type="button">View Last Export Logs</button>
              <div class="log-messages" style="display: none;">
                <div class="result">Result: <?php echo esc_html( $export_logs[$key][$data_type]['success'] ? 'Success' : 'Fail' ) ?></div>
                <div class="date">Date: <?php echo esc_html( isset( $export_logs[$key][$data_type]['date'] ) ? $export_logs[$key][$data_type]['date'] : 'Unknown' ) ?></div>
                <ul class="api-log">
                  <?php foreach ( $export_logs[$key][$data_type]['messages'] as $message ) {
                        $message_type = isset( $message['type'] ) ? $message['type'] : '';
                        $content = isset( $message['message'] ) ? $message['message'] : '';
                        echo "<li class='" . esc_attr( $message_type ) . "'>" . wp_kses( $content, $allowed_html ) . '</li>';
                  } ?>
                </ul>
              </div>
            </div>
          </td>
        </tr>
        <?php endif; ?>
                    <?php
    }

    public function save_settings() {
        if ( empty( $_POST ) ) {
            return;
        }
        if ( !isset( $_POST['security_headers_nonce'] ) || !wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
            return;
        }

        $action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : null;
        if ( $action == 'resetprogress' ) {
            if ( isset( $_POST['configKey'] ) && isset( $_POST['dataType'] ) ) {
                $config_progress = json_decode( get_option( 'dt_data_reporting_configurations_progress' ), true );
                $config_key = sanitize_key( wp_unslash( $_POST['configKey'] ) );
                $data_type = sanitize_key( wp_unslash( $_POST['dataType'] ) );
                if ( isset( $config_progress[$config_key] ) ) {
                    unset( $config_progress[$config_key][$data_type] );
                    update_option( 'dt_data_reporting_configurations_progress', json_encode( $config_progress ) );
                }
            }
        } else if ( $action === 'dtdr_save_settings' ) {
            $intervals = isset( $_POST['interval'] )
              ? $this->sanitize_array_field( wp_unslash( $_POST['interval'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
              : [];
            update_option( 'dt_data_reporting_snapshots', json_encode( $intervals ) );
        } else {
          //configurations
            if ( isset( $_POST['configurations'] ) ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                update_option( 'dt_data_reporting_configurations', json_encode( $this->sanitize_text_or_array_field( wp_unslash( $_POST['configurations'] ) ) ) );
            }


            echo '<div class="notice notice-success notice-dt-data-reporting is-dismissible" data-notice="dt-data-reporting"><p>Settings Saved</p></div>';
        }
    }

    private function sanitize_text_or_array_field( $array_or_string ) {
        if ( is_string( $array_or_string ) ) {
            $array_or_string = sanitize_text_field( $array_or_string );
        } elseif ( is_array( $array_or_string ) ) {
            foreach ( $array_or_string as $key => &$value ) {
                if ( is_array( $value ) ) {
                    $value = $this->sanitize_text_or_array_field( $value );
                } else {
                    $value = sanitize_text_field( $value );
                }
            }
        }

        return $array_or_string;
    }

    private function sanitize_array_field( $array )
    {
        if ( !is_array( $array ) ) {
            return [ sanitize_key( $array ) ];
        }
        return array_map( 'sanitize_key', $array );
    }
}
