const {
  fixTagsFormat,
} = require('./parser');

const fileContentsOld = '{"id":"468","created":"2021-05-12 19:27:39","last_modified":"2021-05-18 10:56:53","type":"Access","tags":"[\\"AS\\"]","languages":[],"location_grid":["TR"],"relation":[],"gender":null,"age":null,"requires_update":true,"overall_status":"Active","milestones":[],"subassigned":[],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":null,"quick_button_contact_established":null,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Attempt Needed","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}\n' +
  '{"id":"487","created":"2021-05-18 20:01:49","last_modified":"2021-05-18 17:01:51","type":"Access","tags":null,"languages":[],"location_grid":["TR"],"relation":[],"gender":null,"age":null,"requires_update":null,"overall_status":"New Contact","milestones":[],"subassigned":[],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":null,"quick_button_contact_established":null,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Attempt Needed","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}\n' +
  '{"id":"479","created":"2021-05-16 16:19:09","last_modified":"2021-05-18 18:35:58","type":"Access","tags":null,"languages":[],"location_grid":["TR"],"relation":[],"gender":null,"age":null,"requires_update":null,"overall_status":"New Contact","milestones":[],"subassigned":[],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":null,"quick_button_contact_established":null,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Attempt Needed","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}\n' +
  '{"id":"470","created":"2021-05-13 15:22:27","last_modified":"2021-05-19 11:07:00","type":"Access","tags":null,"languages":[],"location_grid":["TR"],"relation":[],"gender":"Male","age":"26-40 years old","requires_update":null,"overall_status":"Active","milestones":[],"subassigned":[372],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":1,"quick_button_contact_established":1,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Established","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}\n' +
  '{"id":"445","created":"2021-04-27 09:53:56","last_modified":"2021-05-19 11:07:03","type":"Access","tags":"[\\"AS\\",\\"AB\\"]","languages":[],"location_grid":["TR"],"relation":[],"gender":null,"age":null,"requires_update":true,"overall_status":"Active","milestones":[],"subassigned":[],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":null,"quick_button_contact_established":null,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Attempted","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}';

const fileContentsNew = '{"id":"468","created":"2021-05-12 19:27:39","last_modified":"2021-05-18 10:56:53","type":"Access","tags":["AS"],"languages":[],"location_grid":["TR"],"relation":[],"gender":null,"age":null,"requires_update":true,"overall_status":"Active","milestones":[],"subassigned":[],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":null,"quick_button_contact_established":null,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Attempt Needed","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}\n' +
  '{"id":"487","created":"2021-05-18 20:01:49","last_modified":"2021-05-18 17:01:51","type":"Access","tags":null,"languages":[],"location_grid":["TR"],"relation":[],"gender":null,"age":null,"requires_update":null,"overall_status":"New Contact","milestones":[],"subassigned":[],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":null,"quick_button_contact_established":null,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Attempt Needed","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}\n' +
  '{"id":"479","created":"2021-05-16 16:19:09","last_modified":"2021-05-18 18:35:58","type":"Access","tags":null,"languages":[],"location_grid":["TR"],"relation":[],"gender":null,"age":null,"requires_update":null,"overall_status":"New Contact","milestones":[],"subassigned":[],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":null,"quick_button_contact_established":null,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Attempt Needed","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}\n' +
  '{"id":"470","created":"2021-05-13 15:22:27","last_modified":"2021-05-19 11:07:00","type":"Access","tags":null,"languages":[],"location_grid":["TR"],"relation":[],"gender":"Male","age":"26-40 years old","requires_update":null,"overall_status":"Active","milestones":[],"subassigned":[372],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":1,"quick_button_contact_established":1,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Established","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}\n' +
  '{"id":"445","created":"2021-04-27 09:53:56","last_modified":"2021-05-19 11:07:03","type":"Access","tags":["AS","AB"],"languages":[],"location_grid":["TR"],"relation":[],"gender":null,"age":null,"requires_update":true,"overall_status":"Active","milestones":[],"subassigned":[],"coaching":[],"baptism_date":null,"baptism_generation":null,"coached_by":[],"baptized_by":[],"baptized":[],"people_groups":[],"quick_button_no_answer":null,"quick_button_contact_established":null,"quick_button_meeting_scheduled":null,"quick_button_meeting_complete":null,"quick_button_no_show":null,"groups":[],"assigned_to":["5"],"seeker_path":"Contact Attempted","reason_unassignable":null,"reason_paused":null,"reason_closed":null,"accepted":null,"sources":["test"],"site":"test.disciple.tools"}';

describe('fixTagsFormat', () => {
  test('replaces tags string', () => {
    const result = fixTagsFormat(fileContentsOld);
    expect(result).toBe(fileContentsNew);
  });

  test('does not change correct format', () => {
    const result = fixTagsFormat(fileContentsNew);
    expect(result).toBe(fileContentsNew);
  });

  test('strips assign_to:undefined - only', () => {
    const result = fixTagsFormat(
      '{"id":"123","assigned_to":[undefined],"type":"Access","languages":[]}'
    );
    expect(result).toBe(
      '{"id":"123","assigned_to":[],"type":"Access","languages":[]}'
    );
  });
  test('strips assign_to:test - only', () => {
    const result = fixTagsFormat(
      '{"id":"123","assigned_to":["test"],"type":"Access","languages":[]}'
    );
    expect(result).toBe(
      '{"id":"123","assigned_to":[],"type":"Access","languages":[]}'
    );
  });
  test('strips assign_to:test - first', () => {
    const result = fixTagsFormat(
      '{"id":"123","assigned_to":["test","1"],"type":"Access","languages":[]}'
    );
    expect(result).toBe(
      '{"id":"123","assigned_to":["1"],"type":"Access","languages":[]}'
    );
  });
  test('strips assign_to:test - middle', () => {
    const result = fixTagsFormat(
      '{"id":"123","assigned_to":["1","test","2"],"type":"Access","languages":[]}'
    );
    expect(result).toBe(
      '{"id":"123","assigned_to":["1","2"],"type":"Access","languages":[]}'
    );
  });
  test('strips assign_to:test - last', () => {
    const result = fixTagsFormat(
      '{"id":"123","assigned_to":["1","test"],"type":"Access","languages":[]}'
    );
    expect(result).toBe(
      '{"id":"123","assigned_to":["1"],"type":"Access","languages":[]}'
    );
  });
});
