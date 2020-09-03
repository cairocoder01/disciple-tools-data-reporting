<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
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
        $limit = 100;
        echo "<em>Showing only the top $limit records as a preview. When exporting, all records will be included.</em>";

        switch ($this->type) {
            case 'contact_activity':
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contact_activity(false, $limit);
                $this->main_column_table($columns, $rows);
                break;
            case 'contacts':
            default:
                // This is just a preview, so get the first $limit contacts only
                [$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(false, ['limit' => $limit]);
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
