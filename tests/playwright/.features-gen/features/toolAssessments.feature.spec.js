// Generated from: features/toolAssessments.feature
import { test } from "playwright-bdd";

test.describe('Assessments tool', () => {

  test('Subscribe a learner so the assessment tool has someone to grade', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on "/main/user/subscribe_user.php?keyword=norizales&type=5&cid=3"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Noa"', null, { page }); 
    await Then('I follow "Register"', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test('Set certification minimum score to 50 in course TEMP', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assessments"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "Minimum certification score"', null, { page }); 
    await Then('I press "Edit"', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"gradebook-category-certificate-min-score"},{"value":"50"}]}]}}, { page }); 
    await And('I check "Generate certificates"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "50"', null, { page }); 
  });

  test('Create an evaluation "exam" in course TEMP', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assessments"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "Minimum certification score"', null, { page }); 
    await Then('I press "Add classroom activity"', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"gradebook-evaluation-title"},{"value":"exam"}]},{"cells":[{"value":"gradebook-evaluation-weight"},{"value":"90"}]},{"cells":[{"value":"gradebook-evaluation-max-score"},{"value":"10"}]},{"cells":[{"value":"gradebook-evaluation-min-score"},{"value":"3"}]}]}}, { page }); 
    await And('I check "Grade learners"', null, { page }); 
    await And('I press "Add classroom activity"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await When('I fill in the score for "norizales" with "6"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "exam"', null, { page }); 
  });

  test.skip('Link an Assignment to the evaluation and edit its min score', { tag: ['@common', '@tools', '@skip'] }, async ({ Given, When, Then, And }) => { 
    await Given('I am a platform administrator'); 
    await And('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow "Assignments"'); 
    await And('I wait for the page content to settle'); 
    await Then('I press "Create Assignment"'); 
    await And('wait for the page to be loaded'); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"Assignment name"},{"value":"Assessment Link Work"}]}]}}); 
    await And('I fill in the active tinymce editor with "Link target for the Assessments tool"'); 
    await And('I press "Save"'); 
    await And('I wait for the page content to settle'); 
    await Then('I should see "Assignment created"'); 
    await And('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow "Assessments"'); 
    await And('wait for the page to be loaded when ready'); 
    await Then('I click the "i.mdi-link-plus" element'); 
    await And('I wait for the page to be loaded'); 
    await When('I select "Assignments" from "create_link_select_link"'); 
    await And('wait for the page to be loaded when ready'); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"weight_mask"},{"value":"10"}]},{"cells":[{"value":"min_score"},{"value":"2"}]}]}}); 
    await And('I press "add_link_submit"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "The link has been added"'); 
    await Then('I click the "[title=\'Edit weight\']" icon in the row for "Assessment Link Work"'); 
    await And('I wait for the page to be loaded'); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"min_score"},{"value":"3"}]}]}}); 
    await And('I press "edit_link_form_submit"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "Assessment edited"'); 
  });

  test.skip('Edit a result and verify it in chart view', { tag: ['@common', '@tools', '@skip'] }, async ({ Given, When, Then, And }) => { 
    await Given('I am a platform administrator'); 
    await And('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow "Assessments"'); 
    await And('wait for the page to be loaded when ready'); 
    await And('I follow "exam"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I click the "i.mdi-pencil" icon in the row for "Orizales"'); 
    await And('I wait for the page to be loaded'); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"score"},{"value":"8"}]}]}}); 
    await And('I press "edit_result_form_submit"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I follow "Assessments"'); 
    await And('I wait for the page to be loaded'); 
    await And('I click the "i.mdi-chart-box" element'); 
    await And('wait for the page to be loaded when ready'); 
    await Then('I should see "8 / 10"'); 
  });

  test('Open certificate from list view in Assessments', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assessments"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "Minimum certification score"', null, { page }); 
    await Then('I press "Certificate"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I press "Generate"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "Noa Orizales"', null, { page }); 
    await And('I should see "Certificate"', null, { page }); 
  });

  test('Admin exports all to PDF', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assessments"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "Minimum certification score"', null, { page }); 
    await Then('I press "Students list report"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await And('I press "Export to PDF"', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

  test.skip('Deletes selected assessments', { tag: ['@common', '@tools', '@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I am a platform administrator'); 
    await And('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow "Assessments"'); 
    await And('wait for the page to be loaded when ready'); 
    await Then('I follow "Select all"'); 
    await And('I press "Action"'); 
    await And('I click the "span:has-text(\'Delete selected\')" element'); 
    await And('wait for the page to be loaded when ready'); 
    await Then('I should see "Deleted"'); 
    await And('I should not see "exam"'); 
    await And('I should not see "Assessment Link Work"'); 
    await And('I should not see an error'); 
  });

  test.skip('Admin deletes the dedicated assignment created for the link scenario', { tag: ['@common', '@tools', '@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I am a platform administrator'); 
    await And('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow "Assignments"'); 
    await And('wait for the page to be loaded when ready'); 
    await Then('I should see "Assessment Link Work"'); 
    await And('I click the "input.p-checkbox-input" icon in the row for "Assessment Link Work"'); 
    await And('I press "Delete selected"'); 
    await And('I press "Yes"'); 
    await And('wait for the page to be loaded when ready'); 
    await Then('I should not see "Assessment Link Work"'); 
    await And('I should not see an error'); 
  });

  test('Reset certification minimum score and unsubscribe the learner', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assessments"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "Minimum certification score"', null, { page }); 
    await Then('I press "Edit"', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"gradebook-category-certificate-min-score"},{"value":"75"}]}]}}, { page }); 
    await And('I uncheck "Generate certificates"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "75"', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Users"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should see "Orizales"', null, { page }); 
    await And('I click the "button[title=\'Unsubscribe\']" icon in the row for "Orizales"', null, { page }); 
    await And('I press "Yes"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await Then('I should not see an error', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolAssessments.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":6,"pickleLine":217,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":218,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":219,"keywordType":"Context","textWithKeyword":"And I am on \"/main/user/subscribe_user.php?keyword=norizales&type=5&cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/subscribe_user.php?keyword=norizales&type=5&cid=3\"","children":[{"start":9,"value":"/main/user/subscribe_user.php?keyword=norizales&type=5&cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":220,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":221,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Noa\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Noa\"","children":[{"start":14,"value":"Noa","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":222,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Register\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Register\"","children":[{"start":10,"value":"Register","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":223,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":224,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":16,"pickleLine":226,"tags":["@common","@tools"],"steps":[{"pwStepLine":17,"gherkinStepLine":227,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":228,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":229,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":230,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":231,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":232,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Minimum certification score\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Minimum certification score\"","children":[{"start":14,"value":"Minimum certification score","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":233,"keywordType":"Outcome","textWithKeyword":"Then I press \"Edit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Edit\"","children":[{"start":9,"value":"Edit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":24,"gherkinStepLine":234,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":236,"keywordType":"Action","textWithKeyword":"And I check \"Generate certificates\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Generate certificates\"","children":[{"start":9,"value":"Generate certificates","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":237,"keywordType":"Action","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":238,"keywordType":"Action","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":239,"keywordType":"Outcome","textWithKeyword":"Then I should see \"50\"","stepMatchArguments":[{"group":{"start":13,"value":"\"50\"","children":[{"start":14,"value":"50","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":31,"pickleLine":241,"tags":["@common","@tools"],"steps":[{"pwStepLine":32,"gherkinStepLine":242,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":243,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":244,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":245,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":36,"gherkinStepLine":246,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":247,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Minimum certification score\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Minimum certification score\"","children":[{"start":14,"value":"Minimum certification score","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":248,"keywordType":"Outcome","textWithKeyword":"Then I press \"Add classroom activity\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add classroom activity\"","children":[{"start":9,"value":"Add classroom activity","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":39,"gherkinStepLine":249,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":254,"keywordType":"Action","textWithKeyword":"And I check \"Grade learners\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Grade learners\"","children":[{"start":9,"value":"Grade learners","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":255,"keywordType":"Action","textWithKeyword":"And I press \"Add classroom activity\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add classroom activity\"","children":[{"start":9,"value":"Add classroom activity","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":42,"gherkinStepLine":256,"keywordType":"Action","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":257,"keywordType":"Action","textWithKeyword":"When I fill in the score for \"norizales\" with \"6\"","stepMatchArguments":[{"group":{"start":24,"value":"\"norizales\"","children":[{"start":25,"value":"norizales","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":41,"value":"\"6\"","children":[{"start":42,"value":"6","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":258,"keywordType":"Action","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":259,"keywordType":"Action","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":260,"keywordType":"Outcome","textWithKeyword":"Then I should see \"exam\"","stepMatchArguments":[{"group":{"start":13,"value":"\"exam\"","children":[{"start":14,"value":"exam","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":49,"pickleLine":268,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":50,"gherkinStepLine":269,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator"},{"pwStepLine":51,"gherkinStepLine":270,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":52,"gherkinStepLine":271,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":53,"gherkinStepLine":272,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\""},{"pwStepLine":54,"gherkinStepLine":273,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle"},{"pwStepLine":55,"gherkinStepLine":274,"keywordType":"Outcome","textWithKeyword":"Then I press \"Create Assignment\""},{"pwStepLine":56,"gherkinStepLine":275,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":57,"gherkinStepLine":276,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":58,"gherkinStepLine":278,"keywordType":"Action","textWithKeyword":"And I fill in the active tinymce editor with \"Link target for the Assessments tool\""},{"pwStepLine":59,"gherkinStepLine":279,"keywordType":"Action","textWithKeyword":"And I press \"Save\""},{"pwStepLine":60,"gherkinStepLine":280,"keywordType":"Action","textWithKeyword":"And I wait for the page content to settle"},{"pwStepLine":61,"gherkinStepLine":281,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Assignment created\""},{"pwStepLine":62,"gherkinStepLine":282,"keywordType":"Outcome","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":63,"gherkinStepLine":283,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":64,"gherkinStepLine":284,"keywordType":"Outcome","textWithKeyword":"And I follow \"Assessments\""},{"pwStepLine":65,"gherkinStepLine":285,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":66,"gherkinStepLine":286,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-link-plus\" element"},{"pwStepLine":67,"gherkinStepLine":287,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":68,"gherkinStepLine":288,"keywordType":"Action","textWithKeyword":"When I select \"Assignments\" from \"create_link_select_link\""},{"pwStepLine":69,"gherkinStepLine":289,"keywordType":"Action","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":70,"gherkinStepLine":290,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":71,"gherkinStepLine":293,"keywordType":"Action","textWithKeyword":"And I press \"add_link_submit\""},{"pwStepLine":72,"gherkinStepLine":294,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":73,"gherkinStepLine":295,"keywordType":"Outcome","textWithKeyword":"Then I should see \"The link has been added\""},{"pwStepLine":74,"gherkinStepLine":296,"keywordType":"Outcome","textWithKeyword":"Then I click the \"[title='Edit weight']\" icon in the row for \"Assessment Link Work\""},{"pwStepLine":75,"gherkinStepLine":297,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":76,"gherkinStepLine":298,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":77,"gherkinStepLine":300,"keywordType":"Action","textWithKeyword":"And I press \"edit_link_form_submit\""},{"pwStepLine":78,"gherkinStepLine":301,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":79,"gherkinStepLine":302,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Assessment edited\""}]},
  {"pwTestLine":82,"pickleLine":311,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":83,"gherkinStepLine":312,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator"},{"pwStepLine":84,"gherkinStepLine":313,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":85,"gherkinStepLine":314,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":86,"gherkinStepLine":315,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\""},{"pwStepLine":87,"gherkinStepLine":316,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":88,"gherkinStepLine":317,"keywordType":"Context","textWithKeyword":"And I follow \"exam\""},{"pwStepLine":89,"gherkinStepLine":318,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":90,"gherkinStepLine":319,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-pencil\" icon in the row for \"Orizales\""},{"pwStepLine":91,"gherkinStepLine":320,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":92,"gherkinStepLine":321,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":93,"gherkinStepLine":323,"keywordType":"Action","textWithKeyword":"And I press \"edit_result_form_submit\""},{"pwStepLine":94,"gherkinStepLine":324,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":95,"gherkinStepLine":325,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Assessments\""},{"pwStepLine":96,"gherkinStepLine":326,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":97,"gherkinStepLine":327,"keywordType":"Outcome","textWithKeyword":"And I click the \"i.mdi-chart-box\" element"},{"pwStepLine":98,"gherkinStepLine":328,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":99,"gherkinStepLine":329,"keywordType":"Outcome","textWithKeyword":"Then I should see \"8 / 10\""}]},
  {"pwTestLine":102,"pickleLine":331,"tags":["@common","@tools"],"steps":[{"pwStepLine":103,"gherkinStepLine":332,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":104,"gherkinStepLine":333,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":105,"gherkinStepLine":334,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":106,"gherkinStepLine":335,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":107,"gherkinStepLine":336,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":108,"gherkinStepLine":337,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Minimum certification score\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Minimum certification score\"","children":[{"start":14,"value":"Minimum certification score","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":109,"gherkinStepLine":338,"keywordType":"Outcome","textWithKeyword":"Then I press \"Certificate\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Certificate\"","children":[{"start":9,"value":"Certificate","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":110,"gherkinStepLine":339,"keywordType":"Outcome","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":111,"gherkinStepLine":340,"keywordType":"Outcome","textWithKeyword":"Then I press \"Generate\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Generate\"","children":[{"start":9,"value":"Generate","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":112,"gherkinStepLine":341,"keywordType":"Outcome","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":113,"gherkinStepLine":342,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Noa Orizales\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Noa Orizales\"","children":[{"start":14,"value":"Noa Orizales","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":114,"gherkinStepLine":343,"keywordType":"Outcome","textWithKeyword":"And I should see \"Certificate\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Certificate\"","children":[{"start":14,"value":"Certificate","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":117,"pickleLine":345,"tags":["@common","@tools"],"steps":[{"pwStepLine":118,"gherkinStepLine":346,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":119,"gherkinStepLine":347,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":120,"gherkinStepLine":348,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":121,"gherkinStepLine":349,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":122,"gherkinStepLine":350,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":123,"gherkinStepLine":351,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Minimum certification score\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Minimum certification score\"","children":[{"start":14,"value":"Minimum certification score","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":124,"gherkinStepLine":352,"keywordType":"Outcome","textWithKeyword":"Then I press \"Students list report\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Students list report\"","children":[{"start":9,"value":"Students list report","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":125,"gherkinStepLine":353,"keywordType":"Outcome","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":126,"gherkinStepLine":354,"keywordType":"Outcome","textWithKeyword":"And I press \"Export to PDF\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Export to PDF\"","children":[{"start":9,"value":"Export to PDF","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":127,"gherkinStepLine":355,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":130,"pickleLine":366,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":131,"gherkinStepLine":367,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator"},{"pwStepLine":132,"gherkinStepLine":368,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":133,"gherkinStepLine":369,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":134,"gherkinStepLine":370,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\""},{"pwStepLine":135,"gherkinStepLine":371,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":136,"gherkinStepLine":372,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Select all\""},{"pwStepLine":137,"gherkinStepLine":373,"keywordType":"Outcome","textWithKeyword":"And I press \"Action\""},{"pwStepLine":138,"gherkinStepLine":374,"keywordType":"Outcome","textWithKeyword":"And I click the \"span:has-text('Delete selected')\" element"},{"pwStepLine":139,"gherkinStepLine":375,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":140,"gherkinStepLine":376,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Deleted\""},{"pwStepLine":141,"gherkinStepLine":377,"keywordType":"Outcome","textWithKeyword":"And I should not see \"exam\""},{"pwStepLine":142,"gherkinStepLine":378,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Assessment Link Work\""},{"pwStepLine":143,"gherkinStepLine":379,"keywordType":"Outcome","textWithKeyword":"And I should not see an error"}]},
  {"pwTestLine":146,"pickleLine":385,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":147,"gherkinStepLine":386,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator"},{"pwStepLine":148,"gherkinStepLine":387,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":149,"gherkinStepLine":388,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":150,"gherkinStepLine":389,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\""},{"pwStepLine":151,"gherkinStepLine":390,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":152,"gherkinStepLine":391,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Assessment Link Work\""},{"pwStepLine":153,"gherkinStepLine":392,"keywordType":"Outcome","textWithKeyword":"And I click the \"input.p-checkbox-input\" icon in the row for \"Assessment Link Work\""},{"pwStepLine":154,"gherkinStepLine":393,"keywordType":"Outcome","textWithKeyword":"And I press \"Delete selected\""},{"pwStepLine":155,"gherkinStepLine":394,"keywordType":"Outcome","textWithKeyword":"And I press \"Yes\""},{"pwStepLine":156,"gherkinStepLine":395,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":157,"gherkinStepLine":396,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Assessment Link Work\""},{"pwStepLine":158,"gherkinStepLine":397,"keywordType":"Outcome","textWithKeyword":"And I should not see an error"}]},
  {"pwTestLine":161,"pickleLine":399,"tags":["@common","@tools"],"steps":[{"pwStepLine":162,"gherkinStepLine":400,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":163,"gherkinStepLine":401,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":164,"gherkinStepLine":402,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":165,"gherkinStepLine":403,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":166,"gherkinStepLine":404,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":167,"gherkinStepLine":405,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Minimum certification score\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Minimum certification score\"","children":[{"start":14,"value":"Minimum certification score","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":168,"gherkinStepLine":406,"keywordType":"Outcome","textWithKeyword":"Then I press \"Edit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Edit\"","children":[{"start":9,"value":"Edit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":169,"gherkinStepLine":407,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":170,"gherkinStepLine":409,"keywordType":"Action","textWithKeyword":"And I uncheck \"Generate certificates\"","stepMatchArguments":[{"group":{"start":10,"value":"\"Generate certificates\"","children":[{"start":11,"value":"Generate certificates","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":171,"gherkinStepLine":410,"keywordType":"Action","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":172,"gherkinStepLine":411,"keywordType":"Action","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":173,"gherkinStepLine":412,"keywordType":"Outcome","textWithKeyword":"Then I should see \"75\"","stepMatchArguments":[{"group":{"start":13,"value":"\"75\"","children":[{"start":14,"value":"75","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":174,"gherkinStepLine":413,"keywordType":"Outcome","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":175,"gherkinStepLine":414,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":176,"gherkinStepLine":415,"keywordType":"Outcome","textWithKeyword":"And I follow \"Users\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Users\"","children":[{"start":10,"value":"Users","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":177,"gherkinStepLine":416,"keywordType":"Outcome","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":178,"gherkinStepLine":417,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Orizales\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Orizales\"","children":[{"start":14,"value":"Orizales","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":179,"gherkinStepLine":418,"keywordType":"Outcome","textWithKeyword":"And I click the \"button[title='Unsubscribe']\" icon in the row for \"Orizales\"","stepMatchArguments":[{"group":{"start":12,"value":"\"button[title='Unsubscribe']\"","children":[{"start":13,"value":"button[title='Unsubscribe']","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":62,"value":"\"Orizales\"","children":[{"start":63,"value":"Orizales","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":180,"gherkinStepLine":419,"keywordType":"Outcome","textWithKeyword":"And I press \"Yes\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Yes\"","children":[{"start":9,"value":"Yes","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":181,"gherkinStepLine":420,"keywordType":"Outcome","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":182,"gherkinStepLine":421,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end