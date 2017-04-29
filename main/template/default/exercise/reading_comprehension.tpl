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
    .text-highlight.blur {
        color:#eee;  /* Old browsers don't go transparent. */
        text-shadow:
                0 0 3px #ddd,   /* Many shadows blur out the area around the text */
                5px 0 5px #ddd,
                0 3px 3px #ddd,
                -6px 0 6px #ddd,
                0 -3px 3px #ddd;
    }
    .text-highlight.active {
        color: rgba(0, 0, 0, 1);
        text-shadow: none;
    }
    .text-highlight.border {
        color: #bbb;
        text-shadow: none;
    }
    .radio.hide-reading-answers, .question_title.hide-reading-answers {
        display: none;
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
                $('.radio').removeClass('hide-reading-answers');
                $('.question_title').removeClass('hide-reading-answers');
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
