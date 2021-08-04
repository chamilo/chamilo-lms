<?php

/**
 * Php library to Bake the PNG Images.
 */
class PNGImageBaker
{
    private $_contents;
    private $_size;
    private $_chunks;

    /**
     * Prepares file for handling metadata.
     * Verifies that this file is a valid PNG file.
     * Unpacks file chunks and reads them into an array.
     *
     * @param string $contents File content as a string
     */
    public function __construct($contents)
    {
        $this->_contents = $contents;
        $png_signature = pack("C8", 137, 80, 78, 71, 13, 10, 26, 10);
        // Read 8 bytes of PNG header and verify.
        $header = substr($this->_contents, 0, 8);
        if ($header != $png_signature) {
            echo 'This is not a valid PNG image';
        }
        $this->_size = strlen($this->_contents);
        $this->_chunks = [];
        // Skip 8 bytes of IHDR image header.
        $position = 8;
        do {
            $chunk = @unpack('Nsize/a4type', substr($this->_contents, $position, 8));
            $this->_chunks[$chunk['type']][] = substr($this->_contents, $position + 8, $chunk['size']);
            // Skip 12 bytes chunk overhead.
            $position += $chunk['size'] + 12;
        } while ($position < $this->_size);
    }

    /**
     * Checks if a key already exists in the chunk of said type.
     * We need to avoid writing same keyword into file chunks.
     *
     * @param string $type  chunk type, like iTXt, tEXt, etc
     * @param string $check keyword that needs to be checked
     *
     * @return bool (true|false) True if file is safe to write this keyword, false otherwise
     */
    public function checkChunks($type, $check)
    {
        if (array_key_exists($type, $this->_chunks)) {
            foreach (array_keys($this->_chunks[$type]) as $typekey) {
                list($key, $data) = explode("\0", $this->_chunks[$type][$typekey]);
                if (0 == strcmp($key, $check)) {
                    echo 'Key "'.$check.'" already exists in "'.$type.'" chunk.';

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Add a chunk by type with given key and text.
     *
     * @param string $chunkType chunk type, like iTXt, tEXt, etc
     * @param string $key       keyword that needs to be added
     * @param string $value     currently an assertion URL that is added to an image metadata
     *
     * @return string $result file content with a new chunk as a string
     */
    public function addChunk($chunkType, $key, $value)
    {
        $chunkData = $key."\0".$value;
        $crc = pack("N", crc32($chunkType.$chunkData));
        $len = pack("N", strlen($chunkData));

        $newChunk = $len.$chunkType.$chunkData.$crc;
        $result = substr($this->_contents, 0, $this->_size - 12)
                .$newChunk
                .substr($this->_contents, $this->_size - 12, 12);

        return $result;
    }

    /**
     * removes a chunk by type with given key and text.
     *
     * @param string $chunkType chunk type, like iTXt, tEXt, etc
     * @param string $key       keyword that needs to be deleted
     * @param string $png       the png image
     *
     * @return string $result new File content
     */
    public function removeChunks($chunkType, $key, $png)
    {
        // Read the magic bytes and verify
        $retval = substr($png, 0, 8);
        $ipos = 8;
        if ($retval != "\x89PNG\x0d\x0a\x1a\x0a") {
            throw new Exception('Is not a valid PNG image');
        }
        // Loop through the chunks. Byte 0-3 is length, Byte 4-7 is type
        $chunkHeader = substr($png, $ipos, 8);
        $ipos = $ipos + 8;
        while ($chunkHeader) {
            // Extract length and type from binary data
            $chunk = @unpack('Nsize/a4type', $chunkHeader);
            $skip = false;
            if ($chunk['type'] == $chunkType) {
                $data = substr($png, $ipos, $chunk['size']);
                $sections = explode("\0", $data);
                print_r($sections);
                if ($sections[0] == $key) {
                    $skip = true;
                }
            }
            // Extract the data and the CRC
            $data = substr($png, $ipos, $chunk['size'] + 4);
            $ipos = $ipos + $chunk['size'] + 4;
            // Add in the header, data, and CRC
            if (!$skip) {
                $retval = $retval.$chunkHeader.$data;
            }
            // Read next chunk header
            $chunkHeader = substr($png, $ipos, 8);
            $ipos = $ipos + 8;
        }

        return $retval;
    }

    /**
     * Extracts the baked PNG info by the Key.
     *
     * @param string $png the png image
     * @param string $key keyword that needs to be searched
     *
     * @return mixed - If there is an error - boolean false is returned
     *               If there is PNG information that matches the key an array is returned
     */
    public function extractBadgeInfo($png, $key = 'openbadges')
    {
        // Read the magic bytes and verify
        $retval = substr($png, 0, 8);
        $ipos = 8;
        if ("\x89PNG\x0d\x0a\x1a\x0a" != $retval) {
            return false;
        }

        // Loop through the chunks. Byte 0-3 is length, Byte 4-7 is type
        $chunkHeader = substr($png, $ipos, 8);
        $ipos = $ipos + 8;
        while ($chunkHeader) {
            // Extract length and type from binary data
            $chunk = @unpack('Nsize/a4type', $chunkHeader);
            $skip = false;
            if ('tEXt' == $chunk['type']) {
                $data = substr($png, $ipos, $chunk['size']);
                $sections = explode("\0", $data);
                if ($sections[0] == $key) {
                    return $sections;
                }
            }
            // Extract the data and the CRC
            $data = substr($png, $ipos, $chunk['size'] + 4);
            $ipos = $ipos + $chunk['size'] + 4;

            // Read next chunk header
            $chunkHeader = substr($png, $ipos, 8);
            $ipos = $ipos + 8;
        }
    }
}
