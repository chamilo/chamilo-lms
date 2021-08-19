<?php
/* For licensing terms, see /license.txt */

require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

class Cc13Forum extends Cc13Entities
{
    public function fullPath($path, $dir_sep = DIRECTORY_SEPARATOR)
    {
        $token = '$IMS-CC-FILEBASE$';
        $path = str_replace($token, '', $path);

        if (is_string($path) && ($path != '')) {
            $dot_dir = '.';
            $up_dir = '..';
            $length = strlen($path);
            $rtemp = trim($path);
            $start = strrpos($path, $dir_sep);
            $can_continue = ($start !== false);
            $result = $can_continue ? '' : $path;
            $rcount = 0;

            while ($can_continue) {
                $dir_part = ($start !== false) ? substr($rtemp, $start + 1, $length - $start) : $rtemp;
                $can_continue = ($dir_part !== false);

                if ($can_continue) {
                    if ($dir_part != $dot_dir) {
                        if ($dir_part == $up_dir) {
                            $rcount++;
                        } else {
                            if ($rcount > 0) {
                                $rcount--;
                            } else {
                                $result = ($result == '') ? $dir_part : $dir_part.$dir_sep.$result;
                            }
                        }
                    }
                    $rtemp = substr($path, 0, $start);
                    $start = strrpos($rtemp, $dir_sep);
                    $can_continue = (($start !== false) || (strlen($rtemp) > 0));
                }
            }
        }

        return $result;
    }

    public function generateData()
    {
        $data = [];
        if (!empty(Cc1p3Convert::$instances['instances']['forum'])) {
            foreach (Cc1p3Convert::$instances['instances']['forum'] as $instance) {
                $data[] = $this->getForumData($instance);
            }
        }

        return $data;
    }

    public function getForumData($instance)
    {
        $topic_data = $this->getTopicData($instance);

        $values = [];
        if (!empty($topic_data)) {
            $values = [
                'instance' => $instance['instance'],
                'title' => self::safexml($topic_data['title']),
                'description' => self::safexml($topic_data['description']),
            ];
        }

        return $values;
    }

    public function storeForums($forums)
    {
        // Create a Forum category for the import CC 1.3.
        $courseInfo = api_get_course_info();
        $catForumValues['forum_category_title'] = 'CC1p3';
        $catForumValues['forum_category_comment'] = '';
        $catId = store_forumcategory(
            $catForumValues,
            $courseInfo,
            false
        );

        foreach ($forums as $forum) {
            $forumValues = [];
            $forumValues['forum_title'] = $forum['title'];
            $forumValues['forum_image'] = '';
            $forumValues['forum_comment'] = strip_tags($forum['description']);
            $forumValues['forum_category'] = $catId;
            $forumValues['moderated'] = 0;
            store_forum($forumValues, $courseInfo);
        }

        return true;
    }

    public function getTopicData($instance)
    {
        $topic_data = [];

        $topic_file = $this->getExternalXml($instance['resource_indentifier']);

        if (!empty($topic_file)) {
            $topic_file_path = Cc1p3Convert::$pathToManifestFolder.DIRECTORY_SEPARATOR.$topic_file;
            $topic_file_dir = dirname($topic_file_path);
            $topic = $this->loadXmlResource($topic_file_path);

            if (!empty($topic)) {
                $xpath = Cc1p3Convert::newxPath($topic, Cc1p3Convert::$forumns);

                $topic_title = $xpath->query('/dt:topic/dt:title');
                if ($topic_title->length > 0 && !empty($topic_title->item(0)->nodeValue)) {
                    $topic_title = $topic_title->item(0)->nodeValue;
                } else {
                    $topic_title = 'Untitled Topic';
                }

                $topic_text = $xpath->query('/dt:topic/dt:text');
                $topic_text = !empty($topic_text->item(0)->nodeValue) ? $this->updateSources($topic_text->item(0)->nodeValue, dirname($topic_file)) : '';
                $topic_text = !empty($topic_text) ? str_replace("%24", "\$", $this->includeTitles($topic_text)) : '';

                if (!empty($topic_title)) {
                    $topic_data['title'] = $topic_title;
                    $topic_data['description'] = $topic_text;
                }
            }

            $topic_attachments = $xpath->query('/dt:topic/dt:attachments/dt:attachment/@href');

            if ($topic_attachments->length > 0) {
                $attachment_html = '';

                foreach ($topic_attachments as $file) {
                    $attachment_html .= $this->generateAttachmentHtml($this->fullPath($file->nodeValue, '/'));
                }

                $topic_data['description'] = !empty($attachment_html) ? $topic_text.'<p>Attachments:</p>'.$attachment_html : $topic_text;
            }
        }

        return $topic_data;
    }

    private function generateAttachmentHtml($filename)
    {
        $images_extensions = ['gif', 'jpeg', 'jpg', 'jif', 'jfif', 'png', 'bmp'];

        $fileinfo = pathinfo($filename);

        if (in_array($fileinfo['extension'], $images_extensions)) {
            return '<img src="$@FILEPHP@$/'.$filename.'" title="'.$fileinfo['basename'].'" alt="'.$fileinfo['basename'].'" /><br />';
        } else {
            return '<a href="$@FILEPHP@$/'.$filename.'" title="'.$fileinfo['basename'].'" alt="'.$fileinfo['basename'].'">'.$fileinfo['basename'].'</a><br />';
        }

        return '';
    }
}
