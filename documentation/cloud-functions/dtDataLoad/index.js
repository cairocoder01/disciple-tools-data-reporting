const functions = require('@google-cloud/functions-framework');
const moment = require('moment');
const storage = require('./storage');
const parser = require('./parser');
const firestore = require('./firestore');

// `type` becomes part of the storage filename and the BigQuery table name
const VALID_TYPE = /^[a-z0-9_]+$/;

/**
 * HTTP Cloud Function (2nd Gen).
 */
const dtDataLoad = async (req, res) => {
  if (req.method === 'POST') {
    const data = (req.body) || {};

    try {
      // Authorize: IP whitelist
      if (!await isValidIpAddress(req, res)) {
        return;
      }
      // Authorize: auth keys. Resolves to the site document the key belongs to, or null when auth is disabled
      const authSite = await authorizeKey(req, res);
      if (authSite === false) {
        return;
      }

      // Validate expected body fields
      if (!data.columns) {
        res.status(400);
        return res.send({
          error: 'Invalid payload: Missing columns'
        });
      }
      if (!data.items) {
        res.status(400);
        return res.send({
          error: 'Invalid payload: Missing items'
        });
      }
      if (!data.type) {
        res.status(400);
        return res.send({
          error: 'Invalid payload: Missing type'
        });
      }
      if (!VALID_TYPE.test(data.type)) {
        res.status(400);
        return res.send({
          error: 'Invalid payload: Invalid type'
        });
      }

      // An auth key may only load data for its own site
      if (authSite && (!authSite.site || data.items.some((item) => item.site !== authSite.site))) {
        res.status(403);
        return res.send({
          error: 'Unauthorized access',
          code: 104,
        });
      }

      const site = data.items[0]['site'];
      const config = authSite || (site ? await firestore.getSiteConfig(site) : null);
      const prefix = config && config.prefix ? config.prefix : 'dt';

      const fields = parser.getFieldSchema(data.columns, config, data.type);
      const rows = parser.jsonToRows(data.columns, data.items, config, data.type);
      const fileContent = await parser.rowsToJsonString(rows);

      // set metadata for truncate and sort field
      const isActivity = data.type.includes('_activity');
      const isSnapshots = data.type.includes('_snapshots');
      const metadata = {
        fields: JSON.stringify(fields),
        sortField: isActivity ? 'date' : (isSnapshots ? 'snapshot_date' : 'last_modified'),
        // Tables with the shared default prefix hold several sites' rows, so only a site's own prefix may be truncated
        truncate: !!data.truncate && prefix !== 'dt',
      };
      if (isSnapshots) {
        metadata.groupByFields = 'id, period, site';
      }

      const filename = `${prefix}_${data.type}_${moment().format('YYYYMMDDHHmmssSSS')}.json`;

      const bucketName = process.env.BUCKET_NAME || 'dt-data-load';
      await storage.uploadFile(bucketName, filename, fileContent, metadata);
      res.status(200);
      return res.send({
        count: rows.length,
      });
    } catch(err) {
      console.error(err);
      res.status(404);
      return res.send({ error: 'unable to store' });
    }
  }

  return res.status(400).send();
};

functions.http('dtDataLoad', dtDataLoad);
exports.dtDataLoad = dtDataLoad;


async function isValidIpAddress(req, res) {
  if (process.env.ENABLE_FIRESTORE_WHITELIST === "1") {
    // Google's front end appends the caller's address, so the last entry is the one the client cannot set
    var forwardedFor = (req.headers['x-forwarded-for'] || '').split(',').map((ip) => ip.trim()).filter(Boolean);
    var ip = forwardedFor.length ? forwardedFor[forwardedFor.length - 1] : req.socket.remoteAddress;

    const ipaddresses = await firestore.getWhitelistIPs();

    const isValid = ipaddresses.includes(ip);
    if (!isValid) {
      res.status(403);
      res.send({
        error: 'Unauthorized access',
        code: 101,
      });
    }
    return isValid;
  }
  return true;
}

async function authorizeKey(req, res) {
  // Auth keys are required unless explicitly disabled
  if (process.env.ENABLE_FIRESTORE_AUTH_KEY !== "0") {
    var authKey = (
      req.headers['authorization']
      || req.headers['Authorization']
      || ''
    ).replace(/Bearer /, '');

    if (!authKey) {
      res.status(401);
      res.send({
        error: 'Unauthorized access',
        code: 102,
      });
      return false;
    }

    const siteDoc = await firestore.validateAuthKey(authKey);
    if (!siteDoc) {
      res.status(403);
      res.send({
        error: 'Unauthorized access',
        code: 103,
      });
      return false;
    }
    return siteDoc;
  }
  return null;
}