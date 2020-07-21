/** @namespace H5PUpgrades */
var H5PUpgrades = H5PUpgrades || {};

H5PUpgrades['H5P.Image'] = (function () {
  return {
    1: {
      1: function (parameters, finished, extras) {
        // Use new copyright information if available. Fallback to old.
        var copyright;
        if (parameters.file && parameters.file.copyright !== undefined) {
          copyright = parameters.file.copyright;
        }
        else if (parameters && parameters.copyright !== undefined) {
          copyright = parameters.copyright;
        }

        if (copyright) {
          var years = [];
          if (copyright.year) {
            // Try to find start and end year
            years = copyright.year
              .replace(' ', '')
              .replace('--', '-') // Try to check for LaTeX notation
              .split('-');
          }
          var yearFrom = (years.length > 0) ? new Date(years[0]).getFullYear() : undefined;
          var yearTo = (years.length > 0) ? new Date(years[1]).getFullYear() : undefined;

          // Build metadata object
          var metadata = {
            title: copyright.title,
            authors: (copyright.author) ? [{name: copyright.author, role: 'Author'}] : undefined,
            source: copyright.source,
            yearFrom: isNaN(yearFrom) ? undefined : yearFrom,
            yearTo: isNaN(yearTo) ? undefined : yearTo,
            license: copyright.license,
            licenseVersion: copyright.version
          };

          extras = extras || {};
          extras.metadata = metadata;

          if (parameters.file) {
            delete parameters.file.copyright;
          }
          delete parameters.copyright;
        }

        // Done
        finished(null, parameters, extras);
      }
    }
  };
})();
