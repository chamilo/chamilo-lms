var HotSpotAdmin = (function () {
    var HotspotModel = function (attributes) {
        this.attributes = attributes;
        this.id = 0;
        this.name = '';

        this.changeEvent = null;
    };
    HotspotModel.prototype.set = function (key, value) {
        this.attributes[key] = value;

        if (this.changeEvent) {
            this.changeEvent(this);
        }
    };
    HotspotModel.prototype.get = function (key) {
        return this.attributes[key];
    };
    HotspotModel.prototype.onChange = function (callback) {
        this.changeEvent = callback;
    };
    HotspotModel.prototype.checkPoint = function (x, y) {
        return false;
    };
    HotspotModel.decode = function () {
        return new this;
    };
    HotspotModel.prototype.encode = function () {
        return '';
    };

    var SquareModel = function (attributes) {
        HotspotModel.call(this, attributes);
    };
    SquareModel.prototype = Object.create(HotspotModel.prototype);
    SquareModel.prototype.setStartPoint = function (x, y) {
        x = parseInt(x);
        y = parseInt(y);

        this.set('x', x);
        this.set('y', y);
    };
    SquareModel.prototype.setEndPoint = function (x, y) {
        var startX = this.get('x'),
            startY = this.get('y');

        x = parseInt(x);
        y = parseInt(y);

        if (x >= startX) {
            this.set('width', x - startX);
        }

        if (y >= startY) {
            this.set('height', y - startY);
        }
    };
    SquareModel.prototype.checkPoint = function (x, y) {
        var left = this.get('x'),
            right = this.get('x') + this.get('width'),
            top = this.get('y'),
            bottom = this.get('y') + this.get('height');

        var xIsValid = x >= left && x <= right,
            yIsValid = y >= top && y <= bottom;

        return xIsValid && yIsValid;
    };
    SquareModel.decode = function (hotspotInfo) {
        var coords = hotspotInfo.coord.split('|'),
            position = coords[0].split(';'),
            hotspot = new SquareModel({
                x: parseInt(position[0]),
                y: parseInt(position[1]),
                width: parseInt(coords[1]),
                height: parseInt(coords[2])
            });

        hotspot.id = hotspotInfo.id;
        hotspot.name = hotspotInfo.answer;

        return hotspot;
    };
    SquareModel.prototype.encode = function () {
        return [
            [
                this.get('x'),
                this.get('y')
            ].join(';'),
            this.get('width'),
            this.get('height')
        ].join('|');
    };

    var EllipseModel = function (attributes) {
        HotspotModel.call(this, attributes);
    };
    EllipseModel.prototype = Object.create(HotspotModel.prototype);
    EllipseModel.prototype.setStartPoint = function (x, y) {
        x = parseInt(x);
        y = parseInt(y);

        this.set('centerX', x);
        this.set('centerY', y);
    };
    EllipseModel.prototype.setEndPoint = function (x, y) {
        var startX = this.get('centerX'),
            startY = this.get('centerY'),
            width = 0,
            height = 0;

        x = parseInt(x);
        y = parseInt(y);

        if (x >= startX) {
            width = x - startX;

            this.set('radiusX', Math.round(width / 2));
            this.set('centerX', startX + this.get('radiusX'));
        }

        if (y >= startY) {
            height = y - startY;

            this.set('radiusY', Math.round(height / 2));
            this.set('centerY', startY + this.get('radiusY'));
        }
    };
    EllipseModel.prototype.checkPoint = function (x, y) {
        var dX = x - this.get('centerX'),
            dY = y - this.get('centerY');

        var dividend = Math.pow(dX, 2) / Math.pow(this.get('radiusX'), 2),
            divider = Math.pow(dY, 2) / Math.pow(this.get('radiusY'), 2);

        return dividend + divider <= 1;
    };
    EllipseModel.decode = function (hotspotInfo) {
        var coords = hotspotInfo.coord.split('|'),
            center = coords[0].split(';'),
            hotspot = new EllipseModel({
                centerX: parseInt(center[0]),
                centerY: parseInt(center[1]),
                radiusX: parseInt(coords[1]),
                radiusY: parseInt(coords[2])
            });

        hotspot.id = hotspotInfo.id;
        hotspot.name = hotspotInfo.answer;

        return hotspot;
    };
    EllipseModel.prototype.encode = function () {
        return [
            [
                this.get('centerX'),
                this.get('centerY')
            ].join(';'),
            this.get('radiusX'),
            this.get('radiusY')
        ].join('|');
    };

    var PolygonModel = function (attributes) {
        HotspotModel.call(this, attributes);
    };
    PolygonModel.prototype = Object.create(HotspotModel.prototype);
    PolygonModel.prototype.addPoint = function (x, y) {
        var points = this.get('points');

        x = parseInt(x);
        y = parseInt(y);

        points.push([x, y]);

        this.set('points', points);
    };
    PolygonModel.prototype.checkPoint = function (x, y) {
        var points = this.get('points'),
            isInside = false;

        for (var i = 0, j = points.length - 1; i < points.length; j = i++) {
            var xi = points[i][0],
                yi = points[i][1],
                xj = points[j][0],
                yj = points[j][1];

            var intersect = ((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);

            if (intersect) {
                isInside = !isInside;
            }
        }

        return isInside;
    };
    PolygonModel.decode = function (hotspotInfo) {
        var pairedPoints = hotspotInfo.coord.split('|'),
            points = [],
            hotspot = new PolygonModel({
                points: []
            });

        $.each(pairedPoints, function (index, pair) {
            var point = pair.split(';');

            points.push([
                point[0],
                point[1]
            ]);
        });

        hotspot.set('points', points);

        return hotspot;
    };
    PolygonModel.prototype.encode = function () {
        var pairedPoints = [];

        this.get('points').forEach(function (point) {
            pairedPoints.push(
                point.join(';')
            );
        });

        return pairedPoints.join('|');
    };

    var HotspotsCollection = function () {
        this.hotspots = [];
        this.length = 0;

        this.addEvent = null;
    };
    HotspotsCollection.prototype.add = function (hotspot) {
        this.hotspots.push(hotspot);
        this.length++;

        if (this.addEvent) {
            this.addEvent(hotspot);
        }
    };
    HotspotsCollection.prototype.get = function (index) {
        return this.hotspots[index];
    };
    HotspotsCollection.prototype.set = function (index, newHotspot) {
        this.hotspots[index] = newHotspot;
    };
    HotspotsCollection.prototype.onAdd = function (callback) {
        this.addEvent = callback;
    };

    var HotspotSVG = function (modelModel, index) {
        var self = this;

        this.model = modelModel;
        this.hotspotIndex = index;

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'rect');

        this.model.onChange(function (hotspotModel) {
            self.render();
        });
    };
    HotspotSVG.prototype.render = function () {
        var newEl = null;

        if (this.model instanceof SquareModel) {
            newEl = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            newEl.setAttribute('x', this.model.get('x'));
            newEl.setAttribute('y', this.model.get('y'));
            newEl.setAttribute('width', this.model.get('width'));
            newEl.setAttribute('height', this.model.get('height'));
        } else if (this.model instanceof EllipseModel) {
            newEl = document.createElementNS('http://www.w3.org/2000/svg', 'ellipse');
            newEl.setAttribute('cx', this.model.get('centerX'));
            newEl.setAttribute('cy', this.model.get('centerY'));
            newEl.setAttribute('rx', this.model.get('radiusX'));
            newEl.setAttribute('ry', this.model.get('radiusY'));
        } else if (this.model instanceof PolygonModel) {
            var pointsPaired = [];

            this.model.get('points').forEach(function (point) {
                pointsPaired.push(point.join(','));
            });

            newEl = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
            newEl.setAttribute(
                'points',
                pointsPaired.join(' ')
            );
        }

        newEl.setAttribute('class', 'hotspot-' + this.hotspotIndex);

        if (this.el.parentNode) {
            this.el.parentNode.replaceChild(newEl, this.el);
        }

        this.el = newEl;

        return this;
    };

    var HotspotSelect = function (index, hotspotsCollection, hotspotSVG) {
        this.hotspotIndex = index;
        this.hotspotsCollection = hotspotsCollection;
        this.hotspotSVG = hotspotSVG;

        this.el = document.createElement('div');
        this.el.className = 'col-xs-6 col-sm-4 col-md-3 col-lg-2';

        selectedHotspotIndex = this.hotspotIndex;

        $('.input-group').removeClass('active');
    };
    HotspotSelect.prototype.render = function () {
        var self = this,
            $el = $(this.el);

        var template = '\n\
            <div class="input-group hotspot-'  + this.hotspotIndex + ' active">\n\
                <span class="input-group-addon" id="hotspot-' + this.hotspotIndex + '">\n\
                    <span class="fa fa-square fa-fw" data-hidden="true"></span>\n\
                    <span class="sr-only">' + (this.hotspotSVG.model.get('name') ? this.hotspotSVG.model.get('name') : 'hotspot ' + this.hotspotIndex) + '</span>\n\
                </span>\n\
                <select class="form-control" aria-describedby="hotspot-' + this.hotspotIndex + '">\n\
                    <option value="square">' + lang.Square + '</option>\n\
                    <option value="ellipse">' + lang.Circle + '</option>\n\
                    <option value="polygon">' + lang.Polygon + '</option>\n\
                </select>\n\
            </div>\n\
        ';
        $el.html(template);

        $el.find('select')
            .on('change', function () {
                selectedHotspotIndex = self.hotspotIndex;

                var newHotspot = null,
                    changeEvent = self.hotspotSVG.model.changeEvent;

                switch (this.value) {
                    case 'square':
                        newHotspot = new SquareModel({
                            x: 0,
                            y: 0,
                            width: 0,
                            height: 0
                        });
                        break;

                    case 'ellipse':
                        newHotspot = new EllipseModel({
                            centerX: 0,
                            centerY: 0,
                            radiusX: 0,
                            radiusY: 0
                        });
                        break;

                    case 'polygon':
                        newHotspot = new PolygonModel({
                            points: []
                        });
                        break;
                }

                newHotspot.onChange(changeEvent);

                self.hotspotsCollection.set(self.hotspotIndex, newHotspot);
                self.hotspotSVG.model = newHotspot;
            })
            .on('focus', function () {
                $('.input-group').removeClass('active');

                $el.find('.input-group').addClass('active');

                selectedHotspotIndex = self.hotspotIndex;
            })
            .val(function () {
                if (self.hotspotSVG.model instanceof SquareModel) {
                    return 'square';
                }

                if (self.hotspotSVG.model instanceof EllipseModel) {
                    return 'ellipse';
                }

                if (self.hotspotSVG.model instanceof PolygonModel) {
                    return 'polygon';
                }
            });

        return this;
    };

    var ContextMenu = function () {
        this.el = document.createElement('ul');

        $(this.el).addClass('dropdown-menu').attr('id', "hotspot-context-menu");

        this.hideEvent = null;
    };
    ContextMenu.prototype.onHide = function (callback) {
        this.hideEvent = callback;
    };
    ContextMenu.prototype.render = function () {
        var self = this,
            template = '\n\
                <li>\n\
                    <a href="#">' + 'ClosePolygon' + '</a>\n\
                </li>\n\
            ';

        $(this.el).html(template);
        
        $(this.el).find('a').on('click', function (e) {
            e.preventDefault();

            if (self.hideEvent) {
                self.hideEvent(e);
            }

            $(self.el).hide();
        });

        return this;
    };
    ContextMenu.prototype.show = function (x, y) {
        $(this.el).css({left: x, top: y}).show();
    };

    var HotspotsSVG = function (hotspotsCollection, image) {
        var self = this;

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.collection = hotspotsCollection;
        this.image = image;

        this.collection.onAdd(function (hotspotModel) {
            self.renderHotspot(hotspotModel);
        });
    };
    HotspotsSVG.prototype.render = function () {
        this.el.setAttribute('version', '1.1');
        this.el.setAttribute('viewBox', '0 0 ' + this.image.width + ' ' + this.image.height);

        var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'href', this.image.src);
        imageSvg.setAttribute('width', this.image.width);
        imageSvg.setAttribute('height', this.image.height);

        this.el.appendChild(imageSvg);

        this.setEvents();

        return this;
    };
    HotspotsSVG.prototype.renderHotspot = function (hotspot) {
        var hotspotIndex = this.collection.length - 1,
            hotspotSVG = new HotspotSVG(hotspot, hotspotIndex);

        this.el.appendChild(
            hotspotSVG.render().el
        );

        var hotspotSelect = new HotspotSelect(hotspotIndex, this.collection, hotspotSVG);

        $(config.selector).parent().find('.row').append(
            hotspotSelect.render().el
        );
    };
    HotspotsSVG.prototype.setEvents = function () {
        var self = this,
            $el = $(this.el);
            isDrawing = false;

        var getPointOnImage = function (x, y) {
                var pointerPosition = {
                        left: x + window.scrollX,
                        top: y + window.scrollY
                    },
                    canvasOffset = {
                        x: self.el.getBoundingClientRect().left + window.scrollX,
                        y: self.el.getBoundingClientRect().top + window.scrollY
                    };

                return {
                    x: Math.round(pointerPosition.left - canvasOffset.x),
                    y: Math.round(pointerPosition.top - canvasOffset.y)
                };
            },
            startPoint = {
                x: 0,
                y: 0
            };

        $el.on('dragstart', function (e) {
                e.preventDefault();
            })
            .on('mousedown', function (e) {
                e.preventDefault();

                if (e.button > 0) {
                    return;
                }

                if (self.collection.length <= 0) {
                    return;
                }

                var currentHotspot = self.collection.get(selectedHotspotIndex);

                if (!currentHotspot) {
                    return;
                }

                startPoint = getPointOnImage(e.clientX, e.clientY);

                if (currentHotspot instanceof SquareModel) {
                    isDrawing = true;

                    currentHotspot.set('x', startPoint.x);
                    currentHotspot.set('y', startPoint.y);
                    currentHotspot.set('width', 0);
                    currentHotspot.set('height', 0);

                    return;
                }

                if (currentHotspot instanceof EllipseModel) {
                    isDrawing = true;

                    currentHotspot.set('centerX', 0);
                    currentHotspot.set('centerY', 0);
                    currentHotspot.set('radiusX', 0);
                    currentHotspot.set('radiusY', 0);
                    return;
                }
            })
            .on('mousemove', function (e) {
                e.preventDefault();

                if (self.collection.length <= 0) {
                    return;
                }

                if (!isDrawing) {
                    return;
                }

                var currentHotspot = self.collection.get(selectedHotspotIndex),
                    currentPoint = getPointOnImage(e.clientX, e.clientY);

                if (!currentHotspot) {
                    return;
                }

                if (currentHotspot instanceof SquareModel) {
                    if (startPoint.x < currentPoint.x) {
                        currentHotspot.set('width', currentPoint.x - startPoint.x);
                    } else {
                        currentHotspot.set('x', currentPoint.x);
                        currentHotspot.set('width', startPoint.x - currentPoint.x);
                    }

                    if (startPoint.y < currentPoint.y) {
                        currentHotspot.set('height', currentPoint.y - startPoint.y);
                    } else {
                        currentHotspot.set('y', currentPoint.y);
                        currentHotspot.set('height', startPoint.y - currentPoint.y);
                    }

                    return;
                }

                if (currentHotspot instanceof EllipseModel) {
                    var width = 0,
                        height = 0;

                    if (startPoint.x < currentPoint.x) {
                        width = currentPoint.x - startPoint.x;

                        currentHotspot.set('radiusX', Math.round(width / 2));
                        currentHotspot.set('centerX', startPoint.x + currentHotspot.get('radiusX'));
                    } else {
                        width = startPoint.x - currentPoint.x;

                        currentHotspot.set('radiusX', Math.round(width / 2));
                        currentHotspot.set('centerX', currentPoint.x + currentHotspot.get('radiusX'))
                    }

                    if (startPoint.y < currentPoint.y) {
                        height = currentPoint.y - startPoint.y;

                        currentHotspot.set('radiusY', Math.round(height / 2));
                        currentHotspot.set('centerY', startPoint.y + currentHotspot.get('radiusY'));
                    } else {
                        height = startPoint.y - currentPoint.y;

                        currentHotspot.set('radiusY', Math.round(height / 2));
                        currentHotspot.set('centerY', currentPoint.y + currentHotspot.get('radiusY'));
                    }

                    return;
                }
            })
            .on('mouseup', function (e) {
                e.preventDefault();

                if (e.button > 0) {
                    return;
                }

                if (self.collection.length <= 0) {
                    return;
                }

                if (!isDrawing) {
                    return;
                }

                var currentHotspot = self.collection.get(selectedHotspotIndex),
                    hotspotTypeSelector = '[name="hotspot_type[' + (selectedHotspotIndex + 1) + ']"]',
                    hotspotCoordSelector = '[name="hotspot_coordinates[' + (selectedHotspotIndex + 1) + ']"]';

                if (!currentHotspot) {
                    return;
                }

                if (currentHotspot instanceof SquareModel) {
                    $(hotspotTypeSelector).val('square');
                    $(hotspotCoordSelector).val(currentHotspot.encode());

                    isDrawing = false;
                } else if (currentHotspot instanceof EllipseModel) {
                    $(hotspotTypeSelector).val('circle');
                    $(hotspotCoordSelector).val(currentHotspot.encode());

                    isDrawing = false;
                }
            })
            .on('click', function (e) {
                e.preventDefault();

                var currentHotspot = self.collection.get(selectedHotspotIndex),
                    currentPoint = getPointOnImage(e.clientX, e.clientY);

                if (!currentHotspot) {
                    return;
                }

                if (currentHotspot instanceof PolygonModel) {
                    var points = [];

                    if (!isDrawing) {
                        isDrawing = true;
                    } else {
                        points = currentHotspot.get('points');
                    }

                    points.push([currentPoint.x, currentPoint.y]);

                    currentHotspot.set('points', points);

                    return;
                }
            })
            .on('contextmenu', function (e) {
                e.preventDefault();

                var currentPoint = getPointOnImage(e.clientX, e.clientY),
                    currentHotspot = self.collection.get(selectedHotspotIndex),
                    hotspotTypeSelector = '[name="hotspot_type[' + (selectedHotspotIndex + 1) + ']"]',
                    hotspotCoordSelector = '[name="hotspot_coordinates[' + (selectedHotspotIndex + 1) + ']"]';

                if (!currentHotspot) {
                    return;
                }

                if (currentHotspot instanceof PolygonModel) {
                    contextMenu.show(currentPoint.x, currentPoint.y);
                    contextMenu.onHide(function () {
                        $(hotspotTypeSelector).val('poly');
                        $(hotspotCoordSelector).val(currentHotspot.encode());

                        isDrawing = false;
                    });
                }
            });
    };

    var startHotspotsAdmin = function (questionInfo) {
        var image = new Image();
        image.onload = function () {
            var hotspotsCollection = new HotspotsCollection();

            var hotspotsSVG = new HotspotsSVG(hotspotsCollection, this);

            $(config.selector)
                .css('width', image.width)
                .append(hotspotsSVG.render().el);

            $(config.selector).parent().append('<div class="row"></div>');

            contextMenu = new ContextMenu();

            $(config.selector).append(
                contextMenu.render().el
            );

            $.each(questionInfo.hotspots, function (index, hotspotInfo) {
                var hotspot = null;

                switch (hotspotInfo.type) {
                    case 'square':
                    default:
                        hotspot = SquareModel.decode(hotspotInfo);
                        break;

                    case 'circle':
                        hotspot = EllipseModel.decode(hotspotInfo);
                        break;

                    case 'poly':
                        hotspot = PolygonModel.decode(hotspotInfo);
                        break;
                }

                hotspotsCollection.add(hotspot);
            });
        };
        image.src = questionInfo.image;

        lang = questionInfo.lang;
    };

    var config, lang, selectedHotspotIndex = 0, contextMenu;

    return {
        init: function (settings) {
            config = $.extend({
                questionId: 0,
                selector: ''
            }, settings);

            if (!config.questionId || !config.selector) {
                return;
            }

            var xhrQuestion = $.getJSON('/main/exercice/hotspot_actionscript_admin.as.php', {
                modifyAnswers: parseInt(config.questionId)
            });

            $.when(xhrQuestion).done(function (questionInfo) {
                startHotspotsAdmin(questionInfo);
            });
        }
    };
})();
