var H5PPresave = H5PPresave || {};

/**
 * Resolve the presave logic for the content type Fill in the Blanks
 *
 * @param {object} content
 * @param finished
 * @constructor
 */
H5PPresave['H5P.Blanks'] = function (content, finished) {
  var presave = H5PEditor.Presave;

  if (isContentInvalid()) {
    throw {
      name: 'Invalid Fill in the blanks Error',
      message: 'Could not find expected semantics in content.'
    };
  }

  var score = content.questions
    .map(function (question) {
      var pattern = /\*[^\*]+\*/g;
      var matches = question.match(pattern);
      return Array.isArray(matches) ? matches.length : 0;
    })
    .reduce(function (previous, current) {
      return previous + current;
    }, 0);

  presave.validateScore(score);

  finished({maxScore: score});

  /**
   * Check if required parameters is present
   * @return {boolean}
   */
  function isContentInvalid() {
    return !presave.checkNestedRequirements(content, 'content.questions') || !Array.isArray(content.questions);
  }
};
