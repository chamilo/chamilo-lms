// Generated from: features/courseCategory.feature
import { test } from "playwright-bdd";

test.describe('Course category', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
  });
  
  test('Add a course category', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/admin/course_category.php?action=add"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I should see "Add category"', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"code"},{"value":"COURSE_CATEGORY"}]},{"cells":[{"value":"title"},{"value":"Course category"}]}]}}, { page }); 
    await Then('I fill in editor field "description" with "description"', null, { page }); 
    await Then('I attach the file "/public/img/logo.png" to "picture"', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Edit a course category', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/admin/course_category.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Course category"', null, { page }); 
    await And('I click the "i.mdi-pencil" icon in the row for "Course category"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Edit this category"', null, { page }); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Course category edited"}]}]}}, { page }); 
    await Then('I fill in editor field "description" with "description edited"', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await And('I should see "Course category edited"', null, { page }); 
  });

  test('Delete course category', async ({ Given, Then, And, page }) => { 
    await Given('I am on "/main/admin/course_category.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Course category edited"', null, { page }); 
    await Then('I click the "i.mdi-delete" icon in the row for "Course category edited"', null, { page }); 
    await Then('confirm the popup', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see "Course category edited"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/courseCategory.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":21,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/course_category.php?action=add\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/course_category.php?action=add\"","children":[{"start":9,"value":"/main/admin/course_category.php?action=add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"And I should see \"Add category\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Add category\"","children":[{"start":14,"value":"Add category","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":25,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"Then I fill in editor field \"description\" with \"description\"","stepMatchArguments":[{"group":{"start":23,"value":"\"description\"","children":[{"start":24,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"description\"","children":[{"start":43,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":29,"keywordType":"Outcome","textWithKeyword":"Then I attach the file \"/public/img/logo.png\" to \"picture\"","stepMatchArguments":[{"group":{"start":18,"value":"\"/public/img/logo.png\"","children":[{"start":19,"value":"/public/img/logo.png","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":44,"value":"\"picture\"","children":[{"start":45,"value":"picture","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":30,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":32,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":22,"pickleLine":34,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/course_category.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/course_category.php\"","children":[{"start":9,"value":"/main/admin/course_category.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":37,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Course category\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course category\"","children":[{"start":14,"value":"Course category","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":38,"keywordType":"Outcome","textWithKeyword":"And I click the \"i.mdi-pencil\" icon in the row for \"Course category\"","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-pencil\"","children":[{"start":13,"value":"i.mdi-pencil","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":47,"value":"\"Course category\"","children":[{"start":48,"value":"Course category","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":40,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Edit this category\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Edit this category\"","children":[{"start":14,"value":"Edit this category","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":41,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":43,"keywordType":"Outcome","textWithKeyword":"Then I fill in editor field \"description\" with \"description edited\"","stepMatchArguments":[{"group":{"start":23,"value":"\"description\"","children":[{"start":24,"value":"description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"description edited\"","children":[{"start":43,"value":"description edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":44,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":46,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":34,"gherkinStepLine":47,"keywordType":"Outcome","textWithKeyword":"And I should see \"Course category edited\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course category edited\"","children":[{"start":14,"value":"Course category edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":37,"pickleLine":49,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/admin/course_category.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/course_category.php\"","children":[{"start":9,"value":"/main/admin/course_category.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":39,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":52,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Course category edited\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course category edited\"","children":[{"start":14,"value":"Course category edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":53,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-delete\" icon in the row for \"Course category edited\"","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-delete\"","children":[{"start":13,"value":"i.mdi-delete","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":47,"value":"\"Course category edited\"","children":[{"start":48,"value":"Course category edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":42,"gherkinStepLine":54,"keywordType":"Outcome","textWithKeyword":"Then confirm the popup","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":55,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":56,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Course category edited\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Course category edited\"","children":[{"start":18,"value":"Course category edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end