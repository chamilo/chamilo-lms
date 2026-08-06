// Generated from: features/adminFillUsers.feature
import { test } from "playwright-bdd";

test.describe('Fill users', () => {

  test('Create tests users successfully', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await Then('I am on "/main/admin/filler.php?fill=users"', null, { page }); 
    await And('I wait one minute for the page to be loaded', null, { page }); 
    await Then('I should see "Inserted"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Login as student user successfully', async ({ Given, Then, page }) => { 
    await Given('I am a student', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Login as teacher successfully', async ({ Given, Then, page }) => { 
    await Given('I am a teacher', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Login as HRD successfully', async ({ Given, Then, page }) => { 
    await Given('I am an HR manager', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Login as student boss successfully', async ({ Given, Then, page }) => { 
    await Given('I am a student boss', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Login as invitee successfully', async ({ Given, Then, page }) => { 
    await Given('I am an invitee', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminFillUsers.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":3,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":4,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":5,"keywordType":"Outcome","textWithKeyword":"Then I am on \"/main/admin/filler.php?fill=users\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/filler.php?fill=users\"","children":[{"start":9,"value":"/main/admin/filler.php?fill=users","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":6,"keywordType":"Outcome","textWithKeyword":"And I wait one minute for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":7,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Inserted\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Inserted\"","children":[{"start":14,"value":"Inserted","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":8,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":14,"pickleLine":10,"tags":[],"steps":[{"pwStepLine":15,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":12,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":19,"pickleLine":14,"tags":[],"steps":[{"pwStepLine":20,"gherkinStepLine":15,"keywordType":"Context","textWithKeyword":"Given I am a teacher","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":16,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":24,"pickleLine":18,"tags":[],"steps":[{"pwStepLine":25,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am an HR manager","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":20,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":29,"pickleLine":22,"tags":[],"steps":[{"pwStepLine":30,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"Given I am a student boss","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":24,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":34,"pickleLine":26,"tags":[],"steps":[{"pwStepLine":35,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"Given I am an invitee","stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end