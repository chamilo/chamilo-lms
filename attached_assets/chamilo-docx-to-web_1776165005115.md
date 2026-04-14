---
name: chamilo-docx-to-web
description: >
  Converte um arquivo .docx (Word) formatado com capa, sumário e estrutura
  de títulos/subtítulos em uma página web responsiva dentro da aplicação
  Tannus/Chamilo. A capa vira a hero section, o sumário vira menu lateral
  com scrollspy, e o conteúdo é renderizado com tipografia corporativa
  minimalista e layout responsivo para desktop e mobile.
  Ative quando o usuário pedir para importar, converter ou publicar
  um documento Word como página no sistema.
---

# Docx → Página Web Responsiva — Tannus / Chamilo

## Visão Geral da Feature

Um arquivo `.docx` estruturado é convertido em uma página web com:
- **Hero (testeira)** — extraída da capa do documento
- **Menu lateral sticky com scrollspy** — gerado do sumário/headings
- **Conteúdo principal** — títulos H1–H4, parágrafos, imagens inline, tabelas responsivas
- **Layout responsivo** — desktop (sidebar + conteúdo) e mobile (menu hamburguer/drawer)

---

## Stack Técnica Utilizada

| Camada | Tecnologia |
|---|---|
| Conversão .docx → HTML | `mammoth.js` (Node) ou `mammoth` (Python) |
| Extração de metadados de capa | `python-docx` ou `mammoth` style map |
| Backend Symfony | Controller + Service + Twig |
| Upload | Symfony `UploadedFile` + validação MIME |
| Scrollspy | `IntersectionObserver` API (vanilla JS, sem jQuery) |
| Sidebar sticky | CSS `position: sticky` |
| Estilo | CSS custom properties — design corporativo minimalista |
| Responsividade | CSS Grid + media queries mobile-first |

---

## Verificações Obrigatórias Antes de Iniciar

```bash
# 1. Verificar se mammoth está disponível
node -e "require('mammoth'); console.log('mammoth OK')" 2>/dev/null \
  || php -r "echo class_exists('PhpOffice\PhpWord\PhpWord') ? 'phpword OK' : 'AUSENTE';" 2>/dev/null \
  || echo "Instalar: npm install mammoth OU composer require phpoffice/phpword"

# 2. Verificar diretório de uploads
ls -la public/uploads/ 2>/dev/null || echo "Criar: mkdir -p public/uploads/documents"

# 3. Confirmar que Node está disponível (para mammoth.js)
node -v && npm list mammoth 2>/dev/null

# 4. Confirmar permissões de escrita
touch public/uploads/documents/.write_test && rm public/uploads/documents/.write_test \
  && echo "Permissão OK" || echo "ERRO: sem permissão de escrita"
```

**Se qualquer verificação falhar: pare e informe antes de continuar.**

---

## Arquitetura da Feature

```
src/
├── Controller/
│   └── DocumentPageController.php     ← Upload + render da página
├── Service/
│   └── DocxConverterService.php       ← Lógica de conversão
└── templates/
    └── document_page/
        ├── upload.html.twig            ← Formulário de upload
        └── view.html.twig              ← Página gerada (hero + sidebar + conteúdo)

public/
└── uploads/
    └── documents/                      ← .docx e imagens extraídas (fora do webroot não é opção aqui)

assets/
└── styles/
    └── document-page.css               ← Estilos da página gerada
```

---

## Passo 1 — Instalação das Dependências

### Opção A: mammoth via Node (preferida se Node disponível)
```bash
npm install mammoth
# Verificar instalação
node -e "const m = require('mammoth'); console.log('OK');"
```

### Opção B: mammoth via Python
```bash
pip install mammoth
python3 -c "import mammoth; print('OK')"
```

### Opção C: PHPWord (puro PHP — sem Node/Python)
```bash
composer require phpoffice/phpword
php -r "require 'vendor/autoload.php'; echo class_exists('PhpOffice\PhpWord\PhpWord') ? 'OK' : 'FALHOU';"
```

**Registre no DEVELOPMENT_LOG.md qual opção foi instalada e por quê.**

---

## Passo 2 — Service de Conversão

### `src/Service/DocxConverterService.php`

