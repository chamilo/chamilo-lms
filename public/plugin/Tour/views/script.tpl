<div id="ch-tour-root"></div>
<script>
  (function () {
    console.log('[Tour] initialized force-cache-test-v1');

    const TOUR_ROOT_ID = 'ch-tour-root';

    const TOUR_CONFIG = {
      introCss: '/plugin/Tour/intro.js/introjs.min.css?v=20260402-1',
      introThemeCss: '',
      introJs: '/plugin/Tour/intro.js/intro.min.js?v=20260402-1',
      stepsAjax: '/plugin/Tour/ajax/steps.ajax.php?v=20260402-1',
      saveAjax: '/plugin/Tour/ajax/save.ajax.php?v=20260402-1'
    };

    let cachedSteps = [];
    let cachedPageClass = null;
    let lastEvaluationKey = null;
    let reinitTimer = null;
    let badgeEnforceTimer = null;
    let badgeObserver = null;

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

      loadCssOnce(TOUR_CONFIG.introCss, 'data-tour-intro-css');

      if (TOUR_CONFIG.introThemeCss) {
        loadCssOnce(TOUR_CONFIG.introThemeCss, 'data-tour-intro-theme-css');
      }

      if (!TOUR_CONFIG.introJs) {
        console.warn('[Tour] intro.js URL missing');
        return;
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
      script.onerror = function () {
        console.warn('[Tour] failed to load intro.js');
      };

      document.body.appendChild(script);
    }

    function fetchSteps(pageClass) {
      if (!TOUR_CONFIG.stepsAjax) {
        console.warn('[Tour] steps AJAX URL missing');
        return Promise.resolve([]);
      }

      return fetch(TOUR_CONFIG.stepsAjax + '?page=' + encodeURIComponent(pageClass), {
        credentials: 'same-origin'
      })
        .then(function (response) {
          return response.json();
        })
        .catch(function (error) {
          console.warn('[Tour] failed to fetch steps', error);
          return [];
        });
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
      if (!TOUR_CONFIG.saveAjax) {
        console.warn('[Tour] save AJAX URL missing');
        return;
      }

      const params = new URLSearchParams();
      params.append('page_class', pageClass);

      fetch(TOUR_CONFIG.saveAjax, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: params.toString()
      }).catch(function (error) {
        console.warn('[Tour] failed to save tour state', error);
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

        console.log('[Tour] start requested for page:', pageClass);

        if (!pageClass) {
          console.warn('[Tour] page class not detected');
          resolve(false);
          return;
        }

        const validSteps = filterValidSteps(cachedSteps);

        console.log('[Tour] cached steps:', cachedSteps);
        console.log('[Tour] valid steps:', validSteps);

        if (!validSteps.length) {
          console.warn('[Tour] no valid steps for page:', pageClass);
          resolve(false);
          return;
        }

        loadIntroJs(function () {
          if (!window.introJs) {
            console.warn('[Tour] introJs is not available after load');
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
        console.warn('[Tour] page class not detected during init');
        cachedSteps = [];
        cachedPageClass = null;
        lastEvaluationKey = evaluationKey;
        updateTourApi();
        return;
      }

      cachedPageClass = pageClass;
      lastEvaluationKey = evaluationKey;

      console.log('[Tour] checking configured tour for page:', pageClass);

      fetchSteps(pageClass).then(function (steps) {
        if (cachedPageClass !== pageClass) {
          return;
        }

        cachedSteps = Array.isArray(steps) ? steps : [];

        console.log('[Tour] configured steps found:', cachedSteps.length);

        getRoot();
        updateTourApi();
      }).catch(function (error) {
        console.warn('[Tour] failed during init', error);
        cachedSteps = [];
        updateTourApi();
      });
    }

    function forceRefresh(reason) {
      clearTimeout(reinitTimer);

      reinitTimer = setTimeout(function () {
        console.log('[Tour] forced refresh:', reason);
        init();
      }, 80);
    }

    function scheduleReinit(reason) {
      clearTimeout(reinitTimer);

      reinitTimer = setTimeout(function () {
        const nextKey = getEvaluationKey();

        if (nextKey === lastEvaluationKey) {
          return;
        }

        console.log('[Tour] route change detected:', reason);
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
        scheduleReinit(methodName);
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
            scheduleReinit('body-class');
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
        scheduleReinit('popstate');
      });

      window.addEventListener('hashchange', function () {
        scheduleReinit('hashchange');
      });

      window.addEventListener('tour:start', function () {
        startTour();
      });

      window.addEventListener('tour:refresh-request', function () {
        forceRefresh('external-request');
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
