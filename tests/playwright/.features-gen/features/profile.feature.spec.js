// Generated from: features/profile.feature
import { test } from "playwright-bdd";

test.describe('Profile page', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a student', null, { page }); 
  });
  
  test('Change user first name with Andrew then restore to Andrea', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/account/home"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Edit profile"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"profile_firstname"},{"value":"Andrew"}]},{"cells":[{"value":"profile_lastname"},{"value":"Doe"}]}]}}, { page }); 
    await And('I press "update_profile"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Edit profile"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Andrew"', null, { page }); 
    await And('I should see "Doe"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"profile_firstname"},{"value":"Andrea"}]},{"cells":[{"value":"profile_lastname"},{"value":"Costea"}]}]}}, { page }); 
    await And('I press "update_profile"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I follow "Edit profile"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Andrea"', null, { page }); 
    await And('I should see "Costea"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/profile.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":23,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"Given I am a student","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am on \"/account/home\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/account/home\"","children":[{"start":9,"value":"/account/home","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"And I follow \"Edit profile\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Edit profile\"","children":[{"start":10,"value":"Edit profile","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"And I press \"update_profile\"","stepMatchArguments":[{"group":{"start":8,"value":"\"update_profile\"","children":[{"start":9,"value":"update_profile","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"And I follow \"Edit profile\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Edit profile\"","children":[{"start":10,"value":"Edit profile","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":35,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Andrew\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Andrew\"","children":[{"start":14,"value":"Andrew","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":36,"keywordType":"Outcome","textWithKeyword":"And I should see \"Doe\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Doe\"","children":[{"start":14,"value":"Doe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":22,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":41,"keywordType":"Outcome","textWithKeyword":"And I press \"update_profile\"","stepMatchArguments":[{"group":{"start":8,"value":"\"update_profile\"","children":[{"start":9,"value":"update_profile","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":25,"gherkinStepLine":42,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":43,"keywordType":"Outcome","textWithKeyword":"And I follow \"Edit profile\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Edit profile\"","children":[{"start":10,"value":"Edit profile","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":44,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Andrea\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Andrea\"","children":[{"start":14,"value":"Andrea","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"And I should see \"Costea\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Costea\"","children":[{"start":14,"value":"Costea","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":47,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end