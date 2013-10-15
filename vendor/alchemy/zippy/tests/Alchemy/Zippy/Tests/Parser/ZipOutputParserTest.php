<?php

namespace Alchemy\Zippy\Tests\Parser;

use Alchemy\Zippy\Parser\ZipOutputParser;
use Alchemy\Zippy\Tests\TestCase;

class ZipOutputParserTest extends TestCase
{
    public function testNewParser()
    {
        return new ZipOutputParser();
    }

    /**
     * @depends testNewParser
     */
    public function testParseFileListing($parser)
    {
        $current_timezone = ini_get('date.timezone');
        ini_set('date.timezone', 'UTC');

        $output =
"Length   Date     Time     Name
-------- ----     ----     ----
     0   2006-06-09 12:06  practice/
 10240   2006-06-09 12:06  practice/records
--------                    -------
    785                      2 files";

        $members = $parser->parseFileListing($output);

        $this->assertEquals(2, count($members));

        foreach ($members as $member) {
            $this->assertTrue(is_array($member));
        }

        $memberDirectory = array_shift($members);

        $this->assertTrue($memberDirectory['is_dir']);
        $this->assertEquals('practice/', $memberDirectory['location']);
        $this->assertEquals(0, $memberDirectory['size']);
        $date = $memberDirectory['mtime'];
        $this->assertTrue($date instanceof \DateTime);
        $this->assertEquals('1149854760', $date->format("U"));

        $memberFile = array_pop($members);

        $this->assertFalse($memberFile['is_dir']);
        $this->assertEquals('practice/records', $memberFile['location']);
        $this->assertEquals(10240, $memberFile['size']);
        $date = $memberFile['mtime'];
        $this->assertTrue($date instanceof \DateTime);
        $this->assertEquals('1149854760', $date->format("U"));

        ini_set('date.timezone', $current_timezone);
    }

    /**
     * @depends testNewParser
     */
    public function testParseDeflatorVersion($parser)
    {
        $output = "UnZip 5.52 of 28 February 2005, by Info-ZIP.  Maintained by C. Spieler.  Send
bug reports using http://www.info-zip.org/zip-bug.html; see README for details.

Usage: unzip [-Z] [-opts[modifiers]] file[.zip] [list] [-x xlist] [-d exdir]
  Default action is to extract files in list, except those in xlist, to exdir;
  file[.zip] may be a wildcard.  -Z => ZipInfo mode ('unzip -Z' for usage).

  -p  extract files to pipe, no messages     -l  list files (short format)
  -f  freshen existing files, create none    -t  test compressed archive data
  -u  update files, create if necessary      -z  display archive comment
  -x  exclude files that follow (in xlist)   -d  extract files into exdir

modifiers:                                   -q  quiet mode (-qq => quieter)
  -n  never overwrite existing files         -a  auto-convert any text files
  -o  overwrite files WITHOUT prompting      -aa treat ALL files as text
  -j  junk paths (do not make directories)   -v  be verbose/print version info
  -C  match filenames case-insensitively     -L  make (some) names lowercase
  -X  restore UID/GID info                   -V  retain VMS version numbers
  -K  keep setuid/setgid/tacky permissions   -M  pipe through 'more' pager
Examples (see unzip.txt for more info):
  unzip data1 -x joe   => extract all files except joe from zipfile data1.zip
  unzip -p foo | more  => send contents of foo.zip via pipe into program more
  unzip -fo foo ReadMe => quietly replace existing ReadMe if archive file newer";

        $this->assertEquals('5.52', $parser->parseDeflatorVersion($output));
    }

    /**
     * @depends testNewParser
     */
    public function testParseInflatorVersion($parser)
    {
        $output = "Copyright (c) 1990-2008 Info-ZIP - Type 'zip '-L'' for software license.
Zip 3.0 (July 5th 2008). Usage:
zip [-options] [-b path] [-t mmddyyyy] [-n suffixes] [zipfile list] [-xi list]
  The default action is to add or replace zipfile entries from list, which
  can include the special name - to compress standard input.
  If zipfile and list are omitted, zip compresses stdin to stdout.
  -f   freshen: only changed files  -u   update: only changed or new files
  -d   delete entries in zipfile    -m   move into zipfile (delete OS files)
  -r   recurse into directories     -j   junk (don't record) directory names
  -0   store only                   -l   convert LF to CR LF (-ll CR LF to LF)
  -1   compress faster              -9   compress better
  -q   quiet operation              -v   verbose operation/print version info
  -c   add one-line comments        -z   add zipfile comment
  -@   read names from stdin        -o   make zipfile as old as latest entry
  -x   exclude the following names  -i   include only the following names
  -F   fix zipfile (-FF try harder) -D   do not add directory entries
  -A   adjust self-extracting exe   -J   junk zipfile prefix (unzipsfx)
  -T   test zipfile integrity       -X   eXclude eXtra file attributes
  -y   store symbolic links as the link instead of the referenced file
  -e   encrypt                      -n   don't compress these suffixes
  -h2  show more help";

        $this->assertEquals('3.0', $parser->parseInflatorVersion($output));
    }
}
