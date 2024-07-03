{{ tabs }}

<div class="search-courses">
    {{ frm_filter }}
</div>

<style>
    .input-group .form-control {
        z-index: auto !important;
    }
    /* WIP: To be move in base.css */
    .search-courses .form-inline-box .input-group {
        width: 80%;
        padding-bottom: 14px;
        z-index: auto;
    }
    .search-courses .form-inline-box .input-group label {
        margin-bottom: 0px;
        z-index: auto;
    }
</style>
<div>
    {{ 'TotalNumberOfAvailableCourses'|get_lang }} :
    <strong>{{ total_number_of_courses }}</strong>
</div>
<div>
    {{ 'NumberOfMatchingCourses'|get_lang }} :
    <strong>{{ total_number_of_matching_courses }}</strong>
</div>
<div class="col-md-12 catalog-pagination-top">
    {{ pagination }}
</div>

<div class="grid-courses row">
    {% for course in courses %}
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="items items-courses">
                {% include 'catalog/course_item_catalog.tpl'|get_template %}
            </div>
        </div>
    {% endfor %}
</div>

<div class="col-md-12">
    {{ pagination }}
</div>

<script>
    $(function() {
        $('.star-rating li a').on('click', function(event) {
            var id = $(this).parents('ul').attr('id');
            $('#vote_label2_' + id).html('{{ 'Loading'|get_lang }}');

            $.ajax({
                url: $(this).attr('data-link'),
                success: function(data) {
                    $('#rating_wrapper_'+id).html(data);
                }
            });
        });

        {{ jquery_ready_content }}
    });
</script>
