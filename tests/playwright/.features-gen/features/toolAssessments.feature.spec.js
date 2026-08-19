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
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I click the "i.mdi-pencil" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"edit_cat_form_certif_min_score"},{"value":"50"}]}]}}, { page }); 
    await And('I check "Generate certificates"', null, { page }); 
    await And('I press "edit_cat_form_submit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "50"', null, { page }); 
  });

  test('Create an evaluation "exam" in course TEMP', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assessments"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I click the "i.mdi-table-plus" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"evaluation_title"},{"value":"exam"}]},{"cells":[{"value":"weight_mask"},{"value":"90"}]},{"cells":[{"value":"add_eval_form_max"},{"value":"10"}]},{"cells":[{"value":"min_score"},{"value":"3"}]}]}}, { page }); 
    await And('I check "Grade learners"', null, { page }); 
    await And('I press "add_eval_form_submit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the score for "norizales" with "6"', null, { page }); 
    await And('I press "add_result_form_submit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
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
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I click the "i.mdi-format-list-text" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I click the "i.mdi-certificate" element', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I should see "Noa Orizales"', null, { page }); 
    await And('I follow "Certificate"', null, { page }); 
    await Then('I should see "Certificate"', null, { page }); 
  });

  test('Admin exports all to PDF', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Assessments"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I click the "i.mdi-account" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Export all to PDF"', null, { page }); 
    await And('wait for the page to be loaded when ready', null, { page }); 
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
    await And('wait for the page to be loaded when ready', null, { page }); 
    await Then('I click the "i.mdi-pencil" element', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"edit_cat_form_certif_min_score"},{"value":"75"}]}]}}, { page }); 
    await And('I uncheck "Generate certificates"', null, { page }); 
    await And('I press "edit_cat_form_submit"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "75"', null, { page }); 
    await And('I am on "/main/user/user.php?cid=3"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Orizales"', null, { page }); 
    await Then('I follow "Unsubscribe"', null, { page }); 
    await And('I confirm the popup', null, { page }); 
    await And('wait very long for the page to be loaded', null, { page }); 
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
  {"pwTestLine":6,"pickleLine":211,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":212,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":213,"keywordType":"Context","textWithKeyword":"And I am on \"/main/user/subscribe_user.php?keyword=norizales&type=5&cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/subscribe_user.php?keyword=norizales&type=5&cid=3\"","children":[{"start":9,"value":"/main/user/subscribe_user.php?keyword=norizales&type=5&cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":214,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":10,"gherkinStepLine":215,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Noa\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Noa\"","children":[{"start":14,"value":"Noa","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":11,"gherkinStepLine":216,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Register\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Register\"","children":[{"start":10,"value":"Register","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":12,"gherkinStepLine":217,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":218,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":16,"pickleLine":220,"tags":["@common","@tools"],"steps":[{"pwStepLine":17,"gherkinStepLine":221,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":222,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":223,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":20,"gherkinStepLine":224,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":225,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":226,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-pencil\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-pencil\"","children":[{"start":13,"value":"i.mdi-pencil","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":23,"gherkinStepLine":227,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":24,"gherkinStepLine":228,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":25,"gherkinStepLine":230,"keywordType":"Action","textWithKeyword":"And I check \"Generate certificates\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Generate certificates\"","children":[{"start":9,"value":"Generate certificates","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":26,"gherkinStepLine":231,"keywordType":"Action","textWithKeyword":"And I press \"edit_cat_form_submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"edit_cat_form_submit\"","children":[{"start":9,"value":"edit_cat_form_submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":232,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":233,"keywordType":"Outcome","textWithKeyword":"Then I should see \"50\"","stepMatchArguments":[{"group":{"start":13,"value":"\"50\"","children":[{"start":14,"value":"50","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":31,"pickleLine":235,"tags":["@common","@tools"],"steps":[{"pwStepLine":32,"gherkinStepLine":236,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":33,"gherkinStepLine":237,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":238,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":35,"gherkinStepLine":239,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":36,"gherkinStepLine":240,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":37,"gherkinStepLine":241,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-table-plus\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-table-plus\"","children":[{"start":13,"value":"i.mdi-table-plus","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":38,"gherkinStepLine":242,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":39,"gherkinStepLine":243,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":40,"gherkinStepLine":248,"keywordType":"Action","textWithKeyword":"And I check \"Grade learners\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Grade learners\"","children":[{"start":9,"value":"Grade learners","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":41,"gherkinStepLine":249,"keywordType":"Action","textWithKeyword":"And I press \"add_eval_form_submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"add_eval_form_submit\"","children":[{"start":9,"value":"add_eval_form_submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":42,"gherkinStepLine":250,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":43,"gherkinStepLine":251,"keywordType":"Action","textWithKeyword":"When I fill in the score for \"norizales\" with \"6\"","stepMatchArguments":[{"group":{"start":24,"value":"\"norizales\"","children":[{"start":25,"value":"norizales","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":41,"value":"\"6\"","children":[{"start":42,"value":"6","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":44,"gherkinStepLine":252,"keywordType":"Action","textWithKeyword":"And I press \"add_result_form_submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"add_result_form_submit\"","children":[{"start":9,"value":"add_result_form_submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":45,"gherkinStepLine":253,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":254,"keywordType":"Outcome","textWithKeyword":"Then I should see \"exam\"","stepMatchArguments":[{"group":{"start":13,"value":"\"exam\"","children":[{"start":14,"value":"exam","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":49,"pickleLine":262,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":50,"gherkinStepLine":263,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator"},{"pwStepLine":51,"gherkinStepLine":264,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":52,"gherkinStepLine":265,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":53,"gherkinStepLine":266,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\""},{"pwStepLine":54,"gherkinStepLine":267,"keywordType":"Context","textWithKeyword":"And I wait for the page content to settle"},{"pwStepLine":55,"gherkinStepLine":268,"keywordType":"Outcome","textWithKeyword":"Then I press \"Create Assignment\""},{"pwStepLine":56,"gherkinStepLine":269,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":57,"gherkinStepLine":270,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":58,"gherkinStepLine":272,"keywordType":"Action","textWithKeyword":"And I fill in the active tinymce editor with \"Link target for the Assessments tool\""},{"pwStepLine":59,"gherkinStepLine":273,"keywordType":"Action","textWithKeyword":"And I press \"Save\""},{"pwStepLine":60,"gherkinStepLine":274,"keywordType":"Action","textWithKeyword":"And I wait for the page content to settle"},{"pwStepLine":61,"gherkinStepLine":275,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Assignment created\""},{"pwStepLine":62,"gherkinStepLine":276,"keywordType":"Outcome","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":63,"gherkinStepLine":277,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":64,"gherkinStepLine":278,"keywordType":"Outcome","textWithKeyword":"And I follow \"Assessments\""},{"pwStepLine":65,"gherkinStepLine":279,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":66,"gherkinStepLine":280,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-link-plus\" element"},{"pwStepLine":67,"gherkinStepLine":281,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":68,"gherkinStepLine":282,"keywordType":"Action","textWithKeyword":"When I select \"Assignments\" from \"create_link_select_link\""},{"pwStepLine":69,"gherkinStepLine":283,"keywordType":"Action","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":70,"gherkinStepLine":284,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":71,"gherkinStepLine":287,"keywordType":"Action","textWithKeyword":"And I press \"add_link_submit\""},{"pwStepLine":72,"gherkinStepLine":288,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":73,"gherkinStepLine":289,"keywordType":"Outcome","textWithKeyword":"Then I should see \"The link has been added\""},{"pwStepLine":74,"gherkinStepLine":290,"keywordType":"Outcome","textWithKeyword":"Then I click the \"[title='Edit weight']\" icon in the row for \"Assessment Link Work\""},{"pwStepLine":75,"gherkinStepLine":291,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":76,"gherkinStepLine":292,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":77,"gherkinStepLine":294,"keywordType":"Action","textWithKeyword":"And I press \"edit_link_form_submit\""},{"pwStepLine":78,"gherkinStepLine":295,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":79,"gherkinStepLine":296,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Assessment edited\""}]},
  {"pwTestLine":82,"pickleLine":305,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":83,"gherkinStepLine":306,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator"},{"pwStepLine":84,"gherkinStepLine":307,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":85,"gherkinStepLine":308,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":86,"gherkinStepLine":309,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\""},{"pwStepLine":87,"gherkinStepLine":310,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":88,"gherkinStepLine":311,"keywordType":"Context","textWithKeyword":"And I follow \"exam\""},{"pwStepLine":89,"gherkinStepLine":312,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":90,"gherkinStepLine":313,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-pencil\" icon in the row for \"Orizales\""},{"pwStepLine":91,"gherkinStepLine":314,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":92,"gherkinStepLine":315,"keywordType":"Action","textWithKeyword":"When I fill in the following:"},{"pwStepLine":93,"gherkinStepLine":317,"keywordType":"Action","textWithKeyword":"And I press \"edit_result_form_submit\""},{"pwStepLine":94,"gherkinStepLine":318,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":95,"gherkinStepLine":319,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Assessments\""},{"pwStepLine":96,"gherkinStepLine":320,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":97,"gherkinStepLine":321,"keywordType":"Outcome","textWithKeyword":"And I click the \"i.mdi-chart-box\" element"},{"pwStepLine":98,"gherkinStepLine":322,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":99,"gherkinStepLine":323,"keywordType":"Outcome","textWithKeyword":"Then I should see \"8 / 10\""}]},
  {"pwTestLine":102,"pickleLine":325,"tags":["@common","@tools"],"steps":[{"pwStepLine":103,"gherkinStepLine":326,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":104,"gherkinStepLine":327,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":105,"gherkinStepLine":328,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":106,"gherkinStepLine":329,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":107,"gherkinStepLine":330,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":108,"gherkinStepLine":331,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-format-list-text\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-format-list-text\"","children":[{"start":13,"value":"i.mdi-format-list-text","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":109,"gherkinStepLine":332,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":110,"gherkinStepLine":333,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-certificate\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-certificate\"","children":[{"start":13,"value":"i.mdi-certificate","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":111,"gherkinStepLine":334,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":112,"gherkinStepLine":335,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Noa Orizales\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Noa Orizales\"","children":[{"start":14,"value":"Noa Orizales","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":113,"gherkinStepLine":336,"keywordType":"Outcome","textWithKeyword":"And I follow \"Certificate\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Certificate\"","children":[{"start":10,"value":"Certificate","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":114,"gherkinStepLine":337,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Certificate\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Certificate\"","children":[{"start":14,"value":"Certificate","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":117,"pickleLine":339,"tags":["@common","@tools"],"steps":[{"pwStepLine":118,"gherkinStepLine":340,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":119,"gherkinStepLine":341,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":120,"gherkinStepLine":342,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":121,"gherkinStepLine":343,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":122,"gherkinStepLine":344,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":123,"gherkinStepLine":345,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-account\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-account\"","children":[{"start":13,"value":"i.mdi-account","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":124,"gherkinStepLine":346,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":125,"gherkinStepLine":347,"keywordType":"Outcome","textWithKeyword":"And I follow \"Export all to PDF\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Export all to PDF\"","children":[{"start":10,"value":"Export all to PDF","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":126,"gherkinStepLine":348,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":127,"gherkinStepLine":349,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
  {"pwTestLine":130,"pickleLine":360,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":131,"gherkinStepLine":361,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator"},{"pwStepLine":132,"gherkinStepLine":362,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":133,"gherkinStepLine":363,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":134,"gherkinStepLine":364,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\""},{"pwStepLine":135,"gherkinStepLine":365,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":136,"gherkinStepLine":366,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Select all\""},{"pwStepLine":137,"gherkinStepLine":367,"keywordType":"Outcome","textWithKeyword":"And I press \"Action\""},{"pwStepLine":138,"gherkinStepLine":368,"keywordType":"Outcome","textWithKeyword":"And I click the \"span:has-text('Delete selected')\" element"},{"pwStepLine":139,"gherkinStepLine":369,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":140,"gherkinStepLine":370,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Deleted\""},{"pwStepLine":141,"gherkinStepLine":371,"keywordType":"Outcome","textWithKeyword":"And I should not see \"exam\""},{"pwStepLine":142,"gherkinStepLine":372,"keywordType":"Outcome","textWithKeyword":"And I should not see \"Assessment Link Work\""},{"pwStepLine":143,"gherkinStepLine":373,"keywordType":"Outcome","textWithKeyword":"And I should not see an error"}]},
  {"pwTestLine":146,"pickleLine":379,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":147,"gherkinStepLine":380,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator"},{"pwStepLine":148,"gherkinStepLine":381,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage"},{"pwStepLine":149,"gherkinStepLine":382,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":150,"gherkinStepLine":383,"keywordType":"Context","textWithKeyword":"And I follow \"Assignments\""},{"pwStepLine":151,"gherkinStepLine":384,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":152,"gherkinStepLine":385,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Assessment Link Work\""},{"pwStepLine":153,"gherkinStepLine":386,"keywordType":"Outcome","textWithKeyword":"And I click the \"input.p-checkbox-input\" icon in the row for \"Assessment Link Work\""},{"pwStepLine":154,"gherkinStepLine":387,"keywordType":"Outcome","textWithKeyword":"And I press \"Delete selected\""},{"pwStepLine":155,"gherkinStepLine":388,"keywordType":"Outcome","textWithKeyword":"And I press \"Yes\""},{"pwStepLine":156,"gherkinStepLine":389,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded when ready"},{"pwStepLine":157,"gherkinStepLine":390,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"Assessment Link Work\""},{"pwStepLine":158,"gherkinStepLine":391,"keywordType":"Outcome","textWithKeyword":"And I should not see an error"}]},
  {"pwTestLine":161,"pickleLine":393,"tags":["@common","@tools"],"steps":[{"pwStepLine":162,"gherkinStepLine":394,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","stepMatchArguments":[]},{"pwStepLine":163,"gherkinStepLine":395,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":164,"gherkinStepLine":396,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":165,"gherkinStepLine":397,"keywordType":"Context","textWithKeyword":"And I follow \"Assessments\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Assessments\"","children":[{"start":10,"value":"Assessments","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":166,"gherkinStepLine":398,"keywordType":"Context","textWithKeyword":"And wait for the page to be loaded when ready","stepMatchArguments":[]},{"pwStepLine":167,"gherkinStepLine":399,"keywordType":"Outcome","textWithKeyword":"Then I click the \"i.mdi-pencil\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"i.mdi-pencil\"","children":[{"start":13,"value":"i.mdi-pencil","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":168,"gherkinStepLine":400,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":169,"gherkinStepLine":401,"keywordType":"Action","textWithKeyword":"When I fill in the following:","stepMatchArguments":[]},{"pwStepLine":170,"gherkinStepLine":403,"keywordType":"Action","textWithKeyword":"And I uncheck \"Generate certificates\"","stepMatchArguments":[{"group":{"start":10,"value":"\"Generate certificates\"","children":[{"start":11,"value":"Generate certificates","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":171,"gherkinStepLine":404,"keywordType":"Action","textWithKeyword":"And I press \"edit_cat_form_submit\"","stepMatchArguments":[{"group":{"start":8,"value":"\"edit_cat_form_submit\"","children":[{"start":9,"value":"edit_cat_form_submit","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":172,"gherkinStepLine":405,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":173,"gherkinStepLine":406,"keywordType":"Outcome","textWithKeyword":"Then I should see \"75\"","stepMatchArguments":[{"group":{"start":13,"value":"\"75\"","children":[{"start":14,"value":"75","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":174,"gherkinStepLine":407,"keywordType":"Outcome","textWithKeyword":"And I am on \"/main/user/user.php?cid=3\"","stepMatchArguments":[{"group":{"start":8,"value":"\"/main/user/user.php?cid=3\"","children":[{"start":9,"value":"/main/user/user.php?cid=3","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":175,"gherkinStepLine":408,"keywordType":"Outcome","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":176,"gherkinStepLine":409,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Orizales\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Orizales\"","children":[{"start":14,"value":"Orizales","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":177,"gherkinStepLine":410,"keywordType":"Outcome","textWithKeyword":"Then I follow \"Unsubscribe\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Unsubscribe\"","children":[{"start":10,"value":"Unsubscribe","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":178,"gherkinStepLine":411,"keywordType":"Outcome","textWithKeyword":"And I confirm the popup","stepMatchArguments":[]},{"pwStepLine":179,"gherkinStepLine":412,"keywordType":"Outcome","textWithKeyword":"And wait very long for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":180,"gherkinStepLine":413,"keywordType":"Outcome","textWithKeyword":"Then I should not see an error","stepMatchArguments":[]}]},
]; // bdd-data-end