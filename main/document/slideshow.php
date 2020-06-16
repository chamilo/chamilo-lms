<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Patrick Cool patrick.cool@UGent.be Ghent University Mai 2004
 * @author Julio Montoya Lots of improvements, cleaning, adding security
 * @author Juan Carlos Raña Trabado herodoto@telefonica.net	January 2008
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

if (api_get_configuration_value('disable_slideshow_documents')) {
    api_not_allowed(true);
}

$curdirpath = $path = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;
$courseInfo = api_get_course_info();
$pathurl = urlencode($path);
$slide_id = isset($_GET['slide_id']) ? Security::remove_XSS($_GET['slide_id']) : null;
$document_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
$isAllowedToEdit = api_is_allowed_to_edit(null, true);

if (empty($slide_id)) {
    $edit_slide_id = 1;
} else {
    $edit_slide_id = $slide_id;
}

if ($path != '/') {
    $folder = $path.'/';
} else {
    $folder = '/';
}
$sys_course_path = api_get_path(SYS_COURSE_PATH);

// Breadcrumb navigation
$url = 'document.php?curdirpath='.$pathurl.'&'.api_get_cidreq();
$originaltoolname = get_lang('Documents');
$_course = api_get_course_info();
$interbreadcrumb[] = ['url' => Security::remove_XSS($url), 'name' => $originaltoolname];
$originaltoolname = get_lang('SlideShow');
$sessionId = api_get_session_id();

$groupIid = 0;
$groupMemberWithEditRights = false;
// Setting group variables.
if (!empty($groupId)) {
    $group_properties = GroupManager::get_group_properties($groupId);
    $groupIid = isset($group_properties['iid']) ? $group_properties['iid'] : 0;
}

Display::display_header($originaltoolname, 'Doc');

$slideshowKey = 'slideshow_'.api_get_course_id().api_get_session_id().$curdirpath;
$documentAndFolders = Session::read($slideshowKey);
if (empty($documentAndFolders)) {
    $documentAndFolders = DocumentManager::getAllDocumentData(
        $courseInfo,
        $curdirpath,
        $groupIid,
        null,
        $isAllowedToEdit,
        false
    );
    Session::write($slideshowKey, $documentAndFolders);
}

require 'document_slideshow.inc.php';

// Calculating the current slide, next slide, previous slide and the number of slides
$slide = null;
if ($slide_id != 'all') {
    $slide = $slide_id ? $slide_id : 0;
    $previous_slide = $slide - 1;
    $next_slide = $slide + 1;
}
$total_slides = count($image_files_only);

echo '<div class="actions">';

if ($slide_id != 'all') {
    $image = null;
    if (isset($image_files_only[$slide])) {
        $image = $sys_course_path.$_course['path'].'/document'.$folder.$image_files_only[$slide];
    }
    if (file_exists($image)) {
        echo '<div class="actions-pagination">';
        // Back forward buttons
        if ($slide == 0) {
            $imgp = 'action_prev_na.png';
            $first = Display::return_icon('action_first_na.png');
        } else {
            $imgp = 'action_prev.png';
            $first = '<a href="slideshow.php?slide_id=0&curdirpath='.$pathurl.'&'.api_get_cidreq().'">
                      '.Display::return_icon('action_first.png', get_lang('FirstSlide')).'
                      </a>';
        }

        // First slide
        echo $first;

        // Previous slide
        if ($slide > 0) {
            echo '<a href="slideshow.php?slide_id='.$previous_slide.'&curdirpath='.$pathurl.'&'.api_get_cidreq().'">';
        }

        echo Display::return_icon($imgp, get_lang('Previous'));

        if ($slide > 0) {
            echo '</a>';
        }

        // Divider
        echo ' [ '.$next_slide.'/'.$total_slides.' ] ';

        // Next slide
        if ($slide < $total_slides - 1) {
            echo '<a href="slideshow.php?slide_id='.$next_slide.'&curdirpath='.$pathurl.'&'.api_get_cidreq().'">';
        }
        if ($slide == $total_slides - 1) {
            $imgn = 'action_next_na.png';
            $last = Display::return_icon('action_last_na.png', get_lang('LastSlide'));
        } else {
            $imgn = 'action_next.png';
            $last = '<a href="slideshow.php?slide_id='.($total_slides - 1).'&curdirpath='.$pathurl.'&'.api_get_cidreq().'">
                    '.Display::return_icon('action_last.png', get_lang('LastSlide')).'
                </a>';
        }
        echo Display::return_icon($imgn, get_lang('Next'));
        if ($slide > 0) {
            echo '</a>';
        }

        // Last slide
        echo $last;
        echo '</div>';
    }
}

