var H5PUpgrades = H5PUpgrades || {};

H5PUpgrades['H5P.MarkTheWords'] = (function () {
  return {
    1: {
      1: {
        contentUpgrade: function (parameters, finished) {
          // Moved all behavioural settings into "behaviour" group.
          parameters.behaviour = {
            enableRetry: parameters.enableRetry === undefined ? true: parameters.enableRetry,
            enableSolutionsButton: parameters.enableShowSolution === undefined ? true : parameters.enableShowSolution
          };
          delete parameters.enableRetry;
          delete parameters.enableShowSolution;
          finished(null, parameters);
        }
      },
      5: {
        contentUpgrade: function (parameters, finished) {
          if (parameters.textField !== undefined) {
            parameters.textField = parameters.textField.replace(/\n/g, "<br />");
          }
          finished(null, parameters);
        }
      },

      /**
       * Asynchronous content upgrade hook.
       * Upgrades content parameters to support Mark the Words 1.7
       *
       * Move old feedback message to the new overall feedback system.
       * Do not show the new score points for old content being upgraded.
       *
       * @param {object} parameters
       * @param {function} finished
       */
      7: function (parameters, finished) {
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
      9: function (parameters, finished, extras) {
        var title;

        if (parameters) {
          title = parameters.taskDescription;
        }

        extras = extras || {};
        extras.metadata = extras.metadata || {};
        extras.metadata.title = (title) ? title.replace(/<[^>]*>?/g, '') : ((extras.metadata.title) ? extras.metadata.title : 'Mark the Words');

        finished(null, parameters, extras);
      }
    }
  };
})();
