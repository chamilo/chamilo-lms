$(document).ready(function() {
      /* toggle advanced view */
      $('a#tags-toggle').click(function() {
        $('#tags').toggle(150);
        return false;
      });
      /* reset terms form */
      $('#tags-clean').click(function() {
        // clear multiple select
        $('select option:selected').each(function () {
            $(this).prop('selected', false);
        });
        return false;
      });
      /* ajax suggestions */
      $('#query').autocomplete({
          source: 'search_suggestions.php',
        multiple: false,
        selectFirst: false,
        mustMatch: false,
        autoFill: false
      });
      /* prefilter form */
      $('#prefilter').change(function () {
        var str = "";
        $("#prefilter option:selected").each(function () {
            str += $(this).text() + " ";
        });
        process_terms = function(data) {
            $(".sf-select-multiple").html("");
            $.each(data, function(i,item) {
                $.each(item.terms, function(index, term) {
                    $('<option />').val(index).text(term).appendTo("#sf-" + item.prefix);
                });
            });
        };
        url = "/main/inc/lib/search/get_terms.php";
        params = "?term=" + $(this).val() + "&prefix=" + $(this).attr("title") + "&operator=" + $("input[@name=operator]:checked").val();
        $.getJSON(url + params, process_terms);
      });

});
