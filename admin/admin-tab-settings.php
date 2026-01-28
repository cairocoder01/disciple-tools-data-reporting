<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_Settings
{
    public $type = 'contacts';

    public function __construct() {
        // add_action( 'wp_ajax_dtdr_enable_config', [$this, 'enable_config'] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets( $hook ) {
        if ( is_admin() && isset( $_GET['page'] ) && 'disciple-tools-data-reporting' === sanitize_key( wp_unslash( $_GET['page'] ) ) && isset( $_GET['tab'] ) && 'settings' === sanitize_key( wp_unslash( $_GET['tab'] ) ) ) {
            $url = plugin_dir_url( dirname( __DIR__ ) . '/disciple-tools-data-reporting.php' );
            wp_enqueue_style( 'dtdr-admin-settings', $url . 'assets/css/admin-settings.css', [], '1.0.0' );
            wp_enqueue_script( 'dtdr-admin-settings', $url . 'assets/js/admin-settings.js', [ 'jquery' ], '1.0.0', true );
        }
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
        <div class="config-list-view">
          <!-- General Settings Section -->
          <div class="list-view-section">
            <div class="general-settings-card">
              <h4>General Settings</h4>
              <form method="POST" action="">
                <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
                <input type="hidden" name="action" value="dtdr_save_settings" />

                <div class="field-group">
                  <label class="label">Enable Snapshot Generation</label>
                  <p class="description">Select the intervals for which snapshots should be generated.</p>
                  <div class="snapshot-options">
                    <label for="interval_month">
                      <input type="checkbox" id="interval_month" name="interval[]" value="month" <?php checked( in_array( 'month', $snapshots ) ); ?>>
                      Month
                    </label>
                    <label for="interval_quarter">
                      <input type="checkbox" id="interval_quarter" name="interval[]" value="quarter" <?php checked( in_array( 'quarter', $snapshots ) ); ?>>
                      Quarter
                    </label>
                    <label for="interval_year">
                      <input type="checkbox" id="interval_year" name="interval[]" value="year" <?php checked( in_array( 'year', $snapshots ) ); ?>>
                      Year
                    </label>
                  </div>
                </div>

                <button type="submit" class="button button-primary">Save General Settings</button>
              </form>
            </div>
          </div>

          <!-- Custom Configurations Section -->
          <div class="list-view-section">
            <h3>Custom Configurations</h3>
            <div class="config-cards-grid">
              <?php foreach ( $configurations as $key => $config ): ?>
                <?php $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>
                <div class="config-card" id="config-list-row-<?php echo esc_attr( $key ) ?>">
                  <div class="config-card-header">
                    <h4 class="name"><?php echo esc_html( isset( $config['name'] ) ? $config['name'] : '(new config)' ) ?></h4>
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
                  </div>
                  <div class="config-card-body">
                    <div class="config-card-meta">
                      <span class="label">Provider</span>
                      <span class="value provider"><?php echo esc_html( $config_provider ) ?></span>

                      <?php if ( $config_provider === 'api' && ! empty( $config['url'] ) ) : ?>
                        <span class="label">Endpoint</span>
                        <span class="value url" style="word-break: break-all;"><?php echo esc_url( $config['url'] ) ?></span>
                      <?php endif; ?>

                      <span class="label">Status</span>
                      <span class="value status-text"><?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'Enabled' : 'Disabled' ?></span>
                    </div>
                  </div>
                  <div class="config-card-footer">
                    <span></span>
                    <a href="javascript:;" class="button button-secondary edit-trigger" data-key="<?php echo esc_attr( $key ) ?>">Edit Configuration</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- External Configurations Section -->
          <?php if ( !empty( $configurations_ext ) ): ?>
            <div class="list-view-section">
              <h3>External Configurations</h3>
              <div class="config-cards-grid">
                <?php foreach ( $configurations_ext as $key => $config ): ?>
                  <?php $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>
                  <div class="config-card">
                    <div class="config-card-header">
                      <h4 class="name"><?php echo esc_html( $config['name'] ) ?></h4>
                      <span class="status-badge <?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'active' : 'inactive' ?>">
                        <?php echo isset( $config['active'] ) && $config['active'] == 1 ? '&check; Active' : '&cross; Inactive' ?>
                      </span>
                    </div>
                    <div class="config-card-body">
                      <div class="config-card-meta">
                        <span class="label">Provider</span>
                        <span class="value"><?php echo esc_html( $config_provider ) ?></span>

                        <?php if ( $config_provider === 'api' && ! empty( $config['url'] ) ) : ?>
                          <span class="label">Endpoint</span>
                          <span class="value" style="word-break: break-all;"><?php echo esc_url( $config['url'] ) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="config-card-footer">
                      <span></span>
                      <a href="javascript:;" class="button button-secondary edit-trigger" data-key="<?php echo esc_attr( $key ) ?>">View Details</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <?php $this->edit_forms( $configurations ); ?>
        <?php $this->external_config_forms( $configurations_ext ); ?>
        <?php
    }

    public function edit_forms( $configurations ) {
          $providers = apply_filters( 'dt_data_reporting_providers', array() );
          $post_types = DT_Posts::get_post_types();

        foreach ( $configurations as $key => $config ):
            $config['key'] = $key;
            $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>

        <a href="#" class="back-to-list">&larr; Back to configurations</a>
        <div class="config-edit-view" id="edit-view-<?php echo esc_attr( $key ) ?>">
          <form method="POST" action="">
            <div class="config-edit-view-container">
              <div class="config-edit-sidebar">
                <ul>
                  <li><a href="#section-general-<?php echo esc_attr( $key ) ?>" class="sidebar-nav">General Settings</a></li>
                  <li><a href="#section-provider-<?php echo esc_attr( $key ) ?>" class="sidebar-nav">Connection Settings</a></li>
                  <li class="sidebar-section-header">Data Export</li>
                  <?php foreach ( $post_types as $post_type ):
                    $post_type_settings = DT_Posts::get_post_settings( $post_type );
                    $post_type_label = $post_type_settings['label_plural'];
                    ?>
                    <li class="sidebar-sub-item">
                      <a href="#section-data-<?php echo esc_attr( $post_type ) ?>-<?php echo esc_attr( $key ) ?>" class="sidebar-nav"><?php echo esc_html( $post_type_label ) ?></a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="config-edit-content">
                <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
                <input type="hidden" name="action" value="dtdr_save_config" />
                <input type="hidden" name="key" value="<?php echo esc_attr( $key ) ?>" />

                <!-- General Section -->
                <div id="section-general-<?php echo esc_attr( $key ) ?>" class="config-section">
                  <h2>General Settings</h2>
                  <table class="form-table">
                    <tr>
                      <th><label for="dlg_name_<?php echo esc_attr( $key ) ?>">Name</label></th>
                      <td>
                        <input type="text" name="name" id="dlg_name_<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( isset( $config['name'] ) ? $config['name'] : '' ) ?>" class="regular-text" />
                        <p class="description">Label to identify this configuration.</p>
                      </td>
                    </tr>
                    <tr>
                      <th><label for="dlg_enabled_<?php echo esc_attr( $key ) ?>">Enabled</label></th>
                      <td>
                        <span class="switch">
                          <input type="checkbox" class="config-enable-checkbox" id="dlg_enabled_<?php echo esc_attr( $key ) ?>" name="enabled" <?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'checked' : '' ?> />
                          <label for="dlg_enabled_<?php echo esc_attr( $key ) ?>"><span></span></label>
                        </span>
                        <p class="description">When checked, this configuration is active and will be exported.</p>
                      </td>
                    </tr>
                  </table>
                </div>

                <!-- Provider Section -->
                <div id="section-provider-<?php echo esc_attr( $key ) ?>" class="config-section" style="display: none;">
                  <h2>Connection Settings</h2>
                  <table class="form-table">
                    <tr>
                      <th><label for="dlg_provider_<?php echo esc_attr( $key ) ?>">Provider</label></th>
                      <td>
                        <select name="provider" id="dlg_provider_<?php echo esc_attr( $key ) ?>" class="provider">
                          <option value="api" <?php echo $config_provider == 'api' ? 'selected' : '' ?>>API</option>
                          <?php if ( !empty( $providers ) ): ?>
                            <?php foreach ( $providers as $provider_key => $provider ): ?>
                              <option value="<?php echo esc_attr( $provider_key ) ?>" <?php echo $config_provider == $provider_key ? 'selected' : '' ?>>
                                <?php echo esc_html( $provider['name'] ) ?>
                              </option>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </select>
                      </td>
                    </tr>
                    <tr class="provider-field provider-api <?php echo $config_provider == 'api' ? '' : 'hide' ?>">
                      <th><label for="dlg_endpoint_url_<?php echo esc_attr( $key ) ?>">Endpoint URL</label></th>
                      <td>
                        <input type="text" name="url" id="dlg_endpoint_url_<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( isset( $config['url'] ) ? $config['url'] : '' ) ?>" class="large-text" />
                        <p class="description">API endpoint that should receive your data in JSON format.</p>
                      </td>
                    </tr>
                    <tr class="provider-field provider-api <?php echo $config_provider == 'api' ? '' : 'hide' ?>">
                      <th><label for="dlg_token_<?php echo esc_attr( $key ) ?>">Token</label></th>
                      <td>
                        <input type="text" name="token" id="dlg_token_<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( isset( $config['token'] ) ? $config['token'] : '' ) ?>" class="large-text" />
                        <p class="description">Optional, for Authorization header.</p>
                      </td>
                    </tr>

                    <?php if ( !empty( $providers ) ) {
                      foreach ( $providers as $provider_key => $provider ) {
                        if ( isset( $provider['fields'] ) && !empty( $provider['fields'] ) ) {
                          foreach ( $provider['fields'] as $field_key => $field ) { ?>
                            <tr class="provider-field provider-<?php echo esc_attr( $provider_key ) ?> <?php echo $provider_key == $config_provider ? '' : 'hide' ?>">
                              <th><label for="dlg_<?php echo esc_attr( $field_key ) ?>_<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $field['label'] ) ?></label></th>
                              <td>
                                <?php if ( $field['type'] == 'text' ): ?>
                                  <input type="text" name="<?php echo esc_attr( $field_key ) ?>" id="dlg_<?php echo esc_attr( $field_key ) ?>_<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( isset( $config[$field_key] ) ? $config[$field_key] : '' ) ?>" class="large-text" />
                                <?php endif; ?>
                                <?php if ( isset( $field['helpText'] ) ): ?>
                                  <p class="description"><?php echo esc_html( $field['helpText'] ) ?></p>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php }
                        }
                      }
                    } ?>
                  </table>
                </div>

                <!-- Data Types Section -->
                <?php foreach ( $post_types as $post_type ):
                  $post_type_settings = DT_Posts::get_post_settings( $post_type );
                  $post_type_label = $post_type_settings['label_plural'];
                  $activity_type = rtrim( $post_type, 's' ) . '_activity';
                  $snapshot_type = rtrim( $post_type, 's' ) . '_snapshots';
                  ?>
                  <div id="section-data-<?php echo esc_attr( $post_type ) ?>-<?php echo esc_attr( $key ) ?>" class="config-section" style="display: none;">
                    <h2><?php echo esc_html( $post_type_label ) ?> Export Settings</h2>
                    <p class="description">Configure which <?php echo esc_html( strtolower( $post_type_label ) ) ?> data should be exported.</p>
                    <div class="data-types-grid">
                      <div class="data-type-card">
                        <h4><?php echo esc_html( $post_type_label ) ?></h4>
                        <?php $this->post_type_config_settings( $config, $post_type ) ?>
                      </div>
                      <div class="data-type-card">
                        <h4><?php echo esc_html( $post_type_label ) ?> Activity</h4>
                        <?php $this->post_type_config_settings( $config, $activity_type ) ?>
                      </div>
                      <div class="data-type-card">
                        <h4><?php echo esc_html( $post_type_label ) ?> Snapshots</h4>
                        <?php $this->post_type_config_settings( $config, $snapshot_type ) ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="config-edit-footer">
              <button type="submit" class="button button-primary button-large">Save All Settings</button>
            </div>
          </form>
        </div>
        <?php endforeach;
    }

    public function external_config_forms( $configurations ) {
        if ( empty( $configurations ) ) {
            return;
        }
          $providers = apply_filters( 'dt_data_reporting_providers', array() );
          $post_types = DT_Posts::get_post_types();

        foreach ( $configurations as $key => $config ):
            $config['key'] = $key;
            $config_provider = isset( $config['provider'] ) ? $config['provider'] : 'api'; ?>

      <a href="#" class="back-to-list">&larr; Back to configurations</a>
      <div class="config-edit-view" id="edit-view-<?php echo esc_attr( $key ) ?>">
        <div class="config-edit-view-container">
          <div class="config-edit-sidebar">
            <ul>
              <li><a href="#section-general-<?php echo esc_attr( $key ) ?>" class="sidebar-nav">General Info</a></li>
              <li class="sidebar-section-header">Data Export</li>
              <?php foreach ( $post_types as $post_type ):
                $post_type_settings = DT_Posts::get_post_settings( $post_type );
                $post_type_label = $post_type_settings['label_plural'];
                ?>
                <li class="sidebar-sub-item">
                  <a href="#section-data-<?php echo esc_attr( $post_type ) ?>-<?php echo esc_attr( $key ) ?>" class="sidebar-nav"><?php echo esc_html( $post_type_label ) ?></a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="config-edit-content">

            <!-- General Section -->
            <div id="section-general-<?php echo esc_attr( $key ) ?>" class="config-section">
              <h2>General Information</h2>
              <table class="form-table">
                <tr>
                  <th><label>Name</label></th>
                  <td><?php echo esc_html( isset( $config['name'] ) ? $config['name'] : '' ) ?></td>
                </tr>
                <tr>
                  <th><label>Endpoint URL</label></th>
                  <td><?php echo esc_html( isset( $config['url'] ) ? $config['url'] : '' ) ?></td>
                </tr>
                <?php if ( isset( $config['token'] ) ): ?>
                  <tr>
                    <th><label>Token</label></th>
                    <td><?php echo esc_html( $config['token'] ) ?></td>
                  </tr>
                <?php endif; ?>
                <tr>
                  <th><label>Status</label></th>
                  <td><?php echo isset( $config['active'] ) && $config['active'] == 1 ? 'Active' : 'Inactive' ?></td>
                </tr>
              </table>
            </div>

            <!-- Data Types Section -->
            <?php foreach ( $post_types as $post_type ):
              $post_type_settings = DT_Posts::get_post_settings( $post_type );
              $post_type_label = $post_type_settings['label_plural'];
              $activity_type = rtrim( $post_type, 's' ) . '_activity';
              $snapshot_type = rtrim( $post_type, 's' ) . '_snapshots';
              ?>
              <div id="section-data-<?php echo esc_attr( $post_type ) ?>-<?php echo esc_attr( $key ) ?>" class="config-section" style="display: none;">
                <h2><?php echo esc_html( $post_type_label ) ?> Export Settings</h2>
                <div class="data-types-grid">
                  <div class="data-type-card">
                    <h4><?php echo esc_html( $post_type_label ) ?></h4>
                    <?php $this->post_type_config_settings_external( $config, $post_type ) ?>
                  </div>
                  <div class="data-type-card">
                    <h4><?php echo esc_html( $post_type_label ) ?> Activity</h4>
                    <?php $this->post_type_config_settings_external( $config, $activity_type ) ?>
                  </div>
                  <div class="data-type-card">
                    <h4><?php echo esc_html( $post_type_label ) ?> Snapshots</h4>
                    <?php $this->post_type_config_settings_external( $config, $snapshot_type ) ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endforeach;
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
        <div class="field-group">
            <label class="label">Daily Export</label>
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
            <p class="muted">Enable scheduled exports for this type to be run on a daily basis. Data will automatically be sent to your provider without manual triggering it.</p>
        </div>

        <div class="field-group">
            <label class="label">Export Style</label>
            <div class="export-style-options">
                <label>
                    <input type="radio" name="data_types[<?php echo esc_attr( $data_type ) ?>][all_data]" value="1" <?php checked( $type_config['all_data'], 1 ); ?>>
                    <strong>All Data</strong>
                    <p class="muted" style="margin-top: 5px;">Sends all data whenever an export is run.</p>
                </label>
                <label style="margin-top: 10px; display: block;">
                    <input type="radio" name="data_types[<?php echo esc_attr( $data_type ) ?>][all_data]" value="0" <?php checked( $type_config['all_data'], 0 ); ?>>
                    <strong>Last Updated Only</strong>
                    <p class="muted" style="margin-top: 5px;">Only sends the data that has changed since the last export with a maximum number of records configured below.</p>
                </label>
            </div>
        </div>

        <div class="field-group">
            <label class="label">Max Records</label>
            <input type="number"
                   name="data_types[<?php echo esc_attr( $data_type ) ?>][limit]"
                   value="<?php echo esc_attr( isset( $type_config['limit'] ) ? $type_config['limit'] : $default_type_config['limit'] ) ?>"
            />
            <p class="muted">When exporting only Last Updated records, this is the max number of records that will be sent at one time.</p>
        </div>

        <?php
        $has_progress = isset( $config_progress[$key] ) && isset( $config_progress[$key][$data_type] );
        $has_logs = isset( $export_logs[$key] ) && isset( $export_logs[$key][$data_type] );
        if ( $has_progress || $has_logs ) :
        ?>
        <div class="field-group">
            <label class="label">Last Export</label>
            <div class="last-export-status-row" style="display: flex; align-items: center; gap: 10px;">
                <?php if ( $has_progress ): ?>
                    <div class="muted last-exported-value">
                        <?php echo esc_html( $config_progress[$key][$data_type] ) ?>
                    </div>
                <?php endif; ?>

                <?php if ( $has_logs ): ?>
                    <div class="status-badge <?php echo $export_logs[$key][$data_type]['success'] ? 'active' : 'inactive' ?>" style="padding: 2px 8px; margin: 0;">
                        <?php echo esc_html( $export_logs[$key][$data_type]['success'] ? 'Success' : 'Fail' ) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="last-export-controls" style="margin-top: 5px; display: flex; gap: 5px; align-items: center;">
                <?php if ( $has_logs ): ?>
                    <div class="export-logs">
                        <button type="button" class="button button-small view-logs-trigger">View Logs</button>
                    </div>
                <?php endif; ?>

                <?php if ( $has_progress ): ?>
                    <div class="last-exported-value">
                        <button type="button" class="button button-small"
                                data-config-key="<?php echo esc_attr( $key ) ?>"
                                data-data-type="<?php echo esc_attr( $data_type ) ?>">Reset</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( $has_logs ): ?>
                <div class="log-messages" style="display: none; font-size: 10px; margin-top: 5px;">
                    <ul class="api-log" style="margin: 0; padding: 10px; background: #eee;">
                        <?php foreach ( array_slice( $export_logs[$key][$data_type]['messages'], -3 ) as $message ) {
                            $content = isset( $message['message'] ) ? $message['message'] : '';
                            echo "<li>" . wp_kses( $content, $allowed_html ) . "</li>";
                        } ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
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
        <div class="field-group">
            <label class="label">Daily Export</label>
            <div><?php echo isset( $type_config['schedule'] ) && $type_config['schedule'] == 'daily' ? '✓ Enabled' : 'Disabled' ?></div>
            <p class="muted">Enable scheduled exports for this type to be run on a daily basis.</p>
        </div>

        <div class="field-group">
            <label class="label">Export Style</label>
            <div><?php echo $type_config['all_data'] == 1 ? 'All Data' : 'Last Updated Only' ?></div>
            <p class="muted"><?php echo $type_config['all_data'] == 1 ? 'Sends all data whenever an export is run.' : 'Only sends the data that has changed since the last export.' ?></p>
        </div>

        <?php if ( $type_config['all_data'] == 0 ): ?>
            <div class="field-group">
                <label class="label">Max Records</label>
                <div><?php echo esc_html( isset( $type_config['limit'] ) ? $type_config['limit'] : $default_type_config['limit'] ) ?></div>
                <p class="muted">Max number of records that will be sent at one time.</p>
            </div>
        <?php endif; ?>

        <?php
        $has_progress = isset( $config_progress[$key] ) && isset( $config_progress[$key][$data_type] );
        $has_logs = isset( $export_logs[$key] ) && isset( $export_logs[$key][$data_type] );
        if ( $has_progress || $has_logs ) :
        ?>
        <div class="field-group">
            <label class="label">Last Export</label>
            <div class="last-export-status-row" style="display: flex; align-items: center; gap: 10px;">
                <?php if ( $has_progress ): ?>
                    <div class="muted last-exported-value">
                        <?php echo esc_html( $config_progress[$key][$data_type] ) ?>
                    </div>
                <?php endif; ?>

                <?php if ( $has_logs ): ?>
                    <div class="status-badge <?php echo $export_logs[$key][$data_type]['success'] ? 'active' : 'inactive' ?>" style="padding: 2px 8px; margin: 0;">
                        <?php echo esc_html( $export_logs[$key][$data_type]['success'] ? 'Success' : 'Fail' ) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="last-export-controls" style="margin-top: 5px; display: flex; gap: 5px; align-items: center;">
                <?php if ( $has_logs ): ?>
                    <div class="export-logs">
                        <button type="button" class="button button-small view-logs-trigger">View Logs</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( $has_logs ): ?>
                <div class="log-messages" style="display: none; font-size: 10px; margin-top: 5px;">
                    <ul class="api-log" style="margin: 0; padding: 10px; background: #eee;">
                        <?php foreach ( array_slice( $export_logs[$key][$data_type]['messages'], -3 ) as $message ) {
                            $content = isset( $message['message'] ) ? $message['message'] : '';
                            echo "<li>" . wp_kses( $content, $allowed_html ) . "</li>";
                        } ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
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
