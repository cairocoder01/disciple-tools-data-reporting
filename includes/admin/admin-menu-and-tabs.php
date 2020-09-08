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

        require_once( plugin_dir_path( __FILE__ ) . './admin-tab-manual-export.php' );
        require_once( plugin_dir_path( __FILE__ ) . './admin-tab-bigquery.php' );
        require_once( plugin_dir_path( __FILE__ ) . './admin-tab-preview.php' );
        require_once( plugin_dir_path( __FILE__ ) . './admin-tab-api.php' );
        require_once( plugin_dir_path( __FILE__ ) . './admin-tab-settings.php' );
    } // End __construct()


    function add_styles() {
        echo
        '<style>
            body.wp-admin.extensions-dt_page_DT_Data_Reporting
            #post-body-content {
              overflow-y: auto;
            }
            body.wp-admin.extensions-dt_page_DT_Data_Reporting
            ul {
              list-style: inherit;
              padding-inline-start: 2em;
              margin: 0;
            }
            body.wp-admin.extensions-dt_page_DT_Data_Reporting
            code {
              display: block;
            }
            
            #poststuff h2 {
              padding-left: 0;
              font-size: 1.2rem;
            }
            .table-export th, .table-export-config .config-name {
              font-weight: bold;
            }

            .table-config {
              border-left: solid 1px #a9a9a9;
              padding-left: 15px;
              background-color: #e0e0e0;
            }
            .table-config input[type=text] {
              width: 100%;
            }
            .form-table.table-config th {
              padding-left: 1rem;
            }
            .table-config tr:nth-child(even) {
              background-color: #eee
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
            $tab = 'getting-started';
        }
        if ( isset( $_GET["type"] ) ) {
            $type = sanitize_key( wp_unslash( $_GET["type"] ) );
        } else {
            $type = null;
        }
        if ( isset( $_GET["config"] ) ) {
            $config = sanitize_key( wp_unslash( $_GET["config"] ) );
        } else {
            $config = null;
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Data Reporting', 'DT_Data_Reporting') ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'getting-started' ?>" class="nav-tab <?php ( $tab == 'getting-started' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'Getting Started', 'DT_Data_Reporting') ?></a>
                <a href="<?php echo esc_attr( $link ) . 'export' ?>" class="nav-tab <?php ( $tab == 'export' ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'Manual Export', 'DT_Data_Reporting') ?></a>
                <a href="<?php echo esc_attr( $link ) . 'bigquery' ?>" class="nav-tab <?php ( $tab == 'bigquery' ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'BigQuery Setup', 'DT_Data_Reporting') ?></a>
                <a href="<?php echo esc_attr( $link ) . 'settings' ?>" class="nav-tab <?php ( $tab == 'settings' ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'Settings', 'DT_Data_Reporting') ?></a>
                <?php if ($tab === 'preview' ): ?>
                    <a href="<?php echo esc_attr( $link ) . 'preview' ?>" class="nav-tab <?php ( $tab == 'preview' ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'Preview', 'DT_Data_Reporting') ?></a>
                <?php endif; ?>
                <?php if ($tab === 'api-send' ): ?>
                  <a href="<?php echo esc_attr( $link ) . 'api-send' ?>" class="nav-tab <?php ( $tab == 'api-send' ) ? esc_attr_e( 'nav-tab-active', 'DT_Data_Reporting') : print ''; ?>"><?php esc_attr_e( 'API Send', 'DT_Data_Reporting') ?></a>
                <?php endif; ?>
            </h2>

            <?php
            switch ($tab) {
                case "getting-started":
                    $object = new DT_Data_Reporting_Tab_Getting_Started( $this->token );
                    $object->content();
                    break;
                case "export":
                    $object = new DT_Data_Reporting_Tab_Manual_Export( $this->token );
                    $object->content();
                    break;
                case "bigquery":
                    $object = new DT_Data_Reporting_Tab_BigQuery( $this->token );
                    $object->content();
                    break;
                case "preview":
                    $object = new DT_Data_Reporting_Tab_Preview( $type, $config );
                    $object->content();
                    break;
                case "api-send":
                    $object = new DT_Data_Reporting_Tab_API( $this->token, $type, $config );
                    $object->content();
                    break;
                case "settings":
                    $object = new DT_Data_Reporting_Tab_Settings( );
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

class DT_Data_Reporting_Tab_Getting_Started
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
      ?>
      <table class="widefat">
        <thead>
          <tr><th>Plugin Overview</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p>The Disciple Tools Data Reporting plugin is intended to assist in exporting data to an external data reporting source, such as Google BigQuery.
              The plugin allows you to manually export your data in CSV and JSON (newline delimited) formats. However, it&#39;s primary intended use is for automating data export via a webhook URL to receive JSON formatted data. </p>
            <p>The plugin has been setup for usage with Google Cloud Platform infrastructure (Cloud Functions, Cloud Storage, and BigQuery), but should theoretically be able to be used with anything as the single point of communication is a webhook URL that you could configure to communicate with any system.</p>
          </td>
        </tr>
        </tbody>
      </table>
      <br>

      <table class="widefat">
        <thead>
        <tr><th>Manual Exports</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p>At any time, you can get an export of your site data in either CSV or JSON (line-delimited) formats by going to the Manual Export tab and choosing the format you would like next to the type of data you want to download.</p>
            <p>After following the Setup Instructions below, you can trigger exports to your Endpoint URL using the API Send link next to each data type. </p>
          </td>
        </tr>
        </tbody>
      </table>
      <br>

      <table class="widefat">
        <thead>
        <tr><th>Setup Instructions</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p>To get started, go to the Settings tab and enter an Endpoint URL to receive that reporting data sent from this plugin. You can build you own endpoint, or you can look at the BigQuery tab to get sample code for setting up a process on Google Cloud Platform that should stay within their free usage using Cloud Functions, Cloud Storage, and BigQuery.</p>
            <h3 id="api-documentation">API Documentation</h3>
            <p>The data from this plugin will be sent to the Endpoint URL (configured in Settings tab) using an HTTP POST request. The body (sent with content-type of application/json; charset=utf-8) of the request will have the format:</p>
            <pre><code>{
  column<span class="hljs-variable">s:</span> [{         // Array of <span class="hljs-keyword">all</span> fields that have been exported
    key: <span class="hljs-built_in">string</span>,      // field key <span class="hljs-keyword">as</span> defined by D.T theme/plugin
    name: <span class="hljs-built_in">string</span>,     // field name <span class="hljs-keyword">as</span> defined by D.T theme/plugin
    <span class="hljs-built_in">type</span>: <span class="hljs-built_in">string</span>,     // field <span class="hljs-built_in">type</span> <span class="hljs-keyword">as</span> defined by D.T theme/plugin
    bq_type: <span class="hljs-built_in">string</span>,  // BigQuery column <span class="hljs-built_in">type</span> based <span class="hljs-keyword">on</span> field <span class="hljs-built_in">type</span>
    bq_mode: <span class="hljs-built_in">string</span>,  // BigQuery column <span class="hljs-keyword">mode</span> (<span class="hljs-keyword">e</span>.g. NULLABLE, REPEATED)
  }], //
  item<span class="hljs-variable">s:</span> [],          // <span class="hljs-keyword">all</span> of the structured data <span class="hljs-keyword">for</span> your selected data <span class="hljs-built_in">type</span>
  <span class="hljs-built_in">type</span>: <span class="hljs-built_in">string</span>,       // <span class="hljs-keyword">e</span>.g. contacts, contact_activity
}
</code></pre>
            <p>No specific return value is required besides returning a 200 status code. Any response will be displayed on the page when manually running the API export.</p>
          </td>
        </tr>
        </tbody>
      </table>
      <br>

      <table class="widefat">
        <thead>
        <tr><th>Global Data Sharing</th></tr>
        </thead>
        <tbody>
        <tr>
          <td>
            <p><em>Note: The following is still under development and not yet implemented.</em></p>
            <p style="text-decoration: line-through;">The plugin also has a feature to opt-in to sending anonymized data to a global reporting system for comparing D.T usage across different sites and searching for trends that could be useful for the whole D.T community. To get started, go to the Settings tab and opt-in for global reporting by giving your email address and entering the API key that is sent to you.</p>
            <p style="text-decoration: line-through;">Those who opt-in will then be notified about when new reports or analyses are made in order to learn from the insights and activity of the global D.T community.</p>
          </td>
        </tr>
        </tbody>
      </table>

      <?php
    }

}
