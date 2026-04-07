{% if show_tour is defined and show_tour %}
<div id="ch-tour-root"></div>
<script>
  (function () {
    if (window.__ChamiloTourBooted) {
      return;
    }
    window.__ChamiloTourBooted = true;

    const TOUR_ROOT_ID = 'ch-tour-root';
    const injectedSecurityToken = "{{ tour_security_token|default('')|e('js') }}";
    const injectedStepsAjaxUrl = "{{ web_path.steps_ajax|default('')|e('js') }}";
    const injectedSaveAjaxUrl = "{{ web_path.save_ajax|default('')|e('js') }}";
    const injectedIntroCssUrl = "{{ web_path.intro_css|default('')|e('js') }}";
    const injectedIntroThemeCssUrl = "{{ web_path.intro_theme_css|default('')|e('js') }}";
    const injectedIntroJsUrl = "{{ web_path.intro_js|default('')|e('js') }}";

    const TOUR_CONFIG = {
      introCss: injectedIntroCssUrl,
      introThemeCss: injectedIntroThemeCssUrl,
      introJs: injectedIntroJsUrl,
      stepsAjax: injectedStepsAjaxUrl,
      saveAjax: injectedSaveAjaxUrl
    };

    let cachedSteps = [];
    let cachedPageClass = null;
    let lastEvaluationKey = null;
    let reinitTimer = null;
    let badgeEnforceTimer = null;
    let badgeObserver = null;
    const pageStepCache = new Map();
    const pendingStepRequests = new Map();
    const lastForceRefreshAt = new Map();
    const FORCE_REFRESH_THROTTLE_MS = 1500;

    function getSecurityToken() {
      if (injectedSecurityToken && injectedSecurityToken.indexOf('a') !== 0) {
        return injectedSecurityToken;
      }

      const hiddenInput = document.querySelector('input[name="sec_token"]');

      if (hiddenInput && hiddenInput.value) {
        return hiddenInput.value;
      }

      const bodyToken = document.body ? document.body.getAttribute('data-sec-token') : '';

      return bodyToken || '';
    }

    function getRoot() {
      let root = document.getElementById(TOUR_ROOT_ID);

      if (!root) {
        root = document.createElement('div');
        root.id = TOUR_ROOT_ID;
        document.body.appendChild(root);
      } else if (root.parentNode !== document.body) {
        document.body.appendChild(root);
      }

      return root;
    }

    function getCurrentPageClass() {
      const body = document.body;

      if (!body) {
        return null;
      }

      const classes = Array.from(body.classList).filter(function (cls) {
        return cls.indexOf('page-') === 0;
      });

      if (!classes.length) {
        return null;
      }

      return 'body.' + classes.join('.');
    }

    function getEvaluationKey() {
      const pageClass = getCurrentPageClass();
      const path = window.location.pathname + window.location.search + window.location.hash;

      return JSON.stringify({
        path: path,
        pageClass: pageClass
      });
    }

    function loadCssOnce(href, key) {
      if (!href) {
        return;
      }

      if (document.querySelector('link[' + key + '="1"]')) {
        return;
      }

      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = href;
      link.setAttribute(key, '1');
      document.head.appendChild(link);
    }

    function loadIntroJs(callback) {
      if (window.introJs) {
        callback();
        return;
      }

      if (!TOUR_CONFIG.introCss || !TOUR_CONFIG.introJs) {
        callback();
        return;
      }

      loadCssOnce(TOUR_CONFIG.introCss, 'data-tour-intro-css');

      if (TOUR_CONFIG.introThemeCss) {
        loadCssOnce(TOUR_CONFIG.introThemeCss, 'data-tour-intro-theme-css');
      }

      const existingScript = document.querySelector('script[data-tour-intro-js="1"]');

      if (existingScript) {
        if (window.introJs) {
          callback();
          return;
        }

        existingScript.addEventListener('load', callback, { once: true });
        return;
      }

      const script = document.createElement('script');
      script.src = TOUR_CONFIG.introJs;
      script.setAttribute('data-tour-intro-js', '1');
      script.onload = callback;
      document.body.appendChild(script);
    }

    function fetchSteps(pageClass) {
      if (!TOUR_CONFIG.stepsAjax || !pageClass) {
        return Promise.resolve([]);
      }

      if (pageStepCache.has(pageClass)) {
        return Promise.resolve(pageStepCache.get(pageClass));
      }

      if (pendingStepRequests.has(pageClass)) {
        return pendingStepRequests.get(pageClass);
      }

      const request = fetch(TOUR_CONFIG.stepsAjax + '?page=' + encodeURIComponent(pageClass), {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (response) {
          return response.ok ? response.json() : [];
        })
        .then(function (steps) {
          const normalized = Array.isArray(steps) ? steps : [];
          pageStepCache.set(pageClass, normalized);
          pendingStepRequests.delete(pageClass);
          return normalized;
        })
        .catch(function () {
          pendingStepRequests.delete(pageClass);
          return [];
        });

      pendingStepRequests.set(pageClass, request);

      return request;
    }

    function filterValidSteps(steps) {
      if (!Array.isArray(steps)) {
        return [];
      }

      return steps.filter(function (step) {
        if (!step || typeof step !== 'object') {
          return false;
        }

        if (!step.element) {
          return true;
        }

        const element = document.querySelector(step.element);

        if (!element) {
          return false;
        }

        const rect = element.getBoundingClientRect();

        if (rect.width <= 0 || rect.height <= 0) {
          return false;
        }

        const style = window.getComputedStyle(element);

        if (style.display === 'none' || style.visibility === 'hidden') {
          return false;
        }

        return true;
      });
    }

    function saveTour(pageClass) {
      const securityToken = getSecurityToken();

      if (!TOUR_CONFIG.saveAjax || !securityToken) {
        return;
      }

      const params = new URLSearchParams();
      params.append('page_class', pageClass);
      params.append('sec_token', securityToken);

      fetch(TOUR_CONFIG.saveAjax, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Charset': 'UTF-8',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: params.toString()
      }).catch(function () {
        return null;
      });
    }

    function applyTourStepBadgeStyle() {
      const badge = document.querySelector('.introjs-helperNumberLayer');

      if (!badge) {
        return false;
      }

      badge.textContent = (badge.textContent || '').trim();

      badge.setAttribute(
        'style',
        [
          'position: absolute !important',
          'top: -18px !important',
          'left: -18px !important',
          'width: 40px !important',
          'min-width: 40px !important',
          'height: 40px !important',
          'margin: 0 !important',
          'padding: 0 !important',
          'display: flex !important',
          'align-items: center !important',
          'justify-content: center !important',
          'border-radius: 9999px !important',
          'background: #dc2626 !important',
          'color: #ffffff !important',
          'border: 2px solid #ffffff !important',
          'font-family: Arial, Verdana, Tahoma, sans-serif !important',
          'font-size: 15px !important',
          'font-weight: 700 !important',
          'line-height: 1 !important',
          'text-align: center !important',
          'text-indent: 0 !important',
          'text-shadow: none !important',
          'box-shadow: 0 8px 20px rgba(0, 0, 0, 0.28) !important',
          'box-sizing: border-box !important',
          'z-index: 999999 !important'
        ].join('; ')
      );

      return true;
    }

    function stopTourBadgeEnforcement() {
      if (badgeEnforceTimer) {
        window.clearInterval(badgeEnforceTimer);
        badgeEnforceTimer = null;
      }

      if (badgeObserver) {
        badgeObserver.disconnect();
        badgeObserver = null;
      }
    }

    function enforceTourStepBadgeStyle() {
      stopTourBadgeEnforcement();
      applyTourStepBadgeStyle();

      let attempts = 0;

      badgeEnforceTimer = window.setInterval(function () {
        attempts += 1;
        applyTourStepBadgeStyle();

        if (attempts >= 40) {
          window.clearInterval(badgeEnforceTimer);
          badgeEnforceTimer = null;
        }
      }, 50);

      if (typeof MutationObserver !== 'undefined' && document.body) {
        badgeObserver = new MutationObserver(function () {
          applyTourStepBadgeStyle();
        });

        badgeObserver.observe(document.body, {
          childList: true,
          subtree: true
        });
      }
    }

    function startTour() {
      return new Promise(function (resolve) {
        const pageClass = getCurrentPageClass();

        if (!pageClass) {
          resolve(false);
          return;
        }

        const validSteps = filterValidSteps(cachedSteps);

        if (!validSteps.length) {
          resolve(false);
          return;
        }

        loadIntroJs(function () {
          if (!window.introJs) {
            resolve(false);
            return;
          }

          const intro = window.introJs();

          intro.setOptions({
            steps: validSteps,
            overlayOpacity: 0.34,
            exitOnOverlayClick: true,
            showBullets: true,
            showProgress: true,
            scrollToElement: true,
            disableInteraction: false,
            nextLabel: 'Next →',
            prevLabel: '← Back',
            doneLabel: 'Finish',
            skipLabel: 'Skip'
          });

          intro.oncomplete(function () {
            stopTourBadgeEnforcement();
            saveTour(pageClass);
          });

          intro.onexit(function () {
            stopTourBadgeEnforcement();
            saveTour(pageClass);
          });

          intro.onafterchange(function () {
            enforceTourStepBadgeStyle();
          });

          intro.onchange(function () {
            enforceTourStepBadgeStyle();
          });

          intro.start();

          window.requestAnimationFrame(function () {
            enforceTourStepBadgeStyle();
          });

          setTimeout(function () {
            enforceTourStepBadgeStyle();
          }, 80);

          setTimeout(function () {
            enforceTourStepBadgeStyle();
          }, 200);

          setTimeout(function () {
            enforceTourStepBadgeStyle();
          }, 400);

          resolve(true);
        });
      });
    }

    function notifyTourAvailability() {
      window.dispatchEvent(new CustomEvent('tour:availability-change', {
        detail: {
          hasSteps: filterValidSteps(cachedSteps).length > 0,
          pageClass: getCurrentPageClass()
        }
      }));
    }

    function updateTourApi() {
      if (!window['ChamiloTour'] || typeof window['ChamiloTour'] !== 'object') {
        window['ChamiloTour'] = new Object();
      }

      window['ChamiloTour'].start = startTour;
      window['ChamiloTour'].hasSteps = function () {
        return filterValidSteps(cachedSteps).length > 0;
      };
      window['ChamiloTour'].getCurrentPageClass = getCurrentPageClass;

      notifyTourAvailability();
    }

    function init() {
      const pageClass = getCurrentPageClass();
      const evaluationKey = getEvaluationKey();

      if (!pageClass) {
        cachedSteps = [];
        cachedPageClass = null;
        lastEvaluationKey = evaluationKey;
        updateTourApi();
        return;
      }

      if (
        evaluationKey === lastEvaluationKey &&
        cachedPageClass === pageClass &&
        (pageStepCache.has(pageClass) || pendingStepRequests.has(pageClass))
      ) {
        return;
      }

      cachedPageClass = pageClass;
      lastEvaluationKey = evaluationKey;

      fetchSteps(pageClass)
        .then(function (steps) {
          if (cachedPageClass !== pageClass) {
            return;
          }

          cachedSteps = Array.isArray(steps) ? steps : [];

          getRoot();
          updateTourApi();
        })
        .catch(function () {
          cachedSteps = [];
          updateTourApi();
        });
    }

    function forceRefresh() {
      clearTimeout(reinitTimer);

      reinitTimer = setTimeout(function () {
        const pageClass = getCurrentPageClass();

        if (!pageClass) {
          lastEvaluationKey = null;
          init();
          return;
        }

        const now = Date.now();
        const lastRun = lastForceRefreshAt.get(pageClass) || 0;

        if (now - lastRun < FORCE_REFRESH_THROTTLE_MS) {
          return;
        }

        lastForceRefreshAt.set(pageClass, now);

        // Keep in-flight requests so duplicate refresh events reuse the same promise.
        pageStepCache.delete(pageClass);
        lastEvaluationKey = null;

        init();
      }, 80);
    }

    function scheduleReinit() {
      clearTimeout(reinitTimer);

      reinitTimer = setTimeout(function () {
        const nextKey = getEvaluationKey();

        if (nextKey === lastEvaluationKey) {
          return;
        }

        init();
      }, 250);
    }

    function patchHistoryMethod(methodName) {
      const original = window.history[methodName];

      if (typeof original !== 'function') {
        return;
      }

      window.history[methodName] = function () {
        const result = original.apply(this, arguments);
        scheduleReinit();
        return result;
      };
    }

    function observeBodyClassChanges() {
      const body = document.body;

      if (!body || typeof MutationObserver === 'undefined') {
        return;
      }

      const observer = new MutationObserver(function (mutations) {
        for (const mutation of mutations) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            scheduleReinit();
            return;
          }
        }
      });

      observer.observe(body, {
        attributes: true,
        attributeFilter: ['class']
      });
    }

    function registerRouteListeners() {
      patchHistoryMethod('pushState');
      patchHistoryMethod('replaceState');

      window.addEventListener('popstate', function () {
        scheduleReinit();
      });

      window.addEventListener('hashchange', function () {
        scheduleReinit();
      });

      window.addEventListener('tour:start', function () {
        startTour();
      });

      window.addEventListener('tour:refresh-request', function () {
        forceRefresh();
      });

      observeBodyClassChanges();
    }

    updateTourApi();
    registerRouteListeners();

    window.requestAnimationFrame(function () {
      setTimeout(init, 300);
    });
  })();
</script>
{% endif %}
