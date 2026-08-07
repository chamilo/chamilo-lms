// Generated from: features/toolWiki.feature
import { test } from "playwright-bdd";

test.describe('Wiki tool', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Admin edits a wiki and sees the new content', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Wiki"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await And('I follow "Edit page"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in tinymce field "wiki_page_content" with "New Wiki"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "New Wiki"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolWiki.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":32,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I follow \"Wiki\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Wiki\"","children":[{"start":10,"value":"Wiki","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I follow \"Edit page\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Edit page\"","children":[{"start":10,"value":"Edit page","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":39,"keywordType":"Context","textWithKeyword":"And I fill in tinymce field \"wiki_page_content\" with \"New Wiki\"","stepMatchArguments":[{"group":{"start":24,"value":"\"wiki_page_content\"","children":[{"start":25,"value":"wiki_page_content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":49,"value":"\"New Wiki\"","children":[{"start":50,"value":"New Wiki","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":42,"keywordType":"Outcome","textWithKeyword":"Then I should see \"New Wiki\"","stepMatchArguments":[{"group":{"start":13,"value":"\"New Wiki\"","children":[{"start":14,"value":"New Wiki","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end