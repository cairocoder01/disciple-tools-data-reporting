<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_Preview
{
    public $type = 'contacts';
    public $config;

    public function __construct( $type, $config )
    {
        require_once( plugin_dir_path( __FILE__ ) . '../data-tools.php' );

        $this->type = $type;
        $this->config = DT_Data_Reporting_Tools::get_config_by_key($config);
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

        switch ($this->type) {
            case 'contact_activity':
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contact_activity(false, $limit);
                $this->main_column_table($columns, $rows);
                break;
            case 'contacts':
            default:
                // This is just a preview, so get the first $limit contacts only
                $filter = $this->config && isset( $this->config['contacts_filter'] ) ? $this->config['contacts_filter'] : [];
                $filter['limit'] = $limit;
                [$columns, $rows, $total] = DT_Data_Reporting_Tools::get_contacts(false, $filter);
                $this->main_column_table($columns, $rows, $total);
                break;
        }
    }
    public function main_column_table( $columns, $rows, $total ) {
      if ( $this->config ) {
        echo '<h2>Preview for Configuration: ' . $this->config['name'] . '</h2>';
      }
        ?>
        <div class="total-results">Showing <?php echo count($rows) ?> of <?php echo $total ?></div>
        <?php if ( count($rows) != $total ): ?>
          <em>Showing only the top <?php echo count($rows) ?> records as a preview. When exporting, all records will be included.</em>";
        <?php endif; ?>

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
