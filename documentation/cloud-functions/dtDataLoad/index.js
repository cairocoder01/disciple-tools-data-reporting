const moment = require('moment');
const storage = require('./storage');
const parser = require('./parser');
const firestore = require('./firestore');

/**
 * HTTP Cloud Function.
 *
 * @param {Object} req Cloud Function request context.
 *                     More info: https://expressjs.com/en/api.html#req
 * @param {Object} res Cloud Function response context.
 *                     More info: https://expressjs.com/en/api.html#res
 */
exports.dtDataLoad = async (req, res) => {
  if (req.method === 'POST') {
    const data = (req.body) || {};

    try {
      // Authorize: IP whitelist
      if (!await isValidIpAddress(req, res)) {
        return;
      }
      // Authorize: auth keys
      if (!await isValidAuthKey(req, res)) {
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

      const site = data.items[0]['site'];
      const config = site ? await firestore.getSiteConfig(site) : null;

      const fields = parser.getFieldSchema(data.columns, config, data.type);
      const rows = parser.jsonToRows(data.columns, data.items, config, data.type);
      const fileContent = await parser.rowsToJsonString(rows);

      // set metadata for truncate and sort field
      const isActivity = data.type.includes('_activity');
      const metadata = {
        fields: JSON.stringify(fields),
        sortField: isActivity ? 'date' : 'last_modified',
        truncate: !!data.truncate,
      };

      const prefix = config && config.prefix ? config.prefix : 'dt';
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
      return res.send({ error: 'unable to store', err });
    }
  }

  return res.status(400);
};


async function isValidIpAddress(req, res) {
  if (process.env.ENABLE_FIRESTORE_WHITELIST === "1") {
    var xForwardedFor = (req.headers['x-forwarded-for'] || '').replace(/:\d+$/, '');
    var ip = xForwardedFor || req.connection.remoteAddress;

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

async function isValidAuthKey(req, res) {
  if (process.env.ENABLE_FIRESTORE_AUTH_KEY === "1") {
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

    const isValid = await firestore.validateAuthKey(authKey);
    if (!isValid) {
      res.status(403);
      res.send({
        error: 'Unauthorized access',
        code: 103,
      });
    }
    return isValid;
  }
  return true;
}