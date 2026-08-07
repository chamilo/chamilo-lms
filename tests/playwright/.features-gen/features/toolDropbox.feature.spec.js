// Generated from: features/toolDropbox.feature
import { test } from "playwright-bdd";

test.describe('Dropbox tool', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Admin opens Dropbox and sees the upload action', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Dropbox"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Share a new file"', null, { page }); 
    await And('I follow "Share a new file"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Upload"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolDropbox.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":22,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I follow \"Dropbox\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Dropbox\"","children":[{"start":10,"value":"Dropbox","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":27,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Share a new file\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Share a new file\"","children":[{"start":14,"value":"Share a new file","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"And I follow \"Share a new file\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Share a new file\"","children":[{"start":10,"value":"Share a new file","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":29,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":30,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Upload\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Upload\"","children":[{"start":14,"value":"Upload","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end