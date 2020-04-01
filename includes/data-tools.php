<?php


class DT_Export_Data_Tools
{
    public static function get_contacts( $limit = null ) {
        $filter = array();
        if ( !empty( $limit) ) {
            $filter['limit'] = $limit;
        }

        $contacts = DT_Posts::list_posts('contacts', $filter);
        $items = [];

        $post_settings = apply_filters( "dt_get_post_type_settings", [], 'contacts' );
        $fields = $post_settings["fields"];
        $hidden_fields = [];
//        print_r($fields);

        foreach ($contacts['posts'] as $index => $result) {
            $contact = [
                'ID' => $result['ID'],
            ];
            /*$items[] = [
                'ID' => $result['ID'],
                'Overall Status' => getLabel($result, 'overall_status'),
                'Gender' => getLabel($result, 'gender'),
                'Age' => getLabel($result, 'age'),
                'Type' => getLabel($result, 'type'),
                'Seeker Path' => getLabel($result, 'seeker_path'),
            ];*/
            foreach ( $fields as $field_key => $field ){
                if ( !isset( $field["hidden"] ) || $field["hidden"] === true ) {
                    $hidden_fields[] = $field_key;

                    $type = $field['type'];
                    $fieldValue = null;
                    if (key_exists($field_key, $result)) {
                        switch ($type) {
                            case 'key_select':
                                $fieldValue = self::getLabel($result, $field_key);
                                break;
                            case 'multi_select':
                                $fieldValue = implode(",", $result[$field_key]);
                                break;
                            default:
                                $fieldValue = $result[$field_key];
                                if ( is_array($fieldValue) ) {
                                    $fieldValue = 'Array';
                                }
                                break;
                        }
                    } else {
                        $fieldValue = 'KeyNotFound';
                    }

                    $contact[$field['name']] = $fieldValue;
                }
            }
            $items[] = $contact;
            //todo: milestones
        }
//        print_r($items);
        $columns = array("ID");
        foreach ( $fields as $field_key => $field ){
            if ( !isset( $field["hidden"] ) || $field["hidden"] === true ){
                array_push($columns, $field['name']);
            }
        }
        return array( $columns, $items );
    }

    private static function getLabel($result, $key) {
        return array_key_exists($key, $result) && array_key_exists('label', $result[$key]) ? $result[$key]['label'] : '';
    }
}
