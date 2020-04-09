<?php
/**
 * DT_Export_Plugin_Menu class for the admin page
 *
 * @class       DT_Export_Plugin_Menu
 * @version     0.1.0
 * @since       0.1.0
 */

//@todo Replace all instances if DT_Export
if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/**
 * Initialize menu class
 */
DT_Export_Plugin_Menu::instance();

/**
 * Class DT_Export_Plugin_Menu
 */
class DT_Export_Plugin_Menu {

    public $token = 'dt_export_plugin';

    private static $_instance = null;

    /**
     * DT_Export_Plugin_Menu Instance
     *
     * Ensures only one instance of DT_Export_Plugin_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Export_Plugin_Menu instance
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
            body.wp-admin.extensions-dt_page_dt_export_plugin
            #post-body-content {
              overflow-y: auto;
            }
            body.wp-admin.extensions-dt_page_dt_export_plugin
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
        add_submenu_page( 'dt_extensions', __( 'Export Plugin', 'dt_export_plugin' ), __( 'Export Plugin', 'dt_export_plugin' ), 'manage_dt', $this->token, [ $this, 'content' ] );
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
            <h2><?php esc_attr_e( 'Export Plugin', 'dt_export_plugin' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab <?php ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'dt_export_plugin' ) : print ''; ?>"><?php esc_attr_e( 'General', 'dt_export_plugin' ) ?></a>
                <?php if ($tab === 'preview' ): ?>
                    <a href="<?php echo esc_attr( $link ) . 'preview' ?>" class="nav-tab <?php ( $tab == 'preview' ) ? esc_attr_e( 'nav-tab-active', 'dt_export_plugin' ) : print ''; ?>"><?php esc_attr_e( 'Preview', 'dt_export_plugin' ) ?></a>
                <?php endif; ?>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Export_Tab_General( $this->token );
                    $object->content();
                    break;
                case "preview":
                    $object = new DT_Export_Tab_Preview( $type );
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

/**
 * Class DT_Export_Tab_General
 */
class DT_Export_Tab_General
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
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>CSV Export</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Export Contacts

                    <div class="alignright">
                        <a href="<?php echo esc_attr( $preview_link ) . 'contacts' ?>">Preview</a> |
                        <a href="<?php echo plugins_url('../../exports/csv.php?type=contacts', __FILE__ ) ?>">CSV</a> |
                        <a href="<?php echo plugins_url('../../exports/json.php?type=contacts', __FILE__ ) ?>">JSON</a>
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

/**
 * Class DT_Export_Tab_Preview
 */
class DT_Export_Tab_Preview
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
                [$columns, $rows] = DT_Export_Data_Tools::get_contact_activity(false, 100);
                $this->main_column_table($columns, $rows);
                break;
            case 'contacts':
            default:
                // This is just a preview, so get the first 25 contacts only
                [$columns, $rows] = DT_Export_Data_Tools::get_contacts(false, 25);
                // [$columns, $rows] = DT_Export_Data_Tools::get_contacts(false, 1000);
                $this->main_column_contacts($columns, $rows);
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

