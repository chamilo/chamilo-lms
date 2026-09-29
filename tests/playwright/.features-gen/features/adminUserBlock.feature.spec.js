// Generated from: features/adminUserBlock.feature
import { test } from "playwright-bdd";

test.describe('Admin User management block', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Open User list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "User list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Add a user', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Add a user"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Export users list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Export users list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Import users list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Import users list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Edit users list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Edit users list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Anonymise users list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Anonymise users list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Profiling', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Profiling"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Classes', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Classes"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminUserBlock.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":19,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"And I follow \"User list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"User list\"","children":[{"start":10,"value":"User list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":24,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":19,"pickleLine":26,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I follow \"Add a user\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Add a user\"","children":[{"start":10,"value":"Add a user","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":27,"pickleLine":33,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I follow \"Export users list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Export users list\"","children":[{"start":10,"value":"Export users list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":35,"pickleLine":40,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I follow \"Import users list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Import users list\"","children":[{"start":10,"value":"Import users list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":39,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":43,"pickleLine":47,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And I follow \"Edit users list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Edit users list\"","children":[{"start":10,"value":"Edit users list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":48,"gherkinStepLine":52,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":51,"pickleLine":54,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":55,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":56,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":57,"keywordType":"Context","textWithKeyword":"And I follow \"Anonymise users list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Anonymise users list\"","children":[{"start":10,"value":"Anonymise users list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":55,"gherkinStepLine":58,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":56,"gherkinStepLine":59,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":59,"pickleLine":61,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":60,"gherkinStepLine":62,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":61,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":62,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I follow \"Profiling\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Profiling\"","children":[{"start":10,"value":"Profiling","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":63,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":64,"gherkinStepLine":66,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":67,"pickleLine":68,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":68,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":69,"gherkinStepLine":70,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":70,"gherkinStepLine":71,"keywordType":"Context","textWithKeyword":"And I follow \"Classes\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Classes\"","children":[{"start":10,"value":"Classes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":71,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":72,"gherkinStepLine":73,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end