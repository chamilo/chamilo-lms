var H5PPresave = H5PPresave || {};

/**
 * Resolve the presave logic for the content type True/False
 *
 * @param {Object} content
 * @param {finished} finished
 * @constructor
 */
H5PPresave['H5P.TrueFalse'] = function (content, finished) {
  var presave = H5PEditor.Presave;

  if (isContentInvalid()) {
    throw new presave.exceptions.InvalidContentSemanticsException('Invalid True/False Error');
  }

  finished({maxScore: 1});

  /**
   * Check if required parameters is present
   * @return {boolean}
   */
  function isContentInvalid() {
    return !presave.checkNestedRequirements(content, 'content.question') || content.question.trim() === '';
  }
};
