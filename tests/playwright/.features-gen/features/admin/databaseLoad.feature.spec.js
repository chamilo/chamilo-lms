// Generated from: features/admin/databaseLoad.feature
import { test } from "playwright-bdd";

test.describe('Database load metrics on system status', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Administrator sees database identity rows', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin/system-status?section=database"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should see "Database load"', null, { page }); 
    await And('I should see "driver"', null, { page }); 
  });

  test('Database load panel is collapsed by default', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin/system-status?section=database"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Database load"', null, { page }); 
    await And('I should not see "Auto-refresh every 5 seconds"', null, { page }); 
  });

  test('Expanding the panel reveals load metrics', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/admin/system-status?section=database"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I click the "#database-load-toggle" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Auto-refresh every 5 seconds"', null, { page }); 
    await And('I should see "Uptime"', null, { page }); 
    await And('I should see "Slow queries"', null, { page }); 
    await And('I should see "Threads connected"', null, { page }); 
  });

  test('Non-administrators cannot access the database load page', async ({ Given, Then, And, page }) => { 
    await Given('I am a student', null, { page }); 
    await And('I am on "/admin/system-status?section=database"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('the response status code should be 200'); 
    await And('I should not see "Database load"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/admin/databaseLoad.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":18,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":15,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/system-status?section=database\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=database\"","children":[{"start":9,"value":"/admin/system-status?section=database","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":21,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":22,"keywordType":"Outcome","textWithKeyword":"And I should see \"Database load\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Database load\"","children":[{"start":14,"value":"Database load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":23,"keywordType":"Outcome","textWithKeyword":"And I should see \"driver\"","stepMatchArguments":[{"group":{"start":13,"value":"\"driver\"","children":[{"start":14,"value":"driver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":19,"pickleLine":25,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":15,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/system-status?section=database\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=database\"","children":[{"start":9,"value":"/admin/system-status?section=database","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Database load\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Database load\"","children":[{"start":14,"value":"Database load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":29,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Auto-refresh every 5 seconds\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Auto-refresh every 5 seconds\"","children":[{"start":18,"value":"Auto-refresh every 5 seconds","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":26,"pickleLine":31,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":15,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":27,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/system-status?section=database\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=database\"","children":[{"start":9,"value":"/admin/system-status?section=database","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":28,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":34,"keywordType":"Action","textWithKeyword":"When I click the \"#database-load-toggle\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"#database-load-toggle\"","children":[{"start":13,"value":"#database-load-toggle","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":35,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":36,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Auto-refresh every 5 seconds\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Auto-refresh every 5 seconds\"","children":[{"start":14,"value":"Auto-refresh every 5 seconds","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"And I should see \"Uptime\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Uptime\"","children":[{"start":14,"value":"Uptime","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"And I should see \"Slow queries\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Slow queries\"","children":[{"start":14,"value":"Slow queries","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"And I should see \"Threads connected\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Threads connected\"","children":[{"start":14,"value":"Threads connected","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":37,"pickleLine":41,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":15,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/system-status?section=database\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=database\"","children":[{"start":9,"value":"/admin/system-status?section=database","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":41,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"Then the response status code should be 200","stepMatchArguments":[{"group":{"start":35,"value":"200"},"parameterTypeName":"int"}]},{"pwStepLine":42,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Database load\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Database load\"","children":[{"start":18,"value":"Database load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end