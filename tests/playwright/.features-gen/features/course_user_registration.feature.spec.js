// Generated from: features/course_user_registration.feature
import { test } from "playwright-bdd";

test.describe('Subscribe users to the course', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
  });
  
  test('Subscribe "amann" as student to the course "TEMP"', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/user/subscribe_user.php?keyword=amann&type=5&cid=3"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Aimee"', null, { page }); 
    await Then('I follow "Register"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Unsubscribe user "amann" the course "TEMP"', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/user/user.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Aimee"', null, { page }); 
    await Then('I follow "Unsubscribe"', null, { page }); 
    await And('I confirm the popup', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Subscribe "acostea" as student to the course "TEMP" (leave it subscribed for further tests)', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/user/subscribe_user.php?keyword=acostea&type=5&cid=3"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Andrea"', null, { page }); 
    await Then('I follow "Register"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Subscribe "fapple" as student to the course "TEMP" (leave it subscribed for further tests)', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/user/subscribe_user.php?keyword=fapple&type=5&cid=3"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Fiona"', null, { page }); 
    await Then('I follow "Register"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Subscribe "amann" again as student to the course "TEMP" (leave it subscribed for further tests)', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/user/subscribe_user.php?keyword=amann&type=5&cid=3"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Aimee"', null, { page }); 
    await Then('I follow "Register"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/course_user_registration.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":6,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":4,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":7,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/user/subscribe_user.php?keyword=amann&type=5&cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/subscribe_user.php?keyword=amann&type=5&cid=3\"","children":[{"start":9,"value":"/main/user/subscribe_user.php?keyword=amann&type=5&cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":8,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":9,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Aimee\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Aimee\"","children":[{"start":14,"value":"Aimee","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":10,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Register\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Register\"","children":[{"start":10,"value":"Register","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":11,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":12,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":19,"pickleLine":14,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":4,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":15,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/user/user.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/user.php?cid=3\"","children":[{"start":9,"value":"/main/user/user.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":17,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Aimee\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Aimee\"","children":[{"start":14,"value":"Aimee","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":18,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Unsubscribe\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Unsubscribe\"","children":[{"start":10,"value":"Unsubscribe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":19,"keywordType":"Outcome","textWithKeyword":"And I confirm the popup","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":20,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":21,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":29,"pickleLine":23,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":4,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/user/subscribe_user.php?keyword=acostea&type=5&cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/subscribe_user.php?keyword=acostea&type=5&cid=3\"","children":[{"start":9,"value":"/main/user/subscribe_user.php?keyword=acostea&type=5&cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":26,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Andrea\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Andrea\"","children":[{"start":14,"value":"Andrea","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":27,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Register\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Register\"","children":[{"start":10,"value":"Register","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":29,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":38,"pickleLine":31,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":4,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/user/subscribe_user.php?keyword=fapple&type=5&cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/subscribe_user.php?keyword=fapple&type=5&cid=3\"","children":[{"start":9,"value":"/main/user/subscribe_user.php?keyword=fapple&type=5&cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":41,"gherkinStepLine":34,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Fiona\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Fiona\"","children":[{"start":14,"value":"Fiona","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":42,"gherkinStepLine":35,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Register\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Register\"","children":[{"start":10,"value":"Register","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":43,"gherkinStepLine":36,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":47,"pickleLine":39,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":4,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":48,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/user/subscribe_user.php?keyword=amann&type=5&cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/subscribe_user.php?keyword=amann&type=5&cid=3\"","children":[{"start":9,"value":"/main/user/subscribe_user.php?keyword=amann&type=5&cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":49,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":50,"gherkinStepLine":42,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Aimee\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Aimee\"","children":[{"start":14,"value":"Aimee","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":51,"gherkinStepLine":43,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Register\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Register\"","children":[{"start":10,"value":"Register","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":52,"gherkinStepLine":44,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":53,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end