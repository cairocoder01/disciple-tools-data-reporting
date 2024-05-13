// Import the Google Cloud client libraries
const {BigQuery} = require('@google-cloud/bigquery');
const {Storage} = require('@google-cloud/storage');
const {fixTagsFormat} = require('./parser');
const byline = require('byline');

// Instantiate clients
const bigquery = new BigQuery();
const storage = new Storage();

/**
 * Migration to fix `tags` field from being a string to being a repeated string
 * @param event
 * @param context
 * @returns {Promise<void>}
 */
exports.fixTagsFormat = async (event, context) => {

  const gcsEvent = event;
  const bucketName = gcsEvent.bucket;
  const filename = gcsEvent.name;
  console.log(`Processing file: ${bucketName}/${filename}`);
  const bucket = storage.bucket(bucketName);
  const file = bucket.file(filename);

  // get dataset from environment
  const datasetId = process.env.BQ_DATASET;

  // check for migrateTags metadata (1=not migrated, 0=migrated->exit)
  const shouldMigrate = event.metadata && event.metadata.migrateTags;
  const fields = event.metadata && event.metadata.fields;
  console.log(`shouldMigrate: ${JSON.stringify(shouldMigrate)}`);
  if (shouldMigrate === '1') {
    // read/edit `fields` metadata
    console.log('Migrating file metadata...');
    const newMetadata = {metadata: event.metadata};
    newMetadata.metadata.fields = fields.replace('{"name":"tags","type":"STRING"}', '{"name":"tags","type":"STRING","mode":"REPEATED"}');
    newMetadata.metadata.migrateTags = '0';
    newMetadata.metadata.skipDedup = 'true';

    // read/edit file content
    console.log('Migrating file content formatting...');
    const fileContent = await getFileContent(bucketName, filename);
    if (fileContent.length > 0) {
      console.log(fileContent);
      console.log('Fixing formatting');
      const reformattedContent = fixTagsFormat(fileContent);
      console.log(reformattedContent);
      await file.save(reformattedContent, {
        metadata: newMetadata
      });
    }


    /*file.setMetadata(newMetadata, function(err) {
      if (err) {
        console.error(err);
      } else {
        console.log('Updated metadata');
      }
    });*/

  }
}
/**
 * Triggered from a change to a Cloud Storage bucket.
 *
 * @param {!Object} event Event payload.
 * @param {!Object} context Metadata for the event.
 */
