var H5PUpgrades = H5PUpgrades || {};

H5PUpgrades['H5P.Blanks'] = (function () {
  return {
    1: {
      1: {
        contentUpgrade: function (parameters, finished) {
          // Moved all behavioural settings into "behaviour" group.
          parameters.behaviour = {
            enableRetry: parameters.enableTryAgain === undefined ? true : parameters.enableRetry,
            enableSolutionsButton: true,
            autoCheck: parameters.autoCheck === undefined ? false : parameters.autoCheck,
            caseSensitive: parameters.caseSensitive === undefined ? true : parameters.caseSensitive,
            showSolutionsRequiresInput: parameters.showSolutionsRequiresInput === undefined ? true : parameters.showSolutionsRequiresInput,
            separateLines: parameters.separateLines === undefined ? false : parameters.separateLines
          };
          delete parameters.enableTryAgain;
          delete parameters.enableShowSolution;
          delete parameters.autoCheck;
          delete parameters.caseSensitive;
          delete parameters.showSolutionsRequiresInput;
          delete parameters.separateLines;
          delete parameters.changeAnswer;

          finished(null, parameters);
        }
      },

      /**
       * Asynchronous content upgrade hook.
       * Upgrades content parameters to support Blanks 1.5.
       *
       * Converts task image into media object, adding support for video.
       *
       * @params {Object} parameters
       * @params {function} finished
       */
      5: function (parameters, finished) {

        if (parameters.image) {
          // Convert image field to media field
          parameters.media = {
            library: 'H5P.Image 1.0',
            params: {
              file: parameters.image
            }
          };

          // Remove old image field
          delete parameters.image;
        }

        // Done
        finished(null, parameters);
      },

      /**
       * Asynchronous content upgrade hook.
       * Upgrades content parameters to support Blanks 1.8
       *
       * Move old feedback message to the new overall feedback system.
       *
       * @param {object} parameters
       * @param {function} finished
       */
      8: function (parameters, finished) {
        if (parameters && parameters.score) {
          parameters.overallFeedback = [
            {
              'from': 0,
              'to': 100,
              'feedback': parameters.score
            }
          ];

          delete parameters.score;
        }

        finished(null, parameters);
      },

      /**
       * Asynchronous content upgrade hook.
       *
       * @param {[type]} parameters
       * @param {[type]} finished
       * @param {[type]} extras
       * @return {[type]}
       */
      11: function (parameters, finished, extras) {
        // Move value from getTitle() to metadata title
        if (parameters && parameters.text) {
          extras = extras || {};
          extras.metadata = extras.metadata || {};
          extras.metadata.title = parameters.text.replace(/<[^>]*>?/g, '');
        }
        finished(null, parameters, extras);
      },
      /*
       * Upgrades content parameters to support Blanks 1.9
       *
       * Move disableImageZooming from behaviour to media
       *
       * @param {object} parameters
       * @param {function} finished
       */
      12: function (parameters, finished) {
      // If image has been used, move it down in the hierarchy and add disableImageZooming
        if (parameters && parameters.media) {
          parameters.media = {
            type: parameters.media,
            disableImageZooming: (parameters.behaviour && parameters.behaviour.disableImageZooming) ? parameters.behaviour.disableImageZooming : false
          };
        }

        // Delete old disableImageZooming
        if (parameters && parameters.behaviour) {
          delete parameters.behaviour.disableImageZooming;
        }
        finished(null, parameters);
      }
    }
  };
})();
