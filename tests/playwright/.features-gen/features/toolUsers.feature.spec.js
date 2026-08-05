// Generated from: features/toolUsers.feature
import { test } from "playwright-bdd";

test.describe('Users tool', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test.skip('Admin searches for \'amann\' and unsubscribes the user', { tag: ['@common', '@tools', '@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow the course tool "Users"'); 
    await And('I wait for the page to be loaded'); 
    await And('I click the "[title=\'Search\']" element'); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"search"},{"value":"amann"}]}]}}); 
    await And('I press "Search"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "amann"'); 
    await And('I click the "[title=\'Unsubscribe\']" element'); 
    await And('I press "Yes"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should not see "amann"'); 
    await And('I click the "[title=\'Add\']" element'); 
    await And('I wait for the page to be loaded'); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"search"},{"value":"amann"}]}]}}); 
    await And('I press "Search"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "Mann"'); 
    await And('I click the "[title=\'Register\']" element'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "subscribed to the course"'); 
  });

  test('Admin uses the Teachers tab then searches for \'ywarnier\' and unsubscribes', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow the course tool "Users"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I press "Teachers"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I click the "[title=\'Add\']" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"search"},{"value":"ywarnier"}]}]}}, { page }); 
    await And('I press "Search"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Warnier"', null, { page }); 
    await And('I click the "[title=\'Register\']" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "subscribed to the course"', null, { page }); 
    await And('I click the "[title=\'Back\']" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "ywarnier"', null, { page }); 
    await And('I click the "[title=\'Unsubscribe\']" icon in the row for "ywarnier"', null, { page }); 
    await And('I press "Yes"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "ywarnier"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolUsers.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":87,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":79,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":8,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true},{"pwStepLine":12,"gherkinStepLine":88,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage"},{"pwStepLine":13,"gherkinStepLine":89,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":14,"gherkinStepLine":90,"keywordType":"Context","textWithKeyword":"And I follow the course tool \"Users\""},{"pwStepLine":15,"gherkinStepLine":91,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":16,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"And I click the \"[title='Search']\" element"},{"pwStepLine":17,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I fill in the following:"},{"pwStepLine":18,"gherkinStepLine":95,"keywordType":"Context","textWithKeyword":"And I press \"Search\""},{"pwStepLine":19,"gherkinStepLine":96,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":20,"gherkinStepLine":97,"keywordType":"Outcome","textWithKeyword":"Then I should see \"amann\""},{"pwStepLine":21,"gherkinStepLine":98,"keywordType":"Outcome","textWithKeyword":"And I click the \"[title='Unsubscribe']\" element"},{"pwStepLine":22,"gherkinStepLine":99,"keywordType":"Outcome","textWithKeyword":"And I press \"Yes\""},{"pwStepLine":23,"gherkinStepLine":100,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":24,"gherkinStepLine":101,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"amann\""},{"pwStepLine":25,"gherkinStepLine":106,"keywordType":"Outcome","textWithKeyword":"And I click the \"[title='Add']\" element"},{"pwStepLine":26,"gherkinStepLine":107,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":27,"gherkinStepLine":108,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:"},{"pwStepLine":28,"gherkinStepLine":110,"keywordType":"Outcome","textWithKeyword":"And I press \"Search\""},{"pwStepLine":29,"gherkinStepLine":111,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":30,"gherkinStepLine":115,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Mann\""},{"pwStepLine":31,"gherkinStepLine":116,"keywordType":"Outcome","textWithKeyword":"And I click the \"[title='Register']\" element"},{"pwStepLine":32,"gherkinStepLine":117,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":33,"gherkinStepLine":118,"keywordType":"Outcome","textWithKeyword":"Then I should see \"subscribed to the course\""}]},
  {"pwTestLine":36,"pickleLine":120,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":79,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":121,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":122,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":123,"keywordType":"Context","textWithKeyword":"And I follow the course tool \"Users\"","stepMatchArguments":[{"group":{"start":25,"value":"\"Users\"","children":[{"start":26,"value":"Users","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":124,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":41,"gherkinStepLine":125,"keywordType":"Context","textWithKeyword":"And I press \"Teachers\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Teachers\"","children":[{"start":9,"value":"Teachers","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":42,"gherkinStepLine":126,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":127,"keywordType":"Context","textWithKeyword":"And I click the \"[title='Add']\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"[title='Add']\"","children":[{"start":13,"value":"[title='Add']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":128,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":45,"gherkinStepLine":129,"keywordType":"Context","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":131,"keywordType":"Context","textWithKeyword":"And I press \"Search\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Search\"","children":[{"start":9,"value":"Search","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":132,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":48,"gherkinStepLine":135,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Warnier\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Warnier\"","children":[{"start":14,"value":"Warnier","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":49,"gherkinStepLine":136,"keywordType":"Outcome","textWithKeyword":"And I click the \"[title='Register']\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"[title='Register']\"","children":[{"start":13,"value":"[title='Register']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":50,"gherkinStepLine":137,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":51,"gherkinStepLine":138,"keywordType":"Outcome","textWithKeyword":"Then I should see \"subscribed to the course\"","stepMatchArguments":[{"group":{"start":13,"value":"\"subscribed to the course\"","children":[{"start":14,"value":"subscribed to the course","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":52,"gherkinStepLine":139,"keywordType":"Outcome","textWithKeyword":"And I click the \"[title='Back']\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"[title='Back']\"","children":[{"start":13,"value":"[title='Back']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":140,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":141,"keywordType":"Outcome","textWithKeyword":"Then I should see \"ywarnier\"","stepMatchArguments":[{"group":{"start":13,"value":"\"ywarnier\"","children":[{"start":14,"value":"ywarnier","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":55,"gherkinStepLine":142,"keywordType":"Outcome","textWithKeyword":"And I click the \"[title='Unsubscribe']\" icon in the row for \"ywarnier\"","stepMatchArguments":[{"group":{"start":12,"value":"\"[title='Unsubscribe']\"","children":[{"start":13,"value":"[title='Unsubscribe']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":56,"value":"\"ywarnier\"","children":[{"start":57,"value":"ywarnier","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":56,"gherkinStepLine":143,"keywordType":"Outcome","textWithKeyword":"And I press \"Yes\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Yes\"","children":[{"start":9,"value":"Yes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":57,"gherkinStepLine":144,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":58,"gherkinStepLine":145,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"ywarnier\"","stepMatchArguments":[{"group":{"start":17,"value":"\"ywarnier\"","children":[{"start":18,"value":"ywarnier","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end