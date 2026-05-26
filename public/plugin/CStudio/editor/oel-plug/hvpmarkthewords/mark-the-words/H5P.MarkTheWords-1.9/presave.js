var H5PPresave = H5PPresave || {};

/**
 * Resolve the presave logic for the content type Mark the Words
 *
 * @param {object} content
 * @param finished
 * @constructor
 */
H5PPresave['H5P.MarkTheWords'] = function (content, finished) {
  var presave = H5PEditor.Presave;

  if (isContentInvalid()) {
    throw new presave.exceptions.InvalidContentSemanticsException('Invalid Mark The Words Error');
  }

  var answers = content.textField.replace("**", "").match(/\*[^\*]+\*/g);
  var score = Array.isArray(answers) ? answers.length : 1;

  presave.validateScore(score);

  finished({maxScore: score});

  /**
   * Check if required parameters is present
   * @return {boolean}
   */
  function isContentInvalid() {
    return !presave.checkNestedRequirements(content, 'content.textField');
  }
};
