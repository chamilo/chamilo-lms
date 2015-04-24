<script>
    var DraggableAnswer = {
        gallery: null,
        trash: null,
        deleteItem: function (item, insertHere) {
            if (insertHere.find(".exercise-draggable-answer-option").length > 0) {
                return false;
            }

            item.fadeOut(function () {
                var $list = $('<div class="gallery ui-helper-reset"/>').appendTo(insertHere);

                item.find('a.btn').remove();

                var droppedId = item.attr('id'),
                    dropedOnId = insertHere.attr('id'),
                    originSelectId = 'window_' + droppedId + '_select',
                    value = dropedOnId.split('_')[2];

                $('#' + originSelectId + ' option')
                    .filter(function (index) {
                        return index === parseInt(value);
                    })
                    .attr("selected", true);

                var recycleButton = $('<a>')
                        .attr('href', '#')
                        .addClass('btn btn-default btn-xs')
                        .append(
                            $('<i>').addClass('fa fa-refresh')
                        )
                        .on('click', function (e) {
                            e.preventDefault();

                            var liParent = $(this).parent();

                            DraggableAnswer.recycleItem(liParent);
                        });

                item.append(recycleButton).appendTo($list).fadeIn();
            });
        },
        recycleItem: function (item) {
            item.fadeOut(function () {
                item
                    .find('a.btn')
                    .remove()
                    .end()
                    .find("img")
                    .end()
                    .appendTo(DraggableAnswer.gallery)
                    .fadeIn();
            });

            var droppedId = item.attr('id'),
                originSelectId = 'window_' + droppedId + '_select';

            $('#' + originSelectId + ' option:first').attr('selected', 'selected');
        },
        init: function (gallery, trash) {
            this.gallery = gallery;
            this.trash = trash;

            $("li", DraggableAnswer.gallery).draggable({
                cancel: "a.ui-icon",
                revert: "invalid",
                containment: "document",
                helper: "clone",
                cursor: "move"
            });

            DraggableAnswer.trash.droppable({
                accept: ".exercise-draggable-answer > li",
                hoverClass: "ui-state-active",
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

    $(document).on('ready', function () {
        DraggableAnswer.init(
            $(".exercise-draggable-answer"),
            $(".droppable")
        );
    });
</script>