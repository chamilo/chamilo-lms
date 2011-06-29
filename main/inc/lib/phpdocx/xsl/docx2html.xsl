<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:WX="http://schemas.microsoft.com/office/word/2003/auxHint"
    xmlns:aml="http://schemas.microsoft.com/aml/2001/core"
    xmlns:w10="urn:schemas-microsoft-com:office:word"
    xmlns:mml="http://www.w3.org/1998/Math/MathML"
    xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
    xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
    xmlns="http://www.w3.org/1999/xhtml"                
    version="1.0">

  <xsl:param name="pmathml" select="''"/>
  <xsl:param name="dtd" select="false()"/>
  
  
  
  
  
  <xsl:output method="html" encoding="utf-8" omit-xml-declaration="yes" indent="yes"/>

  <xsl:variable name="paraStyleID_Default">Normal</xsl:variable>
  <xsl:variable name="tblStyleID_Default">TableNormal</xsl:variable>

  <xsl:variable name="tblStyleSuffix">-T</xsl:variable>
  <xsl:variable name="rowStyleSuffix">-R</xsl:variable>
  <xsl:variable name="cellStyleSuffix">-C</xsl:variable>
  <xsl:variable name="paraStyleSuffix">-P</xsl:variable>
  <xsl:variable name="charStyleSuffix">-H</xsl:variable>

  <xsl:variable name="paraMarginDefaultTop">0pt</xsl:variable>
  <xsl:variable name="paraMarginDefaultRight">0pt</xsl:variable>
  <xsl:variable name="paraMarginDefaultBottom">.0001pt</xsl:variable>
  <xsl:variable name="paraMarginDefaultLeft">0pt</xsl:variable>

  <xsl:variable name="cxtSpacing_all"></xsl:variable>
  <xsl:variable name="cxtSpacing_top">t</xsl:variable>
  <xsl:variable name="cxtSpacing_bottom">b</xsl:variable>
  <xsl:variable name="cxtSpacing_none">
    <xsl:value-of select="$cxtSpacing_top"/>
    <xsl:value-of select="$cxtSpacing_bottom"/>
  </xsl:variable>

  <xsl:variable name="bdrSide_top">-top</xsl:variable>
  <xsl:variable name="bdrSide_right">-right</xsl:variable>
  <xsl:variable name="bdrSide_bottom">-bottom</xsl:variable>
  <xsl:variable name="bdrSide_left">-left</xsl:variable>
  <xsl:variable name="bdrSide_char"></xsl:variable>

  <xsl:variable name="prrFrame">1</xsl:variable>
  <xsl:variable name="prrDefaultCellpadding">2</xsl:variable>
  <xsl:variable name="prrCellspacing">3</xsl:variable>
  <xsl:variable name="prrBdrPr_top">4</xsl:variable>
  <xsl:variable name="prrBdrPr_right">5</xsl:variable>
  <xsl:variable name="prrBdrPr_bottom">6</xsl:variable>
  <xsl:variable name="prrBdrPr_left">7</xsl:variable>
  <xsl:variable name="prrBdrPr_between">8</xsl:variable>
  <xsl:variable name="prrBdrPr_bar">9</xsl:variable>
  <xsl:variable name="prrBdrPr_insideH">A</xsl:variable>
  <xsl:variable name="prrBdrPr_insideV">B</xsl:variable>
  <xsl:variable name="prrListSuff">C</xsl:variable>
  <xsl:variable name="prrListInd">D</xsl:variable>
  <xsl:variable name="prrApplyRPr">E</xsl:variable>
  <xsl:variable name="prrUpdateRPr">F</xsl:variable>
  <xsl:variable name="prrApplyTcPr">G</xsl:variable>
  <xsl:variable name="prrCustomCellpadding">H</xsl:variable>
  <xsl:variable name="prrCantSplit">I</xsl:variable>
  <xsl:variable name="prrTblInd">J</xsl:variable>
  <xsl:variable name="prrList">K</xsl:variable>
  <xsl:variable name="prrNonList">L</xsl:variable>

  <xsl:variable name="cnfFirstRow">firstRow</xsl:variable>
  <xsl:variable name="cnfLastRow">lastRow</xsl:variable>
  <xsl:variable name="cnfFirstCol">firstCol</xsl:variable>
  <xsl:variable name="cnfLastCol">lastCol</xsl:variable>
  <xsl:variable name="cnfBand1Vert">band1Vert</xsl:variable>
  <xsl:variable name="cnfBand2Vert">band2Vert</xsl:variable>
  <xsl:variable name="cnfBand1Horz">band1Horz</xsl:variable>
  <xsl:variable name="cnfBand2Horz">band2Horz</xsl:variable>
  <xsl:variable name="cnfNECell">neCell</xsl:variable>
  <xsl:variable name="cnfNWCell">nwCell</xsl:variable>
  <xsl:variable name="cnfSECell">seCell</xsl:variable>
  <xsl:variable name="cnfSWCell">swCell</xsl:variable>

  <xsl:variable name="icnfFirstRow">1</xsl:variable>
  <xsl:variable name="icnfLastRow">2</xsl:variable>
  <xsl:variable name="icnfFirstCol">3</xsl:variable>
  <xsl:variable name="icnfLastCol">4</xsl:variable>
  <xsl:variable name="icnfBand1Vert">5</xsl:variable>
  <xsl:variable name="icnfBand2Vert">6</xsl:variable>
  <xsl:variable name="icnfBand1Horz">7</xsl:variable>
  <xsl:variable name="icnfBand2Horz">8</xsl:variable>
  <xsl:variable name="icnfNECell">9</xsl:variable>
  <xsl:variable name="icnfNWCell">10</xsl:variable>
  <xsl:variable name="icnfSECell">11</xsl:variable>
  <xsl:variable name="icnfSWCell">12</xsl:variable>

  <xsl:variable name="off">0</xsl:variable>
  <xsl:variable name="on">1</xsl:variable>
  <xsl:variable name="na">2</xsl:variable>

  <xsl:variable name="defaultFontSz">20</xsl:variable>

  <xsl:variable name="sep">/</xsl:variable>
  <xsl:variable name="sep1">|</xsl:variable>
  <xsl:variable name="sep2">,</xsl:variable>

  <xsl:variable name="autoColor_hex">auto</xsl:variable>
  <xsl:variable name="autoColor_text">windowtext</xsl:variable>
  <xsl:variable name="autoColor_bg">transparent</xsl:variable>

  <xsl:variable name="transparentColor_hex">transparent</xsl:variable>
  <xsl:variable name="transparentColor_text">transparent</xsl:variable>
  <xsl:variable name="transparentColor_bg">transparent</xsl:variable>

  <xsl:variable name="prListSuff_space">Space</xsl:variable>
  <xsl:variable name="prListSuff_nothing">Nothing</xsl:variable>

  <xsl:variable name="nsStyles" select="/w:document[1]/w:styles[1]/w:style"/>
  <xsl:variable name="ndLists" select="/w:document[1]/w:numbering[1]|//w:cfChunk/w:numbering"/>
  <xsl:variable name="ndDocPr" select="/w:document[1]/w:settings[1]"/>
  <xsl:variable name="ndDocInfo" select="/w:document[1]/w:docInfo[1]"/>
  <xsl:variable name="ndOfficeDocPr" select="/w:document[1]/o:DocumentProperties[1]"/>

  <xsl:variable name="lowercase" select="'abcdefghijklmnopqrstuvwxyz'" />
  <xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />

  <xsl:variable name="pixelsPerInch">
    <xsl:choose>
      <xsl:when test="$ndDocPr/w:pixelsPerInch/@w:val">
        <xsl:value-of select="$ndDocPr/w:pixelsPerInch/@w:val"/>
      </xsl:when>
      <xsl:otherwise>96</xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <xsl:variable name="nfcBullet">bullet</xsl:variable>

  <xsl:variable name="iEmbossImprint">1</xsl:variable>
  <xsl:variable name="iU_Em">2</xsl:variable>
  <xsl:variable name="iStrikeDStrike">3</xsl:variable>
  <xsl:variable name="iSup">4</xsl:variable>
  <xsl:variable name="iSub">5</xsl:variable>
  <xsl:variable name="iVanishWebHidden">6</xsl:variable>
  <xsl:variable name="iBCs">7</xsl:variable>
  <xsl:variable name="iICs">8</xsl:variable>
  <xsl:variable name="iAsciiTheme">9</xsl:variable>
  <xsl:variable name="iAnsiTheme">10</xsl:variable>
  <xsl:variable name="iEATheme">11</xsl:variable>
  <xsl:variable name="iCSTheme">12</xsl:variable>
  <xsl:variable name="ISzCs">13</xsl:variable>

  <xsl:variable name="iTextAutospaceO">1</xsl:variable>
  <xsl:variable name="iTextAutospaceN">2</xsl:variable>
  <xsl:variable name="iInd">3</xsl:variable>

  <xsl:variable name="prsRDefault">
    <xsl:variable name="innerDefault">
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$na"/>
      <xsl:value-of select="$defaultFontSz"/>
    </xsl:variable>

    <xsl:for-each select="/w:document[1]/w:styles[1]/w:docDefaults/w:rPrDefault">
      <xsl:call-template name="PrsUpdateRPrCore">
        <xsl:with-param name="prsR" select="$innerDefault"/>
      </xsl:call-template>
    </xsl:for-each>
  </xsl:variable>

  <xsl:variable name="minorAscii">0</xsl:variable>
  <xsl:variable name="minorAnsi">1</xsl:variable>
  <xsl:variable name="minorEA">3</xsl:variable>
  <xsl:variable name="minorCS">4</xsl:variable>
  <xsl:variable name="majorAscii">5</xsl:variable>
  <xsl:variable name="majorAnsi">6</xsl:variable>
  <xsl:variable name="majorEA">7</xsl:variable>
  <xsl:variable name="majorCS">8</xsl:variable>

  <xsl:variable name="textClassAscii">asciiText</xsl:variable>
  <xsl:variable name="textClassAnsi">hAnsiText</xsl:variable>
  <xsl:variable name="textClassEA">eaText</xsl:variable>
  <xsl:variable name="textClassCS">csText</xsl:variable>

  <xsl:variable name="minorAsciiTheme">minorAsciiTheme</xsl:variable>
  <xsl:variable name="majorAsciiTheme">majorAsciiTheme</xsl:variable>
  <xsl:variable name="minorAnsiTheme">minorAnsiTheme</xsl:variable>
  <xsl:variable name="majorAnsiTheme">majorAnsiTheme</xsl:variable>
  <xsl:variable name="minorEATheme">minorEATheme</xsl:variable>
  <xsl:variable name="majorEATheme">majorEATheme</xsl:variable>
  <xsl:variable name="minorCSTheme">minorCSTheme</xsl:variable>
  <xsl:variable name="majorCSTheme">majorCSTheme</xsl:variable>

  <xsl:variable name="prsPDefault">
    <xsl:value-of select="$na"/>
    <xsl:value-of select="$na"/>
  </xsl:variable>

  <xsl:variable name="footnoteRefLink">_ftnref</xsl:variable>
  <xsl:variable name="footnoteLink">_ftn</xsl:variable>
  <xsl:variable name="endnoteRefLink">_ednref</xsl:variable>
  <xsl:variable name="endnoteLink">_edn</xsl:variable>

  <xsl:template name="ConvertHexToDec">
    <xsl:param name="value"/>
    <xsl:param name="i" select="1"/>
    <xsl:param name="s" select="1"/>
    <xsl:variable name="hexDigit" select="substring($value,$i,1)"/>
    <xsl:if test="not($hexDigit = '')">
      <xsl:text> </xsl:text>
      <xsl:choose>
        <xsl:when test="$hexDigit = 'A'">10</xsl:when>
        <xsl:when test="$hexDigit = 'B'">11</xsl:when>
        <xsl:when test="$hexDigit = 'C'">12</xsl:when>
        <xsl:when test="$hexDigit = 'D'">13</xsl:when>
        <xsl:when test="$hexDigit = 'E'">14</xsl:when>
        <xsl:when test="$hexDigit = 'F'">15</xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$hexDigit"/>
        </xsl:otherwise>
      </xsl:choose>
      <xsl:call-template name="ConvertHexToDec">
        <xsl:with-param name="value" select="$value"/>
        <xsl:with-param name="i" select="$i+$s"/>
        <xsl:with-param name="s" select="$s"/>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>

  <xsl:template name="ConvBorderStyle">
    <xsl:param name="value"/>
    <xsl:choose>
      <xsl:when test="$value='none' or $value='nil'">none</xsl:when>
      <xsl:when test="$value='single'">solid</xsl:when>
      <xsl:when test="contains($value,'stroke')">solid</xsl:when>
      <xsl:when test="$value='dashed'">dashed</xsl:when>
      <xsl:when test="contains($value,'dash')">dashed</xsl:when>
      <xsl:when test="$value='double'">double</xsl:when>
      <xsl:when test="$value='triple'">double</xsl:when>
      <xsl:when test="contains($value,'double')">double</xsl:when>
      <xsl:when test="contains($value,'gap')">double</xsl:when>
      <xsl:when test="$value='dotted'">dotted</xsl:when>
      <xsl:when test="$value='three-d-emboss'">ridge</xsl:when>
      <xsl:when test="$value='three-d-engrave'">groove</xsl:when>
      <xsl:when test="$value='outset'">outset</xsl:when>
      <xsl:when test="$value='inset'">inset</xsl:when>
      <xsl:otherwise>solid</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="EvalTableWidth">
    <xsl:choose>
      <xsl:when test="@w:type = 'pct'">
        <xsl:value-of select="@w:w div 50"/>%
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="@w:w div 20"/>pt
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="ConvColor">
    <xsl:param name="value"/>
    <xsl:choose>
      <xsl:when test="$value='black'">black</xsl:when>
      <xsl:when test="$value='blue'">blue</xsl:when>
      <xsl:when test="$value='cyan'">aqua</xsl:when>
      <xsl:when test="$value='green'">lime</xsl:when>
      <xsl:when test="$value='magenta'">fuchsia</xsl:when>
      <xsl:when test="$value='red'">red</xsl:when>
      <xsl:when test="$value='yellow'">yellow</xsl:when>
      <xsl:when test="$value='white'">white</xsl:when>
      <xsl:when test="$value='darkBlue'">navy</xsl:when>
      <xsl:when test="$value='darkCyan'">teal</xsl:when>
      <xsl:when test="$value='darkGreen'">green</xsl:when>
      <xsl:when test="$value='darkMagenta'">purple</xsl:when>
      <xsl:when test="$value='darkRed'">maroon</xsl:when>
      <xsl:when test="$value='darkYellow'">olive</xsl:when>
      <xsl:when test="$value='darkGray'">gray</xsl:when>
      <xsl:when test="$value='lightGray'">silver</xsl:when>
      <xsl:when test="$value='none'">transparent</xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="ConvHexColor">
    <xsl:param name="value"/>
    <xsl:param name="autoColor" select="$autoColor_text"/>
    <xsl:param name="transparentColor">transparent</xsl:param>
    <xsl:choose>
      <xsl:when test="$value = $autoColor_hex or $value = ''">
        <xsl:value-of select="$autoColor"/>
      </xsl:when>
      <xsl:when test="$value = $transparentColor_hex">
        <xsl:value-of select="$transparentColor"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="concat('#',$value)"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  
  <!--template of underline,strikethrough-->
  
  <xsl:template name="EvalBooleanType">
    <xsl:choose>
      <xsl:when test="string-length(@w:val) = 0 or @w:val = 'off' or @w:val = 'none' or @w:val = '0' ">
        <xsl:value-of select="$off"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$on"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  
  

  <xsl:template name="GetBorderPr">
    <xsl:value-of select="@w:val"/>
    <xsl:value-of select="$sep2"/>
    <xsl:value-of select="@w:color"/>
    <xsl:value-of select="$sep2"/>
    <xsl:choose>
      <xsl:when test="@w:sz">

        <xsl:value-of select="@w:sz * 2.5"/>
        <xsl:value-of select="$sep2"/>
      </xsl:when>
      <xsl:otherwise>
        0<xsl:value-of select="$sep2"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:value-of select="@w:space"/>
    <xsl:value-of select="$sep2"/>
    <xsl:value-of select="@w:shadow"/>
  </xsl:template>

  <xsl:template name="ApplyBorderPr">
    <xsl:param name="pr.bdr"/>
    <xsl:param name="bdrSide" select="$bdrSide_char"/>
    <xsl:if test="not($pr.bdr='')">
      <xsl:text>border</xsl:text>
      <xsl:value-of select="$bdrSide"/>
      <xsl:text>:</xsl:text>
      <xsl:call-template name="ConvBorderStyle">
        <xsl:with-param name="value" select="substring-before($pr.bdr,$sep2)"/>
      </xsl:call-template>
      <xsl:variable name="temp" select="substring-after($pr.bdr,$sep2)"/>
      <xsl:text> </xsl:text>
      <xsl:call-template name="ConvHexColor">
        <xsl:with-param name="value" select="substring-before($temp,$sep2)"/>
      </xsl:call-template>
      <xsl:text> </xsl:text>
      <xsl:value-of select="substring-before(substring-after($temp,$sep2),$sep2) div 20"/>
      <xsl:text>pt;</xsl:text>
      <xsl:if test="$bdrSide = $bdrSide_char">padding:0;</xsl:if>
    </xsl:if>
  </xsl:template>

  <xsl:variable name="valid_hex_digits" select="'0123456789ABCDEF'"/>

  <xsl:template name="TwoHexToDec">
    <xsl:param name="value"/>

    <xsl:choose>
      <xsl:when test="string-length($value) = 0">
        <xsl:value-of select="0"/>
      </xsl:when>
      <xsl:when test="string-length($value) = 1">
        <xsl:value-of select="string-length(substring-before($valid_hex_digits,substring($value,1,1)))"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:variable name="digit1_16" select="substring($value,1,1)"/>
        <xsl:variable name="digit2_16" select="substring($value,2,1)"/>
        <xsl:variable name="digit1_10" select="string-length(substring-before($valid_hex_digits,$digit1_16))"/>
        <xsl:variable name="digit2_10" select="string-length(substring-before($valid_hex_digits,$digit2_16))"/>

        <xsl:value-of select="$digit1_10 * 16 + $digit2_10"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="DecToTwoHex">
    <xsl:param name="value"/>

    <xsl:variable name="digit1_10" select="floor($value div 16)"/>
    <xsl:variable name="digit2_10" select="$value mod 16"/>

    <xsl:variable name="digit1_16" select="substring($valid_hex_digits,$digit1_10 + 1,1)"/>
    <xsl:variable name="digit2_16" select="substring($valid_hex_digits,$digit2_10 + 1,1)"/>

    <xsl:value-of select="concat($digit1_16,$digit2_16)"/>
  </xsl:template>

  <xsl:template name="ApplyShdPct">
    <xsl:param name="value"/>
    <xsl:param name="pct"/>
    <xsl:param name="transparentColor">transparent</xsl:param>

    <xsl:choose>
      <xsl:when test="$value = $autoColor_hex or $value = ''">
        <xsl:call-template name="ApplyShdPct">
          <xsl:with-param name="value" select="'FFFFFF'"/>
          <xsl:with-param name="pct" select="$pct"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:when test="$value = $transparentColor_hex">
        <xsl:value-of select="$transparentColor"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:variable name="r">
          <xsl:call-template name="TwoHexToDec">
            <xsl:with-param name="value" select="substring($value,1,2)"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="g">
          <xsl:call-template name="TwoHexToDec">
            <xsl:with-param name="value" select="substring($value,3,2)"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="b">
          <xsl:call-template name="TwoHexToDec">
            <xsl:with-param name="value" select="substring($value,5,2)"/>
          </xsl:call-template>
        </xsl:variable>

        <xsl:value-of select="'#'"/>
        <xsl:call-template name="DecToTwoHex">
          <xsl:with-param name="value" select="round(number($r) * $pct)"/>
        </xsl:call-template>
        <xsl:call-template name="DecToTwoHex">
          <xsl:with-param name="value" select="round(number($g) * $pct)"/>
        </xsl:call-template>
        <xsl:call-template name="DecToTwoHex">
          <xsl:with-param name="value" select="round(number($b) * $pct)"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>

  </xsl:template>

  <xsl:template name="ApplyShd">

    <xsl:variable name="backgroundColor">
      <xsl:choose>
        <xsl:when test="@w:val = 'clear' or not(@w:val)">
          <xsl:call-template name="ConvHexColor">
            <xsl:with-param name="value" select="@w:fill"/>
            <xsl:with-param name="autoColor" select="$autoColor_bg"/>
          </xsl:call-template>
        </xsl:when>
        <xsl:when test="@w:val = 'solid'">#000000</xsl:when>
        <xsl:when test="substring(@w:val,1,3) = 'pct'">
          <xsl:call-template name="ApplyShdPct">
            <xsl:with-param name="value" select="@w:fill"/>
            <xsl:with-param name="autoColor" select="$autoColor_bg"/>
            <xsl:with-param name="pct" select="(100 - number(substring(@w:val,4))) div 100"/>
          </xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="ConvHexColor">
            <xsl:with-param name="value" select="@WX:bgcolor"/>
            <xsl:with-param name="autoColor" select="$autoColor_bg"/>
          </xsl:call-template>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:text>background-color:</xsl:text>
    
    <xsl:value-of select="$backgroundColor"/>
    <!--<xsl:variable select="backgroundColor"/>-->
    <xsl:text>;</xsl:text>

    <xsl:call-template name="ApplyAutoForeColor">
      <xsl:with-param name="backgroundColor" select="$backgroundColor"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="ApplyAutoForeColor">
    <xsl:param name="backgroundColor"/>

    <xsl:if test="contains($backgroundColor,'#')">
      <xsl:variable name="backgroundHex" select="substring-after($backgroundColor,'#')"/>
      <xsl:variable name="r">
        <xsl:call-template name="TwoHexToDec">
          <xsl:with-param name="value" select="substring($backgroundHex,1,2)"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="g">
        <xsl:call-template name="TwoHexToDec">
          <xsl:with-param name="value" select="substring($backgroundHex,3,2)"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="b">
        <xsl:call-template name="TwoHexToDec">
          <xsl:with-param name="value" select="substring($backgroundHex,5,2)"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:if test="0.299 * number($r) + 0.587 * number($g) + 0.114 * number($b) &lt;= 60">
        <xsl:text>color:#FFFFFF;</xsl:text>
      </xsl:if>
    </xsl:if>
  </xsl:template>

  <xsl:template name="ApplyShdHint">
    <xsl:text>background-color:</xsl:text>
    <xsl:call-template name="ConvHexColor">
      <xsl:with-param name="value" select="@WX:bgcolor"/>
      <xsl:with-param name="autoColor" select="$autoColor_bg"/>
      <xsl:with-param name="transparentColor">transparent</xsl:with-param>
    </xsl:call-template>
    <xsl:text>;</xsl:text>
  </xsl:template>

  <xsl:template name="ApplyTextDirection">
    <xsl:text>layout-flow:</xsl:text>
    <xsl:choose>
      <xsl:when test="@w:val = 'tb-rl-v'">vertical-ideographic</xsl:when>
      <xsl:when test="@w:val = 'lr-tb-v'">horizontal-ideographic</xsl:when>
      <xsl:otherwise>normal</xsl:otherwise>
    </xsl:choose>
    <xsl:text>;</xsl:text>
  </xsl:template>

  <xsl:template name="ApplyCellMar">
    <xsl:choose>
      <xsl:when test="@w:val='none'">none</xsl:when>
      <xsl:otherwise>
        <xsl:text>padding:</xsl:text>
        <xsl:choose>
          <xsl:when test="w:top">
            <xsl:for-each select="w:top[1]">
              <xsl:call-template name="EvalTableWidth"/>
            </xsl:for-each>
          </xsl:when>
          <xsl:otherwise>0</xsl:otherwise>
        </xsl:choose>
        <xsl:text> </xsl:text>
        <xsl:choose>
          <xsl:when test="w:right">
            <xsl:for-each select="w:right[1]">
              <xsl:call-template name="EvalTableWidth"/>
            </xsl:for-each>
          </xsl:when>
          <xsl:otherwise>0</xsl:otherwise>
        </xsl:choose>
        <xsl:text> </xsl:text>
        <xsl:choose>
          <xsl:when test="w:bottom">
            <xsl:for-each select="w:bottom[1]">
              <xsl:call-template name="EvalTableWidth"/>
            </xsl:for-each>
          </xsl:when>
          <xsl:otherwise>0</xsl:otherwise>
        </xsl:choose>
        <xsl:text> </xsl:text>
        <xsl:choose>
          <xsl:when test="w:left">
            <xsl:for-each select="w:left[1]">
              <xsl:call-template name="EvalTableWidth"/>
            </xsl:for-each>
          </xsl:when>
          <xsl:otherwise>0</xsl:otherwise>
        </xsl:choose>
        <xsl:text>;</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="PrsUpdatePPr">
    <xsl:param name="prsP" select="$prsPDefault"/>
    <xsl:param name="ndPrContainer" select="."/>

    <xsl:variable name="prsPTemp">
      <xsl:for-each select="$ndPrContainer">
        <xsl:call-template name="PrsUpdatePPrCore">
          <xsl:with-param name="prsP" select="$prsP"/>
        </xsl:call-template>
      </xsl:for-each>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$prsPTemp=''">
        <xsl:value-of select="$prsP"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$prsPTemp"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="FetchBasedOnPropertyBoolean">
    <xsl:param name="match" select="''"/>

    <xsl:choose>
      <xsl:when test="$match">
        <xsl:for-each select="$match">
          <xsl:call-template name="EvalBooleanType"/>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="../w:basedOn">
        <xsl:variable name="sBasedOn">
          <xsl:value-of select="../w:basedOn/@w:val"/>
        </xsl:variable>
        <xsl:for-each select="$nsStyles[@w:styleId=$sBasedOn]">
          <xsl:call-template name="FetchBasedOnPropertyBoolean">
            <xsl:with-param name="match" select="$match"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$na"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:variable name="fbopModeIndentLeft" select="'1'"/>
  <xsl:variable name="fbopModeIndentLeftChars" select="'2'"/>
  <xsl:variable name="fbopModeIndentRight" select="'3'"/>
  <xsl:variable name="fbopModeIndentRightChars" select="'4'"/>
  <xsl:variable name="fbopModeIndentHanging" select="'5'"/>
  <xsl:variable name="fbopModeIndentHangingChars" select="'6'"/>
  <xsl:variable name="fbopModeIndentFirstLine" select="'7'"/>
  <xsl:variable name="fbopModeIndentFirstLineChars" select="'8'"/>

  <xsl:template name="FetchBasedOnProperty">
    <xsl:param name="mode" select="''"/>
    <xsl:param name="sDefault" select="''"/>

    <xsl:variable name="sValue">
      <xsl:choose>
        <xsl:when test="$mode=$fbopModeIndentLeft">
          <xsl:value-of select="w:ind[1]/@w:left"/>
        </xsl:when>
        <xsl:when test="$mode=$fbopModeIndentLeftChars">
          <xsl:value-of select="w:ind[1]/@w:leftChars"/>
        </xsl:when>
        <xsl:when test="$mode=$fbopModeIndentRight">
          <xsl:value-of select="w:ind[1]/@w:right"/>
        </xsl:when>
        <xsl:when test="$mode=$fbopModeIndentRightChars">
          <xsl:value-of select="w:ind[1]/@w:rightChars"/>
        </xsl:when>
        <xsl:when test="$mode=$fbopModeIndentHanging">
          <xsl:value-of select="w:ind[1]/@w:hanging"/>
        </xsl:when>
        <xsl:when test="$mode=$fbopModeIndentHangingChars">
          <xsl:value-of select="w:ind[1]/@w:hangingChars"/>
        </xsl:when>
        <xsl:when test="$mode=$fbopModeIndentFirstLine">
          <xsl:value-of select="w:ind[1]/@w:firstLine"/>
        </xsl:when>
        <xsl:when test="$mode=$fbopModeIndentFirstLineChars">
          <xsl:value-of select="w:ind[1]/@w:firstLineChars"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text></xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:choose>
      <xsl:when test="not($sValue='')">
        <xsl:value-of select="$sValue"/>
      </xsl:when>
      <xsl:when test="../w:basedOn">
        <xsl:variable name="sBasedOn">
          <xsl:value-of select="../w:basedOn/@w:val"/>
        </xsl:variable>
        <xsl:for-each select="$nsStyles[@w:styleId=$sBasedOn]/w:pPr[1]">
          <xsl:call-template name="FetchBasedOnProperty">
            <xsl:with-param name="mode" select="$mode"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$sDefault"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="PrsUpdatePPrCore">
    <xsl:param name="prsP" select="$prsPDefault"/>
    <xsl:for-each select="w:pPr[1]">

      <xsl:variable name="fTextAutospaceO">

        <xsl:for-each select="w:autoSpaceDE[1]">
          <xsl:call-template name="EvalBooleanType"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$fTextAutospaceO=''">
          <xsl:value-of select="substring($prsP, $iTextAutospaceO, 1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fTextAutospaceO"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="fTextAutospaceN">
        <xsl:for-each select="w:autoSpaceDN[1]">
          <xsl:call-template name="EvalBooleanType"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$fTextAutospaceN=''">
          <xsl:value-of select="substring($prsP, $iTextAutospaceN, 1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fTextAutospaceN"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="prsDefaultInd" select="substring($prsP, $iInd)"/>
      <xsl:variable name="sDefLeft" select="substring-before($prsDefaultInd,$sep2)"/>
      <xsl:variable name="temp1" select="substring-after($prsDefaultInd,$sep2)"/>
      <xsl:variable name="sDefLeftChars" select="substring-before($temp1,$sep2)"/>
      <xsl:variable name="temp2" select="substring-after($temp1,$sep2)"/>
      <xsl:variable name="sDefRight" select="substring-before($temp2,$sep2)"/>
      <xsl:variable name="temp3" select="substring-after($temp2,$sep2)"/>
      <xsl:variable name="sDefRightChars" select="substring-before($temp3,$sep2)"/>
      <xsl:variable name="temp4" select="substring-after($temp3,$sep2)"/>
      <xsl:variable name="sDefHanging" select="substring-before($temp4,$sep2)"/>
      <xsl:variable name="temp5" select="substring-after($temp4,$sep2)"/>
      <xsl:variable name="sDefHangingChars" select="substring-before($temp5,$sep2)"/>
      <xsl:variable name="temp6" select="substring-after($temp5,$sep2)"/>
      <xsl:variable name="sDefFirstLine" select="substring-before($temp6,$sep2)"/>
      <xsl:variable name="sDefFirstLineChars" select="substring-after($temp6,$sep2)"/>

      <xsl:variable name="nInd">

        <xsl:call-template name="FetchBasedOnProperty">
          <xsl:with-param name="mode" select="$fbopModeIndentLeft"/>
          <xsl:with-param name="sDefault" select="$sDefLeft"/>
        </xsl:call-template>

        <xsl:value-of select="$sep2"/>

        <xsl:call-template name="FetchBasedOnProperty">
          <xsl:with-param name="mode" select="$fbopModeIndentLeftChars"/>
          <xsl:with-param name="sDefault" select="$sDefLeftChars"/>
        </xsl:call-template>

        <xsl:value-of select="$sep2"/>

        <xsl:call-template name="FetchBasedOnProperty">
          <xsl:with-param name="mode" select="$fbopModeIndentRight"/>
          <xsl:with-param name="sDefault" select="$sDefRight"/>
        </xsl:call-template>

        <xsl:value-of select="$sep2"/>

        <xsl:call-template name="FetchBasedOnProperty">
          <xsl:with-param name="mode" select="$fbopModeIndentRightChars"/>
          <xsl:with-param name="sDefault" select="$sDefRightChars"/>
        </xsl:call-template>

        <xsl:value-of select="$sep2"/>

        <xsl:call-template name="FetchBasedOnProperty">
          <xsl:with-param name="mode" select="$fbopModeIndentHanging"/>
          <xsl:with-param name="sDefault" select="$sDefHanging"/>
        </xsl:call-template>

        <xsl:value-of select="$sep2"/>

        <xsl:call-template name="FetchBasedOnProperty">
          <xsl:with-param name="mode" select="$fbopModeIndentHangingChars"/>
          <xsl:with-param name="sDefault" select="$sDefHangingChars"/>
        </xsl:call-template>

        <xsl:value-of select="$sep2"/>

        <xsl:call-template name="FetchBasedOnProperty">
          <xsl:with-param name="mode" select="$fbopModeIndentFirstLine"/>
          <xsl:with-param name="sDefault" select="$sDefFirstLine"/>
        </xsl:call-template>

        <xsl:value-of select="$sep2"/>

        <xsl:call-template name="FetchBasedOnProperty">
          <xsl:with-param name="mode" select="$fbopModeIndentFirstLineChars"/>
          <xsl:with-param name="sDefault" select="$sDefFirstLineChars"/>
        </xsl:call-template>

      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$nInd=''">
          <xsl:value-of select="substring($prsP, $iInd)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$nInd"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="PrsUpdateRPr">
    <xsl:param name="prsR" select="$prsRDefault"/>
    <xsl:param name="ndPrContainer" select="."/>
    <xsl:variable name="prsRTemp">
      <xsl:for-each select="$ndPrContainer">
        <xsl:call-template name="PrsUpdateRPrCore">
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:for-each>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$prsRTemp=''">
        <xsl:value-of select="$prsR"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$prsRTemp"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="PrsUpdateRPrCore">
    <xsl:param name="prsR"/>
    <xsl:param name="type" select="$prrNonList"/>

    <xsl:for-each select="w:rPr[1]">

      <xsl:variable name="fEmbossImprint">
        <xsl:variable name="condition1">
          <xsl:for-each select="w:emboss[1]">
            <xsl:call-template name="EvalBooleanType"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:variable name="condition2">
          <xsl:for-each select="w:imprint[1]">
            <xsl:call-template name="EvalBooleanType"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:choose>
          <xsl:when test="$condition1 = $on or $condition2 = $on">
            <xsl:value-of select="$on"/>
          </xsl:when>
          <xsl:when test="$condition1 = $off or $condition2 = $off">
            <xsl:value-of select="$off"/>
          </xsl:when>
        </xsl:choose>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$fEmbossImprint = ''">
          <xsl:value-of select="substring($prsR,$iEmbossImprint,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fEmbossImprint"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="fU_Em">
        <xsl:variable name="condition1">
          
          <!--here is the underline tag-->
          
          <xsl:for-each select="w:u[1]">
            <xsl:call-template name="EvalBooleanType"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:variable name="condition2">
          <xsl:for-each select="w:em[1]">
            <xsl:call-template name="EvalBooleanType"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:choose>
          <xsl:when test="$condition1 = $on or $condition2 = $on">
            <xsl:value-of select="$on"/>
          </xsl:when>
          <xsl:when test="$condition1 = $off or $condition2 = $off">
            <xsl:value-of select="$off"/>
          </xsl:when>
        </xsl:choose>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$fU_Em = ''">
          <xsl:choose>
            <xsl:when test="$type=$prrList">
              <xsl:value-of select="$off"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="substring($prsR,$iU_Em,1)"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fU_Em"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="fStrikeDStrike">
        <xsl:variable name="condition1">
          <xsl:for-each select="w:strike[1]">
            <xsl:value-of select="$on"/>                        
          </xsl:for-each>
        </xsl:variable>
        <xsl:variable name="condition2">
          <xsl:for-each select="w:dstrike[1]">
            <xsl:call-template name="EvalBooleanType"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:choose>
          <xsl:when test="$condition1 = $on or $condition2 = $on">
            <xsl:value-of select="$on"/>
          </xsl:when>
          <xsl:when test="$condition1 = $off or $condition2 = $off">
            <xsl:value-of select="$off"/>
          </xsl:when>
        </xsl:choose>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$fStrikeDStrike = ''">
          <xsl:value-of select="substring($prsR,$iStrikeDStrike,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fStrikeDStrike"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="fSup">
        <xsl:choose>
          <xsl:when test="w:vertAlign/@w:val='superscript'">
            <xsl:value-of select="$on"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$off"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="not(w:vertAlign)">
          <xsl:value-of select="substring($prsR,$iSup,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fSup"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="fSub">
        <xsl:choose>
          <xsl:when test="w:vertAlign/@w:val='subscript'">
            <xsl:value-of select="$on"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$off"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="not(w:vertAlign)">
          <xsl:value-of select="substring($prsR,$iSub,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fSub"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="fVanishWebHidden">
        <xsl:variable name="condition1">
          <xsl:for-each select="w:vanish[1]">
            <xsl:call-template name="EvalBooleanType"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:variable name="condition2">
          <xsl:for-each select="w:webHidden[1]">
            <xsl:call-template name="EvalBooleanType"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:choose>
          <xsl:when test="$condition1 = $on or $condition2 = $on">
            <xsl:value-of select="$on"/>
          </xsl:when>
          <xsl:when test="$condition1 = $off or $condition2 = $off">
            <xsl:value-of select="$off"/>
          </xsl:when>
        </xsl:choose>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$fVanishWebHidden = ''">
          <xsl:value-of select="substring($prsR,$iVanishWebHidden,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fVanishWebHidden"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="fBCs">
        <xsl:for-each select="w:bCs[1]">
          <xsl:call-template name="EvalBooleanType"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$fBCs = ''">
          <xsl:value-of select="substring($prsR,$iBCs,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fBCs"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="fICs">
        <xsl:for-each select="w:iCs[1]">
          <xsl:call-template name="EvalBooleanType"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$fICs = ''">
          <xsl:value-of select="substring($prsR,$iICs,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fICs"/>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="szAsciiTheme" select="string(w:rFonts[1]/@w:asciiTheme)"/>
      <xsl:choose>
        <xsl:when test="$szAsciiTheme = ''">
          <xsl:value-of select="substring($prsR,$iAsciiTheme,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="EvalThemeType">
            <xsl:with-param name="themeStyle" select="$szAsciiTheme"/>
          </xsl:call-template>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="szAnsiTheme" select="string(w:rFonts[1]/@w:hAnsiTheme)"/>
      <xsl:choose>
        <xsl:when test="$szAnsiTheme = ''">
          <xsl:value-of select="substring($prsR,$iAnsiTheme,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="EvalThemeType">
            <xsl:with-param name="themeStyle" select="$szAnsiTheme"/>
          </xsl:call-template>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="szEATheme" select="string(w:rFonts[1]/@w:eastAsiaTheme)"/>
      <xsl:choose>
        <xsl:when test="$szEATheme = ''">
          <xsl:value-of select="substring($prsR,$iEATheme,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="EvalThemeType">
            <xsl:with-param name="themeStyle" select="$szEATheme"/>
          </xsl:call-template>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="szCSTheme" select="string(w:rFonts[1]/@w:cstheme)"/>
      <xsl:choose>
        <xsl:when test="$szCSTheme = ''">
          <xsl:value-of select="substring($prsR,$iCSTheme,1)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="EvalThemeType">
            <xsl:with-param name="themeStyle" select="$szCSTheme"/>
          </xsl:call-template>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:variable name="nSzCs" select="string(w:szCs[1]/@w:val)"/>
      <xsl:choose>
        <xsl:when test="$nSzCs = ''">
          <xsl:value-of select="substring($prsR,$ISzCs)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$nSzCs"/>
        </xsl:otherwise>
      </xsl:choose>

    </xsl:for-each>
  </xsl:template>

  <xsl:template name="EvalThemeType">
    <xsl:param name="themeStyle"/>

    <xsl:choose>
      <xsl:when test="$themeStyle = 'minorAscii'">
        <xsl:value-of select="$minorAscii"/>
      </xsl:when>
      <xsl:when test="$themeStyle = 'majorAscii'">
        <xsl:value-of select="$majorAscii"/>
      </xsl:when>
      <xsl:when test="$themeStyle = 'minorHAnsi'">
        <xsl:value-of select="$minorAnsi"/>
      </xsl:when>
      <xsl:when test="$themeStyle = 'majorHAnsi'">
        <xsl:value-of select="$majorAnsi"/>
      </xsl:when>
      <xsl:when test="$themeStyle = 'minorEastAsia'">
        <xsl:value-of select="$minorEA"/>
      </xsl:when>
      <xsl:when test="$themeStyle = 'majorEastAsia'">
        <xsl:value-of select="$majorEA"/>
      </xsl:when>
      <xsl:when test="$themeStyle = 'minorBidi'">
        <xsl:value-of select="$minorCS"/>
      </xsl:when>
      <xsl:when test="$themeStyle = 'majorBidi'">
        <xsl:value-of select="$majorCS"/>
      </xsl:when>
    </xsl:choose>

  </xsl:template>

  <xsl:template name="PrsGetListPr">
    <xsl:param name="type"/>
    <xsl:param name="prsR"/>
    <xsl:for-each select="w:numPr">
      <xsl:choose>

        <xsl:when test="w:numId and w:ilvl">
          <xsl:call-template name="PrsGetListPrCore">
            <xsl:with-param name="type" select="$type"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:when>

        <xsl:otherwise>
          <xsl:variable name="pstyleId">
            <xsl:for-each select="ancestor::w:p[1]">
              <xsl:call-template name="GetPStyleId"/>
            </xsl:for-each>
          </xsl:variable>
          <xsl:for-each select="($nsStyles[@w:styleId=$pstyleId])[1]/w:pPr[1]/w:numPr[1]">
            <xsl:call-template name="PrsGetListPrCore">
              <xsl:with-param name="type" select="$type"/>
              <xsl:with-param name="prsR" select="$prsR"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="PrsGetListPrCore">
    <xsl:param name="type"/>
    <xsl:param name="prsR"/>

    <xsl:variable name="numId" select="w:numId/@w:val"/>
    <xsl:variable name="ilvl" select="w:ilvl/@w:val"/>
    <xsl:for-each select="$ndLists">

      <xsl:variable name="list" select="w:num[@w:numId=$numId][1]"/>

      <xsl:choose>
        <xsl:when test="w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:lvl[@w:ilvl=$ilvl][1]">
          <xsl:for-each select="w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:lvl[@w:ilvl=$ilvl][1]">
            <xsl:call-template name="PrsGetListPrFromListDef">
              <xsl:with-param name="type" select="$type"/>
              <xsl:with-param name="prsR" select="$prsR"/>
            </xsl:call-template>
          </xsl:for-each>

          <xsl:if test="$list/w:lvlOverride[@w:ilvl=$ilvl]">
            <xsl:for-each select="$list/w:lvlOverride[@w:ilvl=$ilvl]">
              <xsl:call-template name="PrsGetListPrFromListDef">
                <xsl:with-param name="type" select="$type"/>
                <xsl:with-param name="prsR" select="$prsR"/>
              </xsl:call-template>
            </xsl:for-each>
          </xsl:if>
        </xsl:when>

        <xsl:when test="$list/w:lvlOverride[@w:ilvl=$ilvl]">
          <xsl:for-each select="$list/w:lvlOverride[@w:ilvl=$ilvl]">
            <xsl:call-template name="PrsGetListPrFromListDef">
              <xsl:with-param name="type" select="$type"/>
              <xsl:with-param name="prsR" select="$prsR"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>

        <xsl:when test="w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:listStyleLink">
          <xsl:variable name="linkedStyleId" select="w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:listStyleLink/@w:val" />
          <xsl:variable name="linkedStyle" select="$nsStyles[@w:styleId=$linkedStyleId]" />
          <xsl:variable name="linkedList" select="w:num[@w:numId=$linkedStyle/w:pPr/w:numPr/w:numId/@w:val]" />
          <xsl:for-each select="w:abstractNum[@w:abstractNumId=$linkedList/w:abstractNumId/@w:val][1]/w:lvl[@w:ilvl=$ilvl][1]">
            <xsl:call-template name="PrsGetListPrFromListDef">
              <xsl:with-param name="type" select="$type"/>
              <xsl:with-param name="prsR" select="$prsR"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
      </xsl:choose>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="PrsGetListPrFromListDef">
    <xsl:param name="type"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="$type = $prrListSuff">
        <xsl:variable name="suff" select="w:suff[1]/@w:val"/>
        <xsl:choose>
          <xsl:when test="$suff = $prListSuff_space or $suff = $prListSuff_nothing">
            <xsl:value-of select="$suff"/>
          </xsl:when>

          <xsl:otherwise>
            <xsl:value-of select="$prListSuff_space"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>

      <xsl:when test="$type = $prrListInd">
        <xsl:for-each select="w:pPr[1]/w:ind[1]">
          <xsl:value-of select="@w:left"/>
          <xsl:value-of select="$sep2"/>
          <xsl:value-of select="@w:left-chars"/>
          <xsl:value-of select="$sep2"/>
          <xsl:value-of select="@w:hanging"/>
          <xsl:value-of select="$sep2"/>
          <xsl:value-of select="@w:hanging-chars"/>
        </xsl:for-each>
      </xsl:when>

      <xsl:when test="$type = $prrApplyRPr">
        <xsl:call-template name="ApplyRPr.class"/>
      </xsl:when>

      <xsl:when test="$type = $prrUpdateRPr">
        <xsl:call-template name="PrsUpdateRPrCore">
          <xsl:with-param name="type" select="$prrList"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="GetSinglePPr">
    <xsl:param name="type"/>
    <xsl:param name="sParaStyleName"/>

    <xsl:variable name="result">
      <xsl:call-template name="GetSinglePPrCore">
        <xsl:with-param name="type" select="$type"/>
      </xsl:call-template>
    </xsl:variable>

    <xsl:if test="$result=''">
      <xsl:for-each select="$sParaStyleName">
        <xsl:call-template name="GetSinglePPrCore">
          <xsl:with-param name="type" select="$type"/>
        </xsl:call-template>
      </xsl:for-each>
    </xsl:if>
    <xsl:value-of select="$result"/>
  </xsl:template>

  <xsl:template name="GetSinglePPrCore">
    <xsl:param name="type"/>
    <xsl:for-each select="w:pPr[1]">
      <xsl:choose>
        <xsl:when test="$type = $prrBdrPr_top">
          <xsl:for-each select="w:bdr[1]/w:top[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_right">
          <xsl:for-each select="w:bdr[1]/w:right[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_bottom">
          <xsl:for-each select="w:bdr[1]/w:bottom[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_left">
          <xsl:for-each select="w:bdr[1]/w:left[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_between">
          <xsl:for-each select="w:bdr[1]/w:between[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_bar">
          <xsl:for-each select="w:bdr[1]/w:bar[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrFrame">
          <xsl:for-each select="w:framePr[1]">
            <xsl:value-of select="@w:w"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:h"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:h-rule"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:x-align"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:vSpace"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:hSpace"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:wrap"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:drop-cap"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:lines"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:x"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:y-align"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:y"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:hAnchor"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:vAnchor"/>
            <xsl:value-of select="$sep2"/>
            <xsl:value-of select="@w:anchor-lock"/>
          </xsl:for-each>
        </xsl:when>
      </xsl:choose>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="GetSingleTblPr">
    <xsl:param name="type"/>
    <xsl:param name="sTblStyleName"/>
    <xsl:variable name="result">
      <xsl:call-template name="GetSingleTblPrCore">
        <xsl:with-param name="type" select="$type"/>
      </xsl:call-template>
    </xsl:variable>

    <xsl:if test="$result='' and $sTblStyleName">
      <xsl:for-each select="$sTblStyleName">
        <xsl:call-template name="GetSingleTblPrCore">
          <xsl:with-param name="type" select="$type"/>
        </xsl:call-template>
      </xsl:for-each>
    </xsl:if>
    <xsl:value-of select="$result"/>
  </xsl:template>

  <xsl:template name="GetSingleTblPrCore">
    <xsl:param name="type"/>
    <xsl:for-each select="w:tblPr[1]">
      <xsl:choose>
        <xsl:when test="$type = $prrBdrPr_top">
          <xsl:for-each select="w:tblBorders[1]/w:top[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_left">
          <xsl:for-each select="w:tblBorders[1]/w:left[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_bottom">
          <xsl:for-each select="w:tblBorders[1]/w:bottom[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_right">
          <xsl:for-each select="w:tblBorders[1]/w:right[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_insideH">
          <xsl:for-each select="w:tblBorders[1]/w:insideH[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrBdrPr_insideV">
          <xsl:for-each select="w:tblBorders[1]/w:insideV[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrDefaultCellpadding">
          <xsl:for-each select="w:tblCellMar[1]">
            <xsl:call-template name="ApplyCellMar"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="$type = $prrCellspacing">
          <xsl:value-of select="w:tblCellSpacing[1]/@w:w"/>
        </xsl:when>
        <xsl:when test="$type = $prrTblInd">
          <xsl:for-each select="w:tblInd[1]">
            <xsl:call-template name="EvalTableWidth"/>
          </xsl:for-each>
        </xsl:when>
      </xsl:choose>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="WrapCnf">
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="cnfCol"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>

    <xsl:choose>

      <xsl:when test="substring($cnfRow,$icnfBand1Horz,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfBand1Horz][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfBand1Horz)}">
          <xsl:call-template name="WrapCnf.a">
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:when test="substring($cnfRow,$icnfBand2Horz,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfBand2Horz][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfBand2Horz)}">
          <xsl:call-template name="WrapCnf.a">
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:otherwise>

        <xsl:call-template name="WrapCnf.a">
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
          <xsl:with-param name="cnfCol" select="$cnfCol"/>
          <xsl:with-param name="cnfRow" select="$cnfRow"/>
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template name="WrapCnf.a">
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="cnfCol"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="substring($cnfCol,$icnfBand1Vert,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfBand1Vert][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfBand1Vert)}">
          <xsl:call-template name="WrapCnf.b">
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:when test="substring($cnfCol,$icnfBand2Vert,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfBand2Vert][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfBand2Vert)}">
          <xsl:call-template name="WrapCnf.b">
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:otherwise>

        <xsl:call-template name="WrapCnf.b">
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
          <xsl:with-param name="cnfCol" select="$cnfCol"/>
          <xsl:with-param name="cnfRow" select="$cnfRow"/>
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template name="WrapCnf.b">
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="cnfCol"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="substring($cnfCol,$icnfFirstCol,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfFirstCol][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfFirstCol)}">
          <xsl:call-template name="WrapCnf.c">
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:when test="substring($cnfCol,$icnfLastCol,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfLastCol][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfLastCol)}">
          <xsl:call-template name="WrapCnf.c">
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:otherwise>

        <xsl:call-template name="WrapCnf.c">
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
          <xsl:with-param name="cnfCol" select="$cnfCol"/>
          <xsl:with-param name="cnfRow" select="$cnfRow"/>
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template name="WrapCnf.c">
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="cnfCol"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="substring($cnfRow,$icnfFirstRow,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfFirstRow][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfFirstRow)}">
          <xsl:call-template name="WrapCnf.d">
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:when test="substring($cnfRow,$icnfLastRow,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfLastRow][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfLastRow)}">
          <xsl:call-template name="WrapCnf.d">
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:otherwise>

        <xsl:call-template name="WrapCnf.d">
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
          <xsl:with-param name="cnfCol" select="$cnfCol"/>
          <xsl:with-param name="cnfRow" select="$cnfRow"/>
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template name="WrapCnf.d">
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="cnfCol"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="substring($cnfCol,$icnfNECell,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfNECell][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfNECell)}">
          <xsl:call-template name="DisplayBodyContent">
            <xsl:with-param name="ns.content" select="*"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:when test="substring($cnfCol,$icnfNWCell,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfNWCell][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfNWCell)}">
          <xsl:call-template name="DisplayBodyContent">
            <xsl:with-param name="ns.content" select="*"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:when test="substring($cnfCol,$icnfSECell,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfSECell][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfSECell)}">
          <xsl:call-template name="DisplayBodyContent">
            <xsl:with-param name="ns.content" select="*"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:when test="substring($cnfCol,$icnfSWCell,1)=$on">
        <xsl:variable name="p.cnfType" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfSWCell][1]"/>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$p.cnfType"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="prsPAccum.updated">
          <xsl:value-of select="$prsPAccum"/>
          <xsl:for-each select="$p.cnfType">
            <xsl:call-template name="ApplyPPr.many"/>
          </xsl:for-each>
        </xsl:variable>

        <div class="{concat($sTblStyleName/@w:styleId,'-',$cnfSWCell)}">
          <xsl:call-template name="DisplayBodyContent">
            <xsl:with-param name="ns.content" select="*"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum.updated"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
          </xsl:call-template>
        </div>
      </xsl:when>

      <xsl:otherwise>

        <xsl:call-template name="DisplayBodyContent">
          <xsl:with-param name="ns.content" select="*"/>
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="GetCnfPr.all">
    <xsl:param name="type"/>
    <xsl:param name="cnfCol"/>
    <xsl:param name="cnfRow"/>
    <xsl:choose>
      <xsl:when test="substring($cnfRow,$icnfBand1Horz,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand1Horz][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfBand2Horz,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand2Horz][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
    </xsl:choose>
    <xsl:choose>
      <xsl:when test="substring($cnfCol,$icnfBand1Vert,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand1Vert][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfBand2Vert,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand2Vert][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
    </xsl:choose>
    <xsl:choose>
      <xsl:when test="substring($cnfCol,$icnfFirstCol,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfFirstCol][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfLastCol,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfLastCol][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
    </xsl:choose>
    <xsl:choose>
      <xsl:when test="substring($cnfRow,$icnfFirstRow,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfFirstRow][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfLastRow,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfLastRow][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
    </xsl:choose>
    <xsl:choose>
      <xsl:when test="substring($cnfCol,$icnfNECell,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfNECell][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfNWCell,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfNWCell][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfSECell,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfSECell][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfSWCell,1)=$on">
        <xsl:for-each select="w:tblStylePr[@w:type=$cnfSWCell][1]">
          <xsl:call-template name="GetCnfPr.a">
            <xsl:with-param name="type" select="$type"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="GetCnfPr.cell">
    <xsl:param name="type"/>
    <xsl:param name="cnfCol"/>
    <xsl:param name="cnfRow"/>
    <xsl:variable name="result1">
      <xsl:choose>
        <xsl:when test="substring($cnfCol,$icnfNECell,1)=$on">
          <xsl:for-each select="w:tblStylePr[@w:type=$cnfNECell][1]">
            <xsl:call-template name="GetCnfPr.a">
              <xsl:with-param name="type" select="$type"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="substring($cnfCol,$icnfNWCell,1)=$on">
          <xsl:for-each select="w:tblStylePr[@w:type=$cnfNWCell][1]">
            <xsl:call-template name="GetCnfPr.a">
              <xsl:with-param name="type" select="$type"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="substring($cnfCol,$icnfSECell,1)=$on">
          <xsl:for-each select="w:tblStylePr[@w:type=$cnfSECell][1]">
            <xsl:call-template name="GetCnfPr.a">
              <xsl:with-param name="type" select="$type"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="substring($cnfCol,$icnfSWCell,1)=$on">
          <xsl:for-each select="w:tblStylePr[@w:type=$cnfSWCell][1]">
            <xsl:call-template name="GetCnfPr.a">
              <xsl:with-param name="type" select="$type"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
      </xsl:choose>
    </xsl:variable>
    <xsl:value-of select="$result1"/>
    <xsl:if test="$result1=''">
      <xsl:variable name="result2">
        <xsl:choose>
          <xsl:when test="substring($cnfRow,$icnfFirstRow,1)=$on">
            <xsl:for-each select="w:tblStylePr[@w:type=$cnfFirstRow][1]">
              <xsl:call-template name="GetCnfPr.a">
                <xsl:with-param name="type" select="$type"/>
              </xsl:call-template>
            </xsl:for-each>
          </xsl:when>
          <xsl:when test="substring($cnfRow,$icnfLastRow,1)=$on">
            <xsl:for-each select="w:tblStylePr[@w:type=$cnfLastRow][1]">
              <xsl:call-template name="GetCnfPr.a">
                <xsl:with-param name="type" select="$type"/>
              </xsl:call-template>
            </xsl:for-each>
          </xsl:when>
        </xsl:choose>
      </xsl:variable>
      <xsl:value-of select="$result2"/>
      <xsl:if test="$result2=''">
        <xsl:variable name="result3">
          <xsl:choose>
            <xsl:when test="substring($cnfCol,$icnfFirstCol,1)=$on">
              <xsl:for-each select="w:tblStylePr[@w:type=$cnfFirstCol][1]">
                <xsl:call-template name="GetCnfPr.a">
                  <xsl:with-param name="type" select="$type"/>
                </xsl:call-template>
              </xsl:for-each>
            </xsl:when>
            <xsl:when test="substring($cnfCol,$icnfLastCol,1)=$on">
              <xsl:for-each select="w:tblStylePr[@w:type=$cnfLastCol][1]">
                <xsl:call-template name="GetCnfPr.a">
                  <xsl:with-param name="type" select="$type"/>
                </xsl:call-template>
              </xsl:for-each>
            </xsl:when>
          </xsl:choose>
        </xsl:variable>
        <xsl:value-of select="$result3"/>
        <xsl:if test="$result3=''">
          <xsl:variable name="result4">
            <xsl:choose>
              <xsl:when test="substring($cnfCol,$icnfBand1Vert,1)=$on">
                <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand1Vert][1]">
                  <xsl:call-template name="GetCnfPr.a">
                    <xsl:with-param name="type" select="$type"/>
                  </xsl:call-template>
                </xsl:for-each>
              </xsl:when>
              <xsl:when test="substring($cnfCol,$icnfBand2Vert,1)=$on">
                <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand2Vert][1]">
                  <xsl:call-template name="GetCnfPr.a">
                    <xsl:with-param name="type" select="$type"/>
                  </xsl:call-template>
                </xsl:for-each>
              </xsl:when>
            </xsl:choose>
          </xsl:variable>
          <xsl:value-of select="$result4"/>
          <xsl:if test="$result4=''">
            <xsl:choose>
              <xsl:when test="substring($cnfRow,$icnfBand1Horz,1)=$on">
                <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand1Horz][1]">
                  <xsl:call-template name="GetCnfPr.a">
                    <xsl:with-param name="type" select="$type"/>
                  </xsl:call-template>
                </xsl:for-each>
              </xsl:when>
              <xsl:when test="substring($cnfRow,$icnfBand2Horz,1)=$on">
                <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand2Horz][1]">
                  <xsl:call-template name="GetCnfPr.a">
                    <xsl:with-param name="type" select="$type"/>
                  </xsl:call-template>
                </xsl:for-each>
              </xsl:when>
            </xsl:choose>
          </xsl:if>
        </xsl:if>
      </xsl:if>
    </xsl:if>
  </xsl:template>

  <xsl:template name="GetCnfPr.row">
    <xsl:param name="type"/>
    <xsl:param name="cnfRow"/>
    <xsl:variable name="result1">
      <xsl:choose>
        <xsl:when test="substring($cnfRow,$icnfFirstRow,1)=$on">
          <xsl:for-each select="w:tblStylePr[@w:type=$cnfFirstRow][1]">
            <xsl:call-template name="GetCnfPr.a">
              <xsl:with-param name="type" select="$type"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="substring($cnfRow,$icnfLastRow,1)=$on">
          <xsl:for-each select="w:tblStylePr[@w:type=$cnfLastRow][1]">
            <xsl:call-template name="GetCnfPr.a">
              <xsl:with-param name="type" select="$type"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
      </xsl:choose>
    </xsl:variable>
    <xsl:value-of select="$result1"/>
    <xsl:if test="$result1=''">
      <xsl:choose>
        <xsl:when test="substring($cnfRow,$icnfBand1Horz,1)=$on">
          <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand1Horz][1]">
            <xsl:call-template name="GetCnfPr.a">
              <xsl:with-param name="type" select="$type"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="substring($cnfRow,$icnfBand2Horz,1)=$on">
          <xsl:for-each select="w:tblStylePr[@w:type=$cnfBand2Horz][1]">
            <xsl:call-template name="GetCnfPr.a">
              <xsl:with-param name="type" select="$type"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:when>
      </xsl:choose>
    </xsl:if>
  </xsl:template>

  <xsl:template name="GetCnfPr.a">
    <xsl:param name="type"/>
    <xsl:choose>
      <xsl:when test="$type = $prrApplyTcPr">
        <xsl:call-template name="ApplyTcPr.class"/>
      </xsl:when>
      <xsl:when test="$type = $prrCustomCellpadding">
        <xsl:for-each select="w:tcPr[1]/w:tcMar[1]">
          <xsl:call-template name="ApplyCellMar"/>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="$type = $prrDefaultCellpadding">
        <xsl:for-each select="w:tblPr[1]/w:tblCellMar[1]">
          <xsl:call-template name="ApplyCellMar"/>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="$type = $prrCantSplit">
        <xsl:for-each select="w:trPr[1]/w:cantSplit[1]">
          <xsl:choose>
            <xsl:when test="@w:val = 'off'">page-break-inside:auto;</xsl:when>
            <xsl:otherwise>page-break-inside:avoid;</xsl:otherwise>
          </xsl:choose>
        </xsl:for-each>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="GetCnfType">
    <xsl:param name="cnfCol"/>
    <xsl:param name="cnfRow"/>
    <xsl:choose>
      <xsl:when test="substring($cnfCol,$icnfNECell,1)=$on">
        <xsl:value-of select="$cnfNECell"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfNWCell,1)=$on">
        <xsl:value-of select="$cnfNWCell"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfSECell,1)=$on">
        <xsl:value-of select="$cnfSECell"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfSWCell,1)=$on">
        <xsl:value-of select="$cnfSWCell"/>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfFirstRow,1)=$on">
        <xsl:value-of select="$cnfFirstRow"/>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfLastRow,1)=$on">
        <xsl:value-of select="$cnfLastRow"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfFirstCol,1)=$on">
        <xsl:value-of select="$cnfFirstCol"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfLastCol,1)=$on">
        <xsl:value-of select="$cnfLastCol"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfBand1Vert,1)=$on">
        <xsl:value-of select="$cnfBand1Vert"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfBand2Vert,1)=$on">
        <xsl:value-of select="$cnfBand2Vert"/>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfBand1Horz,1)=$on">
        <xsl:value-of select="$cnfBand1Horz"/>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfBand2Horz,1)=$on">
        <xsl:value-of select="$cnfBand2Horz"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="GetCnfTypeRow">
    <xsl:param name="cnfRow"/>
    <xsl:choose>
      <xsl:when test="substring($cnfRow,$icnfFirstRow,1)=$on">
        <xsl:value-of select="$cnfFirstRow"/>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfLastRow,1)=$on">
        <xsl:value-of select="$cnfLastRow"/>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfBand1Horz,1)=$on">
        <xsl:value-of select="$cnfBand1Horz"/>
      </xsl:when>
      <xsl:when test="substring($cnfRow,$icnfBand2Horz,1)=$on">
        <xsl:value-of select="$cnfBand2Horz"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="GetCnfTypeCol">
    <xsl:param name="cnfCol"/>
    <xsl:choose>
      <xsl:when test="substring($cnfCol,$icnfNECell,1)=$on">
        <xsl:value-of select="$cnfNECell"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfNWCell,1)=$on">
        <xsl:value-of select="$cnfNWCell"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfSECell,1)=$on">
        <xsl:value-of select="$cnfSECell"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfSWCell,1)=$on">
        <xsl:value-of select="$cnfSWCell"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfFirstCol,1)=$on">
        <xsl:value-of select="$cnfFirstCol"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfLastCol,1)=$on">
        <xsl:value-of select="$cnfLastCol"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfBand1Vert,1)=$on">
        <xsl:value-of select="$cnfBand1Vert"/>
      </xsl:when>
      <xsl:when test="substring($cnfCol,$icnfBand2Vert,1)=$on">
        <xsl:value-of select="$cnfBand2Vert"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="RecursiveRStyledGetBorderPr">
    <xsl:param name="rStyleId"/>

    <xsl:variable name="myStyle" select="($nsStyles[@w:styleId=$rStyleId])[1]" />

    <xsl:if test="not($rStyleId='')">
      <xsl:choose>
        <xsl:when test="$myStyle/w:rPr[1]/w:bdr[1]">
          <xsl:for-each select="$myStyle/w:rPr[1]/w:bdr[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:otherwise>
          <xsl:if test="$myStyle/w:basedOn">
            <xsl:call-template name="RecursiveRStyledGetBorderPr">
              <xsl:with-param name="rStyleId" select="$myStyle/w:basedOn/@w:val" />
            </xsl:call-template>
          </xsl:if>
        </xsl:otherwise>
      </xsl:choose>

    </xsl:if>
  </xsl:template>

  <xsl:template name="DisplayRBorder">
    <xsl:param name="ns.content" select="*"/>
    <xsl:param name="i.range.start" select="1"/>
    <xsl:param name="i.this" select="number($i.range.start)"/>
    <xsl:param name="pr.bdr.prev" select="''"/>
    <xsl:param name="b.bidi"/>
    <xsl:param name="prsR"/>
    <xsl:param name="runStyleName"/>
    <xsl:choose>

      <xsl:when test="($ns.content)[$i.this]">
        <xsl:for-each select="($ns.content)[$i.this]">
          <xsl:choose>

            <xsl:when test="name() = 'w:proofErr' or (name() = 'aml:annotation' and not(@w:type = 'Word.Insertion'))">
              <xsl:call-template name="DisplayRBorder">
                <xsl:with-param name="ns.content" select="$ns.content"/>
                <xsl:with-param name="i.range.start" select="$i.range.start"/>
                <xsl:with-param name="i.this" select="$i.this+1"/>
                <xsl:with-param name="pr.bdr.prev" select="$pr.bdr.prev"/>
                <xsl:with-param name="b.bidi" select="$b.bidi"/>
                <xsl:with-param name="prsR" select="$prsR"/>
                <xsl:with-param name="runStyleName" select="$runStyleName"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>

              <xsl:variable name="pr.bdr.this">
                <xsl:choose>

                  <xsl:when test="name()='aml:annotation'"/>

                  <xsl:otherwise>

                    <xsl:for-each select="descendant-or-self::*[name()='w:pPr' or name()='w:r'][1]">
                      <xsl:choose>
                        <xsl:when test="w:rPr[1]/w:bdr[1]">
                          <xsl:for-each select="w:rPr[1]/w:bdr[1]">
                            <xsl:call-template name="GetBorderPr"/>
                          </xsl:for-each>
                        </xsl:when>

                        <xsl:otherwise>
                          <xsl:call-template name="RecursiveRStyledGetBorderPr">
                            <xsl:with-param name="rStyleId" select="w:rPr[1]/w:rStyle[1]/@w:val" />
                          </xsl:call-template>
                        </xsl:otherwise>
                      </xsl:choose>
                    </xsl:for-each>
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:variable>
              <xsl:choose>

                <xsl:when test="$pr.bdr.prev = $pr.bdr.this">

                  <xsl:call-template name="DisplayRBorder">
                    <xsl:with-param name="ns.content" select="$ns.content"/>
                    <xsl:with-param name="i.range.start" select="$i.range.start"/>
                    <xsl:with-param name="i.this" select="$i.this+1"/>
                    <xsl:with-param name="pr.bdr.prev" select="$pr.bdr.prev"/>
                    <xsl:with-param name="b.bidi" select="$b.bidi"/>
                    <xsl:with-param name="prsR" select="$prsR"/>
                    <xsl:with-param name="runStyleName" select="$runStyleName"/>
                  </xsl:call-template>
                </xsl:when>

                <xsl:otherwise>

                  <xsl:call-template name="WrapRBorder">
                    <xsl:with-param name="ns.content" select="$ns.content"/>
                    <xsl:with-param name="i.bdrRange.start" select="$i.range.start"/>
                    <xsl:with-param name="i.bdrRange.end" select="$i.this"/>
                    <xsl:with-param name="pr.bdr" select="$pr.bdr.prev"/>
                    <xsl:with-param name="b.bidi" select="$b.bidi"/>
                    <xsl:with-param name="prsR" select="$prsR"/>
                    <xsl:with-param name="runStyleName" select="$runStyleName"/>
                  </xsl:call-template>

                  <xsl:call-template name="DisplayRBorder">
                    <xsl:with-param name="ns.content" select="$ns.content"/>
                    <xsl:with-param name="i.range.start" select="$i.this"/>
                    <xsl:with-param name="i.this" select="$i.this+1"/>
                    <xsl:with-param name="pr.bdr.prev" select="$pr.bdr.this"/>
                    <xsl:with-param name="b.bidi" select="$b.bidi"/>
                    <xsl:with-param name="prsR" select="$prsR"/>
                    <xsl:with-param name="runStyleName" select="$runStyleName"/>
                  </xsl:call-template>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:for-each>
      </xsl:when>

      <xsl:otherwise>

        <xsl:call-template name="WrapRBorder">
          <xsl:with-param name="ns.content" select="$ns.content"/>
          <xsl:with-param name="i.bdrRange.start" select="$i.range.start"/>
          <xsl:with-param name="i.bdrRange.end" select="$i.this"/>
          <xsl:with-param name="pr.bdr" select="$pr.bdr.prev"/>
          <xsl:with-param name="b.bidi" select="$b.bidi"/>
          <xsl:with-param name="prsR" select="$prsR"/>
          <xsl:with-param name="runStyleName" select="$runStyleName"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="WrapRBorder">
    <xsl:param name="ns.content"/>
    <xsl:param name="i.bdrRange.start"/>
    <xsl:param name="i.bdrRange.end"/>
    <xsl:param name="pr.bdr"/>
    <xsl:param name="b.bidi"/>
    <xsl:param name="prsR"/>
    <xsl:param name="runStyleName"/>
    <xsl:choose>

      <xsl:when test="$pr.bdr = ''">
        <xsl:apply-templates select="($ns.content)[position() &gt;= $i.bdrRange.start and position() &lt; $i.bdrRange.end]">
          <xsl:with-param name="b.bidi" select="$b.bidi"/>
          <xsl:with-param name="prsR" select="$prsR"/>
          <xsl:with-param name="runStyleName" select="$runStyleName"/>
        </xsl:apply-templates>
      </xsl:when>

      <xsl:otherwise>
        <span>
          <xsl:attribute name="style">
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$pr.bdr"/>
            </xsl:call-template>
          </xsl:attribute>
          <xsl:apply-templates select="($ns.content)[position() &gt;= $i.bdrRange.start and position() &lt; $i.bdrRange.end]">
            <xsl:with-param name="b.bidi" select="$b.bidi"/>
            <xsl:with-param name="prsR" select="$prsR"/>
            <xsl:with-param name="runStyleName" select="$runStyleName"/>
          </xsl:apply-templates>
        </span>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="DisplayPBorderOld">
    <xsl:param name="pr.frame.prev"/>
    <xsl:param name="pr.bdrTop.prev"/>
    <xsl:param name="pr.bdrLeft.prev"/>
    <xsl:param name="pr.bdrBottom.prev"/>
    <xsl:param name="pr.bdrRight.prev"/>
    <xsl:param name="pr.bdrBetween.prev"/>
    <xsl:param name="pr.bdrBar.prev"/>
    <xsl:param name="ns.content"/>
    <xsl:param name="i.range.start" select="1"/>
    <xsl:param name="i.this" select="number($i.range.start)"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="($ns.content)[$i.this]">
        <xsl:for-each select="($ns.content)[$i.this]">
          <xsl:variable name="pstyle">
            <xsl:call-template name="GetPStyleId"/>
          </xsl:variable>
          <xsl:variable name="sParaStyleName" select="($nsStyles[@w:styleId=$pstyle])[1]"/>

          <xsl:variable name="pr.frame.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrFrame"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrTop.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_top"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrLeft.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_left"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrBottom.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_bottom"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrRight.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_right"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrBetween.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_between"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrBar.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_bar"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:choose>

            <xsl:when test="0 = 1 and $pr.frame.prev = $pr.frame.this and $pr.bdrTop.prev = $pr.bdrTop.this and $pr.bdrLeft.prev = $pr.bdrLeft.this and $pr.bdrBottom.prev = $pr.bdrBottom.this and $pr.bdrRight.prev = $pr.bdrRight.this and $pr.bdrBetween.prev = $pr.bdrBetween.this and $pr.bdrBar.prev = $pr.bdrBar.this">
              <xsl:call-template name="DisplayPBorder">
                <xsl:with-param name="ns.content" select="$ns.content"/>
                <xsl:with-param name="i.range.start" select="$i.range.start"/>
                <xsl:with-param name="i.this" select="$i.this+1"/>
                <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
                <xsl:with-param name="prsP" select="$prsP"/>
                <xsl:with-param name="prsR" select="$prsR"/>
                <xsl:with-param name="pr.frame.prev" select="$pr.frame.prev"/>
                <xsl:with-param name="pr.bdrTop.prev" select="$pr.bdrTop.prev"/>
                <xsl:with-param name="pr.bdrLeft.prev" select="$pr.bdrLeft.prev"/>
                <xsl:with-param name="pr.bdrBottom.prev" select="$pr.bdrBottom.prev"/>
                <xsl:with-param name="pr.bdrRight.prev" select="$pr.bdrRight.prev"/>
                <xsl:with-param name="pr.bdrBetween.prev" select="$pr.bdrBetween.prev"/>
                <xsl:with-param name="pr.bdrBar.prev" select="$pr.bdrBar.prev"/>
              </xsl:call-template>
            </xsl:when>

            <xsl:otherwise>

              <xsl:call-template name="wrapFrame">
                <xsl:with-param name="ns.content" select="$ns.content"/>
                <xsl:with-param name="i.bdrRange.start" select="$i.range.start"/>
                <xsl:with-param name="i.bdrRange.end" select="$i.this"/>
                <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
                <xsl:with-param name="prsP" select="$prsP"/>
                <xsl:with-param name="prsR" select="$prsR"/>
                <xsl:with-param name="framePr" select="$pr.frame.prev"/>
                <xsl:with-param name="pr.bdrTop" select="$pr.bdrTop.prev"/>
                <xsl:with-param name="pr.bdrLeft" select="$pr.bdrLeft.prev"/>
                <xsl:with-param name="pr.bdrBottom" select="$pr.bdrBottom.prev"/>
                <xsl:with-param name="pr.bdrRight" select="$pr.bdrRight.prev"/>
                <xsl:with-param name="pr.bdrBetween" select="$pr.bdrBetween.prev"/>
                <xsl:with-param name="pr.bdrBar" select="$pr.bdrBar.prev"/>
              </xsl:call-template>

              <xsl:call-template name="DisplayPBorder">
                <xsl:with-param name="ns.content" select="$ns.content"/>
                <xsl:with-param name="i.range.start" select="$i.this"/>
                <xsl:with-param name="i.this" select="$i.this+1"/>
                <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
                <xsl:with-param name="prsP" select="$prsP"/>
                <xsl:with-param name="prsR" select="$prsR"/>
                <xsl:with-param name="pr.frame.prev" select="$pr.frame.this"/>
                <xsl:with-param name="pr.bdrTop.prev" select="$pr.bdrTop.this"/>
                <xsl:with-param name="pr.bdrLeft.prev" select="$pr.bdrLeft.this"/>
                <xsl:with-param name="pr.bdrBottom.prev" select="$pr.bdrBottom.this"/>
                <xsl:with-param name="pr.bdrRight.prev" select="$pr.bdrRight.this"/>
                <xsl:with-param name="pr.bdrBetween.prev" select="$pr.bdrBetween.this"/>
                <xsl:with-param name="pr.bdrBar.prev" select="$pr.bdrBar.this"/>
              </xsl:call-template>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:for-each>
      </xsl:when>

      <xsl:otherwise>
        <xsl:call-template name="wrapFrame">
          <xsl:with-param name="ns.content" select="$ns.content"/>
          <xsl:with-param name="i.bdrRange.start" select="$i.range.start"/>
          <xsl:with-param name="i.bdrRange.end" select="$i.this"/>
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
          <xsl:with-param name="framePr" select="$pr.frame.prev"/>
          <xsl:with-param name="pr.bdrTop" select="$pr.bdrTop.prev"/>
          <xsl:with-param name="pr.bdrLeft" select="$pr.bdrLeft.prev"/>
          <xsl:with-param name="pr.bdrBottom" select="$pr.bdrBottom.prev"/>
          <xsl:with-param name="pr.bdrRight" select="$pr.bdrRight.prev"/>
          <xsl:with-param name="pr.bdrBetween" select="$pr.bdrBetween.prev"/>
          <xsl:with-param name="pr.bdrBar" select="$pr.bdrBar.prev"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="DisplayPBorder">
    <xsl:param name="pr.frame.prev"/>
    <xsl:param name="pr.bdrTop.prev"/>
    <xsl:param name="pr.bdrLeft.prev"/>
    <xsl:param name="pr.bdrBottom.prev"/>
    <xsl:param name="pr.bdrRight.prev"/>
    <xsl:param name="pr.bdrBetween.prev"/>
    <xsl:param name="pr.bdrBar.prev"/>
    <xsl:param name="ns.content"/>
    <xsl:param name="i.range.start" select="1"/>
    <xsl:param name="i.this" select="number($i.range.start)"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="($ns.content)[$i.this]">
        <xsl:for-each select="($ns.content)">

          <xsl:variable name="pstyle">
            <xsl:call-template name="GetPStyleId"/>
          </xsl:variable>
          <xsl:variable name="sParaStyleName" select="($nsStyles[@w:styleId=$pstyle])[1]"/>

          <xsl:variable name="pr.frame.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrFrame"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrTop.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_top"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrLeft.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_left"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrBottom.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_bottom"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrRight.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_right"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrBetween.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_between"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:variable name="pr.bdrBar.this">
            <xsl:call-template name="GetSinglePPr">
              <xsl:with-param name="type" select="$prrBdrPr_bar"/>
              <xsl:with-param name="sParaStyleName" select="$sParaStyleName"/>
            </xsl:call-template>
          </xsl:variable>

          <xsl:call-template name="wrapFrame">
            <xsl:with-param name="ns.content" select="."/>
            <xsl:with-param name="i.bdrRange.start" select="1"/>
            <xsl:with-param name="i.bdrRange.end" select="2"/>
            <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
            <xsl:with-param name="prsP" select="$prsP"/>
            <xsl:with-param name="prsR" select="$prsR"/>
            <xsl:with-param name="framePr" select="$pr.frame.prev"/>
            <xsl:with-param name="pr.bdrTop" select="$pr.bdrTop.prev"/>
            <xsl:with-param name="pr.bdrLeft" select="$pr.bdrLeft.prev"/>
            <xsl:with-param name="pr.bdrBottom" select="$pr.bdrBottom.prev"/>
            <xsl:with-param name="pr.bdrRight" select="$pr.bdrRight.prev"/>
            <xsl:with-param name="pr.bdrBetween" select="$pr.bdrBetween.prev"/>
            <xsl:with-param name="pr.bdrBar" select="$pr.bdrBar.prev"/>
          </xsl:call-template>
        </xsl:for-each>

      </xsl:when>

      <xsl:otherwise>
        <xsl:call-template name="wrapFrame">
          <xsl:with-param name="ns.content" select="$ns.content"/>
          <xsl:with-param name="i.bdrRange.start" select="$i.range.start"/>
          <xsl:with-param name="i.bdrRange.end" select="$i.this"/>
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
          <xsl:with-param name="framePr" select="$pr.frame.prev"/>
          <xsl:with-param name="pr.bdrTop" select="$pr.bdrTop.prev"/>
          <xsl:with-param name="pr.bdrLeft" select="$pr.bdrLeft.prev"/>
          <xsl:with-param name="pr.bdrBottom" select="$pr.bdrBottom.prev"/>
          <xsl:with-param name="pr.bdrRight" select="$pr.bdrRight.prev"/>
          <xsl:with-param name="pr.bdrBetween" select="$pr.bdrBetween.prev"/>
          <xsl:with-param name="pr.bdrBar" select="$pr.bdrBar.prev"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="wrapFrame">
    <xsl:param name="framePr"/>
    <xsl:param name="pr.bdrTop"/>
    <xsl:param name="pr.bdrLeft"/>
    <xsl:param name="pr.bdrBottom"/>
    <xsl:param name="pr.bdrRight"/>
    <xsl:param name="pr.bdrBetween"/>
    <xsl:param name="pr.bdrBar"/>
    <xsl:param name="ns.content"/>
    <xsl:param name="i.bdrRange.start"/>
    <xsl:param name="i.bdrRange.end"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="$framePr = ''">
        <xsl:call-template name="wrapPBdr">
          <xsl:with-param name="ns.content" select="$ns.content"/>
          <xsl:with-param name="i.bdrRange.start" select="$i.bdrRange.start"/>
          <xsl:with-param name="i.bdrRange.end" select="$i.bdrRange.end"/>
          <xsl:with-param name="pr.bdrTop" select="$pr.bdrTop"/>
          <xsl:with-param name="pr.bdrLeft" select="$pr.bdrLeft"/>
          <xsl:with-param name="pr.bdrBottom" select="$pr.bdrBottom"/>
          <xsl:with-param name="pr.bdrRight" select="$pr.bdrRight"/>
          <xsl:with-param name="pr.bdrBetween" select="$pr.bdrBetween"/>
          <xsl:with-param name="pr.bdrBar" select="$pr.bdrBar"/>
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:when>

      <xsl:otherwise>
        <xsl:variable name="width" select="substring-before($framePr,$sep2)"/>
        <xsl:variable name="framePr1" select="substring-after($framePr,$sep2)"/>
        <xsl:variable name="height" select="substring-before($framePr1,$sep2)"/>
        <xsl:variable name="framePr2" select="substring-after($framePr1,$sep2)"/>
        <xsl:variable name="hrule" select="substring-before($framePr2,$sep2)"/>
        <xsl:variable name="framePr3" select="substring-after($framePr2,$sep2)"/>
        <xsl:variable name="xalign" select="substring-before($framePr3,$sep2)"/>
        <xsl:variable name="framePr4" select="substring-after($framePr3,$sep2)"/>
        <xsl:variable name="vspace" select="substring-before($framePr4,$sep2)"/>
        <xsl:variable name="framePr5" select="substring-after($framePr4,$sep2)"/>
        <xsl:variable name="hspace" select="substring-before($framePr5,$sep2)"/>
        <xsl:variable name="framePr6" select="substring-after($framePr5,$sep2)"/>
        <xsl:variable name="wrap" select="substring-before($framePr6,$sep2)"/>

        <table cellspacing="0" cellpadding="0" hspace="0" vspace="0">
          <xsl:if test="not($width = '' and $height='')">
            <xsl:attribute name="style">
              <xsl:if test="not($width = '')">
                width:<xsl:value-of select="$width div 20"/>pt;
              </xsl:if>
              <xsl:if test="not($height = '')">
                height:<xsl:value-of select="$height div 20"/>pt;
              </xsl:if>
            </xsl:attribute>
          </xsl:if>
          <xsl:attribute name="align">
            <xsl:choose>
              <xsl:when test="$xalign = 'right' or $xalign = 'outside'">right</xsl:when>
              <xsl:otherwise>left</xsl:otherwise>
            </xsl:choose>
          </xsl:attribute>
          <tr>
            <td valign="top" align="left">
              <xsl:attribute name="style">
                <xsl:text>padding:</xsl:text>
                <xsl:choose>
                  <xsl:when test="$vspace = ''">0</xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="$vspace div 20"/>pt
                  </xsl:otherwise>
                </xsl:choose>
                <xsl:text> </xsl:text>
                <xsl:choose>
                  <xsl:when test="$hspace = ''">0</xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="$hspace div 20"/>pt
                  </xsl:otherwise>
                </xsl:choose>
                <xsl:text>;</xsl:text>
              </xsl:attribute>

              <xsl:call-template name="wrapPBdr">
                <xsl:with-param name="ns.content" select="$ns.content"/>
                <xsl:with-param name="i.bdrRange.start" select="$i.bdrRange.start"/>
                <xsl:with-param name="i.bdrRange.end" select="$i.bdrRange.end"/>
                <xsl:with-param name="pr.bdrTop" select="$pr.bdrTop"/>
                <xsl:with-param name="pr.bdrLeft" select="$pr.bdrLeft"/>
                <xsl:with-param name="pr.bdrBottom" select="$pr.bdrBottom"/>
                <xsl:with-param name="pr.bdrRight" select="$pr.bdrRight"/>
                <xsl:with-param name="pr.bdrBetween" select="$pr.bdrBetween"/>
                <xsl:with-param name="pr.bdrBar" select="$pr.bdrBar"/>
                <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
                <xsl:with-param name="prsP" select="$prsP"/>
                <xsl:with-param name="prsR" select="$prsR"/>
              </xsl:call-template>
            </td>
          </tr>
        </table>
        <xsl:if test="$wrap = '' or $wrap = 'none' or $wrap = 'not-beside'">
          <br clear="all"/>
        </xsl:if>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="wrapPBdr">
    <xsl:param name="pr.bdrTop"/>
    <xsl:param name="pr.bdrLeft"/>
    <xsl:param name="pr.bdrBottom"/>
    <xsl:param name="pr.bdrRight"/>
    <xsl:param name="pr.bdrBetween"/>
    <xsl:param name="pr.bdrBar"/>
    <xsl:param name="ns.content"/>
    <xsl:param name="i.bdrRange.start"/>
    <xsl:param name="i.bdrRange.end"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:choose>

      <xsl:when test="$pr.bdrTop = '' and $pr.bdrLeft = '' and $pr.bdrBottom = '' and $pr.bdrRight = '' and $pr.bdrBar = ''">
        <xsl:apply-templates select="($ns.content)[position() &gt;= $i.bdrRange.start and position() &lt; $i.bdrRange.end]">
          <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
          <xsl:with-param name="prsP" select="$prsP"/>
          <xsl:with-param name="prsR" select="$prsR"/>
          <xsl:with-param name="pr.bdrBetween" select="$pr.bdrBetween"/>
        </xsl:apply-templates>
      </xsl:when>

      <xsl:otherwise>
        <div>

          <xsl:attribute name="style">
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$pr.bdrBar"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
            </xsl:call-template>
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$pr.bdrTop"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
            </xsl:call-template>
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$pr.bdrLeft"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
            </xsl:call-template>
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$pr.bdrBottom"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
            </xsl:call-template>
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$pr.bdrRight"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
            </xsl:call-template>
            <xsl:text>padding:</xsl:text>
            <xsl:variable name="topPad" select="substring-before(substring-after(substring-after(substring-after($pr.bdrTop,$sep2),$sep2),$sep2),$sep2)"/>
            <xsl:variable name="rightPad" select="substring-before(substring-after(substring-after(substring-after($pr.bdrRight,$sep2),$sep2),$sep2),$sep2)"/>
            <xsl:variable name="bottomPad" select="substring-before(substring-after(substring-after(substring-after($pr.bdrBottom,$sep2),$sep2),$sep2),$sep2)"/>
            <xsl:variable name="leftPad" select="substring-before(substring-after(substring-after(substring-after($pr.bdrLeft,$sep2),$sep2),$sep2),$sep2)"/>
            <xsl:choose>
              <xsl:when test="$topPad = ''">0</xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$topPad"/>pt
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text> </xsl:text>
            <xsl:choose>
              <xsl:when test="$rightPad = ''">0</xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$rightPad"/>pt
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text> </xsl:text>
            <xsl:choose>
              <xsl:when test="$bottomPad = ''">0</xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$bottomPad"/>pt
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text> </xsl:text>
            <xsl:choose>
              <xsl:when test="$leftPad = ''">0</xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$leftPad"/>pt
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text>;</xsl:text>
          </xsl:attribute>

          <xsl:apply-templates select="($ns.content)[position() &gt;= $i.bdrRange.start and position() &lt; $i.bdrRange.end]">
            <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
            <xsl:with-param name="prsP" select="$prsP"/>
            <xsl:with-param name="prsR" select="$prsR"/>
            <xsl:with-param name="pr.bdrBetween" select="$pr.bdrBetween"/>
          </xsl:apply-templates>
        </div>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="ApplyArgs">
    <xsl:param name="value"/>
    <xsl:variable name="attributeName" select="normalize-space(substring-before($value,'='))"/>
    <xsl:variable name="afterName" select="concat(substring-after($value,'='),' ')"/>
    <xsl:if test="not($attributeName = '')">
      <xsl:attribute name="{$attributeName}">
        <xsl:value-of select="normalize-space(translate(substring-before($afterName,' '),'&quot;',' '))"/>
      </xsl:attribute>
      <xsl:call-template name="ApplyArgs">
        <xsl:with-param name="value" select="normalize-space(substring-after($afterName,' '))"/>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>

  <xsl:template match="w:scriptAnchor">
    <script>
      <xsl:apply-templates select="*" mode="scriptAnchor"/>
    </script>
  </xsl:template>
  <xsl:template match="w:args" mode="scriptAnchor">
    <xsl:call-template name="ApplyArgs">
      <xsl:with-param name="value" select="."/>
    </xsl:call-template>
  </xsl:template>
  <xsl:template match="w:language" mode="scriptAnchor">
    <xsl:attribute name="language">
      <xsl:value-of select="."/>
    </xsl:attribute>
  </xsl:template>
  <xsl:template match="w:scriptId" mode="scriptAnchor">
    <xsl:attribute name="id">
      <xsl:value-of select="."/>
    </xsl:attribute>
  </xsl:template>
  <xsl:template match="w:scriptText" mode="scriptAnchor">
    <xsl:value-of disable-output-escaping="yes" select="."/>
  </xsl:template>
  <xsl:template match="*" mode="scriptAnchor"/>

  <xsl:template match="w:applet">
    <applet>
      <xsl:apply-templates select="*" mode="applet"/>
    </applet>
  </xsl:template>
  <xsl:template match="w:appletText" mode="applet">
    <xsl:value-of disable-output-escaping="yes" select="."/>
  </xsl:template>
  <xsl:template match="w:args" mode="applet">
    <xsl:call-template name="ApplyArgs">
      <xsl:with-param name="value" select="."/>
    </xsl:call-template>
  </xsl:template>
  <xsl:template match="*" mode="applet"/>

  <xsl:template match="w:txbxContent">
    <xsl:call-template name="DisplayBodyContent">
      <xsl:with-param name="ns.content" select="*"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="WX:pBdrGroup">
    <xsl:variable name="dxaLeft" select="WX:margin-left/@WX:val"/>
    <xsl:variable name="dxaRight" select="WX:margin-right/@WX:val"/>
    <xsl:variable name="ns.borders" select="WX:borders"/>

    <xsl:variable name="bdrStyles">
      <xsl:if test="$ns.borders/WX:top">
        <xsl:text>border-top:</xsl:text>
        <xsl:value-of select="$ns.borders/WX:top/@WX:val"/>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$ns.borders/WX:top/@WX:bdrwidth div 20"/>
        <xsl:text>pt </xsl:text>
        <xsl:call-template name="ConvHexColor">
          <xsl:with-param name="value" select="$ns.borders/WX:top/@WX:color"/>
        </xsl:call-template>
        <xsl:text>;padding-top:</xsl:text>
        <xsl:value-of select="$ns.borders/WX:top/@WX:space"/>
        <xsl:text>pt</xsl:text>
      </xsl:if>
      <xsl:if test="$ns.borders/WX:bottom">
        <xsl:text>;border-bottom:</xsl:text>
        <xsl:value-of select="$ns.borders/WX:bottom/@WX:val"/>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$ns.borders/WX:bottom/@WX:bdrwidth div 20"/>
        <xsl:text>pt </xsl:text>
        <xsl:call-template name="ConvHexColor">
          <xsl:with-param name="value" select="$ns.borders/WX:bottom/@WX:color"/>
        </xsl:call-template>
        <xsl:text>;padding-bottom:</xsl:text>
        <xsl:value-of select="$ns.borders/WX:bottom/@WX:space"/>
        <xsl:text>pt</xsl:text>
      </xsl:if>
      <xsl:if test="$ns.borders/WX:right">
        <xsl:text>;border-right:</xsl:text>
        <xsl:value-of select="$ns.borders/WX:right/@WX:val"/>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$ns.borders/WX:right/@WX:bdrwidth div 20"/>
        <xsl:text>pt </xsl:text>
        <xsl:call-template name="ConvHexColor">
          <xsl:with-param name="value" select="$ns.borders/WX:right/@WX:color"/>
        </xsl:call-template>
        <xsl:text>;padding-right:</xsl:text>
        <xsl:value-of select="$ns.borders/WX:right/@WX:space"/>
        <xsl:text>pt</xsl:text>
      </xsl:if>
      <xsl:if test="$ns.borders/WX:left">
        <xsl:text>;border-left:</xsl:text>
        <xsl:value-of select="$ns.borders/WX:left/@WX:val"/>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$ns.borders/WX:left/@WX:bdrwidth div 20"/>
        <xsl:text>pt </xsl:text>
        <xsl:call-template name="ConvHexColor">
          <xsl:with-param name="value" select="$ns.borders/WX:left/@WX:color"/>
        </xsl:call-template>
        <xsl:text>;padding-left:</xsl:text>
        <xsl:value-of select="$ns.borders/WX:left/@WX:space"/>
        <xsl:text>pt</xsl:text>
      </xsl:if>
      <xsl:if test="$dxaLeft">
        <xsl:text>;margin-left:</xsl:text>
        <xsl:value-of select="$dxaLeft div 20"/>
        <xsl:text>pt</xsl:text>
      </xsl:if>
      <xsl:if test="$dxaRight">
        <xsl:text>;margin-right:</xsl:text>
        <xsl:value-of select="$dxaRight div 20"/>
        <xsl:text>pt</xsl:text>
      </xsl:if>
      <xsl:if test="WX:shd">
        <xsl:text>;background-color:</xsl:text>
        <xsl:call-template name="ConvHexColor">
          <xsl:with-param name="value" select="WX:shd/@WX:bgcolor"/>
          <xsl:with-param name="autoColor" select="$autoColor_bg"/>
          <xsl:with-param name="transparentColor">transparent</xsl:with-param>
        </xsl:call-template>
      </xsl:if>
    </xsl:variable>

    <xsl:choose>
      <xsl:when test="WX:apo">
        <table cellspacing="0" cellpadding="0" hspace="0" vspace="0">
          <xsl:choose>
            <xsl:when test="WX:apo/WX:jc/@WX:val">
              <xsl:attribute name="align">
                <xsl:value-of select="WX:apo/WX:jc/@WX:val"/>
              </xsl:attribute>
            </xsl:when>
            <xsl:otherwise>
              <xsl:attribute name="align">
                <xsl:text>left</xsl:text>
              </xsl:attribute>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:attribute name="style">
            <xsl:if test="WX:apo/WX:width/@WX:val">
              <xsl:text>;width:</xsl:text>
              <xsl:value-of select="WX:apo/WX:width/@WX:val div 20"/>
              <xsl:text>pt</xsl:text>
            </xsl:if>
            <xsl:if test="WX:apo/WX:height/@WX:val">
              <xsl:text>;height:</xsl:text>
              <xsl:value-of select="WX:apo/WX:height/@WX:val div 20"/>
              <xsl:text>pt</xsl:text>
            </xsl:if>
          </xsl:attribute>
          <tr>
            <td valign="top" align="left">
              <xsl:attribute name="style">
                <xsl:if test="WX:apo/WX:vertFromText/@WX:val">
                  <xsl:text>;padding-top:</xsl:text>
                  <xsl:value-of select="WX:apo/WX:vertFromText/@WX:val div 20"/>
                  <xsl:text>pt</xsl:text>
                  <xsl:text>;padding-bottom:</xsl:text>
                  <xsl:value-of select="WX:apo/WX:vertFromText/@WX:val div 20"/>
                  <xsl:text>pt</xsl:text>
                </xsl:if>
                <xsl:if test="WX:apo/WX:horizFromText/@WX:val">
                  <xsl:text>;padding-right:</xsl:text>
                  <xsl:value-of select="WX:apo/WX:horizFromText/@WX:val div 20"/>
                  <xsl:text>pt</xsl:text>
                  <xsl:text>;padding-left:</xsl:text>
                  <xsl:value-of select="WX:apo/WX:horizFromText/@WX:val div 20"/>
                  <xsl:text>pt</xsl:text>
                </xsl:if>
              </xsl:attribute>
              <div>
                <xsl:attribute name="style">
                  <xsl:value-of select="$bdrStyles"/>
                </xsl:attribute>

                <div>
                  <xsl:attribute name="style">
                    <xsl:if test="$dxaLeft">
                      <xsl:text>;margin-left:-</xsl:text>
                      <xsl:value-of select="$dxaLeft div 20"/>
                      <xsl:text>pt</xsl:text>
                    </xsl:if>
                    <xsl:if test="$dxaRight">
                      <xsl:text>;margin-right:-</xsl:text>
                      <xsl:value-of select="$dxaRight div 20"/>
                      <xsl:text>pt</xsl:text>
                    </xsl:if>
                  </xsl:attribute>
                  <xsl:call-template name="DisplayBodyContent">
                    <xsl:with-param name="ns.content" select="*"/>
                  </xsl:call-template>
                </div>
              </div>
            </td>
          </tr>
        </table>
      </xsl:when>
      <xsl:otherwise>
        <div>
          <xsl:attribute name="style">
            <xsl:value-of select="$bdrStyles"/>
          </xsl:attribute>

          <div>
            <xsl:attribute name="style">
              <xsl:if test="$dxaLeft">
                <xsl:text>;margin-left:-</xsl:text>
                <xsl:value-of select="$dxaLeft div 20"/>
                <xsl:text>pt</xsl:text>
              </xsl:if>
              <xsl:if test="$dxaRight">
                <xsl:text>;margin-right:-</xsl:text>
                <xsl:value-of select="$dxaRight div 20"/>
                <xsl:text>pt</xsl:text>
              </xsl:if>
            </xsl:attribute>

            <xsl:call-template name="DisplayBodyContent">
              <xsl:with-param name="ns.content" select="*"/>
            </xsl:call-template>
          </div>
        </div>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!-- by sunil for positioning-->
  <xsl:template match="svg">
    <xsl:apply-templates select="*"/>
    <div>
      <xsl:attribute name="style">
        <xsl:text> padding-bottom:5px;</xsl:text>
      </xsl:attribute>
    </div>
  </xsl:template>  
  <!--<xsl:template match="br">
    <br></br>
  </xsl:template>-->
  <xsl:template match="w:br">
    <br>
      <xsl:attribute name="clear">
        <xsl:choose>
          <xsl:when test="@w:clear">
            <xsl:value-of select="@w:clear"/>
          </xsl:when>
          <xsl:otherwise>all</xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
      <xsl:if test="@w:type = 'page'">
        <xsl:attribute name="style">page-break-before:always</xsl:attribute>
      </xsl:if>
    </br>
  </xsl:template>

  <xsl:template match="w:instrText">
  </xsl:template>

  <xsl:template match="w:delText">
    <xsl:if test="/w:document/w:settings/w:trackRevisions">
      <del>
        <xsl:value-of select="."/>
      </del>
    </xsl:if>
  </xsl:template>

  <xsl:template match="w:r//w:t[../w:rPr/WX:sym]">
    <xsl:variable name="p.SymHint" select="../w:rPr/WX:sym"/>

    <span>
      <xsl:attribute name="style">
        font-family:<xsl:value-of select="$p.SymHint/@WX:font"/>
      </xsl:attribute>
      <xsl:choose>
        <xsl:when test="starts-with($p.SymHint/@WX:char, 'F0')">
          <xsl:text disable-output-escaping="yes">&amp;</xsl:text>#x<xsl:value-of select="substring-after($p.SymHint/@WX:char, 'F0')"/><xsl:text>;</xsl:text>
        </xsl:when>
        <xsl:when test="starts-with($p.SymHint/@WX:char, 'f0')">
          <xsl:text disable-output-escaping="yes">&amp;</xsl:text>#x<xsl:value-of select="substring-after($p.SymHint/@WX:char, 'f0')"/><xsl:text>;</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text disable-output-escaping="yes">&amp;</xsl:text>#x<xsl:value-of select="$p.SymHint/@WX:char"/><xsl:text>;</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </span>

  </xsl:template>
  
  <xsl:template match="w:pict">
    <span >
      <xsl:value-of select="."/>
    </span>

    <br />

  </xsl:template>
  
  <xsl:template match="w:t">
     <xsl:value-of select ="."/>   
  </xsl:template>

  <xsl:template match="w:sym">
    <span>
      <xsl:attribute name="style">
        font-family:<xsl:value-of select="@w:font"/>
      </xsl:attribute>
      <xsl:choose>
        <xsl:when test="starts-with(@w:char, 'F0')">
          <xsl:text disable-output-escaping="yes">&amp;</xsl:text>#x<xsl:value-of select="substring-after(@w:char, 'F0')"/><xsl:text>;</xsl:text>
        </xsl:when>
        <xsl:when test="starts-with(@w:char, 'f0')">
          <xsl:text disable-output-escaping="yes">&amp;</xsl:text>#x<xsl:value-of select="substring-after(@w:char, 'f0')"/><xsl:text>;</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text disable-output-escaping="yes">&amp;</xsl:text>#x<xsl:value-of select="@w:char"/><xsl:text>;</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </span>
  </xsl:template>

  <xsl:template name="OutputTlcChar">
    <xsl:param name="count" select="0"/>
    <xsl:param name="tlc" select="' '"/>
    <xsl:value-of select="$tlc"/>
    <xsl:if test="$count > 1">
      <xsl:call-template name="OutputTlcChar">
        <xsl:with-param name="count" select="$count - 1"/>
        <xsl:with-param name="tlc" select="$tlc"/>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>

  <xsl:template match="w:tab">    
    <!-- Parwati:Commented to fix tab indentation.
    <xsl:call-template name="OutputTlcChar">
      <xsl:with-param name="tlc">
        <xsl:text disable-output-escaping="yes">&#160;</xsl:text>
      </xsl:with-param>
      <xsl:with-param name="count" select="12"/>
      </xsl:call-template>-->
   <!--by default tab value will be 5 inch-->
    <span>
    <xsl:attribute name="style">
      <xsl:choose>
        <xsl:when test="./@w:pos">          
            <xsl:choose>
              <xsl:when test="./@w:pos and ./@w:val = 'left'">
                  margin-left:
                  <xsl:choose>
                    <xsl:when test="@w:pos &lt; 0">
                      <xsl:value-of select="@w:pos div -20"/>pt;
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:value-of select="@w:pos div 20"/>pt;
                    </xsl:otherwise>
                  </xsl:choose>               
              </xsl:when>
              <xsl:when test="./@w:pos and ./@w:val = 'right'">
                <xsl:choose>
                  <xsl:when test="@w:pos &lt; 0">
                    <xsl:value-of select="@w:pos div -20"/>pt;
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="@w:pos div 20"/>pt;
                  </xsl:otherwise>
                </xsl:choose>    
              </xsl:when>              
            </xsl:choose>              
        </xsl:when>
        <xsl:otherwise>
          margin-left:26pt
        </xsl:otherwise>
      </xsl:choose>     
    </xsl:attribute>
    </span>  
  </xsl:template>
