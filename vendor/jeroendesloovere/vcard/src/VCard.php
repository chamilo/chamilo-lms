<?php

namespace JeroenDesloovere\VCard;

/*
 * This file is part of the VCard PHP Class from Jeroen Desloovere.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Behat\Transliterator\Transliterator;

/**
 * VCard PHP Class to generate .vcard files and save them to a file or output as a download.
 */
class VCard
{
    /**
     * definedElements
     *
     * @var array
     */
    private $definedElements;

    /**
     * Filename
     *
     * @var string
     */
    private $filename;

    /**
     * Save Path
     *
     * @var string
     */
    private $savePath = null;

    /**
     * Multiple properties for element allowed
     *
     * @var array
     */
    private $multiplePropertiesForElementAllowed = [
        'email',
        'address',
        'phoneNumber',
        'url',
        'label'
    ];

    /**
     * Properties
     *
     * @var array
     */
    private $properties;

    /**
     * Default Charset
     *
     * @var string
     */
    public $charset = 'utf-8';

    /**
     * Add address
     *
     * @param  string [optional] $name
     * @param  string [optional] $extended
     * @param  string [optional] $street
     * @param  string [optional] $city
     * @param  string [optional] $region
     * @param  string [optional] $zip
     * @param  string [optional] $country
     * @param  string [optional] $type
     *                                     $type may be DOM | INTL | POSTAL | PARCEL | HOME | WORK
     *                                     or any combination of these: e.g. "WORK;PARCEL;POSTAL"
     * @return $this
     */
    public function addAddress(
        $name = '',
        $extended = '',
        $street = '',
        $city = '',
        $region = '',
        $zip = '',
        $country = '',
        $type = 'WORK;POSTAL'
    ) {
        // init value
        $value = $name . ';' . $extended . ';' . $street . ';' . $city . ';' . $region . ';' . $zip . ';' . $country;

        // set property
        $this->setProperty(
            'address',
            'ADR' . (($type != '') ? ';' . $type : '') . $this->getCharsetString(),
            $value
        );

        return $this;
    }

    /**
     * Add birthday
     *
     * @param  string $date Format is YYYY-MM-DD
     * @return $this
     */
    public function addBirthday($date)
    {
        $this->setProperty(
            'birthday',
            'BDAY',
            $date
        );

        return $this;
    }

    /**
     * Add company
     *
     * @param string $company
     * @param string $department
     * @return $this
     */
    public function addCompany($company, $department = '')
    {
        $this->setProperty(
            'company',
            'ORG' . $this->getCharsetString(),
            $company
            . ($department != '' ? ';' . $department : '')
        );

        // if filename is empty, add to filename
        if ($this->filename === null) {
            $this->setFilename($company);
        }

        return $this;
    }

    /**
     * Add email
     *
     * @param  string $address The e-mail address
     * @param  string [optional] $type    The type of the email address
     *                                    $type may be  PREF | WORK | HOME
     *                                    or any combination of these: e.g. "PREF;WORK"
     * @return $this
     */
    public function addEmail($address, $type = '')
    {
        $this->setProperty(
            'email',
            'EMAIL;INTERNET' . (($type != '') ? ';' . $type : ''),
            $address
        );

        return $this;
    }

    /**
     * Add jobtitle
     *
     * @param  string $jobtitle The jobtitle for the person.
     * @return $this
     */
    public function addJobtitle($jobtitle)
    {
        $this->setProperty(
            'jobtitle',
            'TITLE' . $this->getCharsetString(),
            $jobtitle
        );

        return $this;
    }

    /**
     * Add a label
     *
     * @param string $label
     * @param string $type
     *
     * @return $this
     */
    public function addLabel($label, $type = '')
    {
        $this->setProperty(
            'label',
            'LABEL' . ($type !== '' ? ';' . $type : ''),
            $label
        );

        return $this;
    }

    /**
     * Add role
     *
     * @param  string $role The role for the person.
     * @return $this
     */
    public function addRole($role)
    {
        $this->setProperty(
            'role',
            'ROLE' . $this->getCharsetString(),
            $role
        );

        return $this;
    }

