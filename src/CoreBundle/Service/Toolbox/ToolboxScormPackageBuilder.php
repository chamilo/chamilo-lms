<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Toolbox;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const ENT_XML1;

final readonly class ToolboxScormPackageBuilder
{
    /**
     * @return array{file: UploadedFile, path: string}
     */
    public function build(
        string $title,
        string $html,
        string $css,
        string $javascript,
        int $toolboxId,
        int $versionNumber,
    ): array {
        $path = tempnam(sys_get_temp_dir(), 'chamilo_toolbox_');
        if (false === $path) {
            throw new RuntimeException('The Toolbox SCORM package could not be prepared.');
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            @unlink($path);

            throw new RuntimeException('The Toolbox SCORM package could not be created.');
        }

        try {
            $zip->addFromString('imsmanifest.xml', $this->manifest($title, $toolboxId, $versionNumber));
            $zip->addFromString('index.html', $this->indexHtml($title, $html, $css, $javascript));
        } finally {
            $zip->close();
        }

        $filename = \sprintf('toolbox-%d-v%d.zip', $toolboxId, $versionNumber);

        return [
            'file' => new UploadedFile($path, $filename, 'application/zip', null, true),
            'path' => $path,
        ];
    }

    private function manifest(string $title, int $toolboxId, int $versionNumber): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
        $identifier = \sprintf('TOOLBOX_%d_V%d', $toolboxId, $versionNumber);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="{$identifier}" version="1.0"
 xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2"
 xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <metadata>
    <schema>ADL SCORM</schema>
    <schemaversion>1.2</schemaversion>
  </metadata>
  <organizations default="ORG_1">
    <organization identifier="ORG_1">
      <title>{$safeTitle}</title>
      <item identifier="ITEM_1" identifierref="RES_1">
        <title>{$safeTitle}</title>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="RES_1" type="webcontent" adlcp:scormtype="sco" href="index.html">
      <file href="index.html" />
    </resource>
  </resources>
</manifest>
XML;
    }

    private function indexHtml(string $title, string $html, string $css, string $javascript): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $bridge = $this->bridgeScript();

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$safeTitle}</title>
  <style>
    *,*::before,*::after{box-sizing:border-box}
    html,body{margin:0;min-height:100%;font-family:Arial,Helvetica,sans-serif;background:#fff;color:#1f2937}
    body{min-height:100vh}
    button,input,select,textarea{font:inherit}
{$css}
  </style>
</head>
<body>
{$html}
<script>
{$bridge}
</script>
<script>
{$javascript}
</script>
</body>
</html>
HTML;
    }

    private function bridgeScript(): string
    {
        return <<<'JS'
(() => {
  "use strict";

  const changed = new Set();
  const sessionStartedAt = Date.now();
  let initialized = false;
  let lastError = "0";

  function decodeInitialValues() {
    try {
      const params = new URLSearchParams(window.location.hash.replace(/^#/, ""));
      const encoded = params.get("chamilo_scorm_state");
      if (!encoded) return {};
      const binary = window.atob(encoded);
      const bytes = Uint8Array.from(binary, (character) => character.charCodeAt(0));
      const json = new TextDecoder("utf-8").decode(bytes);
      const parsed = JSON.parse(json);
      return parsed && typeof parsed === "object" ? parsed : {};
    } catch (_) {
      return {};
    }
  }

  const values = decodeInitialValues();

  function formatSessionTime() {
    const elapsed = Math.max(0, Date.now() - sessionStartedAt);
    const totalSeconds = elapsed / 1000;
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = (totalSeconds % 60).toFixed(2).padStart(5, "0");
    return `${String(hours).padStart(4, "0")}:${String(minutes).padStart(2, "0")}:${seconds}`;
  }

  function postCommit(terminated, reason) {
    values["cmi.core.session_time"] = formatSessionTime();
    changed.add("cmi.core.session_time");
    window.parent.postMessage({
      type: "chamilo-toolbox-scorm-commit",
      values: { ...values },
      changedKeys: Array.from(changed),
      terminated: Boolean(terminated),
      reason: String(reason || "commit"),
    }, "*");
    changed.clear();
  }

  const api = {
    LMSInitialize() {
      initialized = true;
      lastError = "0";
      return "true";
    },
    LMSFinish() {
      if (!initialized) return "false";
      postCommit(true, "finish");
      initialized = false;
      lastError = "0";
      return "true";
    },
    LMSGetValue(key) {
      lastError = "0";
      return Object.prototype.hasOwnProperty.call(values, key) ? String(values[key] ?? "") : "";
    },
    LMSSetValue(key, value) {
      if (!initialized) return "false";
      values[String(key)] = String(value ?? "");
      changed.add(String(key));
      lastError = "0";
      return "true";
    },
    LMSCommit() {
      if (!initialized) return "false";
      postCommit(false, "commit");
      lastError = "0";
      return "true";
    },
    LMSGetLastError() {
      return lastError;
    },
    LMSGetErrorString(code) {
      return String(code || "0") === "0" ? "No error" : "SCORM error";
    },
    LMSGetDiagnostic() {
      return "";
    },
  };

  window.API = api;
  window.api = api;

  window.addEventListener("pagehide", () => {
    if (initialized && changed.size > 0) {
      postCommit(false, "pagehide");
    }
  });
})();
JS;
    }
}
