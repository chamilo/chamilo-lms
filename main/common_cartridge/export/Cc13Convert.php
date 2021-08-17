<?php
/* For licensing terms, see /license.txt */

class Cc13Convert
{

    public static function convert($packagedir, $outdir, $objCourse)
    {

        $dir = realpath($packagedir);
        if (empty($dir)) {
            throw new InvalidArgumentException('Directory does not exist!');
        }
        $odir = realpath($outdir);
        if (empty($odir)) {
            throw new InvalidArgumentException('Directory does not exist!');
        }

        if (!empty($objCourse)) {

            //Initialize the manifest metadata class
            $meta = new CcMetadataManifest();

            //Package metadata
            $metageneral = new cc_metadata_general();
            $metageneral->set_language($objCourse->info['language']);
            $metageneral->set_title($objCourse->info['title'], $objCourse->info['language']);
            $metageneral->set_description('', $objCourse->info['language']);
            $metageneral->set_catalog('category');
            $metageneral->set_entry($objCourse->info['categoryName']);
            $meta->add_metadata_general($metageneral);

            // Create the manifest
            $manifest = new CcManifest();

            $manifest->add_metadata_manifest($meta);

            $organization = null;

            //Get the course structure - this will be transformed into organization
            //Step 1 - Get the list and order of sections/topics

            $count = 1;
            $sections = [];
            $resources = $objCourse->resources;

            // We check the quiz sections
            if (isset($resources['quiz'])) {
                $quizSections = self::get_item_sections($resources['quiz'], 'quiz',$count, $objCourse->info['code'], $resources['Exercise_Question']);
                $sections = array_merge($sections, $quizSections);
            }

            // We check the document sections
            if (isset($resources['document'])) {
                $documentSections = self::get_item_sections($resources['document'], 'document', $count, $objCourse->info['code']);
                $sections = array_merge($sections, $documentSections);
            }

            // We check the wiki sections
            if (isset($resources['wiki'])) {
                $wikiSections = self::get_item_sections($resources['wiki'], 'wiki', $count, $objCourse->info['code']);
                $sections = array_merge($sections, $wikiSections);
            }

            // We check the forum sections
            if (isset($resources['forum'])) {
                $forumSections = self::get_item_sections($resources['forum'], 'forum', $count, $objCourse->info['code'], $resources['Forum_Category']);
                $sections = array_merge($sections, $forumSections);
            }

            // We check the link sections
            if (isset($resources['link'])) {
                $linkSections = self::get_item_sections($resources['link'], 'link',$count, $objCourse->info['code'], $resources['Link_Category']);
                $sections = array_merge($sections, $linkSections);
            }

            //organization title
            $organization = new CcOrganization();
            foreach ($sections as $sectionid => $values) {
                $item = new cc_item();
                $item->title = $values[0];
                self::process_sequence($item, $manifest, $values[1], $dir, $odir);
                $organization->add_item($item);
            }
            $manifest->put_nodes();

            if (!empty($organization)) {
                $manifest->add_new_organization($organization);
            }

            $manifestpath = $outdir.DIRECTORY_SEPARATOR.'imsmanifest.xml';
            $saved = $manifest->saveTo($manifestpath);
            return $saved;
        }
        return false;
    }

    protected static function get_item_sections($itemData, $itemType, &$count, $courseCode, $itmesExtraData = null)
    {

        $sections = [];
        switch ($itemType) {
            case 'quiz':
            case 'document':
            case 'wiki':
                $convertType = $itemType;
                if ($itemType == 'wiki') {
                    $convertType = 'Page';
                }
                $sectionid      = $count;
                $sectiontitle   = ucfirst($itemType);
                $sequence = self::get_sequence($itemData, 0, $convertType, $courseCode, $itmesExtraData);
                $sections[$sectionid] = array($sectiontitle, $sequence);
                $count++;
                break;
            case 'link':
                $links = self::get_sequence($itemData, null, $itemType);
                foreach($links as $categoryId => $sequence) {
                    $sectionid    = $count;
                    if (isset($itmesExtraData[$categoryId])) {
                        $sectiontitle = $itmesExtraData[$categoryId]->title;
                    }
                    else {
                        $sectiontitle = 'General';
                    }
                    $sections[$sectionid] = array($sectiontitle, $sequence);
                    $count++;
                }
                break;
            case 'forum':
                if (isset($itmesExtraData)) {
                    foreach ($itmesExtraData as $fcategory) {
                        if (isset($fcategory->obj)) {
                            $objCategory = $fcategory->obj;
                            $sectionid    = $count;
                            $sectiontitle = $objCategory->cat_title;
                            $sequence = self::get_sequence($itemData, $objCategory->iid, $itemType);
                            $sections[$sectionid] = array($sectiontitle, $sequence);
                            $count++;
                        }
                    }
                }
                break;
        }
        return $sections;
    }