<!-- Parwati modified to correct tab indentation-->
  <xsl:template match="w:softHyphen">
    <xsl:text>&#xAD;</xsl:text>
  </xsl:template>

  <xsl:template match="w:noBreakHyphen">
    <xsl:text disable-output-escaping="yes">&amp;#8209;</xsl:text>
  </xsl:template>

  <xsl:template name="DisplayRContent">
    <xsl:choose>

      <xsl:when test="w:numPr">

        <xsl:choose>
          <xsl:when test="w:numPr[1]/w:ilvl/@isBullet">
            <xsl:text disable-output-escaping="yes">&amp;#8226;&amp;#160;</xsl:text>
          </xsl:when>
          <xsl:when test="w:numPr[1]/w:ilvl/@numFont">
            <span>
              <xsl:attribute name="style">
                font-family:<xsl:value-of select="w:numPr[1]/w:ilvl/@numFont"/>
              </xsl:attribute>
              <xsl:value-of select="w:numPr[1]/w:ilvl/@numString"/>
            </span>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="w:numPr[1]/w:ilvl/@numString"/>
          </xsl:otherwise>
        </xsl:choose>

        <xsl:if test="w:numPr[1]/WX:t/@WX:wTabAfter">
          <span>
            <xsl:attribute name="style">
              <xsl:text>padding-left:</xsl:text>
              <xsl:value-of select="(w:numPr[1]/WX:t/@WX:wTabAfter div 20)" />
              <xsl:text>pt;</xsl:text>
            </xsl:attribute>
          </span>
        </xsl:if>
      </xsl:when>

      <xsl:otherwise>
        <xsl:apply-templates select="*"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="ApplyRPr.once">
    <xsl:param name="rStyleId"/>
    <xsl:param name="b.bidi"/>
    <xsl:param name="prsR"/>

    <xsl:variable name="b.complexScript">
      <xsl:choose>
        <xsl:when test="w:rPr[1]/w:cs[1] or w:rPr[1]/w:rtl[1]">
          <xsl:value-of select="$on"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$off"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:if test="$b.complexScript = $on">
      <xsl:variable name="suffix.complexScript">-CS</xsl:variable>
      <xsl:variable name="b.font-weight" select="substring($prsR,$iBCs,1)"/>
      <xsl:variable name="b.font-style" select="substring($prsR,$iICs,1)"/>
      <xsl:variable name="pr.sz" select="substring($prsR,$ISzCs)"/>

      <xsl:choose>
        <xsl:when test="$b.font-style = $on">font-style:italic;</xsl:when>
        <xsl:otherwise>font-style:normal;</xsl:otherwise>
      </xsl:choose>

      <xsl:choose>
        <xsl:when test="$b.font-weight = $on">font-weight:bold;</xsl:when>
        <xsl:otherwise>font-weight:normal;</xsl:otherwise>
      </xsl:choose>

      <xsl:choose>
        <xsl:when test="$pr.sz = ''">font-size:12pt;</xsl:when>
        <xsl:otherwise>
          font-size:<xsl:value-of select="$pr.sz div 2"/>pt;
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>

    <xsl:if test="not($b.bidi = '')">
      <xsl:choose>
        <xsl:when test="$b.bidi = $on and not($b.complexScript = $on)">direction:ltr;</xsl:when>
        <xsl:when test="not($b.bidi = $on) and $b.complexScript = $on">direction:rtl;</xsl:when>
      </xsl:choose>
    </xsl:if>

    <xsl:if test="substring($prsR,$iEmbossImprint,1) = $on">color:gray;</xsl:if>

    <xsl:variable name="b.line-through" select="substring($prsR,$iStrikeDStrike,1)"/>
    <xsl:variable name="b.underline" select="substring($prsR,$iU_Em,1)"/>
    <xsl:choose>
      <xsl:when test="$b.line-through = $off and $b.underline = $off">text-decoration:none;</xsl:when>
      <xsl:when test="$b.line-through = $on and $b.underline = $on">text-decoration:line-through underline;</xsl:when>
      <xsl:when test="$b.line-through = $on">text-decoration: line-through;</xsl:when>
      <xsl:when test="$b.underline = $on">text-decoration: underline;</xsl:when>
    </xsl:choose>

    <xsl:variable name="fSup" select="substring($prsR,$iSup,1)"/>
    <xsl:variable name="fSub" select="substring($prsR,$iSub,1)"/>
    <xsl:choose>
      <xsl:when test="$fSup = $on and $fSub = $on">vertical-align:baseline;</xsl:when>
      <xsl:when test="$fSub = $on">vertical-align:sub;</xsl:when>
      <xsl:when test="$fSup = $on">vertical-align:super;</xsl:when>
    </xsl:choose>

    <xsl:if test="not($rStyleId='CommentReference')">
      <xsl:if test="substring($prsR,$iVanishWebHidden,1) = $on">display:none;</xsl:if>
    </xsl:if>
  </xsl:template>

  <xsl:template name="RecursiveApplyRPr.class">
    <xsl:if test="w:basedOn">
      <xsl:variable name="baseStyleName" select="w:basedOn[1]/@w:val" />
      <xsl:variable name="sParaStyleBase" select="($nsStyles[@w:styleId=$baseStyleName])[1]"/>
      <xsl:for-each select="$sParaStyleBase">
        <xsl:call-template name="RecursiveApplyRPr.class" />
      </xsl:for-each>
    </xsl:if>

    <xsl:call-template name="ApplyRPr.class"/>
  </xsl:template>

  <xsl:template name="ApplyRPr.class">
    <xsl:for-each select="w:rPr[1]">

      <xsl:choose>
        <xsl:when test="w:highlight">
          background-color:<xsl:call-template name="ConvColor">
            <xsl:with-param name="value" select="w:hightlight[1]/@w:val"/>          
          </xsl:call-template>         
          <xsl:value-of select="value"/>;         
        </xsl:when>
        <xsl:otherwise>
          <xsl:for-each select="w:shd[1]">
            <xsl:call-template name="ApplyShd"/>
          </xsl:for-each>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:apply-templates select="*" mode="rpr"/>
    </xsl:for-each>
  </xsl:template>

  <xsl:template match="w:highlight" mode="rpr">
    background:<xsl:call-template name="ConvColor">
      <xsl:with-param name="value" select="@w:val"/>
    </xsl:call-template>
    <xsl:value-of select="value"/>;
  </xsl:template>

  <xsl:template match="w:color" mode="rpr">
    color:<xsl:call-template name="ConvHexColor">
      <xsl:with-param name="value" select="@w:val"/>
    </xsl:call-template>;
  </xsl:template>

  <xsl:template match="w:rFonts" mode="rpr">
    font-family:<xsl:value-of select="@w:ascii"/>;
  </xsl:template>

  <xsl:template match="w:smallCaps" mode="rpr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">font-variant:normal;</xsl:when>
      <xsl:otherwise>font-variant:small-caps;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:eastAsianLayout" mode="rpr">
    <xsl:choose>
      <xsl:when test="@w:vert = 'on'">layout-flow:horizontal;</xsl:when>
      <xsl:when test="@w:vert-compress = 'on'">layout-flow:horizontal;</xsl:when>
      <xsl:when test="@w:vert = 'off' or @w:vert-compress = 'off'">layout-flow:normal;</xsl:when>
    </xsl:choose>
    <xsl:if test="@w:combine = 'lines'">text-combine:lines;</xsl:if>
  </xsl:template>

  <xsl:template match="w:spacing" mode="rpr">
    letter-spacing:<xsl:value-of select="@w:val div 20"/>pt;
  </xsl:template>

  <xsl:template match="w:position" mode="rpr">
    <xsl:variable name="fDropCap">
      <xsl:value-of select="ancestor::w:p[1]/w:pPr/w:framePr/@w:drop-cap"/>
    </xsl:variable>
    <xsl:if test="$fDropCap=''">
      <xsl:text>position:relative;top:</xsl:text>
      <xsl:value-of select="@w:val div -2"/>
      <xsl:text>pt;</xsl:text>
    </xsl:if>
  </xsl:template>
  <xsl:template match="w:fitText" mode="rpr">
    text-fit:<xsl:value-of select="@w:val div 20"/>pt;
  </xsl:template>
  <xsl:template match="w:shadow" mode="rpr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">text-shadow:none;</xsl:when>
      <xsl:otherwise>text-shadow:0.2em 0.2em;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:caps" mode="rpr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">text-transform:none;</xsl:when>
      <xsl:otherwise>text-transform:uppercase;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:sz" mode="rpr">
    font-size:<xsl:value-of select="@w:val div 2"/>pt;
  </xsl:template>

  <xsl:template match="w:b" mode="rpr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">font-weight:normal;</xsl:when>
      <xsl:otherwise>font-weight:bold;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:i" mode="rpr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">font-style:normal;</xsl:when>
      <xsl:otherwise>font-style:italic;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="*" mode="rpr"/>

  <xsl:template name="RecursivePrsUpdateRPr">
    <xsl:param name="prsR" />
    <xsl:param name="rStyleId" />

    <xsl:variable name="myStyle" select="($nsStyles[@w:styleId=$rStyleId])[1]"/>

    <xsl:variable name="prsR.updated">
      <xsl:choose>
        <xsl:when test="$myStyle/w:basedOn">
          <xsl:call-template name="RecursivePrsUpdateRPr">
            <xsl:with-param name="prsR" select="$prsR" />
            <xsl:with-param name= "rStyleId" select="$myStyle/w:basedOn/@w:val" />
          </xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$prsR" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:call-template name="PrsUpdateRPr">
      <xsl:with-param name="ndPrContainer" select="$myStyle"/>
      <xsl:with-param name="prsR" select="$prsR.updated"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="PrsGetThemeStyle">
    <xsl:param name="prsR"/>
    <xsl:param name="runTextClass" select="$textClassAscii"/>

    <xsl:if test="not($runTextClass='')">
      <xsl:variable name="themeStyle">
        <xsl:choose>
          <xsl:when test="$runTextClass=$textClassAscii">
            <xsl:value-of select="substring($prsR,$iAsciiTheme,1)"/>
          </xsl:when>
          <xsl:when test="$runTextClass=$textClassAnsi">
            <xsl:value-of select="substring($prsR,$iAnsiTheme,1)"/>
          </xsl:when>
          <xsl:when test="$runTextClass=$textClassEA">
            <xsl:value-of select="substring($prsR,$iEATheme,1)"/>
          </xsl:when>
          <xsl:when test="$runTextClass=$textClassCS">
            <xsl:value-of select="substring($prsR,$iCSTheme,1)"/>
          </xsl:when>
        </xsl:choose>
      </xsl:variable>

      <xsl:choose>
        <xsl:when test="$themeStyle=$minorAscii">
          <xsl:value-of select="$minorAsciiTheme"/>
        </xsl:when>
        <xsl:when test="$themeStyle=$majorAscii">
          <xsl:value-of select="$majorAsciiTheme"/>
        </xsl:when>
        <xsl:when test="$themeStyle=$minorAnsi">
          <xsl:value-of select="$minorAnsiTheme"/>
        </xsl:when>
        <xsl:when test="$themeStyle=$majorAnsi">
          <xsl:value-of select="$majorAnsiTheme"/>
        </xsl:when>
        <xsl:when test="$themeStyle=$minorEA">
          <xsl:value-of select="$minorEATheme"/>
        </xsl:when>
        <xsl:when test="$themeStyle=$majorEA">
          <xsl:value-of select="$majorEATheme"/>
        </xsl:when>
        <xsl:when test="$themeStyle=$minorCS">
          <xsl:value-of select="$minorCSTheme"/>
        </xsl:when>
        <xsl:when test="$themeStyle=$majorCS">
          <xsl:value-of select="$majorCSTheme"/>
        </xsl:when>
      </xsl:choose>
    </xsl:if>
  </xsl:template>

  <xsl:template name="DisplayR">
    <xsl:param name="b.bidi"/>
    <xsl:param name="prsR"/>
    <xsl:param name="runStyleName"/>

    <xsl:variable name="rStyleId" select="string(w:rPr/w:rStyle/@w:val)"/>

    <xsl:variable name="prsR.updated">

      <xsl:variable name="prsR.updated1">
        <xsl:call-template name="RecursivePrsUpdateRPr">
          <xsl:with-param name="rStyleId" select="$rStyleId"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:variable name="prsR.updated2">
        <xsl:call-template name="PrsUpdateRPr">
          <xsl:with-param name="prsR" select="$prsR.updated1"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:variable name="prsRTemp3">
        <xsl:call-template name="PrsGetListPr">
          <xsl:with-param name="type" select="$prrUpdateRPr"/>
          <xsl:with-param name="prsR" select="$prsR.updated2"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$prsRTemp3=''">
          <xsl:value-of select="$prsR.updated2"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$prsRTemp3"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:variable name="themeStyle">
      <xsl:call-template name="PrsGetThemeStyle">
        <xsl:with-param name="prsR" select="$prsR.updated"/>
        <xsl:with-param name="runTextClass" select="string(./@textClass)"/>
      </xsl:call-template>
    </xsl:variable>

    <xsl:variable name="pr.listSuff">
      <xsl:call-template name="PrsGetListPr">
        <xsl:with-param name="type" select="$prrListSuff"/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:variable name="styleMod">
      <xsl:call-template name="ApplyRPr.class"/>

      <xsl:variable name="numId" select="w:numPr/w:numId/@w:val" />
      <xsl:variable name="ilvl" select="w:numPr/w:ilvl/@w:val" />
      <xsl:variable name="ilstDef" select="$ndLists/w:num[@w:numId=$numId]/w:abstractNumId/@w:val" />
      <xsl:variable name="abstractNum" select="$ndLists/w:abstractNum[@w:abstractNumId=$ilstDef]" />

      <xsl:variable name="isBullets">
        <xsl:for-each select="w:numPr[1]">
          <xsl:call-template name="IsListBullet" />
        </xsl:for-each>
      </xsl:variable>

      <xsl:if test="not($isBullets=$on)">
        <xsl:if test="$abstractNum/w:lvl[@w:ilvl=$ilvl]/w:rPr/w:rFonts/@w:ascii" >
          <xsl:apply-templates select="w:numPr[1]/WX:font[1]" mode="rpr"/>
        </xsl:if>

        <xsl:call-template name="PrsGetListPr">
          <xsl:with-param name="type" select="$prrApplyRPr"/>
        </xsl:call-template>
        <xsl:call-template name="ApplyRPr.once">
          <xsl:with-param name="rStyleId" select="$rStyleId"/>
          <xsl:with-param name="b.bidi" select="$b.bidi"/>
          <xsl:with-param name="prsR" select="$prsR.updated"/>
        </xsl:call-template>
      </xsl:if>

      <xsl:if test="$isBullets=$on or ancestor::w:rt">
        <xsl:text>font-style:normal;text-decoration:none;font-weight:normal;</xsl:text>
      </xsl:if>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$rStyleId='' and $styleMod=''">
        <xsl:choose>
          <xsl:when test="not($themeStyle='')">
            <span>

              <xsl:attribute name="class">
                <xsl:value-of select="$themeStyle"/>
                <xsl:if test="not($runStyleName='')">
                  <xsl:value-of select="' '"/>
                  <xsl:value-of select="$runStyleName"/>
                </xsl:if>
              </xsl:attribute>
              <xsl:call-template name="DisplayRContent"/>
            </span>

          </xsl:when>
          <xsl:otherwise>
            <xsl:call-template name="DisplayRContent"/>
          </xsl:otherwise>
        </xsl:choose>

        <xsl:if test="$pr.listSuff = $prListSuff_space">
          <xsl:text> </xsl:text>
        </xsl:if>
      </xsl:when>
      <xsl:otherwise>
        <span>

          <!-- <xsl:if test="not($rStyleId='')">-->
            <xsl:attribute name="class">
              <xsl:if test="not($themeStyle='')">
                <xsl:value-of select="$themeStyle"/>
                <xsl:value-of select="' '"/>
              </xsl:if>
              <xsl:value-of select="$rStyleId"/>
              <xsl:value-of select="$charStyleSuffix"/>
            </xsl:attribute>
          <!-- </xsl:if> -->

          <xsl:if test="not($styleMod='')">
            <xsl:attribute name="style">
              <xsl:value-of select="$styleMod"/>
            </xsl:attribute>
          </xsl:if>

          <xsl:choose>
            <xsl:when test="contains($styleMod, 'vertical-align:super') or contains($styleMod, 'vertical-align:sub')">
              <span>
                <xsl:attribute name="style">font-size:smaller;</xsl:attribute>
                <xsl:call-template name="DisplayRContent"/>
              </span>
            </xsl:when>
            <xsl:otherwise>
              <xsl:call-template name="DisplayRContent"/>
            </xsl:otherwise>
          </xsl:choose>

        </span>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:r">
    <xsl:param name="b.bidi" select="''"/>
    <xsl:param name="prsR" select="$prsRDefault"/>
    <xsl:param name="runStyleName"/>
    <!--<xsl:if test="not(w:fldChar or w:instrText)">-->
    <xsl:if test="not(w:fldChar or w:instrText)">

      <xsl:variable name="instrText" select="preceding-sibling::w:r[w:instrText][1]" />

      <xsl:variable name="nInstrText" select="normalize-space(concat($instrText, ' -'))" />
      <xsl:variable name="instruction" select="substring-before($nInstrText, ' ')" />

      <xsl:choose>
        <xsl:when test="translate($instruction, $lowercase, $uppercase)='HYPERLINK'">
          <a>
            <!--<xsl:template match="a">
              <xsl:variable name="anchor-texts">
                <xsl:value-of select="."/>
              </xsl:variable>
              <xsl:apply-templates/>
              <xsl:if test="@href!=$anchor-texts">
                <fo:inline>
                  <xsl:text>(</xsl:text>
                  <xsl:value-of select="@href"/>
                  <xsl:text>)</xsl:text>
                </fo:inline>
              </xsl:if>
            </xsl:template>-->
            
            
            <xsl:variable name="href">
            
              <xsl:choose>
                <xsl:when test="contains($nInstrText,'\l')">
                  #<xsl:value-of select="translate(substring-before(substring-after($nInstrText, '\l '),' '),'&quot;', '')"/>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:value-of select="translate(substring-before(substring-after($nInstrText, concat($instruction, ' ')),' '),'&quot;', '')"/>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:variable>

            <xsl:if test="not(href='')">
              <xsl:attribute name="href">
                <xsl:value-of select="$href"/>
              </xsl:attribute>
            </xsl:if>

            <xsl:if test="contains($nInstrText,'\t') or contains($nInstrText, '\n')">
              <xsl:attribute name="target">
                <xsl:choose>
                  <xsl:when test="contains($nInstrText, '\n')">
                    <xsl:text>_new</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="translate(substring-before(substring-after($nInstrText, '\t '),' '),'&quot;', '')"/>
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:attribute>
            </xsl:if>

            <xsl:if test="contains($nInstrText,'\o')">
              <xsl:attribute name="title">
                <xsl:value-of select="substring-before(substring-after($nInstrText, '\o &quot;'),'&quot;')"/>
              </xsl:attribute>
            </xsl:if>

            <xsl:call-template name="DisplayR">
              <xsl:with-param name="b.bidi" select="$b.bidi"/>
              <xsl:with-param name="prsR" select="$prsR"/>
              <xsl:with-param name="runStyleName" select="$runStyleName"/>
            </xsl:call-template>

          </a>
        </xsl:when>

        <xsl:otherwise>

          <xsl:call-template name="DisplayR">
            <xsl:with-param name="b.bidi" select="$b.bidi"/>
            <xsl:with-param name="prsR" select="$prsR"/>
            <xsl:with-param name="runStyleName" select="$runStyleName"/>
          </xsl:call-template>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
  </xsl:template>
  <!-- Template added to render columns by Parwati -->
  <xsl:template name="ColumnRender">
    <xsl:param name="page"/>
    <xsl:param name="colNum"/>
    <xsl:param name="style"/>
    <xsl:param name="CoverPage"/>
    <xsl:param name="Align"/>
    <xsl:text disable-output-escaping="yes">&lt;/span&gt;</xsl:text>
    <xsl:text disable-output-escaping="yes">&lt;/p&gt;</xsl:text>
    <xsl:text disable-output-escaping="yes">&lt;/td&gt;</xsl:text>
    <xsl:if test="$page = $on">
      <xsl:text disable-output-escaping="yes">&lt;/tr&gt;</xsl:text>
      <xsl:text disable-output-escaping="yes">&lt;/table&gt;</xsl:text>
      <xsl:if test="$CoverPage = $on">
        <xsl:text disable-output-escaping="yes">&lt;div style="top:1010px;left:0px;position:absolute;visibility:show;"&gt;</xsl:text>
        </xsl:if>
      <xsl:text disable-output-escaping="yes">&lt;table cellpadding="5px"&gt;</xsl:text>
      <xsl:text disable-output-escaping="yes">&lt;tr&gt;</xsl:text>  
    </xsl:if>
    <xsl:text disable-output-escaping="yes">&lt;td valign="top" width="</xsl:text>
    <xsl:value-of select="100 div $colNum"/>
    <xsl:text disable-output-escaping="yes">%"&gt;</xsl:text>    
    <xsl:text disable-output-escaping="yes">&lt;p class="</xsl:text>
    <xsl:variable name="pStyleId">
      <xsl:call-template name="GetPStyleId"/>
    </xsl:variable>
    <xsl:value-of select="$pStyleId"/>
    <xsl:value-of select="$paraStyleSuffix"/>
    <xsl:text>" style="text-align:</xsl:text>
    <xsl:choose>
      <xsl:when test="$Align = 'both'">
        <xsl:text>justify</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$Align"/>
      </xsl:otherwise>
    </xsl:choose>   
    <xsl:text>;</xsl:text>
    <xsl:value-of select="$style"/>
    <xsl:text disable-output-escaping="yes">"&gt;</xsl:text>   
    <xsl:text disable-output-escaping="yes">&lt;span&gt;</xsl:text>   
  </xsl:template>
  <!-- End to template for column rendering -->
  
  <xsl:template match="w:r[count(preceding-sibling::w:r[w:fldChar/@w:fldCharType='begin']) = count(preceding-sibling::w:r[w:fldChar/@w:fldCharType='end'])]">
    <xsl:param name="b.bidi" select="''"/>
    <xsl:param name="prsR" select="$prsRDefault"/>
    <xsl:param name="runStyleName"/> 
    
    <!-- Changed For rendering column by Parwati-->
    <!-- <xsl:if test="//w:body//w:sectPr//w:cols[@w:num] | //w:body/w:p/w:pPr/w:sectPr/w:cols[@w:num]">
      <xsl:variable name="pageEnd">
        <xsl:choose>
          <xsl:when test="w:lastRenderedPageBreak[@pageNum]">
            <xsl:value-of select="$on"/>  
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$off"/>
          </xsl:otherwise>
        </xsl:choose>        
      </xsl:variable> End of Change by Parwati -->
      <xsl:variable name="coverpage">
        <xsl:choose>
          <xsl:when test="./w:lastRenderedPageBreak/@Coverpage">
            <xsl:value-of select="$on"/>  
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$off"/>
          </xsl:otherwise>
        </xsl:choose>        
      </xsl:variable>
      <xsl:variable name="columnNo">
        <xsl:value-of select="./w:lastRenderedPageBreak/@colNum"/>
      </xsl:variable>
      <xsl:variable name="columnCount">
        <xsl:value-of select="./w:lastRenderedPageBreak/@colCount"/>
      </xsl:variable>
      <xsl:variable name="pStyleId">
        <xsl:call-template name="GetPStyleId"/>
      </xsl:variable>
      <xsl:variable name="sParaStyleName" select="($nsStyles[@w:styleId=$pStyleId])[1]"/>
      <xsl:variable name="b.bidi1">
        <xsl:choose>
          <xsl:when test="parent::w:pPr[1]/w:rPr[1]/w:rtl[1]">
            <xsl:value-of select="$on"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$off"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      
      <xsl:variable name="prsR.updated">
        <xsl:call-template name="PrsUpdateRPr">
          <xsl:with-param name="ndPrContainer" select="$sParaStyleName"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </xsl:variable>
      
      <xsl:variable name="prsP.updated1">
        <xsl:call-template name="PrsUpdatePPr">
          <xsl:with-param name="ndPrContainer" select="$sParaStyleName"/>
          <xsl:with-param name="prsP" select="$prsPDefault"/>
        </xsl:call-template>
      </xsl:variable>
      
      <xsl:variable name="prsP.updated">
        <xsl:call-template name="PrsUpdatePPr">
          <xsl:with-param name="prsP" select="$prsP.updated1"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="prsPAccum" select="''"/>
      <xsl:variable name="styleMod">
        
        <xsl:value-of select="$prsPAccum"/>
        
        <xsl:for-each select="$sParaStyleName">
          <xsl:call-template name="RecursiveApplyPPr.many"/>
        </xsl:for-each>
        
        <xsl:call-template name="ApplyPPr.many">
          <xsl:with-param name="cxtSpacing">
            <xsl:variable name="cspacing" select="$sParaStyleName/w:pPr[1]/w:contextualSpacing[1]"/>
            <xsl:if test="$cspacing and not($cspacing/@w:val = 'off')">
              <xsl:if test="following-sibling::*[1]/w:pPr[1]/w:pStyle[1]/@w:val = $pStyleId">
                <xsl:value-of select="$cxtSpacing_top"/>
              </xsl:if>
              <xsl:if test="preceding-sibling::*[1]/w:pPr[1]/w:pStyle[1]/@w:val = $pStyleId">
                <xsl:value-of select="$cxtSpacing_bottom"/>
              </xsl:if>
            </xsl:if>
          </xsl:with-param>
        </xsl:call-template>
        
        <xsl:call-template name="ApplyPPr.class"/>
        <xsl:variable name="bdrBetween" select="''"/>
        <xsl:call-template name="ApplyPPr.once">
          <xsl:with-param name="b.bidi" select="$b.bidi1"/>
          <xsl:with-param name="prsP" select="$prsP.updated"/>
          <xsl:with-param name="i.bdrRange.this" select="position()"/>
          <xsl:with-param name="i.bdrRange.last" select="last()"/>
          <xsl:with-param name="pr.bdrBetween" select="$bdrBetween"/>
        </xsl:call-template>
      </xsl:variable>
    <xsl:variable name="alignment">
      <xsl:if test="parent::*[1]/w:pPr/w:jc">
        <xsl:value-of select="parent::*[1]/w:pPr/w:jc/@w:val"/>
      </xsl:if>
    </xsl:variable>
      <!-- Added for column rendering by Parwati -->
   <!-- <xsl:if test="//w:body//w:sectPr//w:cols[@w:num] | //w:body/w:p/w:pPr/w:sectPr/w:cols[@w:num] | //w:body//w:sdt//w:sdtContent">
      <xsl:if test="descendant::*[name()='w:lastRenderedPageBreak'][1]/@colNum">
        
        <xsl:call-template name="ColumnRender">
          <xsl:with-param name="page" select="$pageEnd"></xsl:with-param>
          <xsl:with-param name="colNum" select="$columnNo"></xsl:with-param>
          <xsl:with-param name="style" select="$styleMod"></xsl:with-param>
          <xsl:with-param name="CoverPage" select="$coverpage"></xsl:with-param>
          <xsl:with-param name="Align" select="$alignment"></xsl:with-param>
        </xsl:call-template>
      </xsl:if>
      </xsl:if>
    </xsl:if> -->
    <!-- End of change by Parwati -->
    <xsl:call-template name="DisplayR">
      <xsl:with-param name="b.bidi" select="$b.bidi"/>
      <xsl:with-param name="prsR" select="$prsR"/>
      <xsl:with-param name="runStyleName" select="$runStyleName"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="ColumnAddition"> <!-- This is template is added by Parwati to handle columns -->
    <xsl:param name="totalColumns"></xsl:param>
    <xsl:text disable-output-escaping="yes">&lt;/td&gt;</xsl:text>
    <xsl:text disable-output-escaping="yes">&lt;td colspan="</xsl:text>
    <xsl:value-of select="$totalColumns"/>
    <xsl:text disable-output-escaping="yes">"&gt;</xsl:text>
  </xsl:template>
 
  <xsl:template match="w:pPr">
    <xsl:param name="b.bidi" select="''"/>
    <xsl:param name="prsR" select="$prsRDefault"/>
    <xsl:call-template name="DisplayR">
      <xsl:with-param name="b.bidi" select="$b.bidi"/>
      <xsl:with-param name="prsR" select="$prsR"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="DisplayHlink">
    <xsl:param name="b.bidi"/>
    <xsl:param name="prsR"/>
    <a style="text-decoration:none;">
    <xsl:variable name="href">
        <xsl:for-each select="@w:dest">
          <xsl:value-of select="."/>
        </xsl:for-each>
        <xsl:choose>
          <xsl:when test="@w:anchor">#<xsl:value-of select="@w:anchor"/>
          </xsl:when>
          <xsl:when test="@w:bookmark">#<xsl:value-of select="@w:bookmark"/>
          </xsl:when>
          <xsl:when test="@w:arbLocation"># <xsl:value-of select="@w:arbLocation"/>
          </xsl:when>
        </xsl:choose>
      </xsl:variable>
      <xsl:if test="not(href='')">
        <xsl:attribute name="href">
          <xsl:value-of select="$href"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:for-each select="@w:tgtFrame">
        <xsl:attribute name="target">
          <xsl:value-of select="."/>
        </xsl:attribute>
      </xsl:for-each>
      <xsl:for-each select="@w:tooltip">
        <xsl:attribute name="title">
          <xsl:value-of select="."/>
        </xsl:attribute>
      </xsl:for-each>
      <xsl:call-template name="DisplayPContent">
        <xsl:with-param name="b.bidi" select="$b.bidi"/>
        <xsl:with-param name="prsR" select="$prsR"/>
      </xsl:call-template>
    </a>
  </xsl:template>

  <xsl:template match="w:hlink | w:hyperlink">
    <xsl:param name="b.bidi" select="''"/>
    <xsl:param name="prsR" select="$prsRDefault"/>
    <xsl:call-template name="DisplayHlink">
      <xsl:with-param name="b.bidi" select="$b.bidi"/>
      <xsl:with-param name="prsR" select="$prsR"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="ApplyPPr.once">
    <xsl:param name="i.bdrRange.this"/>
    <xsl:param name="i.bdrRange.last"/>
    <xsl:param name="pr.bdrBetween"/>
    <xsl:param name="prsP"/>
    <xsl:param name="b.bidi"/>

    <xsl:if test="not($i.bdrRange.this = $i.bdrRange.last)">
      <xsl:call-template name="ApplyBorderPr">
        <xsl:with-param name="pr.bdr" select="$pr.bdrBetween"/>
        <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
      </xsl:call-template>
    </xsl:if>

    <xsl:if test="not($pr.bdrBetween = '')">
      <xsl:choose>
        <xsl:when test="$i.bdrRange.this = 1">padding:0 0 1pt;</xsl:when>
        <xsl:when test="$i.bdrRange.this = i.bdrRange.last">padding:1pt 0 0;</xsl:when>
        <xsl:otherwise>padding:1pt 0 1pt;</xsl:otherwise>
      </xsl:choose>
    </xsl:if>

    <xsl:choose>
      <xsl:when test="$b.bidi = $off">direction:ltr;unicode-bidi:normal;</xsl:when>
      <xsl:when test="$b.bidi = $on">direction:rtl;unicode-bidi:embed;text-align:right;</xsl:when>
    </xsl:choose>

    <xsl:variable name="nInd" select="substring($prsP,$iInd)"/>
    <xsl:variable name="pr.listInd">
      <xsl:for-each select="w:pPr">
        <xsl:call-template name="PrsGetListPr">
          <xsl:with-param name="type" select="$prrListInd"/>
        </xsl:call-template>
      </xsl:for-each>
    </xsl:variable>
    <xsl:if test="not($nInd='' and $pr.listInd='')">

      <xsl:variable name="nInd.left" select="substring-before($nInd,$sep2)"/>
      <xsl:variable name="temp1" select="substring-after($nInd,$sep2)"/>
      <xsl:variable name="nInd.leftChars" select="substring-before($temp1,$sep2)"/>
      <xsl:variable name="temp2" select="substring-after($temp1,$sep2)"/>
      <xsl:variable name="nInd.right" select="substring-before($temp2,$sep2)"/>
      <xsl:variable name="temp3" select="substring-after($temp2,$sep2)"/>
      <xsl:variable name="nInd.rightChars" select="substring-before($temp3,$sep2)"/>
      <xsl:variable name="temp4" select="substring-after($temp3,$sep2)"/>
      <xsl:variable name="nInd.hanging" select="substring-before($temp4,$sep2)"/>
      <xsl:variable name="temp5" select="substring-after($temp4,$sep2)"/>
      <xsl:variable name="nInd.hangingChars" select="substring-before($temp5,$sep2)"/>
      <xsl:variable name="temp6" select="substring-after($temp5,$sep2)"/>
      <xsl:variable name="nInd.firstLine" select="substring-before($temp6,$sep2)"/>
      <xsl:variable name="nInd.firstLineChars" select="substring-after($temp6,$sep2)"/>
      <xsl:variable name="pr.listInd.left" select="substring-before($pr.listInd,$sep2)"/>
      <xsl:variable name="temp1a" select="substring-after($pr.listInd,$sep2)"/>
      <xsl:variable name="pr.listInd.leftChars" select="substring-before($temp1a,$sep2)"/>
      <xsl:variable name="temp2a" select="substring-after($temp1a,$sep2)"/>
      <xsl:variable name="pr.listInd.hanging" select="substring-before($temp2a,$sep2)"/>
      <xsl:variable name="pr.listInd.hangingChars" select="substring-after($temp2a,$sep2)"/>

      <xsl:variable name="marginSide.before">
        margin-<xsl:choose>
          <xsl:when test="$b.bidi=$on">right</xsl:when>
          <xsl:otherwise>left</xsl:otherwise>
        </xsl:choose>:
      </xsl:variable>
      <xsl:variable name="marginSide.after">
        margin-<xsl:choose>
          <xsl:when test="$b.bidi=$on">left</xsl:when>
          <xsl:otherwise>right</xsl:otherwise>
        </xsl:choose>:
      </xsl:variable>

      <xsl:choose>

        <xsl:when test="not($pr.listInd.left = '')">
          <xsl:value-of select="$marginSide.before"/><xsl:value-of select="$pr.listInd.left div 20"/>pt;
        </xsl:when>
        <xsl:when test="not($pr.listInd.leftChars = '' and $pr.listInd.hangingChars='')">
          <xsl:value-of select="$marginSide.before"/>
          <xsl:variable name="leftchars">
            <xsl:choose>
              <xsl:when test="$pr.listInd.leftChars=''">0</xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$pr.listInd.leftChars div 100 * 12"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:variable name="hangingchars">
            <xsl:choose>
              <xsl:when test="$pr.listInd.hangingChars=''">0</xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$pr.listInd.hangingChars div 100 * 12"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:value-of select="$leftchars + $hangingchars"/>
          <xsl:text>pt;</xsl:text>
        </xsl:when>

        <xsl:when test="not($nInd.left = '')"> <!-- Modified by Parwati to handle negative indentation -->
          <xsl:value-of select="$marginSide.before"/>
          <xsl:choose>
            <xsl:when test="$nInd.left &lt; 0">
                0pt;
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="$nInd.left div 20"/>pt;
            </xsl:otherwise>
          </xsl:choose>          
        </xsl:when>
        <xsl:when test="not($nInd.leftChars = '' and $nInd.hangingChars='')">
          <xsl:value-of select="$marginSide.before"/>
          <xsl:variable name="leftchars">
            <xsl:choose>
              <xsl:when test="$nInd.leftChars=''">0</xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$nInd.leftChars div 100"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:variable name="hangingchars">
            <xsl:choose>
              <xsl:when test="$nInd.hangingChars=''">0</xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$nInd.hangingChars div 100"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:value-of select="$leftchars + $hangingchars"/>
          <xsl:text>em;</xsl:text>
        </xsl:when>

      </xsl:choose>

      <xsl:choose>
        <xsl:when test="not($nInd.right = '')">
          <xsl:value-of select="$marginSide.after"/><xsl:value-of select="$nInd.right div 20"/>pt;
        </xsl:when>
        <xsl:when test="not($nInd.rightChars = '')">
          <xsl:value-of select="$marginSide.after"/><xsl:value-of select="$nInd.rightChars div 100"/>em;
        </xsl:when>
      </xsl:choose>
     <!-- TEXT INDENTATION FOR TABLE MODIFIED BY PRASAHANTH-->
      <xsl:choose>
        <xsl:when test="not($nInd.hanging='')">
          <xsl:if test="not(../../w:tc)">
            text-indent:<xsl:value-of select="$nInd.hanging div 20"/>pt; <!-- - sign before 20 removed by Parwati -->
          </xsl:if>
        </xsl:when>
        <!-- TEXT INDENTATION FOR TABLE MODIFIED BY PRASAHANTH-->
        
        <xsl:when test="not($nInd.hangingChars='')">
          text-indent:<xsl:value-of select="$nInd.hangingChars div -100"/>em;
        </xsl:when>
        <xsl:when test="not($nInd.firstLine='')">
          text-indent:<xsl:value-of select="$nInd.firstLine div 20"/>pt;
        </xsl:when>
        <xsl:when test="not($nInd.firstLineChars='')">
          text-indent:<xsl:value-of select="$nInd.firstLineChars div 100"/>em;
        </xsl:when>
        <xsl:when test="not($pr.listInd.hanging='')">
          text-indent:<xsl:value-of select="$pr.listInd.hanging div -20"/>pt;
        </xsl:when>
        <xsl:when test="not($pr.listInd.hangingChars='')">
          text-indent:<xsl:value-of select="$pr.listInd.hangingChars div -100 * 12"/>pt;
        </xsl:when>
      </xsl:choose>
    </xsl:if>

    <xsl:variable name="fTextAutospaceO" select="substring($prsP,$iTextAutospaceO,1)"/>
    <xsl:variable name="fTextAutospaceN" select="substring($prsP,$iTextAutospaceN,1)"/>
    <xsl:choose>
      <xsl:when test="not($fTextAutospaceN = $off) and $fTextAutospaceO = $off">text-autospace:ideograph-numeric;</xsl:when>
      <xsl:when test="not($fTextAutospaceO = $off) and $fTextAutospaceN = $off">text-autospace:ideograph-other;</xsl:when>
      <xsl:when test="$fTextAutospaceO = $off and $fTextAutospaceN = $off">text-autospace:none;</xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="ApplyPPr.many">
    <xsl:param name="cxtSpacing" select="$cxtSpacing_all"/>

    <xsl:variable name="spacing" select="w:pPr[1]/w:spacing[1]"/>
    <xsl:choose>
      <xsl:when test="($spacing/@w:before-autospacing and not($spacing/@w:before-autospacing = 'off')) or $cxtSpacing = $cxtSpacing_none or $cxtSpacing = $cxtSpacing_bottom">

      </xsl:when>
      <xsl:when test="$spacing/@w:before">
        margin-top:<xsl:value-of select="$spacing/@w:before div 20"/>pt;
      </xsl:when>
      <xsl:when test="$spacing/@w:before-lines">
        margin-top:<xsl:value-of select="$spacing/@w:before-lines *.12"/>pt;
      </xsl:when>
    </xsl:choose>
    <xsl:choose>
      <xsl:when test="($spacing/@w:after-autospacing and not($spacing/@w:after-autospacing = 'off')) or $cxtSpacing = $cxtSpacing_none or $cxtSpacing = $cxtSpacing_top">

      </xsl:when>

      <xsl:when test="$spacing/@w:after">
        margin-bottom:<xsl:value-of select="$spacing/@w:after div 20"/>pt;
      </xsl:when>
      <xsl:when test="$spacing/@w:after-lines">
        margin-bottom:<xsl:value-of select="$spacing/@w:after-lines *.12"/>pt;
      </xsl:when>
    </xsl:choose>
    <xsl:for-each select="w:pPr[1]">

      <xsl:for-each select="w:snapToGrid[1]">
        <xsl:choose>
          <xsl:when test="@w:val = 'off'">layout-grid-mode:char;</xsl:when>
          <xsl:otherwise>layout-grid-mode:both;</xsl:otherwise>
        </xsl:choose>
      </xsl:for-each>

      <xsl:for-each select="w:keepNext[1]">
        <xsl:choose>
          <xsl:when test="@w:val = 'off'">page-break-after:auto;</xsl:when>
          <xsl:otherwise>page-break-after:avoid;</xsl:otherwise>
        </xsl:choose>
      </xsl:for-each>

      <xsl:for-each select="w:pageBreakBefore[1]">
        <xsl:choose>
          <xsl:when test="@w:val = 'off'">page-break-before:auto;</xsl:when>
          <xsl:otherwise>page-break-before:always;</xsl:otherwise>
        </xsl:choose>
      </xsl:for-each>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="RecursiveApplyPPr.class">
    <xsl:if test="w:basedOn">
      <xsl:variable name="baseStyleName" select="w:basedOn[1]/@w:val" />
      <xsl:variable name="sParaStyleBase" select="($nsStyles[@w:styleId=$baseStyleName])[1]"/>
      <xsl:for-each select="$sParaStyleBase">
        <xsl:call-template name="RecursiveApplyPPr.class" />
      </xsl:for-each>
    </xsl:if>

    <xsl:call-template name="ApplyPPr.class"/>
  </xsl:template>

  <xsl:template name="ApplyPPr.class">
    <xsl:apply-templates select="w:pPr[1]/*" mode="ppr"/>
  </xsl:template>

  <xsl:template match="w:shd" mode="ppr">
    <xsl:call-template name="ApplyShd"/>
  </xsl:template>

  <xsl:template match="WX:shd" mode="ppr">
    <xsl:call-template name="ApplyShdHint"/>
  </xsl:template>

  <xsl:template match="w:textDirection" mode="ppr">
    <xsl:call-template name="ApplyTextDirection"/>
  </xsl:template>

  <!-- ID 1 Fix linespacing issue - div from 20 to 10-->
  
  <xsl:template match="w:spacing[@w:lineRule or @w:line]" mode="ppr">
    <xsl:choose>
      <xsl:when test="not(@w:lineRule) or @w:lineRule = 'exact'or @w:lineRule = 'auto'">
        line-height:<xsl:value-of select="@w:line div 10"/>pt;
      </xsl:when>
    </xsl:choose>
  </xsl:template> 

  <xsl:template match="w:topLinePunct" mode="ppr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">punctuation-trim:none;</xsl:when>
      <xsl:otherwise>punctuation-trim:leading;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:overflowPunct" mode="ppr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">punctuation-wrap:simple;</xsl:when>
      <xsl:otherwise>punctuation-wrap:hanging;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:jc" mode="ppr">
    <xsl:choose>
      <xsl:when test="@w:val = 'left'">text-align:left;</xsl:when>
      <xsl:when test="@w:val = 'center'">text-align:center;</xsl:when>
      <xsl:when test="@w:val = 'right'">text-align:right;</xsl:when>
      <xsl:when test="@w:val = 'both'">text-align:justify;text-justify:inter-ideograph;</xsl:when>
      <xsl:when test="@w:val = 'distribute'">text-align:justify;text-justify:distribute-all-lines;</xsl:when>
      <xsl:when test="@w:val = 'low-kashida'">text-align:justify;text-justify:kashida;text-kashida:0%;</xsl:when>
      <xsl:when test="@w:val = 'medium-kashida'">text-align:justify;text-justify:kashida;text-kashida:10%;</xsl:when>
      <xsl:when test="@w:val = 'high-kashida'">text-align:justify;text-justify:kashida;text-kashida:20%;</xsl:when>
      <xsl:when test="@w:val = 'thai-distribute'">text-align:justify;text-justify:inter-cluster;</xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:textAlignment" mode="ppr">
    <xsl:choose>
      <xsl:when test="@w:val = 'top'">vertical-align:top;</xsl:when>
      <xsl:when test="@w:val = 'center'">vertical-align:middle;</xsl:when>
      <xsl:when test="@w:val = 'baseline'">vertical-align:baseline;</xsl:when>
      <xsl:when test="@w:val = 'bottom'">vertical-align:bottom;</xsl:when>
      <xsl:when test="@w:val = 'auto'">vertical-align:baseline;</xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:wordWrap" mode="ppr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">word-break:break-all;</xsl:when>
      <xsl:otherwise>word-break:normal;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="*" mode="ppr"/>

  <xsl:template name="DisplayPContent">
    <xsl:param name="b.bidi"/>
    <xsl:param name="prsR"/>
    <xsl:param name="runStyleName"/>
    <xsl:call-template name="DisplayRBorder">
      <xsl:with-param name="b.bidi" select="$b.bidi"/>
      <xsl:with-param name="prsR" select="$prsR"/>
      <xsl:with-param name="runStyleName" select="$runStyleName"/>
    </xsl:call-template>

    <xsl:if test="count(*[not(name()='w:pPr')])=0">
      <xsl:text disable-output-escaping="yes">&#160;</xsl:text>
    </xsl:if>
  </xsl:template>

  <xsl:template name="GetPStyleId">
    <xsl:choose>
      <xsl:when test="w:pPr/w:pStyle/@w:val">
        <xsl:value-of select="w:pPr/w:pStyle/@w:val"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$paraStyleID_Default"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="RecursiveApplyPPr.many">
    <xsl:if test="w:basedOn">
      <xsl:variable name="baseStyleName" select="w:basedOn[1]/@w:val" />
      <xsl:variable name="sParaStyleBase" select="($nsStyles[@w:styleId=$baseStyleName])[1]"/>
      <xsl:for-each select="$sParaStyleBase">
        <xsl:call-template name="RecursiveApplyPPr.many" />
      </xsl:for-each>
    </xsl:if>

    <xsl:call-template name="ApplyPPr.many"/>

  </xsl:template>

  <xsl:template match="w:p">
    <xsl:param name="bdrBetween" select="''"/>
    <xsl:param name="prsPAccum" select="''"/>
    <xsl:param name="prsP" select="$prsPDefault"/>
    <xsl:param name="prsR" select="$prsRDefault"/>

    <xsl:if test="not(w:pPr/w:pStyle/@w:val='z-TopofForm') and not(w:pPr/w:pStyle/@w:val='z-BottomofForm')">
      <p>

        <xsl:variable name="pStyleId">
          <xsl:call-template name="GetPStyleId"/>
        </xsl:variable>
        <xsl:attribute name="class">
          <xsl:value-of select="$pStyleId"/>
          <xsl:value-of select="$paraStyleSuffix"/>
        </xsl:attribute>
        <xsl:variable name="sParaStyleName" select="($nsStyles[@w:styleId=$pStyleId])[1]"/>
        <xsl:variable name="b.bidi">
          <xsl:choose>
            <xsl:when test="w:pPr[1]/w:rPr[1]/w:rtl[1]">
              <xsl:value-of select="$on"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="$off"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>

        <xsl:variable name="prsR.updated">
          <xsl:call-template name="PrsUpdateRPr">
            <xsl:with-param name="ndPrContainer" select="$sParaStyleName"/>
            <xsl:with-param name="prsR" select="$prsR"/>
          </xsl:call-template>
        </xsl:variable>

        <xsl:variable name="prsP.updated1">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="ndPrContainer" select="$sParaStyleName"/>
            <xsl:with-param name="prsP" select="$prsP"/>
          </xsl:call-template>
        </xsl:variable>

        <xsl:variable name="prsP.updated">
          <xsl:call-template name="PrsUpdatePPr">
            <xsl:with-param name="prsP" select="$prsP.updated1"/>
          </xsl:call-template>
        </xsl:variable>

        <xsl:variable name="styleMod">

          <xsl:value-of select="$prsPAccum"/>

          <xsl:for-each select="$sParaStyleName">
            <xsl:call-template name="RecursiveApplyPPr.many"/>
          </xsl:for-each>

          <xsl:call-template name="ApplyPPr.many">
            <xsl:with-param name="cxtSpacing">
              <xsl:variable name="cspacing" select="$sParaStyleName/w:pPr[1]/w:contextualSpacing[1]"/>
              <xsl:if test="$cspacing and not($cspacing/@w:val = 'off')">
                <xsl:if test="following-sibling::*[1]/w:pPr[1]/w:pStyle[1]/@w:val = $pStyleId">
                  <xsl:value-of select="$cxtSpacing_top"/>
                </xsl:if>
                <xsl:if test="preceding-sibling::*[1]/w:pPr[1]/w:pStyle[1]/@w:val = $pStyleId">
                  <xsl:value-of select="$cxtSpacing_bottom"/>
                </xsl:if>
              </xsl:if>
            </xsl:with-param>
          </xsl:call-template>

          <xsl:call-template name="ApplyPPr.class"/>

          <xsl:call-template name="ApplyPPr.once">
            <xsl:with-param name="b.bidi" select="$b.bidi"/>
            <xsl:with-param name="prsP" select="$prsP.updated"/>
            <xsl:with-param name="i.bdrRange.this" select="position()"/>
            <xsl:with-param name="i.bdrRange.last" select="last()"/>
            <xsl:with-param name="pr.bdrBetween" select="$bdrBetween"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:if test="not($styleMod='')">
          <xsl:attribute name="style">
            <xsl:value-of select="$styleMod"/>
          </xsl:attribute>
        </xsl:if>

        <span>
          <xsl:attribute name="class">
            <xsl:value-of select="$pStyleId"/>
            <xsl:value-of select="$charStyleSuffix"/>
          </xsl:attribute>
          <xsl:call-template name="DisplayPContent">
            <xsl:with-param name="b.bidi" select="$b.bidi"/>
            <xsl:with-param name="prsR" select="$prsR.updated"/>
            <xsl:with-param name="runStyleName">
              <xsl:value-of select="$pStyleId"/>
              <xsl:value-of select="$charStyleSuffix"/>
            </xsl:with-param>
          </xsl:call-template>
        </span>
      </p>
