/*
 * Decorative, client-side password castle. The score is illustrative only;
 * it does not validate passwords, replace the configured policy or transmit input.
 * Based on the existing password-castle concept in Chamilo (KEEP-inspired).
 */
(function () {
  'use strict';

  var ROOT_SELECTOR = '[data-password-castle]';
  var WIDTH = 900;
  var HEIGHT = 420;
  var BUILD_SPEED = 46;
  var ATTACK_DELAY_MS = 1250;
  var ATTACK_DURATION_MS = 5600;
  var TARGET_PIECES = [0, 20, 43, 69, 91, 105];
  var DEFAULT_STAGES = ['Unprotected', 'Fence', 'Palisade', 'Stone walls', 'Castle', 'Fortified castle'];
  var PALETTE = {
    outline: '#14263b', stone: '#aeb9c5', stoneLight: '#e3e2dd', stoneDark: '#687f97',
    wood: '#956044', woodLight: '#ca9262', woodDark: '#503c3a', gold: '#fbd071',
    turquoise: '#14aab7', ivory: '#ffebc8', grass: '#4f8b69',
  };

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function lerp(from, to, progress) {
    return from + (to - from) * clamp(progress, 0, 1);
  }

  function range(value, start, end) {
    return clamp((value - start) / (end - start), 0, 1);
  }

  function ease(value) {
    var p = clamp(value, 0, 1);
    return p * p * (3 - 2 * p);
  }

  function fill(ctx, color) {
    ctx.fillStyle = color;
    ctx.fill();
  }

  function line(ctx, color, width) {
    ctx.strokeStyle = color;
    ctx.lineWidth = width;
    ctx.stroke();
  }

  function shape(ctx, points, color) {
    ctx.beginPath();
    points.forEach(function (point, index) {
      if (index) ctx.lineTo(point[0], point[1]);
      else ctx.moveTo(point[0], point[1]);
    });
    ctx.closePath();
    fill(ctx, color);
  }

  function roundedRect(ctx, x, y, width, height, radius, color) {
    var r = Math.min(radius, height / 2, width / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + width, y, x + width, y + height, r);
    ctx.arcTo(x + width, y + height, x, y + height, r);
    ctx.arcTo(x, y + height, x, y, r);
    ctx.arcTo(x, y, x + width, y, r);
    ctx.closePath();
    fill(ctx, color);
  }

  function gradient(ctx, x1, y1, x2, y2, stops) {
    var result = ctx.createLinearGradient(x1, y1, x2, y2);
    stops.forEach(function (stop) { result.addColorStop(stop[0], stop[1]); });
    return result;
  }

  function oval(ctx, x, y, rx, ry, color) {
    ctx.beginPath();
    ctx.ellipse(x, y, rx, ry, 0, 0, Math.PI * 2);
    fill(ctx, color);
  }

  function background(ctx, seconds) {
    ctx.fillStyle = gradient(ctx, 0, 0, 0, 420, [
      [0, '#0a1730'], [0.45, '#1e3655'], [0.78, '#315e72'], [1, '#497d70'],
    ]);
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    var haze = ctx.createRadialGradient(730, 125, 8, 730, 125, 270);
    haze.addColorStop(0, 'rgba(253, 227, 170, 0.19)');
    haze.addColorStop(1, 'rgba(253, 227, 170, 0)');
    ctx.fillStyle = haze;
    ctx.fillRect(450, 0, 450, 375);

    for (var i = 0; i < 33; i++) {
      var sx = (i * 137 + 37) % 900;
      var sy = (i * 47 + 31) % 161;
      var shine = 0.55 + 0.3 * Math.sin(seconds * 1.3 + i);
      ctx.globalAlpha = shine;
      oval(ctx, sx, sy, i % 5 === 0 ? 1.9 : 1.1, i % 5 === 0 ? 1.9 : 1.1, '#f1f4e9');
    }
    ctx.globalAlpha = 1;

    // Circular moon: never distort it by scaling one axis.
    var moon = ctx.createRadialGradient(789, 70, 2, 789, 70, 80);
    moon.addColorStop(0, 'rgba(255,239,194,0.48)');
    moon.addColorStop(1, 'rgba(255,239,194,0)');
    ctx.fillStyle = moon;
    ctx.fillRect(709, -10, 160, 160);
    oval(ctx, 789, 70, 30, 30, '#fff1c8');
    oval(ctx, 800, 76, 6, 5, 'rgba(240,194,129,.37)');
    oval(ctx, 780, 57, 3, 4, 'rgba(240,194,129,.21)');

    shape(ctx, [[0,258],[100,173],[148,226],[226,130],[324,232],[408,173],[493,244],
      [595,151],[674,234],[763,158],[900,246],[900,420],[0,420]], '#263e59');
    shape(ctx, [[198,159],[226,130],[254,161],[234,152],[226,161],[220,149]], '#869ca9');
    shape(ctx, [[562,180],[595,151],[622,188],[601,175],[591,186]], '#8299a9');
    shape(ctx, [[730,188],[763,158],[789,187],[765,181],[758,192]], '#8294a7');
    shape(ctx, [[0,294],[91,226],[184,275],[281,201],[385,285],[477,224],
      [586,293],[691,222],[787,276],[900,211],[900,420],[0,420]], '#24475a');
    shape(ctx, [[0,340],[90,297],[175,330],[255,281],[350,335],[462,281],
      [567,330],[674,278],[782,328],[900,279],[900,420],[0,420]], '#285b60');

    for (var tree = 0; tree < 20; tree++) {
      var tx = tree < 10 ? tree * 35 - 16 : 628 + (tree - 10) * 32;
      var height = 28 + (tree * 19) % 52;
      var base = 341 + (tree % 3) * 7;
      shape(ctx, [[tx,base-height],[tx-16,base-9],[tx-8,base-11],
        [tx-23,base],[tx+24,base],[tx+9,base-12],[tx+16,base-8]],
      tree % 2 ? '#183e47' : '#17434b');
      ctx.fillStyle = '#335a50';
      ctx.fillRect(tx - 2, base - 4, 4, 12);
    }

    // Dry grassy approach. The fox and the fortress share one continuous ground plane.
    ctx.fillStyle = gradient(ctx, 0, 333, 0, HEIGHT, [[0, '#4d9272'], [0.15, '#3c7c62'], [1, '#183e3c']]);
    ctx.fillRect(0, 338, WIDTH, HEIGHT - 338);
    ctx.fillStyle = '#82ad82';
    ctx.fillRect(0, 337, WIDTH, 3);
    for (var grass = 0; grass < 85; grass++) {
      var gx = (grass * 83 + 17) % WIDTH;
      var gy = 352 + (grass * 19) % 68;
      ctx.strokeStyle = grass % 3 ? 'rgba(116,167,118,.25)' : 'rgba(174,194,132,.35)';
      ctx.lineWidth = 1.6;
      ctx.beginPath();
      ctx.moveTo(gx, gy);
      ctx.lineTo(gx - 3, gy - 5);
      ctx.moveTo(gx, gy);
      ctx.lineTo(gx + 3, gy - 7);
      ctx.stroke();
    }
    oval(ctx, 358, 348, 265, 19, 'rgba(7,24,33,.32)');
    oval(ctx, 728, 351, 95, 12, 'rgba(7,24,33,.2)');
    for (var rock = 0; rock < 12; rock++) {
      var rx = (rock * 193 + 63) % WIDTH;
      var ry = 370 + (rock * 17) % 43;
      oval(ctx, rx, ry, 7 + rock % 3 * 3, 2.8, '#537b71');
      oval(ctx, rx - 1, ry - 2, 5 + rock % 3 * 3, 2, '#7e9f8c');
    }
  }

  function treasureChest(ctx, x, y, scale) {
    ctx.save();
    ctx.translate(x, y);
    ctx.scale(scale || 1, scale || 1);
    oval(ctx, 0, 2, 43, 9, 'rgba(9,24,34,.35)');
    roundedRect(ctx, -36, -32, 72, 34, 7, '#382f35');
    roundedRect(ctx, -33, -30, 66, 29, 5,
      gradient(ctx, -34, -30, 36, 1, [[0,'#b36b3e'],[.55,'#d39050'],[1,'#7b432e']]));
    roundedRect(ctx, -38, -47, 76, 25, 10, '#382d37');
    roundedRect(ctx, -35, -44, 70, 18, 9,
      gradient(ctx, 0, -46, 0, -25, [[0,'#edaa5b'],[1,'#8d5238']]));
    ctx.strokeStyle = '#ffd981';
    ctx.lineWidth = 4;
    ctx.beginPath();
    ctx.moveTo(-29,-30);ctx.lineTo(-29,-2);
    ctx.moveTo(29,-30);ctx.lineTo(29,-2);
    ctx.moveTo(-30,-27);ctx.lineTo(30,-27);
    ctx.moveTo(-32,-5);ctx.lineTo(32,-5);
    ctx.stroke();
    roundedRect(ctx, -11, -35, 22, 24, 3, '#f8cf69');
    roundedRect(ctx, -6, -29, 12, 12, 2, '#b67338');
    oval(ctx, 0, -25, 3, 3, '#fff2b7');
    ctx.restore();
  }

  function masonry(ctx, x, y, width, height, cellWidth, cellHeight) {
    ctx.save();
    ctx.beginPath();
    ctx.rect(x, y, width, height);
    ctx.clip();
    ctx.lineWidth = 1.4;
    for (var row = 0; row < Math.ceil(height / cellHeight); row++) {
      var yy = y + row * cellHeight;
      var offset = row % 2 ? cellWidth / 2 : 0;
      for (var col = -1; col <= Math.ceil(width / cellWidth); col++) {
        var xx = x + col * cellWidth + offset;
        ctx.strokeStyle = row % 2 ? 'rgba(49,65,83,.22)' : 'rgba(255,255,255,.24)';
        ctx.beginPath();
        ctx.moveTo(xx, yy);ctx.lineTo(xx + cellWidth - 2, yy);
        ctx.moveTo(xx, yy);ctx.lineTo(xx, yy + cellHeight - 2);
        ctx.stroke();
        if ((row + col * 3) % 8 === 0) {
          roundedRect(ctx, xx + 6, yy + 6, 9, 3, 2, 'rgba(54,83,108,.10)');
        }
      }
    }
    ctx.restore();
  }

  function stoneVolume(ctx, x, y, width, height, side) {
    roundedRect(ctx, x - 2, y - 2, width + 4, height + 4, 3, '#23374d');
    ctx.fillStyle = gradient(ctx, x, y, x + width, y, [
      [0, '#dde1df'], [.38, '#aebac5'], [1, '#7b8fa6'],
    ]);
    ctx.fillRect(x, y, width, height);
    masonry(ctx, x, y, width, height, 37, 22);
    shape(ctx, [[x + width, y],[x + width + side,y-10],
      [x + width + side,y + height - 12],[x + width,y + height]], '#61748a');
    ctx.strokeStyle = 'rgba(232,235,226,.62)';
    ctx.lineWidth = 2;
    ctx.beginPath();ctx.moveTo(x + 3,y + 3);ctx.lineTo(x + width - 3,y + 3);ctx.stroke();
  }

  function crenels(ctx, x, y, width, height, size) {
    stoneVolume(ctx, x - 5, y + height - 9, width + 10, 12, 5);
    var number = Math.floor(width / size);
    for (var i = 0; i < number; i++) {
      if (i % 2 === 0) {
        stoneVolume(ctx, x + i * size, y, size + 1, height, 2);
      }
    }
  }

  function arrowSlit(ctx, x, y, scale) {
    var s = scale || 1;
    roundedRect(ctx, x - 6*s, y - 18*s, 12*s, 37*s, 4*s, '#dbe2df');
    roundedRect(ctx, x - 2.3*s, y - 16*s, 4.6*s, 32*s, 2*s, '#162638');
    roundedRect(ctx, x - 8*s, y - 2*s, 16*s, 4*s, 1.5*s, '#182c40');
  }

  function gate(ctx, x, y, width, height, bars) {
    ctx.beginPath();
    ctx.moveTo(x, y + height);
    ctx.lineTo(x, y + 17);
    ctx.quadraticCurveTo(x + width/2, y - 28, x + width, y + 17);
    ctx.lineTo(x + width, y + height);
    ctx.closePath();
    fill(ctx, '#28364a');
    line(ctx, '#e7e3da', 7);
    ctx.save();
    ctx.clip();
    ctx.fillStyle = gradient(ctx, x, y, x + width, y, [[0,'#182b3d'],[1,'#344354']]);
    ctx.fillRect(x + 2, y, width - 4, height);
    for (var board = 0; board < 5; board++) {
      ctx.strokeStyle = board % 2 ? '#744d3d' : '#86563f';
      ctx.lineWidth = 7;
      ctx.beginPath();
      ctx.moveTo(x + board * width/5 + 4,y+20);
      ctx.lineTo(x + board * width/5 + 4,y + height);
      ctx.stroke();
    }
    if (bars) {
      ctx.strokeStyle = '#adbfc7';
      ctx.lineWidth = 5;
      for (var j = 0; j < 7; j++) {
        ctx.beginPath();ctx.moveTo(x + 8 + j * (width - 16)/6,y);ctx.lineTo(x + 8 + j*(width - 16)/6,y + height);ctx.stroke();
      }
      ctx.lineWidth = 5;
      for (var k = 0; k < 3; k++) {
        ctx.beginPath();ctx.moveTo(x,y + 31 + k*22);ctx.lineTo(x + width,y + 31 + k*22);ctx.stroke();
      }
    }
    ctx.restore();
  }

  function fence(ctx, progress) {
    var count = Math.floor(ease(progress) * 14);
    for (var i = 0; i < count; i++) {
      var x = 180 + i*27;
      ctx.fillStyle = gradient(ctx,x,0,x+14,0,[[0,'#5b433d'],[.5,'#b98056'],[1,'#493b3b']]);
      ctx.fillRect(x,290,13,56);
      shape(ctx,[[x,290],[x+6,280],[x+13,290]],'#c58a5b');
    }
    if (progress > .16) {
      ctx.save();ctx.globalAlpha = ease(range(progress,.16,.5));
      for (var rail = 0; rail < 2; rail++) {
        roundedRect(ctx,174,301 + rail*23,395,9,3,rail ? '#684b3d':'#c18a5e');
        roundedRect(ctx,174,302 + rail*23,395,2,1,'#e7a978');
      }
      ctx.restore();
    }
    // The interrupted center of the fence leaves the chest in plain view.
  }

  function palisade(ctx, progress) {
    var count = Math.floor(ease(progress) * 25);
    for (var i=0;i<count;i++) {
      var x=165+i*16;
      if (x>309 && x<424) continue;
      var y=238 + (i%3)*3;
      shape(ctx,[[x,y+105],[x,y+12],[x+7,y],[x+15,y+12],[x+15,y+105]],
        gradient(ctx,x,y,x+15,y,[[0,'#5a3f37'],[.45,'#cd8853'],[1,'#6a4a3d']]));
      ctx.strokeStyle='rgba(245,186,110,.55)'; ctx.lineWidth=1.6;
      ctx.beginPath();ctx.moveTo(x+5,y+16);ctx.lineTo(x+5,y+97);ctx.stroke();
    }
    if (progress>.45) {
      ctx.save();ctx.globalAlpha=ease(range(progress,.45,.9));
      for (var yy=0;yy<2;yy++) roundedRect(ctx,162,289+yy*33,405,12,3,yy?'#62443b':'#493c3a');
      roundedRect(ctx,318,279,104,64,5,'#3f3035');
      roundedRect(ctx,329,286,82,57,4,'#976243');
      roundedRect(ctx,352,287,36,55,4,'#4b3536');
      ctx.strokeStyle='#e7aa64';ctx.lineWidth=3;
      ctx.beginPath();ctx.moveTo(329,290);ctx.lineTo(329,341);ctx.moveTo(410,290);ctx.lineTo(410,341);ctx.stroke();
      ctx.restore();
    }
  }

  function stoneWalls(ctx) {
    stoneVolume(ctx,184,239,355,104,18);
    shape(ctx,[[185,237],[202,222],[557,222],[539,238]],'#e0e2dc');
    crenels(ctx,185,208,354,30,31);
    arrowSlit(ctx,232,291,.8);
    arrowSlit(ctx,501,291,.8);
    gate(ctx,329,274,82,70,false);
    stoneVolume(ctx,318,327,11,17,4);
    stoneVolume(ctx,411,327,11,17,4);
  }

  function tower(ctx, x, y, width, height) {
    oval(ctx,x+width/2,344,width*.69,10,'rgba(13,27,38,.3)');
    shape(ctx,[[x-12,344],[x,325],[x+width,325],[x+width+13,344]],'#71869a');
    stoneVolume(ctx,x,y,width,height,11);
    stoneVolume(ctx,x-7,y+height-17,width+14,19,6);
    crenels(ctx,x-8,y-24,width+16,27,18);
    arrowSlit(ctx,x+width/2,y+67,.9);
    arrowSlit(ctx,x+width/2,y+126,.73);
  }

  function castle(ctx, fortified) {
    stoneVolume(ctx,192,221,340,122,21);
    crenels(ctx,191,196,340,29,30);
    masonry(ctx,197,226,333,114,38,23);
    stoneVolume(ctx,284,143,159,199,17);
    crenels(ctx,281,109,166,37,24);
    arrowSlit(ctx,324,198,.85);
    arrowSlit(ctx,407,198,.85);
    arrowSlit(ctx,367,161,.72);
    tower(ctx,161,181,83,161);
    tower(ctx,488,181,82,161);
    stoneVolume(ctx,305,264,118,79,10);
    gate(ctx,318,270,93,74,fortified);
    roundedRect(ctx,291,260,148,12,2,'#d9dfdc');
    roundedRect(ctx,291,268,148,5,2,'#667f96');
    if (fortified) {
      // Compact stone buttresses and a reinforced gate, not decorative roofs.
      shape(ctx,[[157,344],[180,290],[206,290],[191,344]],'#60758d');
      shape(ctx,[[567,344],[545,290],[521,290],[537,344]],'#60758d');
      shape(ctx,[[160,343],[177,307],[185,310],[173,343]],'#acb7c0');
      shape(ctx,[[564,343],[549,307],[541,310],[552,343]],'#acb7c0');
      roundedRect(ctx,275,327,176,16,3,'#657d92');
      roundedRect(ctx,280,327,165,5,2,'#c6d2d4');
      for (var rivet=0;rivet<6;rivet++) oval(ctx,287+rivet*30,335,2,2,'#f5d088');
    }
  }

  function drawStage(ctx, built) {
    if (built<=0) return;
    if (built<=20) {
      fence(ctx,built/20);
      return;
    }
    if (built<=43) {
      var timber=range(built,20,43);
      ctx.save();ctx.globalAlpha=1-ease(timber);fence(ctx,1);ctx.restore();
      palisade(ctx,timber);
      return;
    }
    if (built<=69) {
      var wall=range(built,43,69);
      ctx.save();ctx.globalAlpha=1-ease(wall);palisade(ctx,1);ctx.restore();
      ctx.save();ctx.globalAlpha=ease(wall);
      ctx.translate(0,22*(1-ease(wall)));
      stoneWalls(ctx);
      ctx.restore();
      return;
    }
    if (built<=91) {
      var keep=range(built,69,91);
      ctx.save();ctx.globalAlpha=1-ease(keep);stoneWalls(ctx);ctx.restore();
      ctx.save();ctx.globalAlpha=ease(keep);
      ctx.translate(0,24*(1-ease(keep)));
      castle(ctx,false);
      ctx.restore();
      return;
    }
    castle(ctx,false);
    var armor=ease(range(built,91,105));
    if (armor>0) {
      ctx.save();ctx.globalAlpha=armor;
      // Reinforcements are confined to the front and foundation of the fortress.
      shape(ctx,[[155,344],[181,293],[202,296],[186,344]],'#647992');
      shape(ctx,[[573,344],[544,293],[523,296],[539,344]],'#647992');
      shape(ctx,[[157,344],[177,313],[183,313],[173,344]],'#b8c5ca');
      shape(ctx,[[568,344],[549,313],[543,313],[554,344]],'#b8c5ca');
      for (var bar=0;bar<9;bar++) roundedRect(ctx,324+bar*10,281,4,62,1,'#bbcbd0');
      roundedRect(ctx,321,304,100,5,1,'#5a7085');
      roundedRect(ctx,321,326,100,6,1,'#5a7085');
      ctx.restore();
    }
  }

  function fox(ctx, x, ground, time, options) {
    var walk = options.walk ? Math.sin(time*15) : 0;
    var frightened = options.scared ? Math.sin(time*18)*3 : 0;
    var facing = options.facing === 'right' ? -1 : 1;
    ctx.save();
    ctx.translate(x,ground + (options.walk ? Math.abs(walk)*-3 : 0));
    ctx.scale(facing,1);
    oval(ctx,0,6,72,9,'rgba(8,29,35,.29)');

    // Tail follows the whole animal on retreat; there is no placeholder sprite.
    ctx.beginPath();
    ctx.moveTo(24,-42);
    ctx.bezierCurveTo(50,-87,95,-95,113,-75);
    ctx.bezierCurveTo(109,-42,78,-27,44,-31);
    ctx.closePath();
    fill(ctx,gradient(ctx,32,-80,108,-33,[[0,'#f4a354'],[.7,'#d9692f'],[1,'#a84e33']]));
    ctx.beginPath();ctx.moveTo(82,-83);ctx.quadraticCurveTo(107,-92,113,-75);
    ctx.quadraticCurveTo(109,-58,92,-50);ctx.quadraticCurveTo(97,-72,82,-83);
    fill(ctx,'#fff2d7');
    // Back feet are drawn behind the torso; their stride alternates.
    roundedRect(ctx,24+walk*11,-25,15,28,7,'#7e3b2e');
    roundedRect(ctx,-15-walk*11,-26,16,31,7,'#87402c');
    roundedRect(ctx,20+walk*11,-1,27,9,5,'#432f33');
    roundedRect(ctx,-27-walk*11,-1,25,9,5,'#432f33');

    ctx.beginPath();ctx.ellipse(7,-47,59,32,-.13,0,Math.PI*2);
    fill(ctx,gradient(ctx,0,-78,0,-18,[[0,'#ffbe67'],[.55,'#e97c3e'],[1,'#b45134']]));
    ctx.beginPath();ctx.ellipse(-16,-28,37,13,-.12,0,Math.PI*2);fill(ctx,'#fff0d7');
    // Ears are narrow triangles, not square blocks.
    shape(ctx,[[-50,-62],[-58,-111],[-31,-84],[-27,-60]],'#6e3a32');
    shape(ctx,[[-50,-77],[-55,-101],[-35,-81]],'#e77d4a');
    shape(ctx,[[-27,-64],[-20,-105],[-3,-73]],'#7a3d35');
    shape(ctx,[[-23,-80],[-19,-97],[-8,-77]],'#f3a75f');

    ctx.beginPath();ctx.moveTo(-48,-76);ctx.bezierCurveTo(-70,-70,-78,-57,-77,-46);
    ctx.bezierCurveTo(-81,-40,-97,-40,-101,-35);
    ctx.bezierCurveTo(-83,-23,-62,-23,-41,-32);
    ctx.quadraticCurveTo(-12,-45,-30,-67);ctx.closePath();
    fill(ctx,gradient(ctx,-55,-70,-49,-25,[[0,'#ffba62'],[.75,'#e67b3e'],[1,'#9b4a37']]));
    ctx.beginPath();ctx.moveTo(-79,-46);ctx.quadraticCurveTo(-92,-43,-101,-35);
    ctx.quadraticCurveTo(-77,-19,-48,-30);ctx.quadraticCurveTo(-58,-39,-79,-46);
    fill(ctx,'#fff1dd');
    oval(ctx,-99,-36,7,5,'#31303b');
    oval(ctx,-64,-59,5.5,7,'#252f3a');
    oval(ctx,-65,-61,2,2,'#ffffff');
    ctx.strokeStyle='#5e3735';ctx.lineWidth=2;
    ctx.beginPath();ctx.moveTo(-75,-47);ctx.lineTo(-54,-50);ctx.stroke();
    if (options.scared) {
      ctx.strokeStyle='#fff0ca';ctx.lineWidth=2.5;
      ctx.beginPath();ctx.moveTo(-69,-72+frightened);ctx.lineTo(-65,-81+frightened);ctx.stroke();
    }
    roundedRect(ctx,-40+walk*14,-17,15,21,7,'#b95534');
    roundedRect(ctx,-52+walk*14,-1,28,9,5,'#3c3036');

    if (options.carry) {
      // The actual chest remains attached to the fox's mouth on every frame.
      treasureChest(ctx,-126,-20,.55);
    }
    ctx.restore();
  }

  function chameleon(ctx, x, y, seconds, alerted) {
    ctx.save();ctx.translate(x,y + Math.sin(seconds*2)*1.5);
    oval(ctx,0,15,75,8,'rgba(11,33,42,.26)');
    // Curled tail: outline first, then a smooth turquoise line.
    ctx.beginPath();
    ctx.moveTo(-37,-6);ctx.bezierCurveTo(-85,-39,-119,5,-105,27);
    ctx.bezierCurveTo(-91,52,-57,36,-69,16);
    ctx.bezierCurveTo(-84,-1,-94,20,-87,24);
    line(ctx,'#17425e',19);
    ctx.beginPath();ctx.moveTo(-37,-6);ctx.bezierCurveTo(-85,-39,-119,5,-105,27);
    ctx.bezierCurveTo(-91,52,-57,36,-69,16);ctx.bezierCurveTo(-84,-1,-94,20,-87,24);
    line(ctx,'#1ab5b8',13);
    // Defensive dorsal ridge.
    for (var crest=0;crest<7;crest++) {
      var cx=-46 + crest*15;
      shape(ctx,[[cx,-21],[cx+7,-42-(crest%2)*4],[cx+14,-18]],'#f5aa43');
    }
    ctx.beginPath();ctx.ellipse(0,-5,62,30,-.12,0,2*Math.PI);
    fill(ctx,gradient(ctx,0,-38,0,33,[[0,'#48e2d4'],[.65,'#12a7ba'],[1,'#08748f']]));
    ctx.beginPath();ctx.ellipse(21,8,36,11,-.08,0,2*Math.PI);fill(ctx,'#a1e9ae');
    roundedRect(ctx,-35,12,14,22,6,'#0b7293');
    roundedRect(ctx,24,12,14,22,6,'#0c7a96');
    for (var toe=0;toe<3;toe++) {
      roundedRect(ctx,-41+toe*7,29,11,4,2,'#51cebd');
      roundedRect(ctx,19+toe*7,29,11,4,2,'#51cebd');
    }
    ctx.beginPath();ctx.moveTo(32,-29);ctx.bezierCurveTo(65,-40,92,-15,91,8);
    ctx.bezierCurveTo(81,30,47,24,40,12);ctx.closePath();
    fill(ctx,gradient(ctx,45,-28,67,21,[[0,'#5bead7'],[1,'#098fae']]));
    ctx.beginPath();ctx.moveTo(62,10);ctx.quadraticCurveTo(82,24,98,10);
    ctx.quadraticCurveTo(82,32,61,21);fill(ctx,'#c4e8a8');
    oval(ctx,62,-23,18,18,'#f5d36e');
    oval(ctx,65,-23,12,12,'#fff2be');
    oval(ctx,68 + (alerted ? 3 : 0),-24,6,8,'#243f4a');
    oval(ctx,70 + (alerted ? 3 : 0),-27,2,3,'#ffffff');
    for (var spot=0;spot<6;spot++) {
      oval(ctx,-39+spot*16,-12+(spot%2)*8,4,3,spot%2?'#a1efc1':'#2cc9c5');
    }
    ctx.restore();
  }

  function arrow(ctx, progress) {
    if (progress<=0 || progress>=1) return;
    // A real wall opening is the launch point; impact ends near the fox's paws.
    var p=ease(progress);
    var x=536+(641-536)*p;
    var y=249+78*p-26*Math.sin(Math.PI*p);
    ctx.save();ctx.translate(x,y);ctx.rotate(Math.atan2(78-26*Math.PI*Math.cos(Math.PI*p),105));
    roundedRect(ctx,-18,-2,37,4,1,'#dca66c');
    shape(ctx,[[19,-6],[31,0],[19,6]],'#e9ebdf');
    shape(ctx,[[-18,0],[-28,-8],[-26,0],[-28,8]],'#5e9baa');
    ctx.restore();
  }

  function venom(ctx, progress) {
    if (progress<=0 || progress>=1) return;
    var p=ease(progress);
    var startX=432, startY=116;
    var tipX=lerp(startX,650,p);
    var tipY=lerp(startY,320,p)-Math.sin(p*Math.PI)*30;
    ctx.save();
    ctx.strokeStyle='rgba(45,240,210,.35)';ctx.lineWidth=15;ctx.lineCap='round';
    ctx.beginPath();ctx.moveTo(startX,startY);ctx.quadraticCurveTo(564,161,tipX,tipY);ctx.stroke();
    ctx.strokeStyle='#7dffe3';ctx.lineWidth=5;
    ctx.beginPath();ctx.moveTo(startX,startY);ctx.quadraticCurveTo(564,161,tipX,tipY);ctx.stroke();
    for (var drop=0;drop<8;drop++) {
      var t=p*drop/8;
      var xx=lerp(startX,tipX,t);
      var yy=lerp(startY,tipY,t)-Math.sin(t*Math.PI)*18;
      oval(ctx,xx+Math.sin(drop*6)*5,yy,2.5+(drop%2)*2,2.5+(drop%2)*2,'#7ffce1');
    }
    ctx.restore();
  }

  function raid(ctx, level, milliseconds) {
    var t=milliseconds/1000;
    var x=758, y=339;
    var options={facing:'left',walk:true,carry:false,scared:false};
    if (level<=2) {
      var approach=level===2?2.5:2.05;
      var grabAt=level===2?3.05:2.55;
      if (t<approach) {
        x=lerp(758,469,ease(t/approach));
        if (level===2 && t>1.3 && t<2.2) y-=Math.sin(range(t,1.3,2.2)*Math.PI)*38;
      } else if (t<grabAt) {
        x=469;options.walk=false;
      } else {
        x=lerp(469,714,ease(range(t,grabAt,5.1)));
        options.facing='right';options.carry=true;
      }
    } else if (level===3) {
      if (t<1.3) x=lerp(758,640,ease(t/1.3));
      else if (t<2.05) { x=640+Math.sin((t-1.3)*9)*5;options.walk=false;options.scared=true; }
      else { x=lerp(640,748,ease(range(t,2.05,4.3)));options.facing='right';options.scared=true; }
    } else {
      if (t<1.35) x=lerp(758,675,ease(t/1.35));
      else if (t<3.0) { x=675;options.walk=false;options.scared=t>1.75; }
      else { x=lerp(675,748,ease(range(t,3.0,5.05)));options.facing='right';options.scared=true; }
    }
    if (t>5.05) {x=level<=2?714:748;options.walk=false;}
    fox(ctx,x,y,t,options);
    if (level===4 && t>=1.45 && t<=2.85) arrow(ctx,range(t,1.45,2.85));
    if (level===5 && t>=1.42 && t<=2.85) venom(ctx,range(t,1.42,2.85));
    if (level>=3 && t>1.7 && t<2.95) {
      var dust=range(t,1.7,2.95);
      ctx.save();ctx.globalAlpha=1-dust;
      for (var i=0;i<7;i++) {
        oval(ctx,660+Math.cos(i*2.3)*20*dust,334-Math.sin(i*2)*13*dust,
          3+(i%2),2.5,'#f6d9ae');
      }
      ctx.restore();
    }
  }

  function chestTaken(level, attackMs, stolen) {
    return stolen || (level<=1 && attackMs>=2550) || (level===2 && attackMs>=3050);
  }

  function levelOf(value, policy) {
    if (!value) return 0;
    var chars=Array.from(value);
    var length=chars.length;
    if (length<8) return 0;

    var lower=(value.match(/[a-z]/g)||[]).length;
    var upper=(value.match(/[A-Z]/g)||[]).length;
    var numbers=(value.match(/[0-9]/g)||[]).length;
    var specials=(value.match(/[^a-zA-Z0-9]/g)||[]).length;
    var groups=[lower,upper,numbers,specials].filter(function (count) {return count>0;}).length;
    var normalized=value.toLowerCase().replace(/[@4]/g,'a').replace(/0/g,'o').replace(/[1!]/g,'i');
    var known=/^(?:password|passw[o0]rd|123456|123456789|qwerty|admin|letmein|welcome|iloveyou|hunter2|abcdef|asdf|football)/;
    var unique=new Set(chars.map(function (char) {return char.toLowerCase();})).size;
    var repeatedRun=value.match(/(.)\1{3,}/u);
    var repeating=repeatedRun && repeatedRun[0].length>=length/2;
    var sequence=/(?:1234|2345|3456|4567|5678|6789|9876|abcd|bcde|cdef|qwer|asdf)/i.test(value);
    if (known.test(normalized) || repeating || (sequence && length<=10) || unique<5) return 0;

    // A conservative illustration, independent of server-side password validation.
    var stage=length>=16?5:length>=14?4:length>=12?3:length>=10?2:1;
    if (sequence) stage=Math.min(stage,3);
    if (groups===1) stage=Math.min(stage,1);
    if (groups===2) stage=Math.min(stage,2);
    if (groups===3) stage=Math.min(stage,4);
    if (specials===0) stage=Math.min(stage,2);
    if (numbers===0) stage=Math.min(stage,3);
    if (upper===0 || lower===0) stage=Math.min(stage,3);
    if (unique<8) stage=Math.min(stage,2);
    if (length<policy.length) stage=Math.min(stage,3);

    var meetsPolicy=length>=policy.length && lower>=policy.lowercase &&
      upper>=policy.uppercase && numbers>=policy.numeric && specials>=policy.specials;
    if (!meetsPolicy) stage=Math.min(stage,3);
    if (stage===5 && groups<4) stage=4;
    return stage;
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
    var pixelRatio = Math.min(2, window.devicePixelRatio || 1);
    canvas.width = Math.round(WIDTH * pixelRatio);
    canvas.height = Math.round(HEIGHT * pixelRatio);
    ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
    ctx.imageSmoothingEnabled = true;
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
      drawStage(ctx, state.built);

      if (!chestTaken(state.level, state.attackMs, state.chestStolen)) {
        treasureChest(ctx, 365, 333, 1);
        if (state.built >= TARGET_PIECES[4]) {
          // The treasure remains visible behind the closed portcullis.
          ctx.save();
          ctx.globalAlpha = 0.75;
          for (var bar = 0; bar < 8; bar++) {
            roundedRect(ctx, 323 + bar * 12, 279, 4, 64, 1, '#b8c8d0');
          }
          ctx.restore();
        }
      }

      if (state.level === 5 && state.built >= TARGET_PIECES[5] - 1) {
        chameleon(ctx, 343, 104, state.seconds, state.attackMs >= 950);
      }

      if (state.attackMs >= 0) {
        raid(ctx, state.level, state.attackMs);
      } else {
        fox(ctx, state.chestStolen ? 714 : 758, 339, state.seconds, {
          facing: state.chestStolen ? 'right' : 'left',
          walk: false, carry: state.chestStolen, scared: false,
        });
      }

      if (state.pulseMs > 0 && state.level > 0) {
        var pulse = 1 - state.pulseMs / 820;
        ctx.save();ctx.globalAlpha = (1-pulse) * 0.72;
        for (var sparkle = 0; sparkle < 12; sparkle++) {
          var angle = sparkle * Math.PI * 2 / 12;
          oval(ctx, 365 + Math.cos(angle) * (95 + pulse * 90),
            248 + Math.sin(angle) * (58 + pulse * 60), 2.5, 2.5, '#ffe0a1');
        }
        ctx.restore();
      }

      for (var i = 0; i < 5; i++) {
        var x = 22 + i * 39;
        shape(ctx, [[x,20],[x+27,20],[x+27,37],[x+13.5,50],[x,37]],
          i < state.level ? '#f9c973' : '#4c6479');
        shape(ctx, [[x+4,23],[x+23,23],[x+23,36],[x+13.5,44],[x+4,36]],
          i < state.level ? '#fff0b9' : '#233e57');
        if (i < state.level) oval(ctx,x+9,28,2.5,3,'rgba(255,255,255,.8)');
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