    protected static function get_sequence($objItems, $categoryId = null, $itemType = null, $coursecode = null, $itemQuestions = null)
    {

        $sequences = [];
        switch ($itemType) {
            case 'quiz':
                $sequence = [];
                foreach($objItems as $objItem) {
                    if ($categoryId === 0) {
                        $questions = [];
                        foreach ($objItem->obj->question_ids as $questionId) {
                            if (isset($itemQuestions[$questionId])) {
                                $questions[$questionId] = $itemQuestions[$questionId];
                            }
                        }
                        $sequence[$categoryId][$objItem->obj->iid] = [
                            'title' => $objItem->obj->title,
                            'comment' => $objItem->obj->description,
                            'cc_type' => 'quiz',
                            'source_id' => $objItem->obj->iid,
                            'questions' => $questions,
                            'max_attempt' => $objItem->obj->max_attempt,
                            'expired_time' => $objItem->obj->expired_time,
                            'pass_percentage' => $objItem->obj->pass_percentage,
                            'random_answers' => $objItem->obj->random_answers,
                            'course_code' => $coursecode
                        ];
                    }
                }
                $sequences = $sequence[$categoryId];
                break;
            case 'document':
                $sequence = [];
                foreach($objItems as $objItem) {
                    if ($categoryId === 0) {
                        $sequence[$categoryId][$objItem->source_id] = [
                            'title' => $objItem->title,
                            'comment' => $objItem->comment,
                            'cc_type' => ($objItem->file_type == 'folder'?'folder':'resource'),
                            'source_id' => $objItem->source_id,
                            'path' => $objItem->path,
                            'file_type' => $objItem->file_type,
                            'course_code' => $coursecode
                        ];

                    }
                }
                $sequences = $sequence[$categoryId];
                break;
            case 'forum':
                foreach($objItems as $objItem) {
                    if ($categoryId == $objItem->obj->forum_category) {
                        $sequence[$categoryId][$objItem->obj->forum_id] = [
                            'title' => $objItem->obj->forum_title,
                            'comment' => $objItem->obj->forum_comment,
                            'cc_type' => 'forum',
                            'source_id' => $objItem->obj->iid
                        ];
                    }
                }
                $sequences =  $sequence[$categoryId];
                break;
            case 'page':
                foreach($objItems as $objItem) {
                    if ($categoryId === 0) {
                        $sequence[$categoryId][$objItem->page_id] = [
                            'title' => $objItem->title,
                            'comment' => $objItem->content,
                            'cc_type' => 'page',
                            'source_id' => $objItem->page_id,
                            'reflink' => $objItem->reflink
                        ];
                    }
                }
                $sequences =  $sequence[$categoryId];
                break;
            case 'link':
                if (!isset($categoryId)) {
                    $categories = [];
                    foreach($objItems as $objItem) {
                        $categories[$objItem->category_id] = self::get_sequence($objItems, $objItem->category_id, $itemType);
                    }
                    $sequences = $categories;
                }
                else {
                    foreach($objItems as $objItem) {
                        if ($categoryId == $objItem->category_id) {
                            $sequence[$categoryId][$objItem->source_id] = [
                                'title' => $objItem->title,
                                'comment' => $objItem->description,
                                'cc_type' => 'url',
                                'source_id' => $objItem->source_id,
                                'url' => $objItem->url,
                                'target' => $objItem->target
                            ];
                        }
                    }
                    $sequences = $sequence[$categoryId];
                }
                break;
        }
        return $sequences;
    }

    protected static function process_sequence(CcIItem &$item, CcIManifest &$manifest, array $sequence, $packageroot, $outdir)
    {
        if (!empty($sequence)) {
            foreach ($sequence as $seq) {
                $activity_type = ucfirst($seq['cc_type']);
                $activity_indentation = 0;
                $aitem = self::item_indenter($item, $activity_indentation);
                $caller = "CcConverter{$activity_type}";
                if (class_exists($caller)) {
                    $obj = new $caller($aitem, $manifest, $packageroot, $path);
                    if (!$obj->convert($outdir, $seq)) {
                        throw new RuntimeException("failed to convert {$activity_type}");
                    }
                }
            }
        }
    }


    protected static function item_indenter(CcIItem &$item, $level = 0)
    {
        $indent = (int)$level;
        $indent = ($indent) <= 0 ? 0 : $indent;
        $nprev = null;
        $nfirst = null;
        for ($pos = 0, $size = $indent; $pos < $size; $pos++) {
            $nitem = new cc_item();
            $nitem->title = '';
            if (empty($nfirst)) {
                $nfirst = $nitem;
            }
            if (!empty($nprev)) {
                $nprev->add_child_item($nitem);
            }
            $nprev = $nitem;
        }
        $result = $item;
        if (!empty($nfirst)) {
            $item->add_child_item($nfirst);
            $result = $nprev;
        }
        return $result;
    }

}

