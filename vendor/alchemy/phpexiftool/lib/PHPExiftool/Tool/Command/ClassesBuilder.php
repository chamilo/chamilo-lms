<?php

/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Tool\Command;

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPExiftool\ClassUtils\Builder;
use PHPExiftool\ClassUtils\TagProviderBuilder;
use PHPExiftool\Exiftool;
use PHPExiftool\InformationDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class ClassesBuilder extends Command
{
    /**
     * Output interface for Command
     *
     * @var ConsoleOutput
     */
    protected $output;

    /**
     *
     * @var array
     */
    protected $classes = array();

    /**
     *
     * @var array
     */
    protected $types = array();

    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('classes-builder')
            ->setDescription('Build Tags classes from exiftool documentation.')
            ->addOption('with-mwg', '', null, 'Include MWG tags')
            ->addOption('write', 'w',   null, 'Write classes on disk')
            ->addOption('force', 'f',   null, 'Force classes write whenever files already exists');

        return $this;
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        $this->output = $output;

        $this->output->write('Extracting datas... ');

        $logger = new Logger('Builder');
        $logger->pushHandler(new NullHandler());

        if ($input->getOption('verbose')) {
            $logger->pushHandler(new StreamHandler('php://stdout'));
        }

        $dumper = new InformationDumper(new Exiftool($logger));

        $options = array();
        if($input->getOption('with-mwg')) {
            $options[] = InformationDumper::LISTOPTION_MWG;
        }

        $dump = $dumper->listDatas(InformationDumper::LISTTYPE_SUPPORTED_XML, $options);

        $this->output->writeln('Done !');

        $this->output->writeln('Generating classes... ');

        $this->extractDump($dump, $output);

        if ( ! $input->getOption('write')) {
            $this->output->writeln(
                'These classes were not written. Use --write to write on disk'
            );
        } else {
            $this->output->writeln('Erasing previous files... ');

            system('rm -R ' . __DIR__ . '/../../Driver/Tag/*');
            system('rm -R ' . __DIR__ . '/../../Driver/Type/*');

            $this->output->writeln('Writing files... ');
            $this->writeClasses($input->getOption('force'));
        }

        $this->output->writeln(
            sprintf(
                '%d classes generated in %d seconds (%d Mb)'
                , count($this->classes), (microtime(true) - $start), memory_get_peak_usage() >> 20
            )
        );
    }

    /**
     *
     * @return ClassesBuilder
     */
    protected function writeClasses($force = false)
    {
        $n = 0;

        $classesBuffer = new TagProviderBuilder('', 'TagProvider', array(), '\\Pimple');
        $buffer = array();

        foreach ($this->classes as $class) {
            try {

                $class->write($force);

                if (strpos($class->getNamespace(), 'PHPExiftool\\Driver\\Tag') === 0) {

                    if ( ! isset($buffer[$class->getProperty('GroupName')])) {
                        $buffer[$class->getProperty('GroupName')] = array();
                    }

                    $buffer[$class->getProperty('GroupName')][$class->getProperty('Name')] = $class->getNamespace() . '\\' . $class->getClassname();
                }

                $this->output->write(sprintf("\rwriting class #%5d", $n ++ ));
            } catch (\Exception $e) {
                $this->output->writeln(
                    sprintf("\n<error>Error while writing class %s</error>", $class->getPathfile())
                );
            }
        }

        $classesBuffer->setClasses($buffer);

        $classesBuffer->write(true);

        $this->output->writeln('');

        return $this;
    }

    protected function generateTypes()
    {
        foreach ($this->types as $type => $data) {
            if ($type == '?')
                $type = 'unknown';

            $classname = self::generateClassname($type);

            $properties = array(
                'ExiftoolName' => $data,
                'PHPMap'       => $this->getTypeMap($type),
            );

            $classpath = sprintf('%s', $classname);

            $this->classes[$classpath] = new Builder('Type', $classname, $properties, 'AbstractType', array('\\PHPExiftool\\Driver\\AbstractType'));
        }

        return;
    }

    protected function getTypeMap($type)
    {
        /**
         * Some of these types are described here:
         * http://trac.greenstone.org/browser/main/trunk/greenstone2/perllib/cpan/Image/ExifTool/README
         * http://cpansearch.perl.org/src/EXIFTOOL/Image-ExifTool-9.13/lib/Image/ExifTool/PICT.pm
         */
        switch ($type) {
            # Formats defined in the wiki
            case 'int8s':
            case 'int8u':
            case 'int16s':
            case 'int16u':
            case 'int16uRev':
            case 'int32s':
            case 'int32u':
            case 'int32uRev':
            case 'int64s':
            case 'int64u':
            case 'rational32s':
            case 'rational32u':
            case 'rational64s':
            case 'rational64u':
            case 'fixed16s':
            case 'fixed32s':
            case 'fixed32u':
            case 'var_int16u':

            # Apple data structures in PICT images
            case 'Int8uText':
            case 'Int8u2Text':
            case 'Int16Data':
            case 'Int32uData':

            # Source unknown ...
            case 'var_int8u':
            case 'rational':
            case 'integer':
            case 'real':
            case 'digits':
            case 'signed':
            case 'unsigned':
                return 'int';
                break;

            # Formats defined in the wiki
            case 'float':
            case 'double':
            case 'extended':
                return 'float';
                break;

            # Formats defined in the wiki
            case 'undef':
            case 'binary':

            # Source unknown ...
            case 'var_undef':
            case '?':
            case 'null':
            case 'unknown':
            case 'Unknown':
                return 'binary';
                break;

            # Formats defined in the wiki
            case 'string':
            case 'pstring':
            case 'var_string':
            case 'var_pstr32':

            # Apple data structures in PICT images
            case 'Arc':
            case 'BitsRect#': # version-depended
            case 'BitsRgn#': # version-depended
            case 'CompressedQuickTime':
            case 'DirectBitsRect':
            case 'DirectBitsRgn':
            case 'FontName':
            case 'PixPat':
            case 'Point':
            case 'PointText':
            case 'Polygon':
            case 'Rect':
            case 'RGBColor':
            case 'Rgn':
            case 'ShortLine':

            # Source unknown ...
            case 'lang-alt':
            case 'resize':
            case 'utf8':
                return 'string';
                break;

            # Source unknown ...
            case 'date':
                return 'date';
                break;

            # Source unknown ...
            case 'boolean':
                return 'boolean';
                break;
            default:
                $this->output->writeln(sprintf("No type found for %s", $type));
                break;
        }

        return;
    }

    protected function createTagClass($namespace, $classname, array $properties)
    {
        if ($classname == 'Reserved') {
            return;
        }

        $namespace = self::generateNamespace('Tag\\' . $namespace);

        $classpath = sprintf('%s\\%s', $namespace, $classname);

        if (isset($this->classes[$classpath])) {
            foreach ($properties as $property => $value) {
                if ($this->classes[$classpath]->getProperty($property) != $value) {
                    if (in_array($property, array('Writable', 'flag_Binary', 'flag_List'))) {

                        $this->classes[$classpath]->setProperty($property, 'false');
                    } elseif ($property === 'Values') {

                        $new_value = array();

                        if ( ! is_array($this->classes[$classpath]->getProperty($property))) {
                            if (is_array($value)) {
                                $new_value = $value;
                            }
                        } else {
                            if (is_array($value) && $this->classes[$classpath]->getProperty($property) != $value) {
                                $new_value = array_merge($this->classes[$classpath]->getProperty($property), $value);
                            } else {
                                $new_value = $this->classes[$classpath]->getProperty($property);
                            }
                        }

                        $this->classes[$classpath]->setProperty($property, $new_value);
                    } else {
                        $this->classes[$classpath]->setProperty($property, 'mixed');
                    }
                }
            }
        } else {
            $this->classes[$classpath] = new Builder($namespace, $classname, $properties, 'AbstractTag', array('JMS\\Serializer\\Annotation\\ExclusionPolicy', '\\PHPExiftool\\Driver\\AbstractTag'), array('@ExclusionPolicy("all")'));
        }

        return;
    }

    protected function extractDump($dump, OutputInterface $output)
    {
        $progress = $this->getHelper('progress');

        $crawler = new Crawler();
        $crawler->addContent($dump);

        $tag_count = null;
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_QUIET) {
            $output->write('Compute tag count...');
            $tag_count = count($crawler->filter('table>tag'));
            $output->writeln(sprintf('%d', $tag_count));
        }

        $progress->start($output, $tag_count);

        foreach ($crawler->filter('table') as $table) {
            $table_crawler = new Crawler();
            $table_crawler->addNode($table);

            $tag_group_name = $table_crawler->attr('g1');
            $tag_full_name = $table_crawler->attr('name');

            $tag_g0 = $table_crawler->attr('g0');
            $tag_g2 = $table_crawler->attr('g2');

            $tags = $table_crawler->filter('tag');

            foreach ($tags as $tag) {

                $tag_crawler = new Crawler();
                $tag_crawler->addNode($tag);

                $extra = array();

                if ($tag_crawler->attr('g0')) {
                    $extra['local_g0'] = $tag_crawler->attr('g0');
                }

                if ($tag_crawler->attr('g1') && ! in_array($tag_crawler->attr('g1'), array('MakerNotes', 'Chapter#'))) {
                    $g_name = $tag_crawler->attr('g1');
                    $extra['local_g1'] = $tag_crawler->attr('g1');
                } else {
                    $g_name = $tag_group_name;
                }

                if ($tag_crawler->attr('g2')) {
                    $extra['local_g2'] = $tag_crawler->attr('g2');
                }

                $flags = explode(',', $tag_crawler->attr('flags'));

                if (in_array('Avoid', $flags)) {
                    $extra['flag_Avoid'] = 'true';
                }
                if (in_array('Binary', $flags)) {
                    $extra['flag_Binary'] = 'true';
                }
                if (in_array('Permanent', $flags)) {
                    $extra['flag_Permanent'] = 'true';
                }
                if (in_array('Protected', $flags)) {
                    $extra['flag_Protected'] = 'true';
                }
                if (in_array('Unsafe', $flags)) {
                    $extra['flag_Unsafe'] = 'true';
                }
                if (in_array('List', $flags)) {
                    $extra['flag_List'] = 'true';
                }
                if (in_array('Mandatory', $flags)) {
                    $extra['flag_Mandatory'] = 'true';
                }
                if (in_array('Bag', $flags)) {
                    $extra['flag_Bag'] = 'true';
                }
                if (in_array('Seq', $flags)) {
                    $extra['flag_Seq'] = 'true';
                }
                if (in_array('Alt', $flags)) {
                    $extra['flag_Alt'] = 'true';
                }

                $subspace = str_replace('::', '\\', $g_name);

                $tag_name = $tag_crawler->attr('name');

                $classname = self::generateClassname($tag_name);
                $tag_id = $tag_crawler->attr('id');

                $properties = array_merge(array(
                    'Id'          => $tag_id,
                    'Name'        => $tag_name,
                    'FullName'    => $tag_full_name,
                    'GroupName'   => $g_name,
                    'g0'          => $tag_g0,
                    'g1'          => $tag_group_name,
                    'g2'          => $tag_g2,
                    'Type'        => $tag_crawler->attr('type'),
                    'Writable'    => $tag_crawler->attr('writable'),
                    'Description' => $tag_crawler->filter('desc[lang="en"]')->first()->text(),
                    ), $extra);

                if ($tag_crawler->attr('count')) {
                    $properties['MaxLength'] = $tag_crawler->attr('count');
                }

                $this->types[$tag_crawler->attr('type')] = $tag_crawler->attr('type');

                if ($tag_crawler->attr('index')) {
                    $properties['Index'] = $tag_crawler->attr('index');
                }

                if (count($tag_crawler->filter('values')) > 0) {
                    $values = array();

                    $values_tag = $tag_crawler->filter('values')->first();

                    $Keys = $values_tag->filter('key');

                    foreach ($Keys as $Key) {
                        $KeyCrawler = new Crawler();
                        $KeyCrawler->addNode($Key);

                        $Id = $KeyCrawler->attr('id');
                        $Label = $KeyCrawler->filter('val[lang="en"]')->first()->text();

                        $values[$Id] = array('Id'    => $Id, 'Label' => $Label);
                    }

                    $properties['Values'] = $values;
                }

                $this->createTagClass($subspace, $classname, $properties);
                $progress->advance();
            }
        }
        $progress->finish();

        $this->generateTypes();
    }

    protected static $reservedNames = array(
        'abstract',
        'and',
        'array',
        'as',
        'bool',
        'break',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'do',
        'else',
        'elseif',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'extends',
        'false',
        'final',
        'float',
        'for',
        'foreach',
        'function',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'instanceof',
        'int',
        'interface',
        'mixed',
        'namespace',
        'new',
        'null',
        'numeric',
        'object',
        'old_function',
        'or',
        'private',
        'protected',
        'public',
        'resource',
        'static',
        'string',
        'switch',
        'throw',
        'true',
        'try',
        'use',
        'var',
        'while',
        'xor',
        'yield',
    );

    /**
     *
     * @param  type $name
     * @return type
     */
    public static function generateClassname($name)
    {
        $values = preg_split('/\\ |-|_|\\#/', ltrim($name, '0123456789'));

        foreach ($values as $key => $value) {

            $values[$key] = ucfirst($value);
        }

        $retval = implode('', $values);

        if (in_array(strtolower($retval), static::$reservedNames)) {
            $retval = $retval . '0';
        }

        return $retval;
    }

    public static function generateNamespace($namespace)
    {
        $values = explode('\\', $namespace);

        foreach ($values as $key => $value) {
            $values[$key] = ucfirst(self::generateClassname($value));
        }

        return implode('\\', $values);
    }
}
