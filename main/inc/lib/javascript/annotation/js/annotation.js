/* For licensing terms, see /license.txt */

(function (window, $) {
    "use strict";

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
    }

    /**
     * @param {Object} attributes
     * @constructor
     */
    var SvgElementModel = function (attributes) {
        this.attributes = attributes;
        this.id = 0;
        this.questionId = 0;

        this.changeEvents = [];
        this.destroyEvents = [];
    };
    /**
     * @param {string} key
     * @param {*} value
     */
    SvgElementModel.prototype.set = function (key, value) {
        this.attributes[key] = value;

        this.changeEvents.forEach(function (event) {
            event();
        });
    };
    SvgElementModel.prototype.get = function (key) {
        return this.attributes[key];
    };
    SvgElementModel.prototype.destroy = function () {
        this.destroyEvents.forEach(function (event) {
            event();
        });
    };
    /**
     * @param {string} eventName
     * @param {(SvgElementModel~changeEvents|SvgElementModel~destroyEvents)} callback
     */
    SvgElementModel.prototype.on = function (eventName, callback) {
        this[eventName + 'Events'].push(callback);
    };
    /**
     * @abstract
     * @static
     * @param {Object} info
     * @returns {SvgElementModel}
     */
    SvgElementModel.decode = function (info) {
        return new this();
    };
    /**
     * @abstract
     * @returns {string}
     */
    SvgElementModel.prototype.encode = function () {
        return "";
    };

    /**
     * @param {Object} userAttributes
     * @constructor
     * @extends SvgElementModel
     */
    var SvgPathModel = function (userAttributes) {
        var attributes = $.extend({
            color: "#FF0000",
            points: []
        }, userAttributes);

        SvgElementModel.call(this, attributes);
    };
    SvgPathModel.prototype = Object.create(SvgElementModel.prototype);
    SvgPathModel.prototype.addPoint = function (x, y) {
        x = parseInt(x);
        y = parseInt(y);

        var points = this.get("points");
        points.push([x, y]);

        this.set("points", points);
    };
    SvgPathModel.prototype.encode = function () {
        var pairedPoints = [];
        var typeProperties = [
            this.get("color"),
        ];

        this.get("points").forEach(function (point) {
            pairedPoints.push(
                point.join(";")
            );
        });

        return "P;" + typeProperties.join(";") + ")(" + pairedPoints.join(")(");
    };
    /**
     * @static
     * @param {Object} pathInfo
     * @returns {SvgPathModel}
     */
    SvgPathModel.decode = function (pathInfo) {
        pathInfo.points = pathInfo.points.map(function (point) {
            return [point.x, point.y];
        });

        return new SvgPathModel(pathInfo);
    };

    /**
     * @param {Object} userAttributes
     * @constructor
     * @extends SvgElementModel
     */
    var SvgTextModel = function (userAttributes) {
        var attributes = $.extend({
            text: "",
            x: 0,
            y: 0,
            color: "#FF0000",
            fontSize: 20
        }, userAttributes);

        SvgElementModel.call(this, attributes);
    };
    SvgTextModel.prototype = Object.create(SvgElementModel.prototype);
    SvgTextModel.prototype.encode = function () {
        var typeProperties = [
            this.get("color"),
            this.get("fontSize"),
        ];

        return "T;" + typeProperties.join(";") + ")(" + this.get("text") + ")(" + this.get("x") + ';' + this.get("y");
    };
    /**
     * @static
     * @param {Object} textInfo
     * @returns {SvgTextModel}
     */
    SvgTextModel.decode = function (textInfo) {
        return new SvgTextModel(textInfo);
    };

    /**
     * @param {SvgElementModel} model
     * @constructor
     */
    var SvgElementView = function (model) {
        var self = this;

        this.model = model;
        this.model.on('change', function () {
            self.render();
        });
        this.model.on('destroy', function () {
            self.el.remove();
            self.model = null;
        });
    };

    /**
     * @param {SvgPathModel} model
     * @constructor
     * @extends SvgElementView
     */
    var SvgPathView = function (model) {
        SvgElementView.call(this, model);

        this.el = document.createElementNS("http://www.w3.org/2000/svg", "path");
        this.el.setAttribute("fill", "transparent");
    };
    SvgPathView.prototype = Object.create(SvgElementView.prototype);
    SvgPathView.prototype.render = function () {
        var d = "";

        $.each(
            this.model.get("points"),
            function (i, point) {
                d += (i === 0) ? "M" : " L ";
                d += point[0] + " " + point[1];
            }
        );

        this.el.setAttribute("d", d);
        this.el.setAttribute('stroke', this.model.get('color'));
        this.el.setAttribute("stroke-width", "3");

        return this;
    };

    /**
     * @param {SvgTextModel} model
     * @constructor
     * @extends SvgElementView
     */
    var SvgTextView = function (model) {
        SvgElementView.call(this, model);

        this.el = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        this.el.setAttribute('stroke', 'none');
    };
    SvgTextView.prototype = Object.create(SvgElementView.prototype);
    SvgTextView.prototype.render = function () {
        this.el.setAttribute('x', this.model.get('x'));
        this.el.setAttribute('y', this.model.get('y'));
        this.el.setAttribute('fill', this.model.get('color'));
        this.el.setAttribute('font-size', this.model.get('fontSize'));
        this.el.textContent = this.model.get('text');

        return this;
    };

    /**
     * @param {SvgElementModel} model
     * @constructor
     */
    var ControllerView = function (model) {
        var self = this;

        this.model = model;
        this.model.on('change', function () {
            self.render();
        });
        this.model.on('destroy', function () {
            self.el.remove();
            self.model = null;
        });

        var elChoice = (function () {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'choice[' + self.model.questionId + '][' + self.model.id + ']';

            return input;
        })();

        var elHotspot = (function () {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'hotspot[' + self.model.questionId + '][' + self.model.id + ']';

            return input;
        })();

        var elText = (function () {
            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.disabled = self.model instanceof SvgPathModel;
            input.value = self.model instanceof SvgTextModel ? self.model.get('text') : '——————————';

            return input;
        })();
        elText.addEventListener('change', function () {
            self.model.set('text', this.value);
        })

        var txtColor = (function () {
            var input = document.createElement('input');
            input.type = 'color';
            input.value = self.model.get('color');
            input.style.border = '0 none';
            input.style.padding = '0';
            input.style.margin = '0';
            input.style.width = '26px';
            input.style.height = '26px';
            input.style.lineHeight = '28px';
            input.style.verticalAlign = 'middle';

            return input;
        })();
        txtColor.addEventListener('change', function () {
            self.model.set('color', this.value);
        })

        var spanAddonColor = (function () {
            var span = document.createElement('span');
            span.className = 'input-group-addon';
            span.style.padding = '0';

            return span;
        })();
        spanAddonColor.appendChild(txtColor);

        var txtSize = (function () {
            var input = document.createElement('input');
            input.type = 'number';
            input.value = self.model.get('fontSize');
            input.step = '1';
            input.min = '15';
            input.max = '30';
            input.style.border = '0 none';
            input.style.padding = '0 0 0 4px';
            input.style.margin = '0';
            input.style.width = '41px';
            input.style.height = '26px';
            input.style.lineHeight = '28px';
            input.style.verticalAlign = 'middle';
            input.disabled = self.model instanceof SvgPathModel;

            return input;
        })();
        txtSize.addEventListener('change', function () {
            self.model.set('fontSize', this.value);
        })

        var spanAddonSize = (function () {
            var span = document.createElement('span');
            span.className = 'input-group-addon';
            span.style.padding = '0';

            return span;
        })();
        spanAddonSize.appendChild(txtSize);

        var btnRemove = (function () {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-default';
            button.innerHTML = '<span class="fa fa-trash text-danger" aria-hidden="true"></span>';

            return button;
        })();
        btnRemove.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            self.model.destroy();
        });

        var spanGroupBtn = (function () {
            var span = document.createElement('span');
            span.className = 'input-group-btn';

            return span;
        })();
        spanGroupBtn.appendChild(btnRemove);

        this.el = (function () {
            var div = document.createElement('div');
            div.className = 'input-group input-group-sm';
            div.style.marginBottom = '10px';

            return div;
        })();
        this.el.appendChild(elText);
        this.el.appendChild(elHotspot);
        this.el.appendChild(elChoice);
        this.el.appendChild(spanAddonColor);
        this.el.appendChild(spanAddonSize);
        this.el.appendChild(spanGroupBtn);

        this.render = function () {
            elChoice.value = this.model.encode();
            elHotspot.value = this.model.encode();

            return this;
        }
    };

    /**
     * @constructor
     */
    var ElementsCollection = function () {
        /**
         * @type {SvgElementModel[]}
         */
        this.models = [];
        this.addEvent = null;

        var lastId = 0;

        /**
         * @param {SvgElementModel} pathModel
         */
        this.add = function (pathModel) {
            pathModel.id = ++lastId;

            this.models.push(pathModel);

            if (this.addEvent) {
                this.addEvent(pathModel);
            }
        };
        /**
         * @param {number} index
         * @returns {SvgElementModel}
         */
        this.get = function (index) {
            return this.models[index];
        };
        this.reset = function () {
            this.models.forEach(function (model) {
                model.destroy();
            })

            this.models = [];
        };
        /**
         * @param {ElementsCollection~addEvent} callback
         */
        this.onAdd = function (callback) {
            this.addEvent = callback;
        };
    };

    /**
     * @param {ElementsCollection} elementsCollection
     * @param {Image} image
     * @param {number} questionId
     * @constructor
     */
    var AnnotationCanvasView = function (elementsCollection, image, questionId) {
        var self = this;

        this.questionId = questionId;
        this.image = image;

        var svgImage = (function () {
            var image = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            image.setAttributeNS('http://www.w3.org/1999/xlink', 'href', self.image.src);
            image.setAttribute('width', self.image.width);
            image.setAttribute('height', self.image.height);

            return image;
        })();

        this.el = (function () {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('version', '1.1');
            svg.setAttribute('viewBox', '0 0 ' + self.image.width + ' ' + self.image.height);
            svg.setAttribute('width', self.image.width);
            svg.setAttribute('height', self.image.height);

            return svg;
        })();
        this.el.appendChild(svgImage);

        this.elementsCollection = elementsCollection;
        this.elementsCollection.onAdd(function (pathModel) {
            var svgElementView = null;

            if (pathModel instanceof SvgPathModel) {
                svgElementView = new SvgPathView(pathModel);
            } else if (pathModel instanceof SvgTextModel) {
                svgElementView = new SvgTextView(pathModel);
            } else {
                return;
            }

            self.el.appendChild(svgElementView.render().el);

            var controllerView = new ControllerView(pathModel);

            $('#annotation-toolbar-' + self.questionId).append(controllerView.render().el);
            $(controllerView.el).children('input').eq(0).focus();
        });

        var $rdbOptions = null;
        var $btnReset = null;

        this.render = function () {
            $rdbOptions = $('[name="' + this.questionId + '-options"]');
            $btnReset = $('#btn-reset-' + this.questionId);

            setEvents();

            return this;
        };

        function setEvents() {
            var isMoving = false,
                elementModel = null;

            $(self.el)
                .on('dragstart', function (e) {
                    e.preventDefault();
                })
                .on('click', function (e) {
                    e.preventDefault();

                    if ("1" !== $rdbOptions.filter(':checked').val()) {
                        return;
                    }

                    var point = getPointOnImage(self.el, e.clientX, e.clientY);
                    elementModel = new SvgTextModel({x: point.x, y: point.y, text: ''});
                    elementModel.questionId = self.questionId;
                    self.elementsCollection.add(elementModel);
                    elementModel = null;
                    isMoving = false;
                })
                .on('mousedown', function (e) {
                    e.preventDefault();

                    var point = getPointOnImage(self.el, e.clientX, e.clientY);
                    if (isMoving || "0" !== $rdbOptions.filter(':checked').val() || elementModel) {
                        return;
                    }

                    elementModel = new SvgPathModel({points: [[point.x, point.y]]});
                    elementModel.questionId = self.questionId;
                    self.elementsCollection.add(elementModel);
                    isMoving = true;
                })
                .on('mousemove', function (e) {
                    e.preventDefault();

                    if (!isMoving || "0" !== $rdbOptions.filter(':checked').val() || !elementModel) {
                        return;
                    }

                    var point = getPointOnImage(self.el, e.clientX, e.clientY);
                    elementModel.addPoint(point.x, point.y);
                })
                .on('mouseup', function (e) {
                    e.preventDefault();

                    if (!isMoving || "0" !== $rdbOptions.filter(':checked').val() || !elementModel) {
                        return;
                    }

                    elementModel = null;
                    isMoving = false;
                });

            $btnReset.on('click', function (e) {
                e.preventDefault();

                self.elementsCollection.reset();
            });
        }
    };

    window.AnnotationQuestion = function (userSettings) {
        $(function () {
            var settings = $.extend(
                    {
                        questionId: 0,
                        exerciseId: 0,
                        relPath: '/'
                    },
                    userSettings
                ),
                xhrUrl = 'exercise/annotation_user.php?' + _p.web_cid_query,
                $container = $('#annotation-canvas-' + settings.questionId);

            $
                .getJSON(settings.relPath + xhrUrl, {
                    question_id: parseInt(settings.questionId),
                    exe_id: parseInt(settings.exerciseId),
                    course_id: parseInt(settings.courseId)
                })
                .done(function (questionInfo) {
                    var image = new Image();
                    image.onload = function () {
                        var elementsCollection = new ElementsCollection(),
                            canvas = new AnnotationCanvasView(elementsCollection, this, parseInt(settings.questionId));

                        $container.html(canvas.render().el);

                        /** @namespace questionInfo.answers.paths */
                        $.each(questionInfo.answers.paths, function (i, pathInfo) {
                            var pathModel = SvgPathModel.decode(pathInfo);
                            pathModel.questionId = settings.questionId;
                            elementsCollection.add(pathModel);
                        });

                        /** @namespace questionInfo.answers.texts */
                        $(questionInfo.answers.texts).each(function (i, textInfo) {
                            var textModel = SvgTextModel.decode(textInfo);
                            textModel.questionId = settings.questionId;
                            elementsCollection.add(textModel);
                        });
                    };
                    image.src = questionInfo.image.path;
                });
        });
    };
})(window, window.jQuery);
