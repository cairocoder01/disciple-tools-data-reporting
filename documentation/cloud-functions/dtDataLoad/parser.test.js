const { getFieldSchema, jsonToRows, rowsToCsvString, rowsToJsonString } = require('./parser');

const columns = [{
  key:"id",
  name:"ID",
  type: "number",
  bq_type: "INTEGER",
  bq_mode: "NULLABLE",
},{
  key:"created",
  name:"Created",
  type: "date",
  bq_type: "TIMESTAMP",
  bq_mode: "NULLABLE",
},{
  key:"assigned_to",
  name:"Assigned To",
  type: "user_select",
  bq_type: "INTEGER",
  bq_mode: "REPEATED",
}];

const config = {
  site: 'my.site',
  prefix: 'my',
};

describe('getFieldSchema', () => {
  test('sets header with column names', async () => {
    const fields = getFieldSchema(columns);

    expect(fields).toHaveLength(3);
    expect(fields[0]).toHaveProperty('name', 'id');
    expect(fields[1]).toHaveProperty('name', 'created');
    expect(fields[2]).toHaveProperty('name', 'assigned_to');
  });
  test('sets header with type', async () => {
    const fields = getFieldSchema(columns);

    expect(fields).toHaveLength(3);
    expect(fields[0]).toHaveProperty('type', 'INTEGER');
    expect(fields[1]).toHaveProperty('type', 'TIMESTAMP');
    expect(fields[2]).toHaveProperty('type', 'INTEGER');
  });
  test('sets header with mode: repeatable', async () => {
    const fields = getFieldSchema(columns);

    expect(fields).toHaveLength(3);
    expect(fields[0]).not.toHaveProperty('mode');
    expect(fields[1]).not.toHaveProperty('mode');
    expect(fields[2]).toHaveProperty('mode', 'REPEATED');
  });

  test('includes only whitelisted columns (config): contacts', async () => {
    const customConfig = Object.assign({
      fields: {
        contacts: ['id', 'created', 'other_field'],
      },
    }, config);

    const extraColumns = [
      ...columns,
      {
        key: "badfield",
        name: "Bad Field",
        type: "text",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      }
    ];
    const fields = getFieldSchema(extraColumns, customConfig, 'contacts');

    expect(fields[0]).toHaveProperty('name', 'id');
    expect(fields[1]).toHaveProperty('name', 'created');
    expect(fields).toHaveLength(2);
  });
  test('includes only whitelisted columns (default): contacts', async () => {
    const extraColumns = [
      ...columns,
      {
        key: "badfield",
        name: "Bad Field",
        type: "text",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      }
    ];
    const fields = getFieldSchema(extraColumns, config, 'contacts');

    expect(fields).toHaveLength(3);
    expect(fields[0]).toHaveProperty('name', 'id');
    expect(fields[1]).toHaveProperty('name', 'created');
    expect(fields[2]).toHaveProperty('name', 'assigned_to');
  });
  test('includes only whitelisted columns (default): contact_activity', async () => {
    const fields = getFieldSchema(columns, config, 'contact_activity');

    expect(fields).toHaveLength(1);
    expect(fields[0]).toHaveProperty('name', 'id');
  });
  test('includes only whitelisted columns (config): groups', async () => {
    const customConfig = Object.assign({
      fields: {
        contacts: ['id', 'created', 'other_field', 'gender'],
        groups: ['id', 'created', 'group_status'],
      },
    }, config);

    const extraColumns = [
      ...columns,
      {
        key: "group_status",
        name: "Group Status",
        type: "key_select",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      },
      {
        key: "other_field",
        name: "Other Field",
        type: "text",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      },
      {
        key: "badfield",
        name: "Bad Field",
        type: "text",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      }
    ];
    const fields = getFieldSchema(extraColumns, customConfig, 'groups');

    expect(fields[0]).toHaveProperty('name', 'id');
    expect(fields[1]).toHaveProperty('name', 'created');
    expect(fields[2]).toHaveProperty('name', 'group_status');
    expect(fields).toHaveLength(3);
  });
});