```php
<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DocxConverterService
{
    public function __construct(
        private string $uploadDir,
        private string $projectDir
    ) {}

    /**
     * Converte .docx para estrutura de dados da página.
     * Retorna array com: title, subtitle, coverBg, html, headings[]
     */
    public function convert(string $docxPath): array
    {
        // Verificar que o arquivo existe antes de qualquer operação
        if (!file_exists($docxPath)) {
            throw new \RuntimeException("Arquivo não encontrado: {$docxPath}");
        }

        $outputDir = dirname($docxPath) . '/images_' . basename($docxPath, '.docx');
        @mkdir($outputDir, 0755, true);

        $html = $this->runMammoth($docxPath, $outputDir);

        return [
            'title'    => $this->extractTitle($html),
            'subtitle' => $this->extractSubtitle($html),
            'coverBg'  => $this->extractCoverImage($html),
            'html'     => $this->cleanAndWrapSections($html),
            'headings' => $this->extractHeadings($html),
        ];
    }

    private function runMammoth(string $docxPath, string $outputDir): string
    {
        // Tentar Node mammoth primeiro
        if ($this->commandExists('node')) {
            $scriptPath = $this->projectDir . '/scripts/mammoth_convert.js';
            $this->ensureMammothScript($scriptPath);

            $process = new Process([
                'node', $scriptPath,
                '--docx', $docxPath,
                '--output-dir', $outputDir
            ]);
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful()) {
                return $process->getOutput();
            }
        }

        // Fallback: Python mammoth
        if ($this->commandExists('python3')) {
            $process = new Process([
                'python3', '-c',
                "import mammoth, sys, json; " .
                "result = mammoth.convert_to_html(open(sys.argv[1],'rb'), " .
                "convert_image=mammoth.images.img_element(lambda image: " .
                "{'src': 'data:' + image.content_type + ';base64,' + " .
                "__import__('base64').b64encode(image.read()).decode()})); " .
                "print(result.value)",
                $docxPath
            ]);
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful()) {
                return $process->getOutput();
            }
        }

        throw new \RuntimeException(
            'Nenhum conversor disponível. Instale mammoth (npm) ou mammoth (pip).'
        );
    }

    private function ensureMammothScript(string $path): void
    {
        if (file_exists($path)) return;

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, <<<'JS'
const mammoth = require('mammoth');
const args = process.argv.slice(2);
const docxPath = args[args.indexOf('--docx') + 1];
const outputDir = args[args.indexOf('--output-dir') + 1];

const styleMap = [
    "p[style-name='Heading 1'] => h1.doc-h1:fresh",
    "p[style-name='Heading 2'] => h2.doc-h2:fresh",
    "p[style-name='Heading 3'] => h3.doc-h3:fresh",
    "p[style-name='Heading 4'] => h4.doc-h4:fresh",
    "p[style-name='Title'] => h1.doc-cover-title:fresh",
    "p[style-name='Subtitle'] => p.doc-cover-subtitle:fresh",
    "p[style-name='Caption'] => figcaption:fresh",
];

mammoth.convertToHtml(
    { path: docxPath },
    {
        styleMap,
        convertImage: mammoth.images.imgElement(function(image) {
            return image.read('base64').then(function(imageBase64) {
                return { src: 'data:' + image.contentType + ';base64,' + imageBase64 };
            });
        })
    }
)
.then(result => {
    process.stdout.write(result.value);
    if (result.messages.length > 0) {
        process.stderr.write(JSON.stringify(result.messages));
    }
})
.catch(err => {
    process.stderr.write(err.message);
    process.exit(1);
});
JS
        );
    }

    private function extractTitle(string $html): string
    {
        // Pega o primeiro h1 como título da capa
        if (preg_match('/<h1[^>]*class="doc-cover-title"[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            return strip_tags($m[1]);
        }
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            return strip_tags($m[1]);
        }
        return 'Documento';
    }

    private function extractSubtitle(string $html): string
    {
        if (preg_match('/<p[^>]*class="doc-cover-subtitle"[^>]*>(.*?)<\/p>/si', $html, $m)) {
            return strip_tags($m[1]);
        }
        return '';
    }

    private function extractCoverImage(string $html): ?string
    {
        // Primeira imagem do documento como fundo da capa, se existir
        if (preg_match('/<img[^>]+src="(data:image[^"]+)"[^>]*>/i', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractHeadings(string $html): array
    {
        $headings = [];
        preg_match_all('/<(h[1-3])[^>]*id="([^"]*)"[^>]*>(.*?)<\/h[1-3]>/si', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $headings[] = [
                'level' => (int) substr($match[1], 1),
                'id'    => $match[2],
                'text'  => strip_tags($match[3]),
            ];
        }
        return $headings;
    }

    private function cleanAndWrapSections(string $html): string
    {
        // Adicionar IDs únicos a todos os headings para o scrollspy
        $counter = [];
        $html = preg_replace_callback(
            '/<(h[1-3])([^>]*)>(.*?)<\/h[1-3]>/si',
            function ($m) use (&$counter) {
                $tag = $m[1];
                $attrs = $m[2];
                $text = $m[3];
                $slug = $this->slugify(strip_tags($text));
                $counter[$slug] = ($counter[$slug] ?? 0) + 1;
                $id = $counter[$slug] > 1 ? $slug . '-' . $counter[$slug] : $slug;
                // Não sobrescrever id existente
                if (strpos($attrs, 'id=') === false) {
                    $attrs .= " id=\"{$id}\"";
                }
                return "<{$tag}{$attrs}>{$text}</{$tag}>";
            },
            $html
        );

        // Tornar tabelas responsivas
        $html = str_replace('<table>', '<div class="table-wrapper"><table>', $html);
        $html = str_replace('</table>', '</table></div>', $html);

        // Tornar imagens responsivas com lazy loading
        $html = preg_replace(
            '/<img([^>]*)>/i',
            '<img$1 loading="lazy" class="doc-image">',
            $html
        );

        return $html;
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[àáâãäå]/u', 'a', $text);
        $text = preg_replace('/[èéêë]/u', 'e', $text);
        $text = preg_replace('/[ìíîï]/u', 'i', $text);
        $text = preg_replace('/[òóôõö]/u', 'o', $text);
        $text = preg_replace('/[ùúûü]/u', 'u', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    private function commandExists(string $command): bool
    {
        $process = new Process(['which', $command]);
        $process->run();
        return $process->isSuccessful();
    }
}
```

