// Generated from: features/accessCompanyReports.feature
import { test } from "playwright-bdd";

test.describe('Access to portal reports as admin', () => {

  test('See the company reports link on the admin page', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/index.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Reports"', null, { page }); 
  });

  test('Access the company report', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/my_space/company_reports.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "not authorized"', null, { page }); 
  });

  test('Access the resumed version of the company report', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/my_space/company_reports_resumed.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "not authorized"', null, { page }); 
  });

  test('See the teacher time report', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/teacher_time_report.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Teachers time report"', null, { page }); 
  });

  test('Access the teacher time report without authorization error', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/teacher_time_report.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "not authorized"', null, { page }); 
  });

  test('See the teacher time by session report', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/teachers_time_by_session_report.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Teachers time report by session"', null, { page }); 
  });

  test('Access the teacher time by session report without authorization error', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/teachers_time_by_session_report.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "not authorized"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/accessCompanyReports.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":27,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/index.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/index.php\"","children":[{"start":9,"value":"/main/admin/index.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Reports\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Reports\"","children":[{"start":14,"value":"Reports","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":13,"pickleLine":33,"tags":[],"steps":[{"pwStepLine":14,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I am on \"/main/my_space/company_reports.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/my_space/company_reports.php\"","children":[{"start":9,"value":"/main/my_space/company_reports.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":17,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"not authorized\"","stepMatchArguments":[{"group":{"start":17,"value":"\"not authorized\"","children":[{"start":18,"value":"not authorized","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":20,"pickleLine":39,"tags":[],"steps":[{"pwStepLine":21,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"And I am on \"/main/my_space/company_reports_resumed.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/my_space/company_reports_resumed.php\"","children":[{"start":9,"value":"/main/my_space/company_reports_resumed.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":43,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"not authorized\"","stepMatchArguments":[{"group":{"start":17,"value":"\"not authorized\"","children":[{"start":18,"value":"not authorized","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":27,"pickleLine":45,"tags":[],"steps":[{"pwStepLine":28,"gherkinStepLine":46,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":47,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/teacher_time_report.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/teacher_time_report.php\"","children":[{"start":9,"value":"/main/admin/teacher_time_report.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":49,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Teachers time report\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Teachers time report\"","children":[{"start":14,"value":"Teachers time report","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":34,"pickleLine":51,"tags":[],"steps":[{"pwStepLine":35,"gherkinStepLine":52,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":53,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/teacher_time_report.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/teacher_time_report.php\"","children":[{"start":9,"value":"/main/admin/teacher_time_report.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":54,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":55,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"not authorized\"","stepMatchArguments":[{"group":{"start":17,"value":"\"not authorized\"","children":[{"start":18,"value":"not authorized","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":41,"pickleLine":57,"tags":[],"steps":[{"pwStepLine":42,"gherkinStepLine":58,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":59,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/teachers_time_by_session_report.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/teachers_time_by_session_report.php\"","children":[{"start":9,"value":"/main/admin/teachers_time_by_session_report.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":45,"gherkinStepLine":61,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Teachers time report by session\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Teachers time report by session\"","children":[{"start":14,"value":"Teachers time report by session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":48,"pickleLine":63,"tags":[],"steps":[{"pwStepLine":49,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":50,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/teachers_time_by_session_report.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/teachers_time_by_session_report.php\"","children":[{"start":9,"value":"/main/admin/teachers_time_by_session_report.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":51,"gherkinStepLine":66,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":67,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"not authorized\"","stepMatchArguments":[{"group":{"start":17,"value":"\"not authorized\"","children":[{"start":18,"value":"not authorized","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end