// Generated from: features/socialGroup.feature
import { test } from "playwright-bdd";

test.describe('Social Group', () => {

  test('Create a social group', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/social/group_add.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Behat Test Group"}]}]}}, { page }); 
    await Then('I fill in editor field "description" with "This is a group created by Behat"', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I remember the created group id', null, { page }); 
  });

  test('Invite a friend to group', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I have a friend named "fbaggins" with id "11"', null, { page }); 
    await When('I invite to a friend with id "11" to the social group I just created', null, { page }); 
    await Then('I should see "Users already invited"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/socialGroup.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":48,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And I am on \"/main/social/group_add.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/social/group_add.php\"","children":[{"start":9,"value":"/main/social/group_add.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":52,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":54,"keywordType":"Outcome","textWithKeyword":"Then I fill in editor field \"description\" with \"This is a group created by Behat\"","stepMatchArguments":[{"group":{"start":23,"value":"\"description\"","children":[{"start":24,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"This is a group created by Behat\"","children":[{"start":43,"value":"This is a group created by Behat","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":55,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":56,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":57,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":58,"keywordType":"Outcome","textWithKeyword":"And I remember the created group id","stepMatchArguments":[]}]},
  {"pwTestLine":18,"pickleLine":60,"tags":[],"steps":[{"pwStepLine":19,"gherkinStepLine":61,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":62,"keywordType":"Context","textWithKeyword":"And I have a friend named \"fbaggins\" with id \"11\"","stepMatchArguments":[{"group":{"start":22,"value":"\"fbaggins\"","children":[{"start":23,"value":"fbaggins","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":41,"value":"\"11\"","children":[{"start":42,"value":"11","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":63,"keywordType":"Action","textWithKeyword":"When I invite to a friend with id \"11\" to the social group I just created","stepMatchArguments":[{"group":{"start":29,"value":"\"11\"","children":[{"start":30,"value":"11","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":22,"gherkinStepLine":64,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Users already invited\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Users already invited\"","children":[{"start":14,"value":"Users already invited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":65,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end