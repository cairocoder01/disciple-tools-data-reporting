<?php
class DT_Data_Reporting_Tab_Manual_Export
{
    public $token;
    public function __construct( $token ) {
        $this->token = $token;
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
        $preview_link = 'admin.php?page='.$this->token.'&tab=preview&type=';

        $configurations = DT_Data_Reporting_Tools::get_configs();

        $post_types = DT_Posts::get_post_types();
        ?>
        <!-- Box -->
        <table class="widefat striped table-export">
            <thead>
            <th>Default Data Export</th>
            </thead>
            <tbody>
            <?php foreach ( $post_types as $post_type ):
                $post_type_settings = DT_Posts::get_post_settings( $post_type );
                $activity_type = rtrim( $post_type, 's' ) . '_activity';
                ?>
                <tr>
                    <td>
                        Export <?php esc_html_e( $post_type_settings['label_plural'] ) ?>

                        <div class="alignright">
                            <a href="<?php echo esc_attr( $preview_link . $post_type ) ?>">Preview <span class="dashicons dashicons-admin-site-alt3"></span></a> |
                            <a href="<?php echo esc_attr( plugins_url( '../../exports/csv.php?type=' . $post_type, __FILE__ ) ) ?>">CSV <span class="dashicons dashicons-download"></span></a> |
                            <a href="<?php echo esc_attr( plugins_url( '../../exports/json.php?type=' . $post_type, __FILE__ ) ) ?>">JSON <span class="dashicons dashicons-download"></span></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Export <?php esc_html_e( $post_type_settings['label_singular'] ) ?> Activity
                        <div class="alignright">
                            <a href="<?php echo esc_attr( $preview_link . $activity_type ) ?>">Preview <span class="dashicons dashicons-admin-site-alt3"></span></a> |
                            <a href="<?php echo esc_attr( plugins_url( '../../exports/csv.php?type=' . $activity_type, __FILE__ ) ) ?>">CSV <span class="dashicons dashicons-download"></a> |
                            <a href="<?php echo esc_attr( plugins_url( '../../exports/json.php?type=' . $activity_type, __FILE__ ) ) ?>">JSON <span class="dashicons dashicons-download"></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <?php
        if ( !empty( $configurations ) ) {
            echo '<h2>Export Configurations</h2>';
            foreach ( $configurations as $key => $config ) {
                $preview_link_config = 'admin.php?page='.$this->token.'&tab=preview&config='.$key.'&type=';
                $api_action_link_config = 'admin.php?page='.$this->token.'&tab=api-send&config='.$key.'&type=';
                ?>
          <table class="widefat striped table-export-config">
            <thead>
            <th>Configuration: <span class="config-name"><?php echo esc_html( $config['name'] ) ?></span></th>
            </thead>
            <tbody>
                <?php foreach ( $post_types as $post_type ):
                    $post_type_settings = DT_Posts::get_post_settings( $post_type );
                    $activity_type = rtrim( $post_type, 's' ) . '_activity';
                    ?>
                <tr>
                  <td>
                    Export <?php esc_html_e( $post_type_settings['label_plural'] ) ?>

                    <div class="alignright">
                      <a href="<?php echo esc_attr( $preview_link_config . $post_type ) ?>">Preview <span class="dashicons dashicons-admin-site-alt3"></span></a> |
                      <a href="<?php echo esc_attr( $api_action_link_config . $post_type ) ?>">Send Data <span class="dashicons dashicons-migrate"></span></a>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td>
                    Export <?php esc_html_e( $post_type_settings['label_singular'] ) ?> Activity
                    <div class="alignright">
                      <a href="<?php echo esc_attr( $preview_link_config . $activity_type ) ?>">Preview <span class="dashicons dashicons-admin-site-alt3"></span></a> |
                      <a href="<?php echo esc_attr( $api_action_link_config . $activity_type ) ?>">Send Data <span class="dashicons dashicons-migrate"></span></a>
                    </div>
                  </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <br>
                <?php
            }
        }
        ?>
        <?php
    }

}
