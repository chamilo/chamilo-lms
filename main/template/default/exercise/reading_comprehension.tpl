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
                $('.text-highlight').removeClass('border');
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
