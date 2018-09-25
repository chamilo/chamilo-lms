<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Entity\SystemTemplate;

/**
 * Class Version20160804174600
 * Set doctype html5 for system templates
 * @package Application\Migrations\Schema\V111
 */
class Version20160804174600 extends AbstractMigrationChamilo
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $em = $this->getEntityManager();

        $templateTitleCourseTitle = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
                <style type="text/css">
                    .gris_title {
                        color: silver;
                    }
            
                    h1 {
                        text-align: right;
                    }
                </style>
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td style="vertical-align: middle; width: 50%;" colspan="1" rowspan="1">
                        <h1>TITULUS 1<br>
                            <span class="gris_title">TITULUS 2</span><br>
                        </h1>
                    </td>
                    <td style="width: 50%;">
                        <img style="width: 100px; height: 100px;" alt="Chamilo logo" src="{COURSE_DIR}images/logo_chamilo.png">
                    </td>
                </tr>
                </tbody>
            </table>
            <p>
                <br><br>
            </p>
            </body>
            </html>
        ';
        $templateTitleTeacher = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
                <style type="text/css">
                    .text {
                        font-weight: normal;
                    }
                </style>
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td></td>
                    <td style="height: 33%;"></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="width: 25%;"></td>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right; font-weight: bold;"
                        colspan="1" rowspan="1">
                <span class="text">
                <br>
                Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque.</span>
                    </td>
                    <td style="width: 25%; font-weight: bold;">
                        <img style="width: 180px; height: 241px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_case.png ">
                    </td>
                </tr>
                </tbody>
            </table>
            <p>
                <br><br>
            </p>
            </body>
            </html>
        ';
        $templateTitleLeftList = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td style="width: 66%;"></td>
                    <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="4">&nbsp;<img
                            style="width: 180px; height: 248px;" alt="trainer"
                            src="{COURSE_DIR}images/trainer/trainer_reads.png "><br>
                    </td>
                </tr>
                <tr align="right">
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                        Lorem
                        ipsum dolor sit amet.
                    </td>
                </tr>
                <tr align="right">
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                        Vivamus
                        a quam.&nbsp;<br>
                    </td>
                </tr>
                <tr align="right">
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                        Proin
                        a est stibulum ante ipsum.
                    </td>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';
        $templateTitleLeftRightList = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; height: 400px; width: 720px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td></td>
                    <td style="vertical-align: top;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 294px;"
                                                                                        alt="Trainer"
                                                                                        src="{COURSE_DIR}images/trainer/trainer_join_hands.png "><br>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
                        Lorem
                        ipsum dolor sit amet.
                    </td>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                        Convallis
                        ut.&nbsp;Cras dui magna.
                    </td>
                </tr>
                <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
                        Vivamus
                        a quam.&nbsp;<br>
                    </td>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                        Etiam
                        lacinia stibulum ante.<br>
                    </td>
                </tr>
                <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
                        Proin
                        a est stibulum ante ipsum.
                    </td>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                        Consectetuer
                        adipiscing elit. <br>
                    </td>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';
        $templateTitleRightList = '
            <!DOCTYPE html>
            <html>  
            <head>
                {CSS}
            </head>
            <body style="direction: ltr;">
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td style="vertical-align: bottom; width: 50%;" colspan="1" rowspan="4"><img
                            style="width: 300px; height: 199px;" alt="trainer"
                            src="{COURSE_DIR}images/trainer/trainer_points_right.png"><br>
                    </td>
                    <td style="width: 50%;"></td>
                </tr>
                <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                        Convallis
                        ut.&nbsp;Cras dui magna.
                    </td>
                </tr>
                <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                        Etiam
                        lacinia.<br>
                    </td>
                </tr>
                <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                        Consectetuer
                        adipiscing elit. <br>
                    </td>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';
        $templateTitleDiagram = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; height: 33%; width: 33%;">
                        <br>
                        Etiam
                        lacinia stibulum ante.
                        Convallis
                        ut.&nbsp;Cras dui magna.
                    </td>
                    <td colspan="1" rowspan="3">
                        <img style="width: 350px; height: 267px;" alt="Alaska chart"
                             src="{COURSE_DIR}images/diagrams/alaska_chart.png "></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1">
                        <img style="width: 300px; height: 199px;" alt="trainer"
                             src="{COURSE_DIR}images/trainer/trainer_points_right.png "></td>
                </tr>
                <tr>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';
        $templateTitleDesc = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <img style="width: 48px; height: 49px; float: left;" alt="01" src="{COURSE_DIR}images/small/01.png "
                             hspace="5"><br>Lorem ipsum dolor sit amet<br><br><br>
                        <img style="width: 48px; height: 49px; float: left;" alt="02" src="{COURSE_DIR}images/small/02.png "
                             hspace="5">
                        <br>Ut enim ad minim veniam<br><br><br>
                        <img style="width: 48px; height: 49px; float: left;" alt="03" src="{COURSE_DIR}images/small/03.png "
                             hspace="5">Duis aute irure dolor in reprehenderit<br><br><br>
                        <img style="width: 48px; height: 49px; float: left;" alt="04" src="{COURSE_DIR}images/small/04.png "
                             hspace="5">Neque porro quisquam est
                    </td>
            
                    <td style="vertical-align: top; width: 50%; text-align: right;" colspan="1" rowspan="1">
                        <img style="width: 300px; height: 291px;" alt="Gearbox" src="{COURSE_DIR}images/diagrams/gearbox.jpg "><br>
                    </td>
                </tr>
                <tr></tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';
        $templateTitleCycle = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
                <style>
                    .title {
                        color: white;
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="8" cellspacing="6">
                <tbody>
                <tr>
                    <td style="text-align: center; vertical-align: bottom; height: 10%;" colspan="3" rowspan="1">
                        <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/top_arrow.png ">
                    </td>
                </tr>
                <tr>
                    <td style="height: 5%; width: 45%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
                        <span class="title">Lorem ipsum</span>
                    </td>
                    <td style="height: 5%; width: 10%;"></td>
                    <td style="height: 5%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
                        <span class="title">Sed ut perspiciatis</span>
                    </td>
                </tr>
                <tr>
                    <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
                        <ul>
                            <li>dolor sit amet</li>
                            <li>consectetur adipisicing elit</li>
                            <li>sed do eiusmod tempor&nbsp;</li>
                            <li>adipisci velit, sed quia non numquam</li>
                            <li>eius modi tempora incidunt ut labore et dolore magnam</li>
                        </ul>
                    </td>
                    <td style="width: 10%;"></td>
                    <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
                        <ul>
                            <li>ut enim ad minim veniam</li>
                            <li>quis nostrud exercitation</li>
                            <li>ullamco laboris nisi ut</li>
                            <li> Quis autem vel eum iure reprehenderit qui in ea</li>
                            <li>voluptate velit esse quam nihil molestiae consequatur,</li>
                        </ul>
                    </td>
                </tr>
                <tr align="center">
                    <td style="height: 10%; vertical-align: top;" colspan="3" rowspan="1">
                        <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/bottom_arrow.png ">&nbsp;&nbsp;
                        &nbsp; &nbsp; &nbsp;
                    </td>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';
        $templateTitleTimeline = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
                <style>
                    .title {
                        font-weight: bold;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="8" cellspacing="5">
                <tbody>
                <tr class="title">
                    <td style="vertical-align: top; height: 3%; background-color: rgb(224, 224, 224);">Lorem ipsum</td>
                    <td style="height: 3%;"></td>
                    <td style="vertical-align: top; height: 3%; background-color: rgb(237, 237, 237);">Perspiciatis</td>
                    <td style="height: 3%;"></td>
                    <td style="vertical-align: top; height: 3%; background-color: rgb(245, 245, 245);">Nemo enim</td>
                </tr>
                <tr>
                    <td style="vertical-align: top; width: 30%; background-color: rgb(224, 224, 224);">
                        <ul>
                            <li>dolor sit amet</li>
                            <li>consectetur</li>
                            <li>adipisicing elit</li>
                        </ul>
                        <br>
                    </td>
                    <td>
                        <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
                    </td>
                    <td style="vertical-align: top; width: 30%; background-color: rgb(237, 237, 237);">
                        <ul>
                            <li>ut labore</li>
                            <li>et dolore</li>
                            <li>magni dolores</li>
                        </ul>
                    </td>
                    <td>
                        <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
                    </td>
                    <td style="vertical-align: top; background-color: rgb(245, 245, 245); width: 30%;">
                        <ul>
                            <li>neque porro</li>
                            <li>quisquam est</li>
                            <li>qui dolorem&nbsp;&nbsp;</li>
                        </ul>
                        <br><br>
                    </td>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';
        $templateTitleTable = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
                <style type="text/css">
                    .title {
                        font-weight: bold;
                        text-align: center;
                    }
            
                    .items {
                        text-align: right;
                    }
                </style>
            </head>
            <body>
            <br/>
            <h2>A table</h2>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px;"
                   border="1" cellpadding="5" cellspacing="0">
                <tbody>
                <tr class="title">
                    <td>City</td>
                    <td>2005</td>
                    <td>2006</td>
                    <td>2007</td>
                    <td>2008</td>
                </tr>
                <tr class="items">
                    <td>Lima</td>
                    <td>10,40</td>
                    <td>8,95</td>
                    <td>9,19</td>
                    <td>9,76</td>
                </tr>
                <tr class="items">
                    <td>New York</td>
                    <td>18,39</td>
                    <td>17,52</td>
                    <td>16,57</td>
                    <td>16,60</td>
                </tr>
                <tr class="items">
                    <td>Barcelona</td>
                    <td>0,10</td>
                    <td>0,10</td>
                    <td>0,05</td>
                    <td>0,05</td>
                </tr>
                <tr class="items">
                    <td>Paris</td>
                    <td>3,38</td>
                    <td>3,63</td>
                    <td>3,63</td>
                    <td>3,54</td>
                </tr>
                </tbody>
            </table>
            <br>
            </body>
            </html>
        ';
        $templateTitleAudio = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td>
                        <div align="center">
                <span style="text-align: center;">
                    <embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"
                           width="300" height="20" bgcolor="#FFFFFF" src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
                           allowfullscreen="false" allowscriptaccess="always"
                           flashvars="file={COURSE_DIR}audio/ListeningComprehension.mp3&amp;autostart=true"></embed>
                </span></div>
            
                        <br>
                    </td>
                    <td colspan="1" rowspan="3"><br>
                        <img style="width: 300px; height: 341px; float: right;" alt="image"
                             src="{COURSE_DIR}images/diagrams/head_olfactory_nerve.png "><br></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1">
                        <img style="width: 180px; height: 271px;" alt="trainer"
                             src="{COURSE_DIR}images/trainer/trainer_glasses.png"><br></td>
                </tr>
                <tr>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';
        $templateTitleVideo = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div style="text-align: center;" id="player810625-parent">
                            <div style="border-style: none; overflow: hidden; width: 320px; height: 240px; background-color: rgb(220, 220, 220);">
                                <div id="player810625">
                                    <div id="player810625-config"
                                         style="overflow: hidden; display: none; visibility: hidden; width: 0px; height: 0px;">
                                        url={REL_PATH}main/default_course_document/video/flv/example.flv width=320 height=240
                                        loop=false play=false downloadable=false fullscreen=true displayNavigation=true
                                        displayDigits=true align=left dispPlaylist=none playlistThumbs=false
                                    </div>
                                </div>
                                <embed
                                        type="application/x-shockwave-flash"
                                        src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
                                        width="320"
                                        height="240"
                                        id="single"
                                        name="single"
                                        quality="high"
                                        allowfullscreen="true"
                                        flashvars="width=320&height=240&autostart=false&file={REL_PATH}main/default_course_document/video/flv/example.flv&repeat=false&image=&showdownload=false&link={REL_PATH}main/default_course_document/video/flv/example.flv&showdigits=true&shownavigation=true&logo="
                                />
                            </div>
                        </div>
                    </td>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 50%;">
                        <h3><br>
                        </h3>
                        <h3>Lorem ipsum dolor sit amet</h3>
                        <ul>
                            <li>consectetur adipisicing elit</li>
                            <li>sed do eiusmod tempor incididunt</li>
                            <li>ut labore et dolore magna aliqua</li>
                        </ul>
                        <h3>Ut enim ad minim veniam</h3>
                        <ul>
                            <li>quis nostrud exercitation ullamco</li>
                            <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                            <li>Excepteur sint occaecat cupidatat non proident</li>
                        </ul>
                    </td>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            <style type="text/css">body {
            }</style><!-- to fix a strange bug appearing with firefox when editing this template -->
            </body>
            </html>
        ';
        $templateTitleFlash = '
            <!DOCTYPE html>
            <html>
            <head>
                {CSS}
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 100%; height: 400px;"
                   border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                    <td align="center">
                        <embed width="700" height="300" type="application/x-shockwave-flash"
                               pluginspage="http://www.macromedia.com/go/getflashplayer"
                               src="{COURSE_DIR}flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed>
                        </span><br/>
                    </td>
                </tr>
                </tbody>
            </table>
            <p><br>
                <br>
            </p>
            </body>
            </html>
        ';

        $templates = [
            'TemplateTitleCourseTitle' => $templateTitleCourseTitle,
            'TemplateTitleTeacher' => $templateTitleTeacher,
            'TemplateTitleLeftList' => $templateTitleLeftList,
            'TemplateTitleLeftRightList' => $templateTitleLeftRightList,
            'TemplateTitleRightList' => $templateTitleRightList,
            'TemplateTitleDiagram' => $templateTitleDiagram,
            'TemplateTitleDesc' => $templateTitleDesc,
            'TemplateTitleCycle' => $templateTitleCycle,
            'TemplateTitleTimeline' => $templateTitleTimeline,
            'TemplateTitleTable' => $templateTitleTable,
            'TemplateTitleAudio' => $templateTitleAudio,
            'TemplateTitleVideo' => $templateTitleVideo,
            'TemplateTitleFlash' => $templateTitleFlash
        ];

        foreach ($templates as $title => $content) {
            /** @var SystemTemplate $tpl */
            $tpl = $em
                ->getRepository('ChamiloCoreBundle:SystemTemplate')
                ->findOneBy(['title' => $title]);
            if (!empty($tpl)) {
                $tpl->setContent($content);
                $em->merge($tpl);
            }
        }

        $em->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $em = $this->getEntityManager();

        $templateTitleCourseTitle = '
            <head>
                    {CSS}
                    <style type="text/css">
                    .gris_title         	{
                        color: silver;
                    }
                    h1
                    {
                        text-align: right;
                    }
                    </style>
                </head>
                <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="vertical-align: middle; width: 50%;" colspan="1" rowspan="1">
                    <h1>TITULUS 1<br>
                    <span class="gris_title">TITULUS 2</span><br>
                    </h1>
                </td>
                <td style="width: 50%;">
                    <img style="width: 100px; height: 100px;" alt="Chamilo logo" src="{COURSE_DIR}images/logo_chamilo.png"></td>
                </tr>
                </tbody>
                </table>
                <p><br>
                <br>
                </p>
                </body>
        ';
        $templateTitleTeacher = '
            <head>
                   {CSS}
                   <style type="text/css">
                    .text
                    {
                        font-weight: normal;
                    }
                    </style>
                </head>
            <body>
                    <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                    <tbody>
                    <tr>
                    <td></td>
                    <td style="height: 33%;"></td>
                    <td></td>
                    </tr>
                    <tr>
                    <td style="width: 25%;"></td>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right; font-weight: bold;" colspan="1" rowspan="1">
                    <span class="text">
                    <br>
                    Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque.</span>
                    </td>
                    <td style="width: 25%; font-weight: bold;">
                    <img style="width: 180px; height: 241px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_case.png "></td>
                    </tr>
                    </tbody>
                    </table>
                    <p><br>
                    <br>
                    </p>
                </body>
        ';
        $templateTitleLeftList = '
            <head>
               {CSS}
           </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="width: 66%;"></td>
                <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 248px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_reads.png "><br>
                </td>
                </tr>
                <tr align="right">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">Lorem
                ipsum dolor sit amet.
                </td>
                </tr>
                <tr align="right">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Vivamus
                a quam.&nbsp;<br>
                </td>
                </tr>
                <tr align="right">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Proin
                a est stibulum ante ipsum.</td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
        ';
        $templateTitleLeftRightList = '
            <head>
               {CSS}
            </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; height: 400px; width: 720px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td></td>
                <td style="vertical-align: top;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 294px;" alt="Trainer" src="{COURSE_DIR}images/trainer/trainer_join_hands.png "><br>
                </td>
                <td></td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">Lorem
                ipsum dolor sit amet.
                </td>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                Convallis
                ut.&nbsp;Cras dui magna.</td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
                Vivamus
                a quam.&nbsp;<br>
                </td>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                Etiam
                lacinia stibulum ante.<br>
                </td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
                Proin
                a est stibulum ante ipsum.</td>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                Consectetuer
                adipiscing elit. <br>
                </td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
        ';
        $templateTitleRightList = '
            <head>
               {CSS}
            </head>
            <body style="direction: ltr;">
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="vertical-align: bottom; width: 50%;" colspan="1" rowspan="4"><img style="width: 300px; height: 199px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_points_right.png"><br>
                </td>
                <td style="width: 50%;"></td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                Convallis
                ut.&nbsp;Cras dui magna.</td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                Etiam
                lacinia.<br>
                </td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                Consectetuer
                adipiscing elit. <br>
                </td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
        ';
        $templateTitleDiagram = '
            <head>
                   {CSS}
                </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; height: 33%; width: 33%;">
                <br>
                Etiam
                lacinia stibulum ante.
                Convallis
                ut.&nbsp;Cras dui magna.</td>
                <td colspan="1" rowspan="3">
                    <img style="width: 350px; height: 267px;" alt="Alaska chart" src="{COURSE_DIR}images/diagrams/alaska_chart.png "></td>
                </tr>
                <tr>
                <td colspan="1" rowspan="1">
                <img style="width: 300px; height: 199px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_points_right.png "></td>
                </tr>
                <tr>
                </tr>
                </tbody>
                </table>
                <p><br>
                <br>
                </p>
                </body>
        ';
        $templateTitleDesc = '
            <head>
                       {CSS}
                    </head>
            <body>
                        <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                        <tbody>
                        <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <img style="width: 48px; height: 49px; float: left;" alt="01" src="{COURSE_DIR}images/small/01.png " hspace="5"><br>Lorem ipsum dolor sit amet<br><br><br>
                            <img style="width: 48px; height: 49px; float: left;" alt="02" src="{COURSE_DIR}images/small/02.png " hspace="5">
                            <br>Ut enim ad minim veniam<br><br><br>
                            <img style="width: 48px; height: 49px; float: left;" alt="03" src="{COURSE_DIR}images/small/03.png " hspace="5">Duis aute irure dolor in reprehenderit<br><br><br>
                            <img style="width: 48px; height: 49px; float: left;" alt="04" src="{COURSE_DIR}images/small/04.png " hspace="5">Neque porro quisquam est</td>
                        <td style="vertical-align: top; width: 50%; text-align: right;" colspan="1" rowspan="1">
                            <img style="width: 300px; height: 291px;" alt="Gearbox" src="{COURSE_DIR}images/diagrams/gearbox.jpg "><br></td>
                        </tr><tr></tr>
                        </tbody>
                        </table>
                        <p><br>
                        <br>
                        </p>
                    </body>
        ';
        $templateTitleCycle = '
            <head>
                   {CSS}
                   <style>
                   .title
                   {
                       color: white; font-weight: bold;
                   }
                   </style>
                </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="8" cellspacing="6">
                <tbody>
                <tr>
                    <td style="text-align: center; vertical-align: bottom; height: 10%;" colspan="3" rowspan="1">
                        <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/top_arrow.png ">
                    </td>
                </tr>
                <tr>
                    <td style="height: 5%; width: 45%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
                        <span class="title">Lorem ipsum</span>
                    </td>
                    <td style="height: 5%; width: 10%;"></td>
                    <td style="height: 5%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
                        <span class="title">Sed ut perspiciatis</span>
                    </td>
                </tr>
                    <tr>
                        <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
                            <ul>
                                <li>dolor sit amet</li>
                                <li>consectetur adipisicing elit</li>
                                <li>sed do eiusmod tempor&nbsp;</li>
                                <li>adipisci velit, sed quia non numquam</li>
                                <li>eius modi tempora incidunt ut labore et dolore magnam</li>
                            </ul>
                </td>
                <td style="width: 10%;"></td>
                <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
                    <ul>
                    <li>ut enim ad minim veniam</li>
                    <li>quis nostrud exercitation</li><li>ullamco laboris nisi ut</li>
                    <li> Quis autem vel eum iure reprehenderit qui in ea</li>
                    <li>voluptate velit esse quam nihil molestiae consequatur,</li>
                    </ul>
                    </td>
                    </tr>
                    <tr align="center">
                    <td style="height: 10%; vertical-align: top;" colspan="3" rowspan="1">
                    <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/bottom_arrow.png ">&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;
                </td>
                </tr>
                </tbody>
                </table>
                <p><br>
                <br>
                </p>
        </body>
        ';
        $templateTitleTimeline = '
            <head>
               {CSS}
                <style>
                .title
                {
                    font-weight: bold; text-align: center;
                }
                </style>
            </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="8" cellspacing="5">
                <tbody>
                <tr class="title">
                    <td style="vertical-align: top; height: 3%; background-color: rgb(224, 224, 224);">Lorem ipsum</td>
                    <td style="height: 3%;"></td>
                    <td style="vertical-align: top; height: 3%; background-color: rgb(237, 237, 237);">Perspiciatis</td>
                    <td style="height: 3%;"></td>
                    <td style="vertical-align: top; height: 3%; background-color: rgb(245, 245, 245);">Nemo enim</td>
                </tr>
                <tr>
                    <td style="vertical-align: top; width: 30%; background-color: rgb(224, 224, 224);">
                        <ul>
                        <li>dolor sit amet</li>
                        <li>consectetur</li>
                        <li>adipisicing elit</li>
                    </ul>
                    <br>
                    </td>
                    <td>
                        <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
                    </td>
                    <td style="vertical-align: top; width: 30%; background-color: rgb(237, 237, 237);">
                        <ul>
                            <li>ut labore</li>
                            <li>et dolore</li>
                            <li>magni dolores</li>
                        </ul>
                    </td>
                    <td>
                        <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
                    </td>
                    <td style="vertical-align: top; background-color: rgb(245, 245, 245); width: 30%;">
                        <ul>
                            <li>neque porro</li>
                            <li>quisquam est</li>
                            <li>qui dolorem&nbsp;&nbsp;</li>
                        </ul>
                        <br><br>
                    </td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
        ';
        $templateTitleTable = '
            <head>
                   {CSS}
                   <style type="text/css">
                .title
                {
                    font-weight: bold; text-align: center;
                }
                .items
                {
                    text-align: right;
                }
                    </style>
                </head>
            <body>
                <br />
               <h2>A table</h2>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px;" border="1" cellpadding="5" cellspacing="0">
                <tbody>
                <tr class="title">
                    <td>City</td>
                    <td>2005</td>
                    <td>2006</td>
                    <td>2007</td>
                    <td>2008</td>
                </tr>
                <tr class="items">
                    <td>Lima</td>
                    <td>10,40</td>
                    <td>8,95</td>
                    <td>9,19</td>
                    <td>9,76</td>
                </tr>
                <tr class="items">
                <td>New York</td>
                    <td>18,39</td>
                    <td>17,52</td>
                    <td>16,57</td>
                    <td>16,60</td>
                </tr>
                <tr class="items">
                <td>Barcelona</td>
                    <td>0,10</td>
                    <td>0,10</td>
                    <td>0,05</td>
                    <td>0,05</td>
                </tr>
                <tr class="items">
                <td>Paris</td>
                    <td>3,38</td>
                    <td >3,63</td>
                    <td>3,63</td>
                    <td>3,54</td>
                </tr>
                </tbody>
                </table>
                <br>
                </body>
        ';
        $templateTitleAudio = '
            <head>
               {CSS}
            </head>
            <body>
                    <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                    <tbody>
                    <tr>
                    <td>
                    <div align="center">
                    <span style="text-align: center;">
                        <embed  type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" width="300" height="20" bgcolor="#FFFFFF" src="{REL_PATH}main/inc/lib/mediaplayer/player.swf" allowfullscreen="false" allowscriptaccess="always" flashvars="file={COURSE_DIR}audio/ListeningComprehension.mp3&amp;autostart=true"></embed>
                    </span></div>
                    <br>
                    </td>
                    <td colspan="1" rowspan="3"><br>
                        <img style="width: 300px; height: 341px; float: right;" alt="image" src="{COURSE_DIR}images/diagrams/head_olfactory_nerve.png "><br></td>
                    </tr>
                    <tr>
                    <td colspan="1" rowspan="1">
                        <img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_glasses.png"><br></td>
                    </tr>
                    <tr>
                    </tr>
                    </tbody>
                    </table>
                    <p><br>
                    <br>
                    </p>
                    </body>
        ';
        $templateTitleVideo = '
            <head>
                {CSS}
            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
            <tbody>
            <tr>
            <td style="width: 50%; vertical-align: top;">
                 <div style="text-align: center;" id="player810625-parent">
                    <div style="border-style: none; overflow: hidden; width: 320px; height: 240px; background-color: rgb(220, 220, 220);">
                        <div id="player810625">
                            <div id="player810625-config" style="overflow: hidden; display: none; visibility: hidden; width: 0px; height: 0px;">url={REL_PATH}main/default_course_document/video/flv/example.flv width=320 height=240 loop=false play=false downloadable=false fullscreen=true displayNavigation=true displayDigits=true align=left dispPlaylist=none playlistThumbs=false</div>
                        </div>
                        <embed
                            type="application/x-shockwave-flash"
                            src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
                            width="320"
                            height="240"
                            id="single"
                            name="single"
                            quality="high"
                            allowfullscreen="true"
                            flashvars="width=320&height=240&autostart=false&file={REL_PATH}main/default_course_document/video/flv/example.flv&repeat=false&image=&showdownload=false&link={REL_PATH}main/default_course_document/video/flv/example.flv&showdigits=true&shownavigation=true&logo="
                        />
                    </div>
                </div>
            </td>
            <td style="background: transparent url({IMG_DIR}faded_grey.png) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 50%;">
            <h3><br>
            </h3>
            <h3>Lorem ipsum dolor sit amet</h3>
                <ul>
                <li>consectetur adipisicing elit</li>
                <li>sed do eiusmod tempor incididunt</li>
                <li>ut labore et dolore magna aliqua</li>
                </ul>
            <h3>Ut enim ad minim veniam</h3>
                <ul>
                <li>quis nostrud exercitation ullamco</li>
                <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                <li>Excepteur sint occaecat cupidatat non proident</li>
                </ul>
            </td>
            </tr>
            </tbody>
            </table>
            <p><br>
            <br>
            </p>
             <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
            </body>
        ';
        $templateTitleFlash = '
            <head>
               {CSS}
            </head>
            <body>
            <center>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 100%; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                    <tr>
                    <td align="center">
                    <embed width="700" height="300" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="{COURSE_DIR}flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed></span><br />
                    </td>
                    </tr>
                </tbody>
                </table>
                <p><br>
                <br>
                </p>
            </center>
            </body>
        ';

        $templates = [
            'TemplateTitleCourseTitle' => $templateTitleCourseTitle,
            'TemplateTitleTeacher' => $templateTitleTeacher,
            'TemplateTitleLeftList' => $templateTitleLeftList,
            'TemplateTitleLeftRightList' => $templateTitleLeftRightList,
            'TemplateTitleRightList' => $templateTitleRightList,
            'TemplateTitleDiagram' => $templateTitleDiagram,
            'TemplateTitleDesc' => $templateTitleDesc,
            'TemplateTitleCycle' => $templateTitleCycle,
            'TemplateTitleTimeline' => $templateTitleTimeline,
            'TemplateTitleTable' => $templateTitleTable,
            'TemplateTitleAudio' => $templateTitleAudio,
            'TemplateTitleVideo' => $templateTitleVideo,
            'TemplateTitleFlash' => $templateTitleFlash
        ];

        foreach ($templates as $title => $content) {
            /** @var SystemTemplate $tpl */
            $tpl = $em
                ->getRepository('ChamiloCoreBundle:SystemTemplate')
                ->findOneBy(['title' => $title]);
            if (!empty($tpl)) {
                $tpl->setContent($content);
                $em->merge($tpl);
            }
        }

        $em->flush();
    }
}
