<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Onlyoffice\DocsIntegrationSdk\Service\Request\RequestService;

class OnlyofficeAppRequests extends RequestService
{
    /**
     * File url to test convert service.
     *
     * @var string
     */
    private $convertFileUrl;
    private $convertFilePath;

    public function __construct($settingsManager, $httpClient, $jwtManager)
    {
        parent::__construct($settingsManager, $httpClient, $jwtManager);
    }

    public function getFileUrlForConvert()
    {
        $data = [
            'type' => 'empty',
            'courseId' => api_get_course_int_id(),
            'userId' => api_get_user_id(),
            'sessionId' => api_get_session_id(),
        ];
        $hashUrl = $this->jwtManager->getHash($data);

        return api_get_path(WEB_PLUGIN_PATH).'onlyoffice/callback.php?hash='.$hashUrl;
    }
}
