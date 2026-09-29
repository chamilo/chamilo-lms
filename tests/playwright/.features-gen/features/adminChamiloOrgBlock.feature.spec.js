// Generated from: features/adminChamiloOrgBlock.feature
import { test } from "playwright-bdd";

test.describe('Admin Chamilo.org block navigation', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Open Chamilo homepage', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Chamilo homepage"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open User guides', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "User guides"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Chamilo forum', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Chamilo forum"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Installation guide', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Installation guide"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Changes in last version', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Changes in last version"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Contributors list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Contributors list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Security guide', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Security guide"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Optimization guide', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Optimization guide"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Chamilo official services providers', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Chamilo official services providers"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminChamiloOrgBlock.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":40,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I follow \"Chamilo homepage\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Chamilo homepage\"","children":[{"start":10,"value":"Chamilo homepage","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":19,"pickleLine":47,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And I follow \"User guides\"","stepMatchArguments":[{"group":{"start":9,"value":"\"User guides\"","children":[{"start":10,"value":"User guides","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":52,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":27,"pickleLine":54,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":55,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":56,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":57,"keywordType":"Context","textWithKeyword":"And I follow \"Chamilo forum\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Chamilo forum\"","children":[{"start":10,"value":"Chamilo forum","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":58,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":59,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":35,"pickleLine":61,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":62,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I follow \"Installation guide\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Installation guide\"","children":[{"start":10,"value":"Installation guide","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":39,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":66,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":43,"pickleLine":68,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":70,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":71,"keywordType":"Context","textWithKeyword":"And I follow \"Changes in last version\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Changes in last version\"","children":[{"start":10,"value":"Changes in last version","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":48,"gherkinStepLine":73,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":51,"pickleLine":75,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":76,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":77,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":78,"keywordType":"Context","textWithKeyword":"And I follow \"Contributors list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Contributors list\"","children":[{"start":10,"value":"Contributors list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":55,"gherkinStepLine":79,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":56,"gherkinStepLine":80,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":59,"pickleLine":82,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":60,"gherkinStepLine":83,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":61,"gherkinStepLine":84,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":62,"gherkinStepLine":85,"keywordType":"Context","textWithKeyword":"And I follow \"Security guide\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Security guide\"","children":[{"start":10,"value":"Security guide","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":63,"gherkinStepLine":86,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":64,"gherkinStepLine":87,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":67,"pickleLine":89,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":68,"gherkinStepLine":90,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":69,"gherkinStepLine":91,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":70,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"And I follow \"Optimization guide\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Optimization guide\"","children":[{"start":10,"value":"Optimization guide","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":71,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":72,"gherkinStepLine":94,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":75,"pickleLine":96,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":76,"gherkinStepLine":97,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":77,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":78,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I follow \"Chamilo official services providers\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Chamilo official services providers\"","children":[{"start":10,"value":"Chamilo official services providers","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":79,"gherkinStepLine":100,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":80,"gherkinStepLine":101,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end