---

## Passo 3 — Controller

### `src/Controller/DocumentPageController.php`

```php
<?php
namespace App\Controller;

use App\Service\DocxConverterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/document', name: 'document_')]
class DocumentPageController extends AbstractController
{
    public function __construct(
        private DocxConverterService $converter,
        private string $uploadDir
    ) {}

    #[Route('/upload', name: 'upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('docx_file');

            if (!$file) {
                $this->addFlash('error', 'Nenhum arquivo enviado.');
                return $this->redirectToRoute('document_upload');
            }

            // Validação de MIME real — não confiar apenas na extensão
            $violations = $validator->validate($file, [
                new Assert\File([
                    'maxSize'   => '10M',
                    'mimeTypes' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/msword',
                    ],
                    'mimeTypesMessage' => 'Envie um arquivo .docx válido.',
                ])
            ]);

            if (count($violations) > 0) {
                $this->addFlash('error', (string) $violations->get(0)->getMessage());
                return $this->redirectToRoute('document_upload');
            }

            try {
                $filename = uniqid('doc_', true) . '.docx';
                $file->move($this->uploadDir, $filename);
                $docxPath = $this->uploadDir . '/' . $filename;

                $pageData = $this->converter->convert($docxPath);

                return $this->render('document_page/view.html.twig', [
                    'title'    => $pageData['title'],
                    'subtitle' => $pageData['subtitle'],
                    'coverBg'  => $pageData['coverBg'],
                    'content'  => $pageData['html'],
                    'headings' => $pageData['headings'],
                ]);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erro ao salvar o arquivo. Verifique permissões do diretório.');
                return $this->redirectToRoute('document_upload');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('document_upload');
            }
        }

        return $this->render('document_page/upload.html.twig');
    }
}
```

### Registrar o serviço em `config/services.yaml`

```yaml
App\Service\DocxConverterService:
    arguments:
        $uploadDir: '%kernel.project_dir%/public/uploads/documents'
        $projectDir: '%kernel.project_dir%'

App\Controller\DocumentPageController:
    arguments:
        $uploadDir: '%kernel.project_dir%/public/uploads/documents'
```

---

## Passo 4 — Template de Upload

### `templates/document_page/upload.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Importar Documento{% endblock %}

