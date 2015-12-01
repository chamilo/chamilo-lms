var HotSpotAdmin = (function () {
    var HotSpotSquare = function () {
        this.x = 0;
        this.y = 0;
        this.width = 0;
        this.height = 0;
    };
    HotSpotSquare.prototype.setStartPoint = function (x, y) {
        this.x = parseInt(x);
        this.y = parseInt(y);
    };
    HotSpotSquare.prototype.setEndPoint = function (x, y) {
        var x2, y2;

        x = parseInt(x);
        y = parseInt(y);

        if (x < this.x) {
            x2 = this.x;
            this.x = x;
        } else {
            x2 = x;
        }

        if (y < this.y) {
            y2 = this.y;
            this.y = y;
        } else {
            y2 = y;
        }

        this.width = Math.round(x2 - this.x);
        this.height = Math.round(y2 - this.y);
    };
    HotSpotSquare.prototype.encode = function () {
        var encodedPosition = [this.x, this.y].join(';');

        return [
            encodedPosition,
            this.width,
            this.height
        ].join('|');
    }

    var HotSpotEllipse = function () {
        this.centerX = 0;
        this.centerY = 0;
        this.radiusX = 0;
        this.radiusY = 0;
    };
    HotSpotEllipse.prototype.setStartPoint = function (x, y) {
        this.centerX = parseInt(x);
        this.centerY = parseInt(y);
    };
    HotSpotEllipse.prototype.setEndPoint = function (x, y) {
        var startX = this.centerX,
            startY = this.centerY,
            endX = 0,
            endY = 0;

        x = parseInt(x);
        y = parseInt(y);

        if (x < startX) {
            endX = startX;
            startX = x;
        } else {
            endX = x;
        }

        if (y < startY) {
            endY = startY;
            startY = y;
        } else {
            endY = y;
        }

        var width = Math.round(endX - startX);
        var height = Math.round(endY - startY);

        this.radiusX = Math.round(width / 2);
        this.radiusY = Math.round(height / 2);
        this.centerX = startX + this.radiusX;
        this.centerY = startY + this.radiusY;
    };
    HotSpotEllipse.prototype.encode = function () {
        var encodedCenter = [this.centerX, this.centerY].join(';');

        return [
            encodedCenter,
            this.radiusX,
            this.radiusY
        ].join('|');
    };

    var HotSpotPolygon = function () {
        this.points = [];
    };
    HotSpotPolygon.prototype.addPoint = function (x, y) {
        this.points.push([
            parseInt(x),
            parseInt(y)
        ]);
    };
    HotSpotPolygon.prototype.encode = function () {
        var encodedPoints = [];

        this.points.forEach(function (point) {
            encodedPoints.push(point.join(';'));
        });

        return encodedPoints.join('|');
    };

    var HotSpotEl = function (hotspot, color) {
        this.hotspot = hotspot;
        this.element = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        this.elStroke = 'rgb(' + color + ')';
        this.elFill = 'rgba(' + color + ', 0.5)';
    };
    HotSpotEl.prototype.render = function () {
        return this;
    };
    HotSpotEl.prototype.remove = function () {
        if (!this.element) {
            return;
        }

        this.element.parentNode.removeChild(this.element);
        this.hotspot = null;
    };

    var SquareEl = function (hotspot, color) {
        HotSpotEl.call(this, hotspot, color);

        this.element = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        this.element.setAttribute('stroke-width', 2);
        this.element.setAttribute('stroke', this.elStroke);
        this.element.setAttribute('fill', this.elFill);
    };
    SquareEl.prototype = Object.create(HotSpotEl.prototype);
    SquareEl.prototype.render = function () {
        this.element.setAttribute('x', this.hotspot.x);
        this.element.setAttribute('y', this.hotspot.y);
        this.element.setAttribute('width', this.hotspot.width);
        this.element.setAttribute('height', this.hotspot.height);

        return this.element;
    };

    var EllipseEl = function (hotspot, color) {
        HotSpotEl.call(this, hotspot, color);

        this.element = document.createElementNS('http://www.w3.org/2000/svg', 'ellipse');
        this.element.setAttribute('stroke-width', 2);
        this.element.setAttribute('stroke', this.elStroke);
        this.element.setAttribute('fill', this.elFill);
    };
    EllipseEl.prototype = Object.create(HotSpotEl.prototype);
    EllipseEl.prototype.render = function () {
        this.element.setAttribute('cx', this.hotspot.centerX);
        this.element.setAttribute('cy', this.hotspot.centerY);
        this.element.setAttribute('rx', this.hotspot.radiusX);
        this.element.setAttribute('ry', this.hotspot.radiusY);

        return this.element;
    };

    var PolygonEl = function (hotspot, color) {
        HotSpotEl.call(this, hotspot, color);

        this.element = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        this.element.setAttribute('stroke-width', 2);
        this.element.setAttribute('stroke', this.elStroke);
        this.element.setAttribute('fill', this.elFill);
    };
    PolygonEl.prototype = Object.create(HotSpotEl.prototype);
    PolygonEl.prototype.render = function () {
        var pointsPaired = [];

        this.hotspot.points.forEach(function (point) {
            pointsPaired.push(point.join(','));
        });

        this.element.setAttribute(
            'points',
            pointsPaired.join(' ')
        );

        return this.element;
    };

    var HotSpotSelectorEl = function (color, index, selectedValue) {
        this.hotSpotIndex = parseInt(index);
        this.elStroke = 'rgb(' + color + ')';
        this.elFill = 'rgba(' + color + ', 0.5)';

        switch (selectedValue) {
            case 'square':
            default:
                this.selectedValue = 'square';
                break;

            case 'circle':
                this.selectedValue = 'ellipse';
                break;

            case 'poly':
                this.selectedValue = 'polygon';
                break;
        }
    };
    HotSpotSelectorEl.prototype.render = function () {
        var self = this;

        this.el = document.createElement('div');
        this.el.className = 'col-xs-6 col-sm-3 col-md-2';
        this.el.innerHTML = '\n\
            <div class="input-group">\n\
                <span class="input-group-addon" id="hotspot-' + this.hotSpotIndex + '">\n\
                    <span class="fa fa-square fa-fw" data-hidden="true" style="color: ' + this.elStroke + '"></span>\n\
                </span>\n\
                <select class="form-control" aria-describedby="hotspot-' + this.hotSpotIndex + '">\n\
                    <option value="">Select</option>\n\
                    <option value="square">Square</option>\n\
                    <option value="ellipse">Ellipse</option>\n\
                    <option value="polygon">Polygon</option>\n\
                </select>\n\
            </div>\n\
        ';

        $(this.el).find('select').val(this.selectedValue);

        var selectShapeEvent = function (e) {
            switch (this.value) {
                case 'square':
                    //no break
                case 'ellipse':
                    //no break
                case 'polygon':
                    currentShapeType = this.value;
                    currentHotSpotIndex = self.hotSpotIndex;
                    break;

                default:
                    break;
            }
        };

        $(this.el).on('click', selectShapeEvent);
        $(this.el).find('select').on('change', selectShapeEvent);

        return this.el;
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
        '59, 59, 59',
        '247, 189, 226'
    ];

    var getPointOnImage = function (x, y) {
        var pointerPosition = {
            left: x + window.scrollX,
            top: y + window.scrollY
        },
            canvasOffset = {
                x: canvas.getBoundingClientRect().x + window.scrollX,
                y: canvas.getBoundingClientRect().y + window.scrollY
            };

        return {
            x: Math.round(pointerPosition.left - canvasOffset.x),
            y: Math.round(pointerPosition.top - canvasOffset.y)
        };
    };

    var startCanvas = function () {
        var newHotSpotEl = null,
            pressingShift = false;

        document.addEventListener('keydown', function (e) {
            if (e.keyCode === 16) {
                pressingShift = true;
            }
        }, false);
        document.addEventListener('keyup', function (e) {
            if (e.keyCode === 16) {
                pressingShift = false;
            }
        }, false);

        canvas.addEventListener('click', function (e) {
            e.preventDefault();
        }, false);

        container.addEventListener('dragstart', function (e) {
            e.preventDefault();
        }, false);
        container.addEventListener('click', function (e) {
            if (shapes.length >= colors.length) {
                return;
            }

            newHotSpotEl = draw(newHotSpotEl, e.clientX, e.clientY, pressingShift);

            if (!newHotSpotEl) {
                updateValues();
            }
        }, false);
    };

    var draw = function (hotSpotEl, x, y, isPressingShift) {
        var pointerPosition = getPointOnImage(x, y),
            hotSpot = null;

        if (!hotSpotEl) {
            switch (currentShapeType) {
                case 'square':
                    //no break
                case 'ellipse':
                    if (currentShapeType === 'ellipse') {
                        hotSpot = new HotSpotEllipse();
                        hotSpotEl = new EllipseEl(hotSpot, colors[currentHotSpotIndex]);
                    } else {
                        hotSpot = new HotSpotSquare();
                        hotSpotEl = new SquareEl(hotSpot, colors[currentHotSpotIndex]);
                    }

                    hotSpot.setStartPoint(pointerPosition.x, pointerPosition.y);
                    break;

                case 'polygon':
                    hotSpot = new HotSpotPolygon();
                    hotSpotEl = new PolygonEl(hotSpot, colors[currentHotSpotIndex]);

                    hotSpot.addPoint(pointerPosition.x, pointerPosition.y);
                    break;
            }

            shapes[currentHotSpotIndex].remove();
            shapes.splice(currentHotSpotIndex, 1, hotSpotEl);

            canvas.appendChild(hotSpotEl.render());

            return hotSpotEl;
        }

        switch (currentShapeType) {
            case 'square':
                //no break
            case 'ellipse':
                hotSpotEl.hotspot.setEndPoint(pointerPosition.x, pointerPosition.y);
                hotSpotEl.render();

                hotSpotEl = null;
                break;

            case 'polygon':
                $(container).find('#hotspot-alert').text('Keed pressed the SHIFT key and click the image to close the polygon');

                hotSpotEl.hotspot.addPoint(pointerPosition.x, pointerPosition.y);
                hotSpotEl.render();

                if (isPressingShift) {
                    hotSpotEl = null;
                    $(container).find('#hotspot-alert').text('');
                }
                break;
        }

        return hotSpotEl;
    };

    var updateValues = function () {
        var currentHotSpotEl = shapes[currentHotSpotIndex];

        if (currentHotSpotIndex === undefined) {
            return;
        }

        if (currentHotSpotEl.hotspot instanceof HotSpotSquare) {
            $('[name="hotspot_type[' + (currentHotSpotIndex + 1) + ']"]').val('square');
        } else if (currentHotSpotEl.hotspot instanceof HotSpotEllipse) {
            $('[name="hotspot_type[' + (currentHotSpotIndex + 1) + ']"]').val('circle');
        } else if (currentHotSpotEl.hotspot instanceof HotSpotPolygon) {
            $('[name="hotspot_type[' + (currentHotSpotIndex + 1) + ']"]').val('poly');
        }

        $('[name="hotspot_coordinates[' + (currentHotSpotIndex + 1) + ']"]').val(
            currentHotSpotEl.hotspot.encode()
        );
    };

    var loadHotSpots = function (hotSpotList) {
        hotSpotList.forEach(function (hotSpotData, index) {
            var hotSpot = null,
                hotSpotEl = null,
                color = colors[shapes.length];

            switch (hotSpotData.type) {
                case 'square':
                    hotSpot = new HotSpotSquare();
                    hotSpotEl = new SquareEl(hotSpot, color);

                    var coords = hotSpotData.coord.split('|'),
                        position = coords[0].split(';'),
                        x = parseInt(position[0]),
                        y = parseInt(position[1]),
                        width = parseInt(coords[1]),
                        height = parseInt(coords[2]);

                    hotSpot.setStartPoint(x, y);
                    hotSpot.setEndPoint(x + width, y + height);
                    break;
                case 'circle':
                    hotSpot = new HotSpotEllipse();
                    hotSpotEl = new EllipseEl(hotSpot, color);

                    var coords = hotSpotData.coord.split('|'),
                        position = coords[0].split(';'),
                        x = parseInt(position[0] - coords[1]),
                        y = parseInt(position[1] - coords[2]),
                        width = parseInt(coords[1]) * 2,
                        height = parseInt(coords[2]) * 2;

                    hotSpot.setStartPoint(x, y);
                    hotSpot.setEndPoint(x + width, y + height);
                    break;

                case 'poly':
                    hotSpot = new HotSpotPolygon();
                    hotSpotEl = new PolygonEl(hotSpot, color);

                    hotSpotData.coord.split('|').forEach(function (point) {
                        var exis = point.split(';');

                        hotSpot.addPoint(exis[0], exis[1]);
                    });
                    break;
            }

            if (hotSpotEl) {
                var hotSpotSelector = new HotSpotSelectorEl(color, index, hotSpotData.type);

                selectors.appendChild(hotSpotSelector.render());
                canvas.appendChild(hotSpotEl.render());
                shapes.push(hotSpotEl);
            }
        });
    };

    var container, canvas, selectors, currentShapeType, currentHotSpotIndex, shapes = [];

    return {
        init: function (questionId, imageSrc) {
            if (!questionId || !imageSrc) {
                return;
            }

            selectors = document.querySelector('#hotspot-selectors');
            container = document.querySelector('#hotspot-container');
            canvas = document.querySelector('#hotspot-container svg');
            currentShapeType = 'square';

            var xhrImage = new $.Deferred();

            var image = new Image();
            image.onload = function () {
                xhrImage.resolve(this);
            };
            image.onerror = function () {
                xhrImage.reject();
            };
            image.src = imageSrc;

            var xhrHotSpots = $.get('/main/exercice/hotspot_actionscript_admin.as.php', {
                modifyAnswers: parseInt(questionId)
            });

            $.when.apply($, [xhrImage, xhrHotSpots]).done(function (imageRequest, hotSpotsRequest) {
                var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
                imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', imageRequest.src);
                imageSvg.setAttribute('width', imageRequest.width);
                imageSvg.setAttribute('height', imageRequest.height);

                container.style.width = imageRequest.width + 'px';
                canvas.setAttribute('viewBox', '0 0 ' + imageRequest.width + ' ' + imageRequest.height);
                canvas.appendChild(imageSvg);

                loadHotSpots(hotSpotsRequest[0].hotspots);
                startCanvas();
            });
        }
    };
})();
