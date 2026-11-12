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
  var ART_SCALE = 2;
  var BUILD_SPEED = 27;
  var ATTACK_DELAY_MS = 1150;
  var ATTACK_DURATION_MS = 4400;
  var DEFAULT_STAGES = ['Unprotected', 'Fence', 'Palisade', 'Stone walls', 'Castle', 'Fortified castle'];
  var TARGET_PIECES = [0, 20, 43, 69, 91, 105];
  var COLOR = {
    sky: '#111d36', skyLight: '#263452', moon: '#ffe6ae',
    hill: '#2c5460', hillFar: '#263c56', ground: '#244a42',
    wall: '#aab1c5', wallDark: '#5e6887', wallLight: '#e2d7cb',
    wood: '#a97855', woodDark: '#523e51', water: '#327c9b',
    ripple: '#9be4d9', banner: '#e58a76', raccoon: '#c0c2c0',
    raccoonDark: '#353e54', stars: '#eff0df', leaf: '#73c5a7',
    leafDark: '#318a86', ember: '#ffc26e', fire: '#f17d65',
    gold: '#f5cd75', dust: '#e4c2a3', grass: '#39775c',
    stoneDeep: '#414e6f', stoneMid: '#8996b0', brick: '#d3c6c1',
    roof: '#964b64', roofLight: '#dc8491', moss: '#487a73',
    spruce: '#1b4a50', spruceLight: '#326a66', outline: '#25334e',
    light: '#ffdc90', cloud: '#34425d',
    rim: '#fff4d8', stoneShade: '#485672', copper: '#eea775',
    sparkle: '#fcf1be', stoneTop: '#e9e3db',
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

  function microRect(ctx, x, y, w, h, color) {
    ctx.fillStyle = color;
    ctx.fillRect(x, y, w, h);
  }

  function glow(ctx, x, y, radius, color, opacity) {
    ctx.save();
    ctx.globalAlpha = opacity;
    var gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
    gradient.addColorStop(0, color);
    gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(x - radius, y - radius, radius * 2, radius * 2);
    ctx.restore();
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

    // Level 5: golden sparks gather before the dragon appears above the keep.
    for (i = 0; i < 14; i++) {
      pieces.push(piece(145 + (i * 13) % 44, 12 + (i * 7) % 29, 2, 2,
        i % 3 ? COLOR.gold : COLOR.leaf, 'spark'));
    }

    return pieces;
  }

  var PIECES = buildBlueprint();
  var STARS = [[12, 13], [46, 23], [71, 10], [125, 18], [173, 11], [201, 34], [248, 49], [22, 51]];

  function pine(ctx, x, y, size, lit) {
    rect(ctx, x - 1, y - size + 6, 3, size, COLOR.woodDark);
    for (var i = 0; i < 3; i++) {
      var span = Math.round((size - i * 2) * 0.4);
      polygon(ctx, [[x, y - size - 5 + i * 6], [x - span, y - 2 + i * 2],
        [x + span, y - 2 + i * 2]], lit && i === 1 ? COLOR.spruceLight : COLOR.spruce);
    }
    rect(ctx, x - 2, y - size + 4, 2, 3, COLOR.spruceLight);
  }

  function background(ctx, seconds, showChest) {
    // Stacked twilight bands preserve sharp pixels without gradients or image assets.
    rect(ctx, 0, 0, WIDTH, HEIGHT, COLOR.sky);
    rect(ctx, 0, 32, WIDTH, 28, '#192846');
    rect(ctx, 0, 60, WIDTH, 23, COLOR.skyLight);
    rect(ctx, 0, 82, WIDTH, 23, '#345267');

    // Moon bloom stays behind the pixel silhouette, preserving the original art style.
    glow(ctx, 231, 22, 36, '#ffe7a7', 0.18);
    // Pixel moon and its subtle halo.
    rect(ctx, 218, 10, 26, 26, '#354560');
    rect(ctx, 222, 7, 19, 28, '#3c4a64');
    rect(ctx, 225, 9, 14, 23, COLOR.moon);
    rect(ctx, 222, 13, 19, 16, COLOR.moon);
    rect(ctx, 229, 13, 4, 3, '#f7cb8b');
    rect(ctx, 235, 24, 3, 4, '#f7cb8b');
    rect(ctx, 220, 14, 4, 4, COLOR.skyLight);

    // Clouds, constellations and distant snowy peaks.
    rect(ctx, 11, 21, 25, 3, COLOR.cloud);
    rect(ctx, 15, 18, 16, 3, COLOR.cloud);
    rect(ctx, 37, 23, 11, 2, COLOR.cloud);
    rect(ctx, 181, 44, 20, 2, COLOR.cloud);
    rect(ctx, 185, 41, 12, 3, COLOR.cloud);
    STARS.forEach(function (star, index) {
      var size = index % 3 === 0 && Math.sin(seconds * 1.7 + index * 3) > 0.5 ? 2 : 1;
      rect(ctx, star[0], star[1], size, size, COLOR.stars);
      if (index === 3 || index === 5) {
        rect(ctx, star[0] - 2, star[1], 1, 1, '#8ab5d3');
        rect(ctx, star[0] + 2, star[1], 1, 1, '#8ab5d3');
      }
    });
    polygon(ctx, [[0, 90], [30, 61], [42, 68], [64, 44], [87, 77], [111, 57],
      [147, 93], [178, 56], [199, 79], [235, 58], [256, 78], [256, 120], [0, 120]], COLOR.hillFar);
    polygon(ctx, [[54, 55], [64, 44], [72, 57], [65, 54], [62, 59]], '#667992');
    polygon(ctx, [[173, 63], [178, 56], [185, 64], [180, 62]], '#61768b');
    polygon(ctx, [[0, 105], [38, 78], [66, 93], [99, 82], [133, 104],
      [181, 80], [211, 99], [256, 75], [256, 120], [0, 120]], COLOR.hill);

    // Framing trees make the fortress feel like a place rather than loose blocks.
    pine(ctx, 18, 105, 19, false);
    pine(ctx, 38, 106, 13, true);
    pine(ctx, 226, 103, 16, false);
    pine(ctx, 245, 105, 23, true);
    pine(ctx, 5, 105, 12, false);
    rect(ctx, 0, 106, WIDTH, 14, COLOR.ground);
    rect(ctx, 0, 105, WIDTH, 2, '#507b71');
    rect(ctx, 0, 113, WIDTH, 7, '#1c3b39');
    for (var i = 0; i < 22; i++) {
      var x = (i * 37 + 11) % WIDTH;
      rect(ctx, x, 108 + i % 3 * 3, 5, 2, i % 3 ? COLOR.grass : '#508368');
    }
    // A subtle band of reflected light adds depth to the water and distant shore.
    glow(ctx, 128, 109, 44, '#7ac1da', 0.10);
    // Water at the foot of the castle catches moonlight.
    rect(ctx, 58, 107, 144, 12, '#205b73');
    rect(ctx, 61, 108, 138, 2, COLOR.water);
    for (var r = 0; r < 9; r++) {
      var rx = 67 + (r * 19) % 129;
      var wave = Math.round(Math.sin(seconds * 2.5 + r * 1.6) * 1.5);
      rect(ctx, rx + wave, 112 + r % 3 * 2, 8, 1, r % 2 ? '#478da4' : '#76b8bb');
    }
    // Foreground riverbank and soft depth shadows.
    rect(ctx, 0, 115, 58, 5, '#264d43');
    rect(ctx, 201, 115, 55, 5, '#264d43');
    rect(ctx, 54, 111, 7, 5, COLOR.stoneDeep);
    rect(ctx, 198, 112, 9, 4, COLOR.stoneDeep);

    // The treasure is decorative and vanishes after a successful low-level raid.
    if (showChest) {
      rect(ctx, 123, 95, 17, 12, COLOR.outline);
      rect(ctx, 125, 93, 13, 5, COLOR.wood);
      rect(ctx, 126, 98, 11, 7, '#9f654b');
      rect(ctx, 130, 94, 3, 12, COLOR.gold);
      rect(ctx, 126, 100, 11, 2, COLOR.gold);
      rect(ctx, 131, 100, 2, 2, '#fff1b9');
    }
  }

  function brickwork(ctx, x, y, columns, rows, cellW, cellH) {
    for (var row = 0; row < rows; row++) {
      for (var col = 0; col < columns; col++) {
        var bx = x + col * cellW + (row % 2 ? Math.floor(cellW / 2) : 0);
        rect(ctx, bx, y + row * cellH, Math.max(2, cellW - 2), 1,
          (row + col) % 4 === 0 ? COLOR.wallLight : '#8895ac');
        if ((col + row * 3) % 5 === 0) {
          rect(ctx, bx + 1, y + row * cellH + 2, 2, 1, '#7987a0');
        }
      }
    }
  }

  function torch(ctx, x, y, seconds) {
    glow(ctx, x + 1, y - 3, 18, '#ffab57', 0.42);
    rect(ctx, x - 1, y + 4, 4, 2, COLOR.outline);
    rect(ctx, x, y + 1, 2, 5, COLOR.wood);
    rect(ctx, x - 2, y - 3, 5, 5, COLOR.ember);
    rect(ctx, x - 1, y - 5 - (Math.sin(seconds * 8 + x) > 0 ? 1 : 0), 3, 5, '#ffec9b');
    rect(ctx, x, y - 2, 1, 3, '#fff4c9');
  }

  function tower(ctx, x, y, seconds, side) {
    // Slate outline, inset masonry, shaped roof, narrow golden windows.
    rect(ctx, x - 2, y - 2, 27, 55, COLOR.outline);
    rect(ctx, x, y, 23, 51, COLOR.stoneMid);
    rect(ctx, x + 2, y + 2, 5, 47, '#b8bdc8');
    rect(ctx, x + 18, y + 2, 3, 48, COLOR.wallDark);
    // A beveled right face and stone cap give each tower a 2.5D volume.
    polygon(ctx, [[x + 21, y + 2], [x + 26, y - 2],
      [x + 26, y + 45], [x + 23, y + 51], [x + 21, y + 51]], COLOR.stoneShade);
    polygon(ctx, [[x - 4, y - 5], [x + 1, y - 9],
      [x + 28, y - 9], [x + 27, y - 5]], COLOR.stoneTop);
    rect(ctx, x + 1, y + 2, 1, 46, '#f4eadd');
    brickwork(ctx, x + 1, y + 5, 3, 7, 7, 6);
    rect(ctx, x - 4, y - 5, 31, 5, COLOR.outline);
    for (var i = 0; i < 4; i++) {
      rect(ctx, x - 3 + i * 8, y - 10, 5, 7, COLOR.wallLight);
      rect(ctx, x - 2 + i * 8, y - 9, 4, 2, '#fbebe0');
    }
    polygon(ctx, [[x - 4, y - 10], [x + 11, y - 25], [x + 27, y - 10]], COLOR.outline);
    polygon(ctx, [[x, y - 11], [x + 11, y - 22], [x + 23, y - 11]], COLOR.roof);
    polygon(ctx, [[x + 7, y - 17], [x + 11, y - 22], [x + 16, y - 17]], COLOR.roofLight);
    polygon(ctx, [[x + 23, y - 11], [x + 28, y - 12],
      [x + 14, y - 27], [x + 11, y - 22]], '#683b5a');
    rect(ctx, x + 4, y - 12, 18, 1, '#f2a6a0');
    for (var j = 0; j < 3; j++) rect(ctx, x + 3 + j * 6, y - 12 - j * 2, 3, 2, '#c47784');
    rect(ctx, x + 10, y + 17, 6, 13, COLOR.outline);
    glow(ctx, x + 13, y + 22, 13, COLOR.light, 0.25);
    rect(ctx, x + 11, y + 18, 4, 10, COLOR.light);
    rect(ctx, x + 11, y + 18, 1, 9, '#fff5c3');
    rect(ctx, x + 10, y + 24, 6, 2, COLOR.outline);
    rect(ctx, x - 3, y + 30, 28, 3, COLOR.outline);
    rect(ctx, x - 3, y + 33, 28, 2, COLOR.wallLight);
    if (side === 'left') torch(ctx, x + 25, y + 36, seconds);
    else torch(ctx, x - 3, y + 36, seconds);
  }

  function finishedCastle(ctx, seconds) {
    // Deliberate complete silhouette after the last construction piece settles.
    // The outlines, roofs, grout and lighting turn the wireframe into pixel-art.
    rect(ctx, 82, 70, 93, 36, COLOR.outline);
    rect(ctx, 84, 72, 89, 33, COLOR.stoneMid);
    rect(ctx, 86, 74, 4, 29, COLOR.wallLight);
    rect(ctx, 168, 73, 4, 31, COLOR.wallDark);
    polygon(ctx, [[175, 70], [182, 64], [182, 102],
      [175, 106], [171, 101]], COLOR.stoneShade);
    polygon(ctx, [[82, 70], [88, 64], [183, 64],
      [175, 70]], COLOR.stoneTop);
    brickwork(ctx, 87, 78, 11, 4, 8, 6);

    // Central keep with a stepped roof and masonry pilasters.
    rect(ctx, 105, 43, 48, 56, COLOR.outline);
    rect(ctx, 108, 45, 42, 52, COLOR.wall);
    rect(ctx, 110, 46, 5, 51, COLOR.wallLight);
    rect(ctx, 145, 46, 4, 51, COLOR.wallDark);
    polygon(ctx, [[150, 45], [157, 40], [157, 93],
      [151, 100], [149, 97]], '#58627d');
    polygon(ctx, [[105, 43], [112, 38], [157, 38],
      [153, 43]], COLOR.stoneTop);
    rect(ctx, 111, 47, 1, 47, COLOR.rim);
    brickwork(ctx, 116, 51, 4, 7, 8, 6);
    rect(ctx, 102, 42, 54, 5, COLOR.outline);
    for (var i = 0; i < 6; i++) {
      rect(ctx, 103 + i * 9, 35, 6, 9, COLOR.wallLight);
      rect(ctx, 104 + i * 9, 35, 4, 2, '#f5e8df');
    }
    polygon(ctx, [[109, 35], [128, 15], [149, 35]], COLOR.outline);
    polygon(ctx, [[114, 34], [128, 18], [144, 34]], COLOR.roof);
    polygon(ctx, [[119, 29], [128, 18], [133, 24]], COLOR.roofLight);
    polygon(ctx, [[144, 34], [151, 33], [133, 15],
      [128, 18]], '#6b395a');
    rect(ctx, 117, 33, 23, 1, '#f0a19e');
    for (var j = 0; j < 4; j++) {
      rect(ctx, 120 + j * 6, 31 - j % 2 * 2, 4, 2, '#b35d72');
    }
    rect(ctx, 128, 10, 2, 10, COLOR.outline);
    rect(ctx, 130, 10, 13, 7, COLOR.banner);
    rect(ctx, 130, 16, 9, 3, COLOR.roofLight);
    rect(ctx, 132, 12, 3, 3, COLOR.gold);

    // Keep: arch windows and warm light suggest an inhabited stronghold.
    for (var w = 0; w < 2; w++) {
      var wx = 118 + w * 22;
      rect(ctx, wx - 1, 55, 9, 17, COLOR.outline);
      glow(ctx, wx + 3, 64, 17, COLOR.light, 0.18);
      rect(ctx, wx, 58, 7, 13, '#f4bc77');
      rect(ctx, wx + 1, 57, 5, 2, COLOR.gold);
      rect(ctx, wx + 3, 57, 1, 14, COLOR.outline);
      rect(ctx, wx, 64, 7, 1, COLOR.outline);
      rect(ctx, wx - 2, 71, 10, 3, COLOR.wallLight);
    }
    rect(ctx, 112, 77, 34, 4, COLOR.outline);
    rect(ctx, 114, 81, 31, 3, '#dad0ce');
    rect(ctx, 114, 85, 31, 13, COLOR.wallDark);
    brickwork(ctx, 114, 87, 4, 2, 8, 5);

    tower(ctx, 72, 55, seconds, 'left');
    tower(ctx, 163, 55, seconds, 'right');

    // Outer crenellations and a readable arched gate with brass hinges.
    rect(ctx, 76, 94, 108, 4, COLOR.outline);
    for (var c = 0; c < 12; c++) {
      rect(ctx, 77 + c * 9, 88, 6, 8, COLOR.wallLight);
      rect(ctx, 78 + c * 9, 88, 3, 2, '#f8e5d8');
    }
    rect(ctx, 76, 101, 107, 5, COLOR.stoneDeep);
    rect(ctx, 116, 85, 29, 22, COLOR.outline);
    rect(ctx, 119, 89, 23, 18, COLOR.woodDark);
    rect(ctx, 122, 87, 17, 20, '#825765');
    rect(ctx, 126, 87, 9, 19, '#a16a6a');
    rect(ctx, 122, 89, 2, 14, '#c68f74');
    rect(ctx, 129, 89, 2, 18, COLOR.outline);
    rect(ctx, 124, 96, 3, 3, COLOR.gold);
    rect(ctx, 134, 96, 3, 3, COLOR.gold);
    rect(ctx, 119, 103, 23, 3, COLOR.outline);

    // Bridge leading out over the moat: weathered planks and rope detail.
    rect(ctx, 115, 106, 34, 9, COLOR.outline);
    polygon(ctx, [[117, 107], [147, 107], [152, 111],
      [121, 111]], '#d5a478');
    rect(ctx, 117, 111, 30, 4, COLOR.wood);
    for (var p = 0; p < 5; p++) {
      rect(ctx, 118 + p * 6, 107, 1, 7, '#d39b72');
      rect(ctx, 118 + p * 6, 112, 4, 1, COLOR.woodDark);
    }
    rect(ctx, 112, 99, 2, 14, COLOR.woodDark);
    rect(ctx, 150, 99, 2, 14, COLOR.woodDark);
    rect(ctx, 113, 100, 38, 1, COLOR.gold);

    // Small foliage and glowing flowers soften the stone-heavy palette.
    for (var f = 0; f < 6; f++) {
      var fx = f < 3 ? 63 + f * 7 : 185 + (f - 3) * 6;
      rect(ctx, fx, 101 - f % 2 * 2, 8, 5, COLOR.spruce);
      rect(ctx, fx + 2, 98 - f % 2 * 2, 5, 5, COLOR.grass);
      rect(ctx, fx + 4, 100 - f % 2 * 2, 2, 2, f % 2 ? '#e9b5a7' : COLOR.gold);
    }
    torch(ctx, 111, 96, seconds);
    torch(ctx, 149, 96, seconds);
  }

  function finishedWall(ctx) {
    // Texture and defensive details at the stone-wall stage.
    rect(ctx, 79, 83, 101, 24, COLOR.outline);
    rect(ctx, 81, 85, 97, 20, COLOR.stoneMid);
    rect(ctx, 82, 86, 3, 18, COLOR.wallLight);
    rect(ctx, 174, 86, 3, 18, COLOR.wallDark);
    polygon(ctx, [[176, 83], [183, 78], [183, 101],
      [177, 107], [175, 105]], COLOR.stoneShade);
    polygon(ctx, [[79, 83], [86, 78], [183, 78],
      [176, 83]], COLOR.stoneTop);
    brickwork(ctx, 85, 88, 11, 3, 8, 6);
    for (var c = 0; c < 11; c++) {
      rect(ctx, 80 + c * 9, 78, 7, 8, COLOR.outline);
      rect(ctx, 81 + c * 9, 78, 5, 7, COLOR.wallLight);
    }
    rect(ctx, 120, 91, 19, 16, COLOR.outline);
    rect(ctx, 123, 95, 13, 12, COLOR.wood);
    rect(ctx, 128, 95, 2, 12, COLOR.woodDark);
  }

  function dragon(ctx, count, seconds) {
    var reveal = range(count, TARGET_PIECES[4], TARGET_PIECES[5]);
    if (reveal <= 0) return;
    var bob = Math.round(Math.sin(seconds * 2.7) * 2);
    var flap = Math.round(Math.sin(seconds * 6.2) * 5);
    ctx.save();
    ctx.translate(0, 5 + bob - Math.round((1 - reveal) * 12));
    // Tail and swept wings are drawn first to retain a clear dragon silhouette.
    polygon(ctx, [[147, 23], [134, 29], [132, 35], [127, 35], [128, 29],
      [137, 18], [149, 17]], COLOR.outline);
    polygon(ctx, [[147, 22], [137, 28], [133, 33], [132, 28], [142, 19]], COLOR.leafDark);
    polygon(ctx, [[153, 27], [140, 13], [142, 2 + flap], [150, 8 + flap], [157, 2 + flap],
      [166, 9 + flap], [172, 4 + flap], [173, 20], [162, 31]], COLOR.outline);
    polygon(ctx, [[155, 26], [143, 13], [144, 7 + flap], [152, 13 + flap], [157, 6 + flap],
      [165, 15 + flap], [170, 9 + flap], [170, 19], [162, 29]], '#327d84');
    polygon(ctx, [[153, 20], [149, 11 + flap], [156, 15], [164, 17], [164, 24]], '#b0e3b6');
    polygon(ctx, [[156, 24], [147, 16], [152, 15], [159, 19], [166, 15],
      [164, 27]], COLOR.leaf);
    // Scaled torso, belly and little spines.
    polygon(ctx, [[147, 21], [156, 15], [176, 17], [183, 23],
      [175, 33], [156, 33], [145, 28]], COLOR.outline);
    polygon(ctx, [[149, 22], [157, 17], [175, 19], [181, 24],
      [173, 30], [156, 31], [147, 27]], COLOR.leafDark);
    rect(ctx, 158, 26, 17, 5, COLOR.leaf);
    microRect(ctx, 160.5, 25.5, 4, 1, '#c4f0bf');
    microRect(ctx, 169.5, 22.5, 3, 1, '#bde9ba');
    for (var i = 0; i < 4; i++) {
      polygon(ctx, [[150 + i * 6, 19], [153 + i * 6, 13],
        [156 + i * 6, 19]], COLOR.gold);
    }
    rect(ctx, 156, 30, 4, 7, COLOR.outline);
    rect(ctx, 158, 34, 5, 2, COLOR.leafDark);
    rect(ctx, 172, 29, 4, 7, COLOR.outline);
    rect(ctx, 174, 34, 6, 2, COLOR.leafDark);
    // Profile head, horn, amber eye, articulated jaw and nostrils.
    polygon(ctx, [[177, 19], [183, 14], [194, 14], [196, 21],
      [207, 23], [209, 27], [198, 32], [187, 30], [181, 27]], COLOR.outline);
    polygon(ctx, [[180, 20], [185, 16], [193, 17], [194, 23],
      [205, 25], [205, 27], [198, 29], [187, 27]], COLOR.leaf);
    polygon(ctx, [[185, 16], [186, 9], [191, 15]], COLOR.gold);
    polygon(ctx, [[192, 17], [197, 10], [199, 23]], COLOR.leafDark);
    rect(ctx, 190, 20, 4, 4, COLOR.outline);
    rect(ctx, 191, 20, 2, 2, COLOR.gold);
    rect(ctx, 203, 24, 2, 2, COLOR.outline);
    rect(ctx, 194, 29, 10, 2, '#d4a77c');
    rect(ctx, 198, 30, 2, 2, '#fff3d2');
    if (seconds > 0) {
      var breath = Math.sin(seconds * 3.7);
      if (breath > 0.35) {
        glow(ctx, 209, 27, 14, '#fcaa63', (breath - 0.35) * 0.34);
        rect(ctx, 210, 24, 3, 3, COLOR.ember);
        rect(ctx, 214, 23, 2, 2, '#ffe9aa');
      }
    }
    ctx.restore();
    if (reveal < 1) {
      for (var s = 0; s < 5; s++) {
        rect(ctx, 143 + s * 12, 5 + (s * 13) % 34,
          2, 2, s % 2 ? COLOR.gold : '#c4f9d3');
      }
    }
  }

  function castleDetails(ctx, count, seconds) {
    if (count >= TARGET_PIECES[4] - 4) {
      ctx.save();
      ctx.globalAlpha = range(count, TARGET_PIECES[4] - 4, TARGET_PIECES[4]);
      finishedCastle(ctx, seconds);
      ctx.restore();
    } else if (count >= TARGET_PIECES[3] - 2) {
      ctx.save();
      ctx.globalAlpha = range(count, TARGET_PIECES[3] - 2, TARGET_PIECES[3]);
      finishedWall(ctx);
      ctx.restore();
    }
    if (count > TARGET_PIECES[4]) dragon(ctx, count, seconds);
  }

  function drawPiece(ctx, part, drop) {
    var y = part.y - drop;
    if (part.detail === 'spark') {
      rect(ctx, part.x, y, 2, 2, part.color);
      rect(ctx, part.x + 1, y - 2, 1, 1, COLOR.wallLight);
      return;
    }
    var isWater = part.color === COLOR.water;
    var isTimber = part.color === COLOR.wood || part.color === COLOR.woodDark;
    if (!isWater) rect(ctx, part.x + 1, y + 2, part.w, part.h, COLOR.outline);
    rect(ctx, part.x, y, part.w, part.h, part.color);
    if (!isWater) {
      // Highlighted top and shaded side turn each block into a small solid volume.
      rect(ctx, part.x, y, Math.max(1, part.w - 2), 1,
        isTimber ? '#e2af7c' : COLOR.stoneTop);
      rect(ctx, part.x + part.w - 2, y + 2, 2,
        Math.max(1, part.h - 2), isTimber ? COLOR.woodDark : COLOR.stoneShade);
    }
    if (part.detail === 'brick') {
      rect(ctx, part.x + 2, y + 2, Math.max(1, part.w - 4), 1, COLOR.wallLight);
      rect(ctx, part.x + part.w - 2, y + 3, 1, Math.max(1, part.h - 5), COLOR.wallDark);
      rect(ctx, part.x + 3, y + part.h - 2, Math.max(1, part.w - 6), 1, COLOR.stoneDeep);
    }
  }

  function drawConstructionDust(ctx, count, seconds) {
    var last = Math.min(PIECES.length - 1, Math.floor(count) - 1);
    for (var i = Math.max(0, last - 6); i <= last; i++) {
      var part = PIECES[i];
      if (part.detail === 'spark') continue;
      var age = count - i;
      if (age >= 5.6 || age <= 0) continue;
      var life = 1 - age / 5.6;
      var drift = 5 * (1 - life);
      ctx.save();
      ctx.globalAlpha = Math.min(0.78, life);
      var x = part.x + part.w / 2;
      var y = part.y + part.h - 1;
      rect(ctx, x - 3 - drift, y - 1 - drift, 2, 2, COLOR.dust);
      rect(ctx, x + 2 + drift, y - 2 - drift * 0.6, 2, 1, COLOR.ember);
      microRect(ctx, x + 1 - drift * 0.4, y - 4 - drift, 1, 1, COLOR.rim);
      ctx.restore();
    }
  }

  function drawRevealBurst(ctx, level, pulseMs) {
    if (pulseMs <= 0 || level === 0) return;
    var phase = 1 - pulseMs / 820;
    var size = phase * 24;
    for (var i = 0; i < 14; i++) {
      var x = 129 + Math.cos(i * 2.399) * (24 + size);
      var y = 74 + Math.sin(i * 2.399) * (15 + size * 0.55);
      ctx.save();
      ctx.globalAlpha = 0.9 * Math.pow(1 - phase, 1.7);
      rect(ctx, x, y, i % 3 === 0 ? 2 : 1, i % 4 === 0 ? 2 : 1,
        i % 2 ? COLOR.sparkle : COLOR.ember);
      ctx.restore();
    }
  }

  function building(ctx, count, animate) {
    var settled = Math.min(PIECES.length, Math.floor(count));
    for (var index = 0; index < settled; index++) drawPiece(ctx, PIECES[index], 0);

    // Animate only the next falling piece. Previous pieces remain stationary.
    if (animate && settled < PIECES.length && count > 0 && count < PIECES.length && count - settled > 0.001) {
      var fraction = count - settled;
      var drop = Math.round(Math.pow(1 - fraction, 2) * 24);
      var falling = PIECES[settled];
      if (falling.detail !== 'spark' && fraction < 0.8) {
        // The hanging cable and sparks make the construction sequence readable.
        var hookX = falling.x + falling.w / 2;
        rect(ctx, hookX, falling.y - drop - 19, 1, 16, COLOR.rope);
        rect(ctx, hookX - 2, falling.y - drop - 4, 5, 2, COLOR.gold);
        microRect(ctx, hookX - 4, falling.y - drop - 10, 1, 1, COLOR.sparkle);
      }
      drawPiece(ctx, falling, drop);
      if (fraction > 0.76 && fraction < 0.98) {
        var p = PIECES[settled];
        rect(ctx, p.x - 2, p.y + p.h - 2, 2, 2, COLOR.dust);
        rect(ctx, p.x + p.w + 1, p.y + p.h - 4, 2, 2, COLOR.dust);
      }
    }

    if (animate && count > 0) drawConstructionDust(ctx, count, 0);

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
      rect(ctx, -13, 6, 29, 10, COLOR.spruce);
      rect(ctx, -8, 1, 21, 10, COLOR.grass);
      rect(ctx, -3, -2, 12, 6, COLOR.spruceLight);
      rect(ctx, 1, 5, 2, 2, COLOR.stars);
      ctx.restore();
      return;
    }
    // Banded tail and rounded body, with bold, recognizable eye mask.
    rect(ctx, -20, 5, 11, 8, COLOR.outline);
    rect(ctx, -22, 7, 5, 4, COLOR.raccoon);
    rect(ctx, -19, 5, 4, 6, COLOR.raccoon);
    rect(ctx, -15, 6, 3, 6, COLOR.raccoonDark);
    rect(ctx, -12, 7, 3, 6, COLOR.raccoon);
    rect(ctx, -9, 5, 19, 12, COLOR.outline);
    rect(ctx, -7, 6, 16, 10, options.wet ? '#80b6c2' : '#9d9da6');
    rect(ctx, 2, 8, 6, 5, COLOR.raccoon);
    rect(ctx, -5, 15 + legSwing, 5, 5, COLOR.outline);
    rect(ctx, 6, 15 - legSwing, 5, 5, COLOR.outline);
    // Ear tips, face, bright cheeks and a small nose.
    rect(ctx, -1, -6, 5, 7, COLOR.outline);
    rect(ctx, 11, -6, 5, 7, COLOR.outline);
    rect(ctx, 0, -4, 4, 5, COLOR.raccoon);
    rect(ctx, 12, -4, 3, 5, COLOR.raccoon);
    rect(ctx, -2, -1, 18, 14, COLOR.outline);
    rect(ctx, 0, 1, 16, 10, COLOR.raccoon);
    rect(ctx, 1, 3, 6, 5, COLOR.raccoonDark);
    rect(ctx, 10, 3, 5, 5, COLOR.raccoonDark);
    rect(ctx, 3, 4, 2, 2, COLOR.stars);
    rect(ctx, 11, 4, 2, 2, COLOR.stars);
    rect(ctx, 6, 9, 5, 3, COLOR.wallLight);
    rect(ctx, 7, 8, 3, 3, COLOR.outline);
    if (options.sack) {
      polygon(ctx, [[-14, 7], [-7, 5], [-4, 15], [-16, 18]], COLOR.gold);
      rect(ctx, -11, 5, 4, 3, COLOR.woodDark);
      rect(ctx, -11, 10, 2, 2, '#fff0af');
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
    // A curved ember stream from the dragon, safely contained in the canvas.
    if (progress > 0) glow(ctx, 209, 38 + progress * 29, 22, '#ff9558', 0.34);
    for (var i = 0; i < 11; i++) {
      var t = range(progress * 13, i, i + 1);
      if (!t) continue;
      var x = 203 + i * 2 + Math.sin(i * 2.1) * 3;
      var y = 28 + i * 5 + Math.sin(i * 1.6) * 3;
      var radius = 5 + i % 3;
      rect(ctx, x - 1, y - 1, radius + 2, radius + 2, COLOR.fire);
      rect(ctx, x + 1, y, Math.max(3, radius - 2), radius, COLOR.ember);
      rect(ctx, x + 2, y + 2, 2, 3, '#fff1aa');
      rect(ctx, x + Math.sin(i * 3) * 5, y - 7, 1, 2, COLOR.sparkle);
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
        glow(ctx, 165, 104, 18, COLOR.ripple, 0.24);
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
      if (t > 1.9 && t < 2.35) {
        var impact = range(t, 1.9, 2.35);
        for (var spark = 0; spark < 6; spark++) {
          rect(ctx, 199 + Math.cos(spark * 1.3) * impact * 10,
            73 + Math.sin(spark * 1.3) * impact * 7, 2, 2,
            spark % 2 ? COLOR.ember : COLOR.sparkle);
        }
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
    canvas.width = WIDTH * ART_SCALE;
    canvas.height = HEIGHT * ART_SCALE;
    ctx.setTransform(ART_SCALE, 0, 0, ART_SCALE, 0, 0);
    ctx.imageSmoothingEnabled = true;
    var policy = policyOf(root);
    var stages = stageNames(root);
    var suffix = root.getAttribute('data-visual-suffix') || 'Visual guide only';
    var media = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var reduced = media ? media.matches : false;
    var container = root.closest('details');
    var visible = true;
    var ambientTimer = 0;
    var raf = 0;
    var timer = 0;
    var previousTimestamp = 0;
    var state = {
      level: 0, target: 0, built: 0, pendingAttack: false, attackMs: -1,
      seconds: 0, pulseMs: 0,
    };

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
      building(ctx, state.built,
        canAnimate() && Math.abs(state.built - state.target) > 0.001);
      castleDetails(ctx, state.built, state.seconds);
      drawRevealBurst(ctx, state.level, state.pulseMs);
      if (state.attackMs >= 0) raid(ctx, state.level, state.attackMs);
      else raccoon(ctx, 217, 88, 0, { facing: 'left', walk: false, wet: false, hidden: false, sack: false });
      // Small heraldic shields make progress readable without relying on color alone.
      for (var i = 0; i < 5; i++) {
        var x = 9 + i * 11;
        rect(ctx, x, 6, 9, 1, COLOR.outline);
        rect(ctx, x + 1, 7, 7, 5, i < state.level ? COLOR.gold : '#3f516b');
        rect(ctx, x + 2, 12, 5, 2, i < state.level ? '#bb806b' : '#2c405c');
        rect(ctx, x + 3, 14, 3, 1, COLOR.outline);
        if (i < state.level) rect(ctx, x + 4, 8, 2, 3, '#ffedb8');
      }
    }

    function frame(timestamp) {
      raf = 0;
      if (!canAnimate()) return;
      var elapsed = previousTimestamp ? clamp(timestamp - previousTimestamp, 0, 50) : 16;
      previousTimestamp = timestamp;
      state.seconds += elapsed / 1000;
      if (state.pulseMs > 0) state.pulseMs = Math.max(0, state.pulseMs - elapsed);
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
      if (Math.abs(state.built - state.target) > 0.001 || state.attackMs >= 0 || state.pulseMs > 0) {
        raf = window.requestAnimationFrame(frame);
      } else {
        previousTimestamp = 0;
      }
    }

    function startFrame() {
      if (!raf && canAnimate()) raf = window.requestAnimationFrame(frame);
    }

    function stopAmbient() {
      if (ambientTimer) window.clearTimeout(ambientTimer);
      ambientTimer = 0;
    }

    function scheduleAmbient() {
      if (ambientTimer || !canAnimate() || !field.value) return;
      ambientTimer = window.setTimeout(function () {
        ambientTimer = 0;
        if (!canAnimate()) return;
        // Low-frequency idle animation avoids an unbounded 60 fps render loop.
        if (!raf) {
          state.seconds += 0.14;
          draw();
        }
        scheduleAmbient();
      }, 140);
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
      stopAmbient();
      var previousLevel = state.level;
      state.level = levelOf(field.value, policy);
      state.target = TARGET_PIECES[state.level];
      if (state.level > previousLevel && canAnimate()) state.pulseMs = 820;
      else if (state.level <= previousLevel) state.pulseMs = 0;
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
        scheduleAmbient();
      }
    }

    function suspend() {
      stopAmbient();
      cancelAttack();
      stopFrame();
      draw();
    }

    function resume() {
      if (!canAnimate()) return;
      startFrame();
      scheduleAmbient();
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
