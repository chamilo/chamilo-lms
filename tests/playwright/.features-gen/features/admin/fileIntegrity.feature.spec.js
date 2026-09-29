// Generated from: features/admin/fileIntegrity.feature
import { test } from "playwright-bdd";

test.describe('File integrity monitoring', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Global administrator sees the report and the admin actions', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin/security/file-integrity"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should see "File integrity"', null, { page }); 
    await And('I should see "Run a scan now"', null, { page }); 
  });

  test('Global administrator can run a scan on demand', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin/security/file-integrity"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I press "Run a scan now"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should see "Last scan"', null, { page }); 
  });

  test('Pausing alerting is refused with a wrong password', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/admin/security/file-integrity"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in "file-integrity-pause-password" with "not-the-right-password"', null, { page }); 
    await And('I press "Pause for 1 hour"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "Alerting is currently paused for maintenance."', null, { page }); 
  });

  test('Non-administrators cannot access the file integrity page', async ({ Given, Then, And, page }) => { 
    await Given('I am a student', null, { page }); 
    await And('I am on "/admin/security/file-integrity"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('the response status code should be 200'); 
    await And('I should not see "Run a scan now"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/admin/fileIntegrity.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":34,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/security/file-integrity\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/security/file-integrity\"","children":[{"start":9,"value":"/admin/security/file-integrity","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"And I should see \"File integrity\"","stepMatchArguments":[{"group":{"start":13,"value":"\"File integrity\"","children":[{"start":14,"value":"File integrity","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":54,"keywordType":"Outcome","textWithKeyword":"And I should see \"Run a scan now\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Run a scan now\"","children":[{"start":14,"value":"Run a scan now","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":19,"pickleLine":56,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":57,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/security/file-integrity\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/security/file-integrity\"","children":[{"start":9,"value":"/admin/security/file-integrity","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":58,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":59,"keywordType":"Context","textWithKeyword":"And I press \"Run a scan now\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Run a scan now\"","children":[{"start":9,"value":"Run a scan now","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":61,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":62,"keywordType":"Outcome","textWithKeyword":"And I should see \"Last scan\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Last scan\"","children":[{"start":14,"value":"Last scan","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":28,"pickleLine":64,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/security/file-integrity\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/security/file-integrity\"","children":[{"start":9,"value":"/admin/security/file-integrity","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":66,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"And I fill in \"file-integrity-pause-password\" with \"not-the-right-password\"","stepMatchArguments":[{"group":{"start":10,"value":"\"file-integrity-pause-password\"","children":[{"start":11,"value":"file-integrity-pause-password","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":47,"value":"\"not-the-right-password\"","children":[{"start":48,"value":"not-the-right-password","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I press \"Pause for 1 hour\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Pause for 1 hour\"","children":[{"start":9,"value":"Pause for 1 hour","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":34,"gherkinStepLine":70,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Alerting is currently paused for maintenance.\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Alerting is currently paused for maintenance.\"","children":[{"start":18,"value":"Alerting is currently paused for maintenance.","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":37,"pickleLine":72,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":74,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/security/file-integrity\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/security/file-integrity\"","children":[{"start":9,"value":"/admin/security/file-integrity","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":75,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":41,"gherkinStepLine":76,"keywordType":"Outcome","textWithKeyword":"Then the response status code should be 200","stepMatchArguments":[{"group":{"start":35,"value":"200"},"parameterTypeName":"int"}]},{"pwStepLine":42,"gherkinStepLine":77,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Run a scan now\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Run a scan now\"","children":[{"start":18,"value":"Run a scan now","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end