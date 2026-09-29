// Generated from: features/admin/webserverLoad.feature
import { test } from "playwright-bdd";

test.describe('Web server load metrics on system status', () => {

  test('Administrator sees the web server load panel', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/admin/system-status?section=webserver"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should see "Web server load"', null, { page }); 
  });

  test('Web server load panel is collapsed by default', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/admin/system-status?section=webserver"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Web server load"', null, { page }); 
    await And('I should not see "Auto-refresh every 5 seconds"', null, { page }); 
  });

  test('Expanding the panel shows status module guidance', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/admin/system-status?section=webserver"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I click the "#webserver-load-toggle" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Auto-refresh every 5 seconds"', null, { page }); 
    await And('I should see "Requires a local web server status module"', null, { page }); 
    await And('I should see "Paths scanned"', null, { page }); 
  });

  test('Non-administrators cannot access the web server load page', async ({ Given, When, Then, And, page }) => { 
    await Given('I am not logged', null, { page }); 
    await And('I am a student', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I am on "/admin/system-status?section=webserver"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('the page path should not start with "/admin/system-status"', null, { page }); 
    await And('I should not see the "#webserver-load-toggle" element', null, { page }); 
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
  {"pwTestLine":6,"pickleLine":23,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":9,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/system-status?section=webserver\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=webserver\"","children":[{"start":9,"value":"/admin/system-status?section=webserver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":10,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":29,"keywordType":"Outcome","textWithKeyword":"And I should see \"Web server load\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Web server load\"","children":[{"start":14,"value":"Web server load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":15,"pickleLine":31,"tags":[],"steps":[{"pwStepLine":16,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":17,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/system-status?section=webserver\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=webserver\"","children":[{"start":9,"value":"/admin/system-status?section=webserver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":36,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Web server load\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Web server load\"","children":[{"start":14,"value":"Web server load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Auto-refresh every 5 seconds\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Auto-refresh every 5 seconds\"","children":[{"start":18,"value":"Auto-refresh every 5 seconds","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":24,"pickleLine":39,"tags":[],"steps":[{"pwStepLine":25,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":27,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/system-status?section=webserver\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=webserver\"","children":[{"start":9,"value":"/admin/system-status?section=webserver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":28,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":44,"keywordType":"Action","textWithKeyword":"When I click the \"#webserver-load-toggle\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"#webserver-load-toggle\"","children":[{"start":13,"value":"#webserver-load-toggle","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":45,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Auto-refresh every 5 seconds\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Auto-refresh every 5 seconds\"","children":[{"start":14,"value":"Auto-refresh every 5 seconds","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":47,"keywordType":"Outcome","textWithKeyword":"And I should see \"Requires a local web server status module\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Requires a local web server status module\"","children":[{"start":14,"value":"Requires a local web server status module","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":48,"keywordType":"Outcome","textWithKeyword":"And I should see \"Paths scanned\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Paths scanned\"","children":[{"start":14,"value":"Paths scanned","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":36,"pickleLine":50,"tags":[],"steps":[{"pwStepLine":37,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"Given I am not logged","stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":52,"keywordType":"Context","textWithKeyword":"And I am a student","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":53,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":54,"keywordType":"Action","textWithKeyword":"When I am on \"/admin/system-status?section=webserver\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/system-status?section=webserver\"","children":[{"start":9,"value":"/admin/system-status?section=webserver","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":55,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":42,"gherkinStepLine":56,"keywordType":"Outcome","textWithKeyword":"Then the page path should not start with \"/admin/system-status\"","stepMatchArguments":[{"group":{"start":36,"value":"\"/admin/system-status\"","children":[{"start":37,"value":"/admin/system-status","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":43,"gherkinStepLine":57,"keywordType":"Outcome","textWithKeyword":"And I should not see the \"#webserver-load-toggle\" element","stepMatchArguments":[{"group":{"start":21,"value":"\"#webserver-load-toggle\"","children":[{"start":22,"value":"#webserver-load-toggle","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":58,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Web server load\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Web server load\"","children":[{"start":18,"value":"Web server load","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end