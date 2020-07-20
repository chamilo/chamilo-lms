H5P.TrueFalse.Answer = (function ($, EventDispatcher) {
  'use strict';

  var Keys = {
    ENTER: 13,
    SPACE: 32,
    LEFT_ARROW: 37,
    UP_ARROW: 38,
    RIGHT_ARROW: 39,
    DOWN_ARROW: 40
  };

  /**
   * Initialize module.
   *
   * @class H5P.TrueFalse.Answer
   * @extends H5P.EventDispatcher
   * @param {String} text Label
   * @param {String} correctMessage Message read by readspeaker when correct alternative is chosen
   * @param {String} wrongMessage Message read by readspeaker when wrong alternative is chosen
   */
  function Answer (text, correctMessage, wrongMessage) {
    var self = this;

    EventDispatcher.call(self);

    var checked = false;
    var enabled = true;

    var $answer = $('<div>', {
      'class': 'h5p-true-false-answer',
      role: 'radio',
      'aria-checked': false,
      html: text + '<span class="aria-label"></span>',
      tabindex: 0, // Tabable by default
      click: function (event) {
        // Handle left mouse (or tap on touch devices)
        if (event.which === 1) {
          self.check();
        }
      },
      keydown: function (event) {
        if (!enabled) {
          return;
        }
        if ([Keys.SPACE, Keys.ENTER].indexOf(event.keyCode) !== -1) {
          self.check();
        }
        else if ([Keys.LEFT_ARROW, Keys.UP_ARROW, Keys.RIGHT_ARROW, Keys.DOWN_ARROW].indexOf(event.keyCode) !== -1) {
          self.uncheck();
          self.trigger('invert');
        }
      },
      focus: function () {
        self.trigger('focus');
      },
      blur: function () {
        self.trigger('blur');
      }
    });

    var $ariaLabel = $answer.find('.aria-label');

    // A bug in Chrome 54 makes the :after icons (V and X) not beeing rendered.
    // Doing this in a timeout solves this
    // Might be removed when Chrome 56 is out
    var chromeBugFixer = function (callback) {
      setTimeout(function () {
        callback();
      }, 0);
    };

    /**
     * Return the dom element representing the alternative
     *
     * @public
     * @method getDomElement
     * @return {H5P.jQuery}
     */
    self.getDomElement = function () {
      return $answer;
    };

    /**
     * Unchecks the alternative
     *
     * @public
     * @method uncheck
     * @return {H5P.TrueFalse.Answer}
     */
    self.uncheck = function () {
      if (enabled) {
        $answer.blur();
        checked = false;
        chromeBugFixer(function () {
          $answer.attr('aria-checked', checked);
        });
      }
      return self;
    };

    /**
     * Set tabable or not
     * @method tabable
     * @param  {Boolean} enabled
     * @return {H5P.TrueFalse.Answer}
     */
    self.tabable = function (enabled) {
      $answer.attr('tabIndex', enabled ? 0 : null);
      return self;
    };

    /**
     * Checks the alternative
     *
     * @method check
     * @return {H5P.TrueFalse.Answer}
     */
    self.check = function () {
      if (enabled) {
        checked = true;
        chromeBugFixer(function () {
          $answer.attr('aria-checked', checked);
        });
        self.trigger('checked');
        $answer.focus();
      }
      return self;
    };

    /**
     * Is this alternative checked?
     *
     * @method isChecked
     * @return {boolean}
     */
    self.isChecked = function () {
      return checked;
    };

    /**
     * Enable alternative
     *
     * @method enable
     * @return {H5P.TrueFalse.Answer}
     */
    self.enable = function () {
      $answer.attr({
        'aria-disabled': '',
        tabIndex: 0
      });
      enabled = true;

      return self;
    };

    /**
     * Disables alternative
     *
     * @method disable
     * @return {H5P.TrueFalse.Answer}
     */
    self.disable = function () {
      $answer.attr({
        'aria-disabled': true,
        tabIndex: null
      });
      enabled = false;

      return self;
    };

    /**
     * Reset alternative
     *
     * @method reset
     * @return {H5P.TrueFalse.Answer}
     */
    self.reset = function () {
      self.enable();
      self.uncheck();
      self.unmark();
      $ariaLabel.html('');

      return self;
    };

    /**
     * Marks this alternative as the wrong one
     *
     * @method markWrong
     * @return {H5P.TrueFalse.Answer}
     */
    self.markWrong = function () {
      chromeBugFixer(function () {
        $answer.addClass('wrong');
      });
      $ariaLabel.html('.' + wrongMessage);

      return self;
    };

    /**
     * Marks this alternative as the wrong one
     *
     * @method markCorrect
     * @return {H5P.TrueFalse.Answer}
     */
    self.markCorrect = function () {
      chromeBugFixer(function () {
        $answer.addClass('correct');
      });
      $ariaLabel.html('.' + correctMessage);

      return self;
    };

    self.unmark = function () {
      chromeBugFixer(function () {
        $answer.removeClass('wrong correct');
      });

      return self;
    };
  }

  // Inheritance
  Answer.prototype = Object.create(EventDispatcher.prototype);
  Answer.prototype.constructor = Answer;

  return Answer;

})(H5P.jQuery, H5P.EventDispatcher);
