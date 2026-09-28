
// html jquery tooltip
$(document).tooltip({
    content: function () {
        return $(this).prop('title');
    }
});

/**
 * a bit diffrent from calculated_answer.js to allow number like 12.
 * because fonction used in onkeypress context and before entering a number like 12.1
 * student has to enter 12.
 * @param value
 * @returns {boolean}
 */
function isFloatOrInterger(value) {

    return (/^(\-|\+)?([0-9]*(\.[0-9]*)?)$/.test(value));
}


function sanitizeAnswer(answer)
{
    let negative = '';
    // replace , with . (e.g. came from copy/paste of calcultor)
    let result = answer.replace(/,/g, '.');
    result = result.replace(/[^0-9\-.]/g, '');

    if ( result === '') {
        return '';
    }

    // - only first pos
    if (result.charAt(0) === '-') {
        result = result.substring(1).replace(/-/g, '');
        negative = '-';
    } else {
        result = result.replace(/-/g, '');
    }

    // only one . and not first
    if (result.charAt(0) === '.') {
        result = '.'+result.substring(1).replace(/\./g, '');
    } else {
        // just keep first occurence of . delete others
        let index = 0;
        while (result.charAt(index) !== '.' && index < result.length) {
            index++;
        }
        if (index < result.length) {
            result = result.substring(0, index)+'.'+result.substring(index).replace(/\./g, '');
        }
    }

    return negative+result;
}


let displayDecimalHandler = null;

function displayTip(obj)
{
    let tipId = obj.attr('id').replace(/content/, 'warning');

    if (displayDecimalHandler !== null) {
        clearInterval(displayDecimalHandler);
    }

    $('#'+tipId).removeClass('visiblehidden');

    displayDecimalHandler = setInterval(
        function()
        {
            $('#'+tipId).addClass('visiblehidden')
        },
        2000
    );
}

/**
 * remove decimals more than 2 in number
 * and remove forbid characters
 * @param jqobj
 * @param decimalNumber
 */
function checkStudentInput(jqobj, decimalNumber) {
    var answerValue = sanitizeAnswer(jqobj.val());

    jqobj.val(answerValue);

    // if (!isFloatOrInterger(answerValue)) {
    //     jqobj.val('');
    //     return;
    // }

    if (decimalNumber > 0) {
        if (numberInfos = answerValue.match(/^([0-9]*)\.([0-9]+)/)) {
            // if we have a float like 12.235623 and 3 decimals
            // truncate it to 12.23
            // we can have .23 it will be replaced when save with 0.235623 in php function CalculatedAnswer::getStudentAnswerFromChoice()
            newDecimal = numberInfos[2].substring(0, decimalNumber);
            jqobj.val(numberInfos[1]+"."+newDecimal);

            if (numberInfos[2].length > decimalNumber) {
                displayTip(jqobj);
            }
        } else if (numberInfos = answerValue.match(/^(\-[0-9]*)\.([0-9]+)/)) {
            // if we have a float like -12.235623 and 3 decimals
            // truncate it to -12.23
            // numberInfos = answerValue.match(/^(\-[0-9]+)\.([0-9]+)/)
            // we can have -.23 it will be replaced when save with -0.23 in php function CalculatedAnswer::getStudentAnswerFromChoice()
            newDecimal = numberInfos[2].substring(0, decimalNumber);
            jqobj.val(numberInfos[1]+"."+newDecimal);

            if (numberInfos[2].length > decimalNumber) {
                displayTip(jqobj);
            }
        }
    }
    else {
        // decimal number == 0
        if (numberInfos = answerValue.match(/([0-9\-]*)(\.[0-9]*)/)) {
            // if we have a float like 12.235623 and 0 decimal
            // truncate it to 12
            jqobj.val(numberInfos[1]);

            if (numberInfos[2].length > 0) {
                displayTip(jqobj);
            }
        }
    }

    return true;
}



