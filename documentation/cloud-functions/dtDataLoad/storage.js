// By default, the client will authenticate using the service account file
// specified by the GOOGLE_APPLICATION_CREDENTIALS environment variable and use
// the project specified by the GOOGLE_CLOUD_PROJECT environment variable. See
// https://github.com/GoogleCloudPlatform/google-cloud-node/blob/master/docs/authentication.md
// These environment variables are set automatically on Google App Engine
const {Storage} = require('@google-cloud/storage');

// Instantiate a storage client
const storage = new Storage();

exports.uploadFile = (bucketName, filename, content, metadata) => {
  const bucket = storage.bucket(bucketName);

  // Create a new blob in the bucket and upload the file data.
  const file = bucket.file(filename);
  return file.save(content, {
    metadata: {
      metadata,
    }
  });
}
