const stringify = require('csv-stringify');

const fieldWhitelist = {
  contacts: [
    'id',
    'created',
    'assigned_to',
    'overall_status',
    'seeker_path',
    'requires_update',
    'milestones',
    'baptism_date',
    'baptism_generation',
    'gender',
    'age',
    'reason_unassignable',
    'reason_paused',
    'reason_closed',
    'sources',
    'quick_button_no_answer',
    'quick_button_contact_established',
    'quick_button_meeting_scheduled',
    'quick_button_meeting_complete',
    'quick_button_no_show',
    'corresponds_to_user',
    'last_modified',
    'tags',
    'relation',
    'coached_by',
    'coaching',
    'baptized_by',
    'baptized',
    'people_groups',
    'groups',
    'subassigned',
    'location_grid',
    'languages',
    'site',
    'accepted',
    'source_details',
    'type',
  ],
  contact_activity: [
    'id',
    'meta_id',
    'post_id',
    'user_id',
    'user_name',
    'action_type',
    'action_field',
    'action_value',
    'action_value_friendly',
    'action_value_order',
    'action_old_value',
    'note',
    'date',
    'site',
  ],
}
exports.getFieldSchema = (columns, siteConfig, type = 'contacts') => {
  const allowedFields = (siteConfig && siteConfig.fields ? siteConfig.fields[type] : null) || fieldWhitelist[type] || [];
  return columns
    .filter((col) => allowedFields.includes(col.key))
    .map((col) => {
      let colHeading = {
        name: col.key,
      };
      if (col.bq_type) {
        colHeading.type = col.bq_type;
      }
      if (col.bq_mode && col.bq_mode !== 'NULLABLE') {
        colHeading.mode = col.bq_mode;
      }
      return colHeading;
    });
};
function parseConnectionId(value) {
  if (isNaN(value)) {
    return null;
  }
  return value;
}
exports.jsonToRows = (columns, items, siteConfig, type = 'contacts') => {
  if (items && items.length) {
    const allowedFields = (siteConfig && siteConfig.fields ? siteConfig.fields[type] : null) || fieldWhitelist[type] || [];
    return items.map((item) => {
      return columns.reduce((row, col) => {
        // ignore columns not in the whitelist
        if (!allowedFields.includes(col.key)) {
          return row;
        }

        let value = item[col.key] || item[col.name] || null;
        if (col.type === 'user_select') {
          if (Array.isArray(value)) {
            value = value.map(parseConnectionId);
          } else {
            value = parseConnectionId(value);
          }
        }

        if (col.bq_mode === 'REPEATED' && value && !Array.isArray(value)) {
          value = [value];
        }
        row[col.key] = value;
        return row;
      }, {});
    });
  }

  return [];
}

exports.rowsToCsvString = (rows) => {
  return new Promise((resolve, reject) => {
    stringify(rows, (err, output) => {
      if (err) {
        return reject(err);
      }
      resolve(output);
    });
  });
}

exports.rowsToJsonString = (rows) => {
  return rows.map(JSON.stringify).join('\n');
}
