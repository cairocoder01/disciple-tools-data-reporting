# Disciple Tools Data Reporting
The Disciple Tools Data Reporting plugin is intended to assist in exporting data to an external data reporting source, such as Google BigQuery.
The plugin allows you to manually export your data in CSV and JSON (newline delimited) formats. However, it's primary intended use is for automating data export via a webhook URL to receive JSON formatted data. 

The plugin has been setup for usage with Google Cloud Platform infrastructure (Cloud Functions, Cloud Storage, and BigQuery), but should theoretically be able to be used with anything as the single point of communication is a webhook URL that you could configure to communicate with any system.

There is also a feature to opt-in to sending anonymized data to a global reporting system for comparing D.T usage across different sites and searching for trends that could be useful for the whole D.T community. 

## Customization / Developer Notes

### Custom Providers
Custom data providers can be created to send the data to any data source that is need via a separate plugin. The plugin just need a couple hooks in order to connect:

#### Filter `dt_data_reporting_providers`
Add your provider to the list of providers available on the settings screen.

Example:
```
add_filter( "dt_data_reporting_providers", "data_reporting_providers" ), 10, 4 );
function data_reporting_providers($providers) {
    $providers ['custom-provider'] = [
      'name' => 'My Custom Provider',
      'fields' => [
        'custom_key' => [
          'label' => 'My Custom Key',
          'type' => 'text',
          'helpText' => 'This is the custom key you need to authenticate with this provider'
        ]
      ]
    ];
    return $providers;
}
```

**Configuration Options:**
* `key`: Providers are stored as an associative array, meaning you need to provide a key that identifies your provider (e.g. `custom-provider`). This is used in the backend for identifying data specific to this provider
* `name`: Name of this provider that is visible in the UI
* `fields[]`: Associative array of any custom fields that are needed as part of the configuration. Each must have a unique key that is different from any other providers. Because of this, it will be best to prefix your keys with something specific to your provider (e.g. `azure_`, `gcp_`, `aws_`). This key is used any time you need to retrieve data from the saved configuration (e.g. `$config['custom_key']`).
* `fields[].label`: Name of field displayed in UI
* `fields[].type`: Type of field. Currently supports: `text`
* `fields[].helpText`: (optional) Displayed as further explanation of a field underneath the field on the settings screen.


### Hooks (actions & filters)

#### Filter: `dt_data_reporting_field_output`
Customize the output of any fields. Especially useful if you have added custom fields that may contain JSON data, or if you want to change how a certain type is exported.

Example:
```
add_filter( "dt_data_reporting_field_output", "data_reporting_field_output" ), 10, 4 );
function data_reporting_field_output($field_value, $type, $field_key, $flatten ) {
    if ($field_key == 'my_custom_field' ) {
        // catch a custom field and return the desired output
        // example: field is a JSON object with property "id" 
        //     that should be returned instead of the whole object
        $data = $field_value;
        if ( is_string( $field_value ) ) { // encoded JSON
            $data = json_decode( $field_value, true );
        }
        if ( is_array( $data ) && isset( $data['id'] ) ) {
            return $data['id'];
        }
        return "";
    }
    return $field_value; // always return the original value or you will overwrite all other fields
}
```

#### Filter: `dt_data_reporting_configurations`
Customize the list of configurations for export. This allows adding a configuration from a separate plugin.

Example:
```
add_filter( 'dt_data_reporting_configurations', 'data_reporting_configurations' ), 10, 1 );
function data_reporting_configurations( $configurations ) {
  $configurations['my-data-source'] = [
    'name' => 'My Reporting Data Source',
    'url' => 'http://www.mysite.com/api',
    'active' => 1,
    'contacts_filter' => [
      'sources' => ['web']
    ]
  ];
  return $configurations;
}

```

**Configuration Options:**
* `key`: Configurations are stored as an associative array, meaning you need to provide a key that identifies your configuration (e.g. `custom-config`). This is used in the backend for identifying data specific to this config.
* `name`: Name to identify this configuration so users know why it is there
* `url` (required): Endpoint URL to send data
* `active` (required): Set a value of 1 for the configuration to be active and enabled
* `token`: If your API requires an authentication token to be passed in the `Authorization` HTTP header, set this to that required token.
* `contacts_filter`: Filter the query of contacts to be exported. This is passed directly to `DT_Posts::list_posts(...)`, so it uses the format defined at https://github.com/DiscipleTools/disciple-tools-theme/wiki/Filter-and-Search-Lists