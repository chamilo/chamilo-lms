<div class="question-{{ d }}">
    <div id="question-{{ id }}-text" class="center-block question-text">
        {{ text }}
    </div>
</div>

<style>
    .question-text {
        color: #FFF;
        text-align: justify;
    }
    .text-highlight.active {
        color: rgba(0, 0, 0, 1);
    }
    .text-highlight.border {
        color: #888;
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

            if (index > 0) {
                $('#question-{{ id }}-text .text-highlight').removeClass('border');
                var previousWord = $('#question-{{ id }}-text .text-highlight').get(index-1);
                $(previousWord).addClass('border');
            }
            if (index < total) {
                $('#question-{{ id+1 }}-text .text-highlight').removeClass('border');
                var nextWord = $('#question-{{ id }}-text .text-highlight').get(index+1);
                $(nextWord).addClass('border');
            }

            index++;
        }

        updateView();

        var timeOuId = window.setInterval(updateView, {{ refreshTime }} * 1000);
    });
</script>
