// Generated from: features/toolDocument.feature
import { test } from "playwright-bdd";

test.describe('Document tool', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Create a folder', async ({ Given, Then, And, page }) => { 
    await Given('I follow "Documents"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I press "New folder"', null, { page }); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"My new directory"}]}]}}, { page }); 
    await And('I press "Save"', null, { page }); 
    await Then('I should see "Saved"', null, { page }); 
  });

  test.skip('Create a text document', { tag: ['@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I follow "Documents"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I press "New document"'); 
    await And('wait for the page to be loaded'); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"My first document"}]}]}}); 
    await And('I fill in tinymce field "item_content" with "This is my first document!"'); 
    await And('I press "Save"'); 
    await And('wait for the page to be loaded'); 
    await Then('I should see "My first document"'); 
  });

  test.skip('Create a HTML document', { tag: ['@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I follow "Documents"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I press "New document"'); 
    await And('wait for the page to be loaded'); 
    await And('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"My second document"}]}]}}); 
    await And('I fill in tinymce field "item_content" with "<a href=\'www.chamilo.org\'>Click here</a><span><b>This is my second document!!</b></span>"'); 
    await And('I press "Save"'); 
    await And('wait for the page to be loaded'); 
    await Then('I should see "My second document"'); 
  });

  test('Upload a document', async ({ Given, Then, And, page }) => { 
    await Given('I follow "Documents"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I press "Upload"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "Drop files here"', null, { page }); 
    await Then('I attach the file "/public/favicon.ico" to the upload dropzone', null, { page }); 
    await Then('I press "Upload 1 file"', null, { page }); 
    await And('wait for the page to be loaded', null, { page }); 
    await Then('I should see "favicon.ico"', null, { page }); 
  });

  test.skip('Search for "My second document" and edit it', { tag: ['@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I follow "Documents"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "My second document"'); 
    await Then('I click the "[title=\'Edit\']" icon in the row for "My second document"'); 
    await And('wait for the page to be loaded'); 
    await Then('I fill in the following:', {"dataTable":{"rows":[{"cells":[{"value":"title"},{"value":"My second document edited"}]}]}}); 
    await Then('I press "Save"'); 
    await And('wait for the page to be loaded'); 
    await Then('I should see "My second document edited"'); 
  });

  test.skip('Search for "My first document" and delete it', { tag: ['@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I follow "Documents"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "My first document"'); 
    await Then('I click the "[title=\'Delete\']" icon in the row for "My first document"'); 
    await And('I press "Yes"'); 
    await And('wait for the page to be loaded'); 
    await Then('I should not see "My first document"'); 
  });

  test('Delete the remaining test documents', async ({ Given, Then, And, page }) => { 
    await Given('I follow "Documents"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I delete the document "My second document edited" if present', null, { page }); 
    await Then('I delete the document "favicon.ico" if present', null, { page }); 
    await Then('I delete the document "My new directory" if present', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolDocument.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":12,"pickleLine":71,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true,"stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":13,"gherkinStepLine":72,"keywordType":"Context","textWithKeyword":"Given I follow \"Documents\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Documents\"","children":[{"start":10,"value":"Documents","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":14,"gherkinStepLine":73,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":15,"gherkinStepLine":74,"keywordType":"Outcome","textWithKeyword":"Then I press \"New folder\"","stepMatchArguments":[{"group":{"start":8,"value":"\"New folder\"","children":[{"start":9,"value":"New folder","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":16,"gherkinStepLine":75,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:","stepMatchArguments":[]},{"pwStepLine":17,"gherkinStepLine":77,"keywordType":"Outcome","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":78,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Saved\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Saved\"","children":[{"start":14,"value":"Saved","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":21,"pickleLine":91,"skipped":true,"tags":["@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":8,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true},{"pwStepLine":9,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true},{"pwStepLine":22,"gherkinStepLine":92,"keywordType":"Context","textWithKeyword":"Given I follow \"Documents\""},{"pwStepLine":23,"gherkinStepLine":93,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":24,"gherkinStepLine":94,"keywordType":"Outcome","textWithKeyword":"Then I press \"New document\""},{"pwStepLine":25,"gherkinStepLine":95,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":26,"gherkinStepLine":96,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:"},{"pwStepLine":27,"gherkinStepLine":98,"keywordType":"Outcome","textWithKeyword":"And I fill in tinymce field \"item_content\" with \"This is my first document!\""},{"pwStepLine":28,"gherkinStepLine":99,"keywordType":"Outcome","textWithKeyword":"And I press \"Save\""},{"pwStepLine":29,"gherkinStepLine":100,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":30,"gherkinStepLine":101,"keywordType":"Outcome","textWithKeyword":"Then I should see \"My first document\""}]},
  {"pwTestLine":33,"pickleLine":106,"skipped":true,"tags":["@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":8,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true},{"pwStepLine":9,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true},{"pwStepLine":34,"gherkinStepLine":107,"keywordType":"Context","textWithKeyword":"Given I follow \"Documents\""},{"pwStepLine":35,"gherkinStepLine":108,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":36,"gherkinStepLine":109,"keywordType":"Outcome","textWithKeyword":"Then I press \"New document\""},{"pwStepLine":37,"gherkinStepLine":110,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":38,"gherkinStepLine":111,"keywordType":"Outcome","textWithKeyword":"And I fill in the following:"},{"pwStepLine":39,"gherkinStepLine":113,"keywordType":"Outcome","textWithKeyword":"And I fill in tinymce field \"item_content\" with \"<a href='www.chamilo.org'>Click here</a><span><b>This is my second document!!</b></span>\""},{"pwStepLine":40,"gherkinStepLine":114,"keywordType":"Outcome","textWithKeyword":"And I press \"Save\""},{"pwStepLine":41,"gherkinStepLine":115,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":42,"gherkinStepLine":116,"keywordType":"Outcome","textWithKeyword":"Then I should see \"My second document\""}]},
  {"pwTestLine":45,"pickleLine":118,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true,"stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":46,"gherkinStepLine":119,"keywordType":"Context","textWithKeyword":"Given I follow \"Documents\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Documents\"","children":[{"start":10,"value":"Documents","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":47,"gherkinStepLine":120,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":48,"gherkinStepLine":121,"keywordType":"Outcome","textWithKeyword":"Then I press \"Upload\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Upload\"","children":[{"start":9,"value":"Upload","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":49,"gherkinStepLine":122,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":50,"gherkinStepLine":123,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Drop files here\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Drop files here\"","children":[{"start":14,"value":"Drop files here","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":51,"gherkinStepLine":124,"keywordType":"Outcome","textWithKeyword":"Then I attach the file \"/public/favicon.ico\" to the upload dropzone","stepMatchArguments":[{"group":{"start":18,"value":"\"/public/favicon.ico\"","children":[{"start":19,"value":"/public/favicon.ico","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":52,"gherkinStepLine":125,"keywordType":"Outcome","textWithKeyword":"Then I press \"Upload 1 file\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Upload 1 file\"","children":[{"start":9,"value":"Upload 1 file","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":53,"gherkinStepLine":126,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":54,"gherkinStepLine":127,"keywordType":"Outcome","textWithKeyword":"Then I should see \"favicon.ico\"","stepMatchArguments":[{"group":{"start":13,"value":"\"favicon.ico\"","children":[{"start":14,"value":"favicon.ico","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":57,"pickleLine":132,"skipped":true,"tags":["@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":8,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true},{"pwStepLine":9,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true},{"pwStepLine":58,"gherkinStepLine":133,"keywordType":"Context","textWithKeyword":"Given I follow \"Documents\""},{"pwStepLine":59,"gherkinStepLine":134,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":60,"gherkinStepLine":135,"keywordType":"Outcome","textWithKeyword":"Then I should see \"My second document\""},{"pwStepLine":61,"gherkinStepLine":136,"keywordType":"Outcome","textWithKeyword":"Then I click the \"[title='Edit']\" icon in the row for \"My second document\""},{"pwStepLine":62,"gherkinStepLine":137,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":63,"gherkinStepLine":138,"keywordType":"Outcome","textWithKeyword":"Then I fill in the following:"},{"pwStepLine":64,"gherkinStepLine":140,"keywordType":"Outcome","textWithKeyword":"Then I press \"Save\""},{"pwStepLine":65,"gherkinStepLine":141,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":66,"gherkinStepLine":142,"keywordType":"Outcome","textWithKeyword":"Then I should see \"My second document edited\""}]},
  {"pwTestLine":69,"pickleLine":147,"skipped":true,"tags":["@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":8,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true},{"pwStepLine":9,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true},{"pwStepLine":70,"gherkinStepLine":148,"keywordType":"Context","textWithKeyword":"Given I follow \"Documents\""},{"pwStepLine":71,"gherkinStepLine":149,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":72,"gherkinStepLine":150,"keywordType":"Outcome","textWithKeyword":"Then I should see \"My first document\""},{"pwStepLine":73,"gherkinStepLine":151,"keywordType":"Outcome","textWithKeyword":"Then I click the \"[title='Delete']\" icon in the row for \"My first document\""},{"pwStepLine":74,"gherkinStepLine":152,"keywordType":"Outcome","textWithKeyword":"And I press \"Yes\""},{"pwStepLine":75,"gherkinStepLine":153,"keywordType":"Outcome","textWithKeyword":"And wait for the page to be loaded"},{"pwStepLine":76,"gherkinStepLine":154,"keywordType":"Outcome","textWithKeyword":"Then I should not see \"My first document\""}]},
  {"pwTestLine":79,"pickleLine":156,"tags":[],"steps":[{"pwStepLine":7,"gherkinStepLine":67,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":68,"keywordType":"Context","textWithKeyword":"And I am on course \"TEMP\" homepage","isBg":true,"stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":9,"gherkinStepLine":69,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":80,"gherkinStepLine":157,"keywordType":"Context","textWithKeyword":"Given I follow \"Documents\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Documents\"","children":[{"start":10,"value":"Documents","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":81,"gherkinStepLine":158,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":82,"gherkinStepLine":159,"keywordType":"Outcome","textWithKeyword":"Then I delete the document \"My second document edited\" if present","stepMatchArguments":[{"group":{"start":22,"value":"\"My second document edited\"","children":[{"start":23,"value":"My second document edited","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":83,"gherkinStepLine":160,"keywordType":"Outcome","textWithKeyword":"Then I delete the document \"favicon.ico\" if present","stepMatchArguments":[{"group":{"start":22,"value":"\"favicon.ico\"","children":[{"start":23,"value":"favicon.ico","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":84,"gherkinStepLine":161,"keywordType":"Outcome","textWithKeyword":"Then I delete the document \"My new directory\" if present","stepMatchArguments":[{"group":{"start":22,"value":"\"My new directory\"","children":[{"start":23,"value":"My new directory","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end