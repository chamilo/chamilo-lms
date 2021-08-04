(function (FindTheWords) {
  /**
   * Keeps track of the number of times the game is submitted.
   * @class H5P.FindTheWords.Counter
   * @param {H5P.jQuery} $container
   */

  FindTheWords.Counter = function ($container) {
    /** @alias H5P.FindTheWords.Counter# */
    var self = this;
    var current = 0;
    /**
     * @private
     */
    var update = function () {
      $container[0].innerText = current;
    };

    /**
     * Increment the counter.
     */
    self.increment = function () {
      current++;
      update();
    };
    /**
     * Revert counter back to its natural state
     */
    self.reset = function () {
      current = 0;
      update();
    };
  };

})(H5P.FindTheWords);
