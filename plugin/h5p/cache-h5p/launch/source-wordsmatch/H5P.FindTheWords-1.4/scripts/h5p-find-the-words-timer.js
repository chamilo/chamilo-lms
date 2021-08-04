(function (FindTheWords, Timer) {

  /**
   * FindTheWords.Timer - Adapter between Find the words and H5P.Timer.
   * @class H5P.FindTheWords.Timer
   * @extends H5P.Timer
   * @param {H5P.jQuery} $element
   */
  FindTheWords.Timer = function ($element) {
    /** @alias H5P.FindTheWords.Timer# */
    const that = this;
    // Initialize event inheritance
    Timer.call(that, 100);

    /** @private {string} */
    const naturalState = '0:00';

    /**
     * update - Set up callback for time updates.
     * Formats time stamp for humans.
     *
     * @private
     */
    const update = function () {
      const time = that.getTime();

      const minutes = Timer.extractTimeElement(time, 'minutes');
      let seconds = Timer.extractTimeElement(time, 'seconds') % 60;
      if (seconds < 10) {
        seconds = '0' + seconds;
      }
      $element.text(minutes + ':' + seconds);
    };

    // Setup default behavior
    that.notify('every_tenth_second', update);
    that.on('reset', function () {
      $element.text(naturalState);
      that.notify('every_tenth_second', update);
    });
  };

  // Inheritance
  FindTheWords.Timer.prototype = Object.create(Timer.prototype);
  FindTheWords.Timer.prototype.constructor = FindTheWords.Timer;

}) (H5P.FindTheWords, H5P.Timer);
