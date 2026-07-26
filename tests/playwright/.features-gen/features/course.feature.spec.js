// Generated from: features/course.feature
import { test } from "playwright-bdd";

test.describe('Course tools basic testing', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
  });
  
  test('See the courses list', { tag: ['@common', '@tools'] }, async ({ Given, And, page }) => { 
    await Given('I am on "/admin/course-list"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I should not see "not authorized"', null, { page }); 
  });

  test('See the course creation link on the admin page', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/admin/index.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Add course"', null, { page }); 
  });

  test('Create a course before testing', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/admin/course_add.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in "title" with "TEMP"', null, { page }); 
    await When('I press "submit"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should see "TEMP"', null, { page }); 
  });

  test('Make sure the course exists', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the course description tool is available', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I follow "Course description"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the documents tool is available', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I follow "Documents"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the learning path tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/lp/lp_controller.php?action=list&cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the links tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/link/link.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the tests tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/exercise/exercise.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the announcements tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/announcements/announcements.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the assessments tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/gradebook/index.php?cid=1"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the glossary tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/glossary/index.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the attendances tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the course progress tool is available', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I follow "Course progress"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the agenda tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/calendar/agenda_js.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the forums tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/forum/index.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the dropbox tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/dropbox/index.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the users tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/user/user.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the groups tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/group/group.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the chat tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/resources/chat/?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the assignments tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assignments"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the surveys tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/survey/index.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the wiki tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/wiki/index.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the notebook tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/notebook/index.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the projects tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I click the "button.p-button-icon-only" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I follow "Blog"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the reporting tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/tracking/courseLog.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the settings tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/course_info/infocours.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the backup tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/course_info/maintenance.php?cid=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Enter to public password-protected course', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I click the "button.p-button-icon-only" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I follow "Course settings"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I click the "a.collapse_course_access" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"course_registration_password"},{"value":"abc"}]}]}}, { page }); 
    await And('I press "submit"', null, { page }); 
    await Then('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Create a private course before testing', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/admin/course_add.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "not authorized"', null, { page }); 
    await When('I fill in "title" with "TEMP_PRIVATE"', null, { page }); 
    await Then('I check the "Private access (access authorized to group members only)" radio button', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await Then('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/course.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":14,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":15,"keywordType":"Context","textWithKeyword":"Given I am on \"/admin/course-list\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/course-list\"","children":[{"start":9,"value":"/admin/course-list","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I should not see \"not authorized\"","stepMatchArguments":[{"group":{"start":17,"value":"\"not authorized\"","children":[{"start":18,"value":"not authorized","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":16,"pickleLine":20,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":17,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/index.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/index.php\"","children":[{"start":9,"value":"/main/admin/index.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":24,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Add course\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Add course\"","children":[{"start":14,"value":"Add course","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":23,"pickleLine":27,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/course_add.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/course_add.php\"","children":[{"start":9,"value":"/main/admin/course_add.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":25,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I fill in \"title\" with \"TEMP\"","stepMatchArguments":[{"group":{"start":10,"value":"\"title\"","children":[{"start":11,"value":"title","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":23,"value":"\"TEMP\"","children":[{"start":24,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":31,"keywordType":"Action","textWithKeyword":"When I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":28,"gherkinStepLine":32,"keywordType":"Action","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":33,"keywordType":"Outcome","textWithKeyword":"Then I should see \"TEMP\"","stepMatchArguments":[{"group":{"start":13,"value":"\"TEMP\"","children":[{"start":14,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":32,"pickleLine":36,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":38,"pickleLine":42,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":41,"gherkinStepLine":45,"keywordType":"Action","textWithKeyword":"When I follow \"Course description\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Course description\"","children":[{"start":10,"value":"Course description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":42,"gherkinStepLine":46,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":47,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":46,"pickleLine":50,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":47,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":48,"gherkinStepLine":52,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":49,"gherkinStepLine":53,"keywordType":"Action","textWithKeyword":"When I follow \"Documents\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Documents\"","children":[{"start":10,"value":"Documents","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":50,"gherkinStepLine":54,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":51,"gherkinStepLine":55,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":54,"pickleLine":58,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":55,"gherkinStepLine":59,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":56,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":57,"gherkinStepLine":61,"keywordType":"Context","textWithKeyword":"And I am on \"/main/lp/lp_controller.php?action=list&cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/lp/lp_controller.php?action=list&cid=1\"","children":[{"start":9,"value":"/main/lp/lp_controller.php?action=list&cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":58,"gherkinStepLine":62,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":59,"gherkinStepLine":63,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":62,"pickleLine":66,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":63,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":64,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":65,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I am on \"/main/link/link.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/link/link.php?cid=1\"","children":[{"start":9,"value":"/main/link/link.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":66,"gherkinStepLine":70,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":67,"gherkinStepLine":71,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":70,"pickleLine":74,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":71,"gherkinStepLine":75,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":72,"gherkinStepLine":76,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":73,"gherkinStepLine":77,"keywordType":"Context","textWithKeyword":"And I am on \"/main/exercise/exercise.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/exercise/exercise.php?cid=1\"","children":[{"start":9,"value":"/main/exercise/exercise.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":74,"gherkinStepLine":78,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":75,"gherkinStepLine":79,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":78,"pickleLine":82,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":79,"gherkinStepLine":83,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":80,"gherkinStepLine":84,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":81,"gherkinStepLine":85,"keywordType":"Context","textWithKeyword":"And I am on \"/main/announcements/announcements.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/announcements/announcements.php?cid=1\"","children":[{"start":9,"value":"/main/announcements/announcements.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":82,"gherkinStepLine":86,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":83,"gherkinStepLine":87,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":86,"pickleLine":90,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":87,"gherkinStepLine":91,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":88,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":89,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I am on \"/main/gradebook/index.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/gradebook/index.php?cid=1\"","children":[{"start":9,"value":"/main/gradebook/index.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":90,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":91,"gherkinStepLine":100,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":94,"pickleLine":103,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":95,"gherkinStepLine":104,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":96,"gherkinStepLine":105,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":97,"gherkinStepLine":106,"keywordType":"Context","textWithKeyword":"And I am on \"/main/glossary/index.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/glossary/index.php?cid=1\"","children":[{"start":9,"value":"/main/glossary/index.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":98,"gherkinStepLine":107,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":99,"gherkinStepLine":108,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":102,"pickleLine":111,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":103,"gherkinStepLine":112,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":104,"gherkinStepLine":113,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":105,"gherkinStepLine":114,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":108,"pickleLine":117,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":109,"gherkinStepLine":118,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":110,"gherkinStepLine":119,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":111,"gherkinStepLine":120,"keywordType":"Action","textWithKeyword":"When I follow \"Course progress\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Course progress\"","children":[{"start":10,"value":"Course progress","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":112,"gherkinStepLine":121,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":113,"gherkinStepLine":122,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":116,"pickleLine":125,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":117,"gherkinStepLine":126,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":118,"gherkinStepLine":127,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":119,"gherkinStepLine":128,"keywordType":"Context","textWithKeyword":"And I am on \"/main/calendar/agenda_js.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/calendar/agenda_js.php?cid=1\"","children":[{"start":9,"value":"/main/calendar/agenda_js.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":120,"gherkinStepLine":129,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":121,"gherkinStepLine":130,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":124,"pickleLine":133,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":125,"gherkinStepLine":134,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":126,"gherkinStepLine":135,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":127,"gherkinStepLine":136,"keywordType":"Context","textWithKeyword":"And I am on \"/main/forum/index.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/forum/index.php?cid=1\"","children":[{"start":9,"value":"/main/forum/index.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":128,"gherkinStepLine":137,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":129,"gherkinStepLine":138,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":132,"pickleLine":141,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":133,"gherkinStepLine":142,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":134,"gherkinStepLine":143,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":135,"gherkinStepLine":144,"keywordType":"Context","textWithKeyword":"And I am on \"/main/dropbox/index.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/dropbox/index.php?cid=1\"","children":[{"start":9,"value":"/main/dropbox/index.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":136,"gherkinStepLine":145,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":137,"gherkinStepLine":146,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":140,"pickleLine":149,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":141,"gherkinStepLine":150,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":142,"gherkinStepLine":151,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":143,"gherkinStepLine":152,"keywordType":"Context","textWithKeyword":"And I am on \"/main/user/user.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/user.php?cid=1\"","children":[{"start":9,"value":"/main/user/user.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":144,"gherkinStepLine":153,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":145,"gherkinStepLine":154,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":148,"pickleLine":157,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":149,"gherkinStepLine":158,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":150,"gherkinStepLine":159,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":151,"gherkinStepLine":160,"keywordType":"Context","textWithKeyword":"And I am on \"/main/group/group.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/group/group.php?cid=1\"","children":[{"start":9,"value":"/main/group/group.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":152,"gherkinStepLine":161,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":153,"gherkinStepLine":162,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":156,"pickleLine":167,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":157,"gherkinStepLine":168,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":158,"gherkinStepLine":169,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":159,"gherkinStepLine":170,"keywordType":"Context","textWithKeyword":"And I am on \"/resources/chat/?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/resources/chat/?cid=1\"","children":[{"start":9,"value":"/resources/chat/?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":160,"gherkinStepLine":171,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":161,"gherkinStepLine":172,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":164,"pickleLine":175,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":165,"gherkinStepLine":176,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":166,"gherkinStepLine":177,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":167,"gherkinStepLine":178,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assignments\"","children":[{"start":10,"value":"Assignments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":168,"gherkinStepLine":179,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":169,"gherkinStepLine":180,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":172,"pickleLine":183,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":173,"gherkinStepLine":184,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":174,"gherkinStepLine":185,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":175,"gherkinStepLine":186,"keywordType":"Context","textWithKeyword":"And I am on \"/main/survey/index.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/survey/index.php?cid=1\"","children":[{"start":9,"value":"/main/survey/index.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":176,"gherkinStepLine":187,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":177,"gherkinStepLine":188,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":180,"pickleLine":191,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":181,"gherkinStepLine":192,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":182,"gherkinStepLine":193,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":183,"gherkinStepLine":194,"keywordType":"Context","textWithKeyword":"And I am on \"/main/wiki/index.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/wiki/index.php?cid=1\"","children":[{"start":9,"value":"/main/wiki/index.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":184,"gherkinStepLine":195,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":185,"gherkinStepLine":196,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":188,"pickleLine":199,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":189,"gherkinStepLine":200,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":190,"gherkinStepLine":201,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":191,"gherkinStepLine":202,"keywordType":"Context","textWithKeyword":"And I am on \"/main/notebook/index.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/notebook/index.php?cid=1\"","children":[{"start":9,"value":"/main/notebook/index.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":192,"gherkinStepLine":203,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":193,"gherkinStepLine":204,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":196,"pickleLine":207,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":197,"gherkinStepLine":208,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":198,"gherkinStepLine":209,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":199,"gherkinStepLine":210,"keywordType":"Context","textWithKeyword":"And I click the \"button.p-button-icon-only\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"button.p-button-icon-only\"","children":[{"start":13,"value":"button.p-button-icon-only","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":200,"gherkinStepLine":211,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":201,"gherkinStepLine":212,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Blog\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Blog\"","children":[{"start":10,"value":"Blog","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":202,"gherkinStepLine":213,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":203,"gherkinStepLine":214,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":206,"pickleLine":217,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":207,"gherkinStepLine":218,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":208,"gherkinStepLine":219,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":209,"gherkinStepLine":220,"keywordType":"Context","textWithKeyword":"And I am on \"/main/tracking/courseLog.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/tracking/courseLog.php?cid=1\"","children":[{"start":9,"value":"/main/tracking/courseLog.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":210,"gherkinStepLine":221,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":211,"gherkinStepLine":222,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":214,"pickleLine":225,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":215,"gherkinStepLine":226,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":216,"gherkinStepLine":227,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":217,"gherkinStepLine":228,"keywordType":"Context","textWithKeyword":"And I am on \"/main/course_info/infocours.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/course_info/infocours.php?cid=1\"","children":[{"start":9,"value":"/main/course_info/infocours.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":218,"gherkinStepLine":229,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":219,"gherkinStepLine":230,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":222,"pickleLine":233,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":223,"gherkinStepLine":234,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":224,"gherkinStepLine":235,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":225,"gherkinStepLine":236,"keywordType":"Context","textWithKeyword":"And I am on \"/main/course_info/maintenance.php?cid=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/course_info/maintenance.php?cid=1\"","children":[{"start":9,"value":"/main/course_info/maintenance.php?cid=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":226,"gherkinStepLine":237,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":227,"gherkinStepLine":238,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":230,"pickleLine":240,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":231,"gherkinStepLine":241,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":232,"gherkinStepLine":242,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":233,"gherkinStepLine":243,"keywordType":"Context","textWithKeyword":"And I click the \"button.p-button-icon-only\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"button.p-button-icon-only\"","children":[{"start":13,"value":"button.p-button-icon-only","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":234,"gherkinStepLine":244,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":235,"gherkinStepLine":245,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Course settings\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Course settings\"","children":[{"start":10,"value":"Course settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":236,"gherkinStepLine":246,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":237,"gherkinStepLine":247,"keywordType":"Outcome","textWithKeyword":"And I click the \"a.collapse_course_access\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"a.collapse_course_access\"","children":[{"start":13,"value":"a.collapse_course_access","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":238,"gherkinStepLine":248,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":239,"gherkinStepLine":249,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":240,"gherkinStepLine":251,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":241,"gherkinStepLine":252,"keywordType":"Outcome","textWithKeyword":"Then I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":242,"gherkinStepLine":253,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":245,"pickleLine":255,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":246,"gherkinStepLine":256,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/course_add.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/course_add.php\"","children":[{"start":9,"value":"/main/admin/course_add.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":247,"gherkinStepLine":257,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":248,"gherkinStepLine":258,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"not authorized\"","stepMatchArguments":[{"group":{"start":17,"value":"\"not authorized\"","children":[{"start":18,"value":"not authorized","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":249,"gherkinStepLine":259,"keywordType":"Action","textWithKeyword":"When I fill in \"title\" with \"TEMP_PRIVATE\"","stepMatchArguments":[{"group":{"start":10,"value":"\"title\"","children":[{"start":11,"value":"title","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":23,"value":"\"TEMP_PRIVATE\"","children":[{"start":24,"value":"TEMP_PRIVATE","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":250,"gherkinStepLine":260,"keywordType":"Outcome","textWithKeyword":"Then I check the \"Private access (access authorized to group members only)\" radio button","stepMatchArguments":[{"group":{"start":12,"value":"\"Private access (access authorized to group members only)\"","children":[{"start":13,"value":"Private access (access authorized to group members only)","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":251,"gherkinStepLine":261,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":252,"gherkinStepLine":262,"keywordType":"Outcome","textWithKeyword":"Then wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":253,"gherkinStepLine":263,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end