<!--     <xsl:if test="./w:r/w:lastRenderedPageBreak/@ColumnEnd">  Added by Parwati to handle columns
        <xsl:variable name="columnNo">
          <xsl:value-of select="./w:r/w:lastRenderedPageBreak/@colNum"/>
        </xsl:variable>      
        <xsl:call-template name="ColumnAddition">          
          <xsl:with-param name="totalColumns" select="$columnNo"></xsl:with-param>
        </xsl:call-template> 
      </xsl:if> End -->
<!--By Sunil svg shapes positioning-->
      <xsl:if test="./w:r/div/svg">
<!--<br/>
        <br/>-->
      </xsl:if>
      </xsl:if>
  </xsl:template>
  
  
  <xsl:template match="w:sdt/w:sdtContent/w:tc/w:p | w:sdt/w:sdtContent/w:p">    
    <p>
      <xsl:attribute name="class">
        <xsl:value-of select="./w:pPr/w:pStyle/@w:val"/>
        <xsl:value-of select="$charStyleSuffix"/>
      </xsl:attribute>
      <xsl:attribute name="style">
        text-align:<xsl:value-of select="./w:pPr/w:jc/@w:val"/>
      </xsl:attribute>
      <span>
      <xsl:apply-templates/>
      </span>
    </p>
  </xsl:template>

  <xsl:template name="DisplayBodyContent">

    <xsl:param name="ns.content" select="descendant::*[(parent::WX:sect or parent::WX:sub-section) and not(name()='WX:sub-section')]"/>
    <xsl:param name="prsPAccum" select="''"/>
    <xsl:param name="prsP" select="$prsPDefault"/>
    <xsl:param name="prsR" select="$prsRDefault"/>
    <xsl:apply-templates>
      <xsl:with-param name="ns.content" select="$ns.content"/>
      <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
      <xsl:with-param name="prsP" select="$prsP"/>
      <xsl:with-param name="prsR" select="$prsR"/>
    </xsl:apply-templates>

    <xsl:if test="count($ns.content)=0">
      <xsl:text disable-output-escaping="yes">&#160;</xsl:text>
    </xsl:if>
  </xsl:template>

  <xsl:template name="RecursiveApplyTcPr.class">
    <xsl:if test="w:basedOn">
      <xsl:variable name="baseStyleName" select="w:basedOn[1]/@w:val" />
      <xsl:variable name="sTblStyleBase" select="($nsStyles[@w:styleId=$baseStyleName])[1]"/>
      <xsl:for-each select="$sTblStyleBase">
        <xsl:call-template name="RecursiveApplyTcPr.class" />
      </xsl:for-each>
    </xsl:if>

    <xsl:call-template name="ApplyTcPr.class"/>
  </xsl:template>

  <xsl:template name="ApplyTcPr.class">
    <xsl:apply-templates select="w:tcPr[1]/*" mode="tcpr"/>
  </xsl:template>

  <xsl:template match="w:shd" mode="tcpr">
    <xsl:call-template name="ApplyShd"/>
  </xsl:template>

  <xsl:template match="w:textDirection" mode="tcpr">
    <xsl:call-template name="ApplyTextDirection"/>
  </xsl:template>

  <xsl:template match="w:tcFitText" mode="tcpr">
    <xsl:if test="not(@w:val = 'off')">text-fit:100%;</xsl:if>
  </xsl:template>

  <xsl:template match="w:vAlign" mode="tcpr">
    <xsl:choose>
      <xsl:when test="@w:val = 'center'">vertical-align:middle;</xsl:when>
      <xsl:when test="@w:val = 'bottom'">vertical-align:bottom;</xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:noWrap" mode="tcpr">
    <xsl:choose>
      <xsl:when test="@w:val = 'off'">white-space:normal;</xsl:when>
      <xsl:otherwise>white-space:nowrap;</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:tcW" mode="tcpr">
    width:<xsl:call-template name="EvalTableWidth"/>;
  </xsl:template>
  <xsl:template match="*" mode="tcpr"/>

  <xsl:template name="ApplyExtraCornerBorders">
    <xsl:param name="cnfType" />
    <xsl:param name="sTblStyleName" />
    <xsl:choose>
      <xsl:when test="$cnfType=$cnfNWCell">
        <xsl:call-template name="ApplyExtraCornerBordersNW">
          <xsl:with-param name="sTblStyle" select="$sTblStyleName" />
        </xsl:call-template>
      </xsl:when>
      <xsl:when test="$cnfType=$cnfNECell">
        <xsl:call-template name="ApplyExtraCornerBordersNE">
          <xsl:with-param name="sTblStyle" select="$sTblStyleName" />
        </xsl:call-template>
      </xsl:when>
      <xsl:when test="$cnfType=$cnfSECell">
        <xsl:call-template name="ApplyExtraCornerBordersSE">
          <xsl:with-param name="sTblStyle" select="$sTblStyleName" />
        </xsl:call-template>
      </xsl:when>
      <xsl:when test="$cnfType=$cnfSWCell">
        <xsl:call-template name="ApplyExtraCornerBordersSW">
          <xsl:with-param name="sTblStyle" select="$sTblStyleName" />
        </xsl:call-template>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="ApplyExtraCornerBordersNW">
    <xsl:param name="sTblStyle" />

    <xsl:variable name="firstColBorders" select="$sTblStyle/w:tblStylePr[@w:type=$cnfFirstCol][1]/w:tcPr[1]/w:tcBorders[1]" />
    <xsl:variable name="firstRowBorders" select="$sTblStyle/w:tblStylePr[@w:type=$cnfFirstRow][1]/w:tcPr[1]/w:tcBorders[1]" />

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstRowBorders/w:top[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstColBorders/w:top[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstRowBorders/w:left[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstColBorders/w:left[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstRowBorders/w:right[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstColBorders/w:right[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstRowBorders/w:bottom[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstColBorders/w:bottom[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="ApplyExtraCornerBordersNE">
    <xsl:param name="sTblStyle" />

    <xsl:variable name="lastColBorders"  select="$sTblStyle/w:tblStylePr[@w:type=$cnfLastCol][1]/w:tcPr[1]/w:tcBorders[1]" />
    <xsl:variable name="firstRowBorders" select="$sTblStyle/w:tblStylePr[@w:type=$cnfFirstRow][1]/w:tcPr[1]/w:tcBorders[1]" />

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstRowBorders/w:top[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastColBorders/w:top[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstRowBorders/w:left[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastColBorders/w:left[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstRowBorders/w:right[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastColBorders/w:right[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstRowBorders/w:bottom[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastColBorders/w:bottom[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="ApplyExtraCornerBordersSE">
    <xsl:param name="sTblStyle" />

    <xsl:variable name="lastColBorders"  select="$sTblStyle/w:tblStylePr[@w:type=$cnfLastCol][1]/w:tcPr[1]/w:tcBorders[1]" />
    <xsl:variable name="lastRowBorders" select="$sTblStyle/w:tblStylePr[@w:type=$cnfLastRow][1]/w:tcPr[1]/w:tcBorders[1]" />

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastRowBorders/w:top[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastColBorders/w:top[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastRowBorders/w:left[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastColBorders/w:left[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastRowBorders/w:right[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastColBorders/w:right[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastColBorders/w:bottom[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastRowBorders/w:bottom[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>

  </xsl:template>

  <xsl:template name="ApplyExtraCornerBordersSW">
    <xsl:param name="sTblStyle" />

    <xsl:variable name="firstColBorders"  select="$sTblStyle/w:tblStylePr[@w:type=$cnfFirstCol][1]/w:tcPr[1]/w:tcBorders[1]" />
    <xsl:variable name="lastRowBorders" select="$sTblStyle/w:tblStylePr[@w:type=$cnfLastRow][1]/w:tcPr[1]/w:tcBorders[1]" />

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastRowBorders/w:top[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstColBorders/w:top[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastRowBorders/w:left[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstColBorders/w:left[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastRowBorders/w:right[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstColBorders/w:right[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$lastRowBorders/w:bottom[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr">
        <xsl:for-each select="$firstColBorders/w:bottom[1]">
          <xsl:call-template name="GetBorderPr" />
        </xsl:for-each>
      </xsl:with-param>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="ApplyTcBordersFromCnf">
    <xsl:param name="tcBorders" />
    <xsl:param name="sTblStyleName" />
    <xsl:param name="cnfType" />
    <xsl:param name="thisRow"/>
    <xsl:param name="lastRow"/>
    <xsl:param name="bdr.top"/>
    <xsl:param name="bdr.left"/>
    <xsl:param name="bdr.bottom"/>
    <xsl:param name="bdr.right"/>
    <xsl:param name="bdrSide_right.bidi" />
    <xsl:param name="bdrSide_left.bidi" />

    <xsl:variable name="thisBdr.top">
      <xsl:choose>
        <xsl:when test="$tcBorders/w:top">
          <xsl:for-each select="$tcBorders/w:top[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="not($cnfType='')">
          <xsl:choose>
            <xsl:when test="$cnfType=$cnfBand1Vert or $cnfType=$cnfBand2Vert or $cnfType=$cnfFirstCol or $cnfType=$cnfLastCol">
              <xsl:variable name="p.cnfFirstRow" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfFirstRow][1]"/>
              <xsl:choose>
                <xsl:when test="$p.cnfFirstRow and $thisRow=2">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:top[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:when test="not($p.cnfFirstRow) and $thisRow=1">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:top[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:insideH[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
              <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:top[1]">
                <xsl:call-template name="GetBorderPr"/>
              </xsl:for-each>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$bdr.top"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="thisBdr.bottom">
      <xsl:choose>
        <xsl:when test="$tcBorders/w:bottom">
          <xsl:for-each select="$tcBorders/w:bottom[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="not($cnfType='')">
          <xsl:choose>
            <xsl:when test="$cnfType=$cnfBand1Vert or $cnfType=$cnfBand2Vert or $cnfType=$cnfFirstCol or $cnfType=$cnfLastCol">
              <xsl:variable name="p.cnfLastRow" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfLastRow][1]"/>
              <xsl:choose>
                <xsl:when test="$p.cnfLastRow and $thisRow=$lastRow - 1">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:bottom[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:when test="not($p.cnfLastRow) and $thisRow=$lastRow">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:bottom[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:insideH[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
              <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:bottom[1]">
                <xsl:call-template name="GetBorderPr"/>
              </xsl:for-each>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$bdr.bottom"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="thisBdr.left">
      <xsl:choose>
        <xsl:when test="$tcBorders/w:left">
          <xsl:for-each select="$tcBorders/w:left[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="not($cnfType='')">
          <xsl:choose>
            <xsl:when test="$cnfType=$cnfBand1Horz or $cnfType=$cnfBand2Horz">
              <xsl:variable name="p.cnfFirstCol" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfFirstCol][1]"/>
              <xsl:choose>
                <xsl:when test="$p.cnfFirstCol and position()=2">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:left[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:when test="not($p.cnfFirstCol) and position()=1">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:left[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:insideV[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:when>
            <xsl:when test="$cnfType=$cnfFirstRow or $cnfType=$cnfLastRow">
              <xsl:choose>
                <xsl:when test="position()=1">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:left[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:insideV[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
              <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:left[1]">
                <xsl:call-template name="GetBorderPr"/>
              </xsl:for-each>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$bdr.left"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="thisBdr.right">
      <xsl:choose>
        <xsl:when test="$tcBorders/w:right">
          <xsl:for-each select="$tcBorders/w:right[1]">
            <xsl:call-template name="GetBorderPr"/>
          </xsl:for-each>
        </xsl:when>
        <xsl:when test="not($cnfType='')">
          <xsl:choose>
            <xsl:when test="$cnfType=$cnfBand1Horz or $cnfType=$cnfBand2Horz">
              <xsl:variable name="p.cnfLastCol" select="$sTblStyleName/w:tblStylePr[@w:type=$cnfLastCol][1]"/>
              <xsl:choose>
                <xsl:when test="$p.cnfLastCol and position()=last() - 1">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:right[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:when test="not($p.cnfLastCol) and position()=last()">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:right[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:insideV[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:when>
            <xsl:when test="$cnfType=$cnfFirstRow or $cnfType=$cnfLastRow">
              <xsl:choose>
                <xsl:when test="position()=last()">
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:right[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:insideV[1]">
                    <xsl:call-template name="GetBorderPr"/>
                  </xsl:for-each>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
              <xsl:for-each select="$sTblStyleName/w:tblStylePr[@w:type=$cnfType][1]/w:tcPr[1]/w:tcBorders[1]/w:right[1]">
                <xsl:call-template name="GetBorderPr"/>
              </xsl:for-each>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$bdr.right"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr" select="$thisBdr.top"/>
      <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
    </xsl:call-template>
    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr" select="$thisBdr.right"/>
      <xsl:with-param name="bdrSide" select="$bdrSide_right.bidi"/>
    </xsl:call-template>
    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr" select="$thisBdr.bottom"/>
      <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
    </xsl:call-template>
    <xsl:call-template name="ApplyBorderPr">
      <xsl:with-param name="pr.bdr" select="$thisBdr.left"/>
      <xsl:with-param name="bdrSide" select="$bdrSide_left.bidi"/>
    </xsl:call-template>

  </xsl:template>

  <xsl:template name="ApplyTcPr.once">
    <xsl:param name="cellspacing"/>
    <xsl:param name="cellpadding.default"/>
    <xsl:param name="cellpadding.custom"/>
    <xsl:param name="bdr.top"/>
    <xsl:param name="bdr.left"/>
    <xsl:param name="bdr.bottom"/>
    <xsl:param name="bdr.right"/>
    <xsl:param name="bdr.insideV"/>
    <xsl:param name="thisRow"/>
    <xsl:param name="lastRow"/>
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="cnfCol"/>
    <xsl:param name="b.bidivisual"/>

    <xsl:variable name="cnfType">
      <xsl:if test="not($cnfRow='' and $cnfCol='')">
        <xsl:call-template name="GetCnfType">
          <xsl:with-param name="cnfRow" select="$cnfRow"/>
          <xsl:with-param name="cnfCol" select="$cnfCol"/>
        </xsl:call-template>
      </xsl:if>
    </xsl:variable>

    <xsl:variable name="cnfTypeRow">
      <xsl:if test="not($cnfRow='')">
        <xsl:call-template name="GetCnfTypeRow">
          <xsl:with-param name="cnfRow" select="$cnfRow"/>
        </xsl:call-template>
      </xsl:if>
    </xsl:variable>

    <xsl:variable name="cnfTypeCol">
      <xsl:if test="not($cnfCol='')">
        <xsl:call-template name="GetCnfTypeCol">
          <xsl:with-param name="cnfCol" select="$cnfCol"/>
        </xsl:call-template>
      </xsl:if>
    </xsl:variable>

    <xsl:variable name="tcborders" select="w:tcPr[1]/w:tcBorders[1]"/>

    <xsl:variable name="bdrSide_left.bidi">
      <xsl:choose>
        <xsl:when test="$b.bidivisual = $on">
          <xsl:value-of select="$bdrSide_right"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$bdrSide_left"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="bdrSide_right.bidi">
      <xsl:choose>
        <xsl:when test="$b.bidivisual = $on">
          <xsl:value-of select="$bdrSide_left"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$bdrSide_right"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:for-each select="$sTblStyleName/w:tblPr[1]/w:tblBorders[1]">
      <xsl:call-template name="ApplyBorderPr">
        <xsl:with-param name="pr.bdr" select="$bdr.top"/>
        <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
      </xsl:call-template>
      <xsl:call-template name="ApplyBorderPr">
        <xsl:with-param name="pr.bdr" select="$bdr.bottom"/>
        <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
      </xsl:call-template>
      <xsl:call-template name="ApplyBorderPr">
        <xsl:with-param name="pr.bdr" select="$bdr.right"/>
        <xsl:with-param name="bdrSide" select="$bdrSide_right.bidi"/>
      </xsl:call-template>
      <xsl:call-template name="ApplyBorderPr">
        <xsl:with-param name="pr.bdr" select="$bdr.left"/>
        <xsl:with-param name="bdrSide" select="$bdrSide_left.bidi"/>
      </xsl:call-template>
    </xsl:for-each>

    <xsl:call-template name="ApplyExtraCornerBorders">
      <xsl:with-param name="cnfType" select="$cnfType" />
      <xsl:with-param name="sTblStyleName" select="$sTblStyleName" />
    </xsl:call-template>

    <xsl:call-template name="ApplyTcBordersFromCnf">
      <xsl:with-param name="cnfType" select="$cnfTypeRow" />
      <xsl:with-param name="sTblStyleName" select="$sTblStyleName" />
      <xsl:with-param name="tcBorders" select="$tcborders" />
      <xsl:with-param name="bdrSide_right.bidi" select="$bdrSide_right.bidi" />
      <xsl:with-param name="bdrSide_left.bidi" select="$bdrSide_left.bidi" />
      <xsl:with-param name="thisRow" select="$thisRow"/>
      <xsl:with-param name="lastRow" select="$lastRow"/>

      <xsl:with-param name="bdr.top" select="$bdr.top"/>
      <xsl:with-param name="bdr.left" select="$bdr.left"/>
      <xsl:with-param name="bdr.right" select="$bdr.right"/>
      <xsl:with-param name="bdr.bottom" select="$bdr.bottom"/>
    </xsl:call-template>

    <xsl:call-template name="ApplyTcBordersFromCnf">
      <xsl:with-param name="cnfType" select="$cnfTypeCol" />
      <xsl:with-param name="sTblStyleName" select="$sTblStyleName" />
      <xsl:with-param name="tcBorders" select="$tcborders" />
      <xsl:with-param name="bdrSide_right.bidi" select="$bdrSide_right.bidi" />
      <xsl:with-param name="bdrSide_left.bidi" select="$bdrSide_left.bidi" />
      <xsl:with-param name="thisRow" select="$thisRow"/>
      <xsl:with-param name="lastRow" select="$lastRow"/>

      <xsl:with-param name="bdr.top" select="$bdr.top"/>
      <xsl:with-param name="bdr.left" select="$bdr.left"/>
      <xsl:with-param name="bdr.right" select="$bdr.right"/>
      <xsl:with-param name="bdr.bottom" select="$bdr.bottom"/>
    </xsl:call-template>

    <xsl:variable name="cellpadding.custom.merged">

      <xsl:variable name="temp.direct">
        <xsl:for-each select="w:tcPr[1]/w:tcMar[1]">
          <xsl:call-template name="ApplyCellMar"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:value-of select="$temp.direct"/>
      <xsl:if test="$temp.direct=''">

        <xsl:variable name="temp.cnf">
          <xsl:for-each select="$sTblStyleName">
            <xsl:call-template name="GetCnfPr.cell">
              <xsl:with-param name="type" select="$prrCustomCellpadding"/>
              <xsl:with-param name="cnfCol" select="$cnfCol"/>
              <xsl:with-param name="cnfRow" select="$cnfRow"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:variable>
        <xsl:value-of select="$temp.cnf"/>
        <xsl:if test="$temp.cnf=''">

          <xsl:value-of select="$cellpadding.custom"/>
        </xsl:if>
      </xsl:if>
    </xsl:variable>
    <xsl:variable name="cellpadding.default.merged">

      <xsl:variable name="temp.cnf">
        <xsl:for-each select="$sTblStyleName">
          <xsl:call-template name="GetCnfPr.cell">
            <xsl:with-param name="type" select="$prrDefaultCellpadding"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
          </xsl:call-template>
        </xsl:for-each>
      </xsl:variable>
      <xsl:value-of select="$temp.cnf"/>
      <xsl:if test="$temp.cnf=''">

        <xsl:value-of select="$cellpadding.default"/>
      </xsl:if>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$cellpadding.custom.merged = 'none' and not($cellpadding.default.merged='')">
        <xsl:value-of select="$cellpadding.default.merged"/>
      </xsl:when>
      <xsl:when test="not($cellpadding.custom.merged='')">
        <xsl:value-of select="$cellpadding.custom.merged"/>
      </xsl:when>
      <xsl:when test="not($cellpadding.default.merged='')">
        <xsl:value-of select="$cellpadding.default.merged"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:tc">
    <xsl:param name="sTblStyleName" select="($nsStyles[@w:styleId=$tblStyleID_Default])[1]"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:param name="cellspacing"/>
    <xsl:param name="cellpadding.default"/>
    <xsl:param name="cellpadding.custom"/>
    <xsl:param name="bdr.top"/>
    <xsl:param name="bdr.left"/>
    <xsl:param name="bdr.bottom"/>
    <xsl:param name="bdr.right"/>
    <xsl:param name="bdr.insideV"/>
    <xsl:param name="bdr.insideH"/>
    <xsl:param name="thisRow"/>
    <xsl:param name="lastRow"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="b.bidivisual"/>
    <xsl:variable name="cnfCol" select="string(w:tcPr[1]/w:cnfStyle[1]/@w:val)"/>
    <xsl:variable name="vmerge" select="w:tcPr[1]/w:vMerge[1]"/>
    <xsl:variable name="me" select="." />
    <xsl:variable name="tblCount" select="count(ancestor::w:tbl)" />
    <xsl:variable name="meInContext" select="ancestor::w:tr[1]/*[count($me|descendant-or-self::*)=count(descendant-or-self::*)]" />
    <xsl:variable name="before" select="count($meInContext/preceding-sibling::*[descendant-or-self::*[name()='w:tc' and (count(ancestor::w:tbl)=$tblCount)]])" />
    <xsl:variable name="after" select="count($meInContext/following-sibling::*[descendant-or-self::*[name()='w:tc' and (count(ancestor::w:tbl)=$tblCount)]])" />
  <!-- TO FIX THE COVERPAGE issue added by Shbuh
    <xsl:variable name="tStyleId" />
    <xsl:attribute name="class">
      <xsl:value-of select="$tStyleId"/>
      <xsl:value-of select="$tblStyleSuffix"/>
    </xsl:attribute>
    <xsl:variable name="sTblStyleName" select="($nsStyles[@w:styleId=$tStyleId])[1]"/>
    TO FIX THE COVERPAGE issue added by Shbuha-->
    <xsl:if test="not($vmerge and not($vmerge/@w:val))">
      <td>

        <xsl:attribute name="class">
          <xsl:value-of select="$sTblStyleName/@w:styleId"/>
          <xsl:value-of select="$cellStyleSuffix"/>
        </xsl:attribute>

        <xsl:for-each select="w:tcPr[1]/w:gridSpan[1]/@w:val">
          <xsl:attribute name="colspan">
            <xsl:value-of select="."/>
          </xsl:attribute>
        </xsl:for-each>

        <xsl:variable name="rowspan">
          <xsl:choose>
            <xsl:when test="not($vmerge)">1</xsl:when>

            <xsl:otherwise>
              <xsl:variable name="myRow" select="ancestor::w:tr[1]" />
              <xsl:variable name="myRowInContext" select="$myRow/ancestor::w:tbl[1]/*[count($myRow|descendant-or-self::*)=count(descendant-or-self::*)]" />
              <xsl:variable name="belowMe" select="$myRowInContext/following-sibling::*//w:tc[count(ancestor::w:tbl)=$tblCount][$before + 1]" />
              <xsl:variable name="NextRestart" select="($belowMe//w:tcPr/w:vMerge[@w:val='restart'])[1]" />
              <xsl:variable name="NextRestartInContext" select="$NextRestart/ancestor::w:tbl[1]/*[count($NextRestart|descendant-or-self::*)=count(descendant-or-self::*)]" />
              <xsl:variable name="mergesAboveMe"                select="count($myRowInContext/preceding-sibling::*[(descendant-or-self::*[name()='w:tc'])[$before + 1][descendant-or-self::*[name()='w:vMerge']]])" />
              <xsl:variable name="mergesAboveNextRestart" select="count($NextRestartInContext/preceding-sibling::*[(descendant-or-self::*[name()='w:tc'])[$before + 1][descendant-or-self::*[name()='w:vMerge']]])" />

              <xsl:choose>
                <xsl:when test="$NextRestart">
                  <xsl:value-of select="$mergesAboveNextRestart - $mergesAboveMe"/>
                </xsl:when>
                <xsl:when test="$vmerge/@w:val">
                  <xsl:value-of select="count($belowMe[descendant-or-self::*[name()='w:vMerge']]) + 1" />
                </xsl:when>
                <xsl:otherwise>1</xsl:otherwise>
              </xsl:choose>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>

        <xsl:if test="$vmerge">
          <xsl:attribute name="rowspan">
            <xsl:value-of select="$rowspan"/>
          </xsl:attribute>
        </xsl:if>
        <xsl:variable name="lastRow.updated" select="$lastRow - $rowspan + 1"/>

        <xsl:variable name="bdr.bottom.updated">
          <xsl:choose>
            <xsl:when test="$cellspacing='' and $thisRow=$lastRow.updated">
              <xsl:value-of select="$bdr.bottom"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="$bdr.insideH"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        <xsl:variable name="bdr.left.updated">
          <xsl:choose>
            <xsl:when test="$cellspacing='' and $before=0">
              <xsl:value-of select="$bdr.left"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="$bdr.insideV"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        <xsl:variable name="bdr.right.updated">
          <xsl:choose>
            <xsl:when test="$cellspacing='' and $after=0">
              <xsl:value-of select="$bdr.right"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="$bdr.insideV"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>

        <xsl:attribute name="style">

          <xsl:if test="not($cnfRow='' and $cnfCol='')">
            <xsl:for-each select="$sTblStyleName">
              <xsl:call-template name="GetCnfPr.all">
                <xsl:with-param name="type" select="$prrApplyTcPr"/>
                <xsl:with-param name="cnfRow" select="$cnfRow"/>
                <xsl:with-param name="cnfCol" select="$cnfCol"/>
              </xsl:call-template>
            </xsl:for-each>
          </xsl:if>

          <xsl:call-template name="ApplyTcPr.class"/>
          <xsl:call-template name="ApplyTcPr.once">
            <xsl:with-param name="thisRow" select="$thisRow"/>
            <xsl:with-param name="lastRow" select="$lastRow.updated"/>
            <xsl:with-param name="cellspacing" select="$cellspacing"/>
            <xsl:with-param name="cellpadding.default" select="$cellpadding.default"/>
            <xsl:with-param name="cellpadding.custom" select="$cellpadding.custom"/>
            <xsl:with-param name="bdr.top" select="$bdr.top"/>
            <xsl:with-param name="bdr.left" select="$bdr.left.updated"/>
            <xsl:with-param name="bdr.right" select="$bdr.right.updated"/>
            <xsl:with-param name="bdr.bottom" select="$bdr.bottom.updated"/>
            <xsl:with-param name="bdr.insideV" select="$bdr.insideV"/>
            <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
            <xsl:with-param name="cnfRow" select="$cnfRow"/>
            <xsl:with-param name="cnfCol" select="$cnfCol"/>
            <xsl:with-param name="b.bidivisual" select="$b.bidivisual"/>
          </xsl:call-template>
        </xsl:attribute>
        <xsl:choose>
          <xsl:when test="$cnfRow='' and $cnfCol=''">

            <xsl:call-template name="DisplayBodyContent">
              <xsl:with-param name="ns.content" select="*"/>
              <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
              <xsl:with-param name="prsP" select="$prsP"/>
              <xsl:with-param name="prsR" select="$prsR"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:otherwise>

            <xsl:call-template name="WrapCnf">
              <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
              <xsl:with-param name="cnfRow" select="$cnfRow"/>
              <xsl:with-param name="cnfCol" select="$cnfCol"/>
              <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
              <xsl:with-param name="prsP" select="$prsP"/>
              <xsl:with-param name="prsR" select="$prsR"/>
            </xsl:call-template>
          </xsl:otherwise>
        </xsl:choose>
      </td>
    </xsl:if>
  </xsl:template>

  <xsl:template name="RecursiveApplyTrPr.class">
    <xsl:if test="w:basedOn">
      <xsl:variable name="baseStyleName" select="w:basedOn[1]/@w:val" />
      <xsl:variable name="sTblStyleBase" select="($nsStyles[@w:styleId=$baseStyleName])[1]"/>
      <xsl:for-each select="$sTblStyleBase">
        <xsl:call-template name="RecursiveApplyTrPr.class" />
      </xsl:for-each>
    </xsl:if>

    <xsl:call-template name="ApplyTrPr.class"/>
  </xsl:template>

  <xsl:template name="ApplyTrPr.class">
    <xsl:for-each select="w:trPr">

      <xsl:text>height:</xsl:text>
      <xsl:choose>
        <xsl:when test="w:trHeight/@w:val">
          <xsl:value-of select="w:trHeight[1]/@w:val div 20"/>pt
        </xsl:when>
        <xsl:otherwise>0</xsl:otherwise>
      </xsl:choose>
      <xsl:text>;</xsl:text>

      <xsl:for-each select="w:cantSplit[1]">
        <xsl:choose>
          <xsl:when test="@w:val = 'off'">page-break-inside:auto;</xsl:when>
          <xsl:otherwise>page-break-inside:avoid;</xsl:otherwise>
        </xsl:choose>
      </xsl:for-each>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="DisplayEmptyCell">
    <xsl:param name="i" select="1"/>
    <td colspan="$i"></td>
  </xsl:template>

  <xsl:template match="w:tr">
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:param name="cellspacing"/>
    <xsl:param name="cellpadding.default"/>
    <xsl:param name="cellpadding.custom"/>
    <xsl:param name="bdr.top"/>
    <xsl:param name="bdr.left"/>
    <xsl:param name="bdr.bottom"/>
    <xsl:param name="bdr.right"/>
    <xsl:param name="bdr.insideH"/>
    <xsl:param name="bdr.insideV"/>
    <xsl:param name="b.bidivisual"/>
    <tr>

      <xsl:attribute name="class">
        <xsl:value-of select="$sTblStyleName/@w:styleId"/>
        <xsl:value-of select="$rowStyleSuffix"/>
      </xsl:attribute>

      <xsl:variable name="cnfRow" select="string(w:trPr[1]/w:cnfStyle[1]/@w:val)"/>

      <xsl:variable name="styleMod">

        <xsl:if test="not($cnfRow='')">
          <xsl:for-each select="$sTblStyleName">
            <xsl:call-template name="GetCnfPr.row">
              <xsl:with-param name="type" select="$prrCantSplit"/>
              <xsl:with-param name="cnfRow" select="$cnfRow"/>
            </xsl:call-template>
          </xsl:for-each>
        </xsl:if>

        <xsl:call-template name="ApplyTrPr.class"/>
      </xsl:variable>
      <xsl:if test="not($styleMod='')">
        <xsl:attribute name="style">
          <xsl:value-of select="$styleMod"/>
        </xsl:attribute>
      </xsl:if>

      <xsl:variable name="me" select="." />
      <xsl:variable name="tblCount" select="count(ancestor::w:tbl)" />
      <xsl:variable name="meInContext" select="ancestor::w:tbl[1]/*[count($me|descendant-or-self::*)=count(descendant-or-self::*)]" />
      <xsl:variable name="before" select="count($meInContext/preceding-sibling::*[descendant-or-self::*[name()='w:tr' and (count(ancestor::w:tbl)=$tblCount)]])" />
      <xsl:variable name="after" select="count($meInContext/following-sibling::*[descendant-or-self::*[name()='w:tr' and (count(ancestor::w:tbl)=$tblCount)]])" />
      <xsl:variable name="thisRow" select="$before + 1"/>
      <xsl:variable name="lastRow" select="$before + $after + 1"/>

      <xsl:variable name="bdr.top.updated">
        <xsl:choose>
          <xsl:when test="$cellspacing='' and $thisRow=1">
            <xsl:value-of select="$bdr.top"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$bdr.insideH"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>

      <xsl:for-each select="w:trPr[1]/w:gridBefore[1]/@w:val">
        <xsl:call-template name="DisplayEmptyCell">
          <xsl:with-param name="i">
            <xsl:value-of select="."/>
          </xsl:with-param>
        </xsl:call-template>
      </xsl:for-each>

      <xsl:apply-templates select="*[not(name()='w:trPr')]">
        <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
        <xsl:with-param name="prsP" select="$prsP"/>
        <xsl:with-param name="prsR" select="$prsR"/>
        <xsl:with-param name="thisRow" select="$thisRow"/>
        <xsl:with-param name="lastRow" select="$lastRow"/>
        <xsl:with-param name="cellspacing" select="$cellspacing"/>
        <xsl:with-param name="cellpadding.default" select="$cellpadding.default"/>
        <xsl:with-param name="cellpadding.custom" select="$cellpadding.custom"/>
        <xsl:with-param name="bdr.top" select="$bdr.top.updated"/>
        <xsl:with-param name="bdr.left" select="$bdr.left"/>
        <xsl:with-param name="bdr.right" select="$bdr.right"/>
        <xsl:with-param name="bdr.bottom" select="$bdr.bottom"/>
        <xsl:with-param name="bdr.insideV" select="$bdr.insideV"/>
        <xsl:with-param name="bdr.insideH" select="$bdr.insideH"/>
        <xsl:with-param name="cnfRow" select="$cnfRow"/>
        <xsl:with-param name="b.bidivisual" select="$b.bidivisual"/>
      </xsl:apply-templates>

      <xsl:for-each select="w:trPr[1]/w:gridAfter[1]/@w:val">
        <xsl:call-template name="DisplayEmptyCell">
          <xsl:with-param name="i">
            <xsl:value-of select="."/>
          </xsl:with-param>
        </xsl:call-template>
      </xsl:for-each>
    </tr>
  </xsl:template>

  <xsl:template name="RecursiveApplyTblPr.class">
    <xsl:if test="w:basedOn">
      <xsl:variable name="baseStyleName" select="w:basedOn[1]/@w:val" />
      <xsl:variable name="sTblStyleBase" select="($nsStyles[@w:styleId=$baseStyleName])[1]"/>
      <xsl:for-each select="$sTblStyleBase">
        <xsl:call-template name="RecursiveApplyTblPr.class" />
      </xsl:for-each>
    </xsl:if>

    <xsl:call-template name="ApplyTblPr.class"/>
  </xsl:template>

  <xsl:template name="ApplyTblPr.class">
    <xsl:for-each select="w:tblPr[1]">

      <xsl:if test="w:tblpPr/@w:topFromText">
        margin-top:<xsl:value-of select="w:tblpPr/@w:topFromText[1] div 20"/>pt;
      </xsl:if>
      <xsl:if test="w:tblpPr/@w:rightFromText">
        margin-right:<xsl:value-of select="w:tblpPr/@w:rightFromText[1] div 20"/>pt;
      </xsl:if>
      <xsl:if test="w:tblpPr/@w:bottomFromText">
        margin-bottom:<xsl:value-of select="w:tblpPr/@w:bottomFromText[1] div 20"/>pt;
      </xsl:if>
      <xsl:if test="w:tblpPr/@w:leftFromText">
        margin-left:<xsl:value-of select="w:tblpPr/@w:leftFromText[1] div 20"/>pt;
      </xsl:if>

      <xsl:for-each select="w:tblW[1]">
        <xsl:if test="@w:type != 'auto'">
          width:<xsl:call-template name="EvalTableWidth"/>;
        </xsl:if>
      </xsl:for-each>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="tblCore">
    <table>

      <xsl:variable name="tStyleId">
        <xsl:choose>
          <xsl:when test="w:tblPr[1]/w:tblStyle[1]/@w:val">
            <xsl:value-of select="w:tblPr[1]/w:tblStyle[1]/@w:val"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$tblStyleID_Default"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:attribute name="class">
        <xsl:value-of select="$tStyleId"/>
        <xsl:value-of select="$tblStyleSuffix"/>
      </xsl:attribute>
      <xsl:variable name="sTblStyleName" select="($nsStyles[@w:styleId=$tStyleId])[1]"/>

      <xsl:variable name="cellspacingTEMP">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrCellspacing"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="cellspacing">
        <xsl:choose>

          <xsl:when test="$cellspacingTEMP='0'"></xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$cellspacingTEMP"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:variable name="cellpadding.default">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrDefaultCellpadding"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="cellpadding.custom">
        <xsl:for-each select="$sTblStyleName/w:tcPr[1]/w:tcMar[1]">
          <xsl:call-template name="ApplyCellMar"/>
        </xsl:for-each>
      </xsl:variable>

      <xsl:variable name="tblInd">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrTblInd"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:variable name="bdr.top">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrBdrPr_top"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="bdr.left">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrBdrPr_left"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="bdr.bottom">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrBdrPr_bottom"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="bdr.right">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrBdrPr_right"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:variable name="bdr.insideH">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrBdrPr_insideH"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="bdr.insideV">
        <xsl:call-template name="GetSingleTblPr">
          <xsl:with-param name="type" select="$prrBdrPr_insideV"/>
          <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:variable name="b.bidivisual">
        <xsl:for-each select="w:tblPr[1]/w:bidiVisual[1]">
          <xsl:value-of select="$on"/>
        </xsl:for-each>
      </xsl:variable>

      <xsl:variable name="align">
        <xsl:for-each select="w:tblPr[1]/w:tblpPr[1]/@w:tblpXSpec">
          <xsl:value-of select="."/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:if test="not($align='')">
        <xsl:attribute name="align">
          <xsl:choose>
            <xsl:when test="$align = 'right' or $align = 'outside'">right</xsl:when>
            <xsl:otherwise>left</xsl:otherwise>
          </xsl:choose>
        </xsl:attribute>
      </xsl:if>

      <xsl:attribute name="cellspacing">
        <xsl:choose>
          <xsl:when test="$cellspacing=''">0</xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="($cellspacing div 1440) * $pixelsPerInch"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
      <xsl:if test="$cellspacing=''">
        <xsl:attribute name="cellspacing">0</xsl:attribute>
      </xsl:if>

      <xsl:variable name="styleMod">
        <xsl:call-template name="ApplyTblPr.class"/>

        <xsl:choose>
          <xsl:when test="$cellspacing=''">border-collapse:collapse;</xsl:when>
          <xsl:otherwise>
            <xsl:text>border-collapse:separate;</xsl:text>
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$bdr.top"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_top"/>
            </xsl:call-template>
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$bdr.left"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_left"/>
            </xsl:call-template>
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$bdr.bottom"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_bottom"/>
            </xsl:call-template>
            <xsl:call-template name="ApplyBorderPr">
              <xsl:with-param name="pr.bdr" select="$bdr.right"/>
              <xsl:with-param name="bdrSide" select="$bdrSide_right"/>
            </xsl:call-template>
          </xsl:otherwise>
        </xsl:choose>

        <xsl:if test="$b.bidivisual=$on">direction:rtl;</xsl:if>

        <xsl:if test="not(w:tblPr/w:tblpPr)">
          <xsl:text>margin-</xsl:text>
          <xsl:choose>
            <xsl:when test="$b.bidivisual=$on">right</xsl:when>
            <xsl:otherwise>left</xsl:otherwise>
          </xsl:choose>
          <xsl:text>:</xsl:text>
          <xsl:value-of select="$tblInd"/>
          <xsl:text>;</xsl:text>
        </xsl:if>
      </xsl:variable>
      <xsl:if test="not($styleMod='')">
        <xsl:attribute name="style">
          <xsl:value-of select="$styleMod"/>
        </xsl:attribute>
      </xsl:if>

      <xsl:variable name="prsPAccum">

        <xsl:for-each select="$sTblStyleName">
          <xsl:call-template name="ApplyPPr.many"/>
        </xsl:for-each>
      </xsl:variable>

      <xsl:variable name="prsR">
        <xsl:call-template name="PrsUpdateRPr">
          <xsl:with-param name="ndPrContainer" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:variable name="prsP">
        <xsl:call-template name="PrsUpdatePPr">
          <xsl:with-param name="ndPrContainer" select="$sTblStyleName"/>
        </xsl:call-template>
      </xsl:variable>

      <xsl:apply-templates select="*[not(name()='w:tblPr' or name()='w:tblGrid')]">
        <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
        <xsl:with-param name="prsP" select="$prsP"/>
        <xsl:with-param name="prsR" select="$prsR"/>
        <xsl:with-param name="cellspacing" select="$cellspacing"/>
        <xsl:with-param name="cellpadding.default" select="$cellpadding.default"/>
        <xsl:with-param name="cellpadding.custom" select="$cellpadding.custom"/>
        <xsl:with-param name="bdr.top" select="$bdr.top"/>
        <xsl:with-param name="bdr.left" select="$bdr.left"/>
        <xsl:with-param name="bdr.right" select="$bdr.right"/>
        <xsl:with-param name="bdr.bottom" select="$bdr.bottom"/>
        <xsl:with-param name="bdr.insideH" select="$bdr.insideH"/>
        <xsl:with-param name="bdr.insideV" select="$bdr.insideV"/>
        <xsl:with-param name="b.bidivisual" select="$b.bidivisual"/>
      </xsl:apply-templates>

      
      <xsl:for-each select="w:tblGrid[1]">
        <!--<xsl:text disable-output-escaping="yes">&lt;![if !supportMisalignedColumns]&gt;</xsl:text>-->
        <tr height="0">
          <xsl:for-each select="w:gridCol">
            <xsl:variable name="gridStyle">
              margin:0;padding:0;border:none;width:<xsl:call-template name="EvalTableWidth"/>;
            </xsl:variable>
            <td style="{$gridStyle}"/>
          </xsl:for-each>
        </tr>
        <!--<xsl:text disable-output-escaping="yes">&lt;![endif]&gt;</xsl:text>-->
      </xsl:for-each>
    </table>
  </xsl:template>

  <xsl:template match="w:tbl[w:tblPr/w:jc/@w:val]">
    <xsl:variable name="p.Jc" select="w:tblPr/w:jc/@w:val"/>
    <div>
      <xsl:attribute name="align">
        <xsl:value-of select="$p.Jc"/>
      </xsl:attribute>

      <xsl:call-template name="tblCore"/>
    </div>
  </xsl:template>

  <xsl:template match="w:tbl">
    <xsl:call-template name="tblCore"/>
  </xsl:template>

  <xsl:template name="hrCore">
    <xsl:param name="p.Hr"/>
    <hr>
      <xsl:attribute name="style">
        <xsl:value-of select="substring-after($p.Hr/@style, ';')"/>
      </xsl:attribute>
      <xsl:attribute name="align">
        <xsl:value-of select="$p.Hr/@o:hralign"/>
      </xsl:attribute>
      <xsl:if test="$p.Hr/@o:hrnoshade='t'">
        <xsl:attribute name="noshade">
          <xsl:text>1</xsl:text>
        </xsl:attribute>
        <xsl:attribute name="color">
          <xsl:value-of select="$p.Hr/@fillcolor"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:if test="$p.Hr/@o:hrpct">
        <xsl:attribute name="width">
          <xsl:value-of select="$p.Hr/@o:hrpct div 10"/>
          <xsl:text>%</xsl:text>
        </xsl:attribute>
      </xsl:if>
    </hr>
  </xsl:template>

  <xsl:template match="w:p[w:r[1]//v:rect/@o:hrstd and not(w:r[2])]">
    <xsl:call-template name="hrCore">
      <xsl:with-param name="p.Hr" select="w:r//v:rect"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="v:rect[@o:hrstd]">
    <xsl:call-template name="hrCore">
      <xsl:with-param name="p.Hr" select="."/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="w:body">

    <xsl:attribute name="style">
      <xsl:variable name="divBody" select="/w:document/w:divs/w:div[w:bodyDiv/@w:val='on']"/>
      <xsl:variable name="dxaBodyLeft">
        <xsl:value-of select="$divBody/w:marLeft/@w:val"/>
      </xsl:variable>
      <xsl:variable name="dxaBodyRight">
        <xsl:value-of select="$divBody/w:marRight/@w:val"/>
      </xsl:variable>
      <xsl:if test="not($dxaBodyLeft='' or $dxaBodyLeft=0)">
        <xsl:text>margin-left:</xsl:text>
        <xsl:value-of select="$dxaBodyLeft div 20"/>
        <xsl:text>pt;</xsl:text>
      </xsl:if>
      <xsl:if test="not($dxaBodyRight='' or $dxaBodyRight=0)">
        <xsl:text>margin-right:</xsl:text>
        <xsl:value-of select="$dxaBodyRight div 20"/>
        <xsl:text>pt;</xsl:text>
      </xsl:if>
    </xsl:attribute>
    <xsl:apply-templates select="*"/>
  </xsl:template>

  <xsl:template match="w:font">
    <xsl:text>@font-face{font-family:"</xsl:text>
    <xsl:value-of select="@w:name"/>
    <xsl:text>";panose-1:</xsl:text>
    <xsl:variable name="panose1">
      <xsl:call-template name="ConvertHexToDec">
        <xsl:with-param name="value" select="w:panose-1[1]/@w:val"/>
        <xsl:with-param name="i" select="2"/>
        <xsl:with-param name="s" select="2"/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:value-of select="substring($panose1,2)"/>
    <xsl:text>;}</xsl:text>
  </xsl:template>

  <xsl:template name="MakeRStyle">
    <xsl:text>.</xsl:text>
    <xsl:value-of select="@w:styleId"/>
    <xsl:value-of select="$charStyleSuffix"/>
    <xsl:text>{</xsl:text>
    <xsl:call-template name="MakeRStyleCore"/>
    <xsl:text>} 
   </xsl:text>
  </xsl:template>

  <xsl:template name="MakeRStyleCore">

    <xsl:if test="w:basedOn/@w:val">
      <xsl:variable name="sBasedOn">
        <xsl:value-of select="w:basedOn/@w:val"/>
      </xsl:variable>
      <xsl:for-each select="$nsStyles[@w:styleId=$sBasedOn]">
        <xsl:call-template name="MakeRStyleCore"/>
      </xsl:for-each>
    </xsl:if>

    <xsl:call-template name="ApplyRPr.class"/>
  </xsl:template>

  <xsl:template name="MakePStyle">

    <xsl:text>.</xsl:text>
    <xsl:value-of select="@w:styleId"/>
    <xsl:value-of select="$paraStyleSuffix"/>
    <xsl:text>{
   </xsl:text>
    <xsl:call-template name="MakePStyleCore"/>
    <xsl:text>} 
   </xsl:text>

    <xsl:call-template name="MakeRStyle"/>
  </xsl:template>

  <xsl:template name="MakePStyleCore">
    <xsl:param name="beforeAutospace" select="$off" />
    <xsl:param name="afterAutospace" select="$off" />

    <xsl:variable name="spacing" select="w:pPr[1]/w:spacing[1]"/>
    <xsl:variable name="beforeAutospaceHere">
      <xsl:choose>
        <xsl:when test="$spacing/@w:before-autospacing = 'on'">
          <xsl:value-of select="$on" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$beforeAutospace" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:variable name="afterAutospaceHere">
      <xsl:choose>
        <xsl:when test="$spacing/@w:after-autospacing = 'on'">
          <xsl:value-of select="$on" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$afterAutospace" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:choose>
      <xsl:when test="w:basedOn/@w:val">
        <xsl:variable name="sBasedOn">
          <xsl:value-of select="w:basedOn/@w:val"/>
        </xsl:variable>
        <xsl:for-each select="$nsStyles[@w:styleId=$sBasedOn]">
          <xsl:call-template name="MakePStyleCore">
            <xsl:with-param name="beforeAutospace" select="$beforeAutospaceHere" />
            <xsl:with-param name="afterAutospace" select="$afterAutospaceHere" />
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>margin-left:</xsl:text>
        <xsl:value-of select="$paraMarginDefaultLeft"/>
        <xsl:text>;margin-right:</xsl:text>
        <xsl:value-of select="$paraMarginDefaultRight"/>

        <xsl:if test="not($beforeAutospace = $on)" >
          <xsl:if test="(not($spacing/@w:before-autospacing) or $spacing/@w:before-autospacing = 'off')">
            <xsl:text>;margin-top:</xsl:text>
            <xsl:value-of select="$paraMarginDefaultTop"/>
          </xsl:if>
        </xsl:if>

        <xsl:if test="not($afterAutospace = $on)" >
          <xsl:if test="(not($spacing/@w:after-autospacing) or $spacing/@w:after-autospacing = 'off')">
            <xsl:text>;margin-bottom:</xsl:text>
            <xsl:value-of select="$paraMarginDefaultBottom"/>
          </xsl:if>
        </xsl:if>

        <xsl:text>;font-size:10.0pt;font-family:"Times New Roman";</xsl:text>

        <xsl:for-each select="/w:document[1]/w:styles[1]/w:docDefaults/w:pPrDefault">
          <xsl:call-template name="ApplyPPr.many"/>
        </xsl:for-each>

      </xsl:otherwise>
    </xsl:choose>

    <xsl:call-template name="ApplyPPr.class"/>
  </xsl:template>

  <xsl:template name="MakeTblStyle">
    <xsl:variable name="styleId" select="@w:styleId"/>

    <xsl:text>.</xsl:text>
    <xsl:value-of select="$styleId"/>
    <xsl:value-of select="$tblStyleSuffix"/>
    <xsl:text>{</xsl:text>
    <xsl:call-template name="RecursiveApplyTblPr.class"/>
    <xsl:text>} </xsl:text>

    <xsl:text>.</xsl:text>
    <xsl:value-of select="$styleId"/>
    <xsl:value-of select="$rowStyleSuffix"/>
    <xsl:text>{</xsl:text>
    <xsl:call-template name="RecursiveApplyTrPr.class"/>
    <xsl:text>} </xsl:text>

    <xsl:text>.</xsl:text>
    <xsl:value-of select="$styleId"/>
    <xsl:value-of select="$cellStyleSuffix"/>
    <xsl:text>{vertical-align:top;</xsl:text>

    <xsl:call-template name="RecursiveApplyTcPr.class"/>

    <xsl:call-template name="RecursiveApplyPPr.class"/>

    <xsl:call-template name="RecursiveApplyRPr.class"/>
    <xsl:text>} </xsl:text>

    <xsl:for-each select="w:tblStylePr">
      <xsl:text>.</xsl:text><xsl:value-of select="$styleId"/>-<xsl:value-of select="@w:type"/>
      <xsl:text>{vertical-align:top;</xsl:text>
      <xsl:call-template name="ApplyPPr.class"/>
      <xsl:call-template name="ApplyRPr.class"/>
      <xsl:text>} </xsl:text>
    </xsl:for-each>
  </xsl:template>

  <xsl:template match="w:style">
    <xsl:choose>

      <xsl:when test="@w:type = 'character'">
        <xsl:call-template name="MakeRStyle"/>
      </xsl:when>

      <xsl:when test="@w:type = 'paragraph'">
        <xsl:call-template name="MakePStyle"/>
      </xsl:when>

      <xsl:when test="@w:type = 'table'">
        <xsl:call-template name="MakeTblStyle"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="a:fontScheme">
    .<xsl:value-of select="$minorAsciiTheme"/>{font-family:<xsl:value-of select="./a:minorFont/a:latin/@typeface" />;}
    .<xsl:value-of select="$majorAsciiTheme"/>{font-family:<xsl:value-of select="./a:majorFont/a:latin/@typeface" />;}
    .<xsl:value-of select="$minorAnsiTheme"/>{font-family:<xsl:value-of select="./a:minorFont/a:latin/@typeface" />;}
    .<xsl:value-of select="$majorAnsiTheme"/>{font-family:<xsl:value-of select="./a:majorFont/a:latin/@typeface" />;}
    .<xsl:value-of select="$minorEATheme"/>{font-family:<xsl:value-of select="./a:minorFont/a:ea/@typeface" />;}
    .<xsl:value-of select="$majorEATheme"/>{font-family:<xsl:value-of select="./a:majorFont/a:ea/@typeface" />;}
    .<xsl:value-of select="$minorCSTheme"/>{font-family:<xsl:value-of select="./a:minorFont/a:cs/@typeface" />;}
    .<xsl:value-of select="$majorCSTheme"/>{font-family:<xsl:value-of select="./a:majorFont/a:cs/@typeface" />;}
  </xsl:template>

  <xsl:template match="w:bookmarkStart">
    <a name="{@w:name}"/>
  </xsl:template>

  <xsl:template match="w:ins">
    <xsl:param name="b.bidi"/>
    <xsl:param name="prsR"/>
    <ins>
      <xsl:call-template name="DisplayPContent">
        <xsl:with-param name="b.bidi" select="$b.bidi"/>
        <xsl:with-param name="prsR" select="$prsR"/>
      </xsl:call-template>
    </ins>
  </xsl:template>

  <xsl:template match="w:del">
    <xsl:param name="b.bidi"/>
    <xsl:param name="prsR"/>
    <xsl:if test="/w:document/w:settings/w:trackRevisions">
      <del>
        <xsl:call-template name="DisplayPContent">
          <xsl:with-param name="b.bidi" select="$b.bidi"/>
          <xsl:with-param name="prsR" select="$prsR"/>
        </xsl:call-template>
      </del>
    </xsl:if>
  </xsl:template>

  <xsl:template match="aml:annotation[@w:type='Word.Comment']">
    <xsl:variable name="id" select="@aml:id + 1"/>
    <a class="msocomanchor" id="_anchor_{$id}" onmouseover="msoCommentShow('_anchor_{$id}','_com_{$id}')" onmouseout="msoCommentHide('_com_{$id}')" href="#_msocom_{$id}" language="JavaScript" name="_msoanchor_{$id}">
      <xsl:value-of select="concat('[',@w:initials,$id,']')"/>
    </a>
  </xsl:template>

  <xsl:template name="DisplayAnnotationText">
    <xsl:variable name="id" select="@aml:id + 1"/>
    <div id="_com_{$id}" class="msocomtxt" language="JavaScript" onmouseover="msoCommentShow('_anchor_{$id}','_com_{$id}')" onmouseout="msoCommentHide('_com_{$id}')">
      <a name="_msocom_{$id}"></a>
      <a href="#_msoanchor_{$id}" class="msocomoff">
        <xsl:value-of select="concat('[',@w:initials,$id,']')"/>
      </a>
      <xsl:for-each select="aml:content">
        <xsl:call-template name="DisplayBodyContent">
          <xsl:with-param name="ns.content" select="*"/>
        </xsl:call-template>
      </xsl:for-each>
    </div>
  </xsl:template>

  <xsl:template name="DisplayAnnotationScript">
   <!-- <xsl:text disable-output-escaping="yes">&lt;![if !supportAnnotations]&gt;</xsl:text> -->
    <style id="dynCom" type="text/css"></style>
    <script type="text/javascript" language="JavaScript">
      <xsl:comment>
        <xsl:text disable-output-escaping="yes">
function msoCommentShow(anchor_id, com_id)
{
    if(msoBrowserCheck()) 
        {
        c = document.all(com_id);
        a = document.all(anchor_id);
        if (null != c &amp;&amp; null == c.length &amp;&amp; null != a &amp;&amp; null == a.length)
            {
            var cw = c.offsetWidth;
            var ch = c.offsetHeight;
            var aw = a.offsetWidth;
            var ah = a.offsetHeight;
            var x  = a.offsetLeft;
            var y  = a.offsetTop;
            var el = a;
            while (el.tagName != "BODY") 
                {
                el = el.offsetParent;
                x = x + el.offsetLeft;
                y = y + el.offsetTop;
                }
            var bw = document.body.clientWidth;
            var bh = document.body.clientHeight;
            var bsl = document.body.scrollLeft;
            var bst = document.body.scrollTop;
            if (x + cw + ah / 2 > bw + bsl &amp;&amp; x + aw - ah / 2 - cw >= bsl ) 
                { c.style.left = x + aw - ah / 2 - cw; }
            else 
                { c.style.left = x + ah / 2; }
            if (y + ch + ah / 2 > bh + bst &amp;&amp; y + ah / 2 - ch >= bst ) 
                { c.style.top = y + ah / 2 - ch; }
            else 
                { c.style.top = y + ah / 2; }
            c.style.visibility = "visible";
}    }    }
function msoCommentHide(com_id) 
{
    if(msoBrowserCheck())
        {
        c = document.all(com_id);
        if (null != c &amp;&amp; null == c.length)
        {
        c.style.visibility = "hidden";
        c.style.left = -1000;
        c.style.top = -1000;
        } } 
}
function msoBrowserCheck()
{
    ms = navigator.appVersion.indexOf("MSIE");
    vers = navigator.appVersion.substring(ms + 5, ms + 6);
    ie4 = (ms > 0) &amp;&amp; (parseInt(vers) >= 4);
    return ie4;
}
if (msoBrowserCheck())
{
    document.styleSheets.dynCom.addRule(".msocomanchor","background: infobackground");
    document.styleSheets.dynCom.addRule(".msocomoff","display: none");
    document.styleSheets.dynCom.addRule(".msocomtxt","visibility: hidden");
    document.styleSheets.dynCom.addRule(".msocomtxt","position: absolute");
    document.styleSheets.dynCom.addRule(".msocomtxt","top: -1000");
    document.styleSheets.dynCom.addRule(".msocomtxt","left: -1000");
    document.styleSheets.dynCom.addRule(".msocomtxt","width: 33%");
    document.styleSheets.dynCom.addRule(".msocomtxt","background: infobackground");
    document.styleSheets.dynCom.addRule(".msocomtxt","color: infotext");
    document.styleSheets.dynCom.addRule(".msocomtxt","border-top: 1pt solid threedlightshadow");
    document.styleSheets.dynCom.addRule(".msocomtxt","border-right: 2pt solid threedshadow");
    document.styleSheets.dynCom.addRule(".msocomtxt","border-bottom: 2pt solid threedshadow");
    document.styleSheets.dynCom.addRule(".msocomtxt","border-left: 1pt solid threedlightshadow");
    document.styleSheets.dynCom.addRule(".msocomtxt","padding: 3pt 3pt 3pt 3pt");
    document.styleSheets.dynCom.addRule(".msocomtxt","z-index: 100");
}
</xsl:text>
      </xsl:comment>
    </script>
    <!--<xsl:text disable-output-escaping="yes">&lt;![endif]&gt;</xsl:text>-->
  </xsl:template>

  <xsl:template name="copyElements">
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:param name="cellspacing"/>
    <xsl:param name="cellpadding.default"/>
    <xsl:param name="cellpadding.custom"/>
    <xsl:param name="bdr.top"/>
    <xsl:param name="bdr.left"/>
    <xsl:param name="bdr.bottom"/>
    <xsl:param name="bdr.right"/>
    <xsl:param name="bdr.insideV"/>
    <xsl:param name="bdr.insideH"/>
    <xsl:param name="thisRow"/>
    <xsl:param name="lastRow"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="b.bidivisual"/>
    <xsl:element name="{name()}" namespace="{namespace-uri()}">
      <xsl:for-each select="@*">
        <xsl:attribute name="{name()}" namespace="{namespace-uri()}">
          <xsl:value-of select="."/>
        </xsl:attribute>
      </xsl:for-each>
      <xsl:apply-templates>
        <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
        <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
        <xsl:with-param name="prsP" select="$prsP"/>
        <xsl:with-param name="prsR" select="$prsR"/>
        <xsl:with-param name="cellspacing" select="$cellspacing"/>
        <xsl:with-param name="cellpadding.default" select="$cellpadding.default"/>
        <xsl:with-param name="cellpadding.custom" select="$cellpadding.custom"/>
        <xsl:with-param name="bdr.top" select="$bdr.top"/>
        <xsl:with-param name="bdr.left" select="$bdr.left"/>
        <xsl:with-param name="bdr.bottom" select="$bdr.bottom"/>
        <xsl:with-param name="bdr.right" select="$bdr.right"/>
        <xsl:with-param name="bdr.insideV" select="$bdr.insideV"/>
        <xsl:with-param name="bdr.insideH" select="$bdr.insideH"/>
        <xsl:with-param name="thisRow" select="$thisRow"/>
        <xsl:with-param name="lastRow" select="$lastRow"/>
        <xsl:with-param name="cnfRow" select="$cnfRow"/>
        <xsl:with-param name="b.bidivisual" select="$b.bidivisual"/>
      </xsl:apply-templates>
    </xsl:element>
  </xsl:template>

  <xsl:template match="*">
    <xsl:param name="sTblStyleName"/>
    <xsl:param name="prsPAccum"/>
    <xsl:param name="prsP"/>
    <xsl:param name="prsR"/>
    <xsl:param name="cellspacing"/>
    <xsl:param name="cellpadding.default"/>
    <xsl:param name="cellpadding.custom"/>
    <xsl:param name="bdr.top"/>
    <xsl:param name="bdr.left"/>
    <xsl:param name="bdr.bottom"/>
    <xsl:param name="bdr.right"/>
    <xsl:param name="bdr.insideV"/>
    <xsl:param name="bdr.insideH"/>
    <xsl:param name="thisRow"/>
    <xsl:param name="lastRow"/>
    <xsl:param name="cnfRow"/>
    <xsl:param name="b.bidivisual"/>
    <xsl:call-template name="copyElements">
      <xsl:with-param name="sTblStyleName" select="$sTblStyleName"/>
      <xsl:with-param name="prsPAccum" select="$prsPAccum"/>
      <xsl:with-param name="prsP" select="$prsP"/>
      <xsl:with-param name="prsR" select="$prsR"/>
      <xsl:with-param name="cellspacing" select="$cellspacing"/>
      <xsl:with-param name="cellpadding.default" select="$cellpadding.default"/>
      <xsl:with-param name="cellpadding.custom" select="$cellpadding.custom"/>
      <xsl:with-param name="bdr.top" select="$bdr.top"/>
      <xsl:with-param name="bdr.left" select="$bdr.left"/>
      <xsl:with-param name="bdr.bottom" select="$bdr.bottom"/>
      <xsl:with-param name="bdr.right" select="$bdr.right"/>
      <xsl:with-param name="bdr.insideV" select="$bdr.insideV"/>
      <xsl:with-param name="bdr.insideH" select="$bdr.insideH"/>
      <xsl:with-param name="thisRow" select="$thisRow"/>
      <xsl:with-param name="lastRow" select="$lastRow"/>
      <xsl:with-param name="cnfRow" select="$cnfRow"/>
      <xsl:with-param name="b.bidivisual" select="$b.bidivisual"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="v:*">
    <xsl:choose>
      <xsl:when test=".//w10:wrap[@type='topAndBottom']">
        <o:wrapblock>
          <xsl:call-template name="copyElements"/>
        </o:wrapblock>
        <br style="mso-ignore:vglayout" clear='ALL'/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="copyElements"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="w:ruby">
    <ruby>

      <xsl:attribute name="lang">
        <xsl:value-of select="w:rubyPr/w:lid/@w:val" />
      </xsl:attribute>

      <xsl:attribute name="style">

        <xsl:variable name="align" select="w:rubyPr/w:rubyAlign/@w:val" />
        <xsl:text>ruby-align:</xsl:text>
        <xsl:choose>
          <xsl:when test="$align='rightVertical'">
            <xsl:text>auto</xsl:text>
          </xsl:when>
          <xsl:when test="$align='distributeLetter'">
            <xsl:text>distribute-letter</xsl:text>
          </xsl:when>
          <xsl:when test="$align='distributeSpace'">
            <xsl:text>distribute-space</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$align" />
          </xsl:otherwise>
        </xsl:choose>

      </xsl:attribute>

      <span>
        <xsl:if test="w:rubyPr/w:hpsBaseText">
          <xsl:attribute name="style">
            <xsl:text>font-size:</xsl:text>
            <xsl:value-of select="w:rubyPr/w:hpsBaseText/@w:val" />
            <xsl:text>pt;</xsl:text>
          </xsl:attribute>
        </xsl:if>

        <xsl:apply-templates select="w:rubyBase/w:r"/>
      </span>

      <rt>
        <span>
          <xsl:if test="w:rubyPr/w:hps">
            <xsl:attribute name="style">
              <xsl:text>font-size:</xsl:text>
              <xsl:value-of select="w:rubyPr/w:hps/@w:val div 2" />
              <xsl:text>pt;</xsl:text>
            </xsl:attribute>

            <xsl:apply-templates select="w:rt/w:r/w:t"/>
            <!--<xsl:apply-templates select="w:r/w:t"/>-->
          </xsl:if>
        </span>
      </rt>
    </ruby>
  </xsl:template>

  <xsl:template match="w:footnote">

    <xsl:variable name="me" select="." />
    <xsl:variable name="meInContext" select="ancestor::w:r[1]/*[count($me|descendant-or-self::*)=count(descendant-or-self::*)]" />
    <xsl:variable name="start">
      <xsl:choose>
        <xsl:when test="$ndDocPr/w:footnotePr/w:numStart">
          <xsl:value-of select="$ndDocPr/w:footnotePr/w:numStart/@w:val" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="1" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="position" select="count($meInContext/preceding::*[name()='w:footnote' and ancestor::w:body]) + $start" />

    <sup>
      <a>
        <xsl:attribute name="name">
          <xsl:value-of select="$footnoteRefLink" />
          <xsl:value-of select="$position" />
        </xsl:attribute>
        <xsl:attribute name="href"><xsl:text>#</xsl:text>
          <xsl:value-of select="$footnoteLink" />
          <xsl:value-of select="$position" />
        </xsl:attribute>
        <xsl:text>[</xsl:text>
        <xsl:value-of select="$position" />
        <xsl:text>]</xsl:text>
      </a>
    </sup>
  </xsl:template>

  <xsl:template match="w:endnote">

    <xsl:variable name="me" select="." />
    <xsl:variable name="meInContext" select="ancestor::w:r[1]/*[count($me|descendant-or-self::*)=count(descendant-or-self::*)]" />
    <xsl:variable name="start">
      <xsl:choose>
        <xsl:when test="$ndDocPr/w:endnotePr/w:numStart">
          <xsl:value-of select="$ndDocPr/w:endnotePr/w:numStart/@w:val" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="1" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="position" select="count($meInContext/preceding::*[name()='w:endnote' and ancestor::w:body]) + $start" />

    <sup>
      <a>
        <xsl:attribute name="name">
          <xsl:value-of select="$endnoteRefLink" />
          <xsl:value-of select="$position" />
        </xsl:attribute>
        <xsl:attribute name="href"><xsl:text>#</xsl:text>
          <xsl:value-of select="$endnoteLink" />
          <xsl:value-of select="$position" />
        </xsl:attribute>
        <xsl:text>[</xsl:text>
        <xsl:value-of select="$position" />
        <xsl:text>]</xsl:text>
      </a>
    </sup>
  </xsl:template>

  <xsl:template name="IsListBullet">

    <xsl:variable name="numId" select="w:numId/@w:val"/>
    <xsl:variable name="ilvl" select="w:ilvl/@w:val"/>
    <xsl:variable name="list" select="$ndLists/w:num[@w:numId=$numId][1]"/>

    <xsl:variable name="nfc">

      <xsl:choose>
        <xsl:when test="$ndLists/w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:lvl[@w:ilvl=$ilvl][1]">
          <xsl:for-each select="$ndLists/w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:lvl[@w:ilvl=$ilvl][1]">
            <xsl:choose>
              <xsl:when test="$list/w:lvlOverride[@w:ilvl=$ilvl]/w:numFmt">
                <xsl:value-of select="$list/w:lvlOverride[@w:ilvl=$ilvl]/w:numFmt/@w:val" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$ndLists/w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:lvl[@w:ilvl=$ilvl][1]/w:numFmt/@w:val" />
              </xsl:otherwise>
            </xsl:choose>
          </xsl:for-each>
        </xsl:when>

        <xsl:when test="$list/w:lvlOverride[@w:ilvl=$ilvl]">
          <xsl:for-each select="$list/w:lvlOverride[@w:ilvl=$ilvl]">
            <xsl:value-of select="w:numFmt/@w:val" />
          </xsl:for-each>
        </xsl:when>

        <xsl:when test="$ndLists/w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:listStyleLink">
          <xsl:variable name="linkedStyleId" select="$ndLists/w:abstractNum[@w:abstractNumId=$list/w:abstractNumId/@w:val][1]/w:listStyleLink/@w:val" />
          <xsl:variable name="linkedStyle" select="$nsStyles[@w:styleId=$linkedStyleId]" />
          <xsl:variable name="linkedList" select="w:num[@w:numId=$linkedStyle/w:pPr/w:numPr/w:numId/@w:val]" />
          <xsl:for-each select="$ndLists/w:abstractNum[@w:abstractNumId=$linkedList/w:abstractNumId/@w:val][1]/w:lvl[@w:ilvl=$ilvl][1]">
            <xsl:value-of select="w:numFmt/@w:val" />
          </xsl:for-each>
        </xsl:when>
      </xsl:choose>
    </xsl:variable>

    <xsl:if test="$nfc=$nfcBullet">
      <xsl:value-of select="$on" />
    </xsl:if>
  </xsl:template>

  <xsl:template match="w:fldSimple">
    <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="w:*"/>

  <xsl:template match="o:WordFieldCodes"/>

  <xsl:template match="w:cfChunk">
    <xsl:apply-templates />
  </xsl:template>

  <xsl:template match="w:sdt">
    <xsl:apply-templates />
  </xsl:template>

  <xsl:template match="w:sdtContent">
    <xsl:apply-templates />
  </xsl:template>

  <xsl:template match="w:smartTag">
    <xsl:apply-templates />
  </xsl:template>
  
  <!--this template is added now
  <xsl:template match="w:background" mode="rpr">
    bgcolor=<xsl:call-template name="ConvHexColor">
      <xsl:with-param name="value" select="@w:color"/>
    </xsl:call-template>;
  </xsl:template>
-->
  <xsl:template match="/w:document">

    <html>
      <head>
        <xsl:for-each select="$ndOfficeDocPr/o:HyperlinkBase[1]">
          <base href="{(.)}"/>
        </xsl:for-each>
        <xsl:call-template name="DisplayAnnotationScript"/>

        <!--<xsl:comment>-->
          <!--<xsl:text disable-output-escaping="yes">[if !mso]&gt;</xsl:text>-->
          <xsl:text disable-output-escaping="yes">&lt;style&gt;</xsl:text>
          <xsl:text>

                            v\:* {behavior:url(#default#VML);}
                            o\:* {behavior:url(#default#VML);}
                            w10\:* {behavior:url(#default#VML);}
                            .shape {behavior:url(#default#VML);}
                        </xsl:text>

          <xsl:text disable-output-escaping="yes">&lt;/style&gt;</xsl:text>
          <!--<xsl:text disable-output-escaping="yes">&lt;![endif]</xsl:text>-->
       <!-- </xsl:comment> -->

        <style>
       <!--   <xsl:comment> -->

            <xsl:apply-templates select="w:fonts[1]/w:font"/>

            del {text-decoration:line-through;color:red;}
            <xsl:choose>
              <xsl:when test="/w:document/w:settings/w:trackRevisions">
                ins {text-decoration:underline;color:teal;}
              </xsl:when>
              <xsl:otherwise>
                ins {text-decoration:none;}
              </xsl:otherwise>
            </xsl:choose>

            <xsl:apply-templates select="a:theme/a:themeElements/a:fontScheme"/>

            <xsl:apply-templates select="$nsStyles"/>

         <!-- </xsl:comment> -->
        </style>
      </head>

      <body>
        <xsl:if test="w:background/@w:color">
          <xsl:variable name="color"> <!-- Added by Parwati to handle Background Page Color-->
            <xsl:text>#</xsl:text>
          </xsl:variable>
          <xsl:attribute name="bgcolor">
            <xsl:value-of select="$color"/>
            <xsl:value-of select="w:background/@w:color"/>
          </xsl:attribute>
        </xsl:if>
        <!-- Added for column rendering by Parwati
        <xsl:if test="//w:body//w:sectPr//w:cols[@w:num] | //w:body/w:p/w:pPr/w:sectPr/w:cols[@w:num] | //w:body/w:sdt/w:sdtContent">
          <xsl:text disable-output-escaping="yes">&lt;table cellpadding="5px"&gt;</xsl:text>
          <xsl:text disable-output-escaping="yes">&lt;tr&gt;</xsl:text>
          <xsl:text disable-output-escaping="yes">&lt;td valign="top"&gt;</xsl:text>
        </xsl:if>
            End of change by Parwati -->   
        <xsl:apply-templates select="w:body|w:cfChunk"/>
        
        <xsl:if test="//v:background">
          <xsl:for-each select="//v:background[1]">
            <xsl:call-template name="copyElements" />
          </xsl:for-each>
        </xsl:if>

        <xsl:for-each select="//aml:annotation[@w:type='Word.Comment']">
          <xsl:call-template name="DisplayAnnotationText"/>
        </xsl:for-each>

        <xsl:if test="//w:body//w:footnote">
          <xsl:variable name="start">
            <xsl:choose>
              <xsl:when test="$ndDocPr/w:footnotePr/w:numStart">
                <xsl:value-of select="$ndDocPr/w:footnotePr/w:numStart/@w:val" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="0" />
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <hr align="left" size="1" width="33%" />
          <xsl:for-each select="//w:body//w:footnote">
            <a>
              <xsl:attribute name="href">
                <xsl:text>#</xsl:text>
                <xsl:value-of select="$footnoteRefLink" />
                <xsl:value-of select="position() + $start" />
              </xsl:attribute>
              <xsl:attribute name="target">
                <xsl:text>_self</xsl:text>
              </xsl:attribute>
              <xsl:attribute name="name">
                <xsl:value-of select="$footnoteLink" />
                <xsl:value-of select="position() + $start" />
              </xsl:attribute>
              <xsl:text>[</xsl:text>
              <xsl:value-of select="position() + $start" />
              <xsl:text>]</xsl:text>
            </a>
            <xsl:apply-templates select="*" />
          </xsl:for-each>
        </xsl:if>

        <xsl:if test="//w:body//w:endnote">
          <xsl:variable name="start">
            <xsl:choose>
              <xsl:when test="$ndDocPr/w:endnotePr/w:numStart">
                <xsl:value-of select="$ndDocPr/w:endnotePr/w:numStart/@w:val" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="0" />
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <hr align="left" size="1" width="33%" />
          <xsl:for-each select="//w:body//w:endnote">
            <a>
              <xsl:attribute name="href">
                <xsl:text>#</xsl:text>
                <xsl:value-of select="$endnoteRefLink" />
                <xsl:value-of select="position() + $start" />
              </xsl:attribute>
              <xsl:attribute name="target">
                <xsl:text>_self</xsl:text>
              </xsl:attribute>
              <xsl:attribute name="name">
                <xsl:value-of select="$endnoteLink" />
                <xsl:value-of select="position() + $start" />
              </xsl:attribute>
              <xsl:text>[</xsl:text>
              <xsl:value-of select="position() + $start" />
              <xsl:text>]</xsl:text>
            </a>
            <xsl:apply-templates select="*" />
          </xsl:for-each>
        </xsl:if>
            <!-- Added for column rendering by Parwati
        <xsl:if test="//w:body/w:sectPr/w:cols/@w:num | //w:body/w:p/w:pPr/w:sectPr/w:cols[@w:num] | //w:body/w:sdt/w:sdtContent">
          <xsl:text disable-output-escaping="yes">&lt;/td&gt;</xsl:text>
          <xsl:text disable-output-escaping="yes">&lt;/tr&gt;</xsl:text>
          <xsl:text disable-output-escaping="yes">&lt;/table&gt;</xsl:text>          
        </xsl:if>           
         End of change by Parwati -->  
        <!--<xsl:if test="//w:body//w:sdt//w:sdtContent and //w:body/w:p/w:r/w:lastRenderedPageBreak"> --><!-- For coverpage Added by Parwati--><!--
          <xsl:text disable-output-escaping="yes">&lt;/div&gt;</xsl:text>
        </xsl:if>-->
      </body>
    </html>
  </xsl:template>

  <xsl:template match="w:drawing">
   <xsl:variable name="w">
    <xsl:value-of select=".//wp:extent/@cx"/>
   </xsl:variable>

   <xsl:variable name="h">
    <xsl:value-of select=".//wp:extent/@cy"/>
   </xsl:variable>

   <img src="?image={.//a:blip/@r:embed[1]}">

     <xsl:attribute name="width">
       <xsl:value-of select="number($w) div 9525"/>px
     </xsl:attribute>

     <xsl:attribute name="height">
       <xsl:value-of select="number($h) div 9525"/>px
     </xsl:attribute>
   </img>
  </xsl:template>

  <xsl:template match="w:customXml">
    <xsl:apply-templates select="*"/>
  </xsl:template>


  <xsl:template match="/">
    <!--ADDED for Equations -->
    <xsl:if test="$pmathml">
      <xsl:processing-instruction name="xml-stylesheet"
        >type="text/xsl" href="<xsl:value-of select="$pmathml"/>"</xsl:processing-instruction>
    </xsl:if>
    <xsl:if test="$dtd">
      <xsl:text disable-output-escaping="yes"><![CDATA[
<!DOCTYPE html SYSTEM "]]></xsl:text>
      <xsl:value-of select="$dtd"/>
      <xsl:text disable-output-escaping="yes"><![CDATA[" [
<!ENTITY % MATHML.prefixed      "INCLUDE" >
<!ENTITY % MATHML.prefix        "mml" >
]>
]]>
      </xsl:text>
    </xsl:if>
    <!--ADDED for Equations -->
    <xsl:apply-templates select="*"/>
  </xsl:template>
  
  
  
  <!--ADDED FOR MATHML SUPPORT BY SHUBHA -->
  
  
  <!-- Every single unicode character that is recognized by OMML as an operator -->
  <xsl:variable name="sOperators"
    select="concat(
    '&#x0021;&#x0022;&#x0028;&#x0029;&#x002B;&#x002C;&#x002D;&#x002F;&#x2AFE;&#x003A;&#x003B;&#x003C;',
    '&#x003D;&#x003E;&#x003F;&#x005B;&#x005C;&#x005D;&#x007B;&#x007C;&#x007D;&#x00A1;&#x00AC;&#x00B1;',
    '&#x00B7;&#x00BF;&#x00D7;&#x00F7;&#x2000;&#x2001;&#x2002;&#x2003;&#x2004;&#x2005;&#x2006;&#x2009;',
    '&#x200A;&#x2010;&#x2012;&#x2013;&#x2014;&#x2016;&#x2020;&#x2021;&#x2022;&#x2024;&#x2025;&#x2026;',
    '&#x203C;&#x2040;&#x204E;&#x204F;&#x2050;&#x205f;&#x2061;&#x2062;&#x2063;&#x2140;&#x2190;&#x2191;',
    '&#x2192;&#x2193;&#x2194;&#x2195;&#x2196;&#x2197;&#x2198;&#x2199;&#x219A;&#x219B;&#x219C;&#x219D;',
    '&#x219E;&#x219F;&#x21A0;&#x21A1;&#x21A2;&#x21A3;&#x21A4;&#x21A5;&#x21A6;&#x21A7;&#x21A8;&#x21A9;',
    '&#x21AA;&#x21AB;&#x21AC;&#x21AD;&#x21AE;&#x21AF;&#x21B0;&#x21B1;&#x21B2;&#x21B3;&#x21B6;&#x21B7;',
    '&#x21BA;&#x21BB;&#x21BC;&#x21BD;&#x21BE;&#x21BF;&#x21C0;&#x21C1;&#x21C2;&#x21C3;&#x21C4;&#x21C5;',
    '&#x21C6;&#x21C7;&#x21C8;&#x21C9;&#x21CA;&#x21CB;&#x21CC;&#x21CD;&#x21CE;&#x21CF;&#x21D0;&#x21D1;',
    '&#x21D2;&#x21D3;&#x21D4;&#x21D5;&#x21D6;&#x21D7;&#x21D8;&#x21D9;&#x21DA;&#x21DB;&#x21DC;&#x21DD;',
    '&#x21DE;&#x21DF;&#x21E0;&#x21E1;&#x21E2;&#x21E3;&#x21E4;&#x21E5;&#x21E6;&#x21E7;&#x21E8;&#x21E9;',
    '&#x21F4;&#x21F5;&#x21F6;&#x21F7;&#x21F8;&#x21F9;&#x21FA;&#x21FB;&#x21FC;&#x21FD;&#x21FE;&#x21FF;',
    '&#x2200;&#x2201;&#x2202;&#x2203;&#x2204;&#x2206;&#x2207;&#x2208;&#x2209;&#x220A;&#x220B;&#x220C;',
    '&#x220D;&#x220F;&#x2210;&#x2211;&#x2212;&#x2213;&#x2214;&#x2215;&#x2216;&#x2217;&#x2218;&#x2219;',
    '&#x221A;&#x221B;&#x221C;&#x221D;&#x2223;&#x2224;&#x2225;&#x2226;&#x2227;&#x2228;&#x2229;&#x222A;',
    '&#x222B;&#x222C;&#x222D;&#x222E;&#x222F;&#x2230;&#x2231;&#x2232;&#x2233;&#x2234;&#x2235;&#x2236;',
    '&#x2237;&#x2238;&#x2239;&#x223A;&#x223B;&#x223C;&#x223D;&#x223E;&#x2240;&#x2241;&#x2242;&#x2243;',
    '&#x2244;&#x2245;&#x2246;&#x2247;&#x2248;&#x2249;&#x224A;&#x224B;&#x224C;&#x224D;&#x224E;&#x224F;',
    '&#x2250;&#x2251;&#x2252;&#x2253;&#x2254;&#x2255;&#x2256;&#x2257;&#x2258;&#x2259;&#x225A;&#x225B;',
    '&#x225C;&#x225D;&#x225E;&#x225F;&#x2260;&#x2261;&#x2262;&#x2263;&#x2264;&#x2265;&#x2266;&#x2267;',
    '&#x2268;&#x2269;&#x226A;&#x226B;&#x226C;&#x226D;&#x226E;&#x226F;&#x2270;&#x2271;&#x2272;&#x2273;',
    '&#x2274;&#x2275;&#x2276;&#x2277;&#x2278;&#x2279;&#x227A;&#x227B;&#x227C;&#x227D;&#x227E;&#x227F;',
    '&#x2280;&#x2281;&#x2282;&#x2283;&#x2284;&#x2285;&#x2286;&#x2287;&#x2288;&#x2289;&#x228A;&#x228B;',
    '&#x228C;&#x228D;&#x228E;&#x228F;&#x2290;&#x2291;&#x2292;&#x2293;&#x2294;&#x2295;&#x2296;&#x2297;',
    '&#x2298;&#x2299;&#x229A;&#x229B;&#x229C;&#x229D;&#x229E;&#x229F;&#x22A0;&#x22A1;&#x22A2;&#x22A3;',
    '&#x22A5;&#x22A6;&#x22A7;&#x22A8;&#x22A9;&#x22AA;&#x22AB;&#x22AC;&#x22AD;&#x22AE;&#x22AF;&#x22B0;',
    '&#x22B1;&#x22B2;&#x22B3;&#x22B4;&#x22B5;&#x22B6;&#x22B7;&#x22B8;&#x22B9;&#x22BA;&#x22BB;&#x22BC;',
    '&#x22BD;&#x22C0;&#x22C1;&#x22C2;&#x22C3;&#x22C4;&#x22C5;&#x22C6;&#x22C7;&#x22C8;&#x22C9;&#x22CA;',
    '&#x22CB;&#x22CC;&#x22CD;&#x22CE;&#x22CF;&#x22D0;&#x22D1;&#x22D2;&#x22D3;&#x22D4;&#x22D5;&#x22D6;',
    '&#x22D7;&#x22D8;&#x22D9;&#x22DA;&#x22DB;&#x22DC;&#x22DD;&#x22DE;&#x22DF;&#x22E0;&#x22E1;&#x22E2;',
    '&#x22E3;&#x22E4;&#x22E5;&#x22E6;&#x22E7;&#x22E8;&#x22E9;&#x22EA;&#x22EB;&#x22EC;&#x22ED;&#x22EE;',
    '&#x22EF;&#x22F0;&#x22F1;&#x22F2;&#x22F3;&#x22F4;&#x22F5;&#x22F6;&#x22F7;&#x22F8;&#x22F9;&#x22FA;',
    '&#x22FB;&#x22FC;&#x22FD;&#x22FE;&#x22FF;&#x2305;&#x2306;&#x2308;&#x2309;&#x230A;&#x230B;&#x231C;',
    '&#x231D;&#x231E;&#x231F;&#x2322;&#x2323;&#x2329;&#x232A;&#x233D;&#x233F;&#x23B0;&#x23B1;&#x25B2;',
    '&#x25B3;&#x25B4;&#x25B5;&#x25B6;&#x25B7;&#x25B8;&#x25B9;&#x25BC;&#x25BD;&#x25BE;&#x25BF;&#x25C0;',
    '&#x25C1;&#x25C2;&#x25C3;&#x25C4;&#x25C5;&#x25CA;&#x25CB;&#x25E6;&#x25EB;&#x25EC;&#x25F8;&#x25F9;',
    '&#x25FA;&#x25FB;&#x25FC;&#x25FD;&#x25FE;&#x25FF;&#x2605;&#x2606;&#x2772;&#x2773;&#x27D1;&#x27D2;',
    '&#x27D3;&#x27D4;&#x27D5;&#x27D6;&#x27D7;&#x27D8;&#x27D9;&#x27DA;&#x27DB;&#x27DC;&#x27DD;&#x27DE;',
    '&#x27DF;&#x27E0;&#x27E1;&#x27E2;&#x27E3;&#x27E4;&#x27E5;&#x27E6;&#x27E7;&#x27E8;&#x27E9;&#x27EA;',
    '&#x27EB;&#x27F0;&#x27F1;&#x27F2;&#x27F3;&#x27F4;&#x27F5;&#x27F6;&#x27F7;&#x27F8;&#x27F9;&#x27FA;',
    '&#x27FB;&#x27FC;&#x27FD;&#x27FE;&#x27FF;&#x2900;&#x2901;&#x2902;&#x2903;&#x2904;&#x2905;&#x2906;',
    '&#x2907;&#x2908;&#x2909;&#x290A;&#x290B;&#x290C;&#x290D;&#x290E;&#x290F;&#x2910;&#x2911;&#x2912;',
    '&#x2913;&#x2914;&#x2915;&#x2916;&#x2917;&#x2918;&#x2919;&#x291A;&#x291B;&#x291C;&#x291D;&#x291E;',
    '&#x291F;&#x2920;&#x2921;&#x2922;&#x2923;&#x2924;&#x2925;&#x2926;&#x2927;&#x2928;&#x2929;&#x292A;',
    '&#x292B;&#x292C;&#x292D;&#x292E;&#x292F;&#x2930;&#x2931;&#x2932;&#x2933;&#x2934;&#x2935;&#x2936;',
    '&#x2937;&#x2938;&#x2939;&#x293A;&#x293B;&#x293C;&#x293D;&#x293E;&#x293F;&#x2940;&#x2941;&#x2942;',
    '&#x2943;&#x2944;&#x2945;&#x2946;&#x2947;&#x2948;&#x2949;&#x294A;&#x294B;&#x294C;&#x294D;&#x294E;',
    '&#x294F;&#x2950;&#x2951;&#x2952;&#x2953;&#x2954;&#x2955;&#x2956;&#x2957;&#x2958;&#x2959;&#x295A;',
    '&#x295B;&#x295C;&#x295D;&#x295E;&#x295F;&#x2960;&#x2961;&#x2962;&#x2963;&#x2964;&#x2965;&#x2966;',
    '&#x2967;&#x2968;&#x2969;&#x296A;&#x296B;&#x296C;&#x296D;&#x296E;&#x296F;&#x2970;&#x2971;&#x2972;',
    '&#x2973;&#x2974;&#x2975;&#x2976;&#x2977;&#x2978;&#x2979;&#x297A;&#x297B;&#x297C;&#x297D;&#x297E;',
    '&#x297F;&#x2980;&#x2982;&#x2983;&#x2984;&#x2985;&#x2986;&#x2987;&#x2988;&#x2989;&#x298A;&#x298B;',
    '&#x298C;&#x298D;&#x298E;&#x298F;&#x2990;&#x2991;&#x2992;&#x2993;&#x2994;&#x2995;&#x2996;&#x2997;',
    '&#x2998;&#x2999;&#x299A;&#x29B6;&#x29B7;&#x29B8;&#x29B9;&#x29C0;&#x29C1;&#x29C4;&#x29C5;&#x29C6;',
    '&#x29C7;&#x29C8;&#x29CE;&#x29CF;&#x29D0;&#x29D1;&#x29D2;&#x29D3;&#x29D4;&#x29D5;&#x29D6;&#x29D7;',
    '&#x29D8;&#x29D9;&#x29DA;&#x29DB;&#x29DF;&#x29E1;&#x29E2;&#x29E3;&#x29E4;&#x29E5;&#x29E6;&#x29EB;',
    '&#x29F4;&#x29F5;&#x29F6;&#x29F7;&#x29F8;&#x29F9;&#x29FA;&#x29FB;&#x29FC;&#x29FD;&#x29FE;&#x29FF;',
    '&#x2A00;&#x2A01;&#x2A02;&#x2A03;&#x2A04;&#x2A05;&#x2A06;&#x2A07;&#x2A08;&#x2A09;&#x2A0A;&#x2A0B;',
    '&#x2A0C;&#x2A0D;&#x2A0E;&#x2A0F;&#x2A10;&#x2A11;&#x2A12;&#x2A13;&#x2A14;&#x2A15;&#x2A16;&#x2A17;',
    '&#x2A18;&#x2A19;&#x2A1A;&#x2A1B;&#x2A1C;&#x2A1D;&#x2A1E;&#x2A1F;&#x2A20;&#x2A21;&#x2A22;&#x2A23;',
    '&#x2A24;&#x2A25;&#x2A26;&#x2A27;&#x2A28;&#x2A29;&#x2A2A;&#x2A2B;&#x2A2C;&#x2A2D;&#x2A2E;&#x2A2F;',
    '&#x2A30;&#x2A31;&#x2A32;&#x2A33;&#x2A34;&#x2A35;&#x2A36;&#x2A37;&#x2A38;&#x2A39;&#x2A3A;&#x2A3B;',
    '&#x2A3C;&#x2A3D;&#x2A3E;&#x2A3F;&#x2A40;&#x2A41;&#x2A42;&#x2A43;&#x2A44;&#x2A45;&#x2A46;&#x2A47;',
    '&#x2A48;&#x2A49;&#x2A4A;&#x2A4B;&#x2A4C;&#x2A4D;&#x2A4E;&#x2A4F;&#x2A50;&#x2A51;&#x2A52;&#x2A53;',
    '&#x2A54;&#x2A55;&#x2A56;&#x2A57;&#x2A58;&#x2A59;&#x2A5A;&#x2A5B;&#x2A5C;&#x2A5D;&#x2A5E;&#x2A5F;',
    '&#x2A60;&#x2A61;&#x2A62;&#x2A63;&#x2A64;&#x2A65;&#x2A66;&#x2A67;&#x2A68;&#x2A69;&#x2A6A;&#x2A6B;',
    '&#x2A6C;&#x2A6D;&#x2A6E;&#x2A6F;&#x2A70;&#x2A71;&#x2A72;&#x2A73;&#x2A74;&#x2A75;&#x2A76;&#x2A77;',
    '&#x2A78;&#x2A79;&#x2A7A;&#x2A7B;&#x2A7C;&#x2A7D;&#x2A7E;&#x2A7F;&#x2A80;&#x2A81;&#x2A82;&#x2A83;',
    '&#x2A84;&#x2A85;&#x2A86;&#x2A87;&#x2A88;&#x2A89;&#x2A8A;&#x2A8B;&#x2A8C;&#x2A8D;&#x2A8E;&#x2A8F;',
    '&#x2A90;&#x2A91;&#x2A92;&#x2A93;&#x2A94;&#x2A95;&#x2A96;&#x2A97;&#x2A98;&#x2A99;&#x2A9A;&#x2A9B;',
    '&#x2A9C;&#x2A9D;&#x2A9E;&#x2A9F;&#x2AA0;&#x2AA1;&#x2AA2;&#x2AA3;&#x2AA4;&#x2AA5;&#x2AA6;&#x2AA7;',
    '&#x2AA8;&#x2AA9;&#x2AAA;&#x2AAB;&#x2AAC;&#x2AAD;&#x2AAE;&#x2AAF;&#x2AB0;&#x2AB1;&#x2AB2;&#x2AB3;',
    '&#x2AB4;&#x2AB5;&#x2AB6;&#x2AB7;&#x2AB8;&#x2AB9;&#x2ABA;&#x2ABB;&#x2ABC;&#x2ABD;&#x2ABE;&#x2ABF;',
    '&#x2AC0;&#x2AC1;&#x2AC2;&#x2AC3;&#x2AC4;&#x2AC5;&#x2AC6;&#x2AC7;&#x2AC8;&#x2AC9;&#x2ACA;&#x2ACB;',
    '&#x2ACC;&#x2ACD;&#x2ACE;&#x2ACF;&#x2AD0;&#x2AD1;&#x2AD2;&#x2AD3;&#x2AD4;&#x2AD5;&#x2AD6;&#x2AD7;',
    '&#x2AD8;&#x2AD9;&#x2ADA;&#x2ADB;&#x2ADC;&#x2ADD;&#x2ADE;&#x2ADF;&#x2AE0;&#x2AE2;&#x2AE3;&#x2AE4;',
    '&#x2AE5;&#x2AE6;&#x2AE7;&#x2AE8;&#x2AE9;&#x2AEA;&#x2AEB;&#x2AEC;&#x2AED;&#x2AEE;&#x2AEF;&#x2AF0;',
    '&#x2AF2;&#x2AF3;&#x2AF4;&#x2AF5;&#x2AF6;&#x2AF7;&#x2AF8;&#x2AF9;&#x2AFA;&#x2AFB;&#x2AFC;&#x2AFD;')" />
 
  <!-- A string of '-'s repeated exactly as many times as the operators above -->
  <xsl:variable name="sMinuses">
    <xsl:call-template name="SRepeatChar">
      <xsl:with-param name="cchRequired" select="string-length($sOperators)" />
      <xsl:with-param name="ch" select="'-'" />
    </xsl:call-template>
  </xsl:variable>
  
  <!-- Every single unicode character that is recognized by OMML as a number -->
  <xsl:variable name="sNumbers" select="'0123456789'"/>
  
  <!-- A string of '0's repeated exactly as many times as the list of numbers above -->
  <xsl:variable name="sOnes">
    <xsl:call-template name="SRepeatChar">
      <xsl:with-param name="cchRequired" select="string-length($sNumbers)" />
      <xsl:with-param name="ch" select="'1'" />
    </xsl:call-template>
  </xsl:variable>	
  
  <!-- %%Template: SReplace
    
    Replace all occurences of sOrig in sInput with sReplacement
    and return the resulting string. -->
  <xsl:template name="SReplace">
    <xsl:param name="sInput" />
    <xsl:param name="sOrig" />
    <xsl:param name="sReplacement" />
    
    <xsl:choose>
      <xsl:when test="not(contains($sInput, $sOrig))">
        <xsl:value-of select="$sInput" />
      </xsl:when>
      <xsl:otherwise>
        <xsl:variable name="sBefore" select="substring-before($sInput, $sOrig)" />
        <xsl:variable name="sAfter" select="substring-after($sInput, $sOrig)" />
        <xsl:variable name="sAfterProcessed">
          <xsl:call-template name="SReplace">
            <xsl:with-param name="sInput" select="$sAfter" />
            <xsl:with-param name="sOrig" select="$sOrig" />
            <xsl:with-param name="sReplacement" select="$sReplacement" />
          </xsl:call-template>
        </xsl:variable>
        
        <xsl:value-of select="concat($sBefore, concat($sReplacement, $sAfterProcessed))" /> 
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>	
  
  <!--  -->
  <xsl:template match="m:e | m:den | m:num | m:lim | m:sup | m:sub">
    <xsl:choose>
      
      <!-- If there is no scriptLevel speified, just call through -->
      <xsl:when test="not(m:argPr[last()]/m:scrLvl/@m:val)">
        <!-- DPC make sure only one element returned -->
        <mml:mrow><xsl:apply-templates select="*" /></mml:mrow>
      </xsl:when>
      
      <!-- Otherwise, create an mstyle and set the script level -->
      <xsl:otherwise>
        <mml:mstyle>
          <xsl:attribute name="scriptlevel">
            <xsl:value-of select="m:argPr[last()]/m:scrLvl/@m:val" />
          </xsl:attribute>
          <xsl:apply-templates select="*" />
        </mml:mstyle>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!--
    MS stylesheet uses DOE which is (a) unevil, (b) non necessary, and (c) breaks the pass through a temporay tree to get rid of namespace nodes.
    repeat the templates here without doe (and without double quoting amp)
  -->
  <xsl:template match="m:nary">
    <xsl:variable name="sLowerCaseSubHide">
      <xsl:choose>
        <xsl:when test="count(m:naryPr[last()]/m:subHide) = 0">
          <xsl:text>off</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="translate(m:naryPr[last()]/m:subHide/@m:val, 
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
            'abcdefghijklmnopqrstuvwxyz')" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="sLowerCaseSupHide">
      <xsl:choose>
        <xsl:when test="count(m:naryPr[last()]/m:supHide) = 0">
          <xsl:text>off</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="translate(m:naryPr[last()]/m:supHide/@m:val, 
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
            'abcdefghijklmnopqrstuvwxyz')" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="not($sLowerCaseSupHide='off') and 
        not($sLowerCaseSubHide='off')">
        <mml:mo>
          <xsl:choose>
            <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
              m:naryPr[last()]/m:chr/@m:val=''">
              <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
            </xsl:otherwise>
          </xsl:choose>
        </mml:mo>
      </xsl:when>
      <xsl:when test="not($sLowerCaseSubHide='off')">
        <xsl:choose>
          <xsl:when test="m:naryPr[last()]/m:limLoc/@m:val='subSup'">
            <mml:msup>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sup[1]" />
            </mml:msup>
          </xsl:when>
          <xsl:otherwise>
            <mml:mover>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sup[1]" />
            </mml:mover>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:when test="not($sLowerCaseSupHide='off')">
        <xsl:choose>
          <xsl:when test="m:naryPr[last()]/m:limLoc/@m:val='subSup'">
            <mml:msub>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sub[1]" />
            </mml:msub>
          </xsl:when>
          <xsl:otherwise>
            <mml:munder>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sub[1]" />
            </mml:munder>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="m:naryPr[last()]/m:limLoc/@m:val='subSup'">
            <mml:msubsup>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sub[1]" />
              <xsl:apply-templates select="m:sup[1]" />
            </mml:msubsup>
          </xsl:when>
          <xsl:otherwise>
            <mml:munderover>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sub[1]" />
              <xsl:apply-templates select="m:sup[1]" />
            </mml:munderover>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
    <mml:mrow>
      <xsl:apply-templates select="m:e[1]" />
    </mml:mrow>
  </xsl:template>
  
  
  <!--<xsl:template name="CreateGroupChr">
    <xsl:variable name="sLowerCasePos" select="translate(m:groupChrPr[last()]/m:pos/@m:val, 
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCasePos!='top' or 
        not(m:groupChrPr[last()]/m:pos/@m:val)   or
        m:groupChrPr[last()]/m:pos/@m:val=''">
        <mml:munder>
          <xsl:apply-templates select="m:e[1]" />
          <mml:mo>
            <xsl:choose>
              <xsl:when test="string-length(m:groupChrPr[last()]/m:chr/@m:val) &gt;= 1">
                <xsl:value-of select="substring(m:groupChrPr[last()]/m:chr/@m:val,1,1)" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:text disable-output-escaping="no">&#x023DF;</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </mml:mo>
        </mml:munder>
      </xsl:when>
      <xsl:otherwise>
        <mml:mover>
          <xsl:apply-templates select="m:e[1]" />
          <mml:mo>
            <xsl:choose>
              <xsl:when test="string-length(m:groupChrPr[last()]/m:chr/@m:val) &gt;= 1">
                <xsl:value-of select="substring(m:groupChrPr[last()]/m:chr/@m:val,1,1)" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:text disable-output-escaping="no">&#x023DF;</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </mml:mo>
        </mml:mover>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>-->
  <xsl:template match="m:sSub">
    <mml:msub>
      <mml:mrow>
        <xsl:apply-templates select="m:e[1]" />
      </mml:mrow>
      <mml:mrow>
        <xsl:apply-templates select="m:sub[1]" />
      </mml:mrow>
    </mml:msub>		
  </xsl:template>
  
  <xsl:template match="m:sSup">
    <mml:msup>
      <mml:mrow>
        <xsl:apply-templates select="m:e[1]" />
      </mml:mrow>
      <mml:mrow>
        <xsl:apply-templates select="m:sup[1]" />
      </mml:mrow>
    </mml:msup>		
  </xsl:template>
  
  <xsl:template match="m:sSubSup">
    <mml:msubsup>
      <mml:mrow>
        <xsl:apply-templates select="m:e[1]" />
      </mml:mrow>
      <mml:mrow>
        <xsl:apply-templates select="m:sub[1]" />
      </mml:mrow>
      <mml:mrow>
        <xsl:apply-templates select="m:sup[1]" />
      </mml:mrow>
    </mml:msubsup>
  </xsl:template>
  
  <xsl:template match="m:groupChr">
    <xsl:variable name="sLowerCaseOpEmu" select="translate(m:groupChrPr[last()]/m:opEmu/@m:val, 
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCaseOpEmu='on'">
        <mml:mrow>
          <xsl:call-template name="CreateGroupChr" />
        </mml:mrow>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="CreateGroupChr" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  
  <!--Display cos,sin -->
  <xsl:template name="fName">
    <xsl:for-each select="m:fName/*">
      <xsl:apply-templates select="." />
    </xsl:for-each>
  </xsl:template>
  
  <xsl:template match="m:func">
    <mml:mrow>
      <mml:mrow>
        <xsl:call-template name="fName" />
      </mml:mrow>
      <mml:mo>&#x02061;</mml:mo>
      <xsl:apply-templates select="*[position() &gt; 1]" />
    </mml:mrow>
  </xsl:template>
  
  <!-- %%Template: match m:f 
    
    m:f maps directly to mfrac. 
  -->
  <xsl:template match="m:f">
    <mml:mfrac>
      <xsl:call-template name="CreateMathMLFracProp">
        <xsl:with-param name="type" select="m:fPr[last()]/m:type/@m:val" />
        <xsl:with-param name="baseJc" select="m:fPr[last()]/m:baseJc/@m:val" />
        <xsl:with-param name="numJc" select="m:fPr[last()]/m:numJc/@m:val" />
        <xsl:with-param name="denJc" select="m:fPr[last()]/m:type/@m:val" />
      </xsl:call-template>
      
      <mml:mrow><xsl:apply-templates select="m:num[1]" /></mml:mrow>
      <mml:mrow><xsl:apply-templates select="m:den[1]" /></mml:mrow>
    </mml:mfrac>
  </xsl:template>
  
  <!-- %%Template: CreateMathMLFracProp 
    
    Make fraction properties based on supplied parameters.
    OMML differentiates between a linear fraction and a skewed
    one. For MathML, we write both as bevelled.
  -->
  <xsl:template name="CreateMathMLFracProp">
    <xsl:param name="type" />
    <xsl:param name="baseJc" />
    <xsl:param name="numJc" />
    <xsl:param name="denJc" />
    <xsl:variable name="sLowerCaseType" select="translate($type, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:variable name="sLowerCaseNumJc" select="translate($numJc, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:variable name="sLowerCaseDenJc" select="translate($denJc, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')" />
    
    <xsl:if test="$sLowerCaseType='skw' or $sLowerCaseType='lin'">
      <xsl:attribute name="bevelled">true</xsl:attribute>
    </xsl:if>
    <xsl:if test="$sLowerCaseType='nobar'">
      <xsl:attribute name="linethickness">0pt</xsl:attribute>
    </xsl:if>
    <xsl:choose>
      <xsl:when test="sLowerCaseNumJc='right'">
        <xsl:attribute name="numalign">right</xsl:attribute>
      </xsl:when>
      <xsl:when test="sLowerCaseNumJc='left'">
        <xsl:attribute name="numalign">left</xsl:attribute>
      </xsl:when>
    </xsl:choose>
    <xsl:choose>
      <xsl:when test="sLowerCaseDenJc='right'">
        <xsl:attribute name="numalign">right</xsl:attribute>
      </xsl:when>
      <xsl:when test="sLowerCaseDenJc='left'">
        <xsl:attribute name="numalign">left</xsl:attribute>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="m:bar">
    <xsl:variable name="sLowerCasePos" select="translate(m:barPr/m:pos/@m:val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCasePos!='bot' or 
        not($sLowerCasePos)   or
        $sLowerCasePos=''   ">
        <mml:mover>
          <xsl:attribute name="accent">true</xsl:attribute>
          <xsl:apply-templates select="m:e[1]" />
          <mml:mo>
            <xsl:text disable-output-escaping="yes">&amp;#x000AF;</xsl:text>
          </mml:mo>
        </mml:mover>
      </xsl:when>
      <xsl:otherwise>
        <mml:munder>
          <xsl:apply-templates select="m:e[1]" />
          <mml:mo>
            <xsl:text disable-output-escaping="yes">&amp;#x00332;</xsl:text>
          </mml:mo>
        </mml:munder>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- %%Template match m:d
    
    Process a delimiter. 
  -->
  <xsl:template match="m:d">
    <mml:mfenced>		
      <!-- open: default is ( for both OMML and MathML -->
      <xsl:if test="m:dPr[1]/m:begChr/@m:val and not(m:dPr[1]/m:begChr/@m:val ='(')">
        <xsl:attribute name="open">
          <xsl:value-of select="m:dPr[1]/m:begChr/@m:val" />
        </xsl:attribute>
      </xsl:if>
      
      <!-- close: default is ) for both OMML and MathML -->
      <xsl:if test="m:dPr[1]/m:endChr/@m:val and not(m:dPr[1]/m:endChr/@m:val =')')">
        <xsl:attribute name="close">
          <xsl:value-of select="m:dPr[1]/m:endChr/@m:val" />
        </xsl:attribute>
      </xsl:if>
      
      <!-- separator: the default is ',' for MathML, and '|' for OMML -->
      <xsl:choose>
        <!-- Matches MathML default. Write nothing -->
        <xsl:when test="m:dPr[1]/m:sepChr/@m:val = ','" />
        
        <!-- OMML default: | -->
        <xsl:when test="not(m:dPr[1]/m:sepChr/@m:val)">
          <xsl:attribute name="separators">
            <xsl:value-of select="'|'" />
          </xsl:attribute>
        </xsl:when>
        
        <xsl:otherwise>
          <xsl:attribute name="separators">
            <xsl:value-of select="m:dPr[1]/m:sepChr/@m:val" />
          </xsl:attribute>			
        </xsl:otherwise>						
      </xsl:choose>
      
      <!-- now write all the children. Put each one into an mrow
        just in case it produces multiple runs, etc -->
      <xsl:for-each select="m:e">
        <mml:mrow>
          <xsl:apply-templates select="." />
        </mml:mrow>
      </xsl:for-each>			
    </mml:mfenced>
  </xsl:template>
  
  <xsl:template match="m:r">
    <xsl:variable name="sLowerCaseNor" select="translate(child::m:rPr[last()]/m:nor/@m:val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCaseNor='on'">
        <mml:mtext>
          <xsl:value-of select=".//m:t" />
        </mml:mtext>
      </xsl:when>
      <xsl:otherwise>
        <xsl:for-each select=".//m:t">
          <xsl:call-template name="ParseMt">
            <xsl:with-param name="sToParse" select="text()" />
            <xsl:with-param name="mscr" select="../m:rPr[last()]/m:scr/@m:val" />
            <xsl:with-param name="msty" select="../m:rPr[last()]/m:sty/@m:val" />
            <xsl:with-param name="mnor" select="../m:rPr[last()]/m:nor/@m:val" />
          </xsl:call-template>
        </xsl:for-each>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template name="CreateAttributesforToken">
    <xsl:param name="mscr" />
    <xsl:param name="msty" />
    <xsl:param name="mnor" />
    <xsl:param name="nCharToPrint" />
    <xsl:param name="sTokenType" />
    <xsl:variable name="sLowerCaseNor" select="translate($mnor, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCaseNor = 'on'">
        <xsl:attribute name="mathvariant">normal</xsl:attribute>
      </xsl:when>
      <xsl:otherwise>
        <xsl:variable name="mathvariant">
          <xsl:choose>
            <!-- numbers don't care -->
            <xsl:when test="$sTokenType='mn'" />
            
            <xsl:when test="$mscr='monospace'">monospace</xsl:when>
            <xsl:when test="$mscr='sans-serif' and $msty='i'">sans-serif-italic</xsl:when>
            <xsl:when test="$mscr='sans-serif' and $msty='b'">bold-sans-serif</xsl:when>
            <xsl:when test="$mscr='sans-serif'">sans-serif</xsl:when>
            <xsl:when test="$mscr='fraktur' and $msty='b'">bold-fraktur</xsl:when>
            <xsl:when test="$mscr='fraktur'">fraktur</xsl:when>
            <xsl:when test="$mscr='double-struck'">double-struck</xsl:when>
            <xsl:when test="$mscr='script' and $msty='b'">bold-script</xsl:when>
            <xsl:when test="$mscr='script'">script</xsl:when>
            <xsl:when test="($mscr='roman' or not($mscr) or $mscr='') and $msty='b'">bold</xsl:when>
            <xsl:when test="($mscr='roman' or not($mscr) or $mscr='') and $msty='i'">italic</xsl:when>
            <xsl:when test="($mscr='roman' or not($mscr) or $mscr='') and $msty='p'">normal</xsl:when>
            
            <xsl:otherwise />
          </xsl:choose>
        </xsl:variable>
        <xsl:variable name="fontweight">
          <xsl:choose>
            <xsl:when test="$msty='b' or $msty='bi'">bold</xsl:when>
            <xsl:otherwise>normal</xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        <xsl:variable name="fontstyle">
          <xsl:choose>
            <xsl:when test="$msty='p' or $msty='b'">normal</xsl:when>
            <xsl:otherwise>italic</xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        
        <!-- Writing of attributes begins here -->
        <xsl:choose>
          <!-- Don't write mathvariant for operators unless they want to be normal -->
          <xsl:when test="$sTokenType='mo' and $mathvariant!='normal'" />
          
          <!-- A single character within an mi is already italics, don't write -->
          <xsl:when test="$sTokenType='mi' and $nCharToPrint=1 and ($mathvariant='' or $mathvariant='italic')" />
          
          <xsl:when test="$sTokenType='mi' and $nCharToPrint &gt; 1 and ($mathvariant='' or $mathvariant='italic')">
            <xsl:attribute name="mathvariant">
              <xsl:value-of select="'italic'" />
            </xsl:attribute>
          </xsl:when>
          <xsl:when test="$mathvariant!='italic' and $mathvariant!=''">
            <xsl:attribute name="mathvariant">
              <xsl:value-of select="$mathvariant" />
            </xsl:attribute>
          </xsl:when>
          <xsl:otherwise>
            <xsl:if test="not($sTokenType='mi' and $nCharToPrint=1) and $fontstyle='italic'">
              <xsl:attribute name="fontstyle">italic</xsl:attribute>
            </xsl:if>
            <xsl:if test="$fontweight='bold'">
              <xsl:attribute name="fontweight">bold</xsl:attribute>
            </xsl:if>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="m:eqArr">
    <mml:mtable>
      <xsl:attribute name="frame">none</xsl:attribute>
      <xsl:attribute name="columnlines">none</xsl:attribute>
      <xsl:attribute name="rowlines">none</xsl:attribute>
      <xsl:for-each select="m:e">
        <mml:mtr>
          <mml:mtd>
            <mml:maligngroup />
            <xsl:choose>
              <xsl:when test="m:argPr[last()]/m:scrLvl/@m:val!='0' or 
                not(m:argPr[last()]/m:scrLvl/@m:val)  or 
                m:argPr[last()]/m:scrLvl/@m:val=''">
                <mml:mrow>
                  <xsl:call-template name="CreateEqArrRow">
                    <xsl:with-param name="align" select="1" />
                    <xsl:with-param name="ndCur" select="*[1]" />
                  </xsl:call-template>
                </mml:mrow>
              </xsl:when>
              <xsl:otherwise>
                <mml:mstyle>
                  <xsl:attribute name="scriptlevel">
                    <xsl:value-of select="m:argPr[last()]/m:scrLvl/@m:val" />
                  </xsl:attribute>
                  <xsl:call-template name="CreateEqArrRow">
                    <xsl:with-param name="align" select="1" />
                    <xsl:with-param name="ndCur" select="*[1]" />
                  </xsl:call-template>
                </mml:mstyle>
              </xsl:otherwise>
            </xsl:choose>
          </mml:mtd>
        </mml:mtr>
      </xsl:for-each>
    </mml:mtable>
  </xsl:template>
  
  <xsl:template name="CreateEqArrRow">
    <xsl:param name="align" /> 
    <xsl:param name="ndCur" />
    <xsl:variable name="sAllMt"> 
      <xsl:for-each select="$ndCur/m:t">
        <xsl:value-of select="." />
      </xsl:for-each>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="local-name($ndCur)='r' and
        namespace-uri($ndCur)='http://schemas.microsoft.com/office/omml/2004/12/core'">
        
        <xsl:call-template name="ParseEqArrMr">
          <xsl:with-param name="sToParse" select="$sAllMt" />
          <xsl:with-param name="mscr" select="../m:rPr[last()]/m:scr/@m:val" />
          <xsl:with-param name="msty" select="../m:rPr[last()]/m:sty/@m:val" />
          <xsl:with-param name="mnor" select="../m:rPr[last()]/m:nor/@m:val" />
          <xsl:with-param name="align" select="$align" />
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:apply-templates select="$ndCur" />
      </xsl:otherwise>
    </xsl:choose>
    <xsl:if test="count($ndCur/following-sibling::*) &gt; 0">
      <xsl:variable name="cAmp">
        <xsl:call-template name="CountAmp">
          <xsl:with-param name="sAllMt" select="$sAllMt" />
          <xsl:with-param name="cAmp" select="0" />
        </xsl:call-template>
      </xsl:variable>
      <xsl:call-template name="CreateEqArrRow">
        <xsl:with-param name="align" select="($align+($cAmp mod 2)) mod 2" />
        <xsl:with-param name="ndCur" select="$ndCur/following-sibling::*[1]" />
      </xsl:call-template>
    </xsl:if>
  </xsl:template>
  
  <xsl:template name="CountAmp">
    <xsl:param name="sAllMt" />
    <xsl:param name="cAmp" />
    <xsl:choose>
      <xsl:when test="string-length(substring-after($sAllMt, '&amp;')) &gt; 0 or 
        substring($sAllMt, string-length($sAllMt))='&#x0026;'">
        <xsl:call-template name="CountAmp">
          <xsl:with-param name="sAllMt" select="substring-after($sAllMt, '&#x0026;')" />
          <xsl:with-param name="cAmp" select="$cAmp+1" />
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$cAmp" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- %%Template: ParseEqArrMr
    
    Similar to ParseMt, but this one has to do more for an equation 
    array. The presence of &amp; in a run that is in an equation array
    indicates alignment 
  -->
  <xsl:template name="ParseEqArrMr">
    <xsl:param name="sToParse" />
    <xsl:param name="msty" />
    <xsl:param name="mscr" />
    <xsl:param name="mnor" />
    <xsl:param name="align" />
    <xsl:if test="string-length($sToParse) &gt; 0"> 
      <xsl:choose>
        <xsl:when test="substring($sToParse,1,1) = '&amp;'">
          <xsl:choose>
            <xsl:when test="$align='0'">
              <mml:maligngroup />
            </xsl:when>
            <xsl:when test="$align='1'">
              <mml:malignmark>
                <xsl:attribute name="edge">left</xsl:attribute>
              </mml:malignmark>
            </xsl:when>
          </xsl:choose>
          <xsl:call-template name="ParseEqArrMr">
            <xsl:with-param name="sToParse" select="substring($sToParse,2)" />
            <xsl:with-param name="mscr" select="$mscr" />
            <xsl:with-param name="msty" select="$msty" />
            <xsl:with-param name="mnor" select="$mnor" />
            <xsl:with-param name="align">
              <xsl:choose>
                <xsl:when test="$align='1'">0</xsl:when>
                <xsl:otherwise>1</xsl:otherwise>
              </xsl:choose>
            </xsl:with-param>
          </xsl:call-template>
        </xsl:when>
        <xsl:otherwise> 
          <xsl:variable name="sRepNumWith1">
            <xsl:call-template name="SReplaceNumWithOne">
              <xsl:with-param name="sToParse" select="$sToParse" />
            </xsl:call-template>
          </xsl:variable>					
          <xsl:variable name="sRepOperWith-">
            <xsl:call-template name="SReplaceOperWithMinus">
              <xsl:with-param name="sToParse" select="$sRepNumWith1" />
            </xsl:call-template>
          </xsl:variable>
          
          <xsl:variable name="iFirstOper" select="string-length($sRepOperWith-) - string-length(substring-after($sRepOperWith-, '-'))" />
          <xsl:variable name="iFirstNum" select="string-length($sRepOperWith-) - string-length(substring-after($sRepOperWith-, '0'))" />
          <xsl:variable name="iFirstAmp" select="string-length($sRepOperWith-) - string-length(substring-after($sRepOperWith-, '&#x0026;'))" />
          <xsl:variable name="fNumAtPos1"> 
            <xsl:choose>
              <xsl:when test="substring($sRepOperWith-,1,1)='0'">1</xsl:when>
              <xsl:otherwise>0</xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:variable name="fOperAtPos1"> 
            <xsl:choose>
              <xsl:when test="substring($sRepOperWith-,1,1)='-'">1</xsl:when>
              <xsl:otherwise>0</xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:choose>
            
            <!-- Case I: The string begins with neither a number, nor an operator -->
            <xsl:when test="$fNumAtPos1='0' and $fOperAtPos1='0'">
              <xsl:variable name="nCharToPrint">
                <xsl:choose>
                  <xsl:when test="($iFirstOper=$iFirstNum) and 
                    ($iFirstAmp=$iFirstOper) and
                    ($iFirstOper=string-length($sToParse)) and
                    $fNumAtPos1='0' and
                    $fOperAtPos1='0'">
                    <xsl:value-of select="string-length($sToParse)" />
                  </xsl:when>
                  <xsl:when test="($iFirstOper &lt; $iFirstNum) and 
                    ($iFirstOper &lt; $iFirstAmp)">
                    <xsl:value-of select="$iFirstOper - 1" />
                  </xsl:when>
                  <xsl:when test="($iFirstNum &lt; $iFirstOper) and 
                    ($iFirstNum &lt; $iFirstAmp)">
                    <xsl:value-of select="$iFirstNum - 1" />
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="$iFirstAmp - 1" />
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:variable>
              <mml:mi>
                <xsl:call-template name="CreateAttributesforToken">
                  <xsl:with-param name="mscr" select="$mscr" />
                  <xsl:with-param name="msty" select="$msty" />
                  <xsl:with-param name="mnor" select="$mnor" />
                  <xsl:with-param name="nCharToPrint" select="$nCharToPrint" />
                  <xsl:with-param name="sTokenType" select="'mi'" />
                </xsl:call-template>
                <xsl:value-of select="substring($sToParse,1,$nCharToPrint)" />
              </mml:mi>
              <xsl:call-template name="ParseEqArrMr">
                <xsl:with-param name="sToParse" select="substring($sToParse, $nCharToPrint+1)" />
                <xsl:with-param name="mscr" select="$mscr" />
                <xsl:with-param name="msty" select="$msty" />
                <xsl:with-param name="mnor" select="$mnor" />
                <xsl:with-param name="align" select="$align" />
              </xsl:call-template>
            </xsl:when>
            
            <!-- Case II: There is an operator at position 1 -->
            <xsl:when test="$fOperAtPos1='1'">
              <mml:mo>
                <xsl:call-template name="CreateAttributesforToken">
                  <xsl:with-param name="mscr" />
                  <xsl:with-param name="msty" />
                  <xsl:with-param name="mnor" select="$mnor" />
                  <xsl:with-param name="sTokenType" select="'mo'" />
                </xsl:call-template>
                <xsl:value-of select="substring($sToParse,1,1)" />
              </mml:mo>
              <xsl:call-template name="ParseEqArrMr">
                <xsl:with-param name="sToParse" select="substring($sToParse, 2)" />
                <xsl:with-param name="mscr" select="$mscr" />
                <xsl:with-param name="msty" select="$msty" />
                <xsl:with-param name="mnor" select="$mnor" />
                <xsl:with-param name="align" select="$align" />
              </xsl:call-template>
            </xsl:when>
            
            <!-- Case III: There is a number at position 1 -->
            <xsl:otherwise>
              <xsl:variable name="sConsecNum">
                <xsl:call-template name="SNumStart">
                  <xsl:with-param name="sToParse" select="$sToParse" />
                  <xsl:with-param name="sPattern" select="$sRepNumWith1" />									
                </xsl:call-template>
              </xsl:variable>
              <mml:mn>
                <xsl:call-template name="CreateAttributesforToken">
                  <xsl:with-param name="mscr" />
                  <xsl:with-param name="msty" />
                  <xsl:with-param name="mnor" select="$mnor" />
                  <xsl:with-param name="sTokenType" select="'mn'" />
                </xsl:call-template>
                <xsl:value-of select="$sConsecNum" />
              </mml:mn>
              <xsl:call-template name="ParseEqArrMr">
                <xsl:with-param name="sToParse" select="substring-after($sToParse, $sConsecNum)" />
                <xsl:with-param name="mscr" select="$mscr" />
                <xsl:with-param name="msty" select="$msty" />
                <xsl:with-param name="mnor" select="$mnor" />
                <xsl:with-param name="align" select="$align" />
              </xsl:call-template>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
  </xsl:template>
  
  <!-- %%Template: SNumStart 
    
    Return the longest substring of sToParse starting from the 
    start of sToParse that is a number. In addition, it takes the
    pattern string, which is sToParse with all of its numbers 
    replaced with a 0. sPattern should be the same length 
    as sToParse		
  -->
  <xsl:template name="SNumStart">
    <xsl:param name="sToParse" select="''" />
    <xsl:param name="sPattern" select="'$sToParse'"/>	<!-- if we don't get anything, take the string itself -->
    
    <xsl:choose>
      <!-- the pattern says this is a number, recurse with the rest -->
      <xsl:when test="substring($sPattern, 1, 1) = '1'">
        <xsl:call-template name="SNumStart">
          <xsl:with-param name="sToParse" select="$sToParse" />
          <xsl:with-param name="sPattern" select="substring($sPattern, 2)" />
        </xsl:call-template>
      </xsl:when>
      
      <!-- the pattern says we've run out of numbers. Take as many
        characters from sToParse as we shaved off sPattern -->
      <xsl:otherwise>
        <xsl:value-of select="substring($sToParse, 1, string-length($sToParse) - string-length($sPattern))" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <!-- %%Template: ParseMt
    
    Produce a run of text. Technically, OMML makes no distinction 
    between numbers, operators, and other characters in a run. For 
    MathML we need to break these into mi, mn, or mo elements. 
    
    See also ParseEqArrMr
  -->	
  <xsl:template name="ParseMt">
    <xsl:param name="sToParse" />
    <xsl:param name="msty" />
    <xsl:param name="mscr" />
    <xsl:param name="mnor" />
    <xsl:if test="string-length($sToParse) &gt; 0">		
      <xsl:variable name="sRepNumWith1">
        <xsl:call-template name="SReplaceNumWithOne">
          <xsl:with-param name="sToParse" select="$sToParse" />
        </xsl:call-template>
      </xsl:variable>
      <xsl:variable name="sRepOperWith-">
        <xsl:call-template name="SReplaceOperWithMinus">
          <xsl:with-param name="sToParse" select="$sRepNumWith1" />
        </xsl:call-template>
      </xsl:variable>
      
      <xsl:variable name="iFirstOper" select="string-length($sRepOperWith-) - string-length(substring-after($sRepOperWith-, '-'))" />
      <xsl:variable name="iFirstNum" select="string-length($sRepOperWith-) - string-length(substring-after($sRepOperWith-, '0'))" />
      <xsl:variable name="fNumAtPos1">
        <xsl:choose>
          <xsl:when test="substring($sRepOperWith-,1,1)='1'">1</xsl:when>
          <xsl:otherwise>0</xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:variable name="fOperAtPos1"> 
        <xsl:choose>
          <xsl:when test="substring($sRepOperWith-,1,1)='-'">1</xsl:when>
          <xsl:otherwise>0</xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      
      <xsl:choose>
        
        <!-- Case I: The string begins with neither a number, nor an operator -->
        <xsl:when test="$fOperAtPos1='0' and $fNumAtPos1='0'">
          <xsl:variable name="nCharToPrint">
            <xsl:choose>
              <xsl:when test="($iFirstOper=$iFirstNum) and 
                ($iFirstOper=string-length($sToParse)) and
                (substring($sRepOperWith-, string-length($sRepOperWith-))!='0') and 
                (substring($sRepOperWith-, string-length($sRepOperWith-))!='-')">
                <xsl:value-of select="string-length($sToParse)" />
              </xsl:when>
              <xsl:when test="$iFirstOper &lt; $iFirstNum">
                <xsl:value-of select="$iFirstOper - 1" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$iFirstNum - 1" />
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <mml:mi>
            <xsl:call-template name="CreateAttributesforToken">
              <xsl:with-param name="mscr" select="$mscr" />
              <xsl:with-param name="msty" select="$msty" />
              <xsl:with-param name="mnor" select="$mnor" />
              <xsl:with-param name="nCharToPrint" select="$nCharToPrint" />
              <xsl:with-param name="sTokenType" select="'mi'" />
            </xsl:call-template>      
            <xsl:value-of select="translate(substring($sToParse,1,$nCharToPrint),' ','&nbsp;')" />
          </mml:mi>
          <xsl:call-template name="ParseMt">
            <xsl:with-param name="sToParse" select="substring($sToParse, $nCharToPrint+1)" />
            <xsl:with-param name="mscr" select="$mscr" />
            <xsl:with-param name="msty" select="$msty" />
            <xsl:with-param name="mnor" select="$mnor" />
          </xsl:call-template>
        </xsl:when>
        
        <!-- Case II: There is an operator at position 1 -->
        <xsl:when test="$fOperAtPos1='1'">
          <mml:mo>
            <xsl:call-template name="CreateAttributesforToken">
              <xsl:with-param name="mscr" />
              <xsl:with-param name="msty" />
              <xsl:with-param name="mnor" select="$mnor" />
              <xsl:with-param name="sTokenType" select="'mo'" />
            </xsl:call-template>
            <xsl:value-of select="substring($sToParse,1,1)" />
          </mml:mo>
          <xsl:call-template name="ParseMt">
            <xsl:with-param name="sToParse" select="substring($sToParse, 2)" />
            <xsl:with-param name="mscr" select="$mscr" />
            <xsl:with-param name="msty" select="$msty" />
            <xsl:with-param name="mnor" select="$mnor" />
          </xsl:call-template>
        </xsl:when>
        
        <!-- Case III: There is a number at position 1 -->
        <xsl:otherwise>
          <xsl:variable name="sConsecNum">				
            <xsl:call-template name="SNumStart">
              <xsl:with-param name="sToParse" select="$sToParse" />
              <xsl:with-param name="sPattern" select="$sRepNumWith1" /> 
            </xsl:call-template>
          </xsl:variable>
          <mml:mn>
            <xsl:call-template name="CreateAttributesforToken">
              <xsl:with-param name="mscr" select="$mscr" />
              <xsl:with-param name="msty" select="'p'" />
              <xsl:with-param name="mnor" select="$mnor" />
              <xsl:with-param name="sTokenType" select="'mn'" />
            </xsl:call-template>
            <xsl:value-of select="$sConsecNum" />
          </mml:mn>
          <xsl:call-template name="ParseMt">
            <xsl:with-param name="sToParse" select="substring-after($sToParse, $sConsecNum)" />
            <xsl:with-param name="mscr" select="$mscr" />
            <xsl:with-param name="msty" select="$msty" />
            <xsl:with-param name="mnor" select="$mnor" />
          </xsl:call-template>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
  </xsl:template>
  <!-- %%Template SReplaceOperWithMinus
    
    Go through the given string and replace every instance
    of an operator with a minus '-'. This helps quickly identify
    the first instance of an operator.  
  -->
  <xsl:template name="SReplaceOperWithMinus">			
    <xsl:param name="sToParse" select="''" />
    
    <xsl:value-of select="translate($sToParse, $sOperators, $sMinuses)" />			                                
  </xsl:template>
  <!-- %%Template SRepeatCharAcc
    
    The core of SRepeatChar with an accumulator. The current
    string is in param $acc, and we will double and recurse,
    if we're less than half of the required length or else just 
    add the right amount of characters to the accumulator and
    return
  -->
  <xsl:template name="SRepeatCharAcc">
    <xsl:param name="cchRequired" select="1" />
    <xsl:param name="ch" select="'-'" />
    <xsl:param name="acc" select="$ch" />
    
    <xsl:variable name="cchAcc" select="string-length($acc)" />
    <xsl:choose>
      <xsl:when test="(2 * $cchAcc) &lt; $cchRequired">
        <xsl:call-template name="SRepeatCharAcc">
          <xsl:with-param name="cchRequired" select="$cchRequired" />
          <xsl:with-param name="ch" select="$ch" />
          <xsl:with-param name="acc" select="concat($acc, $acc)" />
        </xsl:call-template>
      </xsl:when>
      
      <xsl:otherwise>
        <xsl:value-of select="concat($acc, substring($acc, 1, $cchRequired - $cchAcc))" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  
  <!-- %%Template SRepeatChar
    
    Generates a string nchRequired long by repeating the given character ch
  -->
  <xsl:template name="SRepeatChar">
    <xsl:param name="cchRequired" select="1" />
    <xsl:param name="ch" select="'-'" />
    
    <xsl:call-template name="SRepeatCharAcc">
      <xsl:with-param name="cchRequired" select="$cchRequired" />
      <xsl:with-param name="ch" select="$ch" />
      <xsl:with-param name="acc" select="$ch" />
    </xsl:call-template>
  </xsl:template>
  
  <!-- %%Template SReplaceNumWithOne
    
    Go through the given string and replace every instance
    of an number with a One '1'. This helps quickly identify
    the first occurence of a number. 
    
    Considers the '.' and ',' part of a number iff they are sandwiched 
    between two other numbers. 1.3 will be recognized as a number,
    x.3 will not be. Since these characters can also be an operator, this 
    should be called before SReplaceOperWithMinus.
  -->
  <xsl:template name="SReplaceNumWithOne">			
    <xsl:param name="sToParse" select="''" />
    
    <!-- First do a simple replace. Numbers will all be come 0's.
      After this point, the pattern involving the . or , that 
      we are looking for will become 1.1 or 1,1 -->
    <xsl:variable name="sSimpleReplace" select="translate($sToParse, $sNumbers, $sOnes)" />
    
    <!-- And then, replace 1.1 with just 111. This means that the . will 
      become part of the number -->		
    <xsl:variable name="sReplacePeriod">
      <xsl:call-template name="SReplace">
        <xsl:with-param name="sInput" select="$sSimpleReplace"/>
        <xsl:with-param name="sOrig" select="'1.1'"/>
        <xsl:with-param name="sReplacement" select="'111'"/>			
      </xsl:call-template>
    </xsl:variable>
    
    <!-- And then, replace 1,1 with just 111. This means that the , will 
      become part of the number -->
    <xsl:call-template name="SReplace">
      <xsl:with-param name="sInput" select="$sReplacePeriod"/>
      <xsl:with-param name="sOrig" select="'1,1'"/>
      <xsl:with-param name="sReplacement" select="'111'"/>			
    </xsl:call-template>		
  </xsl:template>	
  <xsl:template match="m:m">
    <mml:mtable>
      <xsl:call-template name="CreateMathMLMatrixAttr">
        <xsl:with-param name="mcJc" select="m:mPr[last()]/m:mcs/m:mc/m:mcPr[last()]/m:mcJc/@m:val" />
      </xsl:call-template>
      <xsl:for-each select="m:mr">
        <mml:mtr>
          <xsl:for-each select="m:e">
            <mml:mtd>
              <xsl:apply-templates select="." />
            </mml:mtd>
          </xsl:for-each>
        </mml:mtr>
      </xsl:for-each>
    </mml:mtable>
  </xsl:template>
  
  <xsl:template name="CreateMathMLMatrixAttr">
    <xsl:param name="mcJc" />
    <xsl:variable name="sLowerCaseMcjc" select="translate($mcJc, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCaseMcjc='left'">
        <xsl:attribute name="columnalign">left</xsl:attribute>
      </xsl:when>
      <xsl:when test="$sLowerCaseMcjc='right'">
        <xsl:attribute name="columnalign">right</xsl:attribute>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
 
  <xsl:template match="m:limLow">
    <mml:munder>
      <mml:mrow>
        <xsl:apply-templates select="m:e[1]" />
      </mml:mrow>
      <mml:mrow>
        <xsl:apply-templates select="m:lim[1]" />
      </mml:mrow>
    </mml:munder>
  </xsl:template>
  
  <xsl:template match="m:limUpp">
    <mml:mover>
      <mml:mrow>
        <xsl:apply-templates select="m:e[1]" />
      </mml:mrow>
      <mml:mrow>
        <xsl:apply-templates select="m:lim[1]" />
      </mml:mrow>
    </mml:mover>
  </xsl:template>
  
  <xsl:template match="m:rad"> 
    <xsl:variable name="sLowerCaseDegHide" select="translate(m:radPr[last()]/m:degHide/@m:val, 
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCaseDegHide='on'"> 
        <mml:msqrt>
          <xsl:apply-templates select="m:e[1]" />
        </mml:msqrt>
      </xsl:when>
      <xsl:otherwise> 
        <mml:mroot>
          <mml:mrow>
            <xsl:apply-templates select="m:e[1]" />
          </mml:mrow>
          <mml:mrow>
            <xsl:apply-templates select="m:deg[1]" />
          </mml:mrow>
        </mml:mroot>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="m:acc"> 
    <mml:mover>
      <xsl:attribute name="accent">true</xsl:attribute>
      <xsl:apply-templates select="m:e[1]" /> 
      <mml:mtext>
        <xsl:call-template name="CreateAttributesforToken">
          <xsl:with-param name="mscr" select="m:e[1]/*/m:rPr[last()]/m:scr/@m:val" />
          <xsl:with-param name="msty" select="m:e[1]/*/m:rPr[last()]/m:sty/@m:val" />
          <xsl:with-param name="mnor" select="m:e[1]/*/m:rPr[last()]/m:nor/@m:val" />
        </xsl:call-template>
        <xsl:choose>
          <xsl:when test="not(m:accPr[last()]/m:chr)">
            <xsl:value-of select="'&#x0302;'" /> 
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="substring(m:accPr/m:chr/@m:val,1,1)" />
          </xsl:otherwise>
        </xsl:choose>
      </mml:mtext>
    </mml:mover>
  </xsl:template>
  
  <xsl:template match="m:sPre">
    <mml:mmultiscripts>
      <mml:mrow>
        <xsl:apply-templates select="m:e[1]" />
      </mml:mrow>
      <mml:mprescripts />
      <mml:mrow>
        <xsl:apply-templates select="m:sub[1]" />
      </mml:mrow>
      <mml:mrow>
        <xsl:apply-templates select="m:sup[1]" />
      </mml:mrow>
    </mml:mmultiscripts>
  </xsl:template>
  
  <!-- Fixex -->
  <!-- avoid printing fnames twice -->
  <xsl:template match="m:func">
    <mml:mrow>
      <mml:mrow>
        <xsl:apply-templates select="m:fName[1]/*" />
      </mml:mrow>
      <mml:mo></mml:mo>
      <xsl:apply-templates select="m:fName[1]/following-sibling::*" />
    </mml:mrow>
  </xsl:template>
  
  <!-- m:r reconstituted from Word comments don't have character data in m:t it is directly i m:r (and style information is in interleaved span and i elements, weird but true -->
  
  <xsl:template match="m:r[not(.//m:t)]">
    <xsl:for-each select=".//text()[translate(.,' &#10;&amp;','')]">
      <xsl:call-template name="ParseMt">
        <xsl:with-param name="sToParse" select="translate(.,' &#10;&amp;','')" />
        <xsl:with-param name="mscr" select="../m:rPr[last()]/m:scr/@m:val" />
        <xsl:with-param name="msty" select="../m:rPr[last()]/m:sty/@m:val" />
        <xsl:with-param name="mnor" select="../m:rPr[last()]/m:nor/@m:val" />
      </xsl:call-template>
    </xsl:for-each>
  </xsl:template>
  
  <!--
    MS stylesheet uses DOE which is (a) unevil, (b) non necessary, and (c) breaks the pass through a temporay tree to get rid of namespace nodes.
    repeat the templates here without doe (and without double quoting amp)
  -->
  <xsl:template match="m:nary">
    <xsl:variable name="sLowerCaseSubHide">
      <xsl:choose>
        <xsl:when test="count(m:naryPr[last()]/m:subHide) = 0">
          <xsl:text>off</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="translate(m:naryPr[last()]/m:subHide/@m:val, 
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
            'abcdefghijklmnopqrstuvwxyz')" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="sLowerCaseSupHide">
      <xsl:choose>
        <xsl:when test="count(m:naryPr[last()]/m:supHide) = 0">
          <xsl:text>off</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="translate(m:naryPr[last()]/m:supHide/@m:val, 
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
            'abcdefghijklmnopqrstuvwxyz')" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="not($sLowerCaseSupHide='off') and 
        not($sLowerCaseSubHide='off')">
        <mml:mo>
          <xsl:choose>
            <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
              m:naryPr[last()]/m:chr/@m:val=''">
              <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
            </xsl:otherwise>
          </xsl:choose>
        </mml:mo>
      </xsl:when>
      <xsl:when test="not($sLowerCaseSubHide='off')">
        <xsl:choose>
          <xsl:when test="m:naryPr[last()]/m:limLoc/@m:val='subSup'">
            <mml:msup>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sup[1]" />
            </mml:msup>
          </xsl:when>
          <xsl:otherwise>
            <mml:mover>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sup[1]" />
            </mml:mover>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:when test="not($sLowerCaseSupHide='off')">
        <xsl:choose>
          <xsl:when test="m:naryPr[last()]/m:limLoc/@m:val='subSup'">
            <mml:msub>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sub[1]" />
            </mml:msub>
          </xsl:when>
          <xsl:otherwise>
            <mml:munder>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sub[1]" />
            </mml:munder>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="m:naryPr[last()]/m:limLoc/@m:val='subSup'">
            <mml:msubsup>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sub[1]" />
              <xsl:apply-templates select="m:sup[1]" />
            </mml:msubsup>
          </xsl:when>
          <xsl:otherwise>
            <mml:munderover>
              <mml:mo>
                <xsl:choose>
                  <xsl:when test="not(m:naryPr[last()]/m:chr/@m:val) or
                    m:naryPr[last()]/m:chr/@m:val=''">
                    <xsl:text disable-output-escaping="no">&#x222b;</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="m:naryPr[last()]/m:chr/@m:val" />
                  </xsl:otherwise>
                </xsl:choose>
              </mml:mo>
              <xsl:apply-templates select="m:sub[1]" />
              <xsl:apply-templates select="m:sup[1]" />
            </mml:munderover>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
    <mml:mrow>
      <xsl:apply-templates select="m:e[1]" />
    </mml:mrow>
  </xsl:template>
  <xsl:template name="CreateGroupChr">
    <xsl:variable name="sLowerCasePos" select="translate(m:groupChrPr[last()]/m:pos/@m:val, 
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCasePos!='top' or 
        not(m:groupChrPr[last()]/m:pos/@m:val)   or
        m:groupChrPr[last()]/m:pos/@m:val=''">
        <mml:munder>
          <xsl:apply-templates select="m:e[1]" />
          <mml:mo>
            <xsl:choose>
              <xsl:when test="string-length(m:groupChrPr[last()]/m:chr/@m:val) &gt;= 1">
                <xsl:value-of select="substring(m:groupChrPr[last()]/m:chr/@m:val,1,1)" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:text disable-output-escaping="no">&#x023DF;</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </mml:mo>
        </mml:munder>
      </xsl:when>
      <xsl:otherwise>
        <mml:mover>
          <xsl:apply-templates select="m:e[1]" />
          <mml:mo>
            <xsl:choose>
              <xsl:when test="string-length(m:groupChrPr[last()]/m:chr/@m:val) &gt;= 1">
                <xsl:value-of select="substring(m:groupChrPr[last()]/m:chr/@m:val,1,1)" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:text disable-output-escaping="no">&#x023DF;</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </mml:mo>
        </mml:mover>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="m:bar">
    <xsl:variable name="sLowerCasePos" select="translate(m:barPr/m:pos/@m:val, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:choose>
      <xsl:when test="$sLowerCasePos!='bot' or 
        not($sLowerCasePos)   or
        $sLowerCasePos=''   ">
        <mml:mover>
          <xsl:attribute name="accent">true</xsl:attribute>
          <xsl:apply-templates select="m:e[1]" />
          <mml:mo>
            <xsl:text disable-output-escaping="no">&#x000AF;</xsl:text>
          </mml:mo>
        </mml:mover>
      </xsl:when>
      <xsl:otherwise>
        <mml:munder>
          <xsl:apply-templates select="m:e[1]" />
          <mml:mo>
            <xsl:text disable-output-escaping="no">&#x00332;</xsl:text>
          </mml:mo>
        </mml:munder>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
   
  <!-- wrong name for (m)phantom -->
  
  <xsl:template match="m:phant">
    <xsl:variable name="sLowerCaseWidth" select="translate(m:phantPr[last()]/m:width/@m:val, 
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:variable name="sLowerCaseAsc" select="translate(m:phantPr[last()]/m:asc/@m:val, 
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:variable name="sLowerCaseDec" select="translate(m:phantPr[last()]/m:dec/@m:val, 
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
      'abcdefghijklmnopqrstuvwxyz')" />
    <xsl:if test="not($sLowerCaseWidth='off' and 
      $sLowerCaseAsc='off'   and
      $sLowerCaseDec='off')">
      <mml:mphantom>				
        <xsl:apply-templates select="m:e[1]" />
      </mml:mphantom>
    </xsl:if>
  </xsl:template>
  
  <!--TEMPLATES NEWLY ADDED-->
  
  <xsl:template match="m:oMath">
    <xsl:variable name="varAlign"> 
      <xsl:value-of select="preceding-sibling::m:oMathParaPr/m:jc/@m:val"/></xsl:variable>
    <xsl:variable name="spStyle">
      <xsl:choose>
        <xsl:when test="((parent::m:oMathPara) and not(preceding-sibling::m:oMathParaPr))">
          display:block;
        </xsl:when>
        <xsl:otherwise>
        </xsl:otherwise>
      </xsl:choose>
      font-family:<xsl:value-of select=".//w:rPr[last()]/w:rFonts/@w:ascii"/>;
      font-size:<xsl:value-of select=".//w:rPr[last()]/w:sz/@w:val div 2"/>pt;
      <xsl:choose>
        <xsl:when test="string-length(.//w:rPr[last()]/w:color/@w:val) = 0">
          color:<xsl:call-template name="ConvHexColor">
            <xsl:with-param name="value" select="ancestor::w:p/w:pPr/w:rPr[last()]/w:color/@w:val"/>
          </xsl:call-template>;
        </xsl:when>
        <xsl:otherwise>
          color:<xsl:call-template name="ConvHexColor">
            <xsl:with-param name="value" select=".//w:rPr[last()]/w:color/@w:val"/>
          </xsl:call-template>;
        </xsl:otherwise>
      </xsl:choose>
      <xsl:choose>
        <xsl:when test="string-length(.//w:rPr[last()]/w:highlight/@w:val) = 0">
          background-color:<xsl:call-template name="ConvColor">
            <xsl:with-param name="value" select="ancestor::w:p/w:pPr/w:rPr[last()]/w:hightlight/@w:val"/>
          </xsl:call-template>;
        </xsl:when>
        <xsl:otherwise>
          background-color:<xsl:call-template name="ConvColor">
            <xsl:with-param name="value" select=".//w:rPr[last()]/w:highlight/@w:val"/>
          </xsl:call-template>;
        </xsl:otherwise>
      </xsl:choose>
      <xsl:choose>
        <xsl:when test="string-length(.//w:rPr[last()]/w:u[1]/@w:val) = 0 or .//w:rPr[last()]/w:u[1]/@w:val = 'off' or .//w:rPr[last()]/w:u[1]/@w:val = 'none' or .//w:rPr[last()]/w:u[1]/@w:val = '0' ">
          text-decoration:none;
        </xsl:when>
        <xsl:otherwise>
          text-decoration:underline;
        </xsl:otherwise>
      </xsl:choose>
      text-align:<xsl:value-of select="$varAlign"></xsl:value-of>;
    </xsl:variable>
    
    <xsl:choose>
      <xsl:when test="(parent::m:oMathPara) and not(preceding-sibling::m:oMathParaPr)">
        <span style="{$spStyle}"> 
        <mml:math display="block">
        <xsl:apply-templates select="*"/> 
        </mml:math> 
        </span>
      </xsl:when>
      <xsl:otherwise>
        <div align="{$varAlign}">
        <span style="{$spStyle}">
          <mml:math>
            <xsl:apply-templates select="*"/>  
          </mml:math>
        </span>   
        </div>
      </xsl:otherwise>
    </xsl:choose>
   
  </xsl:template> 
  <!--ADDED FOR MATHML SUPPORT BY SHUBHA align="{$varAlign}" -->
  
  
  <xsl:template match="m:t">
    
    <xsl:choose>
      <xsl:when test="/@xml:space">
       &#160;
      </xsl:when>
    </xsl:choose>
    <xsl:apply-templates select="*"/>  
  </xsl:template>
</xsl:stylesheet>
