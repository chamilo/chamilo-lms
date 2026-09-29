// Generated from: features/systemAnnouncements.feature
import { test } from "playwright-bdd";

test.describe('System Announcements', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
  });
  
  test('Create a system announcement', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/admin/system_announcements.php?action=add"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Announcement system test"}]}]}}, { page }); 
    await And('I fill in editor field "content" with "Announcement system description"', null, { page }); 
    await And('I select "Invitee" from "roles[]"', null, { page }); 
    await And('I press "Add news"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should see "Announcement system test"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Delete system announcement', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/admin/system_announcements.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I click the "i.mdi-delete" icon in the row for "Announcement system test"', null, { page }); 
    await Then('I confirm the popup', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see "Announcement system test"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/systemAnnouncements.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":23,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true,"stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/system_announcements.php?action=add\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/system_announcements.php?action=add\"","children":[{"start":9,"value":"/main/admin/system_announcements.php?action=add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":25,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":26,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":28,"keywordType":"Action","textWithKeyword":"And I fill in editor field \"content\" with \"Announcement system description\"","stepMatchArguments":[{"group":{"start":23,"value":"\"content\"","children":[{"start":24,"value":"content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":38,"value":"\"Announcement system description\"","children":[{"start":39,"value":"Announcement system description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":29,"keywordType":"Action","textWithKeyword":"And I select \"Invitee\" from \"roles[]\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Invitee\"","children":[{"start":10,"value":"Invitee","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":24,"value":"\"roles[]\"","children":[{"start":25,"value":"roles[]","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":30,"keywordType":"Action","textWithKeyword":"And I press \"Add news\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add news\"","children":[{"start":9,"value":"Add news","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":31,"keywordType":"Action","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":32,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Announcement system test\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Announcement system test\"","children":[{"start":14,"value":"Announcement system test","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":33,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":23,"pickleLine":35,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true,"stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/system_announcements.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/system_announcements.php\"","children":[{"start":9,"value":"/main/admin/system_announcements.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":25,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":38,"keywordType":"Action","textWithKeyword":"When I click the \"i.mdi-delete\" icon in the row for \"Announcement system test\"","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-delete\"","children":[{"start":13,"value":"i.mdi-delete","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":47,"value":"\"Announcement system test\"","children":[{"start":48,"value":"Announcement system test","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"Then I confirm the popup","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":40,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":41,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Announcement system test\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Announcement system test\"","children":[{"start":18,"value":"Announcement system test","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":42,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end