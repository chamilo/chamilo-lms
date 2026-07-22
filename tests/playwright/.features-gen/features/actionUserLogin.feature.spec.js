// Generated from: features/actionUserLogin.feature
import { test } from "playwright-bdd";

test.describe('Login user', () => {

  test('Login as admin user successfully', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/login"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should see "Sign in"', null, { page }); 
    await When('I fill in "admin" for "login"', null, { page }); 
    await And('I fill in "admin" for "password"', null, { page }); 
    await And('I press "Sign in"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/actionUserLogin.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":3,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":4,"keywordType":"Context","textWithKeyword":"Given I am on \"/login\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/login\"","children":[{"start":9,"value":"/login","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":8,"gherkinStepLine":5,"keywordType":"Context","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":9,"gherkinStepLine":6,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Sign in\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Sign in\"","children":[{"start":14,"value":"Sign in","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":10,"gherkinStepLine":7,"keywordType":"Action","textWithKeyword":"When I fill in \"admin\" for \"login\"","stepMatchArguments":[{"group":{"start":10,"value":"\"admin\"","children":[{"start":11,"value":"admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":22,"value":"\"login\"","children":[{"start":23,"value":"login","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":8,"keywordType":"Action","textWithKeyword":"And I fill in \"admin\" for \"password\"","stepMatchArguments":[{"group":{"start":10,"value":"\"admin\"","children":[{"start":11,"value":"admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":22,"value":"\"password\"","children":[{"start":23,"value":"password","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":9,"keywordType":"Action","textWithKeyword":"And I press \"Sign in\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Sign in\"","children":[{"start":9,"value":"Sign in","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":10,"keywordType":"Action","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":12,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end