{% block body %}
<div class="upload-page">
  <div class="upload-card">
    <div class="upload-icon">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="12" y1="18" x2="12" y2="12"/>
        <line x1="9" y1="15" x2="15" y2="15"/>
      </svg>
    </div>
    <h1>Importar Documento</h1>
    <p class="upload-desc">Envie um arquivo <code>.docx</code> com capa, sumário e estrutura de títulos para gerar uma página web.</p>

    {% for message in app.flashes('error') %}
      <div class="alert-error">{{ message }}</div>
    {% endfor %}

    <form action="{{ path('document_upload') }}" method="post" enctype="multipart/form-data" class="upload-form">
      <div class="file-drop-zone" id="dropZone">
        <input type="file" name="docx_file" id="docxFile" accept=".docx" required class="file-input">
        <label for="docxFile" class="file-label">
          <span class="file-label-text">Clique para selecionar ou arraste aqui</span>
          <span class="file-label-hint">.docx — máximo 10MB</span>
        </label>
        <span class="file-chosen" id="fileChosen"></span>
      </div>
      <button type="submit" class="btn-upload">Gerar Página</button>
    </form>

    <p class="upload-tip">
      <strong>Dica:</strong> O documento deve ter estilo <em>Título</em> na capa,
      <em>Heading 1–3</em> nos títulos e <em>Subtítulo</em> no subtítulo da capa.
    </p>
  </div>
</div>

<style>
.upload-page{min-height:80vh;display:flex;align-items:center;justify-content:center;padding:2rem}
.upload-card{background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:3rem 2.5rem;max-width:520px;width:100%;text-align:center;box-shadow:var(--shadow-md)}
.upload-icon{margin-bottom:1.5rem;color:var(--color-primary)}
.upload-card h1{font-size:var(--text-xl);margin-bottom:.75rem}
.upload-desc{color:var(--color-text-muted);margin-bottom:2rem;font-size:var(--text-sm)}
.alert-error{background:var(--color-error-highlight);color:var(--color-error);border-radius:var(--radius-md);padding:.75rem 1rem;margin-bottom:1.5rem;font-size:var(--text-sm);text-align:left}
.file-drop-zone{border:2px dashed var(--color-border);border-radius:var(--radius-lg);padding:2rem;margin-bottom:1.5rem;transition:border-color .2s;cursor:pointer;position:relative}
.file-drop-zone:hover,.file-drop-zone.drag-over{border-color:var(--color-primary)}
.file-input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.file-label{display:flex;flex-direction:column;gap:.25rem;pointer-events:none}
.file-label-text{font-size:var(--text-sm);font-weight:500}
.file-label-hint{font-size:var(--text-xs);color:var(--color-text-muted)}
.file-chosen{display:block;font-size:var(--text-xs);color:var(--color-primary);margin-top:.5rem;font-weight:500}
.btn-upload{width:100%;padding:.875rem 1.5rem;background:var(--color-primary);color:#fff;border:none;border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:600;cursor:pointer;transition:background .2s}
.btn-upload:hover{background:var(--color-primary-hover)}
.upload-tip{font-size:var(--text-xs);color:var(--color-text-muted);margin-top:1.5rem;text-align:left;background:var(--color-surface-offset);padding:1rem;border-radius:var(--radius-md)}
</style>

<script>
const drop = document.getElementById('dropZone');
const input = document.getElementById('docxFile');
const chosen = document.getElementById('fileChosen');
input.addEventListener('change', () => {
  chosen.textContent = input.files[0] ? input.files[0].name : '';
});
drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('drag-over'); });
drop.addEventListener('dragleave', () => drop.classList.remove('drag-over'));
drop.addEventListener('drop', e => {
  e.preventDefault(); drop.classList.remove('drag-over');
  const dt = e.dataTransfer;
  if (dt.files[0]) { input.files = dt.files; chosen.textContent = dt.files[0].name; }
});
</script>
{% endblock %}
```

---

## Passo 5 — Template da Página Gerada

### `templates/document_page/view.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ title }}{% endblock %}

{% block stylesheets %}
{{ parent() }}
<link rel="stylesheet" href="{{ asset('build/document-page.css') }}">
{% endblock %}

{% block body %}

