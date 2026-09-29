// Generated from: features/actionUserCheck.feature
import { test } from "playwright-bdd";

test.describe('User check after installation', () => {

  test('Check admin information', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/user-list?keyword=admin"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "admin"', null, { page }); 
    await Then('I follow "John"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "John Doe"', null, { page }); 
  });

  test('Check anon information', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/user-list?keyword=anon"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "anon"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/actionUserCheck.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":24,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/user-list?keyword=admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/user-list?keyword=admin\"","children":[{"start":9,"value":"/admin/user-list?keyword=admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"Then I should see \"admin\"","stepMatchArguments":[{"group":{"start":13,"value":"\"admin\"","children":[{"start":14,"value":"admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":29,"keywordType":"Outcome","textWithKeyword":"Then I follow \"John\"","stepMatchArguments":[{"group":{"start":9,"value":"\"John\"","children":[{"start":10,"value":"John","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":30,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"Then I should see \"John Doe\"","stepMatchArguments":[{"group":{"start":13,"value":"\"John Doe\"","children":[{"start":14,"value":"John Doe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":16,"pickleLine":33,"tags":[],"steps":[{"pwStepLine":17,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/user-list?keyword=anon\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/user-list?keyword=anon\"","children":[{"start":9,"value":"/admin/user-list?keyword=anon","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"Then I should see \"anon\"","stepMatchArguments":[{"group":{"start":13,"value":"\"anon\"","children":[{"start":14,"value":"anon","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end