<div id="ch-tour-root"></div>

<style>
    .introjs-overlay {
        opacity: 0.18 !important;
    }

    .introjs-helperLayer {
        background: transparent !important;
        border: 2px solid #0ea5e9 !important;
        border-radius: 12px !important;
        box-shadow:
                0 0 0 9999px rgba(15, 23, 42, 0.18),
                0 0 0 3px rgba(14, 165, 233, 0.12) !important;
    }

    .introjs-helperNumberLayer {
        box-sizing: content-box;
    }

    .introjs-tooltip {
        border-radius: 12px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18) !important;
    }

    .introjs-tooltipReferenceLayer {
        background: transparent !important;
    }

    .introjs-disableInteraction {
        background-color: transparent !important;
    }
</style>

<script>
  (function () {
    console.log('[Tour] initialized');

    const TOUR_ROOT_ID = 'ch-tour-root';
    const TOUR_BUTTON_ID = 'ch-tour-button';

    const TOUR_CONFIG = {
      introCss: '/plugin/Tour/intro.js/introjs.min.css',
      introThemeCss: '',
      introJs: '/plugin/Tour/intro.js/intro.min.js',
      stepsAjax: '/plugin/Tour/ajax/steps.ajax.php',
      saveAjax: '/plugin/Tour/ajax/save.ajax.php'
    };

    let cachedSteps = [];
    let cachedPageClass = null;
    let lastEvaluationKey = null;
    let reinitTimer = null;

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

    function removeButton() {
      const oldButton = document.getElementById(TOUR_BUTTON_ID);

      if (oldButton) {
        oldButton.remove();
      }
    }

    function ensureMdiStyles() {
      if (document.getElementById('tour-mdi-styles')) {
        return;
      }

      const link = document.createElement('link');
      link.id = 'tour-mdi-styles';
      link.rel = 'stylesheet';
      link.href = '/build/assets/materialdesignicons.min.css';
      document.head.appendChild(link);
    }

    function createButton() {
      removeButton();
      ensureMdiStyles();

      const button = document.createElement('button');
      button.id = TOUR_BUTTON_ID;
      button.type = 'button';
      button.setAttribute('aria-label', 'Start tour');
      button.setAttribute('title', 'Start tour');

      Object.assign(button.style, {
        position: 'fixed',
        top: '50%',
        right: '18px',
        transform: 'translateY(-50%)',
        zIndex: '999999',
        width: '52px',
        height: '52px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #0ea5e9, #2563eb)',
        color: '#ffffff',
        border: 'none',
        borderRadius: '999px',
        boxShadow: '0 10px 24px rgba(0, 0, 0, 0.24)',
        cursor: 'pointer',
        transition: 'transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease',
        padding: '0'
      });

      const icon = document.createElement('i');
      icon.className = 'mdi mdi-help-circle-outline';
      icon.setAttribute('aria-hidden', 'true');

      Object.assign(icon.style, {
        fontSize: '26px',
        lineHeight: '1'
      });

      button.appendChild(icon);

      button.addEventListener('mouseenter', function () {
        button.style.transform = 'translateY(-50%) scale(1.04)';
        button.style.boxShadow = '0 14px 30px rgba(0, 0, 0, 0.28)';
        button.style.filter = 'brightness(1.04)';
      });

      button.addEventListener('mouseleave', function () {
        button.style.transform = 'translateY(-50%)';
        button.style.boxShadow = '0 10px 24px rgba(0, 0, 0, 0.24)';
        button.style.filter = 'none';
      });

      button.addEventListener('mousedown', function () {
        button.style.transform = 'translateY(-50%) scale(0.96)';
      });

      button.addEventListener('mouseup', function () {
        button.style.transform = 'translateY(-50%) scale(1.04)';
      });

      document.body.appendChild(button);

      return button;
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

      const formData = new FormData();
      formData.append('page_class', pageClass);

      fetch(TOUR_CONFIG.saveAjax, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      }).catch(function (error) {
        console.warn('[Tour] failed to save tour state', error);
      });
    }

    function startTour() {
      const pageClass = getCurrentPageClass();

      console.log('[Tour] start requested for page:', pageClass);

      if (!pageClass) {
        console.warn('[Tour] page class not detected');
        return;
      }

      const validSteps = filterValidSteps(cachedSteps);

      console.log('[Tour] cached steps:', cachedSteps);
      console.log('[Tour] valid steps:', validSteps);

      if (!validSteps.length) {
        console.warn('[Tour] no valid steps for page:', pageClass);
        return;
      }

      loadIntroJs(function () {
        if (!window.introJs) {
          console.warn('[Tour] introJs is not available after load');
          return;
        }

        const intro = window.introJs();

        intro.setOptions({
          steps: validSteps,
          overlayOpacity: 0,
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
          saveTour(pageClass);
        });

        intro.onexit(function () {
          saveTour(pageClass);
        });

        intro.start();
      });
    }

    function init() {
      const pageClass = getCurrentPageClass();
      const evaluationKey = getEvaluationKey();

      if (!pageClass) {
        console.warn('[Tour] page class not detected during init');
        cachedSteps = [];
        cachedPageClass = null;
        lastEvaluationKey = evaluationKey;
        removeButton();
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

        if (!cachedSteps.length) {
          removeButton();
          return;
        }

        getRoot();

        const button = createButton();

        button.addEventListener('click', function () {
          startTour();
        });
      });
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

      observeBodyClassChanges();
    }

    registerRouteListeners();

    window.requestAnimationFrame(function () {
      setTimeout(init, 300);
    });
  })();
</script>
