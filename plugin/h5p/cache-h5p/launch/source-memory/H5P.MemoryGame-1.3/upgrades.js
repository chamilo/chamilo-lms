var H5PUpgrades = H5PUpgrades || {};

H5PUpgrades['H5P.MemoryGame'] = (function () {
  return {
    1: {
      /**
       * Asynchronous content upgrade hook.
       * Upgrades content parameters to support Memory Game 1.1.
       *
       * Move card images into card object as this allows for additonal
       * properties for each card.
       *
       * @params {object} parameters
       * @params {function} finished
       */
      1: function (parameters, finished) {
        for (var i = 0; i < parameters.cards.length; i++) {
          parameters.cards[i] = {
            image: parameters.cards[i]
          };
        }

        finished(null, parameters);
      },

      /**
       * Asynchronous content upgrade hook.
       * Upgrades content parameters to support Memory Game 1.2.
       *
       * Add default behavioural settings for the new options.
       *
       * @params {object} parameters
       * @params {function} finished
       */
      2: function (parameters, finished) {

        parameters.behaviour = {};
        parameters.behaviour.useGrid = false;
        parameters.behaviour.allowRetry = false;

        finished(null, parameters);
      }
    }
  };
})();
