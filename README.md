![Data Reporting banner](/assets/banner-772x250.png)
# Disciple Tools Data Reporting
The Disciple Tools Data Reporting plugin assists in exporting data to an external data reporting source, such as cloud providers like Google Cloud, AWS, and Azure. The plugin allows you to manually download your data in CSV and JSON (newline delimited) formats. However, it's primary intended use is for automating data export directly to your choosen cloud provider.

By default, the plugin can export data in JSON format to a webhook URL for you to process in any way that you need. There are also additional plugins that can be added or created that provide other data provider types for sending data directly to your data store using the APIs or SDKs that are available for them. Currently, a provider of that type is available for Azure and GCP with more to come in the future as need arises.

The plugin allows exporting of both contacts and contact activity data as well as groups and group activity data.

Multiple exports can be created on a single site so you can export to multiple data stores if you partner with others who would like reporting data available to them.

**Features:**
* Contact / Contact Activity export
* Group / Group Activity export
* Preview of data to be exported
* Data download (CSV, JSON)
* Automated nightly export
* Integration with your cloud storage of choice
* Multiple export configurations per site
* Externally-created export configurations created by other plugins

**Upcoming Features:**
* Configure selection of fields to be exported
* Documentation for setting up your own cloud reporting environment

## Additional Data Providers
* [Azure](https://github.com/cairocoder01/disciple-tools-data-reporting-provider-azure)
* [GCP](https://github.com/cairocoder01/disciple-tools-data-reporting-provider-gcp)

## Custom Providers
Custom data providers can be created to send the data to any data source that is need via a separate plugin.

Get started with a [sample provider plugin](https://github.com/cairocoder01/disciple-tools-data-reporting-provider-sample). 

The plugin just needs a couple hooks in order to connect:

### Filter `dt_data_reporting_providers`
Add your provider to the list of providers available on the settings screen.

Example:
```
add_filter( "dt_data_reporting_providers", "data_reporting_providers", 10, 4 );
function data_reporting_providers($providers) {
    $providers ['custom-provider'] = [
      'name' => 'My Custom Provider',
      'flatten' => true,
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
* `flatten`: Boolean value to indicate whether to flatten data in each field. If true, array type data will be joined into comma-separated strings.
* `fields[]`: Associative array of any custom fields that are needed as part of the configuration. Each must have a unique key that is different from any other providers. Because of this, it will be best to prefix your keys with something specific to your provider (e.g. `azure_`, `gcp_`, `aws_`). This key is used any time you need to retrieve data from the saved configuration (e.g. `$config['custom_key']`).
* `fields[].label`: Name of field displayed in UI
* `fields[].type`: Type of field. Currently supports: `text`
* `fields[].helpText`: (optional) Displayed as further explanation of a field underneath the field on the settings screen.

### Filter: `dt_data_reporting_export_provider_{PROVIDER_KEY}`
The key used in the `dt_data_reporting_providers` filter above is used to create the name of this action. So if you created a provider with a key of `custom-provider`, this action would be `dt_data_reporting_export_provider_custom-provider`.

The function is executed in the context of a `<ul>`, so log messaging can be `echo`'ed with a `<li>` tag wrapping it.
The function should return an object with a boolean key `success` to indicate if the export completed successfully and thus track the last successfully exported values. Additionally, a `messages` property can contain an array of log messages to be store in the export log. See the [sample provider plugin](https://github.com/cairocoder01/disciple-tools-data-reporting-provider-sample) for a specific example.

Example:
```
add_filter( "dt_data_reporting_export_provider_custom-provider", "data_reporting_export", 10, 4 );
function data_reporting_export( $columns, $rows, $type, $config ) {
    $result = [
        'success' => true,
        'messages' => array(),
    ];  
  
    $result['messages'][] = [
        'message' => 'Debug config: ' . print_r( $config, true ),
    ];
    $result['messages'][] = [
        'type' => 'success',
        'message' => 'Exported: ' . count($rows),
    ];
    return $result;
}
```

**Function Parameters:**
* `columns`: List and configuration of all columns. Includes data types for each
* `rows`: Array of data
* `type`: Type of data being exported (e.g. contacts, contact_activity, etc.)
* `config`: The saved configuration included values for all custom fields added by the provider

### Action: `dt_data_reporting_tab_provider_{PROVIDER_KEY}`
The key used in the `dt_data_reporting_providers` filter above is used to create the name of this action. So if you created a provider with a key of `custom-provider`, this action would be `dt_data_reporting_tab_provider_custom-provider`.

The function should echo or print any HTML content that you want to be displayed on a tab within the Data Reporting Plugin admin. The tab will use the name of the provider as configured above.

Example:
```
add_action( "dt_data_reporting_tab_provider_custom-provider", "data_reporting_tab", 10, 1 );
function data_reporting_tab( ) {
  ?>
  <h2>My Custom Provider</h2>
  <p>Add here any getting started or how-to information that is needed for your provider</p>
  <?php
}
```

## Hooks (actions & filters)

### Filter: `dt_data_reporting_field_output`
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

### Filter: `dt_data_reporting_configurations`
Customize the list of configurations for export. This allows adding a configuration from a separate plugin.

Example:
```
add_filter( 'dt_data_reporting_configurations', 'data_reporting_configurations' ), 10, 1 );
function data_reporting_configurations( $configurations ) {
  $configurations['my-data-source'] = [
    'name' => 'My Reporting Data Source',
    'provider' => 'api',
    'url' => 'http://www.mysite.com/api',
    'active' => 1,
    'contacts_filter' => [
      'sources' => ['web']
    ],
    'groups_filter' => [
      'tags' => ['studying-bible']
    ],
    'data_types' => [
      'contacts' => [
        'all_data' => 1,
        'schedule' => 'daily'
      ],
      'contact_activity' => [
        'all_data' => 0,
        'limit' => 100
      ],
      'groups' => [
        'all_data' => 1,
        'schedule' => 'daily'
      ],
      'group_activity' => [
        'all_data' => 0,
        'limit' => 100
      ]
    ]
  ];
  return $configurations;
}

```

**Configuration Options:**
* `key`: Configurations are stored as an associative array, meaning you need to provide a key that identifies your configuration (e.g. `custom-config`). This is used in the backend for identifying data specific to this config.
* `name`: Name to identify this configuration so users know why it is there
* `provider` (required): Type of provider to use. If no additional providers are installed, this should be `api`
* `url`: If using the default `api` provider, this is the endpoint URL to send data
* `active` (required): Set a value of 1 for the configuration to be active and enabled
* `token`: If your API requires an authentication token to be passed in the `Authorization` HTTP header, set this to that required token.
* `contacts_filter`: Filter the query of contacts to be exported. This is used in connection with `DT_Posts::list_posts(...)`, so it uses the format defined at [https://github.com/DiscipleTools/disciple-tools-theme/wiki/Filter-and-Search-Lists] but is limited to `tags`, `source`, and `type`
* `groups_filter`: Filter the query of groups to be exported. This is used in connection with `DT_Posts::list_posts(...)`, so it uses the format defined at [https://github.com/DiscipleTools/disciple-tools-theme/wiki/Filter-and-Search-Lists] but is limited to `tags`
* `data_types`: Configuration of each data type to set whether to export all data or only the data since the last export
  * `[type].all_data`: 1 = Export all data every time; 0 = Export only data since last export
  * `[type].limit`: Maximum number of records exported with each export. Recommended to not exceed 5000 as it can cause memory problems on sites with large amounts of data.
  * `[type].schedule`: Set to `daily` to enable automatic daily exporting via CRON task
* Any other custom fields defined by custom providers should be added as defined in their documentation.
