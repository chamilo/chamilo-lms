// Generated from: features/toolCoursedescription.feature
import { test } from "playwright-bdd";

test.describe('Course description tool', () => {

  test.beforeEach('Background', async ({ Given, And, page }, testInfo) => { if (testInfo.error) return;
    await Given('I am a platform administrator', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
  });
  
  test('Admin edits the course description and sees the content', { tag: ['@common', '@tools'] }, async ({ Given, Then, And, page }) => { 
    await Given('I am on course "TEMP" homepage', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I follow "Course description"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await And('I click the "span.mdi-image-text" element', null, { page }); 
    await And('I fill in "course_description_title" with "General"', null, { page }); 
    await And('I fill in tinymce field "course_description_content" with "The surface web, also known as the visible web or indexed web, is the portion of the World Wide Web that is readily accessible to the general public through standard search engines such as Google and Bing, using conventional web browsers like Chrome or Firefox without requiring special software, authentication, or configuration.[1][2] It encompasses publicly available content that is crawled and indexed by search engine algorithms, allowing users to discover and navigate websites via simple URLs and keyword queries."', null, { page }); 
    await And('I press "save"', null, { page }); 
    await And('I wait for the page to be loaded', null, { page }); 
    await Then('I should see "surface web"', null, { page }); 
  });

  test.skip('Student views the course description', { tag: ['@common', '@tools', '@skip'] }, async ({ Given, Then, And }) => { 
    await Given('I am a student'); 
    await And('I wait for the page to be loaded'); 
    await Given('I am on course "TEMP" homepage'); 
    await And('I wait for the page to be loaded'); 
    await And('I follow "Course description"'); 
    await And('I wait for the page to be loaded'); 
    await Then('I should see "surface web"'); 
  });

});

// == technical section ==

test.use({
  $test: [({}, use) => use(test), { scope: 'test', box: true }],
  $uri: [({}, use) => use('features/toolCoursedescription.feature'), { scope: 'test', box: true }],
  $bddFileData: [({}, use) => use(bddFileData), { scope: "test", box: true }],
});

const bddFileData = [ // bdd-data-start
  {"pwTestLine":11,"pickleLine":41,"tags":["@common","@tools"],"steps":[{"pwStepLine":7,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true,"stepMatchArguments":[]},{"pwStepLine":8,"gherkinStepLine":39,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true,"stepMatchArguments":[]},{"pwStepLine":12,"gherkinStepLine":42,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage","stepMatchArguments":[{"group":{"start":15,"value":"\"TEMP\"","children":[{"start":16,"value":"TEMP","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":13,"gherkinStepLine":43,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":14,"gherkinStepLine":44,"keywordType":"Context","textWithKeyword":"And I follow \"Course description\"","stepMatchArguments":[{"group":{"start":9,"value":"\"Course description\"","children":[{"start":10,"value":"Course description","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":15,"gherkinStepLine":45,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":16,"gherkinStepLine":46,"keywordType":"Context","textWithKeyword":"And I click the \"span.mdi-image-text\" element","stepMatchArguments":[{"group":{"start":12,"value":"\"span.mdi-image-text\"","children":[{"start":13,"value":"span.mdi-image-text","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":17,"gherkinStepLine":47,"keywordType":"Context","textWithKeyword":"And I fill in \"course_description_title\" with \"General\"","stepMatchArguments":[{"group":{"start":10,"value":"\"course_description_title\"","children":[{"start":11,"value":"course_description_title","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":42,"value":"\"General\"","children":[{"start":43,"value":"General","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":18,"gherkinStepLine":48,"keywordType":"Context","textWithKeyword":"And I fill in tinymce field \"course_description_content\" with \"The surface web, also known as the visible web or indexed web, is the portion of the World Wide Web that is readily accessible to the general public through standard search engines such as Google and Bing, using conventional web browsers like Chrome or Firefox without requiring special software, authentication, or configuration.[1][2] It encompasses publicly available content that is crawled and indexed by search engine algorithms, allowing users to discover and navigate websites via simple URLs and keyword queries.\"","stepMatchArguments":[{"group":{"start":24,"value":"\"course_description_content\"","children":[{"start":25,"value":"course_description_content","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"},{"group":{"start":58,"value":"\"The surface web, also known as the visible web or indexed web, is the portion of the World Wide Web that is readily accessible to the general public through standard search engines such as Google and Bing, using conventional web browsers like Chrome or Firefox without requiring special software, authentication, or configuration.[1][2] It encompasses publicly available content that is crawled and indexed by search engine algorithms, allowing users to discover and navigate websites via simple URLs and keyword queries.\"","children":[{"start":59,"value":"The surface web, also known as the visible web or indexed web, is the portion of the World Wide Web that is readily accessible to the general public through standard search engines such as Google and Bing, using conventional web browsers like Chrome or Firefox without requiring special software, authentication, or configuration.[1][2] It encompasses publicly available content that is crawled and indexed by search engine algorithms, allowing users to discover and navigate websites via simple URLs and keyword queries.","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":19,"gherkinStepLine":49,"keywordType":"Context","textWithKeyword":"And I press \"save\"","stepMatchArguments":[{"group":{"start":8,"value":"\"save\"","children":[{"start":9,"value":"save","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]},{"pwStepLine":20,"gherkinStepLine":50,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","stepMatchArguments":[]},{"pwStepLine":21,"gherkinStepLine":51,"keywordType":"Outcome","textWithKeyword":"Then I should see \"surface web\"","stepMatchArguments":[{"group":{"start":13,"value":"\"surface web\"","children":[{"start":14,"value":"surface web","children":[{}]},{"children":[{}]}]},"parameterTypeName":"string"}]}]},
  {"pwTestLine":24,"pickleLine":60,"skipped":true,"tags":["@common","@tools","@skip"],"steps":[{"pwStepLine":7,"gherkinStepLine":38,"keywordType":"Context","textWithKeyword":"Given I am a platform administrator","isBg":true},{"pwStepLine":8,"gherkinStepLine":39,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded","isBg":true},{"pwStepLine":25,"gherkinStepLine":61,"keywordType":"Context","textWithKeyword":"Given I am a student"},{"pwStepLine":26,"gherkinStepLine":62,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":27,"gherkinStepLine":63,"keywordType":"Context","textWithKeyword":"Given I am on course \"TEMP\" homepage"},{"pwStepLine":28,"gherkinStepLine":64,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":29,"gherkinStepLine":65,"keywordType":"Context","textWithKeyword":"And I follow \"Course description\""},{"pwStepLine":30,"gherkinStepLine":66,"keywordType":"Context","textWithKeyword":"And I wait for the page to be loaded"},{"pwStepLine":31,"gherkinStepLine":67,"keywordType":"Outcome","textWithKeyword":"Then I should see \"surface web\""}]},
]; // bdd-data-end