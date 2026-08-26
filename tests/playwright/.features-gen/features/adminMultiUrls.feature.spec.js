// Generated from: features/adminMultiUrls.feature
import { test } from "playwright-bdd";

test.describe('Multi URLs admin dashboard', () => {

  test('The default access URL is the current site URL', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I set the default access URL to the current site URL', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see the current access URL', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('The dashboard lists at least one URL', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Multi URLs"', null, { page }); 
    await And('I should not see "No results found"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Administrators are attributed per URL', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Administrators"', null, { page }); 
    await And('I should see "John Doe"', null, { page }); 
  });

  test('General information panel is visible', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "General information"', null, { page }); 
    await And('I should see "Installed version"', null, { page }); 
    await And('I should see "PHP version"', null, { page }); 
  });

  test('Logins chart is visible with a date range selector', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Logins"', null, { page }); 
    await And('I should see "From"', null, { page }); 
    await And('I should see "To"', null, { page }); 
  });

  test('Reachable from the admin panel', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I follow "Multi URLs"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('the URL should contain "/admin/urls"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('A teacher cannot access the dashboard', async ({ Given, Then, And, page }) => { 
    await Given('I am a teacher', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see "General information"', null, { page }); 
  });

  test('The legacy CRUD is reachable from the dashboard', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I follow "Configure multiple access URL"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('User directory shows each user\'s URL attribution', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "User directory"', null, { page }); 
    await And('I should see "Username"', null, { page }); 
    await And('I should see "URLs"', null, { page }); 
  });

  test('Course directory shows each course\'s URL distribution', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Course directory"', null, { page }); 
    await And('I should see "Code"', null, { page }); 
  });

  test('A user\'s info modal shows their email and URL attribution', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Email"', null, { page }); 
    await And('I fill in "usersSearch" with "admin"', null, { page }); 
    await And('I press "Search"', null, { page }); 
    await Then('I should see "admin@example.com"', null, { page }); 
    await And('I click the "button[aria-label=\'Information\']" icon in the row for "John Doe"', null, { page }); 
    await Then('I should see "User details"', null, { page }); 
    await And('I should see "admin@example.com"', null, { page }); 
  });

  test('A course\'s info modal shows its URL distribution', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I click the "button[aria-label=\'Information\']" icon in the row for "AIACT"', null, { page }); 
    await Then('I should see "Course details"', null, { page }); 
    await And('I should see "AIACT"', null, { page }); 
  });

  test('The View details icon opens the per-URL user detail page', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I fill in "usersSearch" with "admin"', null, { page }); 
    await And('I press "Search"', null, { page }); 
    await And('I click the "a[title=\'View details\']" icon in the row for "John Doe"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('the URL should contain "/admin/urls/users/"', null, { page }); 
    await And('I should see "User details"', null, { page }); 
    await And('I should see "John Doe"', null, { page }); 
    await And('I should see the current access URL', null, { page }); 
    await And('I should see "AI Act"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('The View details icon opens the per-course detail page', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/admin/urls"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await And('I click the "a[title=\'View details\']" icon in the row for "AIACT"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('the URL should contain "/admin/urls/courses/"', null, { page }); 
    await And('I should see "Course details"', null, { page }); 
    await And('I should see "AI Act"', null, { page }); 
    await And('I should see the current access URL', null, { page }); 
    await And('I should see "Direct enrollment belongs to the course as a whole"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('A teacher cannot access the course detail page', async ({ Given, Then, And, page }) => { 
    await Given('I am a teacher', null, { page }); 
    await And('I am on "/admin/urls/courses/1"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see "Course details"', null, { page }); 
  });

  test('A teacher cannot access the user detail page', async ({ Given, Then, And, page }) => { 
    await Given('I am a teacher', null, { page }); 
    await And('I am on "/admin/urls/users/1"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see "User details"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/adminMultiUrls.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":18,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":19,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":20,"keywordType":"Context","textWithKeyword":"And I set the default access URL to the current site URL","stepMatchArguments":[]},{"pwStepLine":9,"gherkinStepLine":21,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":10,"gherkinStepLine":22,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":23,"keywordType":"Outcome","textWithKeyword":"Then I should see the current access URL","stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":24,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":15,"pickleLine":26,"tags":[],"steps":[{"pwStepLine":16,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":17,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":29,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":19,"gherkinStepLine":30,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Multi URLs\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Multi URLs\"","children":[{"start":14,"value":"Multi URLs","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":31,"keywordType":"Outcome","textWithKeyword":"And I should not see \"No results found\"","stepMatchArguments":[{"group":{"start":17,"value":"\"No results found\"","children":[{"start":18,"value":"No results found","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":32,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":24,"pickleLine":34,"tags":[],"steps":[{"pwStepLine":25,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":44,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Administrators\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Administrators\"","children":[{"start":14,"value":"Administrators","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":45,"keywordType":"Outcome","textWithKeyword":"And I should see \"John Doe\"","stepMatchArguments":[{"group":{"start":13,"value":"\"John Doe\"","children":[{"start":14,"value":"John Doe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":32,"pickleLine":47,"tags":[],"steps":[{"pwStepLine":33,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":34,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":35,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":51,"keywordType":"Outcome","textWithKeyword":"Then I should see \"General information\"","stepMatchArguments":[{"group":{"start":13,"value":"\"General information\"","children":[{"start":14,"value":"General information","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":37,"gherkinStepLine":52,"keywordType":"Outcome","textWithKeyword":"And I should see \"Installed version\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Installed version\"","children":[{"start":14,"value":"Installed version","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":53,"keywordType":"Outcome","textWithKeyword":"And I should see \"PHP version\"","stepMatchArguments":[{"group":{"start":13,"value":"\"PHP version\"","children":[{"start":14,"value":"PHP version","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":41,"pickleLine":55,"tags":[],"steps":[{"pwStepLine":42,"gherkinStepLine":60,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":61,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":62,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":45,"gherkinStepLine":63,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Logins\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Logins\"","children":[{"start":14,"value":"Logins","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":46,"gherkinStepLine":64,"keywordType":"Outcome","textWithKeyword":"And I should see \"From\"","stepMatchArguments":[{"group":{"start":13,"value":"\"From\"","children":[{"start":14,"value":"From","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":65,"keywordType":"Outcome","textWithKeyword":"And I should see \"To\"","stepMatchArguments":[{"group":{"start":13,"value":"\"To\"","children":[{"start":14,"value":"To","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":50,"pickleLine":67,"tags":[],"steps":[{"pwStepLine":51,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":52,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I am on \"/admin\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin\"","children":[{"start":9,"value":"/admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":70,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":71,"keywordType":"Context","textWithKeyword":"And I follow \"Multi URLs\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Multi URLs\"","children":[{"start":10,"value":"Multi URLs","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":55,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":56,"gherkinStepLine":73,"keywordType":"Outcome","textWithKeyword":"Then the URL should contain \"/admin/urls\"","stepMatchArguments":[{"group":{"start":23,"value":"\"/admin/urls\"","children":[{"start":24,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":57,"gherkinStepLine":74,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":60,"pickleLine":76,"tags":[],"steps":[{"pwStepLine":61,"gherkinStepLine":77,"keywordType":"Context","textWithKeyword":"Given I am a teacher","stepMatchArguments":[]},{"pwStepLine":62,"gherkinStepLine":78,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":63,"gherkinStepLine":79,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":64,"gherkinStepLine":80,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"General information\"","stepMatchArguments":[{"group":{"start":17,"value":"\"General information\"","children":[{"start":18,"value":"General information","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":67,"pickleLine":82,"tags":[],"steps":[{"pwStepLine":68,"gherkinStepLine":86,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":69,"gherkinStepLine":87,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":70,"gherkinStepLine":88,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":71,"gherkinStepLine":89,"keywordType":"Context","textWithKeyword":"And I follow \"Configure multiple access URL\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Configure multiple access URL\"","children":[{"start":10,"value":"Configure multiple access URL","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":72,"gherkinStepLine":90,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":73,"gherkinStepLine":91,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":76,"pickleLine":93,"tags":[],"steps":[{"pwStepLine":77,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":78,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":79,"gherkinStepLine":100,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":80,"gherkinStepLine":101,"keywordType":"Outcome","textWithKeyword":"Then I should see \"User directory\"","stepMatchArguments":[{"group":{"start":13,"value":"\"User directory\"","children":[{"start":14,"value":"User directory","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":81,"gherkinStepLine":102,"keywordType":"Outcome","textWithKeyword":"And I should see \"Username\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Username\"","children":[{"start":14,"value":"Username","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":82,"gherkinStepLine":103,"keywordType":"Outcome","textWithKeyword":"And I should see \"URLs\"","stepMatchArguments":[{"group":{"start":13,"value":"\"URLs\"","children":[{"start":14,"value":"URLs","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":85,"pickleLine":105,"tags":[],"steps":[{"pwStepLine":86,"gherkinStepLine":109,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":87,"gherkinStepLine":110,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":88,"gherkinStepLine":111,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":89,"gherkinStepLine":112,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Course directory\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course directory\"","children":[{"start":14,"value":"Course directory","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":90,"gherkinStepLine":113,"keywordType":"Outcome","textWithKeyword":"And I should see \"Code\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Code\"","children":[{"start":14,"value":"Code","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":93,"pickleLine":115,"tags":[],"steps":[{"pwStepLine":94,"gherkinStepLine":125,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":95,"gherkinStepLine":126,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":96,"gherkinStepLine":127,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":97,"gherkinStepLine":128,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Email\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Email\"","children":[{"start":14,"value":"Email","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":98,"gherkinStepLine":129,"keywordType":"Outcome","textWithKeyword":"And I fill in \"usersSearch\" with \"admin\"","stepMatchArguments":[{"group":{"start":10,"value":"\"usersSearch\"","children":[{"start":11,"value":"usersSearch","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":29,"value":"\"admin\"","children":[{"start":30,"value":"admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":99,"gherkinStepLine":130,"keywordType":"Outcome","textWithKeyword":"And I press \"Search\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Search\"","children":[{"start":9,"value":"Search","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":100,"gherkinStepLine":131,"keywordType":"Outcome","textWithKeyword":"Then I should see \"admin@example.com\"","stepMatchArguments":[{"group":{"start":13,"value":"\"admin@example.com\"","children":[{"start":14,"value":"admin@example.com","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":101,"gherkinStepLine":132,"keywordType":"Outcome","textWithKeyword":"And I click the \"button[aria-label='Information']\" icon in the row for \"John Doe\"","stepMatchArguments":[{"group":{"start":12,"value":"\"button[aria-label='Information']\"","children":[{"start":13,"value":"button[aria-label='Information']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":67,"value":"\"John Doe\"","children":[{"start":68,"value":"John Doe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":102,"gherkinStepLine":133,"keywordType":"Outcome","textWithKeyword":"Then I should see \"User details\"","stepMatchArguments":[{"group":{"start":13,"value":"\"User details\"","children":[{"start":14,"value":"User details","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":103,"gherkinStepLine":134,"keywordType":"Outcome","textWithKeyword":"And I should see \"admin@example.com\"","stepMatchArguments":[{"group":{"start":13,"value":"\"admin@example.com\"","children":[{"start":14,"value":"admin@example.com","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":106,"pickleLine":136,"tags":[],"steps":[{"pwStepLine":107,"gherkinStepLine":137,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":108,"gherkinStepLine":138,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":109,"gherkinStepLine":139,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":110,"gherkinStepLine":140,"keywordType":"Context","textWithKeyword":"And I click the \"button[aria-label='Information']\" icon in the row for \"AIACT\"","stepMatchArguments":[{"group":{"start":12,"value":"\"button[aria-label='Information']\"","children":[{"start":13,"value":"button[aria-label='Information']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":67,"value":"\"AIACT\"","children":[{"start":68,"value":"AIACT","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":111,"gherkinStepLine":141,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Course details\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course details\"","children":[{"start":14,"value":"Course details","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":112,"gherkinStepLine":142,"keywordType":"Outcome","textWithKeyword":"And I should see \"AIACT\"","stepMatchArguments":[{"group":{"start":13,"value":"\"AIACT\"","children":[{"start":14,"value":"AIACT","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":115,"pickleLine":144,"tags":[],"steps":[{"pwStepLine":116,"gherkinStepLine":149,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":117,"gherkinStepLine":150,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":118,"gherkinStepLine":151,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":119,"gherkinStepLine":152,"keywordType":"Context","textWithKeyword":"And I fill in \"usersSearch\" with \"admin\"","stepMatchArguments":[{"group":{"start":10,"value":"\"usersSearch\"","children":[{"start":11,"value":"usersSearch","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":29,"value":"\"admin\"","children":[{"start":30,"value":"admin","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":120,"gherkinStepLine":153,"keywordType":"Context","textWithKeyword":"And I press \"Search\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Search\"","children":[{"start":9,"value":"Search","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":121,"gherkinStepLine":154,"keywordType":"Context","textWithKeyword":"And I click the \"a[title='View details']\" icon in the row for \"John Doe\"","stepMatchArguments":[{"group":{"start":12,"value":"\"a[title='View details']\"","children":[{"start":13,"value":"a[title='View details']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":58,"value":"\"John Doe\"","children":[{"start":59,"value":"John Doe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":122,"gherkinStepLine":155,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":123,"gherkinStepLine":156,"keywordType":"Outcome","textWithKeyword":"Then the URL should contain \"/admin/urls/users/\"","stepMatchArguments":[{"group":{"start":23,"value":"\"/admin/urls/users/\"","children":[{"start":24,"value":"/admin/urls/users/","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":124,"gherkinStepLine":157,"keywordType":"Outcome","textWithKeyword":"And I should see \"User details\"","stepMatchArguments":[{"group":{"start":13,"value":"\"User details\"","children":[{"start":14,"value":"User details","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":125,"gherkinStepLine":158,"keywordType":"Outcome","textWithKeyword":"And I should see \"John Doe\"","stepMatchArguments":[{"group":{"start":13,"value":"\"John Doe\"","children":[{"start":14,"value":"John Doe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":126,"gherkinStepLine":159,"keywordType":"Outcome","textWithKeyword":"And I should see the current access URL","stepMatchArguments":[]},{"pwStepLine":127,"gherkinStepLine":160,"keywordType":"Outcome","textWithKeyword":"And I should see \"AI Act\"","stepMatchArguments":[{"group":{"start":13,"value":"\"AI Act\"","children":[{"start":14,"value":"AI Act","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":128,"gherkinStepLine":161,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":131,"pickleLine":163,"tags":[],"steps":[{"pwStepLine":132,"gherkinStepLine":164,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":133,"gherkinStepLine":165,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls\"","children":[{"start":9,"value":"/admin/urls","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":134,"gherkinStepLine":166,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":135,"gherkinStepLine":167,"keywordType":"Context","textWithKeyword":"And I click the \"a[title='View details']\" icon in the row for \"AIACT\"","stepMatchArguments":[{"group":{"start":12,"value":"\"a[title='View details']\"","children":[{"start":13,"value":"a[title='View details']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":58,"value":"\"AIACT\"","children":[{"start":59,"value":"AIACT","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":136,"gherkinStepLine":168,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":137,"gherkinStepLine":169,"keywordType":"Outcome","textWithKeyword":"Then the URL should contain \"/admin/urls/courses/\"","stepMatchArguments":[{"group":{"start":23,"value":"\"/admin/urls/courses/\"","children":[{"start":24,"value":"/admin/urls/courses/","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":138,"gherkinStepLine":170,"keywordType":"Outcome","textWithKeyword":"And I should see \"Course details\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Course details\"","children":[{"start":14,"value":"Course details","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":139,"gherkinStepLine":171,"keywordType":"Outcome","textWithKeyword":"And I should see \"AI Act\"","stepMatchArguments":[{"group":{"start":13,"value":"\"AI Act\"","children":[{"start":14,"value":"AI Act","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":140,"gherkinStepLine":172,"keywordType":"Outcome","textWithKeyword":"And I should see the current access URL","stepMatchArguments":[]},{"pwStepLine":141,"gherkinStepLine":173,"keywordType":"Outcome","textWithKeyword":"And I should see \"Direct enrollment belongs to the course as a whole\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Direct enrollment belongs to the course as a whole\"","children":[{"start":14,"value":"Direct enrollment belongs to the course as a whole","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":142,"gherkinStepLine":174,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":145,"pickleLine":176,"tags":[],"steps":[{"pwStepLine":146,"gherkinStepLine":177,"keywordType":"Context","textWithKeyword":"Given I am a teacher","stepMatchArguments":[]},{"pwStepLine":147,"gherkinStepLine":178,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls/courses/1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls/courses/1\"","children":[{"start":9,"value":"/admin/urls/courses/1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":148,"gherkinStepLine":179,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":149,"gherkinStepLine":180,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Course details\"","stepMatchArguments":[{"group":{"start":17,"value":"\"Course details\"","children":[{"start":18,"value":"Course details","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":152,"pickleLine":182,"tags":[],"steps":[{"pwStepLine":153,"gherkinStepLine":183,"keywordType":"Context","textWithKeyword":"Given I am a teacher","stepMatchArguments":[]},{"pwStepLine":154,"gherkinStepLine":184,"keywordType":"Context","textWithKeyword":"And I am on \"/admin/urls/users/1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/admin/urls/users/1\"","children":[{"start":9,"value":"/admin/urls/users/1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":155,"gherkinStepLine":185,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":156,"gherkinStepLine":186,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"User details\"","stepMatchArguments":[{"group":{"start":17,"value":"\"User details\"","children":[{"start":18,"value":"User details","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end