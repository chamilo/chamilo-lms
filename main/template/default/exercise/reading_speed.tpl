<div class="question-{{ d }}">
    <div class="btn-toolbar">
        {{ words_count }}
    </div>

    <div id="question-{{ id }}-text" class="center-block question-text">
        {{ text }}
    </div>
</div>

<style>
    .question-text {
        color: #FFF;
        text-align: justify;
        width: 800px;
    }
    .text-highlight.active {
        color: rgba(0, 0, 0, 1);
    }
</style>

<script>
    $(document).on('ready', function () {
        var index = 0,
            total = $('#question-{{ id }}-text .text-highlight').length;

        function updateView()
        {
            $('#question-{{ id }}-text .text-highlight').removeClass('active');

            if (index >= total) {
                window.clearInterval(timeOuId);

                return;
            }

            var current = $('#question-{{ id }}-text .text-highlight').get(index);

            $(current).addClass('active');

            index++;
        }

        updateView();

        var timeOuId = window.setInterval(updateView, 6 * 1000);
    });
</script>
