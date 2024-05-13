# Cloud Functions
This directory contains examples of how to write some cloud functions to receive and process the reporting data via a webhook URL.

These samples are specifically written for Google Cloud Platform but could be replicated in other cloud providers.

- `dtDataLoad`: HTTP webhook URL that receives JSON data from this DT plugin and saves it to a file in cloud storage
  - Has options for authenticating requests either with a mapping between DT URL and a shared auth key, or by using an IP whitelist. Enabled with an environment variable.
- `loadFiltToBigQuery`: Triggered by new files in cloud storage. Loads the given file directly into the database provider (BigQuery on Google).