echo Display::url(
    Display::return_icon('folder_up.png', get_lang('Up'), '', ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&id='.$document_id
);

// Show thumbnails
if ($slide_id != 'all') {
    echo '<a href="slideshow.php?slide_id=all&curdirpath='.$pathurl.'&'.api_get_cidreq().'">'.
        Display::return_icon('thumbnails.png', get_lang('ShowThumbnails'), '', ICON_SIZE_MEDIUM).'</a>';
} else {
    echo Display::return_icon('thumbnails_na.png', get_lang('ShowThumbnails'), '', ICON_SIZE_MEDIUM);
}
// Slideshow options
echo '<a href="slideshowoptions.php?curdirpath='.$pathurl.'&'.api_get_cidreq().'">'.
    Display::return_icon('settings.png', get_lang('SetSlideshowOptions'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';
echo '<br />';

/*	TREATING THE POST DATA FROM SLIDESHOW OPTIONS */
// If we come from slideshowoptions.php we sessionize (new word !!! ;-) the options
if (isset($_POST['Submit'])) {
    // We come from slideshowoptions.php
    Session::write('image_resizing', Security::remove_XSS($_POST['radio_resizing']));
    if ($_POST['radio_resizing'] == 'resizing' && $_POST['width'] != '' && $_POST['height'] != '') {
        Session::write('image_resizing_width', Security::remove_XSS($_POST['width']));
        Session::write('image_resizing_height', Security::remove_XSS($_POST['height']));
    } else {
        Session::write('image_resizing_width', null);
        Session::write('image_resizing_height', null);
    }
}

$target_width = $target_height = null;
$imageResize = Session::read('image_resizing');
// The target height and width depends if we choose resizing or no resizing
if ($imageResize == 'resizing') {
    $target_width = Session::read('image_resizing_width');
    $target_height = Session::read('image_resizing_height');
}

/*	THUMBNAIL VIEW */
// This is for viewing all the images in the slideshow as thumbnails.
$image_tag = [];
$html = '';
if ($slide_id == 'all') {
    // Config for make thumbnails
    $allowed_thumbnail_types = ['jpg', 'jpeg', 'gif', 'png'];
    $max_thumbnail_width = 250;
    $max_thumbnail_height = 250;
    $png_compression = 0; //0(none)-9
    $jpg_quality = 75; //from 0 to 100 (default is 75). More quality less compression
    $directory_thumbnails = $sys_course_path.$_course['path'].'/document'.$folder.'.thumbs/';
    //Other parameters only for show tumbnails
    $row_items = 4; //only in slideshow.php
    $number_image = 7; //num icons cols to show
    $thumbnail_width_frame = $max_thumbnail_width; //optional $max_thumbnail_width+x
    $thumbnail_height_frame = $max_thumbnail_height;

    // Create the template_thumbnails folder (if no exist)
    if (!file_exists($directory_thumbnails)) {
        @mkdir($directory_thumbnails, api_get_permissions_for_new_directories());
    }

    // check files and thumbnails
    if (is_array($image_files_only)) {
        foreach ($image_files_only as $one_image_file) {
            $image = $sys_course_path.$_course['path'].'/document'.$folder.$one_image_file;
            $image_thumbnail = $directory_thumbnails.'.'.$one_image_file;

            if (file_exists($image)) {
                //check thumbnail
                $imagetype = explode(".", $image);
                //or check $imagetype = image_type_to_extension(exif_imagetype($image), false);
                $imagetype = strtolower($imagetype[count($imagetype) - 1]);

                if (in_array($imagetype, $allowed_thumbnail_types)) {
                    if (!file_exists($image_thumbnail)) {
                        //run each once we view thumbnails is too heavy,
                        // then need move into  !file_exists($image_thumbnail,
                        // and only run when haven't the thumbnail
                        $original_image_size = api_getimagesize($image);

                        switch ($imagetype) {
                            case 'gif':
                                $source_img = imagecreatefromgif($image);
                                break;
                            case 'jpg':
                                $source_img = imagecreatefromjpeg($image);
                                break;
                            case 'jpeg':
                                $source_img = imagecreatefromjpeg($image);
                                break;
                            case 'png':
                                $source_img = imagecreatefrompng($image);
                                break;
                        }

                        $new_thumbnail_size = api_calculate_image_size(
                            $original_image_size['width'],
                            $original_image_size['height'],
                            $max_thumbnail_width,
                            $max_thumbnail_height
                        );

                        if ($max_thumbnail_width > $original_image_size['width'] &&
                            $max_thumbnail_height > $original_image_size['height']
                        ) {
                            $new_thumbnail_size['width'] = $original_image_size['width'];
                            $new_thumbnail_size['height'] = $original_image_size['height'];
                        }

                        $crop = imagecreatetruecolor($new_thumbnail_size['width'], $new_thumbnail_size['height']);

                        // preserve transparency
                        if ($imagetype == 'png') {
                            imagesavealpha($crop, true);
                            $color = imagecolorallocatealpha($crop, 0x00, 0x00, 0x00, 127);
                            imagefill($crop, 0, 0, $color);
                        }

                        if ($imagetype == 'gif') {
                            $transindex = imagecolortransparent($source_img);
                            $palletsize = imagecolorstotal($source_img);
                            //GIF89a for transparent and anim (first clip), either GIF87a
                            if ($transindex >= 0 && $transindex < $palletsize) {
                                $transcol = imagecolorsforindex($source_img, $transindex);
                                $transindex = imagecolorallocatealpha(
                                    $crop,
                                    $transcol['red'],
                                    $transcol['green'],
                                    $transcol['blue'],
                                    127
                                );
                                imagefill($crop, 0, 0, $transindex);
                                imagecolortransparent($crop, $transindex);
                            }
                        }

                        // Resampled image
                        imagecopyresampled(
                            $crop,
                            $source_img,
                            0,
                            0,
                            0,
                            0,
                            $new_thumbnail_size['width'],
                            $new_thumbnail_size['height'],
                            $original_image_size['width'],
                            $original_image_size['height']
                        );

                        switch ($imagetype) {
                            case 'gif':
                                imagegif($crop, $image_thumbnail);
                                break;
                            case 'jpg':
                                imagejpeg($crop, $image_thumbnail, $jpg_quality);
                                break;
                            case 'jpeg':
                                imagejpeg($crop, $image_thumbnail, $jpg_quality);
                                break;
                            case 'png':
                                imagepng($crop, $image_thumbnail, $png_compression);
                                break;
                        }

                        //clean memory
                        imagedestroy($crop);
                    }//end !exist thumbnail
                    //show thumbnail and link
                    $one_image_thumbnail_file = '.thumbs/.'.$one_image_file; //get path thumbnail
                    $doc_url = ($path && $path !== '/') ? $path.'/'.$one_image_thumbnail_file : $path.$one_image_thumbnail_file;
                    $image_tag[] = '<img class="img-gallery" src="download.php?doc_url='.$doc_url.'" border="0" title="'.$one_image_file.'">';
                } else {
                    // If images aren't support by gd (not gif, jpg, jpeg, png)
                    if ($imagetype == 'bmp') {
                        // use getimagesize instead api_getimagesize($image);
                        // because api_getimagesize doesn't support bmp files.
                        // Put here for each show, only for a few bmp files isn't heavy
                        $original_image_size = getimagesize($image);
                        if ($max_thumbnail_width < $original_image_size[0] ||
                            $max_thumbnail_height < $original_image_size[1]
                        ) {
                            //don't use resize_image because doesn't run with bmp files
                            $thumbnail_size = api_calculate_image_size(
                                $original_image_size[0],
                                $original_image_size[1],
                                $max_thumbnail_width,
                                $max_thumbnail_height
                            );
                            $image_height = $thumbnail_size['height'];
                            $image_width = $thumbnail_size['width'];
                        } else {
                            $image_height = $original_image_size[0];
                            $image_width = $original_image_size[1];
                        }
                    } else {
                        // Example for svg files,...
                        $image_width = $max_thumbnail_width;
                        $image_height = $max_thumbnail_height;
                    }

                    $doc_url = ($path && $path !== '/') ? $path.'/'.$one_image_file : $path.$one_image_file;
                    $image_tag[] = '<img
                            src="download.php?doc_url='.$doc_url.'"
                            border="0"
                            width="'.$image_width.'" height="'.$image_height.'" title="'.$one_image_file.'">';
                }
            }
        }
    }

    // Creating the table
    $html_table = '';
    $i = 0;
    $count_image = count($image_tag);
    $number_iteration = ceil($count_image / $number_image);
    $p = 0;
    $html = '';
    $html .= '<div class="gallery">';
    for ($k = 0; $k < $number_iteration; $k++) {
        for ($i = 0; $i < $number_image; $i++) {
            if (isset($image_tag[$p])) {
                $html .= '<div class="col-xs-6 col-sm-3 col-md-2">';
                $html .= '<div class="canvas-one">';
                $html .= '<a class="canvas-two" href="slideshow.php?slide_id='.$p.'&curdirpath='.$pathurl.'">';
                $html .= '<div class="frame">';
                $html .= '<div class="photo">';
                $html .= $image_tag[$p];
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</a>';
                $html .= '</div>';
                $html .= '</div>';
            }
            $p++;
        }
    }
    $html .= '</div>';
}

echo $html;

/*	ONE AT A TIME VIEW */
$course_id = api_get_course_int_id();

// This is for viewing all the images in the slideshow one at a time.
if ($slide_id != 'all' && !empty($image_files_only)) {
    if (file_exists($image) && is_file($image)) {
        $image_height_width = DocumentManager::resizeImageSlideShow($image, $target_width, $target_height);
        $image_height = $image_height_width[0];
        $image_width = $image_height_width[1];
        $height_width_tags = null;
        if ($imageResize == 'resizing') {
            $height_width_tags = 'width="'.$image_width.'" height="'.$image_height.'"';
        }

        // This is done really quickly and should be cleaned up a little bit using the API functions
        $tbl_documents = Database::get_course_table(TABLE_DOCUMENT);
        if ($path == '/') {
            $pathpart = '/';
        } else {
            $pathpart = $path.'/';
        }
        $sql = "SELECT * FROM $tbl_documents
                WHERE
                  c_id = $course_id AND
                  path = '".Database::escape_string($pathpart.$image_files_only[$slide])."'";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        echo '<div class="thumbnail">';
        if ($slide < $total_slides - 1 && $slide_id != 'all') {
            echo "<a href='slideshow.php?slide_id=".$next_slide."&curdirpath=$pathurl'>";
        } else {
            echo "<a href='slideshow.php?slide_id=0&curdirpath=$pathurl'>";
        }

        if ($path == '/') {
            $path = '';
        }

        list($width, $height) = getimagesize($image);
        // Auto resize
        if ($imageResize == 'resizing') {
            ?>
        <script>
            var initial_width='<?php echo $width; ?>';
            var initial_height='<?php echo $height; ?>';
            var height = window.innerHeight -320;
            var width = window.innerWidth -360;

            if (initial_height>height || initial_width>width) {
                start_width = width;
                start_height= height;
            } else {
                start_width = initial_width;
                start_height = initial_height;
            }
            document.write('<img id="image" src="<?php echo 'download.php?doc_url='.$path.'/'.$image_files_only[$slide]; ?>" width="'+start_width+'" height="'+start_height+'"  border="0"  alt="<?php echo $image_files_only[$slide]; ?>">');

            function resizeImage() {
                var resize_factor_width = width / initial_width;
                var resize_factor_height = height / initial_height;
                var delta_width = width - initial_width * resize_factor_height;
                var delta_height = height - initial_height * resize_factor_width;

                if (delta_width > delta_height) {
                    width = Math.ceil(initial_width * resize_factor_height);
                    height= Math.ceil(initial_height * resize_factor_height);
                } else if(delta_width < delta_height) {
                    width = Math.ceil(initial_width * resize_factor_width);
                    height = Math.ceil(initial_height * resize_factor_width);
                } else {
                    width = Math.ceil(width);
                    height = Math.ceil(height);
                }

                document.getElementById('image').style.height = height +"px";
                document.getElementById('image').style.width = width +"px";
                document.getElementById('td_image').style.background='none';
                document.getElementById('image').style.visibility='visible';
            }

            if (initial_height > height || initial_width > width) {
                document.getElementById('image').style.visibility='hidden';
                document.getElementById('td_image').style.background='url(<?php echo Display::returnIconPath('loadingAnimation.gif'); ?>) center no-repeat';
                document.getElementById('image').onload = resizeImage;
                window.onresize = resizeImage;
            }
            </script>
    <?php
        } else {
            echo "<img
                class=\"img-responsive\"
                src='download.php?doc_url=$path/".$image_files_only[$slide]."' alt='".$image_files_only[$slide]."'
                border='0'".$height_width_tags.'>';
        }

        echo '</a>';
        echo '<div class="caption text-center">';
        echo Display::tag('h3', $row['title']);
        echo '<p>'.$row['comment'].'</p>';
        echo '</div>';
        echo '</div>';

        if (api_is_allowed_to_edit(null, true)) {
            echo '<ul class="list-unstyled">';
            $aux = explode('.', htmlspecialchars($image_files_only[$slide]));
            $ext = $aux[count($aux) - 1];
            if ($imageResize == 'resizing') {
                $resize_info = get_lang('Resizing').'<br />';
                $resize_width = Session::read('image_resizing_width').' x ';
                $resize_height = Session::read('image_resizing_height');
            } elseif ($imageResize != 'noresizing') {
                $resize_info = get_lang('Resizing').'<br />';
                $resize_width = get_lang('Auto').' x ';
                $resize_height = get_lang('Auto');
            } else {
                $resize_info = get_lang('NoResizing').'<br />';
                $resize_width = '';
                $resize_height = '';
            }

            echo '<li class="text-center">';
            echo $image_files_only[$slide].' ';
            echo Display::toolbarButton(
                get_lang('Modify'),
                'edit_document.php?'.api_get_cidreq().'&'.http_build_query([
                    'id' => $row['id'],
                    'origin' => 'slideshow',
                    'origin_opt' => $edit_slide_id,
                    'curdirpath' => $pathurl,
                ]),
                'edit',
                'link',
                [],
                false
            );
            echo '</li>';
            echo '<li class="text-center">'.$width.' x '.$height.'</li>';
            echo '<li class="text-center">'.round((filesize($image) / 1024), 2).' KB - '.$ext.'</li>';
            echo '<li class="text-center">'.$resize_info.'</li>';
            echo '<li class="text-center">'.$resize_width.'</li>';
            echo '<li class="text-center">'.$resize_height.'</li>';
            echo '</ul>';
        }
    } else {
        echo Display::return_message(get_lang('FileNotFound'), 'warning');
    }
} else {
    if ($slide_id != 'all') {
        echo Display::return_message(get_lang('NoDataAvailable'), 'warning');
    }
}

Display::display_footer();
