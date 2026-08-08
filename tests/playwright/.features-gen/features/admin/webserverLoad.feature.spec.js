// Generated from: features/admin/webserverLoad.feature
import { test } from "playwright-bdd";

test.describe('Web server load metrics on system status', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Administrator sees the web server load panel', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin/system-status?section=webserver"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should see "Web server load"', null, { page }); 
  });

  test('Web server load panel is collapsed by default', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin/system-status?section=webserver"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Web server load"', null, { page }); 
    await And('I should not see "Auto-refresh every 5 seconds"', null, { page }); 
  });

  test('Expanding the panel shows status module guidance', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/admin/system-status?section=webserver"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I click the "#webserver-load-toggle" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Auto-refresh every 5 seconds"', null, { page }); 
    await And('I should see "Requires a local web server status module"', null, { page }); 
    await And('I should see "Paths scanned"', null, { page }); 
  });

  test('Non-administrators cannot access the web server load page', async ({ Given, Then, And, page }) => { 
    await Given('I am a student', null, { page }); 
    await And('I am on "/admin/system-status?section=webserver"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('the response status code should be 200'); 
    await And('I should not see "Web server load"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/admin/webserverLoad.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":21,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/system-status?section=webserver\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=webserver\"","children":[{"start":9,"value":"/admin/system-status?section=webserver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":24,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":25,"keywordType":"Outcome","textWithKeyword":"And I should see \"Web server load\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Web server load\"","children":[{"start":14,"value":"Web server load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":18,"pickleLine":27,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/system-status?section=webserver\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=webserver\"","children":[{"start":9,"value":"/admin/system-status?section=webserver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":30,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Web server load\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Web server load\"","children":[{"start":14,"value":"Web server load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":22,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Auto-refresh every 5 seconds\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Auto-refresh every 5 seconds\"","children":[{"start":18,"value":"Auto-refresh every 5 seconds","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":25,"pickleLine":33,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/system-status?section=webserver\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=webserver\"","children":[{"start":9,"value":"/admin/system-status?section=webserver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":36,"keywordType":"Action","textWithKeyword":"When I click the \"#webserver-load-toggle\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"#webserver-load-toggle\"","children":[{"start":13,"value":"#webserver-load-toggle","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":37,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Auto-refresh every 5 seconds\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Auto-refresh every 5 seconds\"","children":[{"start":14,"value":"Auto-refresh every 5 seconds","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"And I should see \"Requires a local web server status module\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Requires a local web server status module\"","children":[{"start":14,"value":"Requires a local web server status module","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":40,"keywordType":"Outcome","textWithKeyword":"And I should see \"Paths scanned\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Paths scanned\"","children":[{"start":14,"value":"Paths scanned","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":35,"pickleLine":42,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/system-status?section=webserver\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=webserver\"","children":[{"start":9,"value":"/admin/system-status?section=webserver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"Then the response status code should be 200","stepMatchArguments":[{"group":{"start":35,"value":"200"},"parameterTypeName":"int"}]},{"pwStepLine":40,"gherkinStepLine":47,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Web server load\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Web server load\"","children":[{"start":18,"value":"Web server load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end