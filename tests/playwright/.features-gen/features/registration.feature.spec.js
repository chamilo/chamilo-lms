// Generated from: features/registration.feature
import { test } from "playwright-bdd";

test.describe('User registration', () => {

  test('Enter the registration form', async ({ Given, Then, And, page }) => { 
    await Given('I am on the homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Sign up"', null, { page }); 
    await Then('I follow "Sign up"', null, { page }); 
    await Then('I should see "Registration"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"firstname"},{"value":"user registration first name"}]},{"cells":[{"value":"lastname"},{"value":"user registration last name"}]},{"cells":[{"value":"email"},{"value":"user-registration@example.com"}]},{"cells":[{"value":"username"},{"value":"user_registration"}]},{"cells":[{"value":"pass1"},{"value":"user-registration00!"}]},{"cells":[{"value":"pass2"},{"value":"user-registration00!"}]}]}}, { page }); 
    await And('I press "Register"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/registration.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":9,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":10,"keywordType":"Context","textWithKeyword":"Given I am on the homepage","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":9,"gherkinStepLine":12,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Sign up\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Sign up\"","children":[{"start":14,"value":"Sign up","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":10,"gherkinStepLine":13,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Sign up\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Sign up\"","children":[{"start":10,"value":"Sign up","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":14,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Registration\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Registration\"","children":[{"start":14,"value":"Registration","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":15,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":16,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":23,"keywordType":"Outcome","textWithKeyword":"And I press \"Register\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Register\"","children":[{"start":9,"value":"Register","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":24,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":25,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end