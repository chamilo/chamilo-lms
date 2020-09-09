window.HotspotQuestion = (function () {
    return function (settings) {
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
        SquareModel.decode = function (hotspotInfo) {
            var coords = hotspotInfo.coord.split('|'),
                position = coords[0].split(';'),
                hotspot = new SquareModel({
                    x: parseInt(position[0]),
                    y: parseInt(position[1]),
                    width: parseInt(coords[1]),
                    height: parseInt(coords[2])
                });

            hotspot.id = hotspotInfo.iid;
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
        EllipseModel.decode = function (hotspotInfo) {
            var coords = hotspotInfo.coord.split('|'),
                center = coords[0].split(';'),
                hotspot = new EllipseModel({
                    centerX: parseInt(center[0]),
                    centerY: parseInt(center[1]),
                    radiusX: parseInt(coords[1]),
                    radiusY: parseInt(coords[2])
                });

            hotspot.id = hotspotInfo.iid;
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
        PolygonModel.decode = function (hotspotInfo) {
            var pairedPoints = hotspotInfo.coord.split('|'),
                points = [];

            $.each(pairedPoints, function (index, pair) {
                var point = pair.split(';');

                points.push([
                    parseInt(point[0]),
                    parseInt(point[1])
                ]);
            });

            var hotspot = new PolygonModel({
                points: points
            });
            hotspot.id = hotspotInfo.iid;
            hotspot.name = hotspotInfo.answer;

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

        var AnswerModel = function (attributes) {
            this.attributes = attributes;
            this.changeEvent = null;
        };
        AnswerModel.prototype.set = function (key, value) {
            this.attributes[key] = value;

            if (this.changeEvent) {
                this.changeEvent(this);
            }
        };
        AnswerModel.prototype.get = function (key) {
            return this.attributes[key];
        };
        AnswerModel.prototype.onChange = function (callback) {
            this.changeEvent = callback;
        };
        AnswerModel.decode = function (answerInfo) {
            var coords = answerInfo.split(';');

            return new AnswerModel({
                x: coords[0],
                y: coords[1]
            });
        };

        var AnswersCollection = function () {
            this.models = [];
            this.length = 0;
            this.addEvent = null;
        };
        AnswersCollection.prototype.add = function (answerModel) {
            this.models.push(answerModel);
            this.length++;

            if (this.addEvent) {
                this.addEvent(answerModel);
            }
        };
        AnswersCollection.prototype.get = function (index) {
            return this.models[index];
        };
        AnswersCollection.prototype.onAdd = function (callback) {
            this.addEvent = callback;
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

        var AnswerSVG = function (answerModel, index) {
            var self = this;

            this.model = answerModel;
            this.answerIndex = index;

            this.circleEl = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            this.textEl = document.createElementNS('http://www.w3.org/2000/svg', 'text');

            this.model.onChange(function (answerModel) {
                self.render();
            });
        };
        AnswerSVG.prototype.render = function () {
            this.circleEl.setAttribute('cx', this.model.get('x'));
            this.circleEl.setAttribute('cy', this.model.get('y'));
            this.circleEl.setAttribute('r', 15);
            this.circleEl.setAttribute('class', 'hotspot-answer-point');

            this.textEl.setAttribute('x', this.model.get('x'));
            this.textEl.setAttribute('y', this.model.get('y'));
            this.textEl.setAttribute('dy', 5);
            this.textEl.setAttribute('text-anchor', 'middle');
            this.textEl.setAttribute('class', 'hotspot-answer-text');
            this.textEl.textContent = this.answerIndex + 1;

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
            <div class="input-group hotspot-' + this.hotspotIndex + ' active">\n\
                <span class="input-group-addon" id="hotspot-' + this.hotspotIndex + '">\n\
                    <span class="fa fa-square fa-fw" data-hidden="true"></span>\n\
                    <span class="sr-only">' + (this.hotspotSVG.model.name ? this.hotspotSVG.model.name : 'hotspot ' + this.hotspotIndex) + '</span>\n\
                </span>\n\
                <select class="form-control" aria-describedby="hotspot-' + this.hotspotIndex + '">\n\
                    <option value="square">' + lang.Square + '</option>\n\
                    <option value="ellipse">' + lang.Ellipse + '</option>\n\
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
                            $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Other);
                            break;

                        case 'ellipse':
                            newHotspot = new EllipseModel({
                                centerX: 0,
                                centerY: 0,
                                radiusX: 0,
                                radiusY: 0
                            });
                            $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Other);
                            break;

                        case 'polygon':
                            newHotspot = new PolygonModel({
                                points: []
                            });
                            $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Polygon);
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

                    switch (this.value) {
                        case 'square':
                            $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Other);
                            break;

                        case 'ellipse':
                            $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Other);
                            break;

                        case 'polygon':
                            $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Polygon);
                            break;
                    }
                })
                .val(function () {
                    if (self.hotspotSVG.model instanceof SquareModel) {
                        $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Other);

                        return 'square';
                    }

                    if (self.hotspotSVG.model instanceof EllipseModel) {
                        $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Other);

                        return 'ellipse';
                    }

                    if (self.hotspotSVG.model instanceof PolygonModel) {
                        $('#hotspot-messages span:not(.fa)').text(lang.HotspotStatus2Polygon);

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
                    <a href="#">' + lang.ClosePolygon + '</a>\n\
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

        var AdminHotspotsSVG = function (hotspotsCollection, image) {
            var self = this;

            this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            this.collection = hotspotsCollection;
            this.image = image;

            this.collection.onAdd(function (hotspotModel) {
                self.renderHotspot(hotspotModel);
            });
        };
        AdminHotspotsSVG.prototype.render = function () {
            this.el.setAttribute('version', '1.1');
            this.el.setAttribute('viewBox', '0 0 ' + this.image.width + ' ' + this.image.height);
            this.el.setAttribute('width', this.image.width);
            this.el.setAttribute('height', this.image.height);

            var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'href', this.image.src);
            imageSvg.setAttribute('width', this.image.width);
            imageSvg.setAttribute('height', this.image.height);

            this.el.appendChild(imageSvg);

            this.setEvents();

            return this;
        };
        AdminHotspotsSVG.prototype.renderHotspot = function (hotspot) {
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
        AdminHotspotsSVG.prototype.setEvents = function () {
            var self = this,
                $el = $(this.el);
            isDrawing = false;

            var startPoint = {
                x: 0,
                y: 0
            };

            $el
                .on('dragstart', function (e) {
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

                    startPoint = getPointOnImage(self.el, e.clientX, e.clientY);

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
                        currentPoint = getPointOnImage(self.el, e.clientX, e.clientY);

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
                        currentPoint = getPointOnImage(self.el, e.clientX, e.clientY);

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

                    var currentPoint = getPointOnImage(self.el, e.clientX, e.clientY),
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
                $(config.selector).html('');

                var hotspotsCollection = new HotspotsCollection(),
                    hotspotsSVG = new AdminHotspotsSVG(hotspotsCollection, this);

                $(config.selector).css('width', this.width).append(hotspotsSVG.render().el);
                $(config.selector).parent().prepend('\n\
                    <div id="hotspot-messages" class="alert alert-info">\n\
                        <h4><span class="fa fa-info-circle" aria-hidden="true"></span> ' + lang.HotspotStatus1 + '</h4>\n\
                        <span></span>\n\
                    </div>\n\
                ');

                $(config.selector).parent().prepend('<div class="row"></div>');

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

        var UserHotspotsSVG = function (hotspotsCollection, answersCollection, image) {
            var self = this;

            this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            this.hotspotsCollection = hotspotsCollection;
            this.answersCollection = answersCollection;
            this.image = image;

            this.answersCollection.onAdd(function (answerModel) {
                self.renderAnswer(answerModel);
            });
        };
        UserHotspotsSVG.prototype.render = function () {
            this.el.setAttribute('version', '1.1');
            this.el.setAttribute('viewBox', '0 0 ' + this.image.width + ' ' + this.image.height);
            this.el.setAttribute('width', this.image.width);
            this.el.setAttribute('height', this.image.height);

            var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'href', this.image.src);
            imageSvg.setAttribute('width', this.image.width);
            imageSvg.setAttribute('height', this.image.height);

            this.el.appendChild(imageSvg);

            this.setEvents();

            return this;
        };
        UserHotspotsSVG.prototype.renderAnswer = function (answerModel) {
            var answerSVG = new AnswerSVG(answerModel, this.answersCollection.length - 1);
            answerSVG.render();

            this.el.appendChild(answerSVG.circleEl);
            this.el.appendChild(answerSVG.textEl);

            var hotspot = this.hotspotsCollection.get(this.answersCollection.length - 1),
                x = answerModel.get('x'),
                y = answerModel.get('y');

            $('<input>', {
                type: 'hidden',
                name: 'hotspot[' + config.questionId + '][' + hotspot.id + ']'
            }).val(function () {
                return [x, y].join(';');
            }).appendTo(this.el.parentNode);

            $('<input>', {
                type: 'hidden',
                name: 'choice[' + config.questionId + '][' + hotspot.id + ']'
            }).val(function () {
                return [x, y].join(';');
            }).appendTo(this.el.parentNode);
        };
        UserHotspotsSVG.prototype.setEvents = function () {
            var self = this,
                $el = $(this.el);

            var isMoving = false,
                answerIndex = null,
                hotspot = null,
                point = {};

            $el
                .on('dragstart', function (e) {
                    e.preventDefault();
                })
                .on('click', function (e) {
                    e.preventDefault();

                    if (isMoving) {
                        return;
                    }

                    if (self.answersCollection.length >= self.hotspotsCollection.length) {
                        return;
                    }

                    var point = getPointOnImage(self.el, e.clientX, e.clientY);

                    var answerModel = new AnswerModel({
                        x: point.x,
                        y: point.y
                    });

                    self.answersCollection.add(answerModel);

                    if (self.answersCollection.length === self.hotspotsCollection.length) {
                        $(config.selector).parent()
                            .find('#hotspot-messages-' + config.questionId + ' span:not(.fa)').text(
                            lang.HotspotExerciseFinished
                        );

                        return;
                    }

                    $(config.selector).parent()
                        .find('#hotspot-messages-' + config.questionId + ' span:not(.fa)').text(
                        lang.NextAnswer +
                            ' ' +
                            self.hotspotsCollection
                                .get(self.answersCollection.length)
                                .name
                    );

                    isMoving = false;
                })
                .on('mousedown', 'circle, text', function (e) {
                    e.preventDefault();
                    isMoving = true;
                    if (e.target.tagName === 'circle') {
                        //Hack to move correctly the hot spots if there are more than one HS question in same page
                        answerIndex = $(e.target).next().html();
                        answerIndex = parseInt(answerIndex) - 1;
                    } else if (e.target.tagName === 'text') {
                        //Hack to move correctly the hot spots if there are more than one HS question in same page
                        answerIndex = $(e.target).html();
                        answerIndex = parseInt(answerIndex) - 1;
                    }

                    hotspot = self.hotspotsCollection.get(answerIndex);
                })
                .on('mousemove', function (e) {
                    if (!isMoving) {
                        return;
                    }

                    e.preventDefault();

                    point = getPointOnImage(self.el, e.clientX, e.clientY);

                    self.answersCollection.get(answerIndex).set('x', point.x);
                    self.answersCollection.get(answerIndex).set('y', point.y);
                })
                .on('mouseup', function (e) {
                    if (!isMoving) {
                        return;
                    }

                    e.preventDefault();

                    $('[name="hotspot[' + config.questionId + '][' + hotspot.id + ']"]').val(function () {
                        return [point.x, point.y].join(';');
                    });
                    $('[name="choice[' + config.questionId + '][' + hotspot.id + ']"]').val(function () {
                        return [point.x, point.y].join(';');
                    });

                    isMoving = false;
                });
        };

        var startHotspotsUser = function (questionInfo) {
            var image = new Image();
            image.onload = function () {
                $(config.selector).html('');

                var hotspotsCollection = new HotspotsCollection(),
                    answersCollection = new AnswersCollection(),
                    hotspotsSVG = new UserHotspotsSVG(hotspotsCollection, answersCollection, this);

                $(config.selector).css('width', this.width).append(hotspotsSVG.render().el);

                $(config.selector).parent().prepend('\n\
                    <div id="hotspot-messages-' + config.questionId + '" class="alert alert-info">\n\
                        <h4>\n\
                            <span class="fa fa-info-circle" aria-hidden="true"></span>\n\
                            <span></span>\n\
                        </h4>\n\
                    </div>\n\
                ');

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

                $.each(questionInfo.answers, function (index, answerInfo) {
                    var answerModel = new AnswerModel({
                        x: answerInfo.x,
                        y: answerInfo.y
                    });

                    answersCollection.add(answerModel);
                });

                $(config.selector).parent().find('#hotspot-messages-' + config.questionId + ' span:not(.fa)')
                    .text(
                        lang.NextAnswer + ' ' + hotspotsCollection.get(0).name
                    );
            };
            image.src = questionInfo.image;

            lang = questionInfo.lang;
        };

        var SolutionHotspotsSVG = function (hotspotsCollection, answersCollection, image) {
            this.hotspotsCollection = hotspotsCollection;
            this.answersCollection = answersCollection;
            this.image = image;
            this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

            var self = this;

            this.hotspotsCollection.onAdd(function (hotspotModel) {
                self.renderHotspot(hotspotModel);
            });

            this.answersCollection.onAdd(function (answerModel) {
                self.renderAnswer(answerModel);
            });
        };
        SolutionHotspotsSVG.prototype.render = function () {
            this.el.setAttribute('version', '1.1');
            this.el.setAttribute('viewBox', '0 0 ' + this.image.width + ' ' + this.image.height);
            this.el.setAttribute('width', this.image.width);
            this.el.setAttribute('height', this.image.height);

            var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'href', this.image.src);
            imageSvg.setAttribute('width', this.image.width);
            imageSvg.setAttribute('height', this.image.height);

            this.el.appendChild(imageSvg);

            return this;
        };
        SolutionHotspotsSVG.prototype.renderHotspot = function (hotspotModel) {
            var hotspotIndex = this.hotspotsCollection.length - 1,
                hotspotSVG = new HotspotSVG(hotspotModel, hotspotIndex);

            this.el.appendChild(
                hotspotSVG.render().el
            );

            return this;
        };
        SolutionHotspotsSVG.prototype.renderAnswer = function (answerModel) {
            var pointSVG = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            pointSVG.setAttribute('cx', answerModel.get('x'));
            pointSVG.setAttribute('cy', answerModel.get('y'));
            pointSVG.setAttribute('r', 15);
            pointSVG.setAttribute('class', 'hotspot-answer-point');

            var textSVG = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            textSVG.setAttribute('x', answerModel.get('x'));
            textSVG.setAttribute('y', answerModel.get('y'));
            textSVG.setAttribute('dy', 5);
            textSVG.setAttribute('font-family', 'sans-serif');
            textSVG.setAttribute('text-anchor', 'middle');
            textSVG.setAttribute('fill', 'white');
            textSVG.textContent = this.answersCollection.length;

            this.el.appendChild(pointSVG);
            this.el.appendChild(textSVG);

            return this;
        };

        var startHotspotsSolution = function (questionInfo) {
            var image = new Image();
            image.onload = function () {
                $(config.selector).html('');

                var hotspotsCollection = new HotspotsCollection(),
                    answersCollection = new AnswersCollection(),
                    hotspotsSVG = new SolutionHotspotsSVG(hotspotsCollection, answersCollection, this);

                $(config.selector).css('width', this.width).append(hotspotsSVG.render().el);

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

                $.each(questionInfo.answers, function (index, answerInfo) {
                    var answer = AnswerModel.decode(answerInfo);

                    answersCollection.add(answer);
                });
            };
            image.src = questionInfo.image;

            lang = questionInfo.lang;
        };

        var getPointOnImage = function (referenceElement, x, y) {
            var pointerPosition = {
                    left: x + window.scrollX,
                    top: y + window.scrollY
                },
                canvasOffset = {
                    x: referenceElement.getBoundingClientRect().left + window.scrollX,
                    y: referenceElement.getBoundingClientRect().top + window.scrollY
                };

            return {
                x: Math.round(pointerPosition.left - canvasOffset.x),
                y: Math.round(pointerPosition.top - canvasOffset.y)
            };
        };

        var config, lang, selectedHotspotIndex = 0, contextMenu;
        config = $.extend({
            questionId: 0,
            selector: ''
        }, settings);

        if (!config.questionId || !config.selector) {
            return;
        }

        $(config.selector).html('\n\
            <span class="fa fa-spinner fa-spin fa-3x" aria-hidden="hidden"></span>\n\
            <span class="sr-only">Loading</span>\n\
        ');

        var xhrQuestion = null;

        switch (config.for) {
            case 'admin':
                xhrQuestion = $.getJSON(config.relPath + 'exercise/hotspot_actionscript_admin.as.php?' + _p.web_cid_query, {
                    modifyAnswers: parseInt(config.questionId)
                });
                break;
            case 'user':
                xhrQuestion = $.getJSON(config.relPath + 'exercise/hotspot_actionscript.as.php?' + _p.web_cid_query, {
                    modifyAnswers: parseInt(config.questionId),
                    exe_id: parseInt(config.exerciseId)
                });
                break;
            case 'solution':
                //no break
            case 'preview':
                xhrQuestion = $.getJSON(config.relPath + 'exercise/hotspot_answers.as.php?' + _p.web_cid_query, {
                    modifyAnswers: parseInt(config.questionId),
                    exerciseId: parseInt(config.exerciseId),
                    exeId: parseInt(config.exeId)
                });
                break;
        }

        $.when(xhrQuestion).done(function (questionInfo) {
            switch (questionInfo.type) {
                case 'admin':
                    startHotspotsAdmin(questionInfo);
                    break;
                case 'user':
                    startHotspotsUser(questionInfo);
                    break;
                case 'solution':
                    // no break
                case 'preview':
                    startHotspotsSolution(questionInfo);
                    break;
            }
        });
    };
})();

