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
     * @param userAttributes
     * @constructor
     */
    var TextModel = function (userAttributes) {
        var attributes = $.extend({
            text: '',
            x: 0,
            y: 0,
            color: 'red',
            fontSize: 20
        }, userAttributes);

        SvgElementModel.call(this, attributes);
    };
    TextModel.prototype = Object.create(SvgElementModel.prototype);
    TextModel.prototype.encode = function () {
        return 'T)(' + this.get('text') + ')(' + this.get('x') + ';' + this.get('y');
    };
    TextModel.decode = function (textInfo) {
        return new TextModel({
            text: textInfo.text,
            x: textInfo.x,
            y: textInfo.y
        })
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
     * @param model
     * @constructor
     */
    var TextView = function (model) {
        var self = this;

        this.model = model;
        this.model.onChange(function () {
            self.render();
        });

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        this.el.setAttribute('fill', this.model.get('color'));
        this.el.setAttribute('font-size', this.model.get('fontSize'));
        this.el.setAttribute('stroke', 'none');
    };
    TextView.prototype.render = function () {
        this.el.setAttribute('x', this.model.get('x'));
        this.el.setAttribute('y', this.model.get('y'));
        this.el.textContent = this.model.get('text');

        return this;
    };

    /**
     * @constructor
     */
    var ElementsCollection = function () {
        this.models = [];
        this.length = 0;
        this.addEvent = null;
    };
    ElementsCollection.prototype.add = function (pathModel) {
        pathModel.id = ++this.length;

        this.models.push(pathModel);

        if (this.addEvent) {
            this.addEvent(pathModel);
        }
    };
    ElementsCollection.prototype.get = function (index) {
        return this.models[index];
    };
    ElementsCollection.prototype.onAdd = function (callback) {
        this.addEvent = callback;
    };

    /**
     * @param elementsCollection
     * @param image
     * @param questionId
     * @constructor
     */
    var AnnotationCanvasView = function (elementsCollection, image, questionId)   {
        var self = this;

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.$el = $(this.el);

        this.questionId = parseInt(questionId);

        this.image = image;

        this.elementsCollection = elementsCollection;
        this.elementsCollection.onAdd(function (pathModel) {
            self.renderElement(pathModel);
        });

        this.$rdbOptions = null;
    };
    AnnotationCanvasView.prototype.render = function () {
        this.el.setAttribute('version', '1.1');
        this.el.setAttribute('viewBox', '0 0 ' + this.image.width + ' ' + this.image.height);
        this.el.style.width = this.image.width + 'px';
        this.el.style.height = this.image.height + 'px';

        var svgImage = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        svgImage.setAttributeNS('http://www.w3.org/1999/xlink', 'href', this.image.src);
        svgImage.setAttribute('width', this.image.width);
        svgImage.setAttribute('height', this.image.height);

        this.el.appendChild(svgImage);
        this.setEvents();

        this.$rdbOptions = $('[name="' + this.questionId + '-options"]');

        return this;
    };
    AnnotationCanvasView.prototype.setEvents = function () {
        var self = this;

        var isMoving = false,
            elementModel = null;

        self.$el
            .on('dragstart', function (e) {
                e.preventDefault();
            })
            .on('mousedown', function (e) {
                e.preventDefault();

                var point = getPointOnImage(self.el, e.clientX, e.clientY);

                switch (self.$rdbOptions.filter(':checked').val()) {
                    case '0':
                        elementModel = new SvgPathModel({points: [[point.x, point.y]]});
                        break;
                    case '1':
                        elementModel = new TextModel({x: point.x, y: point.y, text: 'Hola, mundo'});
                        break;
                }

                self.elementsCollection.add(elementModel);

                isMoving = true;
            })
            .on('mousemove', function (e) {
                e.preventDefault();

                if (!isMoving) {
                    return;
                }

                if (!elementModel) {
                    return;
                }

                var point = getPointOnImage(self.el, e.clientX, e.clientY);

                if (elementModel instanceof SvgPathModel) {
                    elementModel.addPoint(point.x, point.y);
                } else if (elementModel instanceof TextModel) {
                    elementModel.set('x', point.x);
                    elementModel.set('y', point.y);
                }
            })
            .on('mouseup', function (e) {
                e.preventDefault();

                if (!isMoving) {
                    return;
                }

                $('input[name="choice[' + self.questionId + '][' + elementModel.id + ']"]').val(elementModel.encode());
                $('input[name="hotspot[' + self.questionId + '][' + elementModel.id + ']"]').val(elementModel.encode());

                elementModel = null;

                isMoving = false;
            });
    };
    AnnotationCanvasView.prototype.renderElement = function (elementModel) {
        var elementView = null,
            self = this;

        if (elementModel instanceof SvgPathModel) {
            elementView = new SvgPathView(elementModel);
        } else if (elementModel instanceof TextModel) {
            elementView = new TextView(elementModel);
        }

        if (!elementView) {
            return;
        }

        this.el.appendChild(elementView.render().el);

        $('<input>')
            .attr({
                type: 'hidden',
                name: 'choice[' + this.questionId + '][' + elementModel.id + ']'
            })
            .val(elementModel.encode())
            .appendTo(this.el.parentNode);

        $('<input>')
            .attr({
                type: 'hidden',
                name: 'hotspot[' + this.questionId + '][' + elementModel.id + ']'
            })
            .val(elementModel.encode())
            .appendTo(this.el.parentNode);

        if (elementModel instanceof TextModel) {
            $('<input>')
                .attr({
                    type: 'text',
                    name: 'text[' + this.questionId + '][' + elementModel.id + ']'
                })
                .addClass('form-control input-sm')
                .on('change', function (e) {
                    elementModel.set('text', this.value);

                    $('input[name="choice[' + self.questionId + '][' + elementModel.id + ']"]').val(elementModel.encode());
                    $('input[name="hotspot[' + self.questionId + '][' + elementModel.id + ']"]').val(elementModel.encode());

                    e.preventDefault();
                })
                .val(elementModel.get('text'))
                .appendTo('#annotation-toolbar-' + this.questionId + ' ul')
                .wrap('<li class="form-group">');
        }
    };

    window.AnnotationQuestion = function (userSettings) {
        $(document).on('ready', function () {
        var
            settings = $.extend(
                {questionId: 0, exerciseId: 0, relPath: '/', use: 'user'},
                userSettings
            ),
            xhrUrl = (settings.use == 'preview')
                ? 'exercise/annotation_preview.php'
                : (settings.use == 'admin')
                    ? 'exercise/annotation_admin.php'
                    : 'exercise/annotation_user.php',
            $container = $('#annotation-canvas-' + settings.questionId);

        $
            .getJSON(settings.relPath + xhrUrl, {
                question_id: parseInt(settings.questionId),
                exe_id: parseInt(settings.exerciseId)
            })
            .done(function (questionInfo) {
                var image = new Image();
                image.onload = function () {
                    var elementsCollection = new ElementsCollection(),
                        canvas = new AnnotationCanvasView(elementsCollection, this, settings.questionId);

                    $container
                        .html(canvas.render().el);

                    $.each(questionInfo.answers.paths, function (i, pathInfo) {
                        var pathModel = SvgPathModel.decode(pathInfo);

                        elementsCollection.add(pathModel);
                    });

                    $(questionInfo.answers.texts).each(function (i, textInfo) {
                        var textModel = TextModel.decode(textInfo);

                        elementsCollection.add(textModel);
                    });
                };
                image.src = questionInfo.image.path;
            });

        });
    };
})(window);
