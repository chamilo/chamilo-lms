<?php
/* For licensing terms, see /license.txt */

class Cc1p3Convert extends CcBase
{
 
    const CC_TYPE_FORUM              = 'imsdt_xmlv1p3';
    const CC_TYPE_QUIZ               = 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment';
    const CC_TYPE_QUESTION_BANK      = 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank';
    const CC_TYPE_WEBLINK            = 'imswl_xmlv1p3';
    const CC_TYPE_ASSOCIATED_CONTENT = 'associatedcontent/imscc_xmlv1p3/learning-application-resource';
    const CC_TYPE_BASICLTI           = 'imsbasiclti_xmlv1p3';

    public static $namespaces = array('imscc'    => 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1',
                                      'lomimscc' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/manifest',
                                      'lom'      => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/resource',
                                      'xsi'      => 'http://www.w3.org/2001/XMLSchema-instance',
                                      'cc'       => 'http://www.imsglobal.org/xsd/imsccv1p3/imsccauth_v1p1');

    public static $restypes = array('associatedcontent/imscc_xmlv1p3/learning-application-resource', 'webcontent');
    public static $forumns  = array('dt' => 'http://www.imsglobal.org/xsd/imsccv1p3/imsdt_v1p3');
    public static $quizns   = array('xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2');
    public static $resourcens = array('wl' => 'http://www.imsglobal.org/xsd/imsccv1p3/imswl_v1p3');
    public static $basicltins = array(
                                       'xmlns' => 'http://www.imsglobal.org/xsd/imslticc_v1p0',
                                       'blti'  => 'http://www.imsglobal.org/xsd/imsbasiclti_v1p0',
                                       'lticm' => 'http://www.imsglobal.org/xsd/imslticm_v1p0',
                                       'lticp' => 'http://www.imsglobal.org/xsd/imslticp_v1p0'
                                      );

    public function __construct($path_to_manifest) {
        parent::__construct($path_to_manifest);
    }

    public function generate_import_data() {
                
        $xpath = static::newx_path(static::$manifest, static::$namespaces);
        $items = $xpath->query('/imscc:manifest/imscc:organizations/imscc:organization/imscc:item | /imscc:manifest/imscc:resources/imscc:resource[@type="' . static::CC_TYPE_QUESTION_BANK . '"]');                
        $this->create_instances($items);

        $resources = new Cc13Resource();
        $forums = new Cc13Forum();
        $quiz = new Cc13Quiz();

        $documentValues = $resources->generate_data('document');
        $linkValues = $resources->generate_data('link');
        $forumValues =  $forums->generate_data();
        $quizValues = $quiz->generate_data();

        
        if (!empty($forums)) {
            $saved = $forums->store_forums($forumValues);
        }
        
        if (!empty($quizValues)) {
            $saved = $quiz->store_quizzes($quizValues);
        }
        
        if (!empty($documentValues)) {
            $saved = $resources->store_documents($documentValues, static::$path_to_manifest_folder);
        }
        
        if (!empty($linkValues)) {
            $saved = $resources->store_links($linkValues);
        }
        
    }
    

}
