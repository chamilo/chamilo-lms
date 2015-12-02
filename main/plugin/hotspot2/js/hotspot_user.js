var HotSpotUser = (function () {
    var Answer = function () {
        this.x = 0;
        this.y = 0;
    };

    var HotSpot = function () {
        this.id = 0;
        this.name = '';
    };
    HotSpot.prototype.checkPoint = function (x, y) {
        return false;
    };

    var Square = function () {
        HotSpot.call(this);

        this.x = 0,
        this.y = 0,
        this.width = 0,
        this.height = 0;
    };
    Square.prototype = Object.create(HotSpot.prototype);
    Square.prototype.checkPoint = function (x, y) {
        var left = this.x,
            right = this.x + this.width,
            top = this.y,
            bottom = this.y + this.height;

        var xIsValid = x >= left && x <= right,
            yIsValid = y >= top && y <= bottom;

        return xIsValid && yIsValid;
    };

    var Ellipse = function () {
        HotSpot.call(this);

        this.centerX = 0;
        this.centerY = 0;
        this.radiusX = 0;
        this.radiusY = 0;
    };
    Ellipse.prototype = Object.create(HotSpot.prototype);
    Ellipse.prototype.checkPoint = function (x, y) {
        var dX = x - this.centerX,
            dY = y - this.centerY;

        return Math.pow(dX, 2) / Math.pow(this.radiusX, 2) + Math.pow(dY, 2) / Math.pow(this.radiusY, 2) <= 1;
    };

    var Polygon = function () {
        HotSpot.call(this);

        this.points = [];
    };
    Polygon.prototype = Object.create(HotSpot.prototype);
    Polygon.prototype.checkPoint = function (x, y) {
        var isInside = false;

        for (var i = 0, j = this.points.length - 1; i < this.points.length; j = i++) {
            var xi = this.points[i][0],
                yi = this.points[i][1],
                xj = this.points[j][0],
                yj = this.points[j][1];

            var intersect = ((yi > y) != (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);

            if (intersect) {
                isInside = !isInside;
            }
        }

        return isInside;
    };

    var CanvasSVG = function (image) {
        var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'href', image.src);
        imageSvg.setAttribute('width', image.width);
        imageSvg.setAttribute('height', image.height);

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.el.setAttribute('version', '1.1');
        this.el.setAttribute('viewBox', '0 0 ' + image.width + ' ' + image.height);
        this.el.appendChild(imageSvg);

        this.messagesEl = document.createElement('div');
    };
    CanvasSVG.prototype.setEvents = function () {
        var self = this;

        var getPointOnImage = function (x, y) {
            var pointerPosition = {
                    left: x + window.scrollX,
                    top: y + window.scrollY
                },
                canvasOffset = {
                    x: self.el.getBoundingClientRect().x + window.scrollX,
                    y: self.el.getBoundingClientRect().y + window.scrollY
                };

            return {
                x: Math.round(pointerPosition.left - canvasOffset.x),
                y: Math.round(pointerPosition.top - canvasOffset.y)
            };
        };

        this.el.addEventListener('dragstart', function (e) {
            e.preventDefault();
        }, false);
        this.el.addEventListener('click', function (e) {
            e.preventDefault();

            if (answers.length >= hotSpots.length) {
                return;
            }

            var point = getPointOnImage(e.clientX, e.clientY);

            var answer = new Answer();
            answer.x = point.x;
            answer.y = point.y;

            answers.push(answer);
            self.addAnswer(answer);

            if (answers.length === hotSpots.length) {
                console.log(lang);
                self.messagesEl.textContent = lang.HotspotExerciseFinished;

                return;
            }

            self.messagesEl.textContent = lang.NextAnswer + ' ' + hotSpots[answers.length].name;
        });
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

        var hotSpot = hotSpots[answers.length - 1];

        var hotspotTxt = document.createElement('input');
        hotspotTxt.type = 'hidden';
        hotspotTxt.name = 'hotspot[' + config.questionId + '][' + hotSpot.id + ']';
        hotspotTxt.value = [answer.x, answer.y].join(';');

        var choiceTxt = document.createElement('input');
        choiceTxt.type = 'hidden';
        choiceTxt.name = 'choice[' + config.questionId + '][' + hotSpot.id + ']';
        choiceTxt.value = hotSpot.checkPoint(answer.x, answer.y) ? 1 : 0;

        this.el.parentNode.appendChild(hotspotTxt);
        this.el.parentNode.appendChild(choiceTxt);
    };
    CanvasSVG.prototype.startMessagesPanel = function () {
        this.messagesEl.textContent = lang.NextAnswer + ' ' + hotSpots[0].name;

        this.el.parentNode.parentNode.appendChild(this.messagesEl);
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

        if (hotSpot) {
            hotSpot.id = parseInt(hotSpotInfo.id);
            hotSpot.name = hotSpotInfo.answer;
        }

        return hotSpot;
    };

    var config, lang, hotSpots = [], answers = [];

    var startQuestion = function (hotSpotQuestionInfo) {
        var image = new Image();
        image.onload = function () {
            var canvasSVG = new CanvasSVG(this);

            hotSpotQuestionInfo.hotspots.forEach(function (hotSpotInfo) {
                var hotSpot = decodeHotSpot(hotSpotInfo);

                if (!hotSpot) {
                    return;
                }

                hotSpots.push(hotSpot);
            });

            $(config.selector)
                .css('width', this.width)
                .append(canvasSVG.el);

            canvasSVG.setEvents();
            canvasSVG.startMessagesPanel();
        };
        image.src = hotSpotQuestionInfo.image;

        lang = hotSpotQuestionInfo.lang;
    };

    return {
        init: function (settings) {
            config = $.extend({
                questionId: 0,
                selector: ''
            }, settings);

            if (!config.questionId || !config.selector) {
                return;
            }

            var xhrHotSpotQuestion = $.getJSON('/main/exercice/hotspot_actionscript.as.php', {
                modifyAnswers: parseInt(config.questionId)
            });

            $.when(xhrHotSpotQuestion).done(startQuestion);
        } 
    };
})();
