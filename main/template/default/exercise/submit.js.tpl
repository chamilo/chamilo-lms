<script>

var DraggableAnswer = {
    gallery: null,
    trash: null,
    deleteItem: function (item, insertHere) {
        if (insertHere.find(".exercise-draggable-answer-option").length > 0) {
            return false;
        }

        item.fadeOut(function () {
            var $list = $('ul', insertHere).length
                ? $('ul', insertHere)
                : $('<ul>').addClass('gallery list-unstyled').appendTo(insertHere);

            var droppedId = item.attr('id'),
                dropedOnId = insertHere.attr('id'),
                originSelectId = 'window_' + droppedId + '_select',
                value = dropedOnId.split('_')[2];

            $('#' + originSelectId + ' option')
                .filter(function (index) {
                    var position = insertHere.prop('id').split('_')[2];

                    return index === parseInt(position);
                })
                .prop("selected", true);

            item.appendTo($list).fadeIn();
        });
    },
    recycleItem: function (item) {
        var droppedId = item.attr('id'),
            originSelectId = 'window_' + droppedId + '_select',
            idParts = droppedId.split('_'),
            questionId = parseInt(idParts[0]) || 0;

        if (!questionId) {
            return;
        }

        item.fadeOut(function () {
            item
                .appendTo(DraggableAnswer.gallery.filter('[data-question="' + questionId + '"]'))
                .fadeIn();
        });

        $('#' + originSelectId + ' option').prop('selected', false);
        $('#' + originSelectId + ' option:first').prop('selected', true);
    },
    init: function (gallery, trash) {
        this.gallery = gallery;
        this.trash = trash;

        $("li.touch-items", DraggableAnswer.gallery).draggable({
            revert: "invalid",
            containment: "document",
            helper: "clone",
            cursor: "move"
        });

        DraggableAnswer.trash.droppable({
            accept: ".exercise-draggable-answer > li.touch-items",
            drop: function (e, ui) {
                DraggableAnswer.deleteItem(ui.draggable, $(this));
            }
        });

        DraggableAnswer.gallery.droppable({
            drop: function (e, ui) {
                DraggableAnswer.recycleItem(ui.draggable, $(this));
            }
        });
    }
};

var MatchingDraggable = {
    colorDestination: '#316B31',
    curviness: 0,
    connectorType: 'Straight',
    initialized: false,
    init: function (questionId) {
        var windowQuestionSelector = '.window' + questionId + '_question',
            countConnections = $(windowQuestionSelector).length,
            colorArray = [],
            colorArrayDestination = [];

        if (countConnections > 0) {
            colorArray = $.xcolor.analogous("#da0", countConnections);
            colorArrayDestination = $.xcolor.analogous("#51a351", countConnections);
        } else {
            colorArray = $.xcolor.analogous("#da0", 10);
            colorArrayDestination = $.xcolor.analogous("#51a351", 10);
        }

        jsPlumb.importDefaults({
            DragOptions: {cursor: 'pointer', zIndex: 2000},
            PaintStyle: {strokeStyle: '#000'},
            EndpointStyle: {strokeStyle: '#316b31'},
            Endpoint: 'Rectangle',
            Anchors: ['TopCenter', 'TopCenter']
        });

        var exampleDropOptions = {
            tolerance: 'touch',
            hoverClass: 'dropHover',
            activeClass: 'dragActive'
        };

        var destinationEndPoint = {
            endpoint: ["Dot", {radius: 15}],
            paintStyle: {fillStyle: MatchingDraggable.colorDestination},
            isSource: false,
            connectorStyle: {strokeStyle: MatchingDraggable.colorDestination, lineWidth: 8},
            connector: [
                MatchingDraggable.connectorType,
                {curviness: MatchingDraggable.curviness}
            ],
            maxConnections: 1000,
            isTarget: true,
            dropOptions: exampleDropOptions,
            beforeDrop: function (params) {
                jsPlumb.select({source: params.sourceId}).each(function (connection) {
                    jsPlumb.detach(connection);
                });

                var selectId = params.sourceId + "_select";
                var value = params.targetId.split("_")[2];

                $("#" + selectId + " option")
                    .removeAttr('selected')
                    .filter(function (index) {
                        return index === parseInt(value);
                    })
                    .attr("selected", true);

                return true;
            }
        };

        var count = 0;
        var sourceDestinationArray = [];

        $(windowQuestionSelector).each(function (index) {
            var windowId = $(this).attr("id");
            var scope = windowId + "scope";
            var destinationColor = colorArray[count].getHex();

            var sourceEndPoint = {
                endpoint: [
                    "Dot",
                    {radius: 15}
                ],
                paintStyle: {
                    fillStyle: destinationColor
                },
                isSource: true,
                connectorStyle: {
                    strokeStyle: "#8a8888",
                    lineWidth: 8
                },
                connector: [
                    MatchingDraggable.connectorType,
                    {curviness: MatchingDraggable.curviness}
                ],
                maxConnections: 1,
                isTarget: false,
                dropOptions: exampleDropOptions,
                scope: scope
            };

            sourceDestinationArray[count + 1] = sourceEndPoint;

            count++;

            jsPlumb.addEndpoint(
                windowId,
                {
                    anchor: ['RightMiddle', 'RightMiddle', 'RightMiddle', 'RightMiddle']
                },
                sourceEndPoint
            );

            var destinationCount = 0;

            $(windowQuestionSelector).each(function (index) {
                var windowDestinationId = $(this).attr("id");
                destinationEndPoint.scope = scope;
                destinationEndPoint.paintStyle.fillStyle = colorArrayDestination[destinationCount].getHex();
                destinationCount++;

                jsPlumb.addEndpoint(
                    windowDestinationId + "_answer",
                    {
                        anchors: ['LeftMiddle', 'LeftMiddle', 'LeftMiddle', 'LeftMiddle']
                    },
                    destinationEndPoint
                );
            });
        });

        MatchingDraggable.attachBehaviour();
    },
    attachBehaviour: function () {
        if (!MatchingDraggable.initialized) {
            MatchingDraggable.initialized = true;
        }
    }
};

jsPlumb.ready(function () {
    if ($(".drag_question").length > 0) {
        MatchingDraggable.init();

        $(document).scroll(function () {
            jsPlumb.repaintEverything();
        });

        $(window).resize(function () {
            jsPlumb.repaintEverything();
        });
    }
});

function shuffleArray(array) {
    for (var i = array.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var temp = array[i];
        array[i] = array[j];
        array[j] = temp;
    }
}

$(function () {
    // if shuffle answers
    if ('{{ shuffle_answers }}' == '1') {
        $('.exercise-draggable-answer').each(function(){
            // get current ul
            var $ul = $(this);
            var li = $ul.find('.touch-items');
            var liContents = [];
            li.each(function() {
                liContents.push($(this));
            });
            shuffleArray(liContents);
            $ul.empty().html(liContents);
        });
    }

    DraggableAnswer.init(
        $(".exercise-draggable-answer"),
        $(".droppable")
    );
});
</script>
