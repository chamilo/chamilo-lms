<?php

declare(strict_types=1);

$options_studio = '';
$options_studio_cdt = '';

if (isset($_SESSION['options-studio'])) {
    $options_studio = (string) $_SESSION['options-studio'];
    $options_studio_cdt = (string) $_SESSION['options-studio-cdt'];
}

if ('no options file !' == $options_studio || '' == $options_studio) {
    $fileNameOpts = api_get_folder_options().'options.xml';

    if (file_exists($fileNameOpts)) {
        $xml = simplexml_load_file($fileNameOpts);

        $VactiveLogsCreator = $xml->param[0]->activeLogsCreator;
        if (1 == (int) $VactiveLogsCreator) {
            $options_studio .= (string) 'ALC;';
        }

        $VactiveLogsLearning = $xml->param[0]->activeLogsLearning;
        if (1 == (int) $VactiveLogsLearning) {
            $options_studio .= (string) 'ALL;';
        }

        $VdisplayTemplateArea = $xml->param[0]->displayTemplateArea;
        if (1 == (int) $VdisplayTemplateArea) {
            $options_studio .= (string) 'DTA;';
        }

        $VonlyUserTemplates = $xml->param[0]->onlyUserTemplates;
        if (1 == (int) $VonlyUserTemplates) {
            $options_studio .= (string) 'OUT;';
        }

        $VcustomDefaultTemplates = $xml->param[0]->customDefaultTemplates;
        if (1 == (int) $VcustomDefaultTemplates) {
            $options_studio .= (string) 'CDT;';
            $VlistDefaultTemplates = $xml->param[0]->listDefaultTemplates;
            $_SESSION['options-studio-cdt'] = (string) $VlistDefaultTemplates;
        } else {
            $_SESSION['options-studio-cdt'] = '';
        }

        $_SESSION['options-studio'] = (string) $options_studio;
    } else {
        $options_studio = 'no options file !';
        $options_studio_cdt = '';
    }
}
