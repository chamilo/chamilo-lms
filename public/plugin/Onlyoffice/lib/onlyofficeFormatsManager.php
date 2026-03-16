<?php

use Onlyoffice\DocsIntegrationSdk\Manager\Formats\FormatsManager;
use Onlyoffice\DocsIntegrationSdk\Util\CommonError;

class OnlyofficeFormatsManager extends FormatsManager
{
    public function __construct()
    {
        $formats = self::getFormats();
        $this->formatsList = self::buildNamedFormatsArray($formats);
    }

    private static function getFormats(): array
    {
        foreach (self::getCandidatePaths() as $path) {
            if (!is_string($path) || '' === $path) {
                continue;
            }

            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $contents = @file_get_contents($path);
            if (false === $contents || '' === trim($contents)) {
                continue;
            }

            $decoded = json_decode($contents);
            if (!empty($decoded) && is_array($decoded)) {
                return $decoded;
            }

            error_log('[OnlyOffice] Invalid formats JSON in: '.$path);
        }

        throw new \Exception(CommonError::message(CommonError::EMPTY_FORMATS_ASSET));
    }

    private static function getCandidatePaths(): array
    {
        $pluginRoot = dirname(__DIR__);
        $projectRoot = dirname(__DIR__, 4);

        return [
            // Plugin-local vendor paths.
            $pluginRoot
            .DIRECTORY_SEPARATOR.'vendor'
            .DIRECTORY_SEPARATOR.'onlyoffice'
            .DIRECTORY_SEPARATOR.'docs-integration-sdk'
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.'document-formats'
            .DIRECTORY_SEPARATOR.'onlyoffice-docs-formats.json',

            $pluginRoot
            .DIRECTORY_SEPARATOR.'vendor'
            .DIRECTORY_SEPARATOR.'onlyoffice'
            .DIRECTORY_SEPARATOR.'docs-integration-sdk'
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.'document-formats'
            .DIRECTORY_SEPARATOR.'onlyoffice-docs-formats.txt',

            // Project root vendor paths.
            $projectRoot
            .DIRECTORY_SEPARATOR.'vendor'
            .DIRECTORY_SEPARATOR.'onlyoffice'
            .DIRECTORY_SEPARATOR.'docs-integration-sdk'
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.'document-formats'
            .DIRECTORY_SEPARATOR.'onlyoffice-docs-formats.json',

            $projectRoot
            .DIRECTORY_SEPARATOR.'vendor'
            .DIRECTORY_SEPARATOR.'onlyoffice'
            .DIRECTORY_SEPARATOR.'docs-integration-sdk'
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.'document-formats'
            .DIRECTORY_SEPARATOR.'onlyoffice-docs-formats.txt',

            // Optional fallback if assets were copied into plugin resources.
            $pluginRoot
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.'document-formats'
            .DIRECTORY_SEPARATOR.'onlyoffice-docs-formats.json',

            $pluginRoot
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.'document-formats'
            .DIRECTORY_SEPARATOR.'onlyoffice-docs-formats.txt',
        ];
    }
}
