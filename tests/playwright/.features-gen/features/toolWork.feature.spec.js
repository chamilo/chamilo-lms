// Generated from: features/toolWork.feature
import { test } from "playwright-bdd";

test.describe('Work tool', () => {

  test('Create a work', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assignments"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I press "Create Assignment"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"Assignment name"},{"value":"Work 1 Test"}]}]}}, { page }); 
    await And('I fill in the active tinymce editor with "Work description"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "Assignment created"', null, { page }); 
  });

  test('Edit a work', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assignments"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await And('I follow "Work 1 Test"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "Work description"', null, { page }); 
    await Then('I press "Edit assignment"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"Assignment name"},{"value":"Work 1 Test Edited"}]}]}}, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "Assignment updated"', null, { page }); 
    await Then('I should see "Work 1 Test Edited"', null, { page }); 
  });

  test('Send work as student', async ({ Given, Then, And, page }) => { 
    await Given('I am not logged', null, { page }); 
    await Given('I am a student', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assignments"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "Work 1 Test Edited"', null, { page }); 
    await Then('I follow "Work 1 Test Edited"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "Work description"', null, { page }); 
    await Then('I press "Upload file"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I attach the file "/public/favicon.ico" to the upload dropzone', null, { page }); 
    await And('I press "Upload file"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "File uploaded successfully"', null, { page }); 
  });

  test('Check that work previously uploaded by student is available for the teacher', async ({ Given, Then, And, page }) => { 
    await Given('I am not logged', null, { page }); 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assignments"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await And('I follow "Work 1 Test Edited"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "Work description"', null, { page }); 
    await And('I should see "favicon"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolWork.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":45,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":46,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":47,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assignments\"","children":[{"start":10,"value":"Assignments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":51,"keywordType":"Outcome","textWithKeyword":"Then I press \"Create Assignment\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Create Assignment\"","children":[{"start":9,"value":"Create Assignment","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":52,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":53,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":55,"keywordType":"Action","textWithKeyword":"And I fill in the active tinymce editor with \"Work description\"","stepMatchArguments":[{"group":{"start":41,"value":"\"Work description\"","children":[{"start":42,"value":"Work description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":56,"keywordType":"Action","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":57,"keywordType":"Action","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":58,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Assignment created\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Assignment created\"","children":[{"start":14,"value":"Assignment created","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":21,"pickleLine":60,"tags":[],"steps":[{"pwStepLine":22,"gherkinStepLine":61,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":62,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assignments\"","children":[{"start":10,"value":"Assignments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":27,"gherkinStepLine":66,"keywordType":"Context","textWithKeyword":"And I follow \"Work 1 Test\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Work 1 Test\"","children":[{"start":10,"value":"Work 1 Test","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":28,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":68,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Work description\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Work description\"","children":[{"start":14,"value":"Work description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":69,"keywordType":"Outcome","textWithKeyword":"Then I press \"Edit assignment\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Edit assignment\"","children":[{"start":9,"value":"Edit assignment","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":70,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":71,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:","stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":73,"keywordType":"Outcome","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":74,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":75,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Assignment updated\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Assignment updated\"","children":[{"start":14,"value":"Assignment updated","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":36,"gherkinStepLine":76,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Work 1 Test Edited\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Work 1 Test Edited\"","children":[{"start":14,"value":"Work 1 Test Edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":39,"pickleLine":78,"tags":[],"steps":[{"pwStepLine":40,"gherkinStepLine":79,"keywordType":"Context","textWithKeyword":"Given I am not logged","stepMatchArguments":[]},{"pwStepLine":41,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":42,"gherkinStepLine":81,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":43,"gherkinStepLine":82,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":83,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assignments\"","children":[{"start":10,"value":"Assignments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":84,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":85,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Work 1 Test Edited\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Work 1 Test Edited\"","children":[{"start":14,"value":"Work 1 Test Edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":86,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Work 1 Test Edited\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Work 1 Test Edited\"","children":[{"start":10,"value":"Work 1 Test Edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":48,"gherkinStepLine":87,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":49,"gherkinStepLine":88,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Work description\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Work description\"","children":[{"start":14,"value":"Work description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":50,"gherkinStepLine":89,"keywordType":"Outcome","textWithKeyword":"Then I press \"Upload file\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Upload file\"","children":[{"start":9,"value":"Upload file","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":51,"gherkinStepLine":90,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":91,"keywordType":"Outcome","textWithKeyword":"Then I attach the file \"/public/favicon.ico\" to the upload dropzone","stepMatchArguments":[{"group":{"start":18,"value":"\"/public/favicon.ico\"","children":[{"start":19,"value":"/public/favicon.ico","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":92,"keywordType":"Outcome","textWithKeyword":"And I press \"Upload file\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Upload file\"","children":[{"start":9,"value":"Upload file","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":54,"gherkinStepLine":93,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":55,"gherkinStepLine":94,"keywordType":"Outcome","textWithKeyword":"Then I should see \"File uploaded successfully\"","stepMatchArguments":[{"group":{"start":13,"value":"\"File uploaded successfully\"","children":[{"start":14,"value":"File uploaded successfully","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":58,"pickleLine":96,"tags":[],"steps":[{"pwStepLine":59,"gherkinStepLine":97,"keywordType":"Context","textWithKeyword":"Given I am not logged","stepMatchArguments":[]},{"pwStepLine":60,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":61,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":62,"gherkinStepLine":100,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":63,"gherkinStepLine":101,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assignments\"","children":[{"start":10,"value":"Assignments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":64,"gherkinStepLine":102,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":65,"gherkinStepLine":103,"keywordType":"Context","textWithKeyword":"And I follow \"Work 1 Test Edited\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Work 1 Test Edited\"","children":[{"start":10,"value":"Work 1 Test Edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":66,"gherkinStepLine":104,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":67,"gherkinStepLine":105,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Work description\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Work description\"","children":[{"start":14,"value":"Work description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":68,"gherkinStepLine":106,"keywordType":"Outcome","textWithKeyword":"And I should see \"favicon\"","stepMatchArguments":[{"group":{"start":13,"value":"\"favicon\"","children":[{"start":14,"value":"favicon","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end