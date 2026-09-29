// Generated from: features/extraFieldUser.feature
import { test } from "playwright-bdd";

test.describe('User extra fields', () => {

  test('Create a text extra field', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/extra_fields.php?type=user&action=add"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"display_text"},{"value":"Behat extra field"}]},{"cells":[{"value":"variable"},{"value":"behat_extra_field"}]}]}}, { page }); 
    await And('I select "Text" from "value_type"', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('the URL should not contain "action=add"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/extraFieldUser.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":29,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/extra_fields.php?type=user&action=add\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/extra_fields.php?type=user&action=add\"","children":[{"start":9,"value":"/main/admin/extra_fields.php?type=user&action=add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":33,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":36,"keywordType":"Action","textWithKeyword":"And I select \"Text\" from \"value_type\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Text\"","children":[{"start":10,"value":"Text","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":21,"value":"\"value_type\"","children":[{"start":22,"value":"value_type","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":37,"keywordType":"Action","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":38,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"Then the URL should not contain \"action=add\"","stepMatchArguments":[{"group":{"start":27,"value":"\"action=add\"","children":[{"start":28,"value":"action=add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":40,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end