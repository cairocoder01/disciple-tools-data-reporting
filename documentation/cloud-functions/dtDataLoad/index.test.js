jest.mock('./storage');
jest.mock('./firestore');
const { dtDataLoad } = require('./index');
const storage = require('./storage');
const firestore = require('./firestore');


const resMock = {
  send: jest.fn(),
  status: jest.fn(),
};

const columns = [{
  key:"id",
  name:"ID"
},{
  key:"created",
  name:"Created"
}, {
  key: "assigned_to",
  name: "Assigned To"
}, {
  key: "site",
  name: "Site"
}];
const sampleData = [{
  id: '123',
  created: '2020-01-01',
  assigned_to: 101,
  site: 'my.site',
}, {
  id: '456',
  created: '2020-01-02',
  assigned_to: 102,
  site: 'my.site',
}];

beforeEach(() => {
  storage.uploadFile.mockClear();
  firestore.getWhitelistIPs.mockClear();
  firestore.validateAuthKey.mockClear();
  firestore.getSiteConfig.mockClear();
  resMock.status.mockClear();
  resMock.send.mockClear();
  process.env = {};
});

describe('authorization', () => {

  test('allow if none enabled', async () => {
    await dtDataLoad({
      method: 'POST',
      headers: {
        'x-forwarded-for': '123.456.789.012',
      },
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(firestore.getWhitelistIPs).not.toHaveBeenCalled();
    expect(resMock.status).toHaveBeenCalled();
    expect(resMock.status).toHaveBeenCalledWith(200);
  });

  describe('whitelist', () => {
    test('block if whitelist is empty', async () => {
      process.env.ENABLE_FIRESTORE_WHITELIST = '1';
      firestore.getWhitelistIPs.mockImplementationOnce(() => Promise.resolve([]));
      await dtDataLoad({
        method: 'POST',
        headers: {
          'x-forwarded-for': '123.456.789.012',
        },
        body: {
          columns,
          items: sampleData,
          type: 'contacts',
        },
      }, resMock);
      expect(firestore.getWhitelistIPs).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalledWith(403);
      expect(resMock.send).toHaveBeenCalledWith({
        error: 'Unauthorized access',
        code: 101,
      });
    });
    test('block if IP not in whitelist', async () => {
      process.env.ENABLE_FIRESTORE_WHITELIST = '1';
      firestore.getWhitelistIPs.mockImplementationOnce(() => Promise.resolve(['9.9.9.9']));
      await dtDataLoad({
        method: 'POST',
        headers: {
          'x-forwarded-for': '123.456.789.012',
        },
        body: {
          columns,
          items: sampleData,
          type: 'contacts',
        },
      }, resMock);
      expect(firestore.getWhitelistIPs).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalledWith(403);
      expect(resMock.send).toHaveBeenCalledWith({
        error: 'Unauthorized access',
        code: 101,
      });
    });
    test('allow if IP in whitelist', async () => {
    process.env.ENABLE_FIRESTORE_WHITELIST = '1';
    firestore.getWhitelistIPs.mockImplementationOnce(() => Promise.resolve(['123.456.789.012']));
    await dtDataLoad({
      method: 'POST',
      headers: {
        'x-forwarded-for': '123.456.789.012',
      },
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(firestore.getWhitelistIPs).toHaveBeenCalled();
    expect(resMock.status).toHaveBeenCalled();
    expect(resMock.status).toHaveBeenCalledWith(200);
  });
  });

  describe('auth keys', () => {
    test('calls validateAuthKey with key from Authorization header', async () => {
      process.env.ENABLE_FIRESTORE_AUTH_KEY = '1';
      firestore.validateAuthKey.mockImplementationOnce(() => Promise.resolve(true));
      await dtDataLoad({
        method: 'POST',
        headers: {
          'Authorization': 'Bearer my-auth-key',
        },
        body: {
          columns,
          items: sampleData,
          type: 'contacts',
        },
      }, resMock);
      expect(firestore.validateAuthKey).toHaveBeenCalled();
      expect(firestore.validateAuthKey).toHaveBeenCalledWith('my-auth-key');
    });
    test('calls validateAuthKey with key from authorization header', async () => {
      process.env.ENABLE_FIRESTORE_AUTH_KEY = '1';
      firestore.validateAuthKey.mockImplementationOnce(() => Promise.resolve(true));
      await dtDataLoad({
        method: 'POST',
        headers: {
          'authorization': 'Bearer my-auth-key',
        },
        body: {
          columns,
          items: sampleData,
          type: 'contacts',
        },
      }, resMock);
      expect(firestore.validateAuthKey).toHaveBeenCalled();
      expect(firestore.validateAuthKey).toHaveBeenCalledWith('my-auth-key');
    });
    test('allow if auth key is valid', async () => {
      process.env.ENABLE_FIRESTORE_AUTH_KEY = '1';
      firestore.validateAuthKey.mockImplementationOnce(() => Promise.resolve(true));
      await dtDataLoad({
        method: 'POST',
        headers: {
          'Authorization': 'Bearer my-auth-key',
        },
        body: {
          columns,
          items: sampleData,
          type: 'contacts',
        },
      }, resMock);
      expect(firestore.validateAuthKey).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalledWith(200);
    });
    test('block if auth key is invalid', async () => {
      process.env.ENABLE_FIRESTORE_AUTH_KEY = '1';
      firestore.validateAuthKey.mockImplementationOnce(() => Promise.resolve(false));
      await dtDataLoad({
        method: 'POST',
        headers: {
          'Authorization': 'Bearer my-auth-key',
        },
        body: {
          columns,
          items: sampleData,
          type: 'contacts',
        },
      }, resMock);
      expect(firestore.validateAuthKey).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalledWith(403);
    });
    test('block if auth key is empty', async () => {
      process.env.ENABLE_FIRESTORE_AUTH_KEY = '1';
      await dtDataLoad({
        method: 'POST',
        headers: {
        },
        body: {
          columns,
          items: sampleData,
          type: 'contacts',
        },
      }, resMock);
      expect(firestore.validateAuthKey).not.toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalled();
      expect(resMock.status).toHaveBeenCalledWith(401);
      expect(resMock.send).toHaveBeenCalled();
      expect(resMock.send).toHaveBeenCalledWith({
        error: 'Unauthorized access',
        code: 102,
      });
    });
  });
});

describe('payload validation', () => {

  test('returns error if missing type', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
      },
    }, resMock);
    expect(resMock.status).toHaveBeenCalled();
    expect(resMock.status).toHaveBeenCalledWith(400);
    expect(resMock.send).toHaveBeenCalled();
    expect(resMock.send).toHaveBeenCalledWith({
      error: 'Invalid payload: Missing type'
    });
  });
  test('returns error if missing columns', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        items: sampleData,
        type: 'contacts'
      },
    }, resMock);
    expect(resMock.status).toHaveBeenCalled();
    expect(resMock.status).toHaveBeenCalledWith(400);
    expect(resMock.send).toHaveBeenCalled();
    expect(resMock.send).toHaveBeenCalledWith({
      error: 'Invalid payload: Missing columns'
    });
  });
  test('returns error if missing items', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        type: 'contacts'
      },
    }, resMock);
    expect(resMock.status).toHaveBeenCalled();
    expect(resMock.status).toHaveBeenCalledWith(400);
    expect(resMock.send).toHaveBeenCalled();
    expect(resMock.send).toHaveBeenCalledWith({
      error: 'Invalid payload: Missing items'
    });
  });
});

