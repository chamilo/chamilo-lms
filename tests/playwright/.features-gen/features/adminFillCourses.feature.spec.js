// Generated from: features/adminFillCourses.feature
import { test } from "playwright-bdd";

test.describe('Admin fill courses and subscribe users', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
  });
  
  test('Admin fills courses then subscribes a user to a course with long waits', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/admin/filler.php?fill=courses"', null, { page }); 
    await When('wait very long for the page to be loaded', null, { page }); 
    await When('I am on "/main/admin/subscribe_user2course.php"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await And('I select "Warnier Yannick (ywarnier)" from "UserList[]"', null, { page }); 
    await And('I select "(SOLARSYSTEM) Our solar system" from "CourseList[]"', null, { page }); 
    await When('I press "Add to the course(s)"', null, { page }); 
    await When('wait very long for the page to be loaded', null, { page }); 
    await Then('I should see "The selected users are subscribed to the selected course"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminFillCourses.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":30,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/filler.php?fill=courses\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/filler.php?fill=courses\"","children":[{"start":9,"value":"/main/admin/filler.php?fill=courses","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":32,"keywordType":"Action","textWithKeyword":"When wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":33,"keywordType":"Action","textWithKeyword":"When I am on \"/main/admin/subscribe_user2course.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/subscribe_user2course.php\"","children":[{"start":9,"value":"/main/admin/subscribe_user2course.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":34,"keywordType":"Action","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":35,"keywordType":"Action","textWithKeyword":"And I select \"Warnier Yannick (ywarnier)\" from \"UserList[]\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Warnier Yannick (ywarnier)\"","children":[{"start":10,"value":"Warnier Yannick (ywarnier)","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":43,"value":"\"UserList[]\"","children":[{"start":44,"value":"UserList[]","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":36,"keywordType":"Action","textWithKeyword":"And I select \"(SOLARSYSTEM) Our solar system\" from \"CourseList[]\"","stepMatchArguments":[{"group":{"start":9,"value":"\"(SOLARSYSTEM) Our solar system\"","children":[{"start":10,"value":"(SOLARSYSTEM) Our solar system","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":47,"value":"\"CourseList[]\"","children":[{"start":48,"value":"CourseList[]","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":37,"keywordType":"Action","textWithKeyword":"When I press \"Add to the course(s)\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add to the course(s)\"","children":[{"start":9,"value":"Add to the course(s)","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":38,"keywordType":"Action","textWithKeyword":"When wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"Then I should see \"The selected users are subscribed to the selected course\"","stepMatchArguments":[{"group":{"start":13,"value":"\"The selected users are subscribed to the selected course\"","children":[{"start":14,"value":"The selected users are subscribed to the selected course","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end