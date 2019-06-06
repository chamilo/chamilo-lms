{{ form }}

<script>
    $(function () {
        var firstDestination = '<tr>' +
            '<td width="100">' +
            '<input class="form-control" readonly name="min[#category_id#][]" type="text" data-category="#category_id#" value="0">' +
            '</td>' +
            '<td width="100" class="text-center">&leq; &times; &lt;</td>' +
            '<td width="100">' +
            '<input class="form-control" name="max[#category_id#][]" type="text" data-category="#category_id#">' +
            '</td>' +
            '<td>' +
            '<select class="form-control" name="destination[#category_id#][]" data-category="#category_id#">' +
            {% for category in categories %}
                '<option value="{{ category.id }}">{{ category.name }}</option>' +
            {% endfor %}
            '<option value="0">{{ 'EndTest'|get_lang }}</option>' +
            '</select>' +
            '</td>' +
            '<td width="100">' +
            '<button class="btn-add btn btn-default" data-category="#category_id#" type="button" data-category="#category_id#">' +
            '<em class="fa fa-plus"></em>' +
            '</button>' +
            '</td>' +
            '</tr>';

        var middleDestination = '<tr>' +
            '<td width="100">' +
            '<input class="form-control" readonly name="min[#category_id#][]" type="text" data-category="#category_id#">' +
            '</td>' +
            '<td width="100" class="text-center">&leq; &times; &lt;</td>' +
            '<td width="100">' +
            '<input class="form-control" name="max[#category_id#][]" type="text" data-category="#category_id#">' +
            '</td>' +
            '<td>' +
            '<select class="form-control" name="destination[#category_id#][]" data-category="#category_id#">' +
            {% for category in categories %}
                '<option value="{{ category.id }}">{{ category.name }}</option>' +
            {% endfor %}
            '<option value="0">{{ 'EndTest'|get_lang }}</option>' +
            '</select>' +
            '</td>' +
            '<td width="100">' +
            '<button class="btn-remove btn btn-default" data-category="#category_id#" type="button" data-category="#category_id#">' +
            '<em class="fa fa-minus"></em>' +
            '</button>' +
            '</td>' +
            '</tr>';

        var lastDestination = '<tr>' +
            '<td width="100">' +
            '<input class="form-control" readonly name="min[#category_id#][]" type="text" data-category="#category_id#">' +
            '</td>' +
            '<td width="100" class="text-center">&leq; &times; &leq;</td>' +
            '<td width="100">' +
            '<input class="form-control" readonly name="max[#category_id#][]" type="text" data-category="#category_id#" value="100">' +
            '</td>' +
            '<td>' +
            '<select class="form-control" name="destination[#category_id#][]" data-category="#category_id#">' +
            {% for category in categories %}
                '<option value="{{ category.id }}">{{ category.name }}</option>' +
            {% endfor %}
            '<option value="0">{{ 'EndTest'|get_lang }}</option>' +
            '</select>' +
            '</td>' +
            '<td>&nbsp;</td>' +
            '</tr>';

        /**
         * @param {String} destinationStr
         * @param {Number} categoryId
         */
        function generateForm(destinationStr, categoryId) {
            destinationStr = $.trim(destinationStr);

            var table = $('#tbl-category-' + categoryId + ' tbody'),
                firstTemplate = firstDestination.replace(/#category_id#/g, categoryId),
                middleTemplate = middleDestination.replace(/#category_id#/g, categoryId),
                lastTemplate = lastDestination.replace(/#category_id#/g, categoryId);

            table.append(firstTemplate);

            if (destinationStr.length) {
                var destinationsStr = destinationStr.split('@@');

                for (var i = 0; i < destinationsStr.length; i++) {
                    if (i > 0 && i < destinationsStr.length - 1) {
                        table.append(middleTemplate);
                    }
                }
            }

            table.append(lastTemplate);

            if (destinationStr.length) {
                var destinationsStr = destinationStr.split('@@');

                for (var i = 0; i < destinationsStr.length; i++) {
                    var destinationParts = destinationsStr[i].split(':'),
                        max = $('[name="max[' + categoryId +  '][]"]').get(i),
                        destination = $('[name="destination[' + categoryId +  '][]"]').get(i);

                    $(max).val(destinationParts[0]);
                    $(destination).val(destinationParts[1]);
                }
            }

            $('[name="max[' + categoryId +  '][]"]').trigger('change');
        }

        $('#category_destinations').on('change', 'input[data-category]', function () {
            var self = $(this),
                categoryId = self.data('category'),
                tr = self.parents('tr:first'),
                trIndex = tr.index();

            $('#tbl-category-' + categoryId + ' tbody tr:nth-child(' + (trIndex + 2) + ')')
                .find('[name="min[' + categoryId + '][]"]')
                .val(self.val());
        });

        $('#category_destinations').on('click', '.btn-add', function (e) {
            e.preventDefault();

            var self = $(this),
                categoryId = self.data('category') || 0,
                tr = self.parents('tr:first');

            if (!categoryId) {
                return;
            }

            var template = middleDestination.replace(/#category_id#/g, categoryId);

            $(template).insertAfter(tr);

            $('[name="max[' + categoryId + '][]"]').trigger('change');
        });

        $('#category_destinations').on('click', '.btn-remove', function (e) {
            e.preventDefault();

            var self = $(this),
                categoryId = self.data('category') || 0,
                tr = self.parents('tr:first');

            if (!categoryId) {
                return;
            }

            tr.remove();

            $('[name="max[' + categoryId + '][]"]').trigger('change');
        });

        {% if not saved_categories is empty %}
            {% for saved_category in saved_categories %}
                generateForm('{{ saved_category.destinations }}', {{ saved_category.category_id }});
            {% endfor %}
        {% else %}
            {% for category in categories %}
                generateForm('', {{ category.id }});
            {% endfor %}
        {% endif %}
    });
</script>