exports.loadFileToBigQuery = async (event, context) => {
  try {
    const gcsEvent = event;
    const bucketName = gcsEvent.bucket;
    const filename = gcsEvent.name;
    console.log(`Processing file: ${bucketName}/${filename}`);
    const bucket = storage.bucket(bucketName);
    const file = bucket.file(filename);

    // get dataset from environment
    const datasetId = process.env.BQ_DATASET;

    // build table name from filename
    const match = filename.match(/(.*)_\d*\.(csv|json)/);
    if (!match || match.length < 2) {
      console.error(new Error('Unable to find table name in CSV filename: ' + filename));
      return;
    }
    const tableId = match[1];
    const fileType = match[2].toUpperCase();

    let fields = [];
    const headerRow = await getHeaderRow(bucketName, filename);
    if (headerRow) {
      if (fileType === 'CSV') {
        // get schema from first CSV row
        const columns = headerRow.split(',');
        fields = columns.map((col) => {
          const parts = col.replace(/"/g, "").split(":");
          return {
            name: parts[0],
            type: parts[1],
            mode: parts.length > 2 ? parts[2] : 'NULLABLE',
          };
        });
      } else {
        // get schema from metadata of json files
        fields = JSON.parse(event.metadata && event.metadata.fields);
        // console.log(fields);
      }
    }

    // send file to bigquery load method
    const shouldSkipDedup = event.metadata && event.metadata.skipDedup && event.metadata.skipDedup == 'true';
    const shouldTruncate = event.metadata && event.metadata.truncate && event.metadata.truncate == 'true';
    let writeDisposition = 'WRITE_APPEND';
    if (shouldTruncate) {
      writeDisposition = 'WRITE_TRUNCATE';
    }
    const metadata = {
      schema: {
        fields: fields,
      },
      writeDisposition: writeDisposition,
    };
    if (fileType === 'CSV') {
      metadata.skipLeadingRows = 1;
    }

    // Load data from a Google Cloud Storage file into the table
    const [job] = await bigquery
      .dataset(datasetId)
      .table(tableId)
      .load(file, metadata);

    // load() waits for the job to finish
    console.log(`Job ${job.id} completed.`);
    if (job.statistics && job.statistics.load) {
      console.log(`Imported ${job.statistics.load.outputRows} rows`);
    }

    // Check the job's status for errors
    const errors = job.status.errors;
    if (errors && errors.length > 0) {
      console.error(new Error(errors.join('\n')));
      /*for (let error of errors) {
        console.error(error);
      }*/
    }

    // delete file after successful load
    await file.delete();
    console.log(`gs://${bucketName}/${filename} deleted.`);

    // trigger bigquery query to remove duplicates
    const sortField = event.metadata && event.metadata.sortField;
    if (!shouldSkipDedup && !shouldTruncate && sortField) {
      console.log('Running dedup query');
      let groupByFields = 'id';
      if (tableId.startsWith('dt_')) {
        groupByFields = 'id, site';
      }
      await dedupTable(tableId, sortField, groupByFields);
    }

    // console.log(gcsEvent);
    // console.log(context);
  } catch (err) {
    console.error(err);
    console.error(new Error(err.message));
  }
};

function getHeaderRow(bucketName, filename) {
  return new Promise((resolve, reject) => {

    let headerRow;
    gcsStream = storage.bucket(bucketName).file(filename).createReadStream();
    lineStream = byline.createStream(gcsStream, { encoding: 'utf8' });
    lineStream.on('data', (line) => {
      if (!headerRow) {
        headerRow = line;
      }
      // console.log(line);
    }).on('end', () => {
      resolve(headerRow);
    }).on('error', (err) => {
      // console.error(err);
      reject(err);
    });
  });
}
function getFileContent(bucketName, filename) {
  const chunks = [];
  return new Promise((resolve, reject) => {
    const stream = storage.bucket(bucketName).file(filename).createReadStream();
    stream.on('data', (chunk) => chunks.push(Buffer.from(chunk)));
    stream.on('error', (err) => reject(err));
    stream.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
  });
}

async function dedupTable(tableName, sortField, groupByFields) {
  const dataset = process.env.BQ_DATASET;
  const query = `
MERGE INTO ${dataset}.${tableName} AS INTERNAL_DEST
USING (

  SELECT k.*
  FROM (
    SELECT ARRAY_AGG(original_data ORDER BY ${sortField} DESC LIMIT 1)[OFFSET(0)] k 
    FROM ${dataset}.${tableName} AS original_data
    GROUP BY ${groupByFields}
  )
)
AS INTERNAL_SOURCE
ON FALSE

WHEN NOT MATCHED BY SOURCE
    THEN DELETE

WHEN NOT MATCHED THEN INSERT ROW`;

  // For all options, see https://cloud.google.com/bigquery/docs/reference/rest/v2/jobs/query
  const options = {
    query: query,
  };

  // Run the query as a job
  const [job] = await bigquery.createQueryJob(options);
  console.log(`Job ${job.id} started.`);
  const errors = job && job.status && job.status.errors;
  if (errors && errors.length > 0) {
    console.error(new Error(errors.join('\n')));
  }

  job.getQueryResults({
    maxResults: 100
  }, (err, rows) => {
    if (err) {
      console.error(new Error(err));
    } else {
      console.log('Rows:');
      rows.forEach(row => console.log(row));
    }
  });
}