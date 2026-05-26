H5P.MarkTheWords = H5P.MarkTheWords || {};
H5P.MarkTheWords.Word = (function () {
  /**
   * @constant
   *
   * @type {string}
  */
  Word.ID_MARK_MISSED = "h5p-description-missed";
  /**
   * @constant
   *
   * @type {string}
   */
  Word.ID_MARK_CORRECT = "h5p-description-correct";
  /**
   * @constant
   *
   * @type {string}
   */
  Word.ID_MARK_INCORRECT = "h5p-description-incorrect";

  /**
   * Class for keeping track of selectable words.
   *
   * @class
   * @param {jQuery} $word
   */
  function Word($word, params) {
    var self = this;
    self.params = params;
    H5P.EventDispatcher.call(self);

    var input = $word.text();
    var handledInput = input;

    // Check if word is an answer
    var isAnswer = checkForAnswer();

    // Remove single asterisk and escape double asterisks.
    handleAsterisks();

    if (isAnswer) {
      $word.text(handledInput);
    }

    const ariaText = document.createElement('span');
    ariaText.classList.add('hidden-but-read');
    $word[0].appendChild(ariaText);

    /**
     * Checks if the word is an answer by checking the first, second to last and last character of the word.
     *
     * @private
     * @return {Boolean} Returns true if the word is an answer.
     */
    function checkForAnswer() {
      // Check last and next to last character, in case of punctuations.
      var wordString = removeDoubleAsterisks(input);
      if (wordString.charAt(0) === ('*') && wordString.length > 2) {
        if (wordString.charAt(wordString.length - 1) === ('*')) {
          handledInput = input.slice(1, input.length - 1);
          return true;
        }
        // If punctuation, add the punctuation to the end of the word.
        else if(wordString.charAt(wordString.length - 2) === ('*')) {
          handledInput = input.slice(1, input.length - 2);
          return true;
        }
        return false;
      }
      return false;
    }

    /**
     * Removes double asterisks from string, used to handle input.
     *
     * @private
     * @param {String} wordString The string which will be handled.
     * @return {String} Returns a string without double asterisks.
     */
    function removeDoubleAsterisks(wordString) {
      var asteriskIndex = wordString.indexOf('*');
      var slicedWord = wordString;

      while (asteriskIndex !== -1) {
        if (wordString.indexOf('*', asteriskIndex + 1) === asteriskIndex + 1) {
          slicedWord = wordString.slice(0, asteriskIndex) + wordString.slice(asteriskIndex + 2, input.length);
        }
        asteriskIndex = wordString.indexOf('*', asteriskIndex + 1);
      }

      return slicedWord;
    }

    /**
     * Escape double asterisks ** = *, and remove single asterisk.
     *
     * @private
     */
    function handleAsterisks() {
      var asteriskIndex = handledInput.indexOf('*');

      while (asteriskIndex !== -1) {
        handledInput = handledInput.slice(0, asteriskIndex) + handledInput.slice(asteriskIndex + 1, handledInput.length);
        asteriskIndex = handledInput.indexOf('*', asteriskIndex + 1);
      }
    }

    /**
     * Removes any score points added to the marked word.
     */
    self.clearScorePoint = function () {
      const scorePoint = $word[0].querySelector('div');
      if (scorePoint) {
        scorePoint.parentNode.removeChild(scorePoint);
      }
    };

    /**
     * Get Word as a string
     *
     * @return {string} Word as text
     */
    this.getText = function () {
      return input;
    };

    /**
     * Clears all marks from the word.
     *
     * @public
     */
    this.markClear = function () {
      $word
        .attr('aria-selected', false)
        .removeAttr('aria-describedby');

      ariaText.innerHTML = '';
      this.clearScorePoint();
    };

    /**
     * Check if the word is correctly marked and style it accordingly.
     * Reveal result
     *
     * @public
     * @param {H5P.Question.ScorePoints} scorePoints
     */
    this.markCheck = function (scorePoints) {
      if (this.isSelected()) {
        $word.attr('aria-describedby', isAnswer ? Word.ID_MARK_CORRECT : Word.ID_MARK_INCORRECT);
        ariaText.innerHTML = isAnswer
          ? self.params.correctAnswer
          : self.params.incorrectAnswer;

        if (scorePoints) {
          $word[0].appendChild(scorePoints.getElement(isAnswer));
        }
      }
      else if (isAnswer) {
        $word.attr('aria-describedby', Word.ID_MARK_MISSED);
        ariaText.innerHTML = self.params.missedAnswer;
      }
    };

    /**
     * Checks if the word is marked correctly.
     *
     * @public
     * @returns {Boolean} True if the marking is correct.
     */
    this.isCorrect = function () {
      return (isAnswer && this.isSelected());
    };

    /**
     * Checks if the word is marked wrong.
     *
     * @public
     * @returns {Boolean} True if the marking is wrong.
     */
    this.isWrong = function () {
      return (!isAnswer && this.isSelected());
    };

    /**
     * Checks if the word is correct, but has not been marked.
     *
     * @public
     * @returns {Boolean} True if the marking is missed.
     */
    this.isMissed = function () {
      return (isAnswer && !this.isSelected());
    };

    /**
     * Checks if the word is an answer.
     *
     * @public
     * @returns {Boolean} True if the word is an answer.
     */
    this.isAnswer = function () {
      return isAnswer;
    };

    /**
     * Checks if the word is selected.
     *
     * @public
     * @returns {Boolean} True if the word is selected.
     */
    this.isSelected = function () {
      return $word.attr('aria-selected') === 'true';
    };

    /**
     * Sets that the Word is selected
     *
     * @public
     */
    this.setSelected = function () {
      $word.attr('aria-selected', 'true');
    };
  }
  Word.prototype = Object.create(H5P.EventDispatcher.prototype);
  Word.prototype.constructor = Word;

  return Word;
})();
