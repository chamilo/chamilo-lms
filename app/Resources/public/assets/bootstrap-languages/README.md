The _Languages for Bootstrap 3_ project provides a simple way to present
language labels and names in a _Bootstrap 3_ project.

Head over to the [documentation](http://usrz.github.io/bootstrap-languages)
pages for some hint on how languages are presented, and how to use this library.

This project is distributed under the terms of the
[Apache Software License, Version 2](LICENSE.md).

Install from bower
==========
```bash
bower install bootstrap-language
```


Flag Icons
==========

Flag images have originally made by [IconDrawer](http://www.icondrawer.com),
then converted and assembled using [ImageMagick](http://www.imagemagick.org/)
and optimized using [PNGCrush](http://pmt.sourceforge.net/pngcrush/index.html)
with the following script:

```bash
#!/bin/sh

SMALL=""
MEDIUM=""
LARGE=""
for COUNTRY in sa by bg cz dk de gr us es ee fi fr ie in hr hu id is it \
               il jp kr lt lv mk my mt nl no pl pt ro ru sk si al rs se \
               th tr ua vn cn "_United*Nations" ; do
     SMALL="${SMALL}  16/${COUNTRY}.png"
    MEDIUM="${MEDIUM} 24/${COUNTRY}.png"
     LARGE="${LARGE}  32/${COUNTRY}.png"
done

montage ${SMALL}  -tile 1x -crop 14x11+1+2 -background transparent -geometry '14x11>+0+0' -gravity NorthWest   small.png
montage ${MEDIUM} -tile 1x -crop 22x16+1+4 -background transparent -geometry '22x16>+0+0' -gravity NorthWest   medium.png
montage ${LARGE}  -tile 1x -crop 30x22+1+5 -background transparent -geometry '30x22>+0+0' -gravity NorthWest   large.png

montage  small.png medium.png large.png -mode Concatenate -background transparent -tile 1x4 sprite.png
pngcrush sprite.png languages.png
```

The mapping between language and country flag is as follows (in order):

<table>
  <thead>
    <tr>
      <th colspan="2">Language</th>
      <th colspan="2">Country</th>
      <th colspan="3">Y-Offset</th>
    </tr>
    <tr>
      <th>ISO</th>
      <th>Name</th>
      <th>ISO</th>
      <th>Name</th>
      <th>S</th>
      <th>M</th>
      <th>L</th>
    </tr>
  </thead>
  <tbody>
    <tr><th>ar</th> <td>Arabic</td>     <td>SA</td> <td>Saudi Arabia</td> <td>0</td>   <td>484</td>  <td>1188</td></tr>
    <tr><th>be</th> <td>Belarusian</td> <td>BY</td> <td>Belarus</td>      <td>11</td>  <td>500</td>  <td>1210</td></tr>
    <tr><th>bg</th> <td>Bulgarian</td>  <td>BG</td> <td>Bulgaria</td>     <td>22</td>  <td>516</td>  <td>1232</td></tr>
    <tr><th>cs</th> <td>Czech</td>      <td>CZ</td> <td>Czech</td>        <td>33</td>  <td>532</td>  <td>1254</td></tr>
    <tr><th>da</th> <td>Danish</td>     <td>DK</td> <td>Denmark</td>      <td>44</td>  <td>548</td>  <td>1276</td></tr>
    <tr><th>de</th> <td>German</td>     <td>DE</td> <td>Germany</td>      <td>55</td>  <td>564</td>  <td>1298</td></tr>
    <tr><th>el</th> <td>Greek</td>      <td>GR</td> <td>Greece</td>       <td>66</td>  <td>580</td>  <td>1320</td></tr>
    <tr><th>en</th> <td>English</td>    <td>US</td> <td>United</td>       <td>77</td>  <td>596</td>  <td>1342</td></tr>
    <tr><th>es</th> <td>Spanish</td>    <td>ES</td> <td>Spain</td>        <td>88</td>  <td>612</td>  <td>1364</td></tr>
    <tr><th>et</th> <td>Estonian</td>   <td>EE</td> <td>Estonia</td>      <td>99</td>  <td>628</td>  <td>1386</td></tr>
    <tr><th>fi</th> <td>Finnish</td>    <td>FI</td> <td>Finland</td>      <td>110</td> <td>644</td>  <td>1408</td></tr>
    <tr><th>fr</th> <td>French</td>     <td>FR</td> <td>France</td>       <td>121</td> <td>660</td>  <td>1430</td></tr>
    <tr><th>ga</th> <td>Irish</td>      <td>IE</td> <td>Ireland</td>      <td>132</td> <td>676</td>  <td>1452</td></tr>
    <tr><th>hi</th> <td>Hindi</td>      <td>IN</td> <td>India</td>        <td>143</td> <td>692</td>  <td>1474</td></tr>
    <tr><th>hr</th> <td>Croatian</td>   <td>HR</td> <td>Croatia</td>      <td>154</td> <td>708</td>  <td>1496</td></tr>
    <tr><th>hu</th> <td>Hungarian</td>  <td>HU</td> <td>Hungary</td>      <td>165</td> <td>724</td>  <td>1518</td></tr>
    <tr><th>in</th> <td>Indonesian</td> <td>ID</td> <td>Indonesia</td>    <td>176</td> <td>740</td>  <td>1540</td></tr>
    <tr><th>is</th> <td>Icelandic</td>  <td>IS</td> <td>Iceland</td>      <td>187</td> <td>756</td>  <td>1562</td></tr>
    <tr><th>it</th> <td>Italian</td>    <td>IT</td> <td>Italy</td>        <td>198</td> <td>772</td>  <td>1584</td></tr>
    <tr><th>iw</th> <td>Hebrew</td>     <td>IL</td> <td>Israel</td>       <td>209</td> <td>788</td>  <td>1606</td></tr>
    <tr><th>ja</th> <td>Japanese</td>   <td>JP</td> <td>Japan</td>        <td>220</td> <td>804</td>  <td>1628</td></tr>
    <tr><th>ko</th> <td>Korean</td>     <td>KR</td> <td>South</td>        <td>231</td> <td>820</td>  <td>1650</td></tr>
    <tr><th>lt</th> <td>Lithuanian</td> <td>LT</td> <td>Lithuania</td>    <td>242</td> <td>836</td>  <td>1672</td></tr>
    <tr><th>lv</th> <td>Latvian</td>    <td>LV</td> <td>Latvia</td>       <td>253</td> <td>852</td>  <td>1694</td></tr>
    <tr><th>mk</th> <td>Macedonian</td> <td>MK</td> <td>Macedonia</td>    <td>264</td> <td>868</td>  <td>1716</td></tr>
    <tr><th>ms</th> <td>Malay</td>      <td>MY</td> <td>Malaysia</td>     <td>275</td> <td>884</td>  <td>1738</td></tr>
    <tr><th>mt</th> <td>Maltese</td>    <td>MT</td> <td>Malta</td>        <td>286</td> <td>900</td>  <td>1760</td></tr>
    <tr><th>nl</th> <td>Dutch</td>      <td>NL</td> <td>Netherlands</td>  <td>297</td> <td>916</td>  <td>1782</td></tr>
    <tr><th>no</th> <td>Norwegian</td>  <td>NO</td> <td>Norway</td>       <td>308</td> <td>932</td>  <td>1804</td></tr>
    <tr><th>pl</th> <td>Polish</td>     <td>PL</td> <td>Poland</td>       <td>319</td> <td>948</td>  <td>1826</td></tr>
    <tr><th>pt</th> <td>Portuguese</td> <td>PT</td> <td>Portugal</td>     <td>330</td> <td>964</td>  <td>1848</td></tr>
    <tr><th>ro</th> <td>Romanian</td>   <td>RO</td> <td>Romania</td>      <td>341</td> <td>980</td>  <td>1870</td></tr>
    <tr><th>ru</th> <td>Russian</td>    <td>RU</td> <td>Russia</td>       <td>352</td> <td>996</td>  <td>1892</td></tr>
    <tr><th>sk</th> <td>Slovak</td>     <td>SK</td> <td>Slovakia</td>     <td>363</td> <td>1012</td> <td>1914</td></tr>
    <tr><th>sl</th> <td>Slovenian</td>  <td>SI</td> <td>Slovenia</td>     <td>374</td> <td>1028</td> <td>1936</td></tr>
    <tr><th>sq</th> <td>Albanian</td>   <td>AL</td> <td>Albania</td>      <td>385</td> <td>1044</td> <td>1958</td></tr>
    <tr><th>sr</th> <td>Serbian</td>    <td>RS</td> <td>Serbia</td>       <td>396</td> <td>1060</td> <td>1980</td></tr>
    <tr><th>sv</th> <td>Swedish</td>    <td>SE</td> <td>Sweden</td>       <td>407</td> <td>1076</td> <td>2002</td></tr>
    <tr><th>th</th> <td>Thai</td>       <td>TH</td> <td>Thailand</td>     <td>418</td> <td>1092</td> <td>2024</td></tr>
    <tr><th>tr</th> <td>Turkish</td>    <td>TR</td> <td>Turkey</td>       <td>429</td> <td>1108</td> <td>2046</td></tr>
    <tr><th>uk</th> <td>Ukrainian</td>  <td>UA</td> <td>Ukraine</td>      <td>440</td> <td>1124</td> <td>2068</td></tr>
    <tr><th>vi</th> <td>Vietnamese</td> <td>VN</td> <td>Vietnam</td>      <td>451</td> <td>1140</td> <td>2090</td></tr>
    <tr><th>zh</th> <td>Chinese</td>    <td>CN</td> <td>China</td>        <td>462</td> <td>1156</td> <td>2112</td></tr>
  </tbody>
</table>
