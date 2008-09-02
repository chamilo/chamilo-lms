<?php
/**
 * Add some required CSS and JS to html's head.
 * 
 * Note that $htmlHeadXtra should be passed by reference and not value,
 * otherwise this function will have no effect and your form will be broken.
 *
 * @param   array $htmlHeadXtra     A reference to the doc $htmlHeadXtra
 */
function search_widget_prepare(&$htmlHeadXtra) {
    $htmlHeadXtra[] = '
    <style type="text/css">
    .tags {
        display: block;
        margin-top: 20px;
        width: 70%;
    }
    .tag {
        float: left;
        display: block;
        padding: 5px;
        padding-right: 4px;
        padding-left: 4px;
        margin: 3px;
        border: 1px solid #ddd;
    }
    .tag:hover {
        background: #ddd;
        cursor: pointer;
    }
    .lighttagcolor {
        background: #ddd;
    }
    .lighttagcolor:hover {
        background: #fff;
    }

    </style>';
    $htmlHeadXtra[] = '
    <script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript"></script>';
    $htmlHeadXtra[] = "
    <script type=\"text/javascript\">
    $(document).ready(function() {
        $('#dokeos_search').submit(function (e) {
            var tags = String();
            $('.lighttagcolor').each(function (b, a) {
                tags = tags.concat(a.id+',');
            }); 
            $('#tag_holder').val(tags);
            return true;
        });
    });
    </script>";
}

/**
 * Show the search widget
 * 
 * The form will post to lp_controller.php by default, you can pass a value to
 * $action to use a custom action.
 * IMPORTANT: you have to call search_widget_prepare() before calling this
 * function or otherwise the form will not behave correctly.
 * 
 * @param   string $action     Just in case your action is not
 * lp_controller.php
 */
function search_widget_show($action="lp_controller.php") {
    require_once api_get_path(LIBRARY_PATH).'/search/DokeosQuery.php';
    $dktags = dokeos_query_get_tags();

    $post_tags = array();

    if (isset($_REQUEST['tags'])) {
        $filter = TRUE;
        $post_tags = explode(',', $_REQUEST['tags']);
    }
?>
<form id="dokeos_search" action="<?php echo $action ?>"
method="get">
    <input type="hidden" name="action" value="search"/>
    <input type="text" name="query" size="40" />
    <input type="submit" id="submit" value="<?php echo get_lang("Search") ?>" />
    <br/>
    <h2><?php echo get_lang("Tags") ?></h2>
    <input type="hidden" name="tags" id="tag_holder" />
    <div class="tags">
        <?php
        foreach ($dktags as $tag)
        {
            $tag = trim($tag['name'], 'T ');
            $tag = str_replace(' ', '_', $tag);
            $color = "";
            if ($filter) {
                if (array_search($tag, $post_tags) !== FALSE)
                    $color = "lighttagcolor";
            }
            ?>
            <span class="tag <?php echo $color?>" id="<?php echo $tag ?>">
                <?php echo $tag ?></span>
            <script type="text/javascript">
                $('#<?php echo $tag ?>').click(function waaa (e) {
                    if ( $('.lighttagcolor').size() < 3) {
                        $('#<?php echo $tag ?>').toggleClass('lighttagcolor');
                    } else {
                        $('#<?php echo $tag ?>').removeClass('lighttagcolor');
                    }
                });
            </script>
            <?php
        }
        ?>
    </div>
</form>
<br style="clear: both;"/>
<?php
}
?>