# Cloud Functions
This directory contains examples of how to write some cloud functions to receive and process the reporting data via a webhook URL.

These samples are specifically written for Google Cloud Platform but could be replicated in other cloud providers.

- `dtDataLoad`: HTTP webhook URL that receives JSON data from this DT plugin and saves it to a file in cloud storage
  - Has options for authenticating requests either with a mapping between DT URL and a shared auth key, or by using an IP whitelist. Enabled with an environment variable.
- `loadFiltToBigQuery`: Triggered by new files in cloud storage. Loads the given file directly into the database provider (BigQuery on Google).

## Authentication and site isolation (`dtDataLoad`)
- Auth keys are required by default. Each document in the Firestore `sites` collection holds a site's `token` and its `site` value (the D.T base URL the plugin sends in each row, e.g. `example.com/subsite`). Set `ENABLE_FIRESTORE_AUTH_KEY: "0"` only if the function is protected some other way.
- A key can only load rows whose `site` matches its own document, and the document's `prefix` and field settings are used for that request.
- `truncate` from the plugin ("All Data" exports) is honored only for sites with their own `prefix`. Tables using the shared default `dt` prefix hold several sites' rows, so those loads are appended and de-duplicated instead.
- The IP allowlist checks the last `X-Forwarded-For` entry, which is the address Google's front end appends; earlier entries can be set by the caller.
