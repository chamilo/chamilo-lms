<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Toolbox;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

final class ToolboxJavascriptStructureValidator
{
    public function findViolation(string $javascript): ?string
    {
        $nodeResult = $this->validateWithNode($javascript);
        if (true === $nodeResult['available']) {
            return $nodeResult['violation'];
        }

        return $this->findStructuralViolation($javascript);
    }

    /**
     * @return array{available: bool, violation: ?string}
     */
    private function validateWithNode(string $javascript): array
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            return ['available' => false, 'violation' => null];
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'chamilo_toolbox_js_');
        if (false === $temporaryPath) {
            return ['available' => false, 'violation' => null];
        }

        $path = $temporaryPath.'.js';
        if (!@rename($temporaryPath, $path)) {
            @unlink($temporaryPath);

            return ['available' => false, 'violation' => null];
        }

        try {
            if (false === file_put_contents($path, $javascript)) {
                return ['available' => false, 'violation' => null];
            }

            $process = new Process([$node, '--check', $path]);
            $process->setTimeout(5.0);
            $process->run();

            if ($process->isSuccessful()) {
                return ['available' => true, 'violation' => null];
            }

            $output = trim($process->getErrorOutput()."\n".$process->getOutput());
            if (1 === preg_match('/SyntaxError:\s*(.+)/i', $output, $matches)) {
                return [
                    'available' => true,
                    'violation' => 'JavaScript syntax validation failed: '.trim($matches[1]),
                ];
            }

            return [
                'available' => true,
                'violation' => 'JavaScript syntax validation failed.',
            ];
        } catch (Throwable) {
            return ['available' => false, 'violation' => null];
        } finally {
            @unlink($path);
        }
    }

    private function findStructuralViolation(string $javascript): ?string
    {
        $stack = [];
        $length = \strlen($javascript);
        $state = 'code';
        $quote = '';
        $line = 1;
        $stateStartedAtLine = 1;

        for ($index = 0; $index < $length; ++$index) {
            $character = $javascript[$index];
            $next = $index + 1 < $length ? $javascript[$index + 1] : '';

            if ("\n" === $character) {
                ++$line;
            }

            if ('line-comment' === $state) {
                if ("\n" === $character) {
                    $state = 'code';
                }

                continue;
            }

            if ('block-comment' === $state) {
                if ('*' === $character && '/' === $next) {
                    $state = 'code';
                    ++$index;
                }

                continue;
            }

            if ('string' === $state) {
                if ('\\' === $character) {
                    ++$index;

                    continue;
                }

                if ($character === $quote) {
                    $state = 'code';
                    $quote = '';

                    continue;
                }

                if ("\n" === $character) {
                    return \sprintf(
                        'JavaScript contains an unterminated string opened near line %d.',
                        $stateStartedAtLine,
                    );
                }

                continue;
            }

            if ('template' === $state) {
                if ('\\' === $character) {
                    ++$index;

                    continue;
                }

                if ('`' === $character) {
                    $state = 'code';
                }

                continue;
            }

            if ('/' === $character && '/' === $next) {
                $state = 'line-comment';
                $stateStartedAtLine = $line;
                ++$index;

                continue;
            }

            if ('/' === $character && '*' === $next) {
                $state = 'block-comment';
                $stateStartedAtLine = $line;
                ++$index;

                continue;
            }

            if ("'" === $character || '"' === $character) {
                $state = 'string';
                $quote = $character;
                $stateStartedAtLine = $line;

                continue;
            }

            if ('`' === $character) {
                $state = 'template';
                $stateStartedAtLine = $line;

                continue;
            }

            if (\in_array($character, ['(', '[', '{'], true)) {
                $stack[] = [$character, $line];

                continue;
            }

            if (!\in_array($character, [')', ']', '}'], true)) {
                continue;
            }

            if ([] === $stack) {
                return \sprintf(
                    'JavaScript contains an unexpected "%s" near line %d.',
                    $character,
                    $line,
                );
            }

            [$opening, $openingLine] = array_pop($stack);
            $expected = match ($opening) {
                '(' => ')',
                '[' => ']',
                '{' => '}',
            };

            if ($character !== $expected) {
                return \sprintf(
                    'JavaScript structure is invalid near line %d: "%s" opened near line %d but "%s" was found instead of "%s".',
                    $line,
                    $opening,
                    $openingLine,
                    $character,
                    $expected,
                );
            }
        }

        if ('block-comment' === $state) {
            return \sprintf(
                'JavaScript contains an unterminated block comment opened near line %d.',
                $stateStartedAtLine,
            );
        }

        if ('string' === $state) {
            return \sprintf(
                'JavaScript contains an unterminated string opened near line %d.',
                $stateStartedAtLine,
            );
        }

        if ('template' === $state) {
            return \sprintf(
                'JavaScript contains an unterminated template literal opened near line %d.',
                $stateStartedAtLine,
            );
        }

        if ([] !== $stack) {
            [$opening, $openingLine] = array_pop($stack);
            $expected = match ($opening) {
                '(' => ')',
                '[' => ']',
                '{' => '}',
            };

            return \sprintf(
                'JavaScript contains an unclosed "%s" opened near line %d; expected "%s" before the end of the script.',
                $opening,
                $openingLine,
                $expected,
            );
        }

        return null;
    }
}
