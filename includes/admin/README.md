# Getting Started
## Plugin Overview
The Disciple Tools Data Reporting plugin is intended to assist in exporting data to an external data reporting source, such as Google BigQuery.
The plugin allows you to manually export your data in CSV and JSON (newline delimited) formats. However, it's primary intended use is for automating data export via a webhook URL to receive JSON formatted data. 

The plugin has been setup for usage with Google Cloud Platform infrastructure (Cloud Functions, Cloud Storage, and BigQuery), but should theoretically be able to be used with anything as the single point of communication is a webhook URL that you could configure to communicate with any system.

## Manual Exports
At any time, you can get an export of your site data in either CSV or JSON (line-delimited) formats by going to the Manual Export tab and choosing the format you would like next to the type of data you want to download.

After following the Setup Instructions below, you can trigger exports to your Endpoint URL using the API Send link next to each data type. 

## Setup Instructions
To get started, go to the Settings tab and enter an Endpoint URL to receive that reporting data sent from this plugin. You can build you own endpoint, or you can look at the BigQuery tab to get sample code for setting up a process on Google Cloud Platform that should stay within their free usage using Cloud Functions, Cloud Storage, and BigQuery.

### API Documentation
The data from this plugin will be sent to the Endpoint URL you configure using an HTTP POST request. The body (sent with content-type of application/json; charset=utf-8) of the request will have the format:

```
{
  columns: [{         // Array of all fields that have been exported
    key: string,      // field key as defined by D.T theme/plugin
    name: string,     // field name as defined by D.T theme/plugin
    type: string,     // field type as defined by D.T theme/plugin
    bq_type: string,  // BigQuery column type based on field type
    bq_mode: string,  // BigQuery column mode (e.g. NULLABLE, REPEATED)
  }], //
  items: [],          // all of the structured data for your selected data type
  type: string,       // e.g. contacts, contact_activity
}
```

No specific return value is required besides returning a 200 status code. Any response will be displayed on the page when manually running the API export.

## Global Data Sharing
*Note: The following is still under development and not yet implemented.*

The plugin also has a feature to opt-in to sending anonymized data to a global reporting system for comparing D.T usage across different sites and searching for trends that could be useful for the whole D.T community. To get started, go to the Settings tab and opt-in for global reporting by giving your email address and entering the API key that is sent to you.

Those who opt-in will then be notified about when new reports or analyses are made in order to learn from the insights and activity of the global D.T community.

# BigQuery - Google Cloud Setup
This plugin was built with the intention of using BigQuery as its external data store from which to do all reporting and analysis. As such, you can find below some info and examples of how you can duplicate that setup.

Using Google Cloud Platform, these resources should stay within the free usage limits, depending on your usage. You will need to add your credit card to your account, but as long as your usage isn't overly much, you shouldn't be billed for anything.

## Overview of Process
To stay within free usage, we are going to save data to Cloud Storage and load those files into BigQuery instead of streaming data directly into BigQuery. Because of this, there are 2 different Cloud Functions that will be utilized.

As an overview, these are the steps that will be taken:

1. **Cloud Function (HTTP)**: Receive JSON data from plugin. Save as JSON line-delimited file in Cloud Storage.
1. **Cloud Storage**: Bucket will temporarily hold generated data file.
1. **Cloud Function (Storage trigger)**: Function is triggered when a new file is uploaded to storage bucket. Meta data will be read to know what table to load the data into, and the file will be loaded into BigQuery.
1. **BigQuery**: Holds data ready for reporting. Easy to connect to various visualization tools.

## Account Setup
*Full details to come*

## BigQuery Setup
*Full details to come*

## Cloud Storage Setup
*Full details to come*

## Cloud Function Setup - HTTP Endpoint
*Full details to come*

## Cloud Storage Setup - Storage Trigger
*Full details to come*

