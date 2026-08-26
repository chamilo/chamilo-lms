// Generated from: features/toolAttendance.feature
import { test } from "playwright-bdd";

test.describe('Attendance tool', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
  });
  
  test('Create', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/attendance/index.php?cid=3&action=attendance_add"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Attendance 1"}]}]}}, { page }); 
    await Then('I fill in editor field "description" with "Description for attendance"', null, { page }); 
    await Then('wait for the page to be loaded', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "Add a date time"', null, { page }); 
    await And('I remember the created attendance id', null, { page }); 
  });

  test('Read', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/attendance/index.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Attendance 1"', null, { page }); 
    await Then('I follow "Attendance 1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "The attendance sheets allow you to specify a list of dates"', null, { page }); 
  });

  test('Update', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on the attendance page "/main/attendance/index.php?cid=3&action=attendance_edit&attendance_id=ATTENDANCE_ID"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Edit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Attendance 1 edited"}]}]}}, { page }); 
    await Then('I fill in editor field "description" with "Description edited"', null, { page }); 
    await Then('I press "Update"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Delete', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/attendance/index.php?cid=3&sid=0"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Attendance 1 edited"', null, { page }); 
    await Then('I click the "a[href*=\'attendance_delete\']" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "Attendance 1 edited"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolAttendance.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":28,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/attendance/index.php?cid=3&action=attendance_add\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/attendance/index.php?cid=3&action=attendance_add\"","children":[{"start":9,"value":"/main/attendance/index.php?cid=3&action=attendance_add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":33,"keywordType":"Outcome","textWithKeyword":"Then I fill in editor field \"description\" with \"Description for attendance\"","stepMatchArguments":[{"group":{"start":23,"value":"\"description\"","children":[{"start":24,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"Description for attendance\"","children":[{"start":43,"value":"Description for attendance","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":34,"keywordType":"Outcome","textWithKeyword":"Then wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":35,"keywordType":"Outcome","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":36,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Add a date time\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Add a date time\"","children":[{"start":14,"value":"Add a date time","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"And I remember the created attendance id","stepMatchArguments":[]}]},
  {"pwTestLine":22,"pickleLine":40,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/attendance/index.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/attendance/index.php?cid=3\"","children":[{"start":9,"value":"/main/attendance/index.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":43,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Attendance 1\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Attendance 1\"","children":[{"start":14,"value":"Attendance 1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":44,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Attendance 1\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Attendance 1\"","children":[{"start":10,"value":"Attendance 1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"Then I should see \"The attendance sheets allow you to specify a list of dates\"","stepMatchArguments":[{"group":{"start":13,"value":"\"The attendance sheets allow you to specify a list of dates\"","children":[{"start":14,"value":"The attendance sheets allow you to specify a list of dates","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":31,"pickleLine":48,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"Given I am on the attendance page \"/main/attendance/index.php?cid=3&action=attendance_edit&attendance_id=ATTENDANCE_ID\"","stepMatchArguments":[{"group":{"start":28,"value":"\"/main/attendance/index.php?cid=3&action=attendance_edit&attendance_id=ATTENDANCE_ID\"","children":[{"start":29,"value":"/main/attendance/index.php?cid=3&action=attendance_edit&attendance_id=ATTENDANCE_ID","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":34,"gherkinStepLine":51,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Edit\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Edit\"","children":[{"start":14,"value":"Edit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":35,"gherkinStepLine":52,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":53,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":55,"keywordType":"Outcome","textWithKeyword":"Then I fill in editor field \"description\" with \"Description edited\"","stepMatchArguments":[{"group":{"start":23,"value":"\"description\"","children":[{"start":24,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"Description edited\"","children":[{"start":43,"value":"Description edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":56,"keywordType":"Outcome","textWithKeyword":"Then I press \"Update\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Update\"","children":[{"start":9,"value":"Update","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":39,"gherkinStepLine":57,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":58,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":43,"pickleLine":60,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":61,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/attendance/index.php?cid=3&sid=0\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/attendance/index.php?cid=3&sid=0\"","children":[{"start":9,"value":"/main/attendance/index.php?cid=3&sid=0","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":62,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":63,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Attendance 1 edited\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Attendance 1 edited\"","children":[{"start":14,"value":"Attendance 1 edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":64,"keywordType":"Outcome","textWithKeyword":"Then I click the \"a[href*='attendance_delete']\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"a[href*='attendance_delete']\"","children":[{"start":13,"value":"a[href*='attendance_delete']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":48,"gherkinStepLine":65,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":49,"gherkinStepLine":66,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Attendance 1 edited\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Attendance 1 edited\"","children":[{"start":18,"value":"Attendance 1 edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end