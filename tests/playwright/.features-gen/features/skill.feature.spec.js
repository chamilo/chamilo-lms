// Generated from: features/skill.feature
import { test } from "playwright-bdd";

test.describe('Skills', () => {

  test('Create a skill skill1', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "main/skills/skill_create.php"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"skill1"}]},{"cells":[{"value":"short_code"},{"value":"s1"}]},{"cells":[{"value":"description"},{"value":"description"}]},{"cells":[{"value":"criteria"},{"value":"criteria"}]}]}}, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "skill1"', null, { page }); 
  });

  test('Create a second level skill', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "main/skills/skill_create.php"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"skill11"}]},{"cells":[{"value":"short_code"},{"value":"s11"}]},{"cells":[{"value":"description"},{"value":"description 11"}]},{"cells":[{"value":"criteria"},{"value":"criteria 11"}]}]}}, { page }); 
    await Then('I select "skill1" from "parent_id"', null, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "skill11"', null, { page }); 
  });

  test('Create a skill skilldis', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "main/skills/skill_create.php"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"skilldis"}]},{"cells":[{"value":"short_code"},{"value":"sdis"}]},{"cells":[{"value":"description"},{"value":"description"}]},{"cells":[{"value":"criteria"},{"value":"criteria"}]}]}}, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "skilldis"', null, { page }); 
  });

  test('Disable a skill skilldis', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "main/skills/skill_list.php"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "skilldis"', null, { page }); 
    await Then('I am on "/main/skills/skill_list.php?id=4&action=disable"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Enable a skill skilldis', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "main/skills/skill_list.php"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "skilldis"', null, { page }); 
    await Then('I am on "/main/skills/skill_list.php?id=4&action=enable"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Update a skill skill1', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "main/skills/skill_list.php"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "skill1"', null, { page }); 
    await Then('I follow "Edit"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"skill1 Edited"}]},{"cells":[{"value":"description"},{"value":"description Edited"}]}]}}, { page }); 
    await And('I press "submit"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Assign skill11 to user 1', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "main/skills/assign.php?user=1"', null, { page }); 
    await When('I select "skill11" from "skill"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"argumentation"},{"value":"argumentation"}]}]}}, { page }); 
    await And('I press "save"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('the URL should contain "myStudents.php"', null, { page }); 
    await And('I should see "s11"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('Reassign skill11 to user 1', async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "main/skills/assign.php?user=1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I select "skill11" from "skill"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"argumentation"},{"value":"argumentation"}]}]}}, { page }); 
    await And('I press "save"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('the URL should contain "assign.php"', null, { page }); 
    await And('I should not see an error', null, { page }); 
  });

  test('View assigned skill skill11 to user 1', async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/badge/3/user/1"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Skill acquired"', null, { page }); 
    await And('I should see "John Doe"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/skill.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":25,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":26,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":27,"keywordType":"Context","textWithKeyword":"And I am on \"main/skills/skill_create.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"main/skills/skill_create.php\"","children":[{"start":9,"value":"main/skills/skill_create.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":28,"keywordType":"Context","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":29,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":11,"gherkinStepLine":34,"keywordType":"Action","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":35,"keywordType":"Action","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":36,"keywordType":"Outcome","textWithKeyword":"Then I should see \"skill1\"","stepMatchArguments":[{"group":{"start":13,"value":"\"skill1\"","children":[{"start":14,"value":"skill1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":16,"pickleLine":38,"tags":[],"steps":[{"pwStepLine":17,"gherkinStepLine":39,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":40,"keywordType":"Context","textWithKeyword":"And I am on \"main/skills/skill_create.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"main/skills/skill_create.php\"","children":[{"start":9,"value":"main/skills/skill_create.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":41,"keywordType":"Context","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":42,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":47,"keywordType":"Outcome","textWithKeyword":"Then I select \"skill1\" from \"parent_id\"","stepMatchArguments":[{"group":{"start":9,"value":"\"skill1\"","children":[{"start":10,"value":"skill1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":23,"value":"\"parent_id\"","children":[{"start":24,"value":"parent_id","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":22,"gherkinStepLine":48,"keywordType":"Outcome","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":49,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":50,"keywordType":"Outcome","textWithKeyword":"Then I should see \"skill11\"","stepMatchArguments":[{"group":{"start":13,"value":"\"skill11\"","children":[{"start":14,"value":"skill11","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":27,"pickleLine":52,"tags":[],"steps":[{"pwStepLine":28,"gherkinStepLine":53,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":29,"gherkinStepLine":54,"keywordType":"Context","textWithKeyword":"And I am on \"main/skills/skill_create.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"main/skills/skill_create.php\"","children":[{"start":9,"value":"main/skills/skill_create.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":30,"gherkinStepLine":55,"keywordType":"Context","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":31,"gherkinStepLine":56,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":61,"keywordType":"Action","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":62,"keywordType":"Action","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":34,"gherkinStepLine":63,"keywordType":"Outcome","textWithKeyword":"Then I should see \"skilldis\"","stepMatchArguments":[{"group":{"start":13,"value":"\"skilldis\"","children":[{"start":14,"value":"skilldis","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":37,"pickleLine":71,"tags":[],"steps":[{"pwStepLine":38,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"And I am on \"main/skills/skill_list.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"main/skills/skill_list.php\"","children":[{"start":9,"value":"main/skills/skill_list.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":40,"gherkinStepLine":74,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":41,"gherkinStepLine":75,"keywordType":"Outcome","textWithKeyword":"Then I should see \"skilldis\"","stepMatchArguments":[{"group":{"start":13,"value":"\"skilldis\"","children":[{"start":14,"value":"skilldis","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":42,"gherkinStepLine":76,"keywordType":"Outcome","textWithKeyword":"Then I am on \"/main/skills/skill_list.php?id=4&action=disable\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/skills/skill_list.php?id=4&action=disable\"","children":[{"start":9,"value":"/main/skills/skill_list.php?id=4&action=disable","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":43,"gherkinStepLine":77,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":44,"gherkinStepLine":78,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":47,"pickleLine":86,"tags":[],"steps":[{"pwStepLine":48,"gherkinStepLine":87,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":49,"gherkinStepLine":88,"keywordType":"Context","textWithKeyword":"And I am on \"main/skills/skill_list.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"main/skills/skill_list.php\"","children":[{"start":9,"value":"main/skills/skill_list.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":50,"gherkinStepLine":89,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":51,"gherkinStepLine":90,"keywordType":"Outcome","textWithKeyword":"Then I should see \"skilldis\"","stepMatchArguments":[{"group":{"start":13,"value":"\"skilldis\"","children":[{"start":14,"value":"skilldis","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":52,"gherkinStepLine":91,"keywordType":"Outcome","textWithKeyword":"Then I am on \"/main/skills/skill_list.php?id=4&action=enable\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/skills/skill_list.php?id=4&action=enable\"","children":[{"start":9,"value":"/main/skills/skill_list.php?id=4&action=enable","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":92,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":93,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":57,"pickleLine":97,"tags":[],"steps":[{"pwStepLine":58,"gherkinStepLine":98,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":59,"gherkinStepLine":99,"keywordType":"Context","textWithKeyword":"And I am on \"main/skills/skill_list.php\"","stepMatchArguments":[{"group":{"start":8,"value":"\"main/skills/skill_list.php\"","children":[{"start":9,"value":"main/skills/skill_list.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":60,"gherkinStepLine":100,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":61,"gherkinStepLine":101,"keywordType":"Outcome","textWithKeyword":"Then I should see \"skill1\"","stepMatchArguments":[{"group":{"start":13,"value":"\"skill1\"","children":[{"start":14,"value":"skill1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":62,"gherkinStepLine":102,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Edit\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Edit\"","children":[{"start":10,"value":"Edit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":63,"gherkinStepLine":103,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":64,"gherkinStepLine":104,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":65,"gherkinStepLine":107,"keywordType":"Action","textWithKeyword":"And I press \"submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"submit\"","children":[{"start":9,"value":"submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":66,"gherkinStepLine":108,"keywordType":"Action","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":67,"gherkinStepLine":109,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":70,"pickleLine":119,"tags":[],"steps":[{"pwStepLine":71,"gherkinStepLine":120,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":72,"gherkinStepLine":121,"keywordType":"Context","textWithKeyword":"And I am on \"main/skills/assign.php?user=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"main/skills/assign.php?user=1\"","children":[{"start":9,"value":"main/skills/assign.php?user=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":73,"gherkinStepLine":122,"keywordType":"Action","textWithKeyword":"When I select \"skill11\" from \"skill\"","stepMatchArguments":[{"group":{"start":9,"value":"\"skill11\"","children":[{"start":10,"value":"skill11","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":24,"value":"\"skill\"","children":[{"start":25,"value":"skill","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":74,"gherkinStepLine":123,"keywordType":"Action","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":75,"gherkinStepLine":124,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:","stepMatchArguments":[]},{"pwStepLine":76,"gherkinStepLine":126,"keywordType":"Outcome","textWithKeyword":"And I press \"save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"save\"","children":[{"start":9,"value":"save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":77,"gherkinStepLine":127,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":78,"gherkinStepLine":128,"keywordType":"Outcome","textWithKeyword":"Then the URL should contain \"myStudents.php\"","stepMatchArguments":[{"group":{"start":23,"value":"\"myStudents.php\"","children":[{"start":24,"value":"myStudents.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":79,"gherkinStepLine":129,"keywordType":"Outcome","textWithKeyword":"And I should see \"s11\"","stepMatchArguments":[{"group":{"start":13,"value":"\"s11\"","children":[{"start":14,"value":"s11","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":80,"gherkinStepLine":130,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":83,"pickleLine":132,"tags":[],"steps":[{"pwStepLine":84,"gherkinStepLine":133,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":85,"gherkinStepLine":134,"keywordType":"Context","textWithKeyword":"And I am on \"main/skills/assign.php?user=1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"main/skills/assign.php?user=1\"","children":[{"start":9,"value":"main/skills/assign.php?user=1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":86,"gherkinStepLine":135,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":87,"gherkinStepLine":136,"keywordType":"Action","textWithKeyword":"When I select \"skill11\" from \"skill\"","stepMatchArguments":[{"group":{"start":9,"value":"\"skill11\"","children":[{"start":10,"value":"skill11","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":24,"value":"\"skill\"","children":[{"start":25,"value":"skill","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":88,"gherkinStepLine":137,"keywordType":"Action","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":89,"gherkinStepLine":138,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:","stepMatchArguments":[]},{"pwStepLine":90,"gherkinStepLine":140,"keywordType":"Outcome","textWithKeyword":"And I press \"save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"save\"","children":[{"start":9,"value":"save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":91,"gherkinStepLine":141,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":92,"gherkinStepLine":142,"keywordType":"Outcome","textWithKeyword":"Then the URL should contain \"assign.php\"","stepMatchArguments":[{"group":{"start":23,"value":"\"assign.php\"","children":[{"start":24,"value":"assign.php","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":93,"gherkinStepLine":143,"keywordType":"Outcome","textWithKeyword":"And I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":96,"pickleLine":145,"tags":[],"steps":[{"pwStepLine":97,"gherkinStepLine":146,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":98,"gherkinStepLine":147,"keywordType":"Context","textWithKeyword":"And I am on \"/badge/3/user/1\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/badge/3/user/1\"","children":[{"start":9,"value":"/badge/3/user/1","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":99,"gherkinStepLine":148,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":100,"gherkinStepLine":149,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Skill acquired\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Skill acquired\"","children":[{"start":14,"value":"Skill acquired","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":101,"gherkinStepLine":150,"keywordType":"Outcome","textWithKeyword":"And I should see \"John Doe\"","stepMatchArguments":[{"group":{"start":13,"value":"\"John Doe\"","children":[{"start":14,"value":"John Doe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end