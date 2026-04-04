/* For license terms, see /license.txt */
(function () {
  'use strict';

  if (window.__cardGameWidgetInitialized) {
    return;
  }
  window.__cardGameWidgetInitialized = true;

  var root = document.getElementById('cardgame-root');
  var state;
  var destroyCallbacks = [];
  var originalPushState = null;
  var originalReplaceState = null;

  function registerCleanup(fn) {
    if (typeof fn === 'function') {
      destroyCallbacks.push(fn);
    }
  }

  function destroyWidget() {
    destroyCallbacks.forEach(function (fn) {
      try {
        fn();
      } catch (e) {
        // Ignore cleanup errors.
      }
    });
    destroyCallbacks = [];

    if (state) {
      if (state.launcher && state.launcher.parentNode) {
        state.launcher.parentNode.removeChild(state.launcher);
      }

      if (state.overlay && state.overlay.parentNode) {
        state.overlay.parentNode.removeChild(state.overlay);
      }
    }

    document.body.classList.remove('cardgame-open');

    if (originalPushState && window.history.pushState !== originalPushState) {
      window.history.pushState = originalPushState;
    }

    if (originalReplaceState && window.history.replaceState !== originalReplaceState) {
      window.history.replaceState = originalReplaceState;
    }

    window.__cardGameWidgetInitialized = false;
    window.__cardGameRouteListenersInstalled = false;
    window.__destroyCardGameWidget = null;
    state = null;
  }

  window.__destroyCardGameWidget = destroyWidget;

  function parseParts(raw) {
    if (!raw) {
      return [];
    }

    return raw
      .split(',')
      .map(function (value) {
        return parseInt(value, 10);
      })
      .filter(function (value, index, array) {
        return value >= 1 && value <= 15 && array.indexOf(value) === index;
      })
      .sort(function (a, b) {
        return a - b;
      });
  }

  function getDisplayPan(pan) {
    var value = parseInt(pan, 10) || 1;
    if (value < 1) {
      return 1;
    }
    if (value > 4) {
      return 4;
    }

    return value;
  }

  function pickRandomMissingPart(parts) {
    var available = [];
    var index;

    for (index = 1; index <= 15; index += 1) {
      if (parts.indexOf(index) === -1) {
        available.push(index);
      }
    }

    if (!available.length) {
      return null;
    }

    return available[Math.floor(Math.random() * available.length)];
  }

  function postForm(url, payload) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: new URLSearchParams(payload).toString()
    }).then(function (response) {
      return response.json();
    });
  }

  function createElement(tag, className, text) {
    var element = document.createElement(tag);

    if (className) {
      element.className = className;
    }

    if (typeof text === 'string') {
      element.textContent = text;
    }

    return element;
  }

  function buildPieceMasks(parts) {
    var fragment = document.createDocumentFragment();
    var index;
    var piece;

    for (index = 1; index <= 15; index += 1) {
      piece = document.createElement('div');
      piece.className = 'cardgame-piece-mask';
      piece.setAttribute('data-part', String(index));

      if (parts.indexOf(index) !== -1) {
        piece.classList.add('cardgame-piece-mask--revealed');
      }

      fragment.appendChild(piece);
    }

    return fragment;
  }

  function buildArchive(pan) {
    var container = createElement('div', 'cardgame-archive');
    var title = createElement(
      'div',
      'cardgame-archive__title',
      state.labels.completed + ': ' + Math.max(0, pan - 1)
    );
    var grid = createElement('div', 'cardgame-archive__grid');
    var index;
    var item;
    var maxPreview = Math.min(4, Math.max(0, pan - 1));

    container.appendChild(title);

    for (index = 1; index <= 4; index += 1) {
      item = createElement('div', 'cardgame-archive__item');

      if (index <= maxPreview) {
        item.classList.add('pimg0' + index);
      }

      grid.appendChild(item);
    }

    container.appendChild(grid);

    return container;
  }

  function setStatus(message, variant) {
    if (!state.statusElement) {
      return;
    }

    state.statusElement.textContent = message;
    state.statusElement.className = 'cardgame-status';

    if (variant) {
      state.statusElement.classList.add('cardgame-status--' + variant);
    }
  }

  function updateLauncher() {
    if (!state.launcher || !state.launcherBadge) {
      return;
    }

    state.launcher.classList.toggle('cardgame-launcher--active', state.canPlayToday);
    state.launcherBadge.hidden = !state.canPlayToday;
  }

  function renderModalContent() {
    var body;
    var current;
    var currentHeader;
    var board;
    var actions;
    var revealButton;
    var closeButton;

    if (!state.modalBody) {
      return;
    }

    body = state.modalBody;
    body.innerHTML = '';

    current = createElement('div', 'cardgame-current');
    currentHeader = createElement(
      'div',
      'cardgame-current__header',
      state.labels.title + ' · Panel ' + state.pan
    );

    board = createElement('div', 'cardgame-current__board pimg0' + getDisplayPan(state.pan));
    board.appendChild(buildPieceMasks(state.parts));

    current.appendChild(currentHeader);
    current.appendChild(board);

    actions = createElement('div', 'cardgame-actions');

    state.statusElement = createElement('div', 'cardgame-status');
    if (state.canPlayToday) {
      setStatus(state.labels.openMessage, 'info');
    } else {
      setStatus(state.labels.engageMessage, 'muted');
    }

    revealButton = createElement('button', 'btn btn-primary cardgame-button', state.labels.reveal);
    revealButton.type = 'button';
    revealButton.disabled = !state.canPlayToday || state.busy;
    revealButton.addEventListener('click', revealTodayPiece);

    closeButton = createElement('button', 'btn btn-default cardgame-button', state.labels.close);
    closeButton.type = 'button';
    closeButton.addEventListener('click', closeModal);

    actions.appendChild(state.statusElement);
    actions.appendChild(revealButton);
    actions.appendChild(closeButton);

    body.appendChild(current);
    body.appendChild(buildArchive(state.pan));
    body.appendChild(actions);
  }

  function openModal() {
    if (!shouldShowLauncher()) {
      return;
    }

    renderModalContent();
    state.overlay.classList.add('is-visible');
    document.body.classList.add('cardgame-open');
  }

  function closeModal() {
    if (!state.overlay) {
      return;
    }

    state.overlay.classList.remove('is-visible');
    document.body.classList.remove('cardgame-open');
  }

  function syncStateFromResponse(payload) {
    state.parts = Array.isArray(payload.parts)
      ? payload.parts.map(function (value) {
        return parseInt(value, 10);
      }).filter(function (value, index, array) {
        return value >= 1 && value <= 15 && array.indexOf(value) === index;
      }).sort(function (a, b) {
        return a - b;
      })
      : [];

    state.pan = parseInt(payload.pan, 10) || state.pan;
    state.canPlayToday =
      payload.canPlayToday === true ||
      payload.canPlayToday === 1 ||
      payload.canPlayToday === '1';
  }

  function revealTodayPiece() {
    var nextPart;

    if (state.busy || !state.canPlayToday) {
      return;
    }

    nextPart = pickRandomMissingPart(state.parts);

    if (!nextPart) {
      setStatus(state.labels.loadingError, 'error');
      return;
    }

    state.busy = true;
    renderModalContent();
    setStatus(state.labels.openMessage, 'info');

    postForm(state.endpoint, {
      action: 'reveal',
      part: String(nextPart),
      csrf_token: state.csrfToken
    })
      .then(function (payload) {
        state.busy = false;

        if (!payload || typeof payload !== 'object') {
          renderModalContent();
          setStatus(state.labels.loadingError, 'error');
          return;
        }

        syncStateFromResponse(payload);
        updateLauncher();
        renderModalContent();

        if (payload.completedPan) {
          setStatus(state.labels.panelCompleted + ' ' + (state.pan - 1) + '.', 'success');
          return;
        }

        if (payload.duplicatePart) {
          setStatus(state.labels.duplicateMessage, 'warning');
          return;
        }

        if (payload.alreadyPlayed) {
          setStatus(state.labels.engageMessage, 'muted');
          return;
        }

        setStatus(state.labels.pieceRevealed + ' #' + nextPart + '.', 'success');
      })
      .catch(function () {
        state.busy = false;
        renderModalContent();
        setStatus(state.labels.loadingError, 'error');
      });
  }

  function getCurrentPath() {
    var path = window.location.pathname || '/';

    if (path.length > 1) {
      path = path.replace(/\/+$/, '');
    }

    return path || '/';
  }

  function isCoursesPath(path) {
    return path === '/courses' || path.indexOf('/courses/') === 0;
  }

  function shouldShowLauncher() {
    var path = getCurrentPath();

    return isCoursesPath(path);
  }

  function applyLauncherVisibility() {
    var visible;

    if (!state.launcher) {
      return;
    }

    visible = shouldShowLauncher();
    state.launcher.hidden = !visible;

    if (!visible) {
      closeModal();
    }
  }

  function scheduleVisibilityRefresh() {
    window.setTimeout(applyLauncherVisibility, 0);
    window.setTimeout(applyLauncherVisibility, 150);
    window.setTimeout(applyLauncherVisibility, 400);
    window.setTimeout(applyLauncherVisibility, 800);
  }

  function installRouteListeners() {
    var onPopState;
    var onHashChange;
    var onVisibilityChange;

    if (window.__cardGameRouteListenersInstalled) {
      return;
    }
    window.__cardGameRouteListenersInstalled = true;

    originalPushState = window.history.pushState;
    originalReplaceState = window.history.replaceState;

    window.history.pushState = function () {
      var result = originalPushState.apply(this, arguments);
      scheduleVisibilityRefresh();
      return result;
    };

    window.history.replaceState = function () {
      var result = originalReplaceState.apply(this, arguments);
      scheduleVisibilityRefresh();
      return result;
    };

    onPopState = function () {
      scheduleVisibilityRefresh();
    };

    onHashChange = function () {
      scheduleVisibilityRefresh();
    };

    onVisibilityChange = function () {
      if (!document.hidden) {
        scheduleVisibilityRefresh();
      }
    };

    window.addEventListener('popstate', onPopState);
    window.addEventListener('hashchange', onHashChange);
    document.addEventListener('visibilitychange', onVisibilityChange);

    registerCleanup(function () {
      window.removeEventListener('popstate', onPopState);
      window.removeEventListener('hashchange', onHashChange);
      document.removeEventListener('visibilitychange', onVisibilityChange);
    });
  }

  function buildUi() {
    var launcher = createElement('button', 'cardgame-launcher');
    var badge = createElement('span', 'cardgame-launcher__badge', '●');
    var overlay = createElement('div', 'cardgame-overlay');
    var modal = createElement('div', 'cardgame-modal');
    var header = createElement('div', 'cardgame-modal__header');
    var title = createElement('h3', 'cardgame-modal__title', state.labels.title);
    var close = createElement('button', 'cardgame-modal__close', '×');
    var body = createElement('div', 'cardgame-modal__body');
    var onOverlayClick;
    var onKeyDown;

    launcher.type = 'button';
    launcher.setAttribute('aria-label', state.labels.title);
    launcher.addEventListener('click', openModal);
    launcher.appendChild(badge);

    close.type = 'button';
    close.setAttribute('aria-label', state.labels.close);
    close.addEventListener('click', closeModal);

    onOverlayClick = function (event) {
      if (event.target === overlay) {
        closeModal();
      }
    };

    onKeyDown = function (event) {
      if (event.key === 'Escape' && state.overlay && state.overlay.classList.contains('is-visible')) {
        closeModal();
      }
    };

    overlay.addEventListener('click', onOverlayClick);
    document.addEventListener('keydown', onKeyDown);

    registerCleanup(function () {
      overlay.removeEventListener('click', onOverlayClick);
      document.removeEventListener('keydown', onKeyDown);
    });

    header.appendChild(title);
    header.appendChild(close);

    modal.appendChild(header);
    modal.appendChild(body);
    overlay.appendChild(modal);

    document.body.appendChild(launcher);
    document.body.appendChild(overlay);

    state.launcher = launcher;
    state.launcherBadge = badge;
    state.overlay = overlay;
    state.modalBody = body;

    updateLauncher();
    applyLauncherVisibility();
    installRouteListeners();
    scheduleVisibilityRefresh();
  }

  if (!root) {
    window.__cardGameWidgetInitialized = false;
    window.__destroyCardGameWidget = null;
    return;
  }

  state = {
    endpoint: root.dataset.endpoint || '',
    csrfToken: root.dataset.csrfToken || '',
    canPlayToday: root.dataset.canPlay === '1',
    pan: parseInt(root.dataset.pan, 10) || 1,
    parts: parseParts(root.dataset.parts),
    busy: false,
    launcher: null,
    launcherBadge: null,
    overlay: null,
    modalBody: null,
    statusElement: null,
    labels: {
      title: root.dataset.title || 'Card game',
      openMessage: root.dataset.openMessage || 'You have won a Chamilo card! Open it to see its contents.',
      engageMessage: root.dataset.engageMessage || 'Come back every day to win new cards.',
      duplicateMessage: root.dataset.duplicateMessage || 'You already have this piece. Come back tomorrow.',
      reveal: root.dataset.revealLabel || 'Reveal today’s piece',
      close: root.dataset.closeLabel || 'Close',
      completed: root.dataset.completedLabel || 'Completed panels',
      pieceRevealed: root.dataset.pieceRevealedLabel || 'Piece revealed',
      panelCompleted: root.dataset.panelCompletedLabel || 'Panel completed',
      loadingError: root.dataset.loadingErrorLabel || 'Unable to load the card game right now.'
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', buildUi, { once: true });
  } else {
    buildUi();
  }
})();
