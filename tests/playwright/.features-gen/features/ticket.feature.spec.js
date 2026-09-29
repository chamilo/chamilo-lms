// Generated from: features/ticket.feature
import { test } from "playwright-bdd";

test.describe('Ticket', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
  });
  
  test('Open the Ticket list', { tag: ['@slow-scenario'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on "/tickets?project_id=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Ticket number"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Create a ticket', { tag: ['@slow-scenario'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/tickets/create?project_id=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"subject"},{"value":"Vue functional ticket"}]}]}}, { page }); 
    await And('I fill in tinymce field "ticket-content" with "Ticket description from the Vue interface"', null, { page }); 
    await And('I press "Send message"', null, { page }); 
    await Then('I wait very long for the page to be loaded', null, { page }); 
    await And('I should see "Vue functional ticket"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Check Ticket projects', { tag: ['@slow-scenario'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on "/tickets/settings?section=projects"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Ticket System"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Check Ticket categories', { tag: ['@slow-scenario'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on "/tickets/settings?section=categories&project_id=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Enrollment"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Check Ticket statuses', { tag: ['@slow-scenario'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on "/tickets/settings?section=statuses"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "New"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Check Ticket priorities', { tag: ['@slow-scenario'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on "/tickets/settings?section=priorities"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Normal"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Create a Ticket project', { tag: ['@slow-scenario'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/tickets/settings?section=projects"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I create a ticket setting with title "Vue Ticket Project" and description "Project created from Vue"', null, { page }); 
    await And('I click the "#ticket-settings-save" element', null, { page }); 
    await Then('I wait very long for the page to be loaded', null, { page }); 
    await And('I should see "Vue Ticket Project"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Create a Ticket category', { tag: ['@slow-scenario'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/tickets/settings?section=categories&project_id=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I create a ticket setting with title "Vue Ticket Category" and description "Category created from Vue"', null, { page }); 
    await And('I click the "#ticket-settings-save" element', null, { page }); 
    await Then('I wait very long for the page to be loaded', null, { page }); 
    await And('I should see "Vue Ticket Category"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Create a Ticket status', { tag: ['@slow-scenario'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/tickets/settings?section=statuses"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I create a ticket setting with title "Vue Ticket Status" and description "Status created from Vue"', null, { page }); 
    await And('I click the "#ticket-settings-save" element', null, { page }); 
    await Then('I wait very long for the page to be loaded', null, { page }); 
    await And('I should see "Vue Ticket Status"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Create a Ticket priority', { tag: ['@slow-scenario'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/tickets/settings?section=priorities"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I create a ticket setting with title "Vue Ticket Priority" and description "Priority created from Vue"', null, { page }); 
    await And('I click the "#ticket-settings-save" element', null, { page }); 
    await Then('I wait very long for the page to be loaded', null, { page }); 
    await And('I should see "Vue Ticket Priority"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Deny Ticket settings to a student', { tag: ['@slow-scenario'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am a student', null, { page }); 
    await When('I am on "/tickets/settings?section=projects"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see "Vue Ticket Project"', null, { page }); 
  });

});

// == technical section ==

test.beforeEach('BeforeEach Hooks', ({ $runScenarioHooks }) => $runScenarioHooks('before', {  }));

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/ticket.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":62,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets?project_id=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets?project_id=1\"","children":[{"start":9,"value":"/tickets?project_id=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":65,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Ticket number\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Ticket number\"","children":[{"start":14,"value":"Ticket number","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":66,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":17,"pickleLine":68,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/create?project_id=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/create?project_id=1\"","children":[{"start":9,"value":"/tickets/create?project_id=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":70,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":71,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":73,"keywordType":"Action","textWithKeyword":"And I fill in tinymce field \"ticket-content\" with \"Ticket description from the Vue interface\"","stepMatchArguments":[{"group":{"start":24,"value":"\"ticket-content\"","children":[{"start":25,"value":"ticket-content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":46,"value":"\"Ticket description from the Vue interface\"","children":[{"start":47,"value":"Ticket description from the Vue interface","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":22,"gherkinStepLine":74,"keywordType":"Action","textWithKeyword":"And I press \"Send message\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Send message\"","children":[{"start":9,"value":"Send message","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":75,"keywordType":"Outcome","textWithKeyword":"Then I wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":76,"keywordType":"Outcome","textWithKeyword":"And I should see \"Vue functional ticket\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Vue functional ticket\"","children":[{"start":14,"value":"Vue functional ticket","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":25,"gherkinStepLine":77,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":28,"pickleLine":79,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/settings?section=projects\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=projects\"","children":[{"start":9,"value":"/tickets/settings?section=projects","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":81,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":82,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Ticket System\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Ticket System\"","children":[{"start":14,"value":"Ticket System","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":32,"gherkinStepLine":83,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":35,"pickleLine":85,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":86,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/settings?section=categories&project_id=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=categories&project_id=1\"","children":[{"start":9,"value":"/tickets/settings?section=categories&project_id=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":87,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":38,"gherkinStepLine":88,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Enrollment\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Enrollment\"","children":[{"start":14,"value":"Enrollment","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":39,"gherkinStepLine":89,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":42,"pickleLine":91,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/settings?section=statuses\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=statuses\"","children":[{"start":9,"value":"/tickets/settings?section=statuses","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":45,"gherkinStepLine":94,"keywordType":"Outcome","textWithKeyword":"Then I should see \"New\"","stepMatchArguments":[{"group":{"start":13,"value":"\"New\"","children":[{"start":14,"value":"New","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":46,"gherkinStepLine":95,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":49,"pickleLine":97,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":50,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/settings?section=priorities\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=priorities\"","children":[{"start":9,"value":"/tickets/settings?section=priorities","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":51,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":100,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Normal\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Normal\"","children":[{"start":14,"value":"Normal","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":101,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":56,"pickleLine":103,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":57,"gherkinStepLine":104,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/settings?section=projects\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=projects\"","children":[{"start":9,"value":"/tickets/settings?section=projects","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":58,"gherkinStepLine":105,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":59,"gherkinStepLine":106,"keywordType":"Action","textWithKeyword":"When I create a ticket setting with title \"Vue Ticket Project\" and description \"Project created from Vue\"","stepMatchArguments":[{"group":{"start":37,"value":"\"Vue Ticket Project\"","children":[{"start":38,"value":"Vue Ticket Project","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":74,"value":"\"Project created from Vue\"","children":[{"start":75,"value":"Project created from Vue","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":60,"gherkinStepLine":107,"keywordType":"Action","textWithKeyword":"And I click the \"#ticket-settings-save\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"#ticket-settings-save\"","children":[{"start":13,"value":"#ticket-settings-save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":61,"gherkinStepLine":108,"keywordType":"Outcome","textWithKeyword":"Then I wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":62,"gherkinStepLine":109,"keywordType":"Outcome","textWithKeyword":"And I should see \"Vue Ticket Project\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Vue Ticket Project\"","children":[{"start":14,"value":"Vue Ticket Project","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":63,"gherkinStepLine":110,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":66,"pickleLine":112,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":67,"gherkinStepLine":113,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/settings?section=categories&project_id=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=categories&project_id=1\"","children":[{"start":9,"value":"/tickets/settings?section=categories&project_id=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":68,"gherkinStepLine":114,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":69,"gherkinStepLine":115,"keywordType":"Action","textWithKeyword":"When I create a ticket setting with title \"Vue Ticket Category\" and description \"Category created from Vue\"","stepMatchArguments":[{"group":{"start":37,"value":"\"Vue Ticket Category\"","children":[{"start":38,"value":"Vue Ticket Category","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":75,"value":"\"Category created from Vue\"","children":[{"start":76,"value":"Category created from Vue","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":70,"gherkinStepLine":116,"keywordType":"Action","textWithKeyword":"And I click the \"#ticket-settings-save\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"#ticket-settings-save\"","children":[{"start":13,"value":"#ticket-settings-save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":71,"gherkinStepLine":117,"keywordType":"Outcome","textWithKeyword":"Then I wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":72,"gherkinStepLine":118,"keywordType":"Outcome","textWithKeyword":"And I should see \"Vue Ticket Category\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Vue Ticket Category\"","children":[{"start":14,"value":"Vue Ticket Category","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":73,"gherkinStepLine":119,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":76,"pickleLine":121,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":77,"gherkinStepLine":122,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/settings?section=statuses\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=statuses\"","children":[{"start":9,"value":"/tickets/settings?section=statuses","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":78,"gherkinStepLine":123,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":79,"gherkinStepLine":124,"keywordType":"Action","textWithKeyword":"When I create a ticket setting with title \"Vue Ticket Status\" and description \"Status created from Vue\"","stepMatchArguments":[{"group":{"start":37,"value":"\"Vue Ticket Status\"","children":[{"start":38,"value":"Vue Ticket Status","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":73,"value":"\"Status created from Vue\"","children":[{"start":74,"value":"Status created from Vue","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":80,"gherkinStepLine":125,"keywordType":"Action","textWithKeyword":"And I click the \"#ticket-settings-save\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"#ticket-settings-save\"","children":[{"start":13,"value":"#ticket-settings-save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":81,"gherkinStepLine":126,"keywordType":"Outcome","textWithKeyword":"Then I wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":82,"gherkinStepLine":127,"keywordType":"Outcome","textWithKeyword":"And I should see \"Vue Ticket Status\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Vue Ticket Status\"","children":[{"start":14,"value":"Vue Ticket Status","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":83,"gherkinStepLine":128,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":86,"pickleLine":130,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":87,"gherkinStepLine":131,"keywordType":"Context","textWithKeyword":"Given I am on \"/tickets/settings?section=priorities\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=priorities\"","children":[{"start":9,"value":"/tickets/settings?section=priorities","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":88,"gherkinStepLine":132,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":89,"gherkinStepLine":133,"keywordType":"Action","textWithKeyword":"When I create a ticket setting with title \"Vue Ticket Priority\" and description \"Priority created from Vue\"","stepMatchArguments":[{"group":{"start":37,"value":"\"Vue Ticket Priority\"","children":[{"start":38,"value":"Vue Ticket Priority","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":75,"value":"\"Priority created from Vue\"","children":[{"start":76,"value":"Priority created from Vue","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":90,"gherkinStepLine":134,"keywordType":"Action","textWithKeyword":"And I click the \"#ticket-settings-save\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"#ticket-settings-save\"","children":[{"start":13,"value":"#ticket-settings-save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":91,"gherkinStepLine":135,"keywordType":"Outcome","textWithKeyword":"Then I wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":92,"gherkinStepLine":136,"keywordType":"Outcome","textWithKeyword":"And I should see \"Vue Ticket Priority\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Vue Ticket Priority\"","children":[{"start":14,"value":"Vue Ticket Priority","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":93,"gherkinStepLine":137,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":96,"pickleLine":139,"tags":["@slow-scenario"],"steps":[{"pwStepLine":7,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":97,"gherkinStepLine":140,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":98,"gherkinStepLine":141,"keywordType":"Action","textWithKeyword":"When I am on \"/tickets/settings?section=projects\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets/settings?section=projects\"","children":[{"start":9,"value":"/tickets/settings?section=projects","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":99,"gherkinStepLine":142,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":100,"gherkinStepLine":143,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Vue Ticket Project\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Vue Ticket Project\"","children":[{"start":18,"value":"Vue Ticket Project","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end