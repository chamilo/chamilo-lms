// Generated from: features/seedSettings.feature
import { test } from "playwright-bdd";

test.describe('Seed platform settings', () => {

  test('Enable group categories before testing', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/settings/search_settings?keyword=allow_group_categories"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I select "Yes" from "form_allow_group_categories"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/seedSettings.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":38,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":39,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/search_settings?keyword=allow_group_categories\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/search_settings?keyword=allow_group_categories\"","children":[{"start":9,"value":"/admin/settings/search_settings?keyword=allow_group_categories","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"And I select \"Yes\" from \"form_allow_group_categories\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Yes\"","children":[{"start":10,"value":"Yes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":20,"value":"\"form_allow_group_categories\"","children":[{"start":21,"value":"form_allow_group_categories","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end