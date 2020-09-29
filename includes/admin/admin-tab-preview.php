<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_Preview
{
    public $type = 'contacts';
    public $config;

    public function __construct( $type, $config ) {
        require_once( plugin_dir_path( __FILE__ ) . '../data-tools.php' );

        $this->type = $type;
        $this->config_key = $config;
        $this->config = DT_Data_Reporting_Tools::get_config_by_key( $config );
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
        $limit = 100;
        [ $columns, $rows, $total ] = DT_Data_Reporting_Tools::get_data( $this->type, $this->config_key, false, $limit );
        $this->main_column_table( $columns, $rows, $total );
    }
    public function main_column_table( $columns, $rows, $total ) {
        if ( $this->config ) {
            echo '<h2>Preview for Configuration: ' . esc_html( $this->config['name'] ) . '</h2>';
        }
        ?>
        <div class="total-results">Showing <?php echo count( $rows ) ?> of <?php echo esc_html( $total ) ?></div>
        <?php if ( count( $rows ) != $total ): ?>
          <em>Showing only the first <?php echo count( $rows ) ?> records as a preview. When exporting, all records will be included.</em>
        <?php endif; ?>

        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <?php foreach ( $columns as $column ): ?>
                <th><?php echo esc_html( $column['name'] ) ?></th>
            <?php endforeach; ?>
            </thead>
            <tbody>
            <?php foreach ( $rows as $row ): ?>
            <tr>
                <?php foreach ( $row as $row_value ): ?>
                    <td>
                    <?php
                    if (is_array( $row_value )) {
                        if (sizeof( $row_value )) {
                            echo "<ul><li>" . implode( '</li><li>', array_map( 'esc_attr', $row_value ) ) . "</li></ul>";
                        }
                    } else {
                        echo esc_html( $row_value );
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
