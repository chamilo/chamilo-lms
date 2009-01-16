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
            $(this).attr('selected', '');
        });
        return false;
      });
      /* ajax suggestions */
      $('#query').autocomplete('search_suggestions.php', {
        multiple: false,
        selectFirst: false,
        mustMatch: false,
        autoFill: false
      });
});
