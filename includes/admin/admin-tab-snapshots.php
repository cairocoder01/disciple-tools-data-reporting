<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_Snapshots
{
    public $type = 'contacts';
    public $config;

    public function __construct() {
        require_once( plugin_dir_path( __FILE__ ) . '../data-tools.php' );

//        $this->type = $type;
//        $this->config_key = $config;
//        $this->config = DT_Data_Reporting_Tools::get_config_by_key( $config );
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
      global $wpdb;

      $this->settings_selection();

      if ( isset( $_GET["button"] ) && $_GET["button"] === "run" ) {
        DT_Data_Reporting_Snapshot_Tools::run_snapshot_task();
      }

      $columns = [['name' => 'Interval']];

      $select = "select period_interval";
      $where = "where period_interval is not null";
      $group_by = "group by period_interval";
      $order_by = "";

      if ( isset( $_GET["type"] ) && !empty( $_GET["type"] ) ) {
        $select .= ", post_type";
        $where .= " and post_type = '" . sanitize_key( wp_unslash( $_GET["type"] ) ) . "'";
        $group_by .= ", post_type";
        $columns[] = ['name' => 'Post Type'];
      }
      if ( isset( $_GET["interval"] ) && !empty( $_GET["interval"] ) ) {
        $select .= ", period";
        $where .= " and period_interval = '" . sanitize_key( wp_unslash( $_GET["interval"] ) ) . "'";
        $group_by .= ", period";
        $columns[] = ['name' => 'Period'];
        $order_by = "ORDER BY period DESC";
      } else {
        $select .= ", max(period_start) as max_start, max(period_end) as max_end";
        $columns[] = ['name' => 'Last Period Start'];
        $columns[] = ['name' => 'Last Period End'];
      }

      $select .= ", count(*) as total";
      $columns[] = ['name' => 'Total'];

      $table_name = $wpdb->prefix . "dt_post_snapshots";
      $query = "$select from $table_name $where $group_by $order_by";
      dt_write_log($query);
      $results = $wpdb->get_results( $query, ARRAY_A );

      $this->main_column_table( $columns, $results, count( $results ) );
    }

    public function settings_selection() {
      $post_types = DT_Posts::get_post_types();
      ?>
      <p>Please select a type and/or period to view snapshot status.</p>

      <form method="GET">
        <input type="hidden" name="page" value="disciple-tools-data-reporting">
        <input type="hidden" name="tab" value="snapshots">
        <div style="display: flex; flex-direction: row; gap: 1rem;">
          <div>
            <h3>Record Type</h3>
            <input type="radio" id="type_none" name="type" value=""
              <?php checked(!isset($_GET['type']) || $_GET['type'] === ''); ?>>
            <label for="type_none">All</label><br>

            <?php foreach ($post_types as $post_type) : ?>
              <?php
              $post_label = DT_Posts::get_label_for_post_type( $post_type );
              ?>
              <input type="radio" id="type_<?php echo esc_attr($post_type); ?>"
                     name="type" value="<?php echo esc_attr($post_type); ?>"
                <?php checked(isset($_GET['type']) && $_GET['type'] === $post_type); ?>>
              <label
                for="type_<?php echo esc_attr($post_type); ?>"><?php echo esc_html( $post_label ); ?></label>
              <br>
            <?php endforeach; ?>
          </div>

          <div>
            <h3>Interval</h3>
            <input type="radio" id="interval_none" name="interval" value=""
              <?php checked(!isset($_GET['interval']) || $_GET['interval'] === ''); ?>>
            <label for="interval_none">All</label><br>

            <input type="radio" id="interval_month" name="interval" value="month"
              <?php checked(isset($_GET['interval']) && $_GET['interval'] === 'month'); ?>>
            <label for="interval_month">Month</label><br>

            <input type="radio" id="interval_quarter" name="interval" value="quarter"
              <?php checked(isset($_GET['interval']) && $_GET['interval'] === 'quarter'); ?>>
            <label for="interval_quarter">Quarter</label><br>

            <input type="radio" id="interval_year" name="interval" value="year"
              <?php checked(isset($_GET['interval']) && $_GET['interval'] === 'year'); ?>>
            <label for="interval_year">Year</label><br>
          </div>

        </div>

        <input type="submit" value="View Snapshots" class="button">
        <button type="submit" name="button" value="run" class="button">Run</button>
      </form>
      <?php
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

      <pre style="display:none;"><code style="display:block;"><?php print_r( $rows ); ?></code></pre>

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
