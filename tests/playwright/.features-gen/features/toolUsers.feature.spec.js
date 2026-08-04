// Generated from: features/toolUsers.feature
import { test } from "playwright-bdd";

test.describe('Users tool', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Admin searches for \'amann\' and unsubscribes the user', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow the course tool "Users"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"search_user_keyword"},{"value":"amann"}]}]}}, { page }); 
    await And('I click the "em.mdi-magnify" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "amann"', null, { page }); 
    await And('I follow "Unsubscribe"', null, { page }); 
    await And('I confirm the popup', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I click the "i.mdi-account-plus" element', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"search_user_keyword"},{"value":"amann"}]}]}}, { page }); 
    await And('I click the "em.mdi-magnify" element', null, { page }); 
    await And('I click the "a.btn-small" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });

  test('Admin uses a specific tab then searches for \'ywarnier\' and unsubscribes', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow the course tool "Users"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Trainers"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I click the "i.mdi-account-plus" element', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"search_user_keyword"},{"value":"ywarnier"}]}]}}, { page }); 
    await And('I click the "em.mdi-magnify" element', null, { page }); 
    await And('I click the "a.btn-small" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "ywarnier"', null, { page }); 
    await And('I click the "a[title=\'Unsubscribe\']" icon in the row for "ywarnier"', null, { page }); 
    await And('I confirm the popup', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolUsers.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":76,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":74,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":77,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":78,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":79,"keywordType":"Context","textWithKeyword":"And I follow the course tool \"Users\"","stepMatchArguments":[{"group":{"start":25,"value":"\"Users\"","children":[{"start":26,"value":"Users","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":81,"keywordType":"Context","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":17,"gherkinStepLine":83,"keywordType":"Context","textWithKeyword":"And I click the \"em.mdi-magnify\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"em.mdi-magnify\"","children":[{"start":13,"value":"em.mdi-magnify","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":84,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":85,"keywordType":"Outcome","textWithKeyword":"Then I should see \"amann\"","stepMatchArguments":[{"group":{"start":13,"value":"\"amann\"","children":[{"start":14,"value":"amann","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":86,"keywordType":"Outcome","textWithKeyword":"And I follow \"Unsubscribe\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Unsubscribe\"","children":[{"start":10,"value":"Unsubscribe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":87,"keywordType":"Outcome","textWithKeyword":"And I confirm the popup","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":88,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":92,"keywordType":"Outcome","textWithKeyword":"And I click the \"i.mdi-account-plus\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-account-plus\"","children":[{"start":13,"value":"i.mdi-account-plus","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":93,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":95,"keywordType":"Outcome","textWithKeyword":"And I click the \"em.mdi-magnify\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"em.mdi-magnify\"","children":[{"start":13,"value":"em.mdi-magnify","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":96,"keywordType":"Outcome","textWithKeyword":"And I click the \"a.btn-small\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"a.btn-small\"","children":[{"start":13,"value":"a.btn-small","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":97,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]}]},
  {"pwTestLine":30,"pickleLine":99,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":74,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":100,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":101,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":102,"keywordType":"Context","textWithKeyword":"And I follow the course tool \"Users\"","stepMatchArguments":[{"group":{"start":25,"value":"\"Users\"","children":[{"start":26,"value":"Users","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":103,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":104,"keywordType":"Context","textWithKeyword":"And I follow \"Trainers\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Trainers\"","children":[{"start":10,"value":"Trainers","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":36,"gherkinStepLine":105,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":106,"keywordType":"Context","textWithKeyword":"And I click the \"i.mdi-account-plus\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-account-plus\"","children":[{"start":13,"value":"i.mdi-account-plus","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":107,"keywordType":"Context","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":109,"keywordType":"Context","textWithKeyword":"And I click the \"em.mdi-magnify\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"em.mdi-magnify\"","children":[{"start":13,"value":"em.mdi-magnify","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":110,"keywordType":"Context","textWithKeyword":"And I click the \"a.btn-small\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"a.btn-small\"","children":[{"start":13,"value":"a.btn-small","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":111,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":42,"gherkinStepLine":112,"keywordType":"Outcome","textWithKeyword":"Then I should see \"ywarnier\"","stepMatchArguments":[{"group":{"start":13,"value":"\"ywarnier\"","children":[{"start":14,"value":"ywarnier","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":43,"gherkinStepLine":113,"keywordType":"Outcome","textWithKeyword":"And I click the \"a[title='Unsubscribe']\" icon in the row for \"ywarnier\"","stepMatchArguments":[{"group":{"start":12,"value":"\"a[title='Unsubscribe']\"","children":[{"start":13,"value":"a[title='Unsubscribe']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":57,"value":"\"ywarnier\"","children":[{"start":58,"value":"ywarnier","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":114,"keywordType":"Outcome","textWithKeyword":"And I confirm the popup","stepMatchArguments":[]}]},
]; // bdd-data-end