describe('site config', () => {

  test('gets site config from site column in first row', async () => {
    const data = JSON.parse(JSON.stringify(sampleData));
    data[0].site = 'mycustom.site';

    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: data,
        type: 'contacts',
      },
    }, resMock);

    expect(firestore.getSiteConfig).toHaveBeenCalled();
    const fnArgs = firestore.getSiteConfig.mock.calls[0];
    expect(fnArgs[0]).toEqual('mycustom.site');
  });

  test('sets filename prefix from firestore settings', async () => {
    firestore.getSiteConfig.mockImplementationOnce(() => Promise.resolve({
      site: 'mycustom.site',
      prefix: 'custom',
    }));

    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    expect(uploadArgs[1]).toContain('.json');
    expect(uploadArgs[1]).toContain('custom_contacts');
  });
  test('uses default prefix if none in firestore', async () => {
    firestore.getSiteConfig.mockImplementationOnce(() => Promise.resolve({
      site: 'mycustom.site',
    }));

    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    expect(uploadArgs[1]).toContain('.json');
    expect(uploadArgs[1]).toContain('dt_contacts');
  });
});

describe('storage.uploadFile arguments', () => {

  test('sets date in filename', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    // date = YYYYMMDDhhmmssSSS
    expect(uploadArgs[1]).toMatch(/dt_\w*_\d{17}.json/);
  });

  test('sets filename from JSON type: contacts', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    expect(uploadArgs[1]).toContain('.json');
    expect(uploadArgs[1]).toContain('dt_contacts');
    expect(resMock.send).toHaveBeenCalled();
    expect(resMock.send).toHaveBeenCalledWith({count: 2});
  });
  test('sets filename from JSON type: groups', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'groups',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    expect(uploadArgs[1]).toContain('.json');
    expect(uploadArgs[1]).toContain('dt_groups');
    expect(resMock.send).toHaveBeenCalled();
    expect(resMock.send).toHaveBeenCalledWith({count: 2});
  });
  test('sets filename from JSON type: contact_activity', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contact_activity',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    expect(uploadArgs[1]).toContain('.json');
    expect(uploadArgs[1]).toContain('dt_contact_activity');
    expect(resMock.send).toHaveBeenCalled();
    expect(resMock.send).toHaveBeenCalledWith({count: 2});
  });

  test('sets metadata.fields by type: contacts', async () => {
    firestore.getSiteConfig.mockImplementationOnce(() => Promise.resolve({
      site: 'mycustom.site',
      fields: {
        contacts: ['id', 'assigned_to', 'other_field'],
      },
    }));
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    const metadataFields = JSON.parse(metadata.fields);
    expect(metadataFields).toMatchObject([
      {name: 'id'},
      {name: 'assigned_to'},
    ]);
  });
  test('sets metadata.fields by type: groups', async () => {
    firestore.getSiteConfig.mockImplementationOnce(() => Promise.resolve({
      site: 'mycustom.site',
      fields: {
        groups: ['id', 'assigned_to', 'other_field'],
      },
    }));
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'groups',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    const metadataFields = JSON.parse(metadata.fields);
    expect(metadataFields).toMatchObject([
      {name: 'id'},
      {name: 'assigned_to'},
    ]);
  });
  test('sets metadata.fields by type: trainings', async () => {
    firestore.getSiteConfig.mockImplementationOnce(() => Promise.resolve({
      site: 'mycustom.site',
      fields: {
        trainings: ['id', 'assigned_to', 'other_field'],
      },
    }));
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'trainings',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    const metadataFields = JSON.parse(metadata.fields);
    expect(metadataFields).toMatchObject([
      {name: 'id'},
      {name: 'assigned_to'},
    ]);
  });

  test('sets metadata.sortField by type: contacts', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    expect(metadata).not.toBeNull();
    expect(metadata).toMatchObject({
      sortField: 'last_modified',
    });
  });
  test('sets metadata.sortField by type: groups', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'groups',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    expect(metadata).not.toBeNull();
    expect(metadata).toMatchObject({
      sortField: 'last_modified',
    });
  });
  test('sets metadata.sortField by type: contact_activity', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contact_activity',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    expect(metadata).not.toBeNull();
    expect(metadata).toMatchObject({
      sortField: 'date',
    });
  });
  test('sets metadata.sortField by type: general_activity', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'general_activity',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    expect(metadata).not.toBeNull();
    expect(metadata).toMatchObject({
      sortField: 'date',
    });
  });

  test('sets metadata.truncate from body: undefined', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    expect(metadata).not.toBeNull();
    expect(metadata).toMatchObject({
      truncate: false,
    });
  });
  test('sets metadata.truncate from body: true', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
        truncate: true,
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    expect(metadata).not.toBeNull();
    expect(metadata).toMatchObject({
      truncate: true,
    });
  });
  test('sets metadata.truncate from body: false', async () => {
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
        truncate: false,
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    const metadata = uploadArgs[3];
    expect(metadata).not.toBeNull();
    expect(metadata).not.toBeNull();
    expect(metadata).toMatchObject({
      truncate: false,
    });
  });

  test('sets bucket name from environment variable', async () => {
    process.env.BUCKET_NAME = 'my-bucket-name';
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    expect(uploadArgs[0]).toEqual('my-bucket-name');
  });
  test('sets bucket name from default', async () => {
    process.env =  {};
    await dtDataLoad({
      method: 'POST',
      body: {
        columns,
        items: sampleData,
        type: 'contacts',
      },
    }, resMock);
    expect(storage.uploadFile).toHaveBeenCalled();
    const uploadArgs = storage.uploadFile.mock.calls[0];
    expect(uploadArgs[0]).toEqual('dt-data-load');
  });
});
