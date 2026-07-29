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
  });

  test('Invite a friend to group', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I have a friend named "fbaggins" with id "11"', null, { page }); 
    await When('I invite to a friend with id "11" to a social group with id "1"', null, { page }); 
    await Then('I should see "Invitation sent"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/socialGroup.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":24,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"And I am on \"/main/social/group_add.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/social/group_add.php\"","children":[{"start":9,"value":"/main/social/group_add.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":28,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":30,"keywordType":"Outcome","textWithKeyword":"Then I fill in editor field \"description\" with \"This is a group created by Behat\"","stepMatchArguments":[{"group":{"start":23,"value":"\"description\"","children":[{"start":24,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"This is a group created by Behat\"","children":[{"start":43,"value":"This is a group created by Behat","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":32,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":33,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":17,"pickleLine":35,"tags":[],"steps":[{"pwStepLine":18,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I have a friend named \"fbaggins\" with id \"11\"","stepMatchArguments":[{"group":{"start":22,"value":"\"fbaggins\"","children":[{"start":23,"value":"fbaggins","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":41,"value":"\"11\"","children":[{"start":42,"value":"11","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":38,"keywordType":"Action","textWithKeyword":"When I invite to a friend with id \"11\" to a social group with id \"1\"","stepMatchArguments":[{"group":{"start":29,"value":"\"11\"","children":[{"start":30,"value":"11","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":60,"value":"\"1\"","children":[{"start":61,"value":"1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Invitation sent\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Invitation sent\"","children":[{"start":14,"value":"Invitation sent","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end