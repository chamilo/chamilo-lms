/*global H5P*/

/**
 * Mark The Words module
 * @external {jQuery} $ H5P.jQuery
 */
H5P.MarkTheWords = (function ($, Question, Word, KeyboardNav, XapiGenerator) {
  /**
   * Initialize module.
   *
   * @class H5P.MarkTheWords
   * @extends H5P.Question
   * @param {Object} params Behavior settings
   * @param {Number} contentId Content identification
   * @param {Object} contentData Object containing task specific content data
   *
   * @returns {Object} MarkTheWords Mark the words instance
   */
  function MarkTheWords(params, contentId, contentData) {
    var self = this;
    this.contentId = contentId;
    this.contentData = contentData;
    this.introductionId = 'mark-the-words-introduction-' + contentId;

    Question.call(this, 'mark-the-words');

    // Set default behavior.
    this.params = $.extend(true, {
      taskDescription: "",
      textField: "This is a *nice*, *flexible* content type.",
      overallFeedback: [],
      behaviour: {
        enableRetry: true,
        enableSolutionsButton: true,
        enableCheckButton: true,
        showScorePoints: true
      },
      checkAnswerButton: "Check",
      tryAgainButton: "Retry",
      showSolutionButton: "Show solution",
      correctAnswer: "Correct!",
      incorrectAnswer: "Incorrect!",
      missedAnswer: "Answer not found!",
      displaySolutionDescription:  "Task is updated to contain the solution.",
      scoreBarLabel: 'You got :num out of :total points',
      a11yFullTextLabel: 'Full readable text',
      a11yClickableTextLabel: 'Full text where words can be marked',
      a11ySolutionModeHeader: 'Solution mode',
      a11yCheckingHeader: 'Checking mode',
    }, params);

    this.contentData = contentData;
    if (this.contentData !== undefined && this.contentData.previousState !== undefined) {
      this.previousState = this.contentData.previousState;
    }

    this.keyboardNavigators = [];

    this.initMarkTheWords();
    this.XapiGenerator = new XapiGenerator(this);
  }

  MarkTheWords.prototype = Object.create(H5P.EventDispatcher.prototype);
  MarkTheWords.prototype.constructor = MarkTheWords;

  /**
   * Initialize Mark The Words task
   */
  MarkTheWords.prototype.initMarkTheWords = function () {
    this.$inner = $('<div class="h5p-word-inner"></div>');

    this.addTaskTo(this.$inner);

    // Set user state
    this.setH5PUserState();
  };

  /**
   * Recursive function that creates html for the words
   * @method createHtmlForWords
   * @param  {Array}           nodes Array of dom nodes
   * @return {string}
   */
  MarkTheWords.prototype.createHtmlForWords = function (nodes) {
    var self = this;
    var html = '';
    for (var i = 0; i < nodes.length; i++) {
      var node = nodes[i];

      if (node instanceof Text) {
        var text = $(node).text();
        var selectableStrings = text.replace(/(&nbsp;|\r\n|\n|\r)/g, ' ')
          .match(/ \*[^\*]+\* |[^\s]+/g);

        if (selectableStrings) {
          selectableStrings.forEach(function (entry) {
            entry = entry.trim();

            // Words
            if (html) {
              // Add space before
              html += ' ';
            }

            // Remove prefix punctuations from word
            var prefix = entry.match(/^[\[\({⟨¿¡“"«„]+/);
            var start = 0;
            if (prefix !== null) {
              start = prefix[0].length;
              html += prefix;
            }

            // Remove suffix punctuations from word
            var suffix = entry.match(/[",….:;?!\]\)}⟩»”]+$/);
            var end = entry.length - start;
            if (suffix !== null) {
              end -= suffix[0].length;
            }

            // Word
            entry = entry.substr(start, end);
            if (entry.length) {
              html += '<span role="option" aria-selected="false">' + self.escapeHTML(entry) + '</span>';
            }

            if (suffix !== null) {
              html += suffix;
            }
          });
        }
        else if ((selectableStrings !== null) && text.length) {
          html += '<span role="option" aria-selected="false">' + this.escapeHTML(text) + '</span>';
        }
      }
      else {
        if (node.nodeName === 'BR') {
          html += '<br/>';
        }
        else {
          var attributes = ' ';
          for (var j = 0; j < node.attributes.length; j++) {
            attributes +=node.attributes[j].name + '="' + node.attributes[j].nodeValue + '" ';
          }
          html += '<' + node.nodeName +  attributes + '>';
          html += self.createHtmlForWords(node.childNodes);
          html += '</' + node.nodeName + '>';
        }
      }
    }

    return html;
  };

  /**
   * Escapes HTML
   *
   * @param html
   * @returns {jQuery}
   */
  MarkTheWords.prototype.escapeHTML = function (html) {
    return $('<div>').text(html).html();
  };

  /**
   * Search for the last children in every paragraph and
   * return their indexes in an array
   *
   * @returns {Array}
   */
  MarkTheWords.prototype.getIndexesOfLineBreaks = function () {

    var indexes = [];
    var selectables = this.$wordContainer.find('span.h5p-word-selectable');

    selectables.each(function(index, selectable) {
      if ($(selectable).next().is('br')){
        indexes.push(index);
      }

      if ($(selectable).parent('p') && !$(selectable).parent().is(':last-child') && $(selectable).is(':last-child')){
        indexes.push(index);
      }
    });

    return indexes;
  };

  /**
   * Handle task and add it to container.
   * @param {jQuery} $container The object which our task will attach to.
   */
  MarkTheWords.prototype.addTaskTo = function ($container) {
    var self = this;
    self.selectableWords = [];
    self.answers = 0;

    // Wrapper
    var $wordContainer = $('<div/>', {
      'class': 'h5p-word-selectable-words',
      'aria-labelledby': self.introductionId,
      'aria-multiselectable': 'true',
      'role': 'listbox',
      html: self.createHtmlForWords($.parseHTML(self.params.textField))
    });

    let isNewParagraph = true;
    $wordContainer.find('[role="option"], br').each(function () {
      if ($(this).is('br')) {
        isNewParagraph = true;
        return;
      }

      if (isNewParagraph) {
        // Add keyboard navigation helper
        self.currentKeyboardNavigator = new KeyboardNav();

        // on word clicked
        self.currentKeyboardNavigator.on('select', function () {
          self.isAnswered = true;
          self.triggerXAPI('interacted');
        });

        self.keyboardNavigators.push(self.currentKeyboardNavigator);
        isNewParagraph = false;
      }
      self.currentKeyboardNavigator.addElement(this);

      // Add keyboard navigation to this element
      var selectableWord = new Word($(this), self.params);
      if (selectableWord.isAnswer()) {
        self.answers += 1;
      }
      self.selectableWords.push(selectableWord);
    });

    self.blankIsCorrect = (self.answers === 0);
    if (self.blankIsCorrect) {
      self.answers = 1;
    }

    // A11y full readable text
    const $ariaTextWrapper = $('<div>', {
      'class': 'hidden-but-read',
    }).appendTo($container);
    $('<div>', {
      html: self.params.a11yFullTextLabel,
    }).appendTo($ariaTextWrapper);

    // Add space after each paragraph to read the sentences better
    const ariaText = $('<div>', {
      'html': $wordContainer.html().replace('</p>', ' </p>'),
    }).text();

    $('<div>', {
      text: ariaText,
    }).appendTo($ariaTextWrapper);

    // A11y clickable list label
    this.$a11yClickableTextLabel = $('<div>', {
      'class': 'hidden-but-read',
      html: self.params.a11yClickableTextLabel,
      tabIndex: '-1',
    }).appendTo($container);

    $wordContainer.appendTo($container);
    self.$wordContainer = $wordContainer;
  };

  /**
   * Add check solution and retry buttons.
   */
  MarkTheWords.prototype.addButtons = function () {
    var self = this;
    self.$buttonContainer = $('<div/>', {
      'class': 'h5p-button-bar'
    });

    if (this.params.behaviour.enableCheckButton) {
      this.addButton('check-answer', this.params.checkAnswerButton, function () {
        self.isAnswered = true;
        var answers = self.calculateScore();
        self.feedbackSelectedWords();

        if (!self.showEvaluation(answers)) {
          // Only show if a correct answer was not found.
          if (self.params.behaviour.enableSolutionsButton && (answers.correct < self.answers)) {
            self.showButton('show-solution');
          }
          if (self.params.behaviour.enableRetry) {
            self.showButton('try-again');
          }
        }
        // Set focus to start of text
        self.$a11yClickableTextLabel.html(self.params.a11yCheckingHeader + ' - ' + self.params.a11yClickableTextLabel);
        self.$a11yClickableTextLabel.focus();

        self.hideButton('check-answer');
        self.trigger(self.XapiGenerator.generateAnsweredEvent());
        self.toggleSelectable(true);
      });
    }

    this.addButton('try-again', this.params.tryAgainButton, this.resetTask.bind(this), false);

    this.addButton('show-solution', this.params.showSolutionButton, function () {
      self.setAllMarks();

      self.$a11yClickableTextLabel.html(self.params.a11ySolutionModeHeader + ' - ' + self.params.a11yClickableTextLabel);
      self.$a11yClickableTextLabel.focus();

      if (self.params.behaviour.enableRetry) {
        self.showButton('try-again');
      }
      self.hideButton('check-answer');
      self.hideButton('show-solution');

      self.read(self.params.displaySolutionDescription);
      self.toggleSelectable(true);
    }, false);
  };

  /**
   * Toggle whether words can be selected
   * @param {Boolean} disable
   */
  MarkTheWords.prototype.toggleSelectable = function (disable) {
    this.keyboardNavigators.forEach(function (navigator) {
      if (disable) {
        navigator.disableSelectability();
        navigator.removeAllTabbable();
      }
      else {
        navigator.enableSelectability();
        navigator.setTabbableAt((0));
      }
    });

    if (disable) {
      this.$wordContainer.removeAttr('aria-multiselectable').removeAttr('role');
    }
    else {
      this.$wordContainer.attr('aria-multiselectable', 'true')
        .attr('role', 'listbox');
    }
  };

  /**
   * Get Xapi Data.
   *
   * @see used in contracts {@link https://h5p.org/documentation/developers/contracts#guides-header-6}
   * @return {Object}
   */
  MarkTheWords.prototype.getXAPIData = function () {
    return {
      statement: this.XapiGenerator.generateAnsweredEvent().data.statement
    };
  };

  /**
   * Mark the words as correct, wrong or missed.
   *
   * @fires MarkTheWords#resize
   */
  MarkTheWords.prototype.setAllMarks = function () {
    this.selectableWords.forEach(function (entry) {
      entry.markCheck();
      entry.clearScorePoint();
    });

    /**
     * Resize event
     *
     * @event MarkTheWords#resize
     */
    this.trigger('resize');
  };

  /**
   * Mark the selected words as correct or wrong.
   *
   * @fires MarkTheWords#resize
   */
  MarkTheWords.prototype.feedbackSelectedWords = function () {
    var self = this;

    var scorePoints;
    if (self.params.behaviour.showScorePoints) {
      scorePoints = new H5P.Question.ScorePoints();
    }

    this.selectableWords.forEach(function (entry) {
      if (entry.isSelected()) {
        entry.markCheck(scorePoints);
      }
    });

    this.$wordContainer.addClass('h5p-disable-hover');
    this.trigger('resize');
  };

  /**
   * Evaluate task and display score text for word markings.
   *
   * @fires MarkTheWords#resize
   * @return {Boolean} Returns true if maxScore was achieved.
   */
  MarkTheWords.prototype.showEvaluation = function (answers) {
    this.hideEvaluation();
    var score = answers.score;

    //replace editor variables with values, uses regexp to replace all instances.
    var scoreText = H5P.Question.determineOverallFeedback(this.params.overallFeedback, score / this.answers).replace(/@score/g, score.toString())
      .replace(/@total/g, this.answers.toString())
      .replace(/@correct/g, answers.correct.toString())
      .replace(/@wrong/g, answers.wrong.toString())
      .replace(/@missed/g, answers.missed.toString());

    this.setFeedback(scoreText, score, this.answers, this.params.scoreBarLabel);

    this.trigger('resize');
    return score === this.answers;
  };

  /**
   * Clear the evaluation text.
   *
   * @fires MarkTheWords#resize
   */
  MarkTheWords.prototype.hideEvaluation = function () {
    this.removeFeedback();
    this.trigger('resize');
  };

  /**
   * Calculate the score.
   *
   * @return {Answers}
   */
  MarkTheWords.prototype.calculateScore = function () {
    var self = this;

    /**
     * @typedef {Object} Answers
     * @property {number} correct The number of correct answers
     * @property {number} wrong The number of wrong answers
     * @property {number} missed The number of answers the user missed
     * @property {number} score The calculated score
     */
    var initial = {
      correct: 0,
      wrong: 0,
      missed: 0,
      score: 0
    };

    // iterate over words, and calculate score
    var answers = self.selectableWords.reduce(function (result, word) {
      if (word.isCorrect()) {
        result.correct++;
      }
      else if (word.isWrong()) {
        result.wrong++;
      }
      else if (word.isMissed()) {
        result.missed++;
      }

      return result;
    }, initial);

    // if no wrong answers, and black is correct
    if (answers.wrong === 0 && self.blankIsCorrect) {
      answers.correct = 1;
    }

    // no negative score
    answers.score = Math.max(answers.correct - answers.wrong, 0);

    return answers;
  };

  /**
   * Clear styling on marked words.
   *
   * @fires MarkTheWords#resize
   */
  MarkTheWords.prototype.clearAllMarks = function () {
    this.selectableWords.forEach(function (entry) {
      entry.markClear();
    });

    this.$wordContainer.removeClass('h5p-disable-hover');
    this.trigger('resize');
  };

  /**
   * Returns true if task is checked or a word has been clicked
   *
   * @see {@link https://h5p.org/documentation/developers/contracts|Needed for contracts.}
   * @returns {Boolean} Always returns true.
   */
  MarkTheWords.prototype.getAnswerGiven = function () {
    return this.blankIsCorrect ? true : this.isAnswered;
  };

  /**
   * Counts the score, which is correct answers subtracted by wrong answers.
   *
   * @see {@link https://h5p.org/documentation/developers/contracts|Needed for contracts.}
   * @returns {Number} score The amount of points achieved.
   */
  MarkTheWords.prototype.getScore = function () {
    return this.calculateScore().score;
  };

  /**
   * Gets max score for this task.
   *
   * @see {@link https://h5p.org/documentation/developers/contracts|Needed for contracts.}
   * @returns {Number} maxScore The maximum amount of points achievable.
   */
  MarkTheWords.prototype.getMaxScore = function () {
    return this.answers;
  };

  /**
   * Get title
   * @returns {string}
   */
  MarkTheWords.prototype.getTitle = function () {
    return H5P.createTitle((this.contentData && this.contentData.metadata && this.contentData.metadata.title) ? this.contentData.metadata.title : 'Mark the Words');
  };

  /**
   * Display the evaluation of the task, with proper markings.
   *
   * @fires MarkTheWords#resize
   * @see {@link https://h5p.org/documentation/developers/contracts|Needed for contracts.}
   */
  MarkTheWords.prototype.showSolutions = function () {
    var answers = this.calculateScore();
    this.showEvaluation(answers);
    this.setAllMarks();
    this.read(this.params.displaySolutionDescription);
    this.hideButton('try-again');
    this.hideButton('show-solution');
    this.hideButton('check-answer');
    this.$a11yClickableTextLabel.html(this.params.a11ySolutionModeHeader + ' - ' + this.params.a11yClickableTextLabel);

    this.toggleSelectable(true);
    this.trigger('resize');
  };

  /**
   * Resets the task back to its' initial state.
   *
   * @fires MarkTheWords#resize
   * @see {@link https://h5p.org/documentation/developers/contracts|Needed for contracts.}
   */
  MarkTheWords.prototype.resetTask = function () {
    this.isAnswered = false;
    this.clearAllMarks();
    this.hideEvaluation();
    this.hideButton('try-again');
    this.hideButton('show-solution');
    this.showButton('check-answer');
    this.$a11yClickableTextLabel.html(this.params.a11yClickableTextLabel);

    this.toggleSelectable(false);
    this.trigger('resize');
  };

  /**
   * Returns an object containing the selected words
   *
   * @public
   * @returns {object} containing indexes of selected words
   */
  MarkTheWords.prototype.getCurrentState = function () {
    var selectedWordsIndexes = [];
    if (this.selectableWords === undefined) {
      return undefined;
    }

    this.selectableWords.forEach(function (selectableWord, swIndex) {
      if (selectableWord.isSelected()) {
        selectedWordsIndexes.push(swIndex);
      }
    });
    return selectedWordsIndexes;
  };

  /**
   * Sets answers to current user state
   */
  MarkTheWords.prototype.setH5PUserState = function () {
    var self = this;

    // Do nothing if user state is undefined
    if (this.previousState === undefined || this.previousState.length === undefined) {
      return;
    }

    // Select words from user state
    this.previousState.forEach(function (answeredWordIndex) {
      if (isNaN(answeredWordIndex) || answeredWordIndex >= self.selectableWords.length || answeredWordIndex < 0) {
        throw new Error('Stored user state is invalid');
      }
      self.selectableWords[answeredWordIndex].setSelected();
    });
  };

  /**
   * Register dom elements
   *
   * @see {@link https://github.com/h5p/h5p-question/blob/1558b6144333a431dd71e61c7021d0126b18e252/scripts/question.js#L1236|Called from H5P.Question}
   */
  MarkTheWords.prototype.registerDomElements = function () {
    // wrap introduction in div with id
    var introduction = '<div id="' + this.introductionId + '">' + this.params.taskDescription + '</div>';

    // Register description
    this.setIntroduction(introduction);

    // creates aria descriptions for correct/incorrect/missed
    this.createDescriptionsDom().appendTo(this.$inner);

    // Register content
    this.setContent(this.$inner, {
      'class': 'h5p-word'
    });

    // Register buttons
    this.addButtons();
  };

  /**
   * Creates dom with description to be used with aria-describedby
   * @return {jQuery}
   */
  MarkTheWords.prototype.createDescriptionsDom = function () {
    var self = this;
    var $el = $('<div class="h5p-mark-the-words-descriptions"></div>');

    $('<div id="' + Word.ID_MARK_CORRECT + '">' + self.params.correctAnswer + '</div>').appendTo($el);
    $('<div id="' + Word.ID_MARK_INCORRECT + '">' + self.params.incorrectAnswer + '</div>').appendTo($el);
    $('<div id="' + Word.ID_MARK_MISSED + '">' + self.params.missedAnswer + '</div>').appendTo($el);

    return $el;
  };

  return MarkTheWords;
}(H5P.jQuery, H5P.Question, H5P.MarkTheWords.Word, H5P.KeyboardNav, H5P.MarkTheWords.XapiGenerator));
