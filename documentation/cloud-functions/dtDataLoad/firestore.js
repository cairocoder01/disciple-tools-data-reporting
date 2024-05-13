// By default, the client will authenticate using the service account file
// specified by the GOOGLE_APPLICATION_CREDENTIALS environment variable and use
// the project specified by the GOOGLE_CLOUD_PROJECT environment variable. See
// https://github.com/GoogleCloudPlatform/google-cloud-node/blob/master/docs/authentication.md
// These environment variables are set automatically on Google App Engine
const {Firestore} = require('@google-cloud/firestore');

// Create a new client
const firestore = new Firestore();

exports.getWhitelistIPs = async () => {
  const docRef = await firestore
    .collection('settings')
    .doc('whitelist')
    .get();
  if (docRef.exists) {
    return docRef.data().ipaddresses || [];
  }
  return [];
}

exports.validateAuthKey = async (token) => {
  const snapshot = await firestore
    .collection('sites')
    .where('token', '==', token)
    .get();

  if (!snapshot.empty && snapshot.docs.length) {
    const doc = snapshot.docs[0].data();
    if (doc.scopes) {
      // ensure site config has scope for this process
      return (doc.scopes||[]).includes('dtDataLoad');
    } else {
      // success if `scopes` doesn't exist on site
      return true;
    }
  }
  return false;
}

exports.getSiteConfig = async (site) => {
  const snapshot = await firestore
    .collection('sites')
    .where('site', '==', site)
    .get();

  if (!snapshot.empty && snapshot.docs.length) {
    console.debug(`getSiteConfig(${site}): ${JSON.stringify(snapshot.docs[0].data())}`);
    return snapshot.docs[0].data();
  }

  return null;
}