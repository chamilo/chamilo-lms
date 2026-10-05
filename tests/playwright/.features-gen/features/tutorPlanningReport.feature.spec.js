// Generated from: features/tutorPlanningReport.feature
import { test } from "playwright-bdd";

test.describe('General tutor planning report', () => {

  test('Create the session shown in this feature', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/session/session_add.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Tutor planning session"}]}]}}, { page }); 
    await And('I select "jmontoya" from the ajax select "coach_username"', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Add courses to this session"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('See the session in its tutor\'s weekly planning row', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/reporting/admin/tutor-planning"', null, { page }); 
    await And('I wait up to 30 seconds for the element "table th:has-text(\'Tutor\')" to appear', null, { page }); 
    await Then('I should see the "tr:has-text(\'Julio Montoya\') td.bg-success a:has-text(\'Tutor planning session\')" element', null, { page }); 
    await And('I should see the "table thead th:text-matches(\'^[0-9]{4}-[0-9]{2}$\')" element', null, { page }); 
  });

  test('HR managers do not get access to the planning table', async ({ Given, Then, And, page }) => { 
    await Given('I am an HR manager', null, { page }); 
    await And('I am on "/reporting/admin/tutor-planning"', null, { page }); 
    await And('I wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see "Tutor planning session"', null, { page }); 
  });

  test('Learners do not get access to the planning table', async ({ Given, Then, And, page }) => { 
    await Given('I am a student', null, { page }); 
    await And('I am on "/reporting/admin/tutor-planning"', null, { page }); 
    await And('I wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see "Tutor planning session"', null, { page }); 
  });

  test('Delete the session shown in this feature', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/session-list?keyword=Tutor+planning+session"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I click the ".mdi-delete" icon in the row for "Tutor planning session"', null, { page }); 
    await And('I press "Yes"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "Tutor planning session"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/tutorPlanningReport.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":18,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"And I am on \"/main/session/session_add.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/session/session_add.php\"","children":[{"start":9,"value":"/main/session/session_add.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":22,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":24,"keywordType":"Action","textWithKeyword":"And I select \"jmontoya\" from the ajax select \"coach_username\"","stepMatchArguments":[{"group":{"start":9,"value":"\"jmontoya\"","children":[{"start":10,"value":"jmontoya","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":41,"value":"\"coach_username\"","children":[{"start":42,"value":"coach_username","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":25,"keywordType":"Action","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":26,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":27,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Add courses to this session\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Add courses to this session\"","children":[{"start":14,"value":"Add courses to this session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":18,"pickleLine":30,"tags":[],"steps":[{"pwStepLine":19,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I am on \"/reporting/admin/tutor-planning\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/reporting/admin/tutor-planning\"","children":[{"start":9,"value":"/reporting/admin/tutor-planning","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"And I wait up to 30 seconds for the element \"table th:has-text('Tutor')\" to appear","stepMatchArguments":[{"group":{"start":13,"value":"30"},"parameterTypeName":"int"},{"group":{"start":41,"value":"table th:has-text('Tutor')"}}]},{"pwStepLine":22,"gherkinStepLine":34,"keywordType":"Outcome","textWithKeyword":"Then I should see the \"tr:has-text('Julio Montoya') td.bg-success a:has-text('Tutor planning session')\" element","stepMatchArguments":[{"group":{"start":17,"value":"\"tr:has-text('Julio Montoya') td.bg-success a:has-text('Tutor planning session')\"","children":[{"start":18,"value":"tr:has-text('Julio Montoya') td.bg-success a:has-text('Tutor planning session')","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":35,"keywordType":"Outcome","textWithKeyword":"And I should see the \"table thead th:text-matches('^[0-9]{4}-[0-9]{2}$')\" element","stepMatchArguments":[{"group":{"start":17,"value":"\"table thead th:text-matches('^[0-9]{4}-[0-9]{2}$')\"","children":[{"start":18,"value":"table thead th:text-matches('^[0-9]{4}-[0-9]{2}$')","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":26,"pickleLine":37,"tags":[],"steps":[{"pwStepLine":27,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"Given I am an HR manager","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":39,"keywordType":"Context","textWithKeyword":"And I am on \"/reporting/admin/tutor-planning\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/reporting/admin/tutor-planning\"","children":[{"start":9,"value":"/reporting/admin/tutor-planning","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"And I wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":41,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Tutor planning session\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Tutor planning session\"","children":[{"start":18,"value":"Tutor planning session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":33,"pickleLine":43,"tags":[],"steps":[{"pwStepLine":34,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"And I am on \"/reporting/admin/tutor-planning\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/reporting/admin/tutor-planning\"","children":[{"start":9,"value":"/reporting/admin/tutor-planning","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":36,"gherkinStepLine":46,"keywordType":"Context","textWithKeyword":"And I wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":47,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Tutor planning session\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Tutor planning session\"","children":[{"start":18,"value":"Tutor planning session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":40,"pickleLine":49,"tags":[],"steps":[{"pwStepLine":41,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":42,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/session-list?keyword=Tutor+planning+session\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/session-list?keyword=Tutor+planning+session\"","children":[{"start":9,"value":"/admin/session-list?keyword=Tutor+planning+session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":43,"gherkinStepLine":52,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":53,"keywordType":"Action","textWithKeyword":"When I click the \".mdi-delete\" icon in the row for \"Tutor planning session\"","stepMatchArguments":[{"group":{"start":12,"value":"\".mdi-delete\"","children":[{"start":13,"value":".mdi-delete","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":46,"value":"\"Tutor planning session\"","children":[{"start":47,"value":"Tutor planning session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":54,"keywordType":"Action","textWithKeyword":"And I press \"Yes\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Yes\"","children":[{"start":9,"value":"Yes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":46,"gherkinStepLine":55,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":47,"gherkinStepLine":56,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Tutor planning session\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Tutor planning session\"","children":[{"start":18,"value":"Tutor planning session","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":48,"gherkinStepLine":57,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end