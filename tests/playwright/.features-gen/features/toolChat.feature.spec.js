// Generated from: features/toolChat.feature
import { test } from "playwright-bdd";

test.describe('Chat tool', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
  });
  
  test('Admin sends public and private messages, Andrea checks them', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I follow "Chat"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"chat-writer"},{"value":"I am USER1"}]}]}}, { page }); 
    await Then('I click the "button#chat-send-message" element', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I click the "button#Andrea_Costea_chat" element', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"chat-writer"},{"value":"HelloAndrea"}]}]}}, { page }); 
    await Then('I click the "button#chat-send-message" element', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
  });

  test.skip('Now switch to Andrea (student) and verify messages', { tag: ['@common', '@tools', '@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I am a student'); 
    await And('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await Then('I follow "Chat"'); 
    await And('wait for the page to be loaded'); 
    await Then('I should see "USER1"'); 
    await Then('I should not see "Hello"'); 
    await Then('I click the "button#John_Doe_chat" element'); 
    await And('I wait for the page content to settle'); 
    await Then('I should see "Hello"'); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolChat.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":47,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":50,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Chat\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Chat\"","children":[{"start":10,"value":"Chat","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":51,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":53,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":55,"keywordType":"Outcome","textWithKeyword":"Then I click the \"button#chat-send-message\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"button#chat-send-message\"","children":[{"start":13,"value":"button#chat-send-message","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":56,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":59,"keywordType":"Outcome","textWithKeyword":"Then I click the \"button#Andrea_Costea_chat\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"button#Andrea_Costea_chat\"","children":[{"start":13,"value":"button#Andrea_Costea_chat","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":60,"keywordType":"Outcome","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":61,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":63,"keywordType":"Outcome","textWithKeyword":"Then I click the \"button#chat-send-message\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"button#chat-send-message\"","children":[{"start":13,"value":"button#chat-send-message","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":22,"gherkinStepLine":64,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]}]},
  {"pwTestLine":25,"pickleLine":71,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":26,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"Given I am a student"},{"pwStepLine":27,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":28,"gherkinStepLine":74,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":29,"gherkinStepLine":75,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Chat\""},{"pwStepLine":30,"gherkinStepLine":76,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":31,"gherkinStepLine":77,"keywordType":"Outcome","textWithKeyword":"Then I should see \"USER1\""},{"pwStepLine":32,"gherkinStepLine":78,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Hello\""},{"pwStepLine":33,"gherkinStepLine":81,"keywordType":"Outcome","textWithKeyword":"Then I click the \"button#John_Doe_chat\" element"},{"pwStepLine":34,"gherkinStepLine":82,"keywordType":"Outcome","textWithKeyword":"And I wait for the page content to settle"},{"pwStepLine":35,"gherkinStepLine":83,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Hello\""}]},
]; // bdd-data-end