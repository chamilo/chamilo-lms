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

  test('Now switch to Andrea (student) and verify messages', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am a student', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I follow "Chat"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "USER1"', null, { page }); 
    await Then('I should not see "Hello"', null, { page }); 
    await Then('I click the "button#John_Doe_chat" element', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "Hello"', null, { page }); 
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
  {"pwTestLine":25,"pickleLine":82,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":83,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":27,"gherkinStepLine":84,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":28,"gherkinStepLine":85,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":86,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Chat\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Chat\"","children":[{"start":10,"value":"Chat","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":87,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":88,"keywordType":"Outcome","textWithKeyword":"Then I should see \"USER1\"","stepMatchArguments":[{"group":{"start":13,"value":"\"USER1\"","children":[{"start":14,"value":"USER1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":89,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Hello\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Hello\"","children":[{"start":18,"value":"Hello","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":92,"keywordType":"Outcome","textWithKeyword":"Then I click the \"button#John_Doe_chat\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"button#John_Doe_chat\"","children":[{"start":13,"value":"button#John_Doe_chat","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":93,"keywordType":"Outcome","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":94,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Hello\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Hello\"","children":[{"start":14,"value":"Hello","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end