{# ═══════════════════════════════════════════
   HERO / CAPA
═══════════════════════════════════════════ #}
<header class="doc-hero" {% if coverBg %}style="--cover-bg: url('{{ coverBg }}')"{% endif %}>
  <div class="doc-hero__overlay"></div>
  <div class="doc-hero__content">
    {% if subtitle %}<p class="doc-hero__eyebrow">{{ subtitle }}</p>{% endif %}
    <h1 class="doc-hero__title">{{ title }}</h1>
    {% if headings|length > 0 %}
    <a href="#{{ headings[0].id }}" class="doc-hero__cta">
      Ir para o conteúdo
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </a>
    {% endif %}
  </div>
  <div class="doc-hero__scroll-hint" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>
</header>

{# ═══════════════════════════════════════════
   LAYOUT: SIDEBAR + CONTEÚDO
═══════════════════════════════════════════ #}
<div class="doc-layout">

  {# SIDEBAR — SUMÁRIO #}
  <aside class="doc-sidebar" id="docSidebar" aria-label="Sumário">
    <div class="doc-sidebar__inner">
      <div class="doc-sidebar__header">
        <span class="doc-sidebar__label">Sumário</span>
        <button class="doc-sidebar__close" id="sidebarClose" aria-label="Fechar menu">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <nav class="doc-toc" id="docToc" aria-label="Índice do documento">
        <ol class="doc-toc__list">
          {% for heading in headings %}
            <li class="doc-toc__item doc-toc__item--h{{ heading.level }}">
              <a href="#{{ heading.id }}" class="doc-toc__link" data-target="{{ heading.id }}">
                {{ heading.text }}
              </a>
            </li>
          {% endfor %}
        </ol>
      </nav>
    </div>
  </aside>

  {# CONTEÚDO PRINCIPAL #}
  <main class="doc-content" id="docContent">
    <div class="doc-content__inner doc-prose">
      {{ content|raw }}
    </div>
  </main>

</div>

{# BOTÃO MOBILE — abrir sumário #}
<button class="doc-fab" id="docFab" aria-label="Abrir sumário">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <line x1="3" y1="6" x2="21" y2="6"/>
    <line x1="3" y1="12" x2="21" y2="12"/>
    <line x1="3" y1="18" x2="15" y2="18"/>
  </svg>
  <span>Sumário</span>
</button>

{# OVERLAY mobile #}
<div class="doc-overlay" id="docOverlay"></div>

{% endblock %}

{% block javascripts %}
{{ parent() }}
<script>
(function() {
  'use strict';

  /* ── Scrollspy via IntersectionObserver ── */
  const tocLinks = document.querySelectorAll('.doc-toc__link');
  const headingEls = document.querySelectorAll('.doc-prose h1[id], .doc-prose h2[id], .doc-prose h3[id], .doc-prose h4[id]');

  if (tocLinks.length && headingEls.length) {
    let activeId = null;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          activeId = entry.target.id;
          tocLinks.forEach(link => {
            link.classList.toggle('is-active', link.dataset.target === activeId);
          });
          // Scroll do sumário para mostrar o item ativo
          const activeLink = document.querySelector(`.doc-toc__link[data-target="${activeId}"]`);
          if (activeLink) {
            activeLink.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
          }
        }
      });
    }, {
      rootMargin: '-10% 0px -70% 0px',
      threshold: 0
    });

    headingEls.forEach(el => observer.observe(el));
  }

  /* ── Smooth scroll nos links do sumário ── */
  tocLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const target = document.getElementById(link.dataset.target);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Fechar drawer no mobile após click
        closeSidebar();
      }
    });
  });

  /* ── Mobile drawer ── */
  const sidebar  = document.getElementById('docSidebar');
  const fab      = document.getElementById('docFab');
  const overlay  = document.getElementById('docOverlay');
  const closeBtn = document.getElementById('sidebarClose');

  function openSidebar() {
    sidebar.classList.add('is-open');
    overlay.classList.add('is-visible');
    document.body.style.overflow = 'hidden';
    fab.setAttribute('aria-expanded', 'true');
  }

  function closeSidebar() {
    sidebar.classList.remove('is-open');
    overlay.classList.remove('is-visible');
    document.body.style.overflow = '';
    fab.setAttribute('aria-expanded', 'false');
  }

  fab     && fab.addEventListener('click', openSidebar);
  closeBtn && closeBtn.addEventListener('click', closeSidebar);
  overlay  && overlay.addEventListener('click', closeSidebar);

  /* Fechar com ESC */
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
  });

})();
</script>
{% endblock %}
```

---

## Passo 6 — CSS Corporativo

### `assets/styles/document-page.css`

```css
/* ════════════════════════════════════════════════
   DOCUMENT PAGE — Design Corporativo Minimalista
   Usa as CSS Custom Properties do sistema Tannus
════════════════════════════════════════════════ */

