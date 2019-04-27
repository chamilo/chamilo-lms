<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MajorBrand extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'MajorBrand';

    protected $FullName = 'QuickTime::FileType';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Major Brand';

    protected $MaxLength = 4;

    protected $Values = array(
        '3g2a' => array(
            'Id' => '3g2a',
            'Label' => '3GPP2 Media (.3G2) compliant with 3GPP2 C.S0050-0 V1.0',
        ),
        '3g2b' => array(
            'Id' => '3g2b',
            'Label' => '3GPP2 Media (.3G2) compliant with 3GPP2 C.S0050-A V1.0.0',
        ),
        '3g2c' => array(
            'Id' => '3g2c',
            'Label' => '3GPP2 Media (.3G2) compliant with 3GPP2 C.S0050-B v1.0',
        ),
        '3ge6' => array(
            'Id' => '3ge6',
            'Label' => '3GPP (.3GP) Release 6 MBMS Extended Presentations',
        ),
        '3ge7' => array(
            'Id' => '3ge7',
            'Label' => '3GPP (.3GP) Release 7 MBMS Extended Presentations',
        ),
        '3gg6' => array(
            'Id' => '3gg6',
            'Label' => '3GPP Release 6 General Profile',
        ),
        '3gp1' => array(
            'Id' => '3gp1',
            'Label' => '3GPP Media (.3GP) Release 1 (probably non-existent)',
        ),
        '3gp2' => array(
            'Id' => '3gp2',
            'Label' => '3GPP Media (.3GP) Release 2 (probably non-existent)',
        ),
        '3gp3' => array(
            'Id' => '3gp3',
            'Label' => '3GPP Media (.3GP) Release 3 (probably non-existent)',
        ),
        '3gp4' => array(
            'Id' => '3gp4',
            'Label' => '3GPP Media (.3GP) Release 4',
        ),
        '3gp5' => array(
            'Id' => '3gp5',
            'Label' => '3GPP Media (.3GP) Release 5',
        ),
        '3gp6' => array(
            'Id' => '3gp6',
            'Label' => '3GPP Media (.3GP) Release 6 Streaming Servers',
        ),
        '3gs7' => array(
            'Id' => '3gs7',
            'Label' => '3GPP Media (.3GP) Release 7 Streaming Servers',
        ),
        'CAEP' => array(
            'Id' => 'CAEP',
            'Label' => 'Canon Digital Camera',
        ),
        'CDes' => array(
            'Id' => 'CDes',
            'Label' => 'Convergent Design',
        ),
        'F4A ' => array(
            'Id' => 'F4A ',
            'Label' => 'Audio for Adobe Flash Player 9+ (.F4A)',
        ),
        'F4B ' => array(
            'Id' => 'F4B ',
            'Label' => 'Audio Book for Adobe Flash Player 9+ (.F4B)',
        ),
        'F4P ' => array(
            'Id' => 'F4P ',
            'Label' => 'Protected Video for Adobe Flash Player 9+ (.F4P)',
        ),
        'F4V ' => array(
            'Id' => 'F4V ',
            'Label' => 'Video for Adobe Flash Player 9+ (.F4V)',
        ),
        'JP2 ' => array(
            'Id' => 'JP2 ',
            'Label' => 'JPEG 2000 Image (.JP2) [ISO 15444-1 ?]',
        ),
        'JP20' => array(
            'Id' => 'JP20',
            'Label' => 'Unknown, from GPAC samples (prob non-existent)',
        ),
        'KDDI' => array(
            'Id' => 'KDDI',
            'Label' => '3GPP2 EZmovie for KDDI 3G cellphones',
        ),
        'M4A ' => array(
            'Id' => 'M4A ',
            'Label' => 'Apple iTunes AAC-LC (.M4A) Audio',
        ),
        'M4B ' => array(
            'Id' => 'M4B ',
            'Label' => 'Apple iTunes AAC-LC (.M4B) Audio Book',
        ),
        'M4P ' => array(
            'Id' => 'M4P ',
            'Label' => 'Apple iTunes AAC-LC (.M4P) AES Protected Audio',
        ),
        'M4V ' => array(
            'Id' => 'M4V ',
            'Label' => 'Apple iTunes Video (.M4V) Video',
        ),
        'M4VH' => array(
            'Id' => 'M4VH',
            'Label' => 'Apple TV (.M4V)',
        ),
        'M4VP' => array(
            'Id' => 'M4VP',
            'Label' => 'Apple iPhone (.M4V)',
        ),
        'MPPI' => array(
            'Id' => 'MPPI',
            'Label' => 'Photo Player, MAF [ISO/IEC 23000-3]',
        ),
        'MSNV' => array(
            'Id' => 'MSNV',
            'Label' => 'MPEG-4 (.MP4) for SonyPSP',
        ),
        'NDAS' => array(
            'Id' => 'NDAS',
            'Label' => 'MP4 v2 [ISO 14496-14] Nero Digital AAC Audio',
        ),
        'NDSC' => array(
            'Id' => 'NDSC',
            'Label' => 'MPEG-4 (.MP4) Nero Cinema Profile',
        ),
        'NDSH' => array(
            'Id' => 'NDSH',
            'Label' => 'MPEG-4 (.MP4) Nero HDTV Profile',
        ),
        'NDSM' => array(
            'Id' => 'NDSM',
            'Label' => 'MPEG-4 (.MP4) Nero Mobile Profile',
        ),
        'NDSP' => array(
            'Id' => 'NDSP',
            'Label' => 'MPEG-4 (.MP4) Nero Portable Profile',
        ),
        'NDSS' => array(
            'Id' => 'NDSS',
            'Label' => 'MPEG-4 (.MP4) Nero Standard Profile',
        ),
        'NDXC' => array(
            'Id' => 'NDXC',
            'Label' => 'H.264/MPEG-4 AVC (.MP4) Nero Cinema Profile',
        ),
        'NDXH' => array(
            'Id' => 'NDXH',
            'Label' => 'H.264/MPEG-4 AVC (.MP4) Nero HDTV Profile',
        ),
        'NDXM' => array(
            'Id' => 'NDXM',
            'Label' => 'H.264/MPEG-4 AVC (.MP4) Nero Mobile Profile',
        ),
        'NDXP' => array(
            'Id' => 'NDXP',
            'Label' => 'H.264/MPEG-4 AVC (.MP4) Nero Portable Profile',
        ),
        'NDXS' => array(
            'Id' => 'NDXS',
            'Label' => 'H.264/MPEG-4 AVC (.MP4) Nero Standard Profile',
        ),
        'ROSS' => array(
            'Id' => 'ROSS',
            'Label' => 'Ross Video',
        ),
        'XAVC' => array(
            'Id' => 'XAVC',
            'Label' => 'Sony XAVC',
        ),
        'aax ' => array(
            'Id' => 'aax ',
            'Label' => 'Audible Enhanced Audiobook (.AAX)',
        ),
        'avc1' => array(
            'Id' => 'avc1',
            'Label' => 'MP4 Base w/ AVC ext [ISO 14496-12:2005]',
        ),
        'caqv' => array(
            'Id' => 'caqv',
            'Label' => 'Casio Digital Camera',
        ),
        'da0a' => array(
            'Id' => 'da0a',
            'Label' => 'DMB MAF w/ MPEG Layer II aud, MOT slides, DLS, JPG/PNG/MNG images',
        ),
        'da0b' => array(
            'Id' => 'da0b',
            'Label' => 'DMB MAF, extending DA0A, with 3GPP timed text, DID, TVA, REL, IPMP',
        ),
        'da1a' => array(
            'Id' => 'da1a',
            'Label' => 'DMB MAF audio with ER-BSAC audio, JPG/PNG/MNG images',
        ),
        'da1b' => array(
            'Id' => 'da1b',
            'Label' => 'DMB MAF, extending da1a, with 3GPP timed text, DID, TVA, REL, IPMP',
        ),
        'da2a' => array(
            'Id' => 'da2a',
            'Label' => 'DMB MAF aud w/ HE-AAC v2 aud, MOT slides, DLS, JPG/PNG/MNG images',
        ),
        'da2b' => array(
            'Id' => 'da2b',
            'Label' => 'DMB MAF, extending da2a, with 3GPP timed text, DID, TVA, REL, IPMP',
        ),
        'da3a' => array(
            'Id' => 'da3a',
            'Label' => 'DMB MAF aud with HE-AAC aud, JPG/PNG/MNG images',
        ),
        'da3b' => array(
            'Id' => 'da3b',
            'Label' => 'DMB MAF, extending da3a w/ BIFS, 3GPP timed text, DID, TVA, REL, IPMP',
        ),
        'dmb1' => array(
            'Id' => 'dmb1',
            'Label' => 'DMB MAF supporting all the components defined in the specification',
        ),
        'dmpf' => array(
            'Id' => 'dmpf',
            'Label' => 'Digital Media Project',
        ),
        'drc1' => array(
            'Id' => 'drc1',
            'Label' => 'Dirac (wavelet compression), encapsulated in ISO base media (MP4)',
        ),
        'dv1a' => array(
            'Id' => 'dv1a',
            'Label' => 'DMB MAF vid w/ AVC vid, ER-BSAC aud, BIFS, JPG/PNG/MNG images, TS',
        ),
        'dv1b' => array(
            'Id' => 'dv1b',
            'Label' => 'DMB MAF, extending dv1a, with 3GPP timed text, DID, TVA, REL, IPMP',
        ),
        'dv2a' => array(
            'Id' => 'dv2a',
            'Label' => 'DMB MAF vid w/ AVC vid, HE-AAC v2 aud, BIFS, JPG/PNG/MNG images, TS',
        ),
        'dv2b' => array(
            'Id' => 'dv2b',
            'Label' => 'DMB MAF, extending dv2a, with 3GPP timed text, DID, TVA, REL, IPMP',
        ),
        'dv3a' => array(
            'Id' => 'dv3a',
            'Label' => 'DMB MAF vid w/ AVC vid, HE-AAC aud, BIFS, JPG/PNG/MNG images, TS',
        ),
        'dv3b' => array(
            'Id' => 'dv3b',
            'Label' => 'DMB MAF, extending dv3a, with 3GPP timed text, DID, TVA, REL, IPMP',
        ),
        'dvr1' => array(
            'Id' => 'dvr1',
            'Label' => 'DVB (.DVB) over RTP',
        ),
        'dvt1' => array(
            'Id' => 'dvt1',
            'Label' => 'DVB (.DVB) over MPEG-2 Transport Stream',
        ),
        'isc2' => array(
            'Id' => 'isc2',
            'Label' => 'ISMACryp 2.0 Encrypted File',
        ),
        'iso2' => array(
            'Id' => 'iso2',
            'Label' => 'MP4 Base Media v2 [ISO 14496-12:2005]',
        ),
        'isom' => array(
            'Id' => 'isom',
            'Label' => 'MP4  Base Media v1 [IS0 14496-12:2003]',
        ),
        'jpm ' => array(
            'Id' => 'jpm ',
            'Label' => 'JPEG 2000 Compound Image (.JPM) [ISO 15444-6]',
        ),
        'jpx ' => array(
            'Id' => 'jpx ',
            'Label' => 'JPEG 2000 with extensions (.JPX) [ISO 15444-2]',
        ),
        'mj2s' => array(
            'Id' => 'mj2s',
            'Label' => 'Motion JPEG 2000 [ISO 15444-3] Simple Profile',
        ),
        'mjp2' => array(
            'Id' => 'mjp2',
            'Label' => 'Motion JPEG 2000 [ISO 15444-3] General Profile',
        ),
        'mmp4' => array(
            'Id' => 'mmp4',
            'Label' => 'MPEG-4/3GPP Mobile Profile (.MP4/3GP) (for NTT)',
        ),
        'mp21' => array(
            'Id' => 'mp21',
            'Label' => 'MPEG-21 [ISO/IEC 21000-9]',
        ),
        'mp41' => array(
            'Id' => 'mp41',
            'Label' => 'MP4 v1 [ISO 14496-1:ch13]',
        ),
        'mp42' => array(
            'Id' => 'mp42',
            'Label' => 'MP4 v2 [ISO 14496-14]',
        ),
        'mp71' => array(
            'Id' => 'mp71',
            'Label' => 'MP4 w/ MPEG-7 Metadata [per ISO 14496-12]',
        ),
        'mqt ' => array(
            'Id' => 'mqt ',
            'Label' => 'Sony / Mobile QuickTime (.MQV) US Patent 7,477,830 (Sony Corp)',
        ),
        'odcf' => array(
            'Id' => 'odcf',
            'Label' => 'OMA DCF DRM Format 2.0 (OMA-TS-DRM-DCF-V2_0-20060303-A)',
        ),
        'opf2' => array(
            'Id' => 'opf2',
            'Label' => 'OMA PDCF DRM Format 2.1 (OMA-TS-DRM-DCF-V2_1-20070724-C)',
        ),
        'opx2' => array(
            'Id' => 'opx2',
            'Label' => 'OMA PDCF DRM + XBS extensions (OMA-TS-DRM_XBS-V1_0-20070529-C)',
        ),
        'pana' => array(
            'Id' => 'pana',
            'Label' => 'Panasonic Digital Camera',
        ),
        'qt  ' => array(
            'Id' => 'qt  ',
            'Label' => 'Apple QuickTime (.MOV/QT)',
        ),
        'sdv ' => array(
            'Id' => 'sdv ',
            'Label' => 'SD Memory Card Video',
        ),
        'ssc1' => array(
            'Id' => 'ssc1',
            'Label' => 'Samsung stereoscopic, single stream',
        ),
        'ssc2' => array(
            'Id' => 'ssc2',
            'Label' => 'Samsung stereoscopic, dual stream',
        ),
    );

}
