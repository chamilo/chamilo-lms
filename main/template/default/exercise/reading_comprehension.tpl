<div id="question-{{ id }}" class="question-reading-comprehension-container">
    <div class="question-reading-comprehension-overlay"></div>
    {% if exercise_type == 1 %} {# all in one page #}
        <button type="button" class="btn btn-default btn-lg" id="question-{{ id }}-start">
            {{ 'StartTimeWindow'|get_lang }}
            <span class="fa fa-play" aria-hidden="true"></span>
        </button>
    {% endif %}
    <div id="question-{{ id }}-text" class="center-block question-reading-comprehension-text" onselectstart="return false">
        {{ text }}
    </div>
</div>

<style>
    .question-reading-comprehension-container {
        position: relative;
    }
    .question-reading-comprehension-container button {
        left: 50%;
        margin-left: -60px;
        margin-top: -23px;
        position: absolute;
        top: 50%;
        width: 120px;
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
        -webkit-text-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
        -khtml-text-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
        -moz-text-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
        -ms-text-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
        text-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
        -webkit-transition: color .8s linear, text-shadow .8s linear;
        -khtml-transition: color .8s linear, text-shadow .8s linear;
        -moz-transition: color .8s linear, text-shadow .8s linear;
        -ms-transition: color .8s linear, text-shadow .8s linear;
        transition: color .8s linear, text-shadow .8s linear;
    }
    .question-reading-comprehension-text .text-highlight.active {
        color: #000;
        -webkit-text-shadow: none;
        -khtml-text-shadow: none;
        -moz-text-shadow: none;
        -ms-text-shadow: none;
        text-shadow: none;
        -webkit-transition: color .8s linear, text-shadow .8s linear;
        -khtml-transition: color .8s linear, text-shadow .8s linear;
        -moz-transition: color .8s linear, text-shadow .8s linear;
        -ms-transition: color .8s linear, text-shadow .8s linear;
        transition: color .8s linear, text-shadow .8s linear;
    }
    .question-reading-comprehension-text .text-highlight.border {
        color: #bbb;
        -webkit-text-shadow: none;
        -khtml-text-shadow: none;
        -moz-text-shadow: none;
        -ms-text-shadow: none;
        text-shadow: none;
        -webkit-transition: color .8s linear, text-shadow .8s linear;
        -khtml-transition: color .8s linear, text-shadow .8s linear;
        -moz-transition: color .8s linear, text-shadow .8s linear;
        -ms-transition: color .8s linear, text-shadow .8s linear;
        transition: color .8s linear, text-shadow .8s linear;
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
            $btnFinish = $('#question_div_{{ id }} .form-actions button.question-validate-btn'),
            total = $questionTexts.length,
            timeOuId = null;

        function updateView()
        {
            $questionTexts.removeClass('active border');

            if (index == total - 1) {
                $('#question_div_{{ id }} .radio, #question_div_{{ id }} .question_title').removeClass('hide-reading-answers');

                $btnFinish.show();
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

        function startQuestion() {
            updateView();

            timeOuId = window.setInterval(updateView, {{ refresh_time }} * 1000);
        }

        $btnFinish.hide();

        {% if exercise_type == 1 %}
        $('#question-{{ id }}-start').on('click', function (e) {
            e.preventDefault();

            startQuestion();

            $(this).remove();
        });
        {% else %}
        startQuestion();
        {% endif %}
    });
</script>