    /**
     * Add a photo or logo (depending on property name)
     *
     * @param string $property LOGO|PHOTO
     * @param string $url image url or filename
     * @param bool $include Do we include the image in our vcard or not?
     * @param string $element The name of the element to set
     * @throws VCardException
     */
    private function addMedia($property, $url, $include = true, $element)
    {
        $mimeType = null;

        //Is this URL for a remote resource?
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $headers = get_headers($url, 1);

            if (array_key_exists('Content-Type', $headers)) {
                $mimeType = $headers['Content-Type'];
                if (is_array($mimeType)) {
                    $mimeType = end($mimeType);
                }
            }
        } else {
            //Local file, so inspect it directly
            $mimeType = mime_content_type($url);
        }
        if (strpos($mimeType, ';') !== false) {
            $mimeType = strstr($mimeType, ';', true);
        }
        if (!is_string($mimeType) || substr($mimeType, 0, 6) !== 'image/') {
            throw VCardException::invalidImage();
        }
        $fileType = strtoupper(substr($mimeType, 6));

        if ($include) {
            if ((bool) ini_get('allow_url_fopen') === true) {
                $value = file_get_contents($url);
            } else {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                $value = curl_exec($curl);
                curl_close($curl);
            }

            if (!$value) {
                throw VCardException::emptyURL();
            }

            $value = base64_encode($value);
            $property .= ";ENCODING=b;TYPE=" . $fileType;
        } else {
            if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $propertySuffix = ';VALUE=URL';
                $propertySuffix .= ';TYPE=' . strtoupper($fileType);

                $property = $property . $propertySuffix;
                $value = $url;
            } else {
                $value = $url;
            }
        }

        $this->setProperty(
            $element,
            $property,
            $value
        );
    }

    /**
     * Add a photo or logo (depending on property name)
     *
     * @param string $property LOGO|PHOTO
     * @param string $content image content
     * @param string $element The name of the element to set
     */
    private function addMediaContent($property, $content, $element)
    {
        $finfo = new \finfo();
        $mimeType = $finfo->buffer($content, FILEINFO_MIME_TYPE);

        if (strpos($mimeType, ';') !== false) {
            $mimeType = strstr($mimeType, ';', true);
        }
        if (!is_string($mimeType) || substr($mimeType, 0, 6) !== 'image/') {
            throw VCardException::invalidImage();
        }
        $fileType = strtoupper(substr($mimeType, 6));

        $content = base64_encode($content);
        $property .= ";ENCODING=b;TYPE=" . $fileType;

        $this->setProperty(
            $element,
            $property,
            $content
        );
    }

    /**
     * Add name
     *
     * @param  string [optional] $lastName
     * @param  string [optional] $firstName
     * @param  string [optional] $additional
     * @param  string [optional] $prefix
     * @param  string [optional] $suffix
     * @return $this
     */
    public function addName(
        $lastName = '',
        $firstName = '',
        $additional = '',
        $prefix = '',
        $suffix = ''
    ) {
        // define values with non-empty values
        $values = array_filter([
            $prefix,
            $firstName,
            $additional,
            $lastName,
            $suffix,
        ]);

        // define filename
        $this->setFilename($values);

        // set property
        $property = $lastName . ';' . $firstName . ';' . $additional . ';' . $prefix . ';' . $suffix;
        $this->setProperty(
            'name',
            'N' . $this->getCharsetString(),
            $property
        );

        // is property FN set?
        if (!$this->hasProperty('FN')) {
            // set property
            $this->setProperty(
                'fullname',
                'FN' . $this->getCharsetString(),
                trim(implode(' ', $values))
            );
        }

        return $this;
    }

    /**
     * Add note
     *
     * @param  string $note
     * @return $this
     */
    public function addNote($note)
    {
        $this->setProperty(
            'note',
            'NOTE' . $this->getCharsetString(),
            $note
        );

        return $this;
    }

    /**
     * Add categories
     *
     * @param array $categories
     * @return $this
     */
    public function addCategories($categories)
    {
        $this->setProperty(
            'categories',
            'CATEGORIES' . $this->getCharsetString(),
            trim(implode(',', $categories))
        );

        return $this;
    }

    /**
     * Add phone number
     *
     * @param  string $number
     * @param  string [optional] $type
     *                                   Type may be PREF | WORK | HOME | VOICE | FAX | MSG |
     *                                   CELL | PAGER | BBS | CAR | MODEM | ISDN | VIDEO
     *                                   or any senseful combination, e.g. "PREF;WORK;VOICE"
     * @return $this
     */
    public function addPhoneNumber($number, $type = '')
    {
        $this->setProperty(
            'phoneNumber',
            'TEL' . (($type != '') ? ';' . $type : ''),
            $number
        );

        return $this;
    }

    /**
     * Add Logo
     *
     * @param  string $url image url or filename
     * @param  bool $include Include the image in our vcard?
     * @return $this
     */
    public function addLogo($url, $include = true)
    {
        $this->addMedia(
            'LOGO',
            $url,
            $include,
            'logo'
        );

        return $this;
    }

    /**
     * Add Logo content
     *
     * @param  string $content image content
     * @return $this
     */
    public function addLogoContent($content)
    {
        $this->addMediaContent(
            'LOGO',
            $content,
            'logo'
        );

        return $this;
    }

    /**
     * Add Photo
     *
     * @param  string $url image url or filename
     * @param  bool $include Include the image in our vcard?
     * @return $this
     */
    public function addPhoto($url, $include = true)
    {
        $this->addMedia(
            'PHOTO',
            $url,
            $include,
            'photo'
        );

        return $this;
    }

    /**
     * Add Photo content
     *
     * @param  string $content image content
     * @return $this
     */
    public function addPhotoContent($content)
    {
        $this->addMediaContent(
            'PHOTO',
            $content,
            'photo'
        );

        return $this;
    }

    /**
     * Add URL
     *
     * @param  string $url
     * @param  string [optional] $type Type may be WORK | HOME
     * @return $this
     */
    public function addURL($url, $type = '')
    {
        $this->setProperty(
            'url',
            'URL' . (($type != '') ? ';' . $type : ''),
            $url
        );

        return $this;
    }

    /**
     * Build VCard (.vcf)
     *
     * @return string
     */
    public function buildVCard()
    {
        // init string
        $string = "BEGIN:VCARD\r\n";
        $string .= "VERSION:3.0\r\n";
        $string .= "REV:" . date("Y-m-d") . "T" . date("H:i:s") . "Z\r\n";

        // loop all properties
        $properties = $this->getProperties();
        foreach ($properties as $property) {
            // add to string
            $string .= $this->fold($property['key'] . ':' . $this->escape($property['value']) . "\r\n");
        }

        // add to string
        $string .= "END:VCARD\r\n";

        // return
        return $string;
    }

    /**
     * Build VCalender (.ics) - Safari (< iOS 8) can not open .vcf files, so we have build a workaround.
     *
     * @return string
     */
    public function buildVCalendar()
    {
        // init dates
        $dtstart = date("Ymd") . "T" . date("Hi") . "00";
        $dtend = date("Ymd") . "T" . date("Hi") . "01";

        // init string
        $string = "BEGIN:VCALENDAR\n";
        $string .= "VERSION:2.0\n";
        $string .= "BEGIN:VEVENT\n";
        $string .= "DTSTART;TZID=Europe/London:" . $dtstart . "\n";
        $string .= "DTEND;TZID=Europe/London:" . $dtend . "\n";
        $string .= "SUMMARY:Click attached contact below to save to your contacts\n";
        $string .= "DTSTAMP:" . $dtstart . "Z\n";
        $string .= "ATTACH;VALUE=BINARY;ENCODING=BASE64;FMTTYPE=text/directory;\n";
        $string .= " X-APPLE-FILENAME=" . $this->getFilename() . "." . $this->getFileExtension() . ":\n";

        // base64 encode it so that it can be used as an attachemnt to the "dummy" calendar appointment
        $b64vcard = base64_encode($this->buildVCard());

        // chunk the single long line of b64 text in accordance with RFC2045
        // (and the exact line length determined from the original .ics file exported from Apple calendar
        $b64mline = chunk_split($b64vcard, 74, "\n");

        // need to indent all the lines by 1 space for the iphone (yes really?!!)
        $b64final = preg_replace('/(.+)/', ' $1', $b64mline);
        $string .= $b64final;

        // output the correctly formatted encoded text
        $string .= "END:VEVENT\n";
        $string .= "END:VCALENDAR\n";

        // return
        return $string;
    }

    /**
     * Returns the browser user agent string.
     *
     * @return string
     */
    protected function getUserAgent()
    {
        if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
            $browser = strtolower($_SERVER['HTTP_USER_AGENT']);
        } else {
            $browser = 'unknown';
        }

        return $browser;
    }

    /**
     * Decode
     *
     * @param  string $value The value to decode
     * @return string decoded
     */
    private function decode($value)
    {
        // convert cyrlic, greek or other caracters to ASCII characters
        return Transliterator::transliterate($value);
    }

    /**
     * Download a vcard or vcal file to the browser.
     */
    public function download()
    {
        // define output
        $output = $this->getOutput();

        foreach ($this->getHeaders(false) as $header) {
            header($header);
        }

        // echo the output and it will be a download
        echo $output;
    }

    /**
     * Fold a line according to RFC2425 section 5.8.1.
     *
     * @link http://tools.ietf.org/html/rfc2425#section-5.8.1
     * @param  string $text
     * @return mixed
     */
    protected function fold($text)
    {
        if (strlen($text) <= 75) {
            return $text;
        }

        // split, wrap and trim trailing separator
        return substr($this->chunk_split_unicode($text, 75, "\r\n "), 0, -3);
    }

    /**
     * multibyte word chunk split
     * @link http://php.net/manual/en/function.chunk-split.php#107711
     * 
     * @param  string  $body     The string to be chunked.
     * @param  integer $chunklen The chunk length.
     * @param  string  $end      The line ending sequence.
     * @return string            Chunked string
     */
    protected function chunk_split_unicode($body, $chunklen = 76, $end = "\r\n")
    {
        $array = array_chunk(
            preg_split("//u", $body, -1, PREG_SPLIT_NO_EMPTY), $chunklen);
        $body = "";
        foreach ($array as $item) {
            $body .= join("", $item) . $end;
        }
        return $body;
    }

    /**
     * Escape newline characters according to RFC2425 section 5.8.4.
     *
     * @link http://tools.ietf.org/html/rfc2425#section-5.8.4
     * @param  string $text
     * @return string
     */
    protected function escape($text)
    {
        $text = str_replace("\r\n", "\\n", $text);
        $text = str_replace("\n", "\\n", $text);

        return $text;
    }

    /**
     * Get output as string
     * @deprecated in the future
     *
     * @return string
     */
    public function get()
    {
        return $this->getOutput();
    }

    /**
     * Get charset
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Get charset string
     *
     * @return string
     */
    public function getCharsetString()
    {
        return ';CHARSET=' . $this->charset;
    }

    /**
     * Get content type
     *
     * @return string
     */
    public function getContentType()
    {
        return ($this->isIOS7()) ?
            'text/x-vcalendar' : 'text/x-vcard';
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        if (!$this->filename) {
            return 'unknown';
        }

        return $this->filename;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getFileExtension()
    {
        return ($this->isIOS7()) ?
            'ics' : 'vcf';
    }

    /**
     * Get headers
     *
     * @param  bool $asAssociative
     * @return array
     */
    public function getHeaders($asAssociative)
    {
        $contentType = $this->getContentType() . '; charset=' . $this->getCharset();
        $contentDisposition = 'attachment; filename=' . $this->getFilename() . '.' . $this->getFileExtension();
        $contentLength = mb_strlen($this->getOutput(), $this->getCharset());
        $connection = 'close';

        if ((bool)$asAssociative) {
            return [
                'Content-type' => $contentType,
                'Content-Disposition' => $contentDisposition,
                'Content-Length' => $contentLength,
                'Connection' => $connection,
            ];
        }

        return [
            'Content-type: ' . $contentType,
            'Content-Disposition: ' . $contentDisposition,
            'Content-Length: ' . $contentLength,
            'Connection: ' . $connection,
        ];
    }

    /**
     * Get output as string
     * iOS devices (and safari < iOS 8 in particular) can not read .vcf (= vcard) files.
     * So I build a workaround to build a .ics (= vcalender) file.
     *
     * @return string
     */
    public function getOutput()
    {
        $output = ($this->isIOS7()) ?
            $this->buildVCalendar() : $this->buildVCard();

        return $output;
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Has property
     *
     * @param  string $key
     * @return bool
     */
    public function hasProperty($key)
    {
        $properties = $this->getProperties();

        foreach ($properties as $property) {
            if ($property['key'] === $key && $property['value'] !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Is iOS - Check if the user is using an iOS-device
     *
     * @return bool
     */
    public function isIOS()
    {
        // get user agent
        $browser = $this->getUserAgent();

        return (strpos($browser, 'iphone') || strpos($browser, 'ipod') || strpos($browser, 'ipad'));
    }

    /**
     * Is iOS less than 7 (should cal wrapper be returned)
     *
     * @return bool
     */
    public function isIOS7()
    {
        return ($this->isIOS() && $this->shouldAttachmentBeCal());
    }

    /**
     * Save to a file
     *
     * @return void
     */
    public function save()
    {
        $file = $this->getFilename() . '.' . $this->getFileExtension();

        // Add save path if given
        if (null !== $this->savePath) {
            $file = $this->savePath . $file;
        }

        file_put_contents(
            $file,
            $this->getOutput()
        );
    }

    /**
     * Set charset
     *
     * @param  mixed $charset
     * @return void
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * Set filename
     *
     * @param  mixed $value
     * @param  bool $overwrite [optional] Default overwrite is true
     * @param  string $separator [optional] Default separator is an underscore '_'
     * @return void
     */
    public function setFilename($value, $overwrite = true, $separator = '_')
    {
        // recast to string if $value is array
        if (is_array($value)) {
            $value = implode($separator, $value);
        }

        // trim unneeded values
        $value = trim($value, $separator);

        // remove all spaces
        $value = preg_replace('/\s+/', $separator, $value);

        // if value is empty, stop here
        if (empty($value)) {
            return;
        }

        // decode value + lowercase the string
        $value = strtolower($this->decode($value));

        // urlize this part
        $value = Transliterator::urlize($value);

        // overwrite filename or add to filename using a prefix in between
        $this->filename = ($overwrite) ?
            $value : $this->filename . $separator . $value;
    }

    /**
     * Set the save path directory
     *
     * @param  string $savePath Save Path
     * @throws VCardException
     */
    public function setSavePath($savePath)
    {
        if (!is_dir($savePath)) {
            throw VCardException::outputDirectoryNotExists();
        }

        // Add trailing directory separator the save path
        if (substr($savePath, -1) != DIRECTORY_SEPARATOR) {
            $savePath .= DIRECTORY_SEPARATOR;
        }

        $this->savePath = $savePath;
    }

    /**
     * Set property
     *
     * @param  string $element The element name you want to set, f.e.: name, email, phoneNumber, ...
     * @param  string $key
     * @param  string $value
     * @throws VCardException
     */
    private function setProperty($element, $key, $value)
    {
        if (!in_array($element, $this->multiplePropertiesForElementAllowed)
            && isset($this->definedElements[$element])
        ) {
            throw VCardException::elementAlreadyExists($element);
        }

        // we define that we set this element
        $this->definedElements[$element] = true;

        // adding property
        $this->properties[] = [
            'key' => $key,
            'value' => $value
        ];
    }

    /**
     * Checks if we should return vcard in cal wrapper
     *
     * @return bool
     */
    protected function shouldAttachmentBeCal()
    {
        $browser = $this->getUserAgent();

        $matches = [];
        preg_match('/os (\d+)_(\d+)\s+/', $browser, $matches);
        $version = isset($matches[1]) ? ((int)$matches[1]) : 999;

        return ($version < 8);
    }
}
