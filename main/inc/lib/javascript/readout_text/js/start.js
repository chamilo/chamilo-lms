/* For licensing terms, see /license.txt */

$(function () {
    var parent$ = window.parent.$,
        playerSelector = '#' + parent$('mediaelementwrapper').attr('id') + '_html5',
        $player = parent$(playerSelector);

    var player = $player.get(0),
        def = $.Deferred();

    if (!$player.length) {
        processText(wordsCount);

        return;
    }

    player.preload = 'auto';

    function processText(turns) {
        var tagEnd = '</span> ',
            tagStart = tagEnd + '<span class="text-highlight">',
            wordsPerSecond = Math.ceil(wordsCount / turns);

        var indexes = Object.keys(words);

        var output = '';

        for (var i = 0; i < turns; i++) {
            var block = indexes.slice(i * wordsPerSecond, i * wordsPerSecond + wordsPerSecond),
                index = block[0];

            if (!index) {
                continue;
            }

            output += tagStart + words[index];

            for (var j = 1; j < block.length; j++) {
                index = block[j];
                output += ' ' + words[index];
            }
        }

        output += tagEnd;
        output = output.slice(tagEnd.length);

        $('.page-blank').html(output);

        def.resolve(output);

        return def.promise();
    }

    player.ontimeupdate = function () {
        var block = Math.ceil(this.currentTime);

        $('.text-highlight')
            .removeClass('active')
            .filter(function (index) {
                return index + 1 == block;
            })
            .addClass('active');
    };
    player.onloadedmetadata = function () {
        var turns = Math.ceil(this.duration);

        processText(turns)
            .then(function (output) {
                var to = window.setTimeout(function () {
                    player.play();

                    window.clearTimeout(to);
                }, 1500);
            });
    }
});
