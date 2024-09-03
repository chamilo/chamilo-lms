<?php
/* For licensing terms, see /license.txt */

class Cc1p3Convert extends CcBase
{
    public const CC_TYPE_FORUM = 'imsdt_xmlv1p3';
    public const CC_TYPE_QUIZ = 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment';
    public const CC_TYPE_QUESTION_BANK = 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank';
    public const CC_TYPE_WEBLINK = 'imswl_xmlv1p3';
    public const CC_TYPE_ASSOCIATED_CONTENT = 'associatedcontent/imscc_xmlv1p3/learning-application-resource';
    public const CC_TYPE_WEBCONTENT = 'webcontent';
    public const CC_TYPE_BASICLTI = 'imsbasiclti_xmlv1p3';

    public static $namespaces = ['imscc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1',
                                      'lomimscc' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/manifest',
                                      'lom' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/resource',
                                      'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                                      'cc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imsccauth_v1p1', ];

    public static $restypes = ['associatedcontent/imscc_xmlv1p3/learning-application-resource', 'webcontent'];
    public static $forumns = ['dt' => 'http://www.imsglobal.org/xsd/imsccv1p3/imsdt_v1p3'];
    public static $quizns = ['xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2'];
    public static $resourcens = ['wl' => 'http://www.imsglobal.org/xsd/imsccv1p3/imswl_v1p3'];
    public static $basicltins = [
                                       'xmlns' => 'http://www.imsglobal.org/xsd/imslticc_v1p0',
                                       'blti' => 'http://www.imsglobal.org/xsd/imsbasiclti_v1p0',
                                       'lticm' => 'http://www.imsglobal.org/xsd/imslticm_v1p0',
                                       'lticp' => 'http://www.imsglobal.org/xsd/imslticp_v1p0',
                                      ];

    public function __construct($path_to_manifest)
    {
        parent::__construct($path_to_manifest);
    }

    /**
     * Scan the imsmanifest.xml structure to find elements to import to documents, links, forums, quizzes.
     */
    public function generateImportData(): void
    {
        $countInstances = 0;
        $xpath = static::newxPath(static::$manifest, static::$namespaces);
        // Scan for detached resources of type 'webcontent'
        $resources = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@type="'.static::CC_TYPE_WEBCONTENT.'"]');
        $this->createInstances($resources, 0, $countInstances);

        // Scan for organization items or resources that are tests (question banks)
        $items = $xpath->query('/imscc:manifest/imscc:organizations/imscc:organization/imscc:item | /imscc:manifest/imscc:resources/imscc:resource[@type="'.static::CC_TYPE_QUESTION_BANK.'"]');
        $this->createInstances($items, 0, $countInstances);

        $resources = new Cc13Resource();
        $forums = new Cc13Forum();
        $quiz = new Cc13Quiz();

        // Get the embedded XML files describing resources to import
        $documentValues = $resources->generateData('document');
        $linkValues = $resources->generateData('link');
        $forumValues = $forums->generateData();
        $quizValues = $quiz->generateData();

        if (!empty($forums) or !empty($quizValues) or !empty($documentValues)) {
            $courseInfo = api_get_course_info();
            $sessionId = api_get_session_id();
            $groupId = api_get_group_id();
            $documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/document';

            create_unexisting_directory(
                $courseInfo,
                api_get_user_id(),
                $sessionId,
                $groupId,
                null,
                $documentPath,
                '/commoncartridge',
                'Common Cartridge folder',
                0
            );
        }

        // Import the resources, by type
        if (!empty($forums)) {
            $saved = $forums->storeForums($forumValues);
        }
        if (!empty($quizValues)) {
            $saved = $quiz->storeQuizzes($quizValues);
        }
        if (!empty($documentValues)) {
            $saved = $resources->storeDocuments($documentValues, static::$pathToManifestFolder);
        }
        if (!empty($linkValues)) {
            $saved = $resources->storeLinks($linkValues);
        }
    }
}
