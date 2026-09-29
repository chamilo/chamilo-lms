// Generated from: features/class.feature
import { test } from "playwright-bdd";

test.describe('Classes', () => {

  test('Create a class', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/usergroups"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await When('I press "Add a class"', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Class 1"}]},{"cells":[{"value":"description"},{"value":"Description"}]}]}}, { page }); 
    await And('I attach the file "/public/img/logo.png" to "picture"', null, { page }); 
    await And('I press "Add"', null, { page }); 
    await Then('I should see "Class 1"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Edit a class', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/usergroups"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Class 1"', null, { page }); 
    await And('I click the "button[aria-label=\'Edit\']" icon in the row for "Class 1"', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Class 1 Edited"}]}]}}, { page }); 
    await And('I press "Save"', null, { page }); 
    await Then('I should see "Class 1 Edited"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Delete a class', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/usergroups"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Class 1 Edited"', null, { page }); 
    await When('I click the "button[aria-label=\'Delete\']" icon in the row for "Class 1 Edited"', null, { page }); 
    await And('I press "Yes"', null, { page }); 
    await Then('I should not see "Class 1 Edited"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/class.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":20,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/usergroups\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/usergroups\"","children":[{"start":9,"value":"/admin/usergroups","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":24,"keywordType":"Action","textWithKeyword":"When I press \"Add a class\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add a class\"","children":[{"start":9,"value":"Add a class","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":25,"keywordType":"Action","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":28,"keywordType":"Action","textWithKeyword":"And I attach the file \"/public/img/logo.png\" to \"picture\"","stepMatchArguments":[{"group":{"start":18,"value":"\"/public/img/logo.png\"","children":[{"start":19,"value":"/public/img/logo.png","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":44,"value":"\"picture\"","children":[{"start":45,"value":"picture","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":29,"keywordType":"Action","textWithKeyword":"And I press \"Add\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add\"","children":[{"start":9,"value":"Add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":30,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Class 1\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Class 1\"","children":[{"start":14,"value":"Class 1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":18,"pickleLine":33,"tags":[],"steps":[{"pwStepLine":19,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/usergroups\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/usergroups\"","children":[{"start":9,"value":"/admin/usergroups","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Class 1\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Class 1\"","children":[{"start":14,"value":"Class 1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"And I click the \"button[aria-label='Edit']\" icon in the row for \"Class 1\"","stepMatchArguments":[{"group":{"start":12,"value":"\"button[aria-label='Edit']\"","children":[{"start":13,"value":"button[aria-label='Edit']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":60,"value":"\"Class 1\"","children":[{"start":61,"value":"Class 1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":41,"keywordType":"Outcome","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":42,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Class 1 Edited\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Class 1 Edited\"","children":[{"start":14,"value":"Class 1 Edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":43,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":30,"pickleLine":45,"tags":[],"steps":[{"pwStepLine":31,"gherkinStepLine":46,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":47,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/usergroups\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/usergroups\"","children":[{"start":9,"value":"/admin/usergroups","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":34,"gherkinStepLine":49,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Class 1 Edited\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Class 1 Edited\"","children":[{"start":14,"value":"Class 1 Edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":35,"gherkinStepLine":50,"keywordType":"Action","textWithKeyword":"When I click the \"button[aria-label='Delete']\" icon in the row for \"Class 1 Edited\"","stepMatchArguments":[{"group":{"start":12,"value":"\"button[aria-label='Delete']\"","children":[{"start":13,"value":"button[aria-label='Delete']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":62,"value":"\"Class 1 Edited\"","children":[{"start":63,"value":"Class 1 Edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":36,"gherkinStepLine":51,"keywordType":"Action","textWithKeyword":"And I press \"Yes\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Yes\"","children":[{"start":9,"value":"Yes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":52,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Class 1 Edited\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Class 1 Edited\"","children":[{"start":18,"value":"Class 1 Edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end