describe('jsonToRows', () => {
  test('matches properties by key', async () => {
    const rows = jsonToRows(columns, [{
      id: '123',
      created: '2020-01-01',
      assigned_to: 101,
    }, {
      id: '456',
      created: '2020-01-02',
      assigned_to: 102,
    }]);

    expect(rows).toHaveLength(2);
    expect(rows[0]).toMatchObject({id: '123', created: '2020-01-01', assigned_to: [101]});
    expect(rows[1]).toMatchObject({id: '456', created: '2020-01-02', assigned_to: [102]});
  });
  test('matches properties by name', async () => {
    const rows = jsonToRows(columns, [{
      ID: '123',
      Created: '2020-01-01',
      "Assigned To": 101,
    }, {
      ID: '456',
      Created: '2020-01-02',
      "Assigned To": 102,
    }]);

    expect(rows).toHaveLength(2);
    expect(rows[0]).toMatchObject({id: '123', created: '2020-01-01', assigned_to: [101]});
    expect(rows[1]).toMatchObject({id: '456', created: '2020-01-02', assigned_to: [102]});
  });

  test('handles missing fields', async () => {
    const rows = jsonToRows(columns, [{
      id: '123',
      created: '2020-01-01',
    }]);
    expect(rows).toHaveLength(1);
    expect(rows[0]).toMatchObject({id: '123', created: '2020-01-01', assigned_to: null});
  });
  test('keeps arrays as arrays', async () => {
    const rows = jsonToRows(columns, [{
      id: '123',
      created: '2020-01-01',
      assigned_to: [101, 102],
    }])
    expect(rows).toHaveLength(1);
    expect(rows[0]).toMatchObject({id: '123', created: '2020-01-01', assigned_to: [101, 102]});
  });

  test('handles assigned_to: test', async () => {
    const rows = jsonToRows(columns, [{
      id: '123',
      created: '2020-01-01',
      assigned_to: 'test',
    }, {
      id: '124',
      created: '2020-01-01',
      assigned_to: ['test', 2],
    }]);

    expect(rows).toHaveLength(2);
    expect(rows[0]).toMatchObject({id: '123', created: '2020-01-01', assigned_to: [-1]});
    expect(rows[1]).toMatchObject({id: '124', created: '2020-01-01', assigned_to: [-1, 2]});
  });

  test('includes only whitelisted columns (config): contacts', async () => {
    const customConfig = Object.assign({
      fields: {
        contacts: ['id', 'created', 'other_field'],
      },
    }, config);

    const extraColumns = [
      ...columns,
      {
        key: "badfield",
        name: "Bad Field",
        type: "text",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      }
    ];
    const rows = jsonToRows(extraColumns, [{
      id: '123',
      created: '2020-01-01',
      assigned_to: 101,
      badfield: 'foo',
    }, {
      id: '456',
      created: '2020-01-02',
      assigned_to: 102,
      badfield: 'foo',
    }], customConfig, 'contacts');

    expect(rows).toHaveLength(2);
    expect(rows[0]).toMatchObject({id: '123', created: '2020-01-01'});
    expect(rows[0]).not.toMatchObject({badfield: 'foo'});
    expect(rows[0]).not.toMatchObject({assigned_to: [101]});
    expect(rows[1]).toMatchObject({id: '456', created: '2020-01-02'});
    expect(rows[1]).not.toMatchObject({badfield: 'foo'});
    expect(rows[1]).not.toMatchObject({assigned_to: [102]});
  });
  test('includes only whitelisted columns: contacts', async () => {
    const extraColumns = [
      ...columns,
      {
        key: "badfield",
        name: "Bad Field",
        type: "text",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      }
    ];
    const rows = jsonToRows(extraColumns, [{
      id: '123',
      created: '2020-01-01',
      assigned_to: 101,
      badfield: 'foo',
    }, {
      id: '456',
      created: '2020-01-02',
      assigned_to: 102,
      badfield: 'foo',
    }], 'contacts');

    expect(rows).toHaveLength(2);
    expect(rows[0]).toMatchObject({id: '123', created: '2020-01-01', assigned_to: [101]});
    expect(rows[0]).not.toMatchObject({badfield: 'foo'});
    expect(rows[1]).toMatchObject({id: '456', created: '2020-01-02', assigned_to: [102]});
    expect(rows[1]).not.toMatchObject({badfield: 'foo'});
  });
  test('includes only whitelisted columns: contact_activity', async () => {
    const extraColumns = [
      ...columns,
      {
        key: "badfield",
        name: "Bad Field",
        type: "text",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      }
    ];
    const rows = jsonToRows(extraColumns, [{
      id: '123',
      created: '2020-01-01',
      badfield: 'foo',
    }, {
      id: '456',
      created: '2020-01-02',
      badfield: 'foo',
    }], 'contacts');

    expect(rows).toHaveLength(2);
    expect(rows[0]).toMatchObject({id: '123'});
    expect(rows[0]).not.toMatchObject({badfield: 'foo'});
    expect(rows[1]).toMatchObject({id: '456'});
    expect(rows[1]).not.toMatchObject({badfield: 'foo'});
  });
  test('includes only whitelisted columns (config): groups', async () => {
    const customConfig = Object.assign({
      fields: {
        contacts: ['id', 'created', 'other_field', 'gender'],
        groups: ['id', 'created', 'other_field', 'group_status'],
      },
    }, config);

    const extraColumns = [
      ...columns,
      {
        key: "badfield",
        name: "Bad Field",
        type: "text",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      },
      {
        key: "group_status",
        name: "Group Status",
        type: "key_select",
        bq_type: "STRING",
        bq_mode: "NULLABLE",
      }
    ];
    const rows = jsonToRows(extraColumns, [{
      id: '123',
      created: '2020-01-01',
      assigned_to: 101,
      badfield: 'foo',
      group_status: 'new',
    }, {
      id: '456',
      created: '2020-01-02',
      assigned_to: 102,
      badfield: 'foo',
      group_status: 'active',
    }], customConfig, 'groups');

    expect(rows).toHaveLength(2);
    expect(rows[0]).toMatchObject({id: '123', created: '2020-01-01', group_status: 'new'});
    expect(rows[0]).not.toMatchObject({badfield: 'foo'});
    expect(rows[0]).not.toMatchObject({assigned_to: [101]});
    expect(rows[1]).toMatchObject({id: '456', created: '2020-01-02', group_status: 'active'});
    expect(rows[1]).not.toMatchObject({badfield: 'foo'});
    expect(rows[1]).not.toMatchObject({assigned_to: [102]});
  });
});

