<?php

namespace Onlyoffice\DocsIntegrationSdk\Manager\Document;

/**
 *
 * (c) Copyright Ascensio System SIA 2024
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
 *
 */

interface DocumentManagerInterface
{
    /**
    * Generates a unique document identifier used by the service to recognize the document.
    *
    * @param string $fileId The file ID.
    * @param bool $embedded Specifies if the editor is opened in the embedded mode (true) or not (false).
    * @throws Exception If the processing fails unexpectedly.
    * @return string The unique document identifier.
    */
    public function getDocumentKey(string $fileId, bool $embedded);

    /**
    * Returns the document name by file ID.
    *
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    * @return string Document name
    */
    public function getDocumentName(string $fileId);

    /**
    * Returns the locale by lang code
    *
    * @throws Exception If the processing fails unexpectedly.
    * @return string Locale
    */
    public static function getLangMapping();

    /**
    * Returns the URL to download a file with the ID specified in the request.
    *
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    * @return string The URL to download a file.
    */
    public function getFileUrl(string $fileId);

    /**
    * Returns the URL to the callback handler.
    *
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    * @return string The URL to the callback handler.
    */
    public function getCallbackUrl(string $fileId);

    /**
    * Returns the URL to the location folder of a file with the ID specified in the request.
    *
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    * @return string The URL to the file location folder.
    */
    public function getGobackUrl(string $fileId);

    /**
    * Returns the URL to create a new file with the ID specified in the request.
    *
    * @param string $fileId The file ID.
    * @throws Exception If the processing fails unexpectedly.
    * @return string The URL to create a new file.
    */
    public function getCreateUrl(string $fileId);

    /**
    * Returns the path of empty file from assets/document-templates by extension.
    *
    * @param string $fileExt Extension of the file.
    * @throws Exception If the processing fails unexpectedly.
    * @return string The URL to create a new file.
    */
    public function getEmptyTemplate(string $fileExt);

    /**
    * Returns temporary file info (path and url as array)
    *
    * @throws Exception If the processing fails unexpectedly.
    * @return array Temporary file info.
    */
    public function getTempFile();

    /**
    * Return file type by extension
    *
    * @param string $extension Extension of the file.
    * @throws Exception If the processing fails unexpectedly.
    * @return string File type
    */
    public function getDocType(string $extension);

    /**
    * Return actions for file by extension
    *
    * @param string $extension Extension of the file.
    * @throws Exception If the processing fails unexpectedly.
    * @return array Actions for file by extension
    */
    public function getDocActions(string $extension);

    /**
    * Return convert extensions for file by current extension
    *
    * @param string $extension Extension of the file.
    * @throws Exception If the processing fails unexpectedly.
    * @return array Convert extensions for file by current extension
    */
    public function getDocConvert(string $extension);


    /**
    * Return array of all mime types for file by extension
    *
    * @param string $extension Extension of the file.
    * @throws Exception If the processing fails unexpectedly.
    * @return array All mime types for file by extension
    */
    public function getDocMimes(string $extension);

    /**
    * Return one mime type of the file by extension
    *
    * @param string $extension Extension of the file.
    * @throws Exception If the processing fails unexpectedly.
    * @return string Mime type of the file by extension
    */
    public function getDocMimeType(string $extension);

    /**
    * Returns the file base name without the full path and extension.
    *
    * @param string $filePath The file path.
    * @return string The file name without the extension or null if the file name is empty.
    */
    public function getBaseName(string $filePath);

    /**
    * Returns the file extension.
    *
    * @param string $filePath The file path.
    * @return string The file extension.
    */
    public function getExt(string $filePath);

    /**
    * Returns the file name.
    *
    * @param string $filePath The file path.
    * @return string The file name.
    */
    public function getFileName(string $filePath);

    /**
    * Determines whether a document with a name specified in the request is viewable.
    *
    * @param string $filePath The file path.
    * @return bool True if the document is viewable.
    */
    public function isDocumentViewable(string $filePath);

    /**
    * Determines whether a document with a name specified in the request is editable.
    *
    * @param string $filePath The file path.
    * @return bool True if the document is editable.
    */
    public function isDocumentEditable(string $filePath);

    /**
    * Determines whether a document with a name specified in the request is convertable.
    *
    * @param string $filePath The file path.
    * @return bool True if the document is convertable.
    */
    public function isDocumentConvertable(string $filePath);

    /**
    * Determines whether a document with a name specified in the request is fillable.
    *
    * @param string $filePath The file path.
    * @return bool True if the document is fillable.
    */
    public function isDocumentFillable(string $filePath);

    /**
    * Translation key to a supported form
    *
    * @param string $expectedKey The expected key for document.
    * @return string Generated key
    */
    public static function generateRevisionId(string $expectedKey);
}
