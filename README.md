# Disciple Tools Data Reporting
The Disciple Tools Data Reporting plugin is intended to assist in exporting data to an external data reporting source, such as Google BigQuery.
The plugin allows you to manually export your data in CSV and JSON (newline delimited) formats. However, it's primary intended use is for automating data export via a webhook URL to receive JSON formatted data. 

The plugin has been setup for usage with Google Cloud Platform infrastructure (Cloud Functions, Cloud Storage, and BigQuery), but should theoretically be able to be used with anything as the single point of communication is a webhook URL that you could configure to communicate with any system.

There is also a feature to opt-in to sending anonymized data to a global reporting system for comparing D.T usage across different sites and searching for trends that could be useful for the whole D.T community. 
