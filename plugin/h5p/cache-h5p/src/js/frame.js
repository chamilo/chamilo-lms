import H5P from 'imports-loader?H5PIntegration=>window.parent.H5PIntegration!H5P';
import 'H5PEventDispatcher';
import 'H5PxAPI';
import 'H5PxAPIEvent';
import 'H5PContentType';
import 'H5PConfirmationDialog';
import 'H5PRequestQueue';
import 'H5PActionBar';

H5P.getLibraryPath = function (library) {
  if (H5PIntegration.pathIncludesVersion) {
    return this.librariesPath + '/' + library;
  }
  return this.librariesPath + '/' + library.split('-')[0];
};

H5P.getPath = function (path, contentId) {
  var hasProtocol = function (path) {
    return path.match(/^[a-z0-9]+:\/\//i);
  };

  if (hasProtocol(path)) {
    return path;
  }

  var prefix;
  if (contentId !== undefined) {
    prefix = H5PIntegration.url + '/content';
  }
  else if (window.H5PEditor !== undefined) {
    prefix = H5PEditor.filesPath;
  }
  else {
    return;
  }

  return prefix + '/' + path;
};
