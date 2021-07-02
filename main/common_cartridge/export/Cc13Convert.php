<?php
/* For licensing terms, see /license.txt */

class Cc13Convert
{
    
    public static function convert($packagedir, $outdir, $objCourse) {
        
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

            if (isset($resources['quiz'])) {
                $sectionid      = $count;
                $sectiontitle   = 'Quiz';
                $sectionpath    = '';
                $sequence = self::get_sequence($resources['quiz'], 0, 'quiz', $objCourse->info['code'], $resources['Exercise_Question']);                                                
                $sections[$sectionid] = array($sectiontitle, $sequence);
                $count++; 
            }
            
            if (isset($resources['document'])) {                                
                $sectionid      = $count;
                $sectiontitle   = 'Document';
                $sectionpath    = '';
                $sequence = self::get_sequence($resources['document'], 0, 'document', $objCourse->info['code']);                                                
                $sections[$sectionid] = array($sectiontitle, $sequence);
                $count++;                                
            }

            // We check the forum sections
            if (isset($resources['Forum_Category'])) {                
                foreach ($resources['Forum_Category'] as $fcategory) {
                    if (isset($fcategory->obj)) {
                        $objCategory = $fcategory->obj;                       
                        $sectionid    = $count;
                        $sectiontitle = $objCategory->cat_title;
                        $sectionpath  = '';
                        $sequence = self::get_sequence($resources['forum'], $objCategory->iid, 'forum');
                        $sections[$sectionid] = array($sectiontitle, $sequence);
                        $count++;
                    }
                }                                                
            }
            
            if (isset($resources['link'])) {                                
                $links = self::get_sequence($resources['link'], null, 'link');                   
                foreach($links as $categoryid => $sequence) {
                    $sectionid    = $count;
                    $sectionpath  = '';
                    if (isset($resources['Link_Category'][$categoryid])) {                                          
                        $sectiontitle = $resources['Link_Category'][$categoryid]->title;                        
                    }
                    else {
                        $sectiontitle = 'General';
                    }                    
                    $sections[$sectionid] = array($sectiontitle, $sequence);                    
                    $count++;
                }                                
            }
            
            if (isset($resources['wiki'])) {                
                $sectionid    = $count;
                $sectiontitle = 'Wiki';
                $sectionpath  = '';
                $sequence = self::get_sequence($resources['wiki'], 0, 'page');
                $sections[$sectionid] = array($sectiontitle, $sequence);
                $count++;
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
    
    
    protected static function get_sequence($objItems, $categoryid = null, $type = null, $coursecode = null, $itemQuestions = null) {        
        $sequence = [];
        
        if ($type == 'quiz') {
            
            foreach($objItems as $objItem) {                                
                if ($categoryid === 0) {
                    
                        $questions = [];                        
                        foreach ($objItem->obj->question_ids as $question_id) {
                            if (isset($itemQuestions[$question_id])) {
                                $questions[$question_id] = $itemQuestions[$question_id];
                            }
                        }
                                            
                        $sequence[$categoryid][$objItem->obj->iid] = [
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
            return $sequence[$categoryid];
            
        }
        
        if ($type == 'document') {
            foreach($objItems as $objItem) {                                
                if ($categoryid === 0) {
                        $sequence[$categoryid][$objItem->source_id] = [
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
            return $sequence[$categoryid];
        }
        if ($type == 'forum') {                        
            foreach($objItems as $objItem) {                                
                if ($categoryid == $objItem->obj->forum_category) {
                    $sequence[$categoryid][$objItem->obj->forum_id] = [
                        'title' => $objItem->obj->forum_title, 
                        'comment' => $objItem->obj->forum_comment, 
                        'cc_type' => 'forum',
                        'source_id' => $objItem->obj->iid
                    ];
                }                
            }  
            return $sequence[$categoryid];
        }
        
        if ($type == 'page') {
            foreach($objItems as $objItem) {                                
                if ($categoryid === 0) {
                    $sequence[$categoryid][$objItem->page_id] = [
                        'title' => $objItem->title, 
                        'comment' => $objItem->content, 
                        'cc_type' => 'page',
                        'source_id' => $objItem->page_id,
                        'reflink' => $objItem->reflink
                    ];
                }                
            }  
            return $sequence[$categoryid];
        }
        
        if ($type == 'link') {           
            if (!isset($categoryid)) {               
                $categories = [];
                foreach($objItems as $objItem) {                                                                    
                    $categories[$objItem->category_id] = self::get_sequence($objItems, $objItem->category_id, $type);
                }
                return $categories;
            }
            else {                
                foreach($objItems as $objItem) {                                
                    if ($categoryid == $objItem->category_id) {
                        $sequence[$categoryid][$objItem->source_id] = [
                            'title' => $objItem->title, 
                            'comment' => $objItem->description, 
                            'cc_type' => 'url',
                            'source_id' => $objItem->source_id,
                            'url' => $objItem->url,
                            'target' => $objItem->target
                        ];
                    }                
                }  
                return $sequence[$categoryid];                
            }
        }
        return false;    
    }
    
    protected static function process_sequence(CcIItem &$item, CcIManifest &$manifest, array $sequence, $packageroot, $outdir) {                
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
    
    
    protected static function item_indenter(CcIItem &$item, $level = 0) {
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

