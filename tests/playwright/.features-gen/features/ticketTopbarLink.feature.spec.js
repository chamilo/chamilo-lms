// Generated from: features/ticketTopbarLink.feature
import { test } from "playwright-bdd";

test.describe('Ticket icon in the topbar', () => {

  test('Allow only teachers in the default ticket project', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/settings/search_settings?keyword=show_link_ticket_notification"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I select "Yes" from "form_show_link_ticket_notification"', null, { page }); 
    await And('I press "Save settings"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/admin/settings/search_settings?keyword=ticket_project_user_roles"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"form_ticket_project_user_roles"},{"value":"{\"permissions\":{\"1\":[1]}}"}]}]}}, { page }); 
    await And('I press "Save settings"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('A platform administrator always sees the ticket icon', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/home"', null, { page }); 
    await And('I wait for the element "a.item-button[href^=\'/resources/messages\']" to appear', null, { page }); 
    await Then('I should see the "a.item-button[href^=\'/tickets\']" element', null, { page }); 
  });

  test('A teacher, whose role is allowed, sees the ticket icon', async ({ Given, Then, And, page }) => { 
    await Given('I am a teacher', null, { page }); 
    await And('I am on "/home"', null, { page }); 
    await And('I wait for the element "a.item-button[href^=\'/resources/messages\']" to appear', null, { page }); 
    await Then('I should see the "a.item-button[href^=\'/tickets\']" element', null, { page }); 
  });

  test('A student, whose role is not allowed, does not see the ticket icon', async ({ Given, Then, And, page }) => { 
    await Given('I am a student', null, { page }); 
    await And('I am on "/home"', null, { page }); 
    await And('I wait for the element "a.item-button[href^=\'/resources/messages\']" to appear', null, { page }); 
    await Then('I should not see the "a.item-button[href^=\'/tickets\']" element', null, { page }); 
  });

  test('A student can still reach their own tickets by URL, as in legacy', async ({ Given, Then, And, page }) => { 
    await Given('I am a student', null, { page }); 
    await And('I am on "/tickets?project_id=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Ticket number"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Restore the ticket settings to their defaults', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/settings/search_settings?keyword=show_link_ticket_notification"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I select "No" from "form_show_link_ticket_notification"', null, { page }); 
    await And('I press "Save settings"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I am on "/admin/settings/search_settings?keyword=ticket_project_user_roles"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"form_ticket_project_user_roles"},{"value":""}]}]}}, { page }); 
    await And('I press "Save settings"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/ticketTopbarLink.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":29,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":30,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":31,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/search_settings?keyword=show_link_ticket_notification\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/search_settings?keyword=show_link_ticket_notification\"","children":[{"start":9,"value":"/admin/settings/search_settings?keyword=show_link_ticket_notification","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":32,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"And I select \"Yes\" from \"form_show_link_ticket_notification\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Yes\"","children":[{"start":10,"value":"Yes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":20,"value":"\"form_show_link_ticket_notification\"","children":[{"start":21,"value":"form_show_link_ticket_notification","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"And I press \"Save settings\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save settings\"","children":[{"start":9,"value":"Save settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":35,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":36,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/search_settings?keyword=ticket_project_user_roles\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/search_settings?keyword=ticket_project_user_roles\"","children":[{"start":9,"value":"/admin/settings/search_settings?keyword=ticket_project_user_roles","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"And I press \"Save settings\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save settings\"","children":[{"start":9,"value":"Save settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":42,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":21,"pickleLine":44,"tags":[],"steps":[{"pwStepLine":22,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":46,"keywordType":"Context","textWithKeyword":"And I am on \"/home\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/home\"","children":[{"start":9,"value":"/home","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":47,"keywordType":"Context","textWithKeyword":"And I wait for the element \"a.item-button[href^='/resources/messages']\" to appear","stepMatchArguments":[{"group":{"start":24,"value":"a.item-button[href^='/resources/messages']"}}]},{"pwStepLine":25,"gherkinStepLine":48,"keywordType":"Outcome","textWithKeyword":"Then I should see the \"a.item-button[href^='/tickets']\" element","stepMatchArguments":[{"group":{"start":17,"value":"\"a.item-button[href^='/tickets']\"","children":[{"start":18,"value":"a.item-button[href^='/tickets']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":28,"pickleLine":50,"tags":[],"steps":[{"pwStepLine":29,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"Given I am a teacher","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":52,"keywordType":"Context","textWithKeyword":"And I am on \"/home\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/home\"","children":[{"start":9,"value":"/home","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":53,"keywordType":"Context","textWithKeyword":"And I wait for the element \"a.item-button[href^='/resources/messages']\" to appear","stepMatchArguments":[{"group":{"start":24,"value":"a.item-button[href^='/resources/messages']"}}]},{"pwStepLine":32,"gherkinStepLine":54,"keywordType":"Outcome","textWithKeyword":"Then I should see the \"a.item-button[href^='/tickets']\" element","stepMatchArguments":[{"group":{"start":17,"value":"\"a.item-button[href^='/tickets']\"","children":[{"start":18,"value":"a.item-button[href^='/tickets']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":35,"pickleLine":56,"tags":[],"steps":[{"pwStepLine":36,"gherkinStepLine":57,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":58,"keywordType":"Context","textWithKeyword":"And I am on \"/home\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/home\"","children":[{"start":9,"value":"/home","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":59,"keywordType":"Context","textWithKeyword":"And I wait for the element \"a.item-button[href^='/resources/messages']\" to appear","stepMatchArguments":[{"group":{"start":24,"value":"a.item-button[href^='/resources/messages']"}}]},{"pwStepLine":39,"gherkinStepLine":60,"keywordType":"Outcome","textWithKeyword":"Then I should not see the \"a.item-button[href^='/tickets']\" element","stepMatchArguments":[{"group":{"start":21,"value":"\"a.item-button[href^='/tickets']\"","children":[{"start":22,"value":"a.item-button[href^='/tickets']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":42,"pickleLine":62,"tags":[],"steps":[{"pwStepLine":43,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"Given I am a student","stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I am on \"/tickets?project_id=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/tickets?project_id=1\"","children":[{"start":9,"value":"/tickets?project_id=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":66,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Ticket number\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Ticket number\"","children":[{"start":14,"value":"Ticket number","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":67,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":50,"pickleLine":69,"tags":[],"steps":[{"pwStepLine":51,"gherkinStepLine":70,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":71,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/search_settings?keyword=show_link_ticket_notification\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/search_settings?keyword=show_link_ticket_notification\"","children":[{"start":9,"value":"/admin/settings/search_settings?keyword=show_link_ticket_notification","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"And I select \"No\" from \"form_show_link_ticket_notification\"","stepMatchArguments":[{"group":{"start":9,"value":"\"No\"","children":[{"start":10,"value":"No","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":19,"value":"\"form_show_link_ticket_notification\"","children":[{"start":20,"value":"form_show_link_ticket_notification","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":55,"gherkinStepLine":74,"keywordType":"Context","textWithKeyword":"And I press \"Save settings\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save settings\"","children":[{"start":9,"value":"Save settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":56,"gherkinStepLine":75,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":57,"gherkinStepLine":76,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/settings/search_settings?keyword=ticket_project_user_roles\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/settings/search_settings?keyword=ticket_project_user_roles\"","children":[{"start":9,"value":"/admin/settings/search_settings?keyword=ticket_project_user_roles","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":58,"gherkinStepLine":77,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":59,"gherkinStepLine":78,"keywordType":"Context","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":60,"gherkinStepLine":80,"keywordType":"Context","textWithKeyword":"And I press \"Save settings\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save settings\"","children":[{"start":9,"value":"Save settings","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":61,"gherkinStepLine":81,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":62,"gherkinStepLine":82,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end