window.DelineationQuestion = (function () {
    var PolygonModel = function (attributes) {
        this.id = 0;
        this.name = '';
        this.attributes = attributes;

        this.event = null;
    };
    PolygonModel.prototype.set = function (key, value) {
        this.attributes[key] = value;

        if (this.event) {
            this.event(this);
        }
    };
    PolygonModel.prototype.get = function (key) {
        if (!this.attributes[key]) {
            return;
        }

        return this.attributes[key];
    };
    PolygonModel.prototype.onChange = function (callback) {
        this.event = callback;
    };
    PolygonModel.prototype.encode = function () {
        var pairedPoints = [];

        $.each(this.get('points'), function (index, point) {
            pairedPoints.push(
                point.join(';')
            );
        });

        return pairedPoints.join('|');
    };
    PolygonModel.decode = function (hotspotInfo) {
        var pairedPoints = hotspotInfo.coord.split('|'),
            points = [];

        $.each(pairedPoints, function (index, pair) {
            var point = pair.split(';');

            points.push([
                parseInt(point[0]),
                point[1] ? parseInt(point[1]) : 0
            ]);
        });

        var hotspot = null;

        if (hotspotInfo.type === 'delineation') {
            hotspot = new DelineationModel({
                points: points
            });
        } else if (hotspotInfo.type === 'oar') {
            hotspot = new OarModel({
                points: points
            });
        }

        if (!hotspot) {
            return;
        }

        hotspot.id = hotspotInfo.id;
        hotspot.name = hotspotInfo.answer;

        return hotspot;
    };

    var DelineationModel = function (attributes) {
        PolygonModel.call(this, attributes);
    };
    DelineationModel.prototype = Object.create(PolygonModel.prototype);

    var OarModel = function (attributes) {
        PolygonModel.call(this, attributes);
    };
    OarModel.prototype = Object.create(PolygonModel.prototype);

    var AnswerModel = function (attributes) {
        PolygonModel.call(this, attributes);
    };
    AnswerModel.prototype = Object.create(PolygonModel.prototype);
    AnswerModel.prototype.encode = function () {
        var pairedPoints = [];

        $.each(this.get('points'), function (index, point) {
            pairedPoints.push(point.join(';'));
        });

        return pairedPoints.join('/');
    };

    var PolygonCollection = function () {
        this.models = [];
        this.length = 0;

        this.event = null;
    };
    PolygonCollection.prototype.add = function (model) {
        this.models.push(model);
        this.length++;

        if (this.event) {
            this.event(model);
        }
    };
    PolygonCollection.prototype.get = function (index) {
        return this.models[index];
    };
    PolygonCollection.prototype.set = function (index, model) {
        this.models[index] = model;
    };
    PolygonCollection.prototype.onAdd = function (callback) {
        this.event = callback;
    };

    var PolygonSvg = function (polygonModel) {
        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        this.model = polygonModel;
        this.model.onChange(function () {
            self.render();
        });

        var self = this,
            $el = $(this.el);

        this.render = function () {
            var newEl = document.createElementNS('http://www.w3.org/2000/svg', 'polygon'),
                pointsPaired = [];

            $.each(this.model.get('points'), function (index, point) {
                pointsPaired.push(point.join(','));
            });

            newEl.setAttributeNS(null, 'points', pointsPaired.join(' '));
            newEl.setAttributeNS(null, 'class', 'hotspot-' + this.model.id);

            if ($el.parent().length > 0) {
                $el.replaceWith(newEl);
            }

            if (this.el.parentNode) {
                this.el.parentNode.replaceChild(newEl, this.el);
            }

            this.el = newEl;

            return this;
        };
    };

    var HotspotSelect = function (polygonModel) {
        this.el = $('<div>').addClass('col-xs-6 col-sm-4 col-md-3 col-lg-2').get(0);
        this.model = polygonModel;

        selectedPolygonIndex = this.model.id;

        var self = this,
            $el = $(this.el);

        this.render = function () {
            var type = this.model instanceof OarModel ? 'oar' : 'delineation';

            var template = '\n\
                <div class="input-group hotspot-' + this.model.id + ' active">\n\
                    <span class="input-group-addon" id="hotspot-' + this.model.id + '">\n\
                        <span class="fa fa-square fa-fw" data-hidden="true"></span>\n\
                        <span class="sr-only">' + (type === 'delineation' ? lang.Delineation : lang.Oar) + '</span>\n\
                    </span>\n\
                    <select class="form-control" aria-describedby="hotspot-' + this.hotspotIndex + '">\n\
                        <option selected>' + (type === 'delineation' ? lang.Delineation : lang.Oar) + '</option>\n\
                    </select>\n\
                </div>\n\
            ';

            $el.html(template);

            $el.find('select')
                .on('focus', function () {
                    $('.input-group').removeClass('active');

                    $el.find('.input-group').addClass('active');

                    selectedPolygonIndex = self.model.id;
                });

            return this;
        };
    };

    var ContextMenu = function () {
        this.el = $('<ul>', {
            id: 'hotspot-context-menu'
        }).addClass('dropdown-menu').get(0);

        var self = this,
            $el = $(this.el);

        this.onHide = function (callback) {
            $(this).on('hide', function () {
                callback();
            });
        };

        this.render = function () {
            var template = '\n\
                <li>\n\
                    <a href="#">' + lang.CloseDelineation + '</a>\n\
                </li>\n\
            ';

            $el.html(template);

            $el.find('a')
                .on('click', function (e) {
                    e.preventDefault();

                    $(self).trigger('hide');

                    $el.hide();
                });

            return this;
        };

        this.show = function (x, y) {
            $el.css({
                left: x,
                top: y
            }).show();
        };
    };

    var AdminSvg = function (polygonCollection, image) {
        this.collection = polygonCollection;
        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

        var self = this,
            $el = $(this.el);

        this.collection.onAdd(function (polygonModel) {
            self.renderPolygon(polygonModel);
        });

        this.render = function () {
            var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', image.src);
            imageSvg.setAttribute('width', image.width);
            imageSvg.setAttribute('height', image.height);

            this.el.setAttribute('version', '1.1');
            this.el.setAttribute('viewBox', '0 0 ' + image.width + ' ' + image.height);
            this.el.setAttribute('width', image.width);
            this.el.setAttribute('height', image.height);
            this.el.appendChild(imageSvg);

            var isDrawing = false;

            var contextMenu = new ContextMenu();
            contextMenu.onHide(function () {
                var currentHotspot = self.collection.get(selectedPolygonIndex);

                $('[name="hotspot_coordinates[' + (currentHotspot.id + 1) + ']"]').val(
                    currentHotspot.encode()
                );

                isDrawing = false;
            });

            $el.on({
                'dragstart': function (e) {
                    e.preventDefault();
                },
                'click': function (e) {
                    e.preventDefault();

                    var currentPoint = getPointOnImage(self.el, e.clientX, e.clientY),
                        points = [];

                    if (!isDrawing) {
                        isDrawing = true;
                    } else {
                        points = self.collection.get(selectedPolygonIndex).get('points');
                    }

                    points.push([currentPoint.x, currentPoint.y]);

                    self.collection.get(selectedPolygonIndex).set('points', points);
                },
                'contextmenu': function (e) {
                    e.preventDefault();

                    if (!contextMenu.el.parentNode) {
                        $el.parent().append(contextMenu.render().el);
                    }

                    var currentPoint = getPointOnImage(self.el, e.clientX, e.clientY);

                    contextMenu.show(currentPoint.x, currentPoint.y);
                }
            });

            return this;
        };

        this.renderPolygon = function (oarModel) {
            var oarSVG = new PolygonSvg(oarModel);

            $el.append(oarSVG.render().el);

            var oarSelect = new HotspotSelect(oarModel);

            $el.parent().parent().find('.row').append(
                oarSelect.render().el
            );

            return this;
        };
    };

    var startAdminSvg = function (questionInfo) {
        var image = new Image();
        image.onload = function () {
            $(config.selector).html('');

            var polygonCollection = new PolygonCollection(),
                adminSvg = new AdminSvg(polygonCollection, image);

            $(config.selector)
                .css('width', this.width)
                .append(
                    adminSvg.render().el
                );

            $(config.selector).parent().prepend('\n\
                <div id="delineation-messages" class="alert alert-info">\n\
                    <h4>\n\
                        <span class="fa fa-info-circle" aria-hidden="true"></span>\n\
                        <span>' + lang.DelineationStatus1 + '</span>\n\
                    </h4>\n\
                </div>\n\
            ');

            $(config.selector).parent().prepend('<div class="row"></div>');

            $.each(questionInfo.hotspots, function (index, hotspotInfo) {
                $('.input-group').removeClass('active');

                var polygonModel = PolygonModel.decode(hotspotInfo);
                polygonModel.id = index;

                polygonCollection.add(polygonModel);
            });
        };
        image.src = questionInfo.image;

        lang = questionInfo.lang;
    };

    var UserSvg = function (answerModel, image) {
        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.model = answerModel;

        var self = this,
            $el = $(this.el);

        this.render = function () {
            var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', image.src);
            imageSvg.setAttribute('width', image.width);
            imageSvg.setAttribute('height', image.height);

            this.el.setAttribute('version', '1.1');
            this.el.setAttribute('viewBox', '0 0 ' + image.width + ' ' + image.height);
            this.el.setAttribute('width', image.width);
            this.el.setAttribute('height', image.height);
            this.el.appendChild(imageSvg);

            this.renderDelineation();

            var isDrawing = false;

            var contextMenu = new ContextMenu();
            contextMenu.onHide(function () {
                var answerInput = $('hotspot[' + config.questionId + '][1]'),
                    choiceInput = $('choice[' + config.questionId + '][1]');

                if (!answerInput.length) {
                    answerInput = $('<input>', {
                        type: 'hidden',
                        name: 'hotspot[' + config.questionId + '][1]'
                    }).insertAfter($el);
                }

                if (!choiceInput.length) {
                    choiceInput = $('<input>', {
                        type: 'hidden',
                        name: 'choice[' + config.questionId + '][1]'
                    }).insertAfter($el);
                }

                answerInput.val(self.model.encode());
                choiceInput.val(1);

                isDrawing = false;
            });

            $el.on({
                'dragstart': function (e) {
                    e.preventDefault();
                },
                'click': function (e) {
                    e.preventDefault();

                    var currentPoint = getPointOnImage(self.el, e.clientX, e.clientY),
                        points = [];

                    if (!isDrawing) {
                        isDrawing = true;
                    } else {
                        points = self.model.get('points');
                    }

                    points.push([currentPoint.x, currentPoint.y]);

                    self.model.set('points', points);
                },
                'contextmenu': function (e) {
                    e.preventDefault();

                    if (!contextMenu.el.parentNode) {
                        $el.parent().append(contextMenu.render().el);
                    }

                    var currentPoint = getPointOnImage(self.el, e.clientX, e.clientY);

                    contextMenu.show(currentPoint.x, currentPoint.y);
                }
            });

            return this;
        };

        this.renderDelineation = function () {
            var delineationSvg = new PolygonSvg(this.model);

            $el.append(
                delineationSvg.render().el
            );
        };
    };

    var startUserSvg = function (questionInfo) {
        var image = new Image();
        image.onload = function () {
            $(config.selector).html('');

            var answerModel = new AnswerModel({
                    points: []
                }),
                userSvg = new UserSvg(answerModel, image);

            $(config.selector).parent().prepend('\n\
                <div id="delineation-messages" class="alert alert-info">\n\
                    <h4>\n\
                        <span class="fa fa-info-circle" aria-hidden="true"></span>\n\
                        <span>' + lang.DelineationStatus1 + '</span>\n\
                    </h4>\n\
                </div>\n\
            ');

            $(config.selector)
                .css('width', this.width)
                .append(
                    userSvg.render().el
                );
        };
        image.src = questionInfo.image;

        lang = questionInfo.lang;
    };

    var PreviewSVG = function (polygonCollection, image) {
        this.collection = polygonCollection;
        this.image = image;
        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

        var self = this,
            $el = $(this.el);

        this.collection.onAdd(function (polygonModel) {
            self.renderPolygon(polygonModel);
        });

        this.render = function () {
            var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', this.image.src);
            imageSvg.setAttribute('width', this.image.width);
            imageSvg.setAttribute('height', this.image.height);

            this.el.setAttribute('version', '1.1');
            this.el.setAttribute('viewBox', '0 0 ' + this.image.width + ' ' + this.image.height);
            this.el.setAttribute('width', this.image.width);
            this.el.setAttribute('height', this.image.height);
            this.el.appendChild(imageSvg);

            return this;
        };

        this.renderPolygon = function (oarModel) {
            var oarSVG = new PolygonSvg(oarModel);

            $el.append(oarSVG.render().el);

            return this;
        };
    };

    var startPreviewSvg = function (questionInfo) {
        var image = new Image();
        image.onload = function () {
            $(config.selector).html('');

            var polygonCollection = new PolygonCollection(),
                previewSvg = new PreviewSVG(polygonCollection, image);

            $(config.selector)
                .css('width', this.width)
                .append(
                    previewSvg.render().el
                );

            $.each(questionInfo.hotspots, function (index, hotspotInfo) {
                var polygonModel = PolygonModel.decode(hotspotInfo);
                polygonModel.id = index;

                polygonCollection.add(polygonModel);
            });
        };
        image.src = questionInfo.image;

        lang = questionInfo.lang;
    };

    var config = {
            questionId: 0,
            exerciseId: 0,
            selector: null,
            for: ''
        },
        lang = {},
        selectedPolygonIndex = -1;

    var getPointOnImage = function (referenceElement, x, y) {
        var pointerPosition = {
                left: x + window.scrollX,
                top: y + window.scrollY
            },
            canvasOffset = {
                x: referenceElement.getBoundingClientRect().left + window.scrollX,
                y: referenceElement.getBoundingClientRect().top + window.scrollY
            };

        return {
            x: Math.round(pointerPosition.left - canvasOffset.x),
            y: Math.round(pointerPosition.top - canvasOffset.y)
        };
    };

    return function (settings) {
        config = $.extend({
            questionId: 0,
            selector: ''
        }, settings);

        if (!config.questionId || !config.selector) {
            return;
        }

        $(config.selector).html('\n\
            <span class="fa fa-spinner fa-spin fa-3x" aria-hidden="hidden"></span>\n\
            <span class="sr-only">Loading</span>\n\
        ');

        var xhrQuestion = null;

        switch (config.for) {
            case 'admin':
                xhrQuestion = $.getJSON(config.relPath + 'exercise/hotspot_actionscript_admin.as.php?' + _p.web_cid_query, {
                    modifyAnswers: parseInt(config.questionId)
                });
                break;
            case 'user':
                xhrQuestion = $.getJSON(config.relPath + 'exercise/hotspot_actionscript.as.php?' + _p.web_cid_query, {
                    modifyAnswers: parseInt(config.questionId),
                    exe_id: parseInt(config.exerciseId)
                });
                break;
            case 'solution':
                // no break
            case 'preview':
                xhrQuestion = $.getJSON(config.relPath + 'exercise/hotspot_answers.as.php?' + _p.web_cid_query, {
                    modifyAnswers: parseInt(config.questionId),
                    exerciseId: parseInt(config.exerciseId),
                    exeId: parseInt(config.exeId)
                });
                break;
        }

        $.when(xhrQuestion).done(function (questionInfo) {
            switch (questionInfo.type) {
                case 'admin':
                    startAdminSvg(questionInfo);
                    break;
                case 'user':
                    startUserSvg(questionInfo);
                    break;
                case 'solution':
                    // no break
                case 'preview':
                    startPreviewSvg(questionInfo);
                    break;
            }
        });
    };
})();
