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
    await And('I select "Language skills" from the ajax select "update_course_course_categories"', null, { page }); 
    await And('I select "English" from "course_language"', null, { page }); 
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
    await And('I am on "/main/lp/lp_controller.php?action=list&cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the links tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/link/link.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the tests tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/exercise/exercise.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the announcements tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/announcements/announcements.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the assessments tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/gradebook/index.php?cid=3"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the glossary tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/glossary/index.php?cid=3"', null, { page }); 
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
    await And('I am on "/main/calendar/agenda_js.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the forums tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/forum/index.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the dropbox tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/dropbox/index.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the users tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/user/user.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the groups tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/group/group.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the chat tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/resources/chat/?cid=3"', null, { page }); 
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
    await And('I am on "/main/survey/index.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the wiki tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/wiki/index.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the notebook tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/notebook/index.php?cid=3"', null, { page }); 
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
    await And('I am on "/main/tracking/courseLog.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the settings tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/course_info/infocours.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Make sure the backup tool is available', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/main/course_info/maintenance.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test.skip('Enter to public password-protected course', { tag: ['@common', '@tools', '@skip'] }, async ({ Given, When, Then, And }) => { 
    await Given('I am on "/main/admin/course_add.php"'); 
    await And('I wait for the page to be loaded'); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Password Protected"}]},{"cells":[{"value":"visual_code"},{"value":"PASSWORDPROTECTED"}]}]}}); 
    await And('I select "Language skills" from the ajax select "update_course_course_categories"'); 
    await And('I select "English" from "course_language"'); 
    await And('I check the "Public - access allowed for the whole world" radio button'); 
    await And('I press "submit"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "Password Protected"'); 
    await And('I resolve the numeric id of course "PASSWORDPROTECTED"'); 
    await Given('I am on the course settings page of course "PASSWORDPROTECTED"'); 
    await And('I wait for the page to be loaded'); 
    await And('I click the "a[data-target=\'#collapse_course_access\']" element'); 
    await And('I wait for the page to be loaded'); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"course_registration_password"},{"value":"123456"}]}]}}); 
    await And('I press "Save settings"'); 
    await Then('I wait for the page to be loaded'); 
    await Then('I should not see an error'); 
    await Given('I am not logged'); 
    await And('I am a student'); 
    await And('I am on the modern homepage of course "PASSWORDPROTECTED"'); 
    await Then('I should see "This course requires a password"'); 
    await When('I fill in "course_password" with "wrong-password"'); 
    await And('I press "Accept"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "The course password is incorrect"'); 
    await When('I fill in "course_password" with "123456"'); 
    await And('I press "Accept"'); 
    await And('I wait for the page to be loaded when ready'); 
    await Then('I should be on the modern homepage of course "PASSWORDPROTECTED"'); 
    await And('I should see "Password Protected"'); 
    await And('I should not see "The course password is incorrect"'); 
  });

  test('Create a private course before testing', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/admin/course_add.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "not authorized"', null, { page }); 
    await When('I fill in "title" with "TEMP_PRIVATE"', null, { page }); 
    await And('I select "Language skills" from the ajax select "update_course_course_categories"', null, { page }); 
    await And('I select "English" from "course_language"', null, { page }); 
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
  {"pwTestLine":23,"pickleLine":27,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/course_add.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/course_add.php\"","children":[{"start":9,"value":"/main/admin/course_add.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":25,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I fill in \"title\" with \"TEMP\"","stepMatchArguments":[{"group":{"start":10,"value":"\"title\"","children":[{"start":11,"value":"title","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":23,"value":"\"TEMP\"","children":[{"start":24,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I select \"Language skills\" from the ajax select \"update_course_course_categories\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Language skills\"","children":[{"start":10,"value":"Language skills","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":48,"value":"\"update_course_course_categories\"","children":[{"start":49,"value":"update_course_course_categories","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":28,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I select \"English\" from \"course_language\"","stepMatchArguments":[{"group":{"start":9,"value":"\"English\"","children":[{"start":10,"value":"English","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":24,"value":"\"course_language\"","children":[{"start":25,"value":"course_language","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":44,"keywordType":"Action","textWithKeyword":"When I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":45,"keywordType":"Action","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"Then I should see \"TEMP\"","stepMatchArguments":[{"group":{"start":13,"value":"\"TEMP\"","children":[{"start":14,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":34,"pickleLine":49,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":36,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":52,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":40,"pickleLine":55,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":41,"gherkinStepLine":56,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":42,"gherkinStepLine":57,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":58,"keywordType":"Action","textWithKeyword":"When I follow \"Course description\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Course description\"","children":[{"start":10,"value":"Course description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":59,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":45,"gherkinStepLine":60,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":48,"pickleLine":63,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":49,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":50,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":51,"gherkinStepLine":66,"keywordType":"Action","textWithKeyword":"When I follow \"Documents\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Documents\"","children":[{"start":10,"value":"Documents","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":52,"gherkinStepLine":67,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":53,"gherkinStepLine":68,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":56,"pickleLine":71,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":57,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":58,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":59,"gherkinStepLine":74,"keywordType":"Context","textWithKeyword":"And I am on \"/main/lp/lp_controller.php?action=list&cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/lp/lp_controller.php?action=list&cid=3\"","children":[{"start":9,"value":"/main/lp/lp_controller.php?action=list&cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":60,"gherkinStepLine":75,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":61,"gherkinStepLine":76,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":64,"pickleLine":79,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":65,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":66,"gherkinStepLine":81,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":67,"gherkinStepLine":82,"keywordType":"Context","textWithKeyword":"And I am on \"/main/link/link.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/link/link.php?cid=3\"","children":[{"start":9,"value":"/main/link/link.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":68,"gherkinStepLine":83,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":69,"gherkinStepLine":84,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":72,"pickleLine":87,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":73,"gherkinStepLine":88,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":74,"gherkinStepLine":89,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":75,"gherkinStepLine":90,"keywordType":"Context","textWithKeyword":"And I am on \"/main/exercise/exercise.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/exercise/exercise.php?cid=3\"","children":[{"start":9,"value":"/main/exercise/exercise.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":76,"gherkinStepLine":91,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":77,"gherkinStepLine":92,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":80,"pickleLine":95,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":81,"gherkinStepLine":96,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":82,"gherkinStepLine":97,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":83,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"And I am on \"/main/announcements/announcements.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/announcements/announcements.php?cid=3\"","children":[{"start":9,"value":"/main/announcements/announcements.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":84,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":85,"gherkinStepLine":100,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":88,"pickleLine":103,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":89,"gherkinStepLine":104,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":90,"gherkinStepLine":105,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":91,"gherkinStepLine":106,"keywordType":"Context","textWithKeyword":"And I am on \"/main/gradebook/index.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/gradebook/index.php?cid=3\"","children":[{"start":9,"value":"/main/gradebook/index.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":92,"gherkinStepLine":112,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":93,"gherkinStepLine":113,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":96,"pickleLine":116,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":97,"gherkinStepLine":117,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":98,"gherkinStepLine":118,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":99,"gherkinStepLine":119,"keywordType":"Context","textWithKeyword":"And I am on \"/main/glossary/index.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/glossary/index.php?cid=3\"","children":[{"start":9,"value":"/main/glossary/index.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":100,"gherkinStepLine":120,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":101,"gherkinStepLine":121,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":104,"pickleLine":124,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":105,"gherkinStepLine":125,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":106,"gherkinStepLine":126,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":107,"gherkinStepLine":127,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":110,"pickleLine":130,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":111,"gherkinStepLine":131,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":112,"gherkinStepLine":132,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":113,"gherkinStepLine":133,"keywordType":"Action","textWithKeyword":"When I follow \"Course progress\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Course progress\"","children":[{"start":10,"value":"Course progress","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":114,"gherkinStepLine":134,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":115,"gherkinStepLine":135,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":118,"pickleLine":138,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":119,"gherkinStepLine":139,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":120,"gherkinStepLine":140,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":121,"gherkinStepLine":141,"keywordType":"Context","textWithKeyword":"And I am on \"/main/calendar/agenda_js.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/calendar/agenda_js.php?cid=3\"","children":[{"start":9,"value":"/main/calendar/agenda_js.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":122,"gherkinStepLine":142,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":123,"gherkinStepLine":143,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":126,"pickleLine":146,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":127,"gherkinStepLine":147,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":128,"gherkinStepLine":148,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":129,"gherkinStepLine":149,"keywordType":"Context","textWithKeyword":"And I am on \"/main/forum/index.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/forum/index.php?cid=3\"","children":[{"start":9,"value":"/main/forum/index.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":130,"gherkinStepLine":150,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":131,"gherkinStepLine":151,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":134,"pickleLine":154,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":135,"gherkinStepLine":155,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":136,"gherkinStepLine":156,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":137,"gherkinStepLine":157,"keywordType":"Context","textWithKeyword":"And I am on \"/main/dropbox/index.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/dropbox/index.php?cid=3\"","children":[{"start":9,"value":"/main/dropbox/index.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":138,"gherkinStepLine":158,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":139,"gherkinStepLine":159,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":142,"pickleLine":162,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":143,"gherkinStepLine":163,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":144,"gherkinStepLine":164,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":145,"gherkinStepLine":165,"keywordType":"Context","textWithKeyword":"And I am on \"/main/user/user.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/user.php?cid=3\"","children":[{"start":9,"value":"/main/user/user.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":146,"gherkinStepLine":166,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":147,"gherkinStepLine":167,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":150,"pickleLine":170,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":151,"gherkinStepLine":171,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":152,"gherkinStepLine":172,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":153,"gherkinStepLine":173,"keywordType":"Context","textWithKeyword":"And I am on \"/main/group/group.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/group/group.php?cid=3\"","children":[{"start":9,"value":"/main/group/group.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":154,"gherkinStepLine":174,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":155,"gherkinStepLine":175,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":158,"pickleLine":180,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":159,"gherkinStepLine":181,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":160,"gherkinStepLine":182,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":161,"gherkinStepLine":183,"keywordType":"Context","textWithKeyword":"And I am on \"/resources/chat/?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/resources/chat/?cid=3\"","children":[{"start":9,"value":"/resources/chat/?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":162,"gherkinStepLine":184,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":163,"gherkinStepLine":185,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":166,"pickleLine":188,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":167,"gherkinStepLine":189,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":168,"gherkinStepLine":190,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":169,"gherkinStepLine":191,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assignments\"","children":[{"start":10,"value":"Assignments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":170,"gherkinStepLine":192,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":171,"gherkinStepLine":193,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":174,"pickleLine":196,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":175,"gherkinStepLine":197,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":176,"gherkinStepLine":198,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":177,"gherkinStepLine":199,"keywordType":"Context","textWithKeyword":"And I am on \"/main/survey/index.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/survey/index.php?cid=3\"","children":[{"start":9,"value":"/main/survey/index.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":178,"gherkinStepLine":200,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":179,"gherkinStepLine":201,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":182,"pickleLine":204,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":183,"gherkinStepLine":205,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":184,"gherkinStepLine":206,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":185,"gherkinStepLine":207,"keywordType":"Context","textWithKeyword":"And I am on \"/main/wiki/index.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/wiki/index.php?cid=3\"","children":[{"start":9,"value":"/main/wiki/index.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":186,"gherkinStepLine":208,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":187,"gherkinStepLine":209,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":190,"pickleLine":212,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":191,"gherkinStepLine":213,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":192,"gherkinStepLine":214,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":193,"gherkinStepLine":215,"keywordType":"Context","textWithKeyword":"And I am on \"/main/notebook/index.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/notebook/index.php?cid=3\"","children":[{"start":9,"value":"/main/notebook/index.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":194,"gherkinStepLine":216,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":195,"gherkinStepLine":217,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":198,"pickleLine":220,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":199,"gherkinStepLine":221,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":200,"gherkinStepLine":222,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":201,"gherkinStepLine":223,"keywordType":"Context","textWithKeyword":"And I click the \"button.p-button-icon-only\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"button.p-button-icon-only\"","children":[{"start":13,"value":"button.p-button-icon-only","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":202,"gherkinStepLine":224,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":203,"gherkinStepLine":225,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Blog\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Blog\"","children":[{"start":10,"value":"Blog","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":204,"gherkinStepLine":226,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":205,"gherkinStepLine":227,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":208,"pickleLine":230,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":209,"gherkinStepLine":231,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":210,"gherkinStepLine":232,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":211,"gherkinStepLine":233,"keywordType":"Context","textWithKeyword":"And I am on \"/main/tracking/courseLog.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/tracking/courseLog.php?cid=3\"","children":[{"start":9,"value":"/main/tracking/courseLog.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":212,"gherkinStepLine":234,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":213,"gherkinStepLine":235,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":216,"pickleLine":238,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":217,"gherkinStepLine":239,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":218,"gherkinStepLine":240,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":219,"gherkinStepLine":241,"keywordType":"Context","textWithKeyword":"And I am on \"/main/course_info/infocours.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/course_info/infocours.php?cid=3\"","children":[{"start":9,"value":"/main/course_info/infocours.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":220,"gherkinStepLine":242,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":221,"gherkinStepLine":243,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":224,"pickleLine":246,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":225,"gherkinStepLine":247,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":226,"gherkinStepLine":248,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":227,"gherkinStepLine":249,"keywordType":"Context","textWithKeyword":"And I am on \"/main/course_info/maintenance.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/course_info/maintenance.php?cid=3\"","children":[{"start":9,"value":"/main/course_info/maintenance.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":228,"gherkinStepLine":250,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":229,"gherkinStepLine":251,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":232,"pickleLine":283,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":233,"gherkinStepLine":284,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/course_add.php\""},{"pwStepLine":234,"gherkinStepLine":285,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":235,"gherkinStepLine":286,"keywordType":"Context","textWithKeyword":"And I fill in the following:"},{"pwStepLine":236,"gherkinStepLine":293,"keywordType":"Context","textWithKeyword":"And I select \"Language skills\" from the ajax select \"update_course_course_categories\""},{"pwStepLine":237,"gherkinStepLine":294,"keywordType":"Context","textWithKeyword":"And I select \"English\" from \"course_language\""},{"pwStepLine":238,"gherkinStepLine":295,"keywordType":"Context","textWithKeyword":"And I check the \"Public - access allowed for the whole world\" radio button"},{"pwStepLine":239,"gherkinStepLine":296,"keywordType":"Context","textWithKeyword":"And I press \"submit\""},{"pwStepLine":240,"gherkinStepLine":297,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":241,"gherkinStepLine":298,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Password Protected\""},{"pwStepLine":242,"gherkinStepLine":299,"keywordType":"Outcome","textWithKeyword":"And I resolve the numeric id of course \"PASSWORDPROTECTED\""},{"pwStepLine":243,"gherkinStepLine":301,"keywordType":"Context","textWithKeyword":"Given I am on the course settings page of course \"PASSWORDPROTECTED\""},{"pwStepLine":244,"gherkinStepLine":302,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":245,"gherkinStepLine":303,"keywordType":"Context","textWithKeyword":"And I click the \"a[data-target='#collapse_course_access']\" element"},{"pwStepLine":246,"gherkinStepLine":304,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":247,"gherkinStepLine":305,"keywordType":"Context","textWithKeyword":"And I fill in the following:"},{"pwStepLine":248,"gherkinStepLine":307,"keywordType":"Context","textWithKeyword":"And I press \"Save settings\""},{"pwStepLine":249,"gherkinStepLine":308,"keywordType":"Outcome","textWithKeyword":"Then I wait for the page to be loaded"},{"pwStepLine":250,"gherkinStepLine":309,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error"},{"pwStepLine":251,"gherkinStepLine":313,"keywordType":"Context","textWithKeyword":"Given I am not logged"},{"pwStepLine":252,"gherkinStepLine":314,"keywordType":"Context","textWithKeyword":"And I am a student"},{"pwStepLine":253,"gherkinStepLine":315,"keywordType":"Context","textWithKeyword":"And I am on the modern homepage of course \"PASSWORDPROTECTED\""},{"pwStepLine":254,"gherkinStepLine":316,"keywordType":"Outcome","textWithKeyword":"Then I should see \"This course requires a password\""},{"pwStepLine":255,"gherkinStepLine":317,"keywordType":"Action","textWithKeyword":"When I fill in \"course_password\" with \"wrong-password\""},{"pwStepLine":256,"gherkinStepLine":318,"keywordType":"Action","textWithKeyword":"And I press \"Accept\""},{"pwStepLine":257,"gherkinStepLine":319,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":258,"gherkinStepLine":320,"keywordType":"Outcome","textWithKeyword":"Then I should see \"The course password is incorrect\""},{"pwStepLine":259,"gherkinStepLine":321,"keywordType":"Action","textWithKeyword":"When I fill in \"course_password\" with \"123456\""},{"pwStepLine":260,"gherkinStepLine":322,"keywordType":"Action","textWithKeyword":"And I press \"Accept\""},{"pwStepLine":261,"gherkinStepLine":323,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded when ready"},{"pwStepLine":262,"gherkinStepLine":324,"keywordType":"Outcome","textWithKeyword":"Then I should be on the modern homepage of course \"PASSWORDPROTECTED\""},{"pwStepLine":263,"gherkinStepLine":325,"keywordType":"Outcome","textWithKeyword":"And I should see \"Password Protected\""},{"pwStepLine":264,"gherkinStepLine":326,"keywordType":"Outcome","textWithKeyword":"And I should not see \"The course password is incorrect\""}]},
  {"pwTestLine":267,"pickleLine":328,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":11,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":268,"gherkinStepLine":329,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/course_add.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/course_add.php\"","children":[{"start":9,"value":"/main/admin/course_add.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":269,"gherkinStepLine":330,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":270,"gherkinStepLine":331,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"not authorized\"","stepMatchArguments":[{"group":{"start":17,"value":"\"not authorized\"","children":[{"start":18,"value":"not authorized","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":271,"gherkinStepLine":332,"keywordType":"Action","textWithKeyword":"When I fill in \"title\" with \"TEMP_PRIVATE\"","stepMatchArguments":[{"group":{"start":10,"value":"\"title\"","children":[{"start":11,"value":"title","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":23,"value":"\"TEMP_PRIVATE\"","children":[{"start":24,"value":"TEMP_PRIVATE","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":272,"gherkinStepLine":333,"keywordType":"Action","textWithKeyword":"And I select \"Language skills\" from the ajax select \"update_course_course_categories\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Language skills\"","children":[{"start":10,"value":"Language skills","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":48,"value":"\"update_course_course_categories\"","children":[{"start":49,"value":"update_course_course_categories","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":273,"gherkinStepLine":340,"keywordType":"Action","textWithKeyword":"And I select \"English\" from \"course_language\"","stepMatchArguments":[{"group":{"start":9,"value":"\"English\"","children":[{"start":10,"value":"English","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":24,"value":"\"course_language\"","children":[{"start":25,"value":"course_language","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":274,"gherkinStepLine":341,"keywordType":"Outcome","textWithKeyword":"Then I check the \"Private access (access authorized to group members only)\" radio button","stepMatchArguments":[{"group":{"start":12,"value":"\"Private access (access authorized to group members only)\"","children":[{"start":13,"value":"Private access (access authorized to group members only)","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":275,"gherkinStepLine":342,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":276,"gherkinStepLine":343,"keywordType":"Outcome","textWithKeyword":"Then wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":277,"gherkinStepLine":344,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end