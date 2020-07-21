var H5PUpgrades = H5PUpgrades || {};

H5PUpgrades['H5P.TrueFalse'] = (function () {
  return {
    1: {
      5: function (parameters, finished, extras) {
        var title;

        if (parameters && parameters.question) {
          title = parameters.question;
        }

        extras = extras || {};
        extras.metadata = extras.metadata || {};
        extras.metadata.title = (title) ? title.replace(/<[^>]*>?/g, '') : ((extras.metadata.title) ? extras.metadata.title : 'True-False');

        finished(null, parameters, extras);
      },
      /**
       * Move disableImageZooming from behaviour to media
       *
       * @param {object} parameters
       * @param {function} finished
       */
      6: function (parameters, finished) {
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
