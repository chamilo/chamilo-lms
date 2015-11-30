var HotSpotUser = (function () {
    var config, lang, canvas, image, hotspots;

    config = {questionId: 0, selector: ''};

    var canvas = (function () {
        var points = [],
            messageText = document.createElement('div');

        return {
            el: document.createElementNS('http://www.w3.org/2000/svg', 'svg'),
            render: function () {
                var self = this;

                var imageSvg = document.createElementNS('http://www.w3.org/2000/svg', 'image');
                imageSvg.setAttributeNS('http://www.w3.org/1999/xlink', 'href', image.src);
                imageSvg.setAttribute('width', image.width);
                imageSvg.setAttribute('height', image.height);

                canvas.el.setAttribute('version', '1.1');
                canvas.el.setAttribute('viewBox', '0 0 ' + image.width + ' ' + image.height);
                canvas.el.appendChild(imageSvg);
                canvas.el.addEventListener('dragstart', function (e) {
                    e.preventDefault();
                }, false);
                canvas.el.addEventListener('click', function (e) {
                    e.preventDefault();

                    if (points.length >= hotspots.length) {
                        return;
                    }

                    var point = getPointOnImage(e.clientX, e.clientY);

                    self.addPoint(point.x, point.y);
                }, false);

                $(messageText).text(lang.NextAnswer + ' ' + hotspots[0].answer);

                $(config.selector).prepend(messageText);

                return this;
            },
            addPoint: function (x, y) {
                points.push([x, y]);

                var pointSVG = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                pointSVG.setAttribute('cx', x);
                pointSVG.setAttribute('cy', y);
                pointSVG.setAttribute('r', 15);
                pointSVG.setAttribute('fill', '#00677C');

                var textSVG = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                textSVG.setAttribute('x', x);
                textSVG.setAttribute('y', y);
                textSVG.setAttribute('dy', 5);
                textSVG.setAttribute('font-family', 'sans-serif');
                textSVG.setAttribute('text-anchor', 'middle');
                textSVG.setAttribute('fill', 'white');
                textSVG.textContent = points.length;

                var txtHotSpot = $('<input>').attr({
                    type: 'hidden',
                    name: 'hotspot[' + config.questionId + '][' + hotspots[points.length - 1].id + ']'
                }).val([x, y].join(';'));

                var txtChoice = $('<input>').attr({
                    type: 'hidden',
                    name: 'choice[' + config.questionId + '][' + hotspots[points.length - 1].id + ']'
                }).val(1);

                $(canvas.el).append(pointSVG);
                $(canvas.el).append(textSVG);
                $(config.selector).append(txtHotSpot);
                $(config.selector).append(txtChoice);

                if (points.length === hotspots.length) {
                    $(messageText).text(lang.ExeFinished);

                    return;
                }

                $(messageText).text(lang.NextAnswer + ' ' + hotspots[points.length].answer);
            }
        };
    })();

    var startQuestion = function (hotSpotQuestionInfo) {
        image = new Image();
        image.onload = function () {
            $(config.selector)
                .css('width', this.width + 'px')
                .append(canvas.render().el);
        };
        image.src = hotSpotQuestionInfo.image;

        hotspots = hotSpotQuestionInfo.hotspots;

        lang = hotSpotQuestionInfo.lang;
    };

    var getPointOnImage = function (x, y) {
        var pointerPosition = {
            left: x + window.scrollX,
            top: y + window.scrollY
        },
            canvasOffset = {
                x: canvas.el.getBoundingClientRect().x + window.scrollX,
                y: canvas.el.getBoundingClientRect().y + window.scrollY
            };

        return {
            x: Math.round(pointerPosition.left - canvasOffset.x),
            y: Math.round(pointerPosition.top - canvasOffset.y)
        };
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