/* ── Hero / Capa ── */
.doc-hero {
  position: relative;
  min-height: 60vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  padding: clamp(3rem, 8vw, 6rem) clamp(1.5rem, 5vw, 4rem);
  background-color: var(--color-primary-active);
  background-image: var(--cover-bg, none);
  background-size: cover;
  background-position: center;
  overflow: hidden;
}

.doc-hero__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    oklch(0.18 0.04 210 / 0.92) 0%,
    oklch(0.22 0.05 200 / 0.75) 100%
  );
}

.doc-hero__content {
  position: relative;
  z-index: 1;
  max-width: 720px;
}

.doc-hero__eyebrow {
  font-size: var(--text-sm);
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: oklch(0.85 0.08 190);
  margin-bottom: 1rem;
  opacity: 0.9;
}

.doc-hero__title {
  font-family: var(--font-display, sans-serif);
  font-size: clamp(2rem, 4vw, 3.5rem);
  font-weight: 700;
  line-height: 1.1;
  color: #ffffff;
  margin-bottom: 1.5rem;
}

.doc-hero__cta {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .625rem 1.25rem;
  border: 1.5px solid oklch(1 0 0 / 0.35);
  border-radius: var(--radius-full);
  color: #fff;
  font-size: var(--text-sm);
  font-weight: 500;
  text-decoration: none;
  transition: background var(--transition-interactive), border-color var(--transition-interactive);
}

.doc-hero__cta:hover {
  background: oklch(1 0 0 / 0.12);
  border-color: oklch(1 0 0 / 0.6);
}

/* Scroll hint animado */
.doc-hero__scroll-hint {
  position: absolute;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.doc-hero__scroll-hint span {
  display: block;
  width: 2px;
  height: 8px;
  background: oklch(1 0 0 / 0.5);
  border-radius: 2px;
  animation: scrollHint 1.4s ease-in-out infinite;
}
.doc-hero__scroll-hint span:nth-child(2) { animation-delay: .2s; }
.doc-hero__scroll-hint span:nth-child(3) { animation-delay: .4s; }
@keyframes scrollHint {
  0%, 100% { opacity: 0.2; transform: translateY(0); }
  50%       { opacity: 1;   transform: translateY(4px); }
}

/* ── Layout grid ── */
.doc-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  grid-template-areas: "sidebar content";
  min-height: 100vh;
  max-width: 1280px;
  margin: 0 auto;
  gap: 0;
}

/* ── Sidebar / Sumário ── */
.doc-sidebar {
  grid-area: sidebar;
  border-right: 1px solid var(--color-border);
  background: var(--color-surface);
}

.doc-sidebar__inner {
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  padding: 2rem 0;
  display: flex;
  flex-direction: column;
  scrollbar-width: thin;
  scrollbar-color: var(--color-border) transparent;
}

.doc-sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem 1rem;
  border-bottom: 1px solid var(--color-divider);
  margin-bottom: 1rem;
}

.doc-sidebar__label {
  font-size: var(--text-xs);
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-text-muted);
}

.doc-sidebar__close { display: none; }

/* TOC */
.doc-toc__list {
  list-style: none;
  padding: 0 .75rem;
}

.doc-toc__item { margin: 0; }

.doc-toc__item--h2 .doc-toc__link { padding-left: 1rem; font-size: var(--text-xs); }
.doc-toc__item--h3 .doc-toc__link { padding-left: 1.75rem; font-size: var(--text-xs); color: var(--color-text-muted); }

.doc-toc__link {
  display: block;
  padding: .4rem .75rem;
  border-radius: var(--radius-sm);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  text-decoration: none;
  border-left: 2px solid transparent;
  transition: color var(--transition-interactive),
              background var(--transition-interactive),
              border-color var(--transition-interactive);
  line-height: 1.4;
}