describe('rowsToCsvString', () => {
  test('generates row string for single row', async () => {
    const rowString = await rowsToCsvString([["123", "2020-01-01", 101]]);

    expect(rowString).toContain("123,2020-01-01,101");
  });
  test('generates row string for multiple rows', async () => {
    const rowString = await rowsToCsvString([
      ["123", "2020-01-01", 101],
      ["456", "2020-01-02", 102],
    ]);

    expect(rowString).toContain("123,2020-01-01,101");
    expect(rowString).toContain("456,2020-01-02,102");
  });
  test('escapes commas', async () => {
    const rowString = await rowsToCsvString([
      ["12,3", "2020-01-01", 101],
    ]);

    expect(rowString).toContain("\"12,3\",2020-01-01,101");
  });
  test('handles null values', async () => {
    const rowString = await rowsToCsvString([
      ["123", null, 101],
    ]);
    expect(rowString).toContain('123,,101');
  });
});

describe('rowsToJsonString', () => {
  test('generates row string for single row', async () => {
    const row1 = {id: "123", created: "2020-01-01", assigned_to: [101]};
    const rowString = await rowsToJsonString([row1]);

    expect(rowString).toContain(JSON.stringify(row1));
  });
  test('generates row string for multiple rows', async () => {
    const row1 = {id: "123", created: "2020-01-01", assigned_to: [101]};
    const row2 = {id: "456", created: "2020-01-02", assigned_to: [102]};
    const rowString = await rowsToJsonString([
      row1,
      row2,
    ]);

    const resultRows = rowString.split('\n');
    expect(resultRows).toHaveLength(2);
    expect(resultRows[0]).toEqual(JSON.stringify(row1));
    expect(resultRows[1]).toEqual(JSON.stringify(row2));
  });
});
