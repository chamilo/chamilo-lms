// Generated from: features/admin/courseSettingsPolicies.feature
import { test } from "playwright-bdd";

test.describe('Platform policies for course settings', () => {

  test('Platform administrator can inspect the generated course settings policy list', { tag: ['@common'] }, async ({ Given, Then, And, page }) => {
    await Given('I am a platform administrator', null, { page });
    await And('I am on "/admin/settings/course-settings"', null, { page });
    await And('I wait for the page to be loaded', null, { page });
    await Then('I should not see an error', null, { page });
    await And('I should see "student_validate_own_attendance"', null, { page });
    await And('I should see the "input[name=\'editable[student_validate_own_attendance]\']" element', null, { page });
  });

  test('Teachers cannot access platform course-setting policies', { tag: ['@common'] }, async ({ Given, Then, And, page }) => {
    await Given('I am a teacher', null, { page });
    await And('I am on "/admin/settings/course-settings"', null, { page });
    await And('I wait for the page to be loaded', null, { page });
    await Then('I should not see "Default platform value"', null, { page });
  });

  test('Students cannot access platform course-setting policies', { tag: ['@common'] }, async ({ Given, Then, And, page }) => {
    await Given('I am a student', null, { page });
    await And('I am on "/admin/settings/course-settings"', null, { page });
    await And('I wait for the page to be loaded', null, { page });
    await Then('I should not see "Default platform value"', null, { page });
  });

  test('Shared enabled settings are identified by their tool category', { tag: ['@common'] }, async ({ Given, Then, And, page }) => {
    await Given('I am a platform administrator', null, { page });
    await And('I am on "/admin/settings/course-settings?q=wiki.enabled"', null, { page });
    await And('I wait for the page to be loaded', null, { page });
    await Then('I should not see an error', null, { page });
    await And('I should see "wiki.enabled"', null, { page });
    await And('I should see the "input[name=\'editable[wiki.enabled]\']" element', null, { page });
  });

  test('Attendance policies remain visible with legacy blank categories', { tag: ['@common'] }, async ({ Given, Then, And, page }) => {
    await Given('I am a platform administrator', null, { page });
    await And('I am on "/admin/settings/course-settings?q=student_validate_own_attendance"', null, { page });
    await And('I wait for the page to be loaded', null, { page });
    await Then('I should not see an error', null, { page });
    await And('I should see "student_validate_own_attendance"', null, { page });
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/admin/courseSettingsPolicies.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":7,"tags":["@common"],"steps":[{"pwStepLine":7,"gherkinStepLine":8,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":9,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/course-settings\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/course-settings\"","children":[{"start":9,"value":"/admin/settings/course-settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":10,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":11,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":12,"keywordType":"Outcome","textWithKeyword":"And I should see \"student_validate_own_attendance\"","stepMatchArguments":[{"group":{"start":13,"value":"\"student_validate_own_attendance\"","children":[{"start":14,"value":"student_validate_own_attendance","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":13,"keywordType":"Outcome","textWithKeyword":"And I should see the \"input[name='editable[student_validate_own_attendance]']\" element","stepMatchArguments":[{"group":{"start":17,"value":"\"input[name='editable[student_validate_own_attendance]']\"","children":[{"start":18,"value":"input[name='editable[student_validate_own_attendance]']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":15,"pickleLine":15,"tags":["@common"],"steps":[{"pwStepLine":16,"gherkinStepLine":16,"keywordType":"Context","textWithKeyword":"Given I am a teacher","stepMatchArguments":[]},{"pwStepLine":17,"gherkinStepLine":17,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/course-settings\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/course-settings\"","children":[{"start":9,"value":"/admin/settings/course-settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":18,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":19,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Default platform value\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Default platform value\"","children":[{"start":18,"value":"Default platform value","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":22,"pickleLine":21,"tags":["@common"],"steps":[{"pwStepLine":23,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":23,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/course-settings\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/course-settings\"","children":[{"start":9,"value":"/admin/settings/course-settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":25,"gherkinStepLine":24,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":25,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Default platform value\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Default platform value\"","children":[{"start":18,"value":"Default platform value","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":29,"pickleLine":27,"tags":["@common"],"steps":[{"pwStepLine":30,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/course-settings?q=wiki.enabled\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/course-settings?q=wiki.enabled\"","children":[{"start":9,"value":"/admin/settings/course-settings?q=wiki.enabled","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":34,"gherkinStepLine":32,"keywordType":"Outcome","textWithKeyword":"And I should see \"wiki.enabled\"","stepMatchArguments":[{"group":{"start":13,"value":"\"wiki.enabled\"","children":[{"start":14,"value":"wiki.enabled","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":35,"gherkinStepLine":33,"keywordType":"Outcome","textWithKeyword":"And I should see the \"input[name='editable[wiki.enabled]']\" element","stepMatchArguments":[{"group":{"start":17,"value":"\"input[name='editable[wiki.enabled]']\"","children":[{"start":18,"value":"input[name='editable[wiki.enabled]']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":38,"pickleLine":35,"tags":["@common"],"steps":[{"pwStepLine":39,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/course-settings?q=student_validate_own_attendance\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/course-settings?q=student_validate_own_attendance\"","children":[{"start":9,"value":"/admin/settings/course-settings?q=student_validate_own_attendance","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":42,"gherkinStepLine":39,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":40,"keywordType":"Outcome","textWithKeyword":"And I should see \"student_validate_own_attendance\"","stepMatchArguments":[{"group":{"start":13,"value":"\"student_validate_own_attendance\"","children":[{"start":14,"value":"student_validate_own_attendance","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end