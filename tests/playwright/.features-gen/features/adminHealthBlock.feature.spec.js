// Generated from: features/adminHealthBlock.feature
import { test } from "playwright-bdd";

test.describe('Admin Health check block', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Open Health check', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Health check"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('See health warnings', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Health check"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminHealthBlock.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":28,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Health check\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Health check\"","children":[{"start":14,"value":"Health check","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":32,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":18,"pickleLine":34,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Health check\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Health check\"","children":[{"start":14,"value":"Health check","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":22,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end