.doc-toc__link:hover {
  color: var(--color-text);
  background: var(--color-surface-offset);
}

.doc-toc__link.is-active {
  color: var(--color-primary);
  border-left-color: var(--color-primary);
  background: var(--color-primary-highlight);
  font-weight: 600;
}

/* ── Conteúdo ── */
.doc-content {
  grid-area: content;
  background: var(--color-bg);
}

.doc-content__inner {
  max-width: 760px;
  margin: 0 auto;
  padding: clamp(2rem, 4vw, 4rem) clamp(1.5rem, 4vw, 3rem);
}

/* ── Tipografia da Prosa ── */
.doc-prose h1 {
  font-family: var(--font-display, sans-serif);
  font-size: var(--text-2xl);
  font-weight: 700;
  line-height: 1.15;
  color: var(--color-text);
  margin: 3rem 0 1rem;
  padding-top: 1rem;
  border-bottom: 2px solid var(--color-divider);
  padding-bottom: .75rem;
}

.doc-prose h2 {
  font-family: var(--font-display, sans-serif);
  font-size: var(--text-xl);
  font-weight: 700;
  color: var(--color-text);
  margin: 2.5rem 0 .875rem;
}

.doc-prose h3 {
  font-size: var(--text-lg);
  font-weight: 600;
  color: var(--color-text);
  margin: 2rem 0 .75rem;
}

.doc-prose h4 {
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: .06em;
  margin: 1.5rem 0 .5rem;
}

.doc-prose p {
  font-size: var(--text-base);
  line-height: 1.75;
  color: var(--color-text);
  margin-bottom: 1.25rem;
  max-width: 70ch;
}

.doc-prose ul, .doc-prose ol {
  margin: 0 0 1.25rem 1.5rem;
  color: var(--color-text);
}

.doc-prose li {
  margin-bottom: .375rem;
  line-height: 1.7;
}

.doc-prose strong { font-weight: 700; }
.doc-prose em     { font-style: italic; }

/* Imagens */
.doc-image {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  margin: 1.5rem auto;
  display: block;
}

figcaption {
  text-align: center;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin-top: -.75rem;
  margin-bottom: 1.5rem;
}

/* Tabelas responsivas */
.table-wrapper {
  overflow-x: auto;
  margin: 1.5rem 0;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.doc-prose table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--text-sm);
}

.doc-prose th {
  background: var(--color-surface);
  font-weight: 700;
  text-align: left;
  padding: .75rem 1rem;
  border-bottom: 2px solid var(--color-border);
  color: var(--color-text);
  white-space: nowrap;
}

.doc-prose td {
  padding: .625rem 1rem;
  border-bottom: 1px solid var(--color-divider);
  color: var(--color-text);
}

.doc-prose tr:last-child td { border-bottom: none; }
.doc-prose tr:hover td { background: var(--color-surface-offset); }

/* Citações / blockquote */
.doc-prose blockquote {
  border-left: 3px solid var(--color-primary);
  margin: 1.5rem 0;
  padding: 1rem 1.5rem;
  background: var(--color-primary-highlight);
  border-radius: 0 var(--radius-md) var(--radius-md) 0;
  font-style: italic;
  color: var(--color-text-muted);
}

/* Code inline */
.doc-prose code {
  font-family: 'Fira Code', 'Consolas', monospace;
  font-size: .875em;
  background: var(--color-surface-offset);
  padding: .15em .4em;
  border-radius: var(--radius-sm);
}

/* ── FAB mobile ── */
.doc-fab {
  display: none;
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 100;
  align-items: center;
  gap: .5rem;
  padding: .75rem 1.25rem;
  background: var(--color-primary);
  color: #fff;
  border: none;
  border-radius: var(--radius-full);
  font-size: var(--text-sm);
  font-weight: 600;
  box-shadow: var(--shadow-lg);
  cursor: pointer;
  transition: background var(--transition-interactive);
}

.doc-fab:hover { background: var(--color-primary-hover); }

/* ── Overlay mobile ── */
.doc-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: oklch(0.1 0 0 / 0.5);
  z-index: 199;
  backdrop-filter: blur(2px);
}

.doc-overlay.is-visible { display: block; }

/* ════ RESPONSIVIDADE ════ */

