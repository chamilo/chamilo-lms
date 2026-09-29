// Generated from: features/adminSessionBlock.feature
import { test } from "playwright-bdd";

test.describe('Admin Sessions management block', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Open Training sessions list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Training sessions list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Add a training session', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Add a training session"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Sessions categories list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Sessions categories list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Import sessions list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Import sessions list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Import list of HR directors into sessions', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Import list of HR directors into sessions"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Export sessions list', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Export sessions list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Copy from course in session to another session', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Copy from course in session to another session"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Move users results from base course to a session', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Move users results from base course to a session"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Careers and promotions', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Careers and promotions"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Manage session fields', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Manage session fields"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Resources sequencing (sessions)', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Resources sequencing"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Export all results from an exercise', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Export all results from an exercise"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminSessionBlock.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":20,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And I follow \"Training sessions list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Training sessions list\"","children":[{"start":10,"value":"Training sessions list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":25,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":19,"pickleLine":27,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I follow \"Add a training session\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Add a training session\"","children":[{"start":10,"value":"Add a training session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":32,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":27,"pickleLine":34,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I follow \"Sessions categories list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Sessions categories list\"","children":[{"start":10,"value":"Sessions categories list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":35,"pickleLine":41,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And I follow \"Import sessions list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Import sessions list\"","children":[{"start":10,"value":"Import sessions list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":39,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":43,"pickleLine":48,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I follow \"Import list of HR directors into sessions\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Import list of HR directors into sessions\"","children":[{"start":10,"value":"Import list of HR directors into sessions","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":52,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":48,"gherkinStepLine":53,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":51,"pickleLine":55,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":56,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":57,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":58,"keywordType":"Context","textWithKeyword":"And I follow \"Export sessions list\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Export sessions list\"","children":[{"start":10,"value":"Export sessions list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":55,"gherkinStepLine":59,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":56,"gherkinStepLine":60,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":59,"pickleLine":62,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":60,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":61,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":62,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And I follow \"Copy from course in session to another session\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Copy from course in session to another session\"","children":[{"start":10,"value":"Copy from course in session to another session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":63,"gherkinStepLine":66,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":64,"gherkinStepLine":67,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":67,"pickleLine":69,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":68,"gherkinStepLine":70,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":69,"gherkinStepLine":71,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":70,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"And I follow \"Move users results from base course to a session\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Move users results from base course to a session\"","children":[{"start":10,"value":"Move users results from base course to a session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":71,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":72,"gherkinStepLine":74,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":75,"pickleLine":76,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":76,"gherkinStepLine":77,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":77,"gherkinStepLine":78,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":78,"gherkinStepLine":79,"keywordType":"Context","textWithKeyword":"And I follow \"Careers and promotions\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Careers and promotions\"","children":[{"start":10,"value":"Careers and promotions","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":79,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":80,"gherkinStepLine":81,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":83,"pickleLine":83,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":84,"gherkinStepLine":84,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":85,"gherkinStepLine":85,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":86,"gherkinStepLine":86,"keywordType":"Context","textWithKeyword":"And I follow \"Manage session fields\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Manage session fields\"","children":[{"start":10,"value":"Manage session fields","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":87,"gherkinStepLine":87,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":88,"gherkinStepLine":88,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":91,"pickleLine":90,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":92,"gherkinStepLine":91,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":93,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":94,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I follow \"Resources sequencing\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Resources sequencing\"","children":[{"start":10,"value":"Resources sequencing","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":95,"gherkinStepLine":94,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":96,"gherkinStepLine":95,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":99,"pickleLine":97,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":100,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":101,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":102,"gherkinStepLine":100,"keywordType":"Context","textWithKeyword":"And I follow \"Export all results from an exercise\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Export all results from an exercise\"","children":[{"start":10,"value":"Export all results from an exercise","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":103,"gherkinStepLine":101,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":104,"gherkinStepLine":102,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end