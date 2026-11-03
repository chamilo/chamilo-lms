// Generated from: features/globalReportingSessions.feature
import { test } from "playwright-bdd";

test.describe('Your sessions report', () => {

  test('See the sessions list with its title, filter and columns', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await When('I am on "/reporting/sessions"', null, { page }); 
    await And('I wait up to 60 seconds for the element ".p-datatable" to appear', null, { page }); 
    await Then('I should see "Your sessions"', null, { page }); 
    await And('I should see "Number of courses per session"', null, { page }); 
    await And('I should see "Number of learners by session"', null, { page }); 
    await And('I should not see "Reset"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/globalReportingSessions.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":11,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":12,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":13,"keywordType":"Action","textWithKeyword":"When I am on \"/reporting/sessions\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/reporting/sessions\"","children":[{"start":9,"value":"/reporting/sessions","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":14,"keywordType":"Action","textWithKeyword":"And I wait up to 60 seconds for the element \".p-datatable\" to appear","stepMatchArguments":[{"group":{"start":13,"value":"60"},"parameterTypeName":"int"},{"group":{"start":41,"value":".p-datatable"}}]},{"pwStepLine":10,"gherkinStepLine":15,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Your sessions\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Your sessions\"","children":[{"start":14,"value":"Your sessions","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":16,"keywordType":"Outcome","textWithKeyword":"And I should see \"Number of courses per session\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Number of courses per session\"","children":[{"start":14,"value":"Number of courses per session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":17,"keywordType":"Outcome","textWithKeyword":"And I should see \"Number of learners by session\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Number of learners by session\"","children":[{"start":14,"value":"Number of learners by session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":18,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Reset\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Reset\"","children":[{"start":18,"value":"Reset","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":19,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end