@media (max-width: 900px) {
  .doc-layout {
    grid-template-columns: 1fr;
    grid-template-areas: "content";
  }

  /* Sidebar vira drawer lateral */
  .doc-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: min(320px, 85vw);
    z-index: 200;
    transform: translateX(-100%);
    transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: var(--shadow-lg);
    border-right: 1px solid var(--color-border);
  }

  .doc-sidebar.is-open {
    transform: translateX(0);
  }

  .doc-sidebar__close { display: flex; }

  .doc-sidebar__inner {
    position: static;
    height: 100%;
  }

  .doc-fab { display: flex; }
}

@media (max-width: 480px) {
  .doc-hero { min-height: 50vh; }
  .doc-hero__title { font-size: clamp(1.5rem, 8vw, 2.5rem); }
  .doc-content__inner { padding: 1.5rem 1rem; }
  .doc-prose h1 { font-size: var(--text-xl); }
  .doc-prose h2 { font-size: var(--text-lg); }
}

/* ── Dark mode ── */
[data-theme="dark"] .doc-hero__overlay {
  background: linear-gradient(135deg, oklch(0.1 0.03 210 / 0.95) 0%, oklch(0.15 0.04 200 / 0.82) 100%);
}
```

---

## Passo 7 — Registrar o CSS no Webpack Encore

Em `webpack.config.js`:
```javascript
// Adicionar entry para o CSS da feature
.addEntry('document-page', './assets/styles/document-page.css')
```

Após editar:
```bash
# Verificar a edição
grep 'document-page' webpack.config.js

# Rebuildar
npm run dev

# Confirmar que o arquivo foi gerado
ls public/build/ | grep document
```

---

## Passo 8 — Criar Diretório de Uploads

```bash
mkdir -p public/uploads/documents
chmod 755 public/uploads/documents

# Adicionar ao .gitignore para não versionar os arquivos enviados
echo "public/uploads/documents/*.docx" >> .gitignore
echo "public/uploads/documents/images_*/" >> .gitignore

# Adicionar .gitkeep para manter a pasta
touch public/uploads/documents/.gitkeep
git add public/uploads/documents/.gitkeep
```

---

## Guia de Formatação do .docx para Melhor Resultado

Oriente o usuário a formatar o documento com estes estilos nativos do Word:

| Elemento | Estilo Word a usar |
|---|---|
| Título principal da capa | `Título` (Title) |
| Subtítulo da capa | `Subtítulo` (Subtitle) |
| Seção principal | `Título 1` (Heading 1) |
| Subseção | `Título 2` (Heading 2) |
| Sub-subseção | `Título 3` (Heading 3) |
| Texto normal | `Normal` |
| Legenda de imagem | `Legenda` (Caption) |
| Destaque/citação | `Citação Intensa` ou `Quote` |

---

## Tratamento de Erros Esperados

| Erro | Causa | Solução |
|---|---|---|
| "mammoth não encontrado" | Node/Python não instalado | Executar instalação conforme Passo 1 |
| "Permissão negada" no upload | Diretório sem escrita | `chmod 755 public/uploads/documents` |
| Headings não extraídos | Documento sem estilos Word | Pedir ao usuário para aplicar estilos nativos |
| Imagens não aparecem | Mammoth não extraiu | Usar conversão base64 inline (já no script) |
| Tabela quebrada no mobile | Sem `.table-wrapper` | Verificar se `cleanAndWrapSections()` foi chamado |

---

## Registro Obrigatório no DEVELOPMENT_LOG.md

Após implementar, registrar:
- Qual conversor foi instalado (mammoth Node / Python / PHPWord) e versão
- Caminho do diretório de uploads
- Rota criada e nome
- Qualquer ajuste no styleMap para os estilos específicos do documento do cliente
- Comportamento observado em testes com documento real

---

## Segurança — Pontos Críticos

- MIME type validado via `Assert\File` — não confiar apenas na extensão `.docx`
- Nome do arquivo gerado com `uniqid()` — nunca usar nome original do upload
- Diretório de uploads fora do escopo de execução PHP (apenas servir estático)
- HTML gerado pelo mammoth é renderizado com `|raw` no Twig — o mammoth produz HTML seguro por default, mas NUNCA concatenar input de usuário nesse HTML
- Limite de 10MB configurado no Symfony — verificar também `upload_max_filesize` e `post_max_size` no PHP
