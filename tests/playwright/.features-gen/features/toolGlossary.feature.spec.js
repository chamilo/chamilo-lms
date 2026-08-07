// Generated from: features/toolGlossary.feature
import { test } from "playwright-bdd";

test.describe('Glossary tool', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Create glossary term in course TEMP', { tag: ['@common', '@tools', '@settings-toolGlossary'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Glossary"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await And('I press "Add new glossary term"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I create the glossary term "Device" with description "a device is a thing"', null, { page }); 
    await Then('I should see "Device"', null, { page }); 
  });

  test('Add glossary link from Documents in course TEMP', { tag: ['@common', '@tools', '@settings-toolGlossary'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Documents"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await And('I press "New document"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"Title"},{"value":"Glossary"}]}]}}, { page }); 
    await And('I fill in tinymce field "item_content" with "Several words, including device"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Glossary"', null, { page }); 
  });

  test('Enable glossary display in extra tools from admin settings', { tag: ['@common', '@tools', '@settings-toolGlossary'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/admin/settings/glossary"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I select "Learning path" from "form_show_glossary_in_extra_tools"', null, { page }); 
    await And('I press "Save settings"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test.skip('Create Learning path named Glossary in course TEMP', { tag: ['@common', '@tools', '@settings-toolGlossary', '@skip'] }, async ({ Given, When, Then, And }) => { 
    await Given('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow "Learning paths"'); 
    await And('I wait for the page content to settle'); 
    await And('I click the "button[title=\'More actions\']" element'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow "Create new learning path"'); 
    await And('I wait for the page to be loaded'); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"Learning path name"},{"value":"Glossary"}]}]}}); 
    await And('I press "Continue"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "Glossary"'); 
    await And('I delete the learning path I just created'); 
  });

  test('Glossary export form is reachable', { tag: ['@common', '@tools', '@settings-toolGlossary'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Glossary"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await And('I press "Export glossary"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Export format"', null, { page }); 
  });

});

// == technical section ==

test.beforeAll('BeforeAll Hooks', ({ $runBeforeAllHooks, baseURL, browser }) => $runBeforeAllHooks(test, { baseURL, browser }, bddFileData));
test.afterAll('AfterAll Hooks', ({ $registerAfterAllHooks }) => $registerAfterAllHooks(test, {  }, bddFileData));

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolGlossary.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":95,"tags":["@common","@tools","@settings-toolGlossary"],"steps":[{"pwStepLine":7,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":96,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":97,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"And I follow \"Glossary\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Glossary\"","children":[{"start":10,"value":"Glossary","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":100,"keywordType":"Context","textWithKeyword":"And I press \"Add new glossary term\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add new glossary term\"","children":[{"start":9,"value":"Add new glossary term","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":101,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":102,"keywordType":"Action","textWithKeyword":"When I create the glossary term \"Device\" with description \"a device is a thing\"","stepMatchArguments":[{"group":{"start":27,"value":"\"Device\"","children":[{"start":28,"value":"Device","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":53,"value":"\"a device is a thing\"","children":[{"start":54,"value":"a device is a thing","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":103,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Device\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Device\"","children":[{"start":14,"value":"Device","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":22,"pickleLine":105,"tags":["@common","@tools","@settings-toolGlossary"],"steps":[{"pwStepLine":7,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":106,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":107,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":108,"keywordType":"Context","textWithKeyword":"And I follow \"Documents\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Documents\"","children":[{"start":10,"value":"Documents","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":109,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":27,"gherkinStepLine":110,"keywordType":"Context","textWithKeyword":"And I press \"New document\"","stepMatchArguments":[{"group":{"start":8,"value":"\"New document\"","children":[{"start":9,"value":"New document","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":28,"gherkinStepLine":111,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":112,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":114,"keywordType":"Action","textWithKeyword":"And I fill in tinymce field \"item_content\" with \"Several words, including device\"","stepMatchArguments":[{"group":{"start":24,"value":"\"item_content\"","children":[{"start":25,"value":"item_content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":44,"value":"\"Several words, including device\"","children":[{"start":45,"value":"Several words, including device","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":115,"keywordType":"Action","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":116,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":117,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Glossary\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Glossary\"","children":[{"start":14,"value":"Glossary","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":36,"pickleLine":119,"tags":["@common","@tools","@settings-toolGlossary"],"steps":[{"pwStepLine":7,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":120,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/settings/glossary\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/glossary\"","children":[{"start":9,"value":"/admin/settings/glossary","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":121,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":122,"keywordType":"Action","textWithKeyword":"When I select \"Learning path\" from \"form_show_glossary_in_extra_tools\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Learning path\"","children":[{"start":10,"value":"Learning path","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":30,"value":"\"form_show_glossary_in_extra_tools\"","children":[{"start":31,"value":"form_show_glossary_in_extra_tools","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":123,"keywordType":"Action","textWithKeyword":"And I press \"Save settings\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save settings\"","children":[{"start":9,"value":"Save settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":124,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":42,"gherkinStepLine":125,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":45,"pickleLine":134,"skipped":true,"tags":["@common","@tools","@settings-toolGlossary","@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":8,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true},{"pwStepLine":46,"gherkinStepLine":135,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage"},{"pwStepLine":47,"gherkinStepLine":136,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":48,"gherkinStepLine":137,"keywordType":"Context","textWithKeyword":"And I follow \"Learning paths\""},{"pwStepLine":49,"gherkinStepLine":138,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle"},{"pwStepLine":50,"gherkinStepLine":139,"keywordType":"Context","textWithKeyword":"And I click the \"button[title='More actions']\" element"},{"pwStepLine":51,"gherkinStepLine":140,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":52,"gherkinStepLine":141,"keywordType":"Context","textWithKeyword":"And I follow \"Create new learning path\""},{"pwStepLine":53,"gherkinStepLine":142,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":54,"gherkinStepLine":143,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":55,"gherkinStepLine":145,"keywordType":"Action","textWithKeyword":"And I press \"Continue\""},{"pwStepLine":56,"gherkinStepLine":146,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":57,"gherkinStepLine":147,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Glossary\""},{"pwStepLine":58,"gherkinStepLine":157,"keywordType":"Outcome","textWithKeyword":"And I delete the learning path I just created"}]},
  {"pwTestLine":61,"pickleLine":159,"tags":["@common","@tools","@settings-toolGlossary"],"steps":[{"pwStepLine":7,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":62,"gherkinStepLine":160,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":63,"gherkinStepLine":161,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":64,"gherkinStepLine":162,"keywordType":"Context","textWithKeyword":"And I follow \"Glossary\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Glossary\"","children":[{"start":10,"value":"Glossary","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":65,"gherkinStepLine":163,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":66,"gherkinStepLine":164,"keywordType":"Context","textWithKeyword":"And I press \"Export glossary\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Export glossary\"","children":[{"start":9,"value":"Export glossary","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":67,"gherkinStepLine":165,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":68,"gherkinStepLine":166,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Export format\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Export format\"","children":[{"start":14,"value":"Export format","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end