/* For licensing terms, see /license.txt */

(function (window) {
    /**
     * @param referenceElement Element to get the point
     * @param x MouseEvent's clientX
     * @param y MouseEvent's clientY
     * @returns {{x: number, y: number}}
     */
    function getPointOnImage(referenceElement, x, y) {
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

    /**
     * @param Object attributes
     * @constructor
     */
    var SvgElementModel = function (attributes) {
        this.attributes = attributes;
        this.id = 0;
        this.name = '';

        this.changeEvent = null;
    };
    SvgElementModel.prototype.set = function (key, value) {
        this.attributes[key] = value;

        if (this.changeEvent) {
            this.changeEvent(this);
        }
    };
    SvgElementModel.prototype.get = function (key) {
        return this.attributes[key];
    };
    SvgElementModel.prototype.onChange = function (callback) {
        this.changeEvent = callback;
    };
    SvgElementModel.decode = function () {
        return new this;
    };
    SvgElementModel.prototype.encode = function () {
        return '';
    };

    /**
     * @param Object attributes
     * @constructor
     */
    var SvgPathModel = function (attributes) {
        SvgElementModel.call(this, attributes);
    };
    SvgPathModel.prototype = Object.create(SvgElementModel.prototype);
    SvgPathModel.prototype.addPoint = function (x, y) {
        x = parseInt(x);
        y = parseInt(y);

        var points = this.get('points');
        points.push([x, y]);

        this.set('points', points);
    };
    SvgPathModel.prototype.encode = function () {
        var pairedPoints = [];

        this.get('points').forEach(function (point) {
            pairedPoints.push(
                point.join(';')
            );
        });

        return 'P)(' + pairedPoints.join(')(');
    };
    SvgPathModel.decode = function (pathInfo) {
        var points = [];

        $(pathInfo).each(function (i, point) {
            points.push([point.x, point.y]);
        });

        return new SvgPathModel({points: points});
    };

    /**
     * @param Object model
     * @constructor
     */
    var SvgPathView = function (model) {
        var self = this;

        this.model = model;
        this.model.onChange(function () {
            self.render();
        });

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        this.el.setAttribute('fill', 'transparent');
        this.el.setAttribute('stroke', 'red');
        this.el.setAttribute('stroke-width', 3);
    };
    SvgPathView.prototype.render = function () {
        var d = '',
            points = this.model.get('points');

        $.each(
            this.model.get('points'),
            function (i, point) {
                d += (i === 0) ? 'M' : ' L ';
                d += point[0] + ' ' + point[1];
            }
        );

        this.el.setAttribute('d', d);

        return this;
    };

    /**
     * @constructor
     */
    var PathsCollection = function () {
        this.models = [];
        this.length = 0;
        this.addEvent = null;
    };
    PathsCollection.prototype.add = function (pathModel) {
        pathModel.id = ++this.length;

        this.models.push(pathModel);

        if (this.addEvent) {
            this.addEvent(pathModel);
        }
    };
    PathsCollection.prototype.get = function (index) {
        return this.models[index];
    };
    PathsCollection.prototype.onAdd = function (callback) {
        this.addEvent = callback;
    };

    /**
     * @param pathsCollection
     * @param image
     * @param questionId
     * @constructor
     */
    var AnnotationCanvasView = function (pathsCollection, image, questionId) {
        var self = this;

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.$el = $(this.el);

        this.questionId = parseInt(questionId);

        this.image = image;

        this.pathsCollection = pathsCollection;
        this.pathsCollection.onAdd(function (pathModel) {
            self.renderPath(pathModel);
        });
    };
    AnnotationCanvasView.prototype.render = function () {
        this.el.setAttribute('version', '1.1');
        this.el.setAttribute('viewBox', '0 0 ' + this.image.width + ' ' + this.image.height);

        var svgImage = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        svgImage.setAttributeNS('http://www.w3.org/1999/xlink', 'href', this.image.src);
        svgImage.setAttribute('width', this.image.width);
        svgImage.setAttribute('height', this.image.height);

        this.el.appendChild(svgImage);
        this.setEvents();

        return this;
    };
    AnnotationCanvasView.prototype.setEvents = function () {
        var self = this;

        var isMoving = false,
            pathModel = null;

        self.$el
            .on('dragstart', function (e) {
                e.preventDefault();
            })
            .on('mousedown', function (e) {
                e.preventDefault();

                var point = getPointOnImage(self.el, e.clientX, e.clientY);

                pathModel = new SvgPathModel({points: [[point.x, point.y]]});

                self.pathsCollection.add(pathModel);

                isMoving = true;
            })
            .on('mousemove', function (e) {
                e.preventDefault();

                if (!isMoving) {
                    return;
                }

                var point = getPointOnImage(self.el, e.clientX, e.clientY);

                if (!pathModel) {
                    return;
                }

                pathModel.addPoint(point.x, point.y);
            })
            .on('mouseup', function (e) {
                e.preventDefault();

                if (!isMoving) {
                    return;
                }

                $('input[name="choice[' + self.questionId + '][' + pathModel.id + ']"]').val(pathModel.encode());
                $('input[name="hotspot[' + self.questionId + '][' + pathModel.id + ']"]').val(pathModel.encode());

                pathModel = null;

                isMoving = false;
            });
    };
    AnnotationCanvasView.prototype.renderPath = function (pathModel) {
        var pathView = new SvgPathView(pathModel);

        this.el.appendChild(pathView.render().el);

        $('<input>')
            .attr({
                type: 'hidden',
                name: 'choice[' + this.questionId + '][' + pathModel.id + ']'
            })
            .val(pathModel.encode())
            .appendTo(this.el.parentNode);

        $('<input>')
            .attr({
                type: 'hidden',
                name: 'hotspot[' + this.questionId + '][' + pathModel.id + ']'
            })
            .val(pathModel.encode())
            .appendTo(this.el.parentNode);
    };

    window.AnnotationQuestion = function (userSettings) {
        var settings = $.extend({
            questionId: 0,
            exerciseId: 0,
            relPath: '/',
            use: 'user'
        }, userSettings);

        var xhrUrl = (settings.use == 'preview')
            ? 'exercise/annotation_preview.php'
            : (settings.use == 'admin')
                ? 'exercise/annotation_admin.php'
                : 'exercise/annotation_user.php';

        $
            .getJSON(settings.relPath + xhrUrl, {
                question_id: parseInt(settings.questionId),
                exe_id: parseInt(settings.exerciseId)
            })
            .done(function (questionInfo) {
                var image = new Image();
                image.onload = function () {
                    var pathsCollection = new PathsCollection(),
                        canvas = new AnnotationCanvasView(pathsCollection, this, settings.questionId);

                    $('#annotation-canvas-' + settings.questionId)
                        .css({width: this.width})
                        .html(canvas.render().el);

                    $.each(questionInfo.answers.paths, function (i, pathInfo) {
                        var pathModel = SvgPathModel.decode(pathInfo);

                        pathsCollection.add(pathModel);
                    });
                };
                image.src = questionInfo.image.path;
            });
    };
})(window);
