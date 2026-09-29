// Generated from: features/createUserViaCSV.feature
import { test } from "playwright-bdd";

test.describe('Users creation via CSV', () => {

  test('Import user via CSV', { tag: ['@administration'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/admin/user_import.php"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I attach the file "/public/main/admin/example.csv" to "import_file"', null, { page }); 
    await Then('I press "Import"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
    await Then('I am on "/admin/user-list?keyword=drbrown@example.net"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should see "emmert"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/createUserViaCSV.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":18,"tags":["@administration"],"steps":[{"pwStepLine":7,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"And I am on \"/main/admin/user_import.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/admin/user_import.php\"","children":[{"start":9,"value":"/main/admin/user_import.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":22,"keywordType":"Outcome","textWithKeyword":"Then I attach the file \"/public/main/admin/example.csv\" to \"import_file\"","stepMatchArguments":[{"group":{"start":18,"value":"\"/public/main/admin/example.csv\"","children":[{"start":19,"value":"/public/main/admin/example.csv","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":54,"value":"\"import_file\"","children":[{"start":55,"value":"import_file","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":23,"keywordType":"Outcome","textWithKeyword":"Then I press \"Import\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Import\"","children":[{"start":9,"value":"Import","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":24,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":25,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":26,"keywordType":"Outcome","textWithKeyword":"Then I am on \"/admin/user-list?keyword=drbrown@example.net\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/user-list?keyword=drbrown@example.net\"","children":[{"start":9,"value":"/admin/user-list?keyword=drbrown@example.net","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":27,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":28,"keywordType":"Outcome","textWithKeyword":"Then I should see \"emmert\"","stepMatchArguments":[{"group":{"start":13,"value":"\"emmert\"","children":[{"start":14,"value":"emmert","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end