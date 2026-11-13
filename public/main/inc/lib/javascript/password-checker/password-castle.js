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
  var ATTACK_DURATION_MS = 5200;
  var DEFAULT_STAGES = ['Unprotected', 'Fence', 'Palisade', 'Stone walls', 'Castle', 'Fortified castle'];
  var TARGET_PIECES = [0, 20, 43, 69, 91, 105];
  var COLOR = {
    sky: '#111d36', skyLight: '#263452', moon: '#ffe6ae',
    hill: '#2c5460', hillFar: '#263c56', ground: '#244a42',
    wall: '#aab1c5', wallDark: '#5e6887', wallLight: '#e2d7cb',
    wood: '#a97855', woodDark: '#523e51', rope: '#e4c2a3', water: '#327c9b',
    ripple: '#9be4d9', banner: '#23558c', stars: '#eff0df', leaf: '#73c5a7',
    leafDark: '#318a86', ember: '#ffc26e', fire: '#f17d65',
    gold: '#f5cd75', dust: '#e4c2a3', grass: '#39775c',
    stoneDeep: '#414e6f', stoneMid: '#8996b0', brick: '#d3c6c1',
    spruce: '#1b4a50', spruceLight: '#326a66', outline: '#25334e',
    light: '#ffdc90', cloud: '#34425d',
    rim: '#fff4d8', stoneShade: '#485672', copper: '#eea775',
    sparkle: '#fcf1be', stoneTop: '#e9e3db',
    fox: '#df8240', foxLight: '#ffbc69', foxDark: '#92503e', foxCream: '#fff0c3',
    guardian: '#0568ad', guardianLight: '#34bfe7', guardianDark: '#052d69',
    guardianRim: '#d4f2fa', guardianCrest: '#ff842d', guardianCrestLight: '#ffd078',
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

    // Level 4: stone towers, gatehouse and crenellated battlements.
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
    pieces.push(piece(109, 36, 7, 8, COLOR.wallLight));
    pieces.push(piece(123, 36, 7, 8, COLOR.wallLight));
    pieces.push(piece(137, 36, 7, 8, COLOR.wallLight));
    pieces.push(piece(121, 62, 13, 13, COLOR.woodDark));

    // Level 5: reinforce the defenses; the chameleon appears on the battlements.
    for (i = 0; i < 8; i++) {
      pieces.push(piece(101 + i * 7, 89, 3, 18, COLOR.stoneDeep));
    }
    for (i = 0; i < 6; i++) {
      pieces.push(piece(77 + i * 21, 97, 9, 9, i % 2 ? COLOR.wall : COLOR.wallDark));
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

  function background(ctx, seconds) {
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

    // The chest is drawn in front of the weaker structures to keep the raid readable.
  }

  function treasureChest(ctx, x, y) {
    rect(ctx, x, y + 2, 17, 12, COLOR.outline);
    rect(ctx, x + 2, y, 13, 5, COLOR.wood);
    rect(ctx, x + 3, y + 5, 11, 7, '#9f654b');
    rect(ctx, x + 7, y + 1, 3, 12, COLOR.gold);
    rect(ctx, x + 3, y + 7, 11, 2, COLOR.gold);
    rect(ctx, x + 8, y + 7, 2, 2, '#fff1b9');
    rect(ctx, x + 3, y + 1, 9, 1, '#e2af7c');
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

  function arrowSlit(ctx, x, y, height) {
    rect(ctx, x - 1, y - 1, 4, height + 2, COLOR.wallLight);
    rect(ctx, x, y, 2, height, COLOR.outline);
    rect(ctx, x + 1, y + 1, 1, height - 2, COLOR.stoneDeep);
    rect(ctx, x - 2, y + Math.floor(height / 2), 6, 2, COLOR.outline);
  }

  function battlements(ctx, x, y, count, spacing) {
    rect(ctx, x - 2, y + 6, count * spacing + 5, 4, COLOR.outline);
    rect(ctx, x - 2, y + 7, count * spacing + 4, 2, COLOR.stoneTop);
    for (var i = 0; i < count; i++) {
      rect(ctx, x + i * spacing - 1, y - 1, spacing - 1, 9, COLOR.outline);
      rect(ctx, x + i * spacing, y, spacing - 3, 7, COLOR.wallLight);
      rect(ctx, x + i * spacing + 1, y, spacing - 4, 2, COLOR.stoneTop);
      rect(ctx, x + i * spacing + spacing - 4, y + 2, 1, 5, COLOR.wallDark);
    }
  }

  function tower(ctx, x, y, side) {
    // Thick tower shafts, corner buttresses, arrow slits and walkable battlements.
    rect(ctx, x - 3, y - 8, 29, 61, COLOR.outline);
    rect(ctx, x - 1, y - 6, 25, 58, COLOR.stoneMid);
    rect(ctx, x + 1, y - 4, 5, 54, '#b7bdca');
    rect(ctx, x + 20, y - 5, 4, 56, COLOR.stoneShade);
    polygon(ctx, [[x + 23, y - 6], [x + 29, y - 11],
      [x + 29, y + 46], [x + 24, y + 53]], COLOR.stoneShade);
    brickwork(ctx, x + 2, y + 1, 3, 7, 7, 7);
    battlements(ctx, x - 3, y - 18, 4, 8);
    arrowSlit(ctx, x + 10, y + 9, 16);
    arrowSlit(ctx, x + 10, y + 34, 10);
    rect(ctx, x - 6, y + 48, 33, 5, COLOR.outline);
    rect(ctx, x - 6, y + 49, 33, 3, COLOR.stoneTop);
    // Exterior masonry is wider at ground level to deflect direct attacks.
    if (side === 'left') {
      polygon(ctx, [[x - 4, y + 40], [x + 3, y + 41],
        [x + 1, y + 53], [x - 10, y + 53]], COLOR.wallDark);
      rect(ctx, x - 7, y + 51, 9, 3, COLOR.stoneTop);
    } else {
      polygon(ctx, [[x + 20, y + 41], [x + 27, y + 40],
        [x + 33, y + 53], [x + 23, y + 53]], COLOR.wallDark);
      rect(ctx, x + 23, y + 51, 9, 3, COLOR.stoneTop);
    }
  }

  function finishedCastle(ctx) {
    // A defensive medieval fortress: no decorative roofs, glass or torches.
    // Outer curtain wall, stone masonry and a projecting parapet.
    rect(ctx, 80, 68, 99, 39, COLOR.outline);
    rect(ctx, 82, 70, 95, 35, COLOR.stoneMid);
    rect(ctx, 84, 71, 5, 34, COLOR.wallLight);
    rect(ctx, 172, 71, 5, 34, COLOR.stoneShade);
    polygon(ctx, [[177, 68], [184, 62], [184, 103],
      [178, 107], [175, 101]], COLOR.stoneShade);
    brickwork(ctx, 87, 73, 11, 5, 8, 6);
    battlements(ctx, 82, 59, 12, 8);

    // Central stone keep and raised gatehouse, with no pitched roof.
    rect(ctx, 103, 42, 54, 64, COLOR.outline);
    rect(ctx, 106, 44, 48, 61, COLOR.wall);
    rect(ctx, 108, 45, 5, 58, COLOR.wallLight);
    rect(ctx, 151, 44, 3, 60, COLOR.wallDark);
    polygon(ctx, [[154, 44], [160, 38], [160, 97],
      [155, 106]], COLOR.stoneShade);
    brickwork(ctx, 116, 48, 4, 8, 9, 7);
    battlements(ctx, 105, 32, 6, 9);
    // Very narrow arrow slits replace the large, illuminated palace windows.
    arrowSlit(ctx, 115, 56, 14);
    arrowSlit(ctx, 145, 56, 14);
    arrowSlit(ctx, 92, 80, 12);
    arrowSlit(ctx, 168, 80, 12);

    tower(ctx, 71, 55, 'left');
    tower(ctx, 164, 55, 'right');

    // Stone-buttressed gateway with a clearly visible treasure under a portcullis.
    rect(ctx, 113, 80, 36, 27, COLOR.outline);
    rect(ctx, 116, 83, 30, 24, COLOR.wallDark);
    rect(ctx, 120, 84, 22, 23, COLOR.outline);
    rect(ctx, 122, 87, 18, 20, '#263146');
    treasureChest(ctx, 123, 92);
    for (var bar = 0; bar < 6; bar++) {
      rect(ctx, 122 + bar * 4, 85, 2, 22, COLOR.stoneTop);
    }
    for (var rung = 0; rung < 3; rung++) {
      rect(ctx, 122, 89 + rung * 6, 20, 2, COLOR.wallDark);
    }
    rect(ctx, 117, 81, 30, 4, COLOR.stoneTop);
    rect(ctx, 115, 103, 34, 4, COLOR.outline);
    rect(ctx, 115, 105, 34, 2, COLOR.wallLight);
    // Additional stone shoulders and dry stone foundations surround the gate.
    polygon(ctx, [[80, 96], [86, 99], [83, 107], [74, 107]], COLOR.wallDark);
    polygon(ctx, [[177, 96], [172, 99], [175, 107], [185, 107]], COLOR.wallDark);
    rect(ctx, 73, 105, 112, 3, COLOR.stoneDeep);
    // Short, utilitarian drawbridge over the moat.
    rect(ctx, 117, 108, 30, 7, COLOR.outline);
    rect(ctx, 119, 109, 27, 5, COLOR.wood);
    for (var plank = 0; plank < 5; plank++) {
      rect(ctx, 120 + plank * 5, 110, 1, 5, COLOR.woodDark);
    }
  }

  function fortifiedCastle(ctx, count) {
    var reveal = range(count, TARGET_PIECES[4], TARGET_PIECES[5]);
    if (!reveal) return;
    ctx.save();
    ctx.globalAlpha = reveal;
    // Sloped stone bastions, extra cross-bracing and stronger forward portcullis.
    polygon(ctx, [[72, 91], [79, 96], [77, 107], [63, 107]], COLOR.wallDark);
    polygon(ctx, [[188, 91], [181, 96], [184, 107], [198, 107]], COLOR.wallDark);
    rect(ctx, 64, 106, 15, 3, COLOR.stoneTop);
    rect(ctx, 182, 106, 16, 3, COLOR.stoneTop);
    rect(ctx, 109, 99, 41, 7, COLOR.outline);
    rect(ctx, 111, 100, 37, 4, COLOR.wall);
    for (var bar = 0; bar < 7; bar++) {
      rect(ctx, 117 + bar * 4, 82, 2, 25, COLOR.wallLight);
    }
    rect(ctx, 117, 91, 28, 2, COLOR.wallDark);
    rect(ctx, 117, 99, 28, 2, COLOR.wallDark);
    // Blue field standard, rather than the former dragon and red palace roof.
    rect(ctx, 163, 33, 2, 19, COLOR.outline);
    polygon(ctx, [[165, 34], [182, 37], [178, 43],
      [165, 42]], COLOR.banner);
    rect(ctx, 169, 37, 4, 2, COLOR.guardianRim);
    rect(ctx, 173, 39, 2, 2, COLOR.guardianRim);
    ctx.restore();
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

  function castleDetails(ctx, count) {
    if (count >= TARGET_PIECES[4] - 4) {
      ctx.save();
      ctx.globalAlpha = range(count, TARGET_PIECES[4] - 4, TARGET_PIECES[4]);
      finishedCastle(ctx);
      ctx.restore();
    } else if (count >= TARGET_PIECES[3] - 2) {
      ctx.save();
      ctx.globalAlpha = range(count, TARGET_PIECES[3] - 2, TARGET_PIECES[3]);
      finishedWall(ctx);
      ctx.restore();
    }
    fortifiedCastle(ctx, count);
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

  function fox(ctx, x, y, progress, options) {
    var step = options.walk ? (Math.sin(progress * 19) > 0 ? 2 : -2) : 0;
    var facing = options.facing === 'left' ? -1 : 1;
    ctx.save();
    ctx.translate(Math.round(x), Math.round(y));
    ctx.scale(facing, 1);

    if (options.hidden) {
      rect(ctx, -20, 10, 45, 7, COLOR.spruce);
      rect(ctx, -13, 4, 34, 9, COLOR.grass);
      rect(ctx, 1, 0, 14, 6, COLOR.spruceLight);
      rect(ctx, 6, 6, 2, 2, COLOR.stars);
      ctx.restore();
      return;
    }

    // Bushy tail, pointed ears and narrow muzzle distinguish the thief from the guardian.
    polygon(ctx, [[-8, 8], [-17, 1], [-27, 0], [-24, 5], [-30, 7],
      [-27, 12], [-17, 16], [-7, 13]], COLOR.outline);
    polygon(ctx, [[-9, 7], [-17, 3], [-23, 2], [-21, 7], [-27, 8],
      [-22, 12], [-15, 14], [-8, 11]], COLOR.foxDark);
    polygon(ctx, [[-16, 4], [-22, 2], [-21, 7], [-26, 8], [-21, 11],
      [-17, 12], [-19, 8]], COLOR.foxCream);

    polygon(ctx, [[-13, 7], [-8, 0], [7, -1], [18, 5], [19, 13],
      [13, 17], [-8, 16], [-13, 12]], COLOR.outline);
    polygon(ctx, [[-11, 6], [-7, 1], [7, 1], [15, 5], [16, 12],
      [10, 15], [-8, 14]], COLOR.fox);
    polygon(ctx, [[-8, 2], [5, 0], [14, 5], [8, 6], [-8, 5]], COLOR.foxLight);
    polygon(ctx, [[-7, 10], [2, 12], [14, 9], [11, 15], [-7, 14]], COLOR.foxCream);
    for (var fur = 0; fur < 4; fur++) {
      rect(ctx, -8 + fur * 5, 6 + (fur % 2), 3, 1, fur % 2 ? COLOR.foxDark : COLOR.foxLight);
    }
    rect(ctx, -4, 15 + step, 5, 5, COLOR.outline);
    rect(ctx, -3, 16 + step, 4, 3, COLOR.foxDark);
    rect(ctx, 9, 14 - step, 6, 6, COLOR.outline);
    rect(ctx, 10, 15 - step, 4, 4, COLOR.foxDark);

    // A larger front ear and a smaller rear ear make the fox silhouette unambiguous.
    polygon(ctx, [[7, 0], [9, -12], [17, -6], [19, 1]], COLOR.outline);
    polygon(ctx, [[10, -2], [11, -9], [16, -5], [17, 1]], COLOR.fox);
    polygon(ctx, [[12, -6], [14, -4], [13, -1]], '#e7a9a0');
    polygon(ctx, [[18, 0], [21, -11], [27, -5], [28, 4]], COLOR.outline);
    polygon(ctx, [[21, 0], [22, -7], [25, -4], [26, 3]], COLOR.foxLight);
    polygon(ctx, [[22, -4], [24, -2], [23, 1]], '#e7a9a0');

    polygon(ctx, [[12, 2], [15, -4], [25, -5], [30, 0], [30, 5],
      [36, 7], [36, 12], [26, 14], [15, 12]], COLOR.outline);
    polygon(ctx, [[15, 2], [18, -3], [25, -3], [28, 1], [28, 6],
      [34, 8], [33, 10], [25, 12], [17, 10]], COLOR.fox);
    polygon(ctx, [[18, -3], [25, -2], [29, 2], [22, 3], [16, 4]], COLOR.foxLight);
    polygon(ctx, [[18, 7], [26, 8], [34, 7], [33, 11], [25, 12], [20, 10]], COLOR.foxCream);
    rect(ctx, 33, 8, 3, 3, COLOR.outline);
    rect(ctx, 22, 2, 3, 3, COLOR.outline);
    rect(ctx, 23, 2, 1, 1, COLOR.stars);
    rect(ctx, 28, 11, 4, 1, COLOR.foxDark);

    if (options.wet) {
      rect(ctx, -1, 7, 3, 3, COLOR.ripple);
      rect(ctx, 12, 11, 2, 3, COLOR.water);
      rect(ctx, 23, 6, 2, 2, COLOR.ripple);
    }
    if (options.grab) {
      rect(ctx, 34, 10, 8, 2, COLOR.fox);
      rect(ctx, 40, 9, 3, 3, COLOR.foxDark);
    }
    if (options.carry) {
      // The same treasure is visibly carried away, not replaced by an unrelated sack.
      ctx.save();
      ctx.translate(32, -2);
      ctx.scale(0.82, 0.82);
      treasureChest(ctx, 0, 0);
      ctx.restore();
      rect(ctx, 30, 8, 5, 2, COLOR.foxDark);
    }
    ctx.restore();
  }

  function guardianChameleon(ctx, x, y, seconds, alert, scale) {
    // Original 2.5D pixel-art guardian: blue scales, amber eye and orange crest.
    // Photo-inspired palette, not the Chamilo logo or an embedded external image.
    var breath = Math.round(Math.sin(seconds * 2.2) * 0.8);
    ctx.save();
    ctx.translate(Math.round(x), Math.round(y + breath));
    ctx.scale(scale || 1, scale || 1);

    // Battlement contact shadow; the curled tail stays separate from the fox.
    ctx.save();
    ctx.globalAlpha = 0.35;
    rect(ctx, -25, 19, 69, 3, COLOR.outline);
    ctx.restore();
    ctx.beginPath();
    for (var i = 0; i <= 48; i++) {
      var portion = i / 48;
      var angle = -0.25 + portion * Math.PI * 3.8;
      var radius = 13.5 - portion * 11;
      var px = -14 + Math.cos(angle) * radius;
      var py = 6 + Math.sin(angle) * radius;
      if (!i) ctx.moveTo(px, py);
      else ctx.lineTo(px, py);
    }
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.lineWidth = 8;
    ctx.strokeStyle = COLOR.guardianDark;
    ctx.stroke();
    ctx.lineWidth = 5;
    ctx.strokeStyle = COLOR.guardian;
    ctx.stroke();
    ctx.lineWidth = 1.2;
    ctx.strokeStyle = COLOR.guardianLight;
    ctx.stroke();

    // Orange crown spikes are a signature feature of the supplied animal reference.
    for (var crest = 0; crest < 5; crest++) {
      var cx = -7 + crest * 6;
      var tip = -12 - (crest % 3) * 3;
      polygon(ctx, [[cx - 3, 4], [cx - 1, tip + 2],
        [cx + 3, tip - 3], [cx + 5, 5]], COLOR.guardianDark);
      polygon(ctx, [[cx - 1, 2], [cx, tip + 1],
        [cx + 2, tip], [cx + 3, 4]], COLOR.guardianCrest);
      rect(ctx, cx + 1, tip + 2, 1, 3, COLOR.guardianCrestLight);
    }

    // Torso: dark outline, upper turquoise planes and shaded underbelly.
    polygon(ctx, [[-12, 9], [-9, 0], [-2, -4], [12, -3], [21, 3],
      [20, 13], [13, 18], [-2, 18], [-10, 14]], COLOR.guardianDark);
    polygon(ctx, [[-9, 8], [-6, 1], [1, -2], [12, -1], [19, 4],
      [17, 12], [12, 15], [-1, 15], [-8, 12]], COLOR.guardian);
    polygon(ctx, [[-6, 4], [3, -1], [14, 2], [16, 6], [3, 9], [-7, 8]], COLOR.guardianLight);
    polygon(ctx, [[-6, 10], [6, 12], [17, 9], [12, 15], [-2, 16]], '#115188');
    for (var scale = 0; scale < 12; scale++) {
      var sx = -5 + (scale % 6) * 4;
      var sy = 6 + Math.floor(scale / 6) * 4;
      rect(ctx, sx + (scale % 2), sy, 2, 1,
        scale % 3 === 0 ? '#a2edf4' : '#378ab9');
    }

    // Clawed feet keep the defender securely planted on the battlements.
    rect(ctx, -1, 14, 7, 6, COLOR.guardianDark);
    rect(ctx, 0, 15, 5, 4, COLOR.guardian);
    rect(ctx, 1, 19, 7, 2, COLOR.guardianLight);
    rect(ctx, 11, 13, 9, 7, COLOR.guardianDark);
    rect(ctx, 13, 15, 6, 4, COLOR.guardian);
    rect(ctx, 16, 19, 8, 2, COLOR.guardianLight);

    // Three-dimensional casque and muzzle; highlights track the moonward upper face.
    polygon(ctx, [[11, 3], [13, -7], [20, -12], [30, -12],
      [34, -6], [32, 1], [40, 3], [44, 8], [39, 13],
      [25, 14], [15, 11]], COLOR.guardianDark);
    polygon(ctx, [[14, 2], [17, -7], [22, -10], [29, -9],
      [32, -5], [30, 3], [38, 5], [41, 8], [37, 11],
      [24, 12], [18, 10]], COLOR.guardian);
    polygon(ctx, [[18, -6], [23, -10], [29, -8], [33, -4],
      [30, -1], [22, -2]], COLOR.guardianLight);
    polygon(ctx, [[30, 1], [36, 3], [41, 7], [35, 7], [29, 4]], '#58d0ef');
    polygon(ctx, [[28, 9], [36, 9], [39, 11], [35, 12], [29, 12]], '#174679');
    rect(ctx, 38, 5, 2, 2, COLOR.guardianDark);
    rect(ctx, 34, 11, 4, 1, COLOR.guardianCrestLight);

    // Ringed amber eye, dark pupil and reflective glint; blink very occasionally.
    ctx.fillStyle = COLOR.guardianDark;
    ctx.beginPath();
    ctx.arc(23, -3, 7, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = COLOR.guardianCrest;
    ctx.beginPath();
    ctx.arc(23, -3, 5.3, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = '#ffe7ac';
    ctx.beginPath();
    ctx.arc(23, -3, 3.7, 0, Math.PI * 2);
    ctx.fill();
    if (Math.sin(seconds * 1.6) > 0.995) {
      rect(ctx, 17, -4, 12, 2, COLOR.guardianDark);
    } else {
      ctx.fillStyle = COLOR.guardianDark;
      ctx.beginPath();
      ctx.arc(alert ? 25 : 24, -3, 2.6, 0, Math.PI * 2);
      ctx.fill();
      rect(ctx, 24, -5, 1, 2, COLOR.stars);
    }
    for (var spot = 0; spot < 6; spot++) {
      var hx = 17 + (spot % 3) * 6;
      var hy = 5 + Math.floor(spot / 3) * 4;
      rect(ctx, hx, hy, 3, 1, spot % 2 ? COLOR.guardianLight : '#8cdcf1');
    }

    if (alert) {
      // A subtle warning shimmer accompanies the castle's final defense.
      for (var shine = 0; shine < 4; shine++) {
        var phase = seconds * 3.5 + shine * 2.4;
        rect(ctx, -22 + shine * 19, -20 - Math.sin(phase) * 3,
          2, 2, shine % 2 ? COLOR.guardianCrestLight : COLOR.ripple);
      }
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

  function guardianWarning(ctx, progress) {
    // A brief turquoise signal from the rooftop guardian scares the fox away.
    if (progress <= 0) return;
    var x = lerp(151, 192, progress);
    var y = lerp(30, 79, progress);
    ctx.save();
    ctx.globalAlpha = 0.85 * (1 - 0.3 * progress);
    ctx.strokeStyle = COLOR.guardianLight;
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.moveTo(151, 30);
    ctx.quadraticCurveTo(178, 36, x, y);
    ctx.stroke();
    for (var i = 0; i < 4; i++) {
      var px = x + Math.cos(i * Math.PI / 2) * 5 * progress;
      var py = y + Math.sin(i * Math.PI / 2) * 5 * progress;
      rect(ctx, px, py, 2, 2, i % 2 ? COLOR.guardianCrestLight : COLOR.ripple);
    }
    ctx.restore();
  }

  function raid(ctx, level, ms) {
    var t = ms / 1000;
    var x = 216;
    var y = 88;
    var options = { facing: 'left', walk: true, carry: false, grab: false, wet: false, hidden: false };

    if (level <= 1) {
      // An unguarded chest or a simple rope fence cannot stop the first theft.
      if (t < 1.35) x = lerp(216, 163, t / 1.35);
      else if (t < 1.75) {
        x = 163;
        options.walk = false;
        options.grab = t < 1.55;
      } else {
        x = lerp(163, 195, (t - 1.75) / 1.85);
        options.facing = 'right';
        options.carry = true;
      }
      if (t >= 3.6) {
        x = 195;
        options.walk = false;
        options.carry = true;
      }
      fox(ctx, x, y, t, options);
      return;
    }

    if (level === 2) {
      // The palisade costs time, but its ladder still lets the thief reach the chest.
      if (t < 0.95) x = lerp(216, 195, t / 0.95);
      else if (t < 2.05) {
        x = 195;
        y = lerp(88, 54, (t - 0.95) / 1.1);
      } else if (t < 3.0) {
        x = lerp(195, 163, (t - 2.05) / 0.95);
        y = lerp(54, 88, (t - 2.05) / 0.95);
      } else if (t < 3.35) {
        x = 163;
        options.walk = false;
        options.grab = t < 3.15;
      } else if (t < 4.2) {
        x = lerp(163, 197, (t - 3.35) / 0.85);
        y = lerp(88, 52, (t - 3.35) / 0.85);
        options.facing = 'right';
        options.carry = true;
      } else {
        x = lerp(197, 195, (t - 4.2) / 0.75);
        y = lerp(52, 88, (t - 4.2) / 0.75);
        options.facing = 'right';
        options.carry = true;
      }
      if (t >= 0.8 && t < 4.85) {
        rect(ctx, 194, 60, 2, 47, COLOR.woodDark);
        rect(ctx, 204, 60, 2, 47, COLOR.woodDark);
        for (var rung = 0; rung < 6; rung++) rect(ctx, 194, 64 + rung * 7, 12, 2, COLOR.wood);
      }
      if (t >= 4.95) {
        x = 195;
        y = 88;
        options.walk = false;
      }
      fox(ctx, x, y, t, options);
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
      fox(ctx, x, y, t, options);
      return;
    }

    if (level === 4) {
      if (t < 1.1) x = lerp(216, 188, t / 1.1);
      else if (t < 2.6) {
        x = 188;
        options.walk = false;
        if (t > 1.3) arrow(ctx, range(t, 1.3, 2.0));
      } else {
        x = lerp(188, 224, (t - 2.6) / 1.5);
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
        rect(ctx, 128, 54, 7, 9, COLOR.wallLight);
        rect(ctx, 130, 51, 4, 4, COLOR.gold);
        rect(ctx, 129, 58, 6, 2, COLOR.woodDark);
      }
      fox(ctx, x, y, t, options);
      return;
    }

    if (t < 1.1) x = lerp(216, 191, t / 1.1);
    else if (t < 2.6) {
      x = 191;
      options.walk = false;
      if (t > 1.3) guardianWarning(ctx, range(t, 1.3, 2.2));
    } else {
      x = lerp(191, 226, (t - 2.6) / 1.2);
      options.facing = 'right';
      options.hidden = t > 3.55;
    }
    fox(ctx, x, y, t, options);
  }

  function chestTaken(level, attackMs, stolen) {
    return stolen || (level <= 1 && attackMs >= 1550) || (level === 2 && attackMs >= 3150);
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
    ctx.imageSmoothingEnabled = false;
    var policy = policyOf(root);
    var stages = stageNames(root);
    var suffix = root.getAttribute('data-visual-suffix') || 'Visual guide only';
    var stolenLabel = root.getAttribute('data-stolen-label') || 'Treasure stolen';
    var blockedLabel = root.getAttribute('data-blocked-label') || 'Attack blocked';
    var media = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var reduced = media ? media.matches : false;
    var container = root.closest('details');
    var visible = true;
    var ambientTimer = 0;
    var raf = 0;
    var timer = 0;
    var previousTimestamp = 0;
    var lastInputValue = field.value;
    var state = {
      level: 0, target: 0, built: 0, pendingAttack: false, attackMs: -1,
      seconds: 0, pulseMs: 0, chestStolen: false, attackResult: '',
    };

    function canAnimate() {
      return !reduced && visible && document.visibilityState !== 'hidden' && (!container || container.open);
    }

    function stopFrame() {
      if (raf) window.cancelAnimationFrame(raf);
      raf = 0;
      previousTimestamp = 0;
    }

    function updateStatus() {
      var result = state.attackResult === 'stolen' ? stolenLabel :
        state.attackResult === 'blocked' ? blockedLabel : '';
      status.textContent = stages[state.level] + ' — ' + (result ? result + ' · ' : '') + suffix;
      root.dataset.castleResult = state.attackResult || 'idle';
    }

    function draw() {
      ctx.clearRect(0, 0, WIDTH, HEIGHT);
      background(ctx, state.seconds);
      building(ctx, state.built,
        canAnimate() && Math.abs(state.built - state.target) > 0.001);
      castleDetails(ctx, state.built);
      if (state.level <= 2 && !chestTaken(state.level, state.attackMs, state.chestStolen)) {
        treasureChest(ctx, 123, 93);
      }
      drawRevealBurst(ctx, state.level, state.pulseMs);
      if (state.level === 5 && state.built >= TARGET_PIECES[5] - 2) {
        // The final guardian replaces the dragon, perched on the keep's battlements.
        guardianChameleon(ctx, 120, 25, state.seconds,
          state.attackMs >= 0 && state.attackMs > 950, 0.61);
      }
      if (state.attackMs >= 0) raid(ctx, state.level, state.attackMs);
      else if (state.level > 0) fox(ctx, state.chestStolen ? 195 : 212, 88, state.seconds, {
        facing: state.chestStolen ? 'right' : 'left',
        walk: false, hidden: false, grab: false, carry: state.chestStolen,
      });
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
        if (state.attackMs >= ATTACK_DURATION_MS) {
          state.chestStolen = state.level <= 2;
          state.attackResult = state.chestStolen ? 'stolen' : 'blocked';
          state.attackMs = -1;
          updateStatus();
        }
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
      if (!canAnimate() || !field.value || timer || state.pendingAttack || state.attackMs >= 0 || state.attackResult) return;
      timer = window.setTimeout(function () {
        timer = 0;
        if (!canAnimate()) return;
        state.pendingAttack = true;
        startFrame();
      }, ATTACK_DELAY_MS);
    }

    function update() {
      if (field.value === lastInputValue) return;
      lastInputValue = field.value;
      cancelAttack();
      stopAmbient();
      var previousLevel = state.level;
      state.chestStolen = false;
      state.attackResult = '';
      state.level = levelOf(field.value, policy);
      state.target = TARGET_PIECES[state.level];
      if (state.level > previousLevel && canAnimate()) state.pulseMs = 820;
      else if (state.level <= previousLevel) state.pulseMs = 0;
      root.dataset.castleLevel = String(state.level);
      updateStatus();
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
      scheduleAttack();
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
    root.dataset.castleLevel = String(state.level);
    updateStatus();
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
