/*
 * Password castle: a decorative, local-only illustration of password input.
 * This does not enforce password policy or provide a security-strength guarantee.
 * Original artwork/animation inspired by the KEEP concept (Justin Finkelman, MIT).
 */
(function () {
  'use strict';

  var ROOT_SELECTOR = '[data-password-castle]';
  var WIDTH = 256;
  var HEIGHT = 120;
  var BUILD_SPEED = 44;
  var ATTACK_DELAY_MS = 1150;
  var ATTACK_DURATION_MS = 4400;
  var DEFAULT_STAGES = ['Unprotected', 'Fence', 'Palisade', 'Stone walls', 'Castle', 'Fortified castle'];
  var TARGET_PIECES = [0, 20, 43, 69, 91, 105];
  var COLOR = {
    sky: '#14213d', skyLight: '#27375b', moon: '#f5db93',
    hill: '#294f52', hillFar: '#233b49', ground: '#274036',
    wall: '#b6abc0', wallDark: '#786c8e', wallLight: '#d9cee1',
    wood: '#8b5e57', woodDark: '#583f4e', water: '#4d95ad',
    ripple: '#8ad1d5', banner: '#ee987b', raccoon: '#a2a5b1',
    raccoonDark: '#383e51', stars: '#dce2f0', leaf: '#7ac8a5',
    leafDark: '#328e82', ember: '#ffb060', fire: '#fa7059',
    gold: '#e6bb67', dust: '#d7c3ae', grass: '#355c48',
  };

  function rect(ctx, x, y, w, h, color) {
    ctx.fillStyle = color;
    ctx.fillRect(Math.round(x), Math.round(y), Math.round(w), Math.round(h));
  }

  function polygon(ctx, points, color) {
    ctx.fillStyle = color;
    ctx.beginPath();
    points.forEach(function (point, index) {
      if (index === 0) ctx.moveTo(point[0], point[1]);
      else ctx.lineTo(point[0], point[1]);
    });
    ctx.closePath();
    ctx.fill();
  }

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function lerp(a, b, progress) {
    return a + (b - a) * clamp(progress, 0, 1);
  }

  function range(value, start, end) {
    return clamp((value - start) / (end - start), 0, 1);
  }

  function piece(x, y, w, h, color, detail) {
    return { x: x, y: y, w: w, h: h, color: color, detail: detail || '' };
  }

  function buildBlueprint() {
    var pieces = [];
    var i;

    // Level 1: a rope fence and its wooden stakes.
    for (i = 0; i < 10; i++) {
      pieces.push(piece(78 + i * 11, 84, 4, 23, COLOR.woodDark));
      pieces.push(piece(79 + i * 11, 83, 2, 4, COLOR.wood));
    }

    // Level 2: the timber wall and the gate.
    for (i = 0; i < 19; i++) {
      pieces.push(piece(78 + i * 6, 74 + (i % 3), 5, 32 - (i % 3), i % 2 ? COLOR.wood : COLOR.woodDark));
    }
    pieces.push(piece(118, 89, 20, 18, COLOR.woodDark));
    pieces.push(piece(121, 92, 14, 15, COLOR.wood));
    pieces.push(piece(129, 99, 2, 2, COLOR.gold));
    pieces.push(piece(78, 72, 115, 3, COLOR.woodDark));

    // Level 3: water, staggered stonework and battlements.
    for (i = 0; i < 6; i++) {
      pieces.push(piece(63 + i * 24, 106, 25, 13, COLOR.water));
    }
    for (i = 0; i < 16; i++) {
      pieces.push(piece(80 + (i % 8) * 12, 92 - Math.floor(i / 8) * 12, 12, 12,
        (i + Math.floor(i / 8)) % 3 ? COLOR.wall : COLOR.wallDark, 'brick'));
    }
    for (i = 0; i < 4; i++) {
      pieces.push(piece(82 + i * 29, 73, 12, 7, COLOR.wallLight));
    }

    // Level 4: side towers, central keep, roof, windows and flag.
    for (i = 0; i < 5; i++) {
      pieces.push(piece(70, 94 - i * 9, 23, 9, i % 2 ? COLOR.wall : COLOR.wallDark, 'brick'));
      pieces.push(piece(162, 94 - i * 9, 23, 9, i % 2 ? COLOR.wall : COLOR.wallDark, 'brick'));
    }
    for (i = 0; i < 5; i++) {
      pieces.push(piece(105, 92 - i * 10, 46, 10, i % 2 ? COLOR.wallDark : COLOR.wall, 'brick'));
    }
    pieces.push(piece(103, 40, 50, 5, COLOR.wallLight));
    pieces.push(piece(110, 30, 37, 12, COLOR.wallDark));
    pieces.push(piece(115, 24, 28, 6, COLOR.wall));
    pieces.push(piece(126, 15, 2, 18, COLOR.woodDark));
    pieces.push(piece(128, 17, 17, 8, COLOR.banner));
    pieces.push(piece(128, 25, 11, 3, COLOR.banner));
    pieces.push(piece(121, 62, 13, 13, COLOR.woodDark));

    // Level 5: the dragon assembles above the keep, piece by piece.
    pieces.push(piece(138, 24, 7, 11, COLOR.leafDark));
    pieces.push(piece(144, 19, 13, 15, COLOR.leaf));
    pieces.push(piece(152, 23, 18, 12, COLOR.leaf));
    pieces.push(piece(168, 16, 10, 13, COLOR.leaf));
    pieces.push(piece(176, 21, 8, 5, COLOR.leaf));
    pieces.push(piece(169, 17, 3, 3, COLOR.sky));
    pieces.push(piece(145, 33, 5, 9, COLOR.leafDark));
    pieces.push(piece(160, 33, 5, 9, COLOR.leafDark));
    pieces.push(piece(135, 16, 12, 10, COLOR.leaf));
    pieces.push(piece(142, 11, 11, 9, COLOR.leafDark));
    pieces.push(piece(149, 9, 11, 9, COLOR.leaf));
    pieces.push(piece(160, 9, 10, 8, COLOR.leafDark));
    pieces.push(piece(183, 23, 3, 2, COLOR.leafDark));
    pieces.push(piece(174, 16, 3, 2, COLOR.gold));

    return pieces;
  }

  var PIECES = buildBlueprint();
  var STARS = [[12, 13], [46, 23], [71, 10], [125, 18], [173, 11], [201, 34], [248, 49], [22, 51]];

  function background(ctx, seconds, showChest) {
    rect(ctx, 0, 0, WIDTH, HEIGHT, COLOR.sky);
    rect(ctx, 0, 70, WIDTH, 50, COLOR.skyLight);
    rect(ctx, 222, 12, 16, 16, COLOR.moon);
    rect(ctx, 220, 16, 4, 8, COLOR.skyLight);
    STARS.forEach(function (star, index) {
      rect(ctx, star[0], star[1], index % 3 === 0 && Math.sin(seconds * 2 + index) > 0 ? 3 : 2, 2, COLOR.stars);
    });
    polygon(ctx, [[0, 91], [56, 59], [100, 87], [160, 55], [212, 82], [256, 69], [256, 120], [0, 120]], COLOR.hillFar);
    polygon(ctx, [[0, 100], [50, 77], [95, 102], [164, 77], [216, 105], [256, 86], [256, 120], [0, 120]], COLOR.hill);
    rect(ctx, 0, 106, WIDTH, 14, COLOR.ground);
    rect(ctx, 0, 105, WIDTH, 2, COLOR.wood);
    for (var i = 0; i < 15; i++) {
      var x = 9 + i * 17;
      rect(ctx, x, 113 - i % 3, 5, 2, COLOR.grass);
    }
    // The treasure disappears when a low-level raid succeeds.
    if (showChest) {
      rect(ctx, 123, 93, 18, 14, COLOR.woodDark);
      rect(ctx, 125, 90, 15, 7, COLOR.wood);
      rect(ctx, 131, 90, 3, 14, COLOR.gold);
      rect(ctx, 125, 96, 15, 2, COLOR.gold);
    }
  }

  function drawPiece(ctx, part, drop) {
    var y = part.y - drop;
    rect(ctx, part.x, y, part.w, part.h, part.color);
    if (part.detail === 'brick') {
      rect(ctx, part.x + 2, y + 2, Math.max(1, part.w - 4), 1, COLOR.wallLight);
      rect(ctx, part.x + part.w - 2, y + 3, 1, Math.max(1, part.h - 5), COLOR.wallDark);
    }
  }

  function building(ctx, count, animate) {
    var settled = Math.min(PIECES.length, Math.floor(count));
    for (var index = 0; index < settled; index++) drawPiece(ctx, PIECES[index], 0);

    // Animate only the next falling piece. Previous pieces remain stationary.
    if (animate && settled < PIECES.length && count > 0 && count < PIECES.length) {
      var fraction = count - settled;
      var drop = Math.round(Math.pow(1 - fraction, 2) * 24);
      drawPiece(ctx, PIECES[settled], drop);
      if (fraction > 0.76 && fraction < 0.98) {
        var p = PIECES[settled];
        rect(ctx, p.x - 2, p.y + p.h - 2, 2, 2, COLOR.dust);
        rect(ctx, p.x + p.w + 1, p.y + p.h - 4, 2, 2, COLOR.dust);
      }
    }

    // The rope remains behind the heavier walls in later levels.
    if (count >= 18 && count < TARGET_PIECES[2]) {
      rect(ctx, 80, 93, 98, 1, COLOR.gold);
      rect(ctx, 80, 101, 98, 1, COLOR.wood);
    }
    if (count >= TARGET_PIECES[3] && count < TARGET_PIECES[4]) {
      for (var w = 0; w < 5; w++) rect(ctx, 74 + w * 24, 111, 11, 2, COLOR.ripple);
    }
    if (count >= TARGET_PIECES[4]) {
      rect(ctx, 77, 60, 7, 9, COLOR.sky);
      rect(ctx, 174, 60, 7, 9, COLOR.sky);
      rect(ctx, 121, 51, 5, 9, COLOR.sky);
      rect(ctx, 137, 51, 5, 9, COLOR.sky);
      rect(ctx, 125, 70, 9, 5, COLOR.wood);
    }
  }

  function raccoon(ctx, x, y, progress, options) {
    var legSwing = options.walk ? (Math.sin(progress * 20) > 0 ? 2 : -2) : 0;
    var facing = options.facing === 'left' ? -1 : 1;
    ctx.save();
    ctx.translate(Math.round(x), Math.round(y));
    ctx.scale(facing, 1);
    // A bush hides the raccoon at the end of the dragon attack.
    if (options.hidden) {
      rect(ctx, -8, 3, 21, 13, COLOR.grass);
      rect(ctx, -4, -2, 15, 6, COLOR.leafDark);
      rect(ctx, -2, 6, 4, 3, COLOR.stars);
      ctx.restore();
      return;
    }
    rect(ctx, -16, 3, 10, 5, COLOR.raccoonDark);
    rect(ctx, -19, 7, 8, 5, COLOR.raccoon);
    rect(ctx, -15, 8, 4, 2, COLOR.raccoonDark);
    rect(ctx, -5, 4, 17, 11, options.wet ? COLOR.ripple : COLOR.raccoon);
    rect(ctx, -1, -3, 15, 11, COLOR.raccoonDark);
    rect(ctx, 1, -7, 4, 5, COLOR.raccoonDark);
    rect(ctx, 9, -7, 4, 5, COLOR.raccoonDark);
    rect(ctx, 0, 0, 12, 5, COLOR.raccoon);
    rect(ctx, 2, -2, 3, 3, COLOR.sky);
    rect(ctx, 9, -2, 3, 3, COLOR.sky);
    rect(ctx, 6, 3, 3, 3, COLOR.raccoonDark);
    rect(ctx, -1, 14 + legSwing, 4, 5, COLOR.raccoonDark);
    rect(ctx, 9, 14 - legSwing, 4, 5, COLOR.raccoonDark);
    if (options.sack) {
      rect(ctx, -15, 7, 10, 9, COLOR.gold);
      rect(ctx, -11, 4, 4, 3, COLOR.woodDark);
    }
    ctx.restore();
  }

  function arrow(ctx, progress) {
    var x = lerp(129, 203, progress);
    var y = 70 + (1 - progress) * 5;
    rect(ctx, x, y, 11, 2, COLOR.wood);
    rect(ctx, x + 11, y - 2, 4, 6, COLOR.wallLight);
    rect(ctx, x, y - 2, 2, 6, COLOR.banner);
  }

  function fire(ctx, progress) {
    for (var i = 0; i < 10; i++) {
      var t = range(progress * 12, i, i + 1);
      if (!t) continue;
      var x = 183 + i * 3 + Math.sin(i * 1.7) * 3;
      var y = 25 + i * 4.3 + Math.sin(i * 2) * 3;
      rect(ctx, x, y, 8 - i % 3, 8 + i % 2, i % 2 ? COLOR.fire : COLOR.ember);
      rect(ctx, x + 2, y + 2, 3, 4, COLOR.gold);
    }
  }

  function raid(ctx, level, ms) {
    var t = ms / 1000;
    var x = 216;
    var y = 88;
    var options = { facing: 'left', walk: true, sack: false, wet: false, hidden: false };

    if (level <= 1) {
      if (t < 1.6) x = lerp(216, 137, t / 1.6);
      else if (t < 2.2) {
        x = 137;
        options.walk = false;
      } else {
        x = lerp(137, 250, (t - 2.2) / 1.6);
        options.facing = 'right';
        options.sack = true;
      }
      raccoon(ctx, x, y, t, options);
      return;
    }

    if (level === 2) {
      if (t < 1.15) x = lerp(216, 188, t / 1.15);
      else if (t < 3.2) {
        x = 188;
        y = lerp(88, 60, (t - 1.15) / 2.05);
      } else {
        x = lerp(188, 238, (t - 3.2) / 1.2);
        y = lerp(60, 88, (t - 3.2) / 1.2);
        options.facing = 'right';
      }
      if (t > 0.95 && t < 3.55) {
        rect(ctx, 186, 58, 2, 48, COLOR.woodDark);
        rect(ctx, 195, 58, 2, 48, COLOR.woodDark);
        for (var rung = 0; rung < 6; rung++) rect(ctx, 186, 62 + rung * 7, 11, 2, COLOR.wood);
      }
      raccoon(ctx, x, y, t, options);
      return;
    }

    if (level === 3) {
      if (t < 1) x = lerp(216, 198, t);
      else if (t < 2.35) {
        var jump = range(t, 1, 2.35);
        x = lerp(198, 164, jump);
        y = 88 - Math.sin(jump * Math.PI) * 25 + 12 * jump;
      } else {
        x = lerp(164, 227, (t - 2.35) / 1.7);
        options.wet = true;
        options.facing = 'right';
      }
      if (t > 2.08 && t < 3.3) {
        var splash = Math.sin((t - 2.08) * 11);
        rect(ctx, 163, 101 - Math.abs(splash) * 8, 4, 3, COLOR.ripple);
        rect(ctx, 154, 103 - Math.abs(splash) * 6, 5, 2, COLOR.ripple);
        rect(ctx, 175, 103 - Math.abs(splash) * 9, 4, 2, COLOR.ripple);
      }
      raccoon(ctx, x, y, t, options);
      return;
    }

    if (level === 4) {
      if (t < 1.1) x = lerp(216, 188, t / 1.1);
      else if (t < 2.6) {
        x = 188;
        options.walk = false;
        if (t > 1.3) arrow(ctx, range(t, 1.3, 2.0));
      } else {
        x = lerp(188, 249, (t - 2.6) / 1.5);
        options.facing = 'right';
      }
      if (t > 1.4 && t < 2.6) {
        // A small knight watches from the upper window.
        rect(ctx, 128, 54, 7, 9, COLOR.wallLight);
        rect(ctx, 130, 51, 4, 4, COLOR.gold);
        rect(ctx, 129, 58, 6, 2, COLOR.woodDark);
      }
      raccoon(ctx, x, y, t, options);
      return;
    }

    if (t < 1.1) x = lerp(216, 191, t / 1.1);
    else if (t < 2.6) {
      x = 191;
      options.walk = false;
      if (t > 1.3) fire(ctx, range(t, 1.3, 2.2));
    } else {
      x = lerp(191, 226, (t - 2.6) / 1.2);
      options.facing = 'right';
      options.hidden = t > 3.55;
    }
    raccoon(ctx, x, y, t, options);
  }

  function levelOf(value, policy) {
    if (!value) return 0;
    var tests = [
      value.length >= policy.length,
      (value.match(/[a-z]/g) || []).length >= policy.lowercase,
      (value.match(/[A-Z]/g) || []).length >= policy.uppercase,
      (value.match(/[0-9]/g) || []).length >= policy.numeric,
      (value.match(/[^a-zA-Z0-9]/g) || []).length >= policy.specials,
    ];
    var met = tests.filter(Boolean).length;
    var lengthRatio = Math.min(1, value.length / policy.length);
    var diversity = [/[a-z]/, /[A-Z]/, /\d/, /[^a-zA-Z0-9]/].filter(function (re) { return re.test(value); }).length;
    var score = 0.4 * lengthRatio + 0.4 * (met / 5) + 0.2 * (diversity / 4);
    var normalized = value.toLowerCase().replace(/[@4]/g, 'a').replace(/0/g, 'o').replace(/[1!]/g, 'i');
    var known = /^(?:password|password123|123456|123456789|qwerty|admin|letmein|welcome|iloveyou|hunter2)/;
    if (known.test(normalized) || /^(.)\1{3,}$/.test(value) || /^(?:1234|abcd|qwer|asdf|9876)/i.test(value)) {
      score = Math.min(score, 0.12);
    }
    if (value.length < policy.length * 0.65) score = Math.min(score, 0.3);
    // This is not a password-strength estimator. Avoid the strongest scenes for short strings.
    if (value.length < 14) score = Math.min(score, 0.62);
    else if (value.length < 16) score = Math.min(score, 0.82);
    return clamp(Math.floor(score * 5 + 0.5), 1, 5);
  }

  function policyOf(root) {
    var policy = {};
    try { policy = JSON.parse(root.getAttribute('data-policy') || '{}') || {}; } catch (error) { policy = {}; }
    return {
      length: Math.max(1, Number(policy.length) || 12),
      lowercase: Math.max(0, Number(policy.lowercase) || 0),
      uppercase: Math.max(0, Number(policy.uppercase) || 0),
      numeric: Math.max(0, Number(policy.numeric) || 0),
      specials: Math.max(0, Number(policy.specials) || 0),
    };
  }

  function stageNames(root) {
    var raw = root.getAttribute('data-stage-labels');
    if (!raw) return DEFAULT_STAGES;
    try {
      var names = JSON.parse(raw);
      if (Array.isArray(names) && names.length === 6 && names.every(function (name) {
        return typeof name === 'string' && name.trim().length > 0;
      })) return names;
    } catch (error) {
      // Fall back to English if the translated values are malformed.
    }
    return DEFAULT_STAGES;
  }

  function mount(root) {
    if (root.dataset.castleMounted === 'true') return;
    var selector = root.getAttribute('data-target');
    var field = selector ? document.querySelector(selector) : null;
    var canvas = root.querySelector('canvas');
    var status = root.querySelector('[data-castle-status]');
    if (!field || !canvas || !status || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    root.dataset.castleMounted = 'true';
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    ctx.imageSmoothingEnabled = false;
    var policy = policyOf(root);
    var stages = stageNames(root);
    var suffix = root.getAttribute('data-visual-suffix') || 'Visual guide only';
    var media = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var reduced = media ? media.matches : false;
    var container = root.closest('details');
    var visible = true;
    var active = false;
    var raf = 0;
    var timer = 0;
    var previousTimestamp = 0;
    var state = { level: 0, target: 0, built: 0, pendingAttack: false, attackMs: -1, seconds: 0 };

    function canAnimate() {
      return !reduced && visible && document.visibilityState !== 'hidden' && (!container || container.open);
    }

    function stopFrame() {
      if (raf) window.cancelAnimationFrame(raf);
      raf = 0;
      previousTimestamp = 0;
    }

    function draw() {
      ctx.clearRect(0, 0, WIDTH, HEIGHT);
      background(ctx, state.seconds, !(state.level <= 1 && state.attackMs > 1750));
      building(ctx, state.built, canAnimate());
      if (state.attackMs >= 0) raid(ctx, state.level, state.attackMs);
      else raccoon(ctx, 217, 88, 0, { facing: 'left', walk: false, wet: false, hidden: false, sack: false });
      // Discrete shields keep the indicator readable when motion is reduced.
      for (var i = 0; i < 5; i++) {
        rect(ctx, 8 + i * 9, 5, 7, 5, i < state.level ? COLOR.gold : COLOR.skyLight);
      }
    }

    function frame(timestamp) {
      raf = 0;
      if (!canAnimate()) return;
      var elapsed = previousTimestamp ? clamp(timestamp - previousTimestamp, 0, 50) : 16;
      previousTimestamp = timestamp;
      state.seconds += elapsed / 1000;
      if (Math.abs(state.built - state.target) > 0.001) {
        var step = (BUILD_SPEED * elapsed) / 1000;
        state.built = state.target > state.built
          ? Math.min(state.target, state.built + step)
          : Math.max(state.target, state.built - step * 1.6);
      }
      if (state.pendingAttack && Math.abs(state.built - state.target) < 0.01) {
        state.pendingAttack = false;
        state.attackMs = 0;
      }
      if (state.attackMs >= 0) {
        state.attackMs += elapsed;
        if (state.attackMs >= ATTACK_DURATION_MS) state.attackMs = -1;
      }
      draw();
      if (Math.abs(state.built - state.target) > 0.001 || state.attackMs >= 0) {
        raf = window.requestAnimationFrame(frame);
      } else {
        previousTimestamp = 0;
      }
    }

    function startFrame() {
      if (!raf && canAnimate()) raf = window.requestAnimationFrame(frame);
    }

    function cancelAttack() {
      if (timer) window.clearTimeout(timer);
      timer = 0;
      state.pendingAttack = false;
      state.attackMs = -1;
    }

    function scheduleAttack() {
      if (!canAnimate() || !field.value) return;
      timer = window.setTimeout(function () {
        timer = 0;
        if (!canAnimate()) return;
        state.pendingAttack = true;
        startFrame();
      }, ATTACK_DELAY_MS);
    }

    function update() {
      cancelAttack();
      var previousLevel = state.level;
      state.level = levelOf(field.value, policy);
      state.target = TARGET_PIECES[state.level];
      if (state.level !== previousLevel) status.textContent = stages[state.level] + ' — ' + suffix;
      root.dataset.castleLevel = String(state.level);
      if (!canAnimate()) {
        stopFrame();
        state.built = state.target;
      }
      draw();
      if (canAnimate()) {
        startFrame();
        scheduleAttack();
      }
    }

    function suspend() {
      cancelAttack();
      stopFrame();
      draw();
    }

    function resume() {
      if (!canAnimate()) return;
      startFrame();
    }

    field.addEventListener('input', update);
    field.addEventListener('change', update);
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') suspend();
      else resume();
    });
    if (container) {
      container.addEventListener('toggle', function () {
        if (!container.open) suspend();
        else resume();
      });
    }
    if (typeof IntersectionObserver !== 'undefined') {
      var observer = new IntersectionObserver(function (entries) {
        visible = entries[0].isIntersecting;
        if (visible) resume();
        else suspend();
      });
      observer.observe(root);
    }
    if (media) {
      var onMotionChange = function () {
        reduced = media.matches;
        if (reduced) {
          suspend();
          state.built = state.target;
          draw();
        } else {
          resume();
        }
      };
      if (media.addEventListener) media.addEventListener('change', onMotionChange);
      else if (media.addListener) media.addListener(onMotionChange);
    }

    state.level = levelOf(field.value, policy);
    state.target = TARGET_PIECES[state.level];
    state.built = state.target;
    status.textContent = stages[state.level] + ' — ' + suffix;
    root.dataset.castleLevel = String(state.level);
    draw();
  }

  function init() {
    document.querySelectorAll(ROOT_SELECTOR).forEach(mount);
    document.querySelectorAll('[data-castle-reveal]').forEach(function (button) {
      if (button.dataset.castleRevealMounted === 'true') return;
      var selector = button.getAttribute('data-target');
      var field = selector ? document.querySelector(selector) : null;
      if (!field) return;
      button.dataset.castleRevealMounted = 'true';
      button.addEventListener('click', function () {
        var showing = field.type === 'text';
        field.type = showing ? 'password' : 'text';
        button.setAttribute('aria-pressed', String(!showing));
        button.textContent = showing
          ? (button.getAttribute('data-label-show') || 'Show')
          : (button.getAttribute('data-label-hide') || 'Hide');
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
