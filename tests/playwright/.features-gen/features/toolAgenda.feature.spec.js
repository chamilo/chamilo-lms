// Generated from: features/toolAgenda.feature
import { test } from "playwright-bdd";

test.describe('Agenda tool', () => {

  test.beforeEach('Background', async ({ Given, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
  });
  
  test('Create a personal event', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/calendar/agenda.php?action=add&type=personal"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Event 1"}]}]}}, { page }); 
    await And('I focus "date_range"', null, { page }); 
    await And('I fill in "date_range" with "2017-03-07 12:15 / 2017-03-07 12:15"', null, { page }); 
    await Then('I fill in editor field "content" with "Description event"', null, { page }); 
    await And('I press "Add event"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Create an event inside course TEMP', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/main/calendar/agenda.php?action=add&type=course&cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"Event in course"}]}]}}, { page }); 
    await Then('I fill in editor field "content" with "Description event"', null, { page }); 
    await Then('I wait for the page to be loaded', null, { page }); 
    await And('I focus "date_range"', null, { page }); 
    await And('I fill in "date_range" with "2017-03-07 12:15 / 2017-03-07 12:15"', null, { page }); 
    await And('I press "Add event"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Create a personal event from the general agenda', async ({ Given, When, Then, And, page }) => { 
    await Given('I am on "/resources/ccalendarevent"', null, { page }); 
    await When('I press "Add event"', null, { page }); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"Title"},{"value":"Personal event from general agenda"}]}]}}, { page }); 
    await And('I fill in tinymce field "calendar-event-content" with "Content for personal event from general agenda"', null, { page }); 
    await And('I press "Add"', null, { page }); 
    await Then('I should see "Personal event from general agenda"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolAgenda.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":10,"pickleLine":50,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/calendar/agenda.php?action=add&type=personal\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/calendar/agenda.php?action=add&type=personal\"","children":[{"start":9,"value":"/main/calendar/agenda.php?action=add&type=personal","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":52,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":53,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":55,"keywordType":"Action","textWithKeyword":"And I focus \"date_range\"","stepMatchArguments":[{"group":{"start":9,"value":"date_range"}}]},{"pwStepLine":15,"gherkinStepLine":56,"keywordType":"Action","textWithKeyword":"And I fill in \"date_range\" with \"2017-03-07 12:15 / 2017-03-07 12:15\"","stepMatchArguments":[{"group":{"start":10,"value":"\"date_range\"","children":[{"start":11,"value":"date_range","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":28,"value":"\"2017-03-07 12:15 / 2017-03-07 12:15\"","children":[{"start":29,"value":"2017-03-07 12:15 / 2017-03-07 12:15","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":57,"keywordType":"Outcome","textWithKeyword":"Then I fill in editor field \"content\" with \"Description event\"","stepMatchArguments":[{"group":{"start":23,"value":"\"content\"","children":[{"start":24,"value":"content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":38,"value":"\"Description event\"","children":[{"start":39,"value":"Description event","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":58,"keywordType":"Outcome","textWithKeyword":"And I press \"Add event\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add event\"","children":[{"start":9,"value":"Add event","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":59,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":60,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":22,"pickleLine":62,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":23,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"Given I am on \"/main/calendar/agenda.php?action=add&type=course&cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/calendar/agenda.php?action=add&type=course&cid=3\"","children":[{"start":9,"value":"/main/calendar/agenda.php?action=add&type=course&cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":65,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":67,"keywordType":"Outcome","textWithKeyword":"Then I fill in editor field \"content\" with \"Description event\"","stepMatchArguments":[{"group":{"start":23,"value":"\"content\"","children":[{"start":24,"value":"content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":38,"value":"\"Description event\"","children":[{"start":39,"value":"Description event","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":68,"keywordType":"Outcome","textWithKeyword":"Then I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":69,"keywordType":"Outcome","textWithKeyword":"And I focus \"date_range\"","stepMatchArguments":[{"group":{"start":9,"value":"date_range"}}]},{"pwStepLine":29,"gherkinStepLine":70,"keywordType":"Outcome","textWithKeyword":"And I fill in \"date_range\" with \"2017-03-07 12:15 / 2017-03-07 12:15\"","stepMatchArguments":[{"group":{"start":10,"value":"\"date_range\"","children":[{"start":11,"value":"date_range","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":28,"value":"\"2017-03-07 12:15 / 2017-03-07 12:15\"","children":[{"start":29,"value":"2017-03-07 12:15 / 2017-03-07 12:15","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":71,"keywordType":"Outcome","textWithKeyword":"And I press \"Add event\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add event\"","children":[{"start":9,"value":"Add event","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":72,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":73,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":35,"pickleLine":75,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":76,"keywordType":"Context","textWithKeyword":"Given I am on \"/resources/ccalendarevent\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/resources/ccalendarevent\"","children":[{"start":9,"value":"/resources/ccalendarevent","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":77,"keywordType":"Action","textWithKeyword":"When I press \"Add event\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add event\"","children":[{"start":9,"value":"Add event","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":78,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":80,"keywordType":"Outcome","textWithKeyword":"And I fill in tinymce field \"calendar-event-content\" with \"Content for personal event from general agenda\"","stepMatchArguments":[{"group":{"start":24,"value":"\"calendar-event-content\"","children":[{"start":25,"value":"calendar-event-content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":54,"value":"\"Content for personal event from general agenda\"","children":[{"start":55,"value":"Content for personal event from general agenda","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":81,"keywordType":"Outcome","textWithKeyword":"And I press \"Add\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add\"","children":[{"start":9,"value":"Add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":82,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Personal event from general agenda\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Personal event from general agenda\"","children":[{"start":14,"value":"Personal event from general agenda","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end