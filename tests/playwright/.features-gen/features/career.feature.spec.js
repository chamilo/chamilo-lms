// Generated from: features/career.feature
import { test } from "playwright-bdd";

test.describe('Career', () => {

  test('Create a career', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/career_dashboard.php"', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I am on "/main/admin/careers.php?action=add"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"career_title"},{"value":"Developer"}]}]}}, { page }); 
    await And('I fill in editor field "description" with "Description"', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Developer"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Edit a career', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/careers.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should see "Developer"', null, { page }); 
    await And('I click the "i.mdi-pencil" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in editor field "description" with "Description edited"', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Developer"', null, { page }); 
  });

  test('Copy a career', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/careers.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I should not see an error', null, { page }); 
    await And('I should see "Developer"', null, { page }); 
    await When('I click the "i.mdi-text-box-plus" element', null, { page }); 
    await And('I confirm the popup', null, { page }); 
    await Then('I wait for the page to be loaded', null, { page }); 
    await And('I should not see an error', null, { page }); 
    await And('I should see "Developer Copy"', null, { page }); 
  });

  test('Delete a career', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/careers.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should see "Developer"', null, { page }); 
    await When('I click the "i.mdi-delete" element', null, { page }); 
    await And('I confirm the popup', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await When('I click the "i.mdi-delete" element', null, { page }); 
    await And('I confirm the popup', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should not see "Developer Copy"', null, { page }); 
    await And('I should not see "Developer"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/career.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":6,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":7,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":8,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/career_dashboard.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/career_dashboard.php\"","children":[{"start":9,"value":"/main/admin/career_dashboard.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":9,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":10,"keywordType":"Outcome","textWithKeyword":"And I am on \"/main/admin/careers.php?action=add\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/careers.php?action=add\"","children":[{"start":9,"value":"/main/admin/careers.php?action=add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":11,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":12,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":14,"keywordType":"Action","textWithKeyword":"And I fill in editor field \"description\" with \"Description\"","stepMatchArguments":[{"group":{"start":23,"value":"\"description\"","children":[{"start":24,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"Description\"","children":[{"start":43,"value":"Description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":15,"keywordType":"Action","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":16,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":17,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Developer\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Developer\"","children":[{"start":14,"value":"Developer","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":18,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":20,"pickleLine":20,"tags":[],"steps":[{"pwStepLine":21,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/careers.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/careers.php\"","children":[{"start":9,"value":"/main/admin/careers.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":24,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":25,"keywordType":"Outcome","textWithKeyword":"And I should see \"Developer\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Developer\"","children":[{"start":14,"value":"Developer","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":26,"keywordType":"Outcome","textWithKeyword":"And I click the \"i.mdi-pencil\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-pencil\"","children":[{"start":13,"value":"i.mdi-pencil","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":27,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"And I fill in editor field \"description\" with \"Description edited\"","stepMatchArguments":[{"group":{"start":23,"value":"\"description\"","children":[{"start":24,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"Description edited\"","children":[{"start":43,"value":"Description edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":29,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":30,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Developer\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Developer\"","children":[{"start":14,"value":"Developer","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":34,"pickleLine":33,"tags":[],"steps":[{"pwStepLine":35,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/careers.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/careers.php\"","children":[{"start":9,"value":"/main/admin/careers.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I should not see an error","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I should see \"Developer\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Developer\"","children":[{"start":14,"value":"Developer","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":39,"keywordType":"Action","textWithKeyword":"When I click the \"i.mdi-text-box-plus\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-text-box-plus\"","children":[{"start":13,"value":"i.mdi-text-box-plus","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":40,"keywordType":"Action","textWithKeyword":"And I confirm the popup","stepMatchArguments":[]},{"pwStepLine":42,"gherkinStepLine":41,"keywordType":"Outcome","textWithKeyword":"Then I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":42,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":43,"keywordType":"Outcome","textWithKeyword":"And I should see \"Developer Copy\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Developer Copy\"","children":[{"start":14,"value":"Developer Copy","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":47,"pickleLine":45,"tags":[],"steps":[{"pwStepLine":48,"gherkinStepLine":46,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":49,"gherkinStepLine":47,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/careers.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/careers.php\"","children":[{"start":9,"value":"/main/admin/careers.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":50,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":51,"gherkinStepLine":49,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":50,"keywordType":"Outcome","textWithKeyword":"And I should see \"Developer\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Developer\"","children":[{"start":14,"value":"Developer","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":51,"keywordType":"Action","textWithKeyword":"When I click the \"i.mdi-delete\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-delete\"","children":[{"start":13,"value":"i.mdi-delete","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":54,"gherkinStepLine":52,"keywordType":"Action","textWithKeyword":"And I confirm the popup","stepMatchArguments":[]},{"pwStepLine":55,"gherkinStepLine":53,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":56,"gherkinStepLine":54,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":57,"gherkinStepLine":55,"keywordType":"Action","textWithKeyword":"When I click the \"i.mdi-delete\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-delete\"","children":[{"start":13,"value":"i.mdi-delete","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":58,"gherkinStepLine":56,"keywordType":"Action","textWithKeyword":"And I confirm the popup","stepMatchArguments":[]},{"pwStepLine":59,"gherkinStepLine":57,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":60,"gherkinStepLine":58,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":61,"gherkinStepLine":59,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Developer Copy\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Developer Copy\"","children":[{"start":18,"value":"Developer Copy","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":62,"gherkinStepLine":60,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Developer\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Developer\"","children":[{"start":18,"value":"Developer","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end