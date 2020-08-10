<?php
/**
 * DT_Data_Reporting_Menu class for the admin page
 *
 * @class       DT_Data_Reporting_Menu
 * @version     0.1.0
 * @since       0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/**
 * Initialize menu class
 */
DT_Data_Reporting_Menu::instance();

/**
 * Class DT_Data_Reporting_Menu
 */
class DT_Data_Reporting_Menu {

    public $token = 'DT_Data_Reporting';

    private static $_instance = null;

    /**
     * DT_Data_Reporting_Menu Instance
     *
     * Ensures only one instance of DT_Data_Reporting_Menu is loaded or can be loaded.
     *
     * @return DT_Data_Reporting_Menu instance
     * @since 0.1.0
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );
        add_action( "admin_head", array( $this, "add_styles" ) );
    } // End __construct()


    function add_styles() {
        echo
        '<style>
            body.wp-admin.extensions-dt_page_dt_data_reporting
            #post-body-content {
              overflow-y: auto;
            }
            body.wp-admin.extensions-dt_page_dt_data_reporting
            ul {
              list-style: inherit;
              padding-inline-start: 2em;
              margin: 0;
            }
          </style>';
    }

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_menu_page( __( 'Extensions (DT)', 'disciple_tools' ), __( 'Extensions (DT)', 'disciple_tools' ), 'manage_dt', 'dt_extensions', [ $this, 'extensions_menu' ], 'dashicons-admin-generic', 59 );
        add_submenu_page( 'dt_extensions', __( 'Data Reporting', 'DT_Data_Reporting'), __( 'Data Reporting', 'DT_Data_Reporting'), 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }
        if ( isset( $_GET["type"] ) ) {
            $type = sanitize_key( wp_unslash( $_GET["type"] ) );
        } else {
            $type = null;
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Data Reporting', 'DT_Data_Reporting') ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab <?php ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'General', 'DT_Data_Reporting') ?></a>
                <a href="<?php echo esc_attr( $link ) . 'bigquery' ?>" class="nav-tab <?php ( $tab == 'bigquery' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'BigQuery Schema', 'DT_Data_Reporting') ?></a>
                <?php if ($tab === 'preview' ): ?>
                    <a href="<?php echo esc_attr( $link ) . 'preview' ?>" class="nav-tab <?php ( $tab == 'preview' ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'Preview', 'DT_Data_Reporting') ?></a>
                <?php endif; ?>
                <?php if ($tab === 'api-send' ): ?>
                  <a href="<?php echo esc_attr( $link ) . 'api-send' ?>" class="nav-tab <?php ( $tab == 'api-send' ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'API Send', 'DT_Data_Reporting') ?></a>
                <?php endif; ?>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Data_Reporting_Tab_General( $this->token );
                    $object->content();
                    break;
                case "bigquery":
                    $object = new DT_Data_Reporting_Tab_BigQuery( $this->token );
                    $object->content();
                    break;
                case "preview":
                    $object = new DT_Data_Reporting_Tab_Preview( $type );
                    $object->content();
                    break;
                case "api-send":
                    $object = new DT_Data_Reporting_Tab_API( $type );
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }
}

class DT_Data_Reporting_Tab_General
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
        $api_action_link = 'admin.php?page='.$this->token.'&tab=api-send&type=';
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Data Export</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Export Contacts

                    <div class="alignright">
                        <a href="<?php echo esc_attr( $preview_link ) . 'contacts' ?>">Preview <span class="dashicons dashicons-admin-site-alt3"></span></a> |
                        <a href="<?php echo plugins_url('../../exports/csv.php?type=contacts', __FILE__ ) ?>">CSV <span class="dashicons dashicons-download"></span></a> |
                        <a href="<?php echo plugins_url('../../exports/json.php?type=contacts', __FILE__ ) ?>">JSON <span class="dashicons dashicons-download"></span></a> |
                        <a href="<?php echo esc_attr( $api_action_link ) . 'contacts' ?>">Send to API <span class="dashicons dashicons-migrate"></span></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Export Contact Activity
                    <div class="alignright">
                        <a href="<?php echo esc_attr( $preview_link ) . 'contact_activity' ?>">Preview</a> |
                        <a href="<?php echo plugins_url('../../exports/csv.php?type=contact_activity', __FILE__ ) ?>">CSV</a> |
                        <a href="<?php echo plugins_url('../../exports/json.php?type=contact_activity', __FILE__ ) ?>">JSON</a>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

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

class DT_Data_Reporting_Tab_Preview
{
    public $type = 'contacts';

    public function __construct( $type )
    {
        $this->type = $type;
        require_once( plugin_dir_path( __FILE__ ) . '../data-tools.php' );
    }

    public function content() {
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
        switch ($this->type) {
            case 'contact_activity':
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contact_activity(false, 100);
                $this->main_column_table($columns, $rows);
                break;
            case 'contacts':
            default:
                // This is just a preview, so get the first 25 contacts only
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, 25);
                // [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, 1000);
                $this->main_column_table($columns, $rows);
                break;
        }
    }
    public function main_column_table( $columns, $rows ) {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <?php foreach( $columns as $column ): ?>
                <th><?php echo esc_html( $column['name'] ) ?></th>
            <?php endforeach; ?>
            </thead>
            <tbody>
            <?php foreach( $rows as $row ): ?>
            <tr>
                <?php foreach( $row as $rowValue ): ?>
                    <td>
                    <?php
                        if (is_array($rowValue)) {
                            if (sizeof($rowValue)) {
                                echo "<ul><li>" . implode('</li><li>', $rowValue) . "</li></ul>";
                            }
                        } else {
                            echo esc_html($rowValue);
                        }
                    ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}

class DT_Data_Reporting_Tab_API
{
    public $type = 'contacts';

    public function __construct( $type )
    {
        $this->type = $type;
        require_once( plugin_dir_path( __FILE__ ) . '../data-tools.php' );
    }

    public function content() {
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
        switch ($this->type) {
            /*case 'contact_activity':
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contact_activity(false);
                $this->export_data($columns, $rows);
                break;*/
            case 'contacts':
            default:
                // This is just a preview, so get the first 25 contacts only
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, 10);
                // [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, 1000);
                $this->export_data($columns, $rows, $this->type);
                break;
        }
    }
    public function export_data($columns, $rows, $type ) {

      echo '<ul>';
      echo '<li>Starting export...';
      // todo: load this from a plugin setting
      $url = 'https://us-central1-maarifa-logging.cloudfunctions.net/dtDataLoad';
      $args = [
        'method' => 'POST',
        'headers' => array(
          'Content-Type' => 'application/json; charset=utf-8'
        ),
        'body'      => json_encode([
          'columns' => $columns,
          'items' => $rows,
          'type' => $type,
        ]),
      ];

      $result = wp_remote_post( $url, $args );
      if ( is_wp_error( $result ) ){
        $error_message = $result->get_error_message() ?? '';
        dt_write_log($error_message);
        echo "<li>Error: $error_message</li>";
      } else {
        $result_body = json_decode($result['body']);
        echo "<li><pre><code>".$result['body']."</code></pre>";
//        if (!empty($result_body) && $result_body === true) {
//          return [
//            "success" => true,
//            "message" => $linked,
//          ];
//        }
      }
      echo '<li>Done exporting.</li>';
      echo '</ul>';
    }
}

