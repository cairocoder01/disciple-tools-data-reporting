<?php


class DT_Export_Data_Tools
{
    public static function get_contacts( $flatten = false, $limit = null ) {
        $filter = array();
        if ( !empty( $limit) ) {
            $filter['limit'] = $limit;
        }

        $contacts = DT_Posts::list_posts('contacts', $filter);
        // todo: if total is greater than length, recursively get more
        dt_write_log(sizeof($contacts['posts']) . ' of ' . $contacts['total']);
        $items = [];

        $post_settings = apply_filters( "dt_get_post_type_settings", [], 'contacts' );
        $fields = $post_settings["fields"];
        $excluded_fields = ['tasks', 'facebook_data'];
        $base_url = self::get_current_site_base_url();

        foreach ($contacts['posts'] as $index => $result) {
            $contact = [
                'ID' => $result['ID'],
            ];
            foreach ( $fields as $field_key => $field ){
                // skip if field is hidden
                if ( isset( $field['hidden'] ) && $field['hidden'] == true ) {
                    continue;
                }
                // skip if in list of excluded fields
                if ( in_array( $field_key, $excluded_fields ) ) {
                    continue;
                }

                $type = $field['type'];
                $fieldValue = null;
                if (key_exists($field_key, $result)) {
                    switch ($type) {
                        case 'key_select':
                            $fieldValue = self::getLabel($result, $field_key);
                            break;
                        case 'multi_select':
                            $fieldValue = $flatten ? implode(",", $result[$field_key]) : $result[$field_key];
                            break;
                        case 'user_select':
                            $fieldValue = $result[$field_key]['id'];
                            break;
                        case 'date':
                            $fieldValue = $result[$field_key]['timestamp'];
                            break;
                        case 'location':
                            $location_ids = array_map(function ( $location ) { return $location['label']; }, $result[$field_key]);
                            $fieldValue = $flatten ? implode(",", $location_ids) : $location_ids;
                            break;
                        case 'connection':
                            $connection_ids = array_map(function ( $connection ) { return $connection['ID']; }, $result[$field_key]);
                            $fieldValue = $flatten ? implode(",", $connection_ids) : $connection_ids;
                            break;
                        default:
                            $fieldValue = $result[$field_key];
                            if ( is_array($fieldValue) ) {
                                $fieldValue = json_encode($fieldValue);
                            }
                            break;
                    }
                    // special cases...
                    if ( $field_key == 'last_modified' ) {
                        $fieldValue = date("Y-m-d H:i:s", $result[$field_key]);
                    }
                } else {
                    // Set default/blank value
                    switch ($type) {
                        case 'number':
                            $fieldValue = $field['default'] ?? 0;
                            break;
                        case 'key_select':
                            $fieldValue = null;
                            break;
                        case 'multi_select':
                            $fieldValue = $flatten ? null : array();
                            break;
                        case 'array':
                        case 'boolean':
                        case 'date':
                        case 'text':
                        case 'location':
                        default:
                            $fieldValue = $field['default'] ?? null;
                            break;
                    }
                }

                $fieldValue = apply_filters('dt_data_export_field_output', $type, $field_key, $fieldValue, $flatten);
                $contact[$field_key] = $fieldValue;
            }
            $contact['site'] = $base_url;

            $items[] = $contact;
        }
        $columns = array();
        array_push( $columns, array(
            'key' => "id",
            'name' => "ID"
        ));

        foreach ( $fields as $field_key => $field ){
            // skip if field is hidden
            if ( isset( $field['hidden'] ) && $field['hidden'] == true ) {
                continue;
            }
            // skip if in list of excluded fields
            if ( in_array( $field_key, $excluded_fields ) ) {
                continue;
            }

            array_push($columns, array(
                'key' => $field_key,
                'name' => $field['name']
            ));
        }
        array_push( $columns, array(
            'key' => 'site',
            'name' => 'Site'
        ));
        return array( $columns, $items );
    }

    private static function getLabel($result, $key) {
        return array_key_exists($key, $result) && array_key_exists('label', $result[$key]) ? $result[$key]['label'] : '';
    }

    protected static function get_current_site_base_url() {
        $url = str_replace( 'http://', '', home_url() );
        $url = str_replace( 'https://', '', $url );

        return trim( $url );
    }
}
