// Generated from: features/adminSettings.feature
import { test } from "playwright-bdd";

test.describe('Settings update', () => {

  test('Update \'profile\' setting', { tag: ['@settings'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/settings/search_settings?keyword=changeable_options"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I select "Name" from "form_changeable_options"', null, { page }); 
    await And('I additionally select "E-mail" from "form_changeable_options"', null, { page }); 
    await And('I additionally select "Official code" from "form_changeable_options"', null, { page }); 
    await And('I additionally select "Login" from "form_changeable_options"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Update \'allow_registration\' setting', { tag: ['@settings'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/settings/search_settings?keyword=allow_registration"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I select "Yes" from "form_allow_registration"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Update \'allow_group_categories\' setting', { tag: ['@settings'] }, async ({ Given, Then, And, page }) => { 
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

test.beforeAll('BeforeAll Hooks', ({ $runBeforeAllHooks, baseURL, browser }) => $runBeforeAllHooks(test, { baseURL, browser }, bddFileData));
test.afterAll('AfterAll Hooks', ({ $registerAfterAllHooks }) => $registerAfterAllHooks(test, {  }, bddFileData));

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminSettings.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":15,"tags":["@settings"],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/search_settings?keyword=changeable_options\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/search_settings?keyword=changeable_options\"","children":[{"start":9,"value":"/admin/settings/search_settings?keyword=changeable_options","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"And I select \"Name\" from \"form_changeable_options\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Name\"","children":[{"start":10,"value":"Name","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":21,"value":"\"form_changeable_options\"","children":[{"start":22,"value":"form_changeable_options","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"And I additionally select \"E-mail\" from \"form_changeable_options\"","stepMatchArguments":[{"group":{"start":22,"value":"\"E-mail\"","children":[{"start":23,"value":"E-mail","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":36,"value":"\"form_changeable_options\"","children":[{"start":37,"value":"form_changeable_options","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"And I additionally select \"Official code\" from \"form_changeable_options\"","stepMatchArguments":[{"group":{"start":22,"value":"\"Official code\"","children":[{"start":23,"value":"Official code","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":43,"value":"\"form_changeable_options\"","children":[{"start":44,"value":"form_changeable_options","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"And I additionally select \"Login\" from \"form_changeable_options\"","stepMatchArguments":[{"group":{"start":22,"value":"\"Login\"","children":[{"start":23,"value":"Login","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":35,"value":"\"form_changeable_options\"","children":[{"start":36,"value":"form_changeable_options","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":25,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":19,"pickleLine":27,"tags":["@settings"],"steps":[{"pwStepLine":20,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/search_settings?keyword=allow_registration\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/search_settings?keyword=allow_registration\"","children":[{"start":9,"value":"/admin/settings/search_settings?keyword=allow_registration","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":22,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"And I select \"Yes\" from \"form_allow_registration\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Yes\"","children":[{"start":10,"value":"Yes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":20,"value":"\"form_allow_registration\"","children":[{"start":21,"value":"form_allow_registration","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":25,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":34,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":29,"pickleLine":36,"tags":["@settings"],"steps":[{"pwStepLine":30,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/search_settings?keyword=allow_group_categories\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/search_settings?keyword=allow_group_categories\"","children":[{"start":9,"value":"/admin/settings/search_settings?keyword=allow_group_categories","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":39,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"And I select \"Yes\" from \"form_allow_group_categories\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Yes\"","children":[{"start":10,"value":"Yes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":20,"value":"\"form_allow_group_categories\"","children":[{"start":21,"value":"form_allow_group_categories","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":35,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":43,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end