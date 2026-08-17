// Generated from: features/adminPlatformBlock.feature
import { test } from "playwright-bdd";

test.describe('Admin Platform management block', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Open Configuration settings', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Configuration settings"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Languages', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Languages"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Plugins', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Plugins"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Regions', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Regions"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Portal news', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Portal news"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Global agenda', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Global agenda"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Pages', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Pages"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Setting the registration page', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Setting the registration page"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Global statistics', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Global statistics"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Reports catalog', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Reports catalog"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Teachers time report', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Teachers time report"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Extra fields', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Extra fields"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Multi URLs', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Multi URLs"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Mail templates', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Mail templates"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open External tools (LTI)', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "External tools (LTI)"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open Contact form categories', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Contact form categories"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open System templates', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "System templates"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Open a course report without course context', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/admin/report.php?id=course_learners_tracking"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Report on learners"', null, { page }); 
    await And('I should see "Course"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Periodic export is not listed in the reports catalog', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/admin/reports_catalog.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "Periodic export"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Course reporting canonical URL uses the course selector', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/admin/report.php?id=course_activity_statistics"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Course activity statistics"', null, { page }); 
    await And('I should see "Course"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Exercises global report keeps its own modern course selector', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/admin/report.php?id=course_exercise_global_report"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Exercises global report"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminPlatformBlock.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":27,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I follow \"Configuration settings\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Configuration settings\"","children":[{"start":10,"value":"Configuration settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":32,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":19,"pickleLine":34,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I follow \"Languages\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Languages\"","children":[{"start":10,"value":"Languages","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":27,"pickleLine":41,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And I follow \"Plugins\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Plugins\"","children":[{"start":10,"value":"Plugins","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":35,"pickleLine":48,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I follow \"Regions\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Regions\"","children":[{"start":10,"value":"Regions","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":39,"gherkinStepLine":52,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":53,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":43,"pickleLine":55,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":56,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":57,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":58,"keywordType":"Context","textWithKeyword":"And I follow \"Portal news\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Portal news\"","children":[{"start":10,"value":"Portal news","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":59,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":48,"gherkinStepLine":60,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":51,"pickleLine":62,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And I follow \"Global agenda\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Global agenda\"","children":[{"start":10,"value":"Global agenda","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":55,"gherkinStepLine":66,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":56,"gherkinStepLine":67,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":59,"pickleLine":69,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":60,"gherkinStepLine":70,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":61,"gherkinStepLine":71,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":62,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"And I follow \"Pages\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Pages\"","children":[{"start":10,"value":"Pages","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":63,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":64,"gherkinStepLine":74,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":67,"pickleLine":76,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":68,"gherkinStepLine":77,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":69,"gherkinStepLine":78,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":70,"gherkinStepLine":79,"keywordType":"Context","textWithKeyword":"And I follow \"Setting the registration page\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Setting the registration page\"","children":[{"start":10,"value":"Setting the registration page","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":71,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":72,"gherkinStepLine":81,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":75,"pickleLine":83,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":76,"gherkinStepLine":84,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":77,"gherkinStepLine":85,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":78,"gherkinStepLine":86,"keywordType":"Context","textWithKeyword":"And I follow \"Global statistics\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Global statistics\"","children":[{"start":10,"value":"Global statistics","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":79,"gherkinStepLine":87,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":80,"gherkinStepLine":88,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":83,"pickleLine":90,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":84,"gherkinStepLine":91,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":85,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":86,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I follow \"Reports catalog\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Reports catalog\"","children":[{"start":10,"value":"Reports catalog","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":87,"gherkinStepLine":94,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":88,"gherkinStepLine":95,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":91,"pickleLine":97,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":92,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":93,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":94,"gherkinStepLine":100,"keywordType":"Context","textWithKeyword":"And I follow \"Teachers time report\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Teachers time report\"","children":[{"start":10,"value":"Teachers time report","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":95,"gherkinStepLine":101,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":96,"gherkinStepLine":102,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":99,"pickleLine":104,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":100,"gherkinStepLine":105,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":101,"gherkinStepLine":106,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":102,"gherkinStepLine":107,"keywordType":"Context","textWithKeyword":"And I follow \"Extra fields\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Extra fields\"","children":[{"start":10,"value":"Extra fields","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":103,"gherkinStepLine":108,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":104,"gherkinStepLine":109,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":107,"pickleLine":111,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":108,"gherkinStepLine":115,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":109,"gherkinStepLine":116,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":110,"gherkinStepLine":117,"keywordType":"Context","textWithKeyword":"And I follow \"Multi URLs\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Multi URLs\"","children":[{"start":10,"value":"Multi URLs","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":111,"gherkinStepLine":118,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":112,"gherkinStepLine":119,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":115,"pickleLine":121,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":116,"gherkinStepLine":122,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":117,"gherkinStepLine":123,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":118,"gherkinStepLine":124,"keywordType":"Context","textWithKeyword":"And I follow \"Mail templates\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Mail templates\"","children":[{"start":10,"value":"Mail templates","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":119,"gherkinStepLine":125,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":120,"gherkinStepLine":126,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":123,"pickleLine":128,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":124,"gherkinStepLine":129,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":125,"gherkinStepLine":130,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":126,"gherkinStepLine":131,"keywordType":"Context","textWithKeyword":"And I follow \"External tools (LTI)\"","stepMatchArguments":[{"group":{"start":9,"value":"\"External tools (LTI)\"","children":[{"start":10,"value":"External tools (LTI)","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":127,"gherkinStepLine":132,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":128,"gherkinStepLine":133,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":131,"pickleLine":135,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":132,"gherkinStepLine":136,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":133,"gherkinStepLine":137,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":134,"gherkinStepLine":138,"keywordType":"Context","textWithKeyword":"And I follow \"Contact form categories\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Contact form categories\"","children":[{"start":10,"value":"Contact form categories","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":135,"gherkinStepLine":139,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":136,"gherkinStepLine":140,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":139,"pickleLine":142,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":140,"gherkinStepLine":143,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":141,"gherkinStepLine":144,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":142,"gherkinStepLine":145,"keywordType":"Context","textWithKeyword":"And I follow \"System templates\"","stepMatchArguments":[{"group":{"start":9,"value":"\"System templates\"","children":[{"start":10,"value":"System templates","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":143,"gherkinStepLine":146,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":144,"gherkinStepLine":147,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":147,"pickleLine":149,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":148,"gherkinStepLine":150,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/report.php?id=course_learners_tracking\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/report.php?id=course_learners_tracking\"","children":[{"start":9,"value":"/main/admin/report.php?id=course_learners_tracking","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":149,"gherkinStepLine":151,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":150,"gherkinStepLine":152,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Report on learners\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Report on learners\"","children":[{"start":14,"value":"Report on learners","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":151,"gherkinStepLine":153,"keywordType":"Outcome","textWithKeyword":"And I should see \"Course\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course\"","children":[{"start":14,"value":"Course","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":152,"gherkinStepLine":154,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":155,"pickleLine":156,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":156,"gherkinStepLine":157,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/reports_catalog.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/reports_catalog.php\"","children":[{"start":9,"value":"/main/admin/reports_catalog.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":157,"gherkinStepLine":158,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":158,"gherkinStepLine":159,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Periodic export\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Periodic export\"","children":[{"start":18,"value":"Periodic export","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":159,"gherkinStepLine":160,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":162,"pickleLine":162,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":163,"gherkinStepLine":163,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/report.php?id=course_activity_statistics\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/report.php?id=course_activity_statistics\"","children":[{"start":9,"value":"/main/admin/report.php?id=course_activity_statistics","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":164,"gherkinStepLine":164,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":165,"gherkinStepLine":165,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Course activity statistics\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course activity statistics\"","children":[{"start":14,"value":"Course activity statistics","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":166,"gherkinStepLine":166,"keywordType":"Outcome","textWithKeyword":"And I should see \"Course\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course\"","children":[{"start":14,"value":"Course","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":167,"gherkinStepLine":167,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":170,"pickleLine":169,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":171,"gherkinStepLine":170,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/report.php?id=course_exercise_global_report\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/report.php?id=course_exercise_global_report\"","children":[{"start":9,"value":"/main/admin/report.php?id=course_exercise_global_report","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":172,"gherkinStepLine":171,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":173,"gherkinStepLine":172,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Exercises global report\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Exercises global report\"","children":[{"start":14,"value":"Exercises global report","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":174,"gherkinStepLine":173,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end