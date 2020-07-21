/** @namespace H5PUpgrades */
var H5PUpgrades = H5PUpgrades || {};

H5PUpgrades['H5P.GuessTheAnswer'] = (function () {
  /**
   * Generates a new UUID
   *
   * @return {string}
   */
  var generateUUID = function() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(char) {
      var random = Math.random()*16|0, newChar = char === 'x' ? random : (random&0x3|0x8);
      return newChar.toString(16);
    });
  };

  return {
    1: {
      /**
       * Asynchronous content upgrade hook.
       * Upgrades content parameters to support Guess the Answer 1.2.
       *
       * Replace the image with optional media (both image and video)
       *
       * @params {Object} parameters
       * @params {function} finished
       */
      2: function (parameters, finished) {
        if (parameters.solutionImage) {
          parameters.media = {
            library: 'H5P.Image 1.0',
            subContentId: generateUUID(),
            params: {
              contentName: 'Image',
              file: parameters.solutionImage,
              alt:'',
              title: ''
            }
          };

          delete parameters.solutionImage
        }

        finished(null, parameters);
      }
    }
  };
})();
