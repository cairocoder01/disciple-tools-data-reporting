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
        $limit = 100;
        $offset = 0;
        if ( isset( $_GET["limit"] ) ) {
            $limit = sanitize_key( wp_unslash( $_GET["limit"] ) );
        }
        if ( isset( $_GET["offset"] ) ) {
            $offset = sanitize_key( wp_unslash( $_GET["offset"] ) );
        }

        $this->settings_selection();

        if ( isset( $_GET["type"] ) && isset( $_GET["period"] ) ) {
//          [$columns, $rows, $total] = DT_Data_Reporting_Tools::get_data($this->type, $this->config_key, false, $limit, $offset);
          $columns = [];
          $rows = [];
          $total = 0;
          $this->main_column_table( $columns, $rows, $total );
        }
    }

    public function settings_selection() {
      $post_types = DT_Posts::get_post_types();
      ?>
      <p>Please select a type and period to view snapshots.</p>

      <form method="GET">
        <input type="hidden" name="page" value="disciple-tools-data-reporting">
        <input type="hidden" name="tab" value="snapshots">
        <div style="display: flex; flex-direction: row; gap: 1rem;">
        <div>
          <h3>Record Type</h3>
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
          <h3>Time Period</h3>
          <input type="radio" id="period_month" name="period" value="month"
            <?php checked(isset($_GET['period']) && $_GET['period'] === 'month'); ?>>
          <label for="period_month">Month</label><br>

          <input type="radio" id="period_quarter" name="period" value="quarter"
            <?php checked(isset($_GET['period']) && $_GET['period'] === 'quarter'); ?>>
          <label for="period_quarter">Quarter</label><br>

          <input type="radio" id="period_year" name="period" value="year"
            <?php checked(isset($_GET['period']) && $_GET['period'] === 'year'); ?>>
          <label for="period_year">Year</label><br>
        </div>

          <div>
            <h3>Post ID</h3>
            <label for="post_id" style="display:block;">Enter a post ID to view snapshots for a specific post.</label>
            <input id="post_id" type="text" name="post_id" value="<?php echo isset($_GET['post_id']) ? esc_attr($_GET['post_id']) : ''; ?>">
          </div>

        </div>

        <input type="submit" value="View Snapshots" class="button">
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
