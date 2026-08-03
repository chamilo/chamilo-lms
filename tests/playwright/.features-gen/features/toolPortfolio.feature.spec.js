// Generated from: features/toolPortfolio.feature
import { test } from "playwright-bdd";

test.describe('Portfolio tool', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Create a portfolio item in the Vue interface', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I follow "Portfolio"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await And('I follow "Add"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I fill in "portfolio_title" with "Modern portfolio evidence"', null, { page }); 
    await And('I fill in tinymce field "portfolio_content" with "Evidence created from the Vue Portfolio form"', null, { page }); 
    await And('I press "save"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Modern portfolio evidence"', null, { page }); 
  });

  test('Add a comment in the Vue interface', { tag: ['@common', '@tools'] }, async ({ Given, When, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await When('I follow "Portfolio"', null, { page }); 
    await And('I wait for the page content to settle', null, { page }); 
    await And('I follow "Modern portfolio evidence"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I press "Add a new comment"', null, { page }); 
    await And('I fill in tinymce field "portfolio_comment_content" with "Comment created from the Vue Portfolio dialog"', null, { page }); 
    await And('I press "Save"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "Comment created from the Vue Portfolio dialog"', null, { page }); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolPortfolio.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":36,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":37,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":39,"keywordType":"Action","textWithKeyword":"When I follow \"Portfolio\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Portfolio\"","children":[{"start":10,"value":"Portfolio","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":40,"keywordType":"Action","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":41,"keywordType":"Action","textWithKeyword":"And I follow \"Add\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Add\"","children":[{"start":10,"value":"Add","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":42,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":18,"gherkinStepLine":43,"keywordType":"Action","textWithKeyword":"And I fill in \"portfolio_title\" with \"Modern portfolio evidence\"","stepMatchArguments":[{"group":{"start":10,"value":"\"portfolio_title\"","children":[{"start":11,"value":"portfolio_title","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":33,"value":"\"Modern portfolio evidence\"","children":[{"start":34,"value":"Modern portfolio evidence","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":44,"keywordType":"Action","textWithKeyword":"And I fill in tinymce field \"portfolio_content\" with \"Evidence created from the Vue Portfolio form\"","stepMatchArguments":[{"group":{"start":24,"value":"\"portfolio_content\"","children":[{"start":25,"value":"portfolio_content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":49,"value":"\"Evidence created from the Vue Portfolio form\"","children":[{"start":50,"value":"Evidence created from the Vue Portfolio form","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":45,"keywordType":"Action","textWithKeyword":"And I press \"save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"save\"","children":[{"start":9,"value":"save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":21,"gherkinStepLine":46,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":22,"gherkinStepLine":47,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Modern portfolio evidence\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Modern portfolio evidence\"","children":[{"start":14,"value":"Modern portfolio evidence","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":25,"pickleLine":49,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":33,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":34,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":26,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":27,"gherkinStepLine":51,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":28,"gherkinStepLine":52,"keywordType":"Action","textWithKeyword":"When I follow \"Portfolio\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Portfolio\"","children":[{"start":10,"value":"Portfolio","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":29,"gherkinStepLine":53,"keywordType":"Action","textWithKeyword":"And I wait for the page content to settle","stepMatchArguments":[]},{"pwStepLine":30,"gherkinStepLine":54,"keywordType":"Action","textWithKeyword":"And I follow \"Modern portfolio evidence\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Modern portfolio evidence\"","children":[{"start":10,"value":"Modern portfolio evidence","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":31,"gherkinStepLine":55,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":32,"gherkinStepLine":56,"keywordType":"Action","textWithKeyword":"And I press \"Add a new comment\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Add a new comment\"","children":[{"start":9,"value":"Add a new comment","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":33,"gherkinStepLine":57,"keywordType":"Action","textWithKeyword":"And I fill in tinymce field \"portfolio_comment_content\" with \"Comment created from the Vue Portfolio dialog\"","stepMatchArguments":[{"group":{"start":24,"value":"\"portfolio_comment_content\"","children":[{"start":25,"value":"portfolio_comment_content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":57,"value":"\"Comment created from the Vue Portfolio dialog\"","children":[{"start":58,"value":"Comment created from the Vue Portfolio dialog","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":34,"gherkinStepLine":58,"keywordType":"Action","textWithKeyword":"And I press \"Save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"Save\"","children":[{"start":9,"value":"Save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":35,"gherkinStepLine":59,"keywordType":"Action","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":36,"gherkinStepLine":60,"keywordType":"Outcome","textWithKeyword":"Then I should see \"Comment created from the Vue Portfolio dialog\"","stepMatchArguments":[{"group":{"start":13,"value":"\"Comment created from the Vue Portfolio dialog\"","children":[{"start":14,"value":"Comment created from the Vue Portfolio dialog","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
]; // bdd-data-end