var HotSpotSolution = (function () {
    var Answer = function () {
        this.x = 0;
        this.y = 0;
    };

    var HotSpot = function () {
        this.id = 0;
        this.name = '';
    };

    var Square = function () {
        HotSpot.call(this);

        this.x = 0,
        this.y = 0,
        this.width = 0,
        this.height = 0;
    };
    Square.prototype = Object.create(HotSpot.prototype);

    var Ellipse = function () {
        HotSpot.call(this);

        this.centerX = 0;
        this.centerY = 0;
        this.radiusX = 0;
        this.radiusY = 0;
    };
    Ellipse.prototype = Object.create(HotSpot.prototype);

    var Polygon = function () {
        HotSpot.call(this);

        this.points = [];
    };
    Polygon.prototype = Object.create(HotSpot.prototype);

    var config, lang, hotSpots = [], answers = [];

    var CanvasSVG = function (image) {
        var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'href', image.src);
        imageSvg.setAttribute('width', image.width);
        imageSvg.setAttribute('height', image.height);

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.el.setAttribute('version', '1.1');
        this.el.setAttribute('viewBox', '0 0 ' + image.width + ' ' + image.height);
        this.el.appendChild(imageSvg);
    };
    CanvasSVG.prototype.addHotSpot = function (hotSpot) {
        var hotSpotSVG = null;

        if (hotSpot instanceof Square) {
            hotSpotSVG = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            hotSpotSVG.setAttribute('x', hotSpot.x);
            hotSpotSVG.setAttribute('y', hotSpot.y);
            hotSpotSVG.setAttribute('width', hotSpot.width);
            hotSpotSVG.setAttribute('height', hotSpot.height);
        } else if (hotSpot instanceof Ellipse) {
            hotSpotSVG = document.createElementNS('http://www.w3.org/2000/svg', 'ellipse');
            hotSpotSVG.setAttribute('cx', hotSpot.centerX);
            hotSpotSVG.setAttribute('cy', hotSpot.centerY);
            hotSpotSVG.setAttribute('rx', hotSpot.radiusX);
            hotSpotSVG.setAttribute('ry', hotSpot.radiusY);
        } else if (hotSpot instanceof Polygon) {
            var pointsPaired = [];

            hotSpot.points.forEach(function (point) {
                pointsPaired.push(point.join(','));
            });

            hotSpotSVG = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
            hotSpotSVG.setAttribute(
                'points',
                pointsPaired.join(' ')
            );
        }

        if (!hotSpotSVG) {
            return;
        }

        var color = colors[hotSpots.length - 1];

        hotSpotSVG.setAttribute('stroke-width', 2);
        hotSpotSVG.setAttribute('stroke', 'rgb(' + color + ')');
        hotSpotSVG.setAttribute('fill', 'rgba(' + color + ', 0.75)');

        this.el.appendChild(hotSpotSVG);
    };
    CanvasSVG.prototype.addAnswer = function (answer) {
        var pointSVG = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        pointSVG.setAttribute('cx', answer.x);
        pointSVG.setAttribute('cy', answer.y);
        pointSVG.setAttribute('r', 15);
        pointSVG.setAttribute('fill', '#00677C');

        var textSVG = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        textSVG.setAttribute('x', answer.x);
        textSVG.setAttribute('y', answer.y);
        textSVG.setAttribute('dy', 5);
        textSVG.setAttribute('font-family', 'sans-serif');
        textSVG.setAttribute('text-anchor', 'middle');
        textSVG.setAttribute('fill', 'white');
        textSVG.textContent = answers.length;

        this.el.appendChild(pointSVG);
        this.el.appendChild(textSVG);
    };

    var decodeHotSpot = function (hotSpotInfo) {
        var hotSpot = null,
            coords = hotSpotInfo.coord.split('|');

        switch (hotSpotInfo.type) {
            case 'square':
                var position = coords[0].split(';');

                hotSpot = new Square();
                hotSpot.x = parseInt(position[0]);
                hotSpot.y = parseInt(position[1]);
                hotSpot.width = parseInt(coords[1]);
                hotSpot.height = parseInt(coords[2]);
                break;
            case 'circle':
                var center = coords[0].split(';');

                hotSpot = new Ellipse();
                hotSpot.centerX = parseInt(center[0]);
                hotSpot.centerY = parseInt(center[1]);
                hotSpot.radiusX = parseInt(coords[1]);
                hotSpot.radiusY = parseInt(coords[2]);
                break;
            case 'poly':
                hotSpot = new Polygon();

                coords.forEach(function (pairedCoord) {
                    var coord = pairedCoord.split(';');

                    hotSpot.points.push([
                        parseInt(coord[0]),
                        parseInt(coord[1])
                    ]);
                });
                break;
        }

        return hotSpot;
    };

    var decodeAnswer = function (answerInfo) {
        var answer = null,
            coords = answerInfo.split(';');

        answer = new Answer();
        answer.x = coords[0];
        answer.y = coords[1];

        return answer;
    };

    var colors = [
        '66, 113, 181',
        '254, 142, 22',
        '69, 199, 240',
        '188, 214, 49',
        '214, 49, 115',
        '215, 215, 215',
        '144, 175, 221',
        '174, 134, 64',
        '79, 146, 66',
        '244, 235, 36',
        '237, 32, 36',
        '59, 59, 59'
    ];

    var startAnswer = function (hotSpotAnswerInfo) {
        var image = new Image();
        image.onload = function () {
            var canvasSVG = new CanvasSVG(this);

            hotSpotAnswerInfo.hotspots.forEach(function (hotSpotInfo) {
                var hotSpot = decodeHotSpot(hotSpotInfo);

                if (!hotSpot) {
                    return;
                }

                hotSpots.push(hotSpot);
                canvasSVG.addHotSpot(hotSpot);
            });

            hotSpotAnswerInfo.answers.forEach(function (answerInfo) {
               var answer = decodeAnswer(answerInfo);

               answers.push(answer);
               canvasSVG.addAnswer(answer);
            });

            $(config.selector)
                .css('width', this.width)
                .append(canvasSVG.el);
        };
        image.src = hotSpotAnswerInfo.image;

        lang = hotSpotAnswerInfo.lang;
    };

    return {
        init: function (settings) {
            config = $.extend({
                questionId: 0,
                exerciseId: 0,
                selector: ''
            }, settings);

            if (!config.questionId || !config.selector) {
                return;
            }

            var xhrHotSpotAnswers = $.getJSON('/main/exercice/hotspot_answers.as.php', {
                modifyAnswers: parseInt(config.questionId),
                exe_id: parseInt(config.exerciseId)
            });

            $.when(xhrHotSpotAnswers).done(startAnswer);
        }
    };
})();