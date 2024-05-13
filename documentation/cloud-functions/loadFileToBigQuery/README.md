# Function: load-file-to-bigquery

Monitors for CSV and JSON (line delimited) files added to a GCS bucket and loads them into BigQuery. File is deleted after processing.

## Environment Variables

- `BQ_DATASET` - Defines which BigQuery dataset that data will be loaded into

## Event Parameters

- `bucket` - ID of GCS bucket that file was in
- `name` - Filename of uploaded file
- `metadata` - Data read from the metadata of the file
  - `sortField` - Name of field to be used for de-duplicate query
  - `skipDedup` - Set to "true" to prevent de-duplicate query from running after import
  - `truncate` - Set to "true" to overwrite whole table with just the contents of the given file
  - `fields` - JSON definition of all fields to import and their types

## Process Description

- Read table name and file type from file name
  - Expects: `{table_name}_{datetime}.{csv|json}`
  - Date/time should be all numeric
- Map all field types from file to table
  - CSV files: read from header row
  - JSON files: read from `metadata.fields`
- Send file to BigQuery to be imported
- Run de-duplicate query (if not skipped via `skipDedup`)
  - Groups fields by `id` column (except tables with prefix `dt_`)
  - If duplicates are found by grouping, remove the older row based on `sortField`
  - Based on `sortField`, finds duplicate rows
