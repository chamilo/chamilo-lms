// Generated from: features/breadcrumb.feature
import { test } from "playwright-bdd";

test.describe('Breadcrumb visibility', () => {

  test('The personal agenda shows no breadcrumb', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await When('I am on "/resources/ccalendarevent"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see the ".app-breadcrumb" element', null, { page }); 
  });

  test('The course agenda shows a breadcrumb', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await When('I click the "a[href*=\'ccalendarevent\'][href*=\'cid=\']" element', null, { page }); 
    await And('I wait for the element ".app-breadcrumb" to appear', null, { page }); 
    await Then('I should see the ".app-breadcrumb" element', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/breadcrumb.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":9,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":10,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":11,"keywordType":"Action","textWithKeyword":"When I am on \"/resources/ccalendarevent\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/resources/ccalendarevent\"","children":[{"start":9,"value":"/resources/ccalendarevent","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":12,"keywordType":"Action","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":13,"keywordType":"Outcome","textWithKeyword":"Then I should not see the \".app-breadcrumb\" element","stepMatchArguments":[{"group":{"start":21,"value":"\".app-breadcrumb\"","children":[{"start":22,"value":".app-breadcrumb","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":13,"pickleLine":15,"tags":[],"steps":[{"pwStepLine":14,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":18,"keywordType":"Action","textWithKeyword":"When I click the \"a[href*='ccalendarevent'][href*='cid=']\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"a[href*='ccalendarevent'][href*='cid=']\"","children":[{"start":13,"value":"a[href*='ccalendarevent'][href*='cid=']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":19,"keywordType":"Action","textWithKeyword":"And I wait for the element \".app-breadcrumb\" to appear","stepMatchArguments":[{"group":{"start":24,"value":".app-breadcrumb"}}]},{"pwStepLine":18,"gherkinStepLine":20,"keywordType":"Outcome","textWithKeyword":"Then I should see the \".app-breadcrumb\" element","stepMatchArguments":[{"group":{"start":17,"value":"\".app-breadcrumb\"","children":[{"start":18,"value":".app-breadcrumb","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end