<div id="question-{{ id }}" class="question-reading-comprehension-container">
    <div class="question-reading-comprehension-overlay"></div>
    <div id="question-{{ id }}-text" class="center-block question-reading-comprehension-text" onselectstart="return false">
        {{ text }}
    </div>
</div>

<style>
    .question-reading-comprehension-container {
        position: relative;
    }
    .question-reading-comprehension-container .question-reading-comprehension-overlay {
        bottom: 0;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
    }
    .question-reading-comprehension-text {
        text-align: justify;
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    .question-reading-comprehension-text .text-highlight {
        color: transparent;
        -webkit-text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        -khtml-text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        -moz-text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        -ms-text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
    }
    .question-reading-comprehension-text .text-highlight.active {
        color: #000;
        -webkit-text-shadow: none;
        -khtml-text-shadow: none;
        -moz-text-shadow: none;
        -ms-text-shadow: none;
        text-shadow: none;
    }
    .question-reading-comprehension-text .text-highlight.border {
        color: #bbb;
        -webkit-text-shadow: none;
        -khtml-text-shadow: none;
        -moz-text-shadow: none;
        -ms-text-shadow: none;
        text-shadow: none;
    }
    .question-reading-comprehension-text br {
        margin-bottom: 1em;
    }
    .radio.hide-reading-answers, .question_title.hide-reading-answers {
        display: none;
    }
</style>

<script>
    $(document).on('ready', function () {
        var index = 0,
            $questionTexts = $('#question-{{ id }}-text .text-highlight'),
            total = $questionTexts.length;

        function updateView()
        {
            $questionTexts.removeClass('active border');

            if (index == total - 1) {
                $('#question_div_{{ id }} .radio, #question_div_{{ id }} .question_title').removeClass('hide-reading-answers');
            }

            if (index >= total) {
                window.clearInterval(timeOuId);

                return;
            }

            var prev = index > 0 ? $('#question-{{ id }}-text .text-highlight').get(index - 1) : null,
                current = $questionTexts.get(index),
                next = index < total ? $('#question-{{ id }}-text .text-highlight').get(index + 1) : null;

            $(current).addClass('active');

            if (prev) {
                $(prev).addClass('border');
            }

            if (next) {
                $(next).addClass('border');
            }

            index++;
        }

        updateView();

        var timeOuId = window.setInterval(updateView, {{ refresh_time }} * 1000);
    });
</script>
