(function () {
  if (window.__ltiProviderBridgeInstalled) {
    return;
  }

  window.__ltiProviderBridgeInstalled = true;

  var context = window.__ltiProviderContext || {};
  var token = context.token || '';
  var launchId = context.launchId || '';
  var tokenParam = 'lti_provider_token';
  var launchIdParam = 'lti_launch_id';

  function isSkippableUrl(url) {
    if (!url) {
      return true;
    }

    var lower = String(url).toLowerCase();

    return lower.indexOf('#') === 0 ||
      lower.indexOf('javascript:') === 0 ||
      lower.indexOf('mailto:') === 0 ||
      lower.indexOf('tel:') === 0 ||
      lower.indexOf('data:') === 0 ||
      lower.indexOf('blob:') === 0;
  }

  function shouldPatchUrl(url) {
    var resolved;

    if (!token || isSkippableUrl(url)) {
      return false;
    }

    try {
      resolved = new URL(url, window.location.href);
    } catch (error) {
      return false;
    }

    if (resolved.origin !== window.location.origin) {
      return false;
    }

    if (resolved.pathname.indexOf('/main/') === 0) {
      return true;
    }

    if (url.indexOf('?') === 0) {
      return true;
    }

    if (!/^[a-z]+:/i.test(url) && url.indexOf('/') !== 0) {
      return true;
    }

    return false;
  }

  function appendContext(url) {
    var resolved;

    if (!shouldPatchUrl(url)) {
      return url;
    }

    try {
      resolved = new URL(url, window.location.href);
      resolved.searchParams.set(tokenParam, token);

      if (launchId) {
        resolved.searchParams.set(launchIdParam, launchId);
      }

      return resolved.toString();
    } catch (error) {
      return url;
    }
  }

  function ensureHiddenInput(form, name, value) {
    var input;

    if (!value) {
      return;
    }

    input = form.querySelector('input[type="hidden"][name="' + name + '"]');

    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      form.appendChild(input);
    }

    input.value = value;
  }

  function patchForm(form) {
    var action;

    if (!form || form.nodeType !== 1) {
      return;
    }

    action = form.getAttribute('action') || window.location.href;
    form.setAttribute('action', appendContext(action));
    ensureHiddenInput(form, tokenParam, token);

    if (launchId) {
      ensureHiddenInput(form, launchIdParam, launchId);
    }
  }

  function patchAnchor(anchor) {
    var href;

    if (!anchor || anchor.nodeType !== 1) {
      return;
    }

    href = anchor.getAttribute('href');

    if (href) {
      anchor.setAttribute('href', appendContext(href));
    }
  }

  function patchIframe(frame) {
    var src;

    if (!frame || frame.nodeType !== 1) {
      return;
    }

    src = frame.getAttribute('src');

    if (src) {
      frame.setAttribute('src', appendContext(src));
    }
  }

  function patchNode(node) {
    if (!node || node.nodeType !== 1 || !node.matches) {
      return;
    }

    if (node.matches('a[href]')) {
      patchAnchor(node);
      return;
    }

    if (node.matches('form')) {
      patchForm(node);
      return;
    }

    if (node.matches('iframe[src]')) {
      patchIframe(node);
    }
  }

  function patchTree(root) {
    var nodes;
    var index;

    if (!root || !root.querySelectorAll) {
      return;
    }

    patchNode(root);

    nodes = root.querySelectorAll('a[href], form, iframe[src]');

    for (index = 0; index < nodes.length; index += 1) {
      patchNode(nodes[index]);
    }
  }

  function installMutationObserver() {
    var observer;

    if (!window.MutationObserver || !document.documentElement) {
      return;
    }

    observer = new MutationObserver(function (mutations) {
      var i;
      var j;
      var mutation;
      var node;

      for (i = 0; i < mutations.length; i += 1) {
        mutation = mutations[i];

        for (j = 0; j < mutation.addedNodes.length; j += 1) {
          node = mutation.addedNodes[j];

          if (node && node.nodeType === 1) {
            patchTree(node);
          }
        }
      }
    });

    observer.observe(document.documentElement, {
      childList: true,
      subtree: true
    });
  }

  function installSubmitPatch() {
    if (document.addEventListener) {
      document.addEventListener('submit', function (event) {
        if (event && event.target && event.target.tagName === 'FORM') {
          patchForm(event.target);
        }
      }, true);
    }

    if (typeof HTMLFormElement !== 'undefined' && HTMLFormElement.prototype.submit) {
      var nativeSubmit = HTMLFormElement.prototype.submit;

      HTMLFormElement.prototype.submit = function () {
        patchForm(this);

        return nativeSubmit.call(this);
      };
    }
  }

  function installFetchPatch() {
    var originalFetch;

    if (typeof window.fetch !== 'function') {
      return;
    }

    originalFetch = window.fetch;

    window.fetch = function (input, init) {
      var finalInput = input;
      var finalInit = init || {};
      var sourceHeaders;
      var headers;

      if (typeof finalInput === 'string') {
        finalInput = appendContext(finalInput);
      } else if (typeof URL !== 'undefined' && finalInput instanceof URL) {
        finalInput = appendContext(finalInput.toString());
      } else if (typeof Request !== 'undefined' && finalInput instanceof Request) {
        finalInput = new Request(appendContext(finalInput.url), finalInput);
      }

      sourceHeaders = finalInit.headers;

      if (!sourceHeaders && typeof Request !== 'undefined' && input instanceof Request) {
        sourceHeaders = input.headers;
      }

      headers = new Headers(sourceHeaders || undefined);
      headers.set('X-Lti-Provider-Token', token);

      if (launchId) {
        headers.set('X-Lti-Launch-Id', launchId);
      }

      finalInit.headers = headers;

      return originalFetch.call(window, finalInput, finalInit);
    };
  }

  function installXmlHttpRequestPatch() {
    var originalOpen;
    var originalSend;

    if (typeof XMLHttpRequest === 'undefined') {
      return;
    }

    originalOpen = XMLHttpRequest.prototype.open;
    originalSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
      var patchedUrl = typeof url === 'string' ? appendContext(url) : url;

      this.__ltiProviderToken = token;
      this.__ltiProviderLaunchId = launchId;

      return originalOpen.call(this, method, patchedUrl, async, user, password);
    };

    XMLHttpRequest.prototype.send = function (body) {
      try {
        if (this.__ltiProviderToken) {
          this.setRequestHeader('X-Lti-Provider-Token', this.__ltiProviderToken);
        }

        if (this.__ltiProviderLaunchId) {
          this.setRequestHeader('X-Lti-Launch-Id', this.__ltiProviderLaunchId);
        }
      } catch (error) {
      }

      return originalSend.call(this, body);
    };
  }

  function installJQueryPatch() {
    if (!window.jQuery || typeof window.jQuery.ajaxPrefilter !== 'function') {
      return;
    }

    window.jQuery.ajaxPrefilter(function (options) {
      if (!options) {
        return;
      }

      if (typeof options.url === 'string') {
        options.url = appendContext(options.url);
      }

      if (!options.headers) {
        options.headers = {};
      }

      options.headers['X-Lti-Provider-Token'] = token;

      if (launchId) {
        options.headers['X-Lti-Launch-Id'] = launchId;
      }
    });
  }

  function bootstrap() {
    patchTree(document);
    installMutationObserver();
    installSubmitPatch();
    installFetchPatch();
    installXmlHttpRequestPatch();
    installJQueryPatch();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
  } else {
    bootstrap();
  }
})();
