<?php

/*
     pChart - a PHP class to build charts!
     Copyright (C) 2008 Jean-Damien POGOLOTTI
     Version  1.27d last updated on 09/30/08

     Extension by Gabriele FERRI
	http://www.gabrieleferri.it
	MyHorBar.class is writen to extend pChart.class to write a horizontal bar charts
	Copyright (C) 2009 Gabriele FERRI
	version 1.0beta 08/05/2009
	Contact me with bug reports and comments: info@gabrieleferri.it


     http://pchart.sourceforge.net

     This program is free software: you can redistribute it and/or modify
     it under the terms of the GNU General Public License as published by
     the Free Software Foundation, either version 1,2,3 of the License, or
     (at your option) any later version.

     This program is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     You should have received a copy of the GNU General Public License
     along with this program.  If not, see <http://www.gnu.org/licenses/>.
     Draw methods :
	drawHorBarGraph($Data,$DataDescription,$Shadow=TRUE,$Alpha=100)
	drawHorScale($Data,$DataDescription,$ScaleMode,$R,$G,$B,$DrawTicks=TRUE,$Angle=0,$Decimals=1,$WithMargin=FALSE,$SkipLabels=1,$RightScale=FALSE)
	drawHorGrid($LineWidth,$Mosaic,$R=220,$G=220,$B=220,$Alpha=100)
	drawTreshold($Value,$R,$G,$B,$ShowLabel=FALSE,$ShowOnBottom=FALSE,$TickWidth=4,$FreeText=NULL,$Angle=0)
*/

class MyHorBar extends pChart
  {


   /* This function draw a bar graph */
   function drawHorBarGraph($Data,$DataDescription,$Shadow=TRUE,$Alpha=100)
    {
     /* Validate the Data and DataDescription array */
     $this->validateDataDescription("drawBarGraph",$DataDescription);
     $this->validateData("drawBarGraph",$Data);

     $GraphID      = 0;
     $Series       = count($DataDescription["Values"]);
     $SeriesWidth  = $this->DivisionWidth / ($Series+1);

     $SerieYOffset = $this->DivisionWidth / 2 - $SeriesWidth * $Series / 2;
     
     $XZero  = $this->GArea_X1 + ((0-$this->VMin) * $this->DivisionRatio);
     if ( $XZero < $this->GArea_X1 ) { $XZero = $this->GArea_X1; }

     $SerieID = 0;
     foreach ( $DataDescription["Values"] as $Key2 => $ColName )
      {
       $ID = 0;
       foreach ( $DataDescription["Description"] as $keyI => $ValueI )
        { if ( $keyI == $ColName ) { $ColorID = $ID; }; $ID++; }

       $YPos  = $this->GArea_Y1 + $this->GAreaXOffset + $SerieYOffset + $SeriesWidth * $SerieID;

       $XLast = -1;
       foreach ( $Data as $Key => $Values )
        {
         if ( isset($Data[$Key][$ColName]))
          {
           if ( is_numeric($Data[$Key][$ColName]) )
            {
             $Value = $Data[$Key][$ColName];

	     $XPos = $this->GArea_X1 + (($Value-$this->VMin) * $this->DivisionRatio);

             /* Save point into the image map if option activated */
             if ( $this->BuildMap )
              {
               $this->addToImageMap(min($XZero,$XPos),$YPos+1,max($XZero,$XPos), $YPos+$SeriesWidth-1,$DataDescription["Description"][$ColName],$Data[$Key][$ColName].$DataDescription["Unit"]["Y"],"Bar");
              }
           
             if ( $Shadow && $Alpha == 100 )
              $this->drawRectangle($XZero,$YPos+1,$XPos,$YPos+$SeriesWidth-1,25,25,25,TRUE,$Alpha);

             $this->drawFilledRectangle($XZero,$YPos+1,$XPos,$YPos+$SeriesWidth-1,$this->Palette[$ColorID]["R"],$this->Palette[$ColorID]["G"],$this->Palette[$ColorID]["B"],TRUE,$Alpha);
            }
          }
         $YPos = $YPos + $this->DivisionWidth;
        }
       $SerieID++;
      }
    }


/* Compute and draw the scale */
   function drawHorScale($Data,$DataDescription,$ScaleMode,$R,$G,$B,$DrawTicks=TRUE,$Angle=0,$Decimals=1,$WithMargin=FALSE,$SkipLabels=1,$RightScale=FALSE)
    {
     /* Validate the Data and DataDescription array */
     $this->validateData("drawScale",$Data);

     $C_TextColor =$this->AllocateColor($this->Picture,$R,$G,$B);

     $this->drawLine($this->GArea_X1,$this->GArea_Y1,$this->GArea_X2,$this->GArea_Y1,$R,$G,$B);
     $this->drawLine($this->GArea_X1,$this->GArea_Y1,$this->GArea_X1,$this->GArea_Y2,$R,$G,$B);

     if ( $this->VMin == NULL && $this->VMax == NULL)
      {
       if (isset($DataDescription["Values"][0]))
        {
         $this->VMin = $Data[0][$DataDescription["Values"][0]];
         $this->VMax = $Data[0][$DataDescription["Values"][0]];
        }
       else { $this->VMin = 2147483647; $this->VMax = -2147483647; }

       /* Compute Min and Max values */
       if ( $ScaleMode == SCALE_NORMAL || $ScaleMode == SCALE_START0 )
        {
         if ( $ScaleMode == SCALE_START0 ) { $this->VMin = 0; }

         foreach ( $Data as $Key => $Values )
          {
           foreach ( $DataDescription["Values"] as $Key2 => $ColName )
            {
             if (isset($Data[$Key][$ColName]))
              {
               $Value = $Data[$Key][$ColName];

               if ( is_numeric($Value) )
                {
                 if ( $this->VMax < $Value) { $this->VMax = $Value; }
                 if ( $this->VMin > $Value) { $this->VMin = $Value; }
                }
              }
            }
          }
        }
       elseif ( $ScaleMode == SCALE_ADDALL || $ScaleMode == SCALE_ADDALLSTART0 ) /* Experimental */
        {
         if ( $ScaleMode == SCALE_ADDALLSTART0 ) { $this->VMin = 0; }

         foreach ( $Data as $Key => $Values )
          {
           $Sum = 0;
           foreach ( $DataDescription["Values"] as $Key2 => $ColName )
            {
             if (isset($Data[$Key][$ColName]))
              {
               $Value = $Data[$Key][$ColName];
               if ( is_numeric($Value) )
                $Sum  += $Value;
              }
            }
           if ( $this->VMax < $Sum) { $this->VMax = $Sum; }
           if ( $this->VMin > $Sum) { $this->VMin = $Sum; }
          }
        }

       if ( $this->VMax > preg_replace('/\.[0-9]+/','',$this->VMax) )
        $this->VMax = preg_replace('/\.[0-9]+/','',$this->VMax)+1;

       /* If all values are the same */
       if ( $this->VMax == $this->VMin )
        {
         if ( $this->VMax >= 0 ) { $this->VMax++; }
         else { $this->VMin--; }
        }

       $DataRange = $this->VMax - $this->VMin;
       if ( $DataRange == 0 ) { $DataRange = .1; }

       /* Compute automatic scaling */
       $ScaleOk = FALSE; $Factor = 1;
       $MinDivHeight = 25;

       $MaxDivs = ($this->GArea_X2 - $this->GArea_X1) / $MinDivHeight;

       if ( $this->VMin == 0 && $this->VMax == 0 )
        { $this->VMin = 0; $this->VMax = 2; $Scale = 1; $Divisions = 2;}
       elseif ($MaxDivs > 1)
        {
         while(!$ScaleOk)
          {
           $Scale1 = ( $this->VMax - $this->VMin ) / $Factor;
           $Scale2 = ( $this->VMax - $this->VMin ) / $Factor / 2;
           $Scale4 = ( $this->VMax - $this->VMin ) / $Factor / 4;

           if ( $Scale1 > 1 && $Scale1 <= $MaxDivs && !$ScaleOk) { $ScaleOk = TRUE; $Divisions = floor($Scale1); $Scale = 1;}
           if ( $Scale2 > 1 && $Scale2 <= $MaxDivs && !$ScaleOk) { $ScaleOk = TRUE; $Divisions = floor($Scale2); $Scale = 2;}
           if (!$ScaleOk)
            {
             if ( $Scale2 > 1 ) { $Factor = $Factor * 10; }
             if ( $Scale2 < 1 ) { $Factor = $Factor / 10; }
            }
          }

         if ( floor($this->VMax / $Scale / $Factor) != $this->VMax / $Scale / $Factor)
          {
           $GridID     = floor ( $this->VMax / $Scale / $Factor) + 1;
           $this->VMax = $GridID * $Scale * $Factor;
           $Divisions++;
          }

         if ( floor($this->VMin / $Scale / $Factor) != $this->VMin / $Scale / $Factor)
          {
           $GridID     = floor( $this->VMin / $Scale / $Factor);
           $this->VMin = $GridID * $Scale * $Factor;
           $Divisions++;
          }
        }
       else /* Can occurs for small graphs */
        $Scale = 1;

       if ( !isset($Divisions) )
        $Divisions = 2;

       if ($Scale == 1 && $Divisions%2 == 1)
        $Divisions--;
      }
     else
      $Divisions = $this->Divisions;

     $this->DivisionCount = $Divisions;

     $DataRange = $this->VMax - $this->VMin;
     if ( $DataRange == 0 ) { $DataRange = .1; }

     $this->DivisionHeight = ( $this->GArea_X2 - $this->GArea_X1 ) / $Divisions;
     $this->DivisionRatio  = ( $this->GArea_X2 - $this->GArea_X1 ) / $DataRange;

     $this->GAreaYOffset  = 0;
     if ( count($Data) > 1 )
      {
       if ( $WithMargin == FALSE )
	$this->DivisionWidth = ( $this->GArea_Y2 - $this->GArea_Y1 ) / (count($Data)-1);
       else
        {
	 $this->DivisionWidth = ( $this->GArea_Y2 - $this->GArea_Y1 ) / (count($Data));
	 $this->GAreaYOffset  = $this->DivisionWidth / 2;
        }
      }
     else
      {
       $this->DivisionWidth = $this->GArea_Y2 - $this->GArea_Y1;
       $this->GAreaYOffset  = $this->DivisionWidth / 2;
      }

     $this->DataCount = count($Data);

     if ( $DrawTicks == FALSE )
      return(0);

     $XPos = $this->GArea_X1;
     $YMin = NULL;

     for($i=1;$i<=$Divisions+1;$i++)
      {
	$this->drawLine($XPos,$this->GArea_Y1,$XPos,$this->GArea_Y1-5,$R,$G,$B);

       $Value     = $this->VMin + ($i-1) * (( $this->VMax - $this->VMin ) / $Divisions);
       $Value     = round($Value * pow(10,$Decimals)) / pow(10,$Decimals);
       if ( $DataDescription["Format"]["Y"] == "number" )
        $Value = $Value.$DataDescription["Unit"]["Y"];
       if ( $DataDescription["Format"]["Y"] == "time" )
        $Value = $this->ToTime($Value);        
       if ( $DataDescription["Format"]["Y"] == "date" )
        $Value = $this->ToDate($Value);        
       if ( $DataDescription["Format"]["Y"] == "metric" )
        $Value = $this->ToMetric($Value);        
       if ( $DataDescription["Format"]["Y"] == "currency" )
        $Value = $this->ToCurrency($Value);        
		
       $Position  = imageftbbox($this->FontSize,0,$this->FontName,$Value);
       $TextWidth = $Position[2]-$Position[0];

         imagettftext($this->Picture,$this->FontSize,0,$XPos-($this->FontSize/2),$this->GArea_Y1-10,$C_TextColor,$this->FontName,$Value);
         
	 if ( $YMin > $this->GArea_Y1-10-$TextWidth || $YMin == NULL ) { $YMin = $this->GArea_Y1-10-$TextWidth; }

       $XPos = $XPos + $this->DivisionHeight;
      }

     /* Write the Y Axis caption if set */ 
     if ( isset($DataDescription["Axis"]["Y"]) )
      {
       $Position   = imageftbbox($this->FontSize,90,$this->FontName,$DataDescription["Axis"]["Y"]);
       $TextHeight = abs($Position[1])+abs($Position[3]);
       $TextTop = (($this->GArea_X2 - $this->GArea_X1) / 2) + $this->GArea_X1 + ($TextHeight/2);

	imagettftext($this->Picture,$this->FontSize,90,$YMin-$this->FontSize,$TextTop,$C_TextColor,$this->FontName,$DataDescription["Axis"]["Y"]);
      }

     /* Horizontal Axis */
     $YPos = $this->GArea_Y1 + $this->GAreaYOffset;
     $ID = 1;
     $XMax = NULL;
     foreach ( $Data as $Key => $Values )
      {
       if ( $ID % $SkipLabels == 0 )
        {
         
	 $this->drawLine($this->GArea_X1,floor($YPos),$this->GArea_X1-5,floor($YPos),$R,$G,$B);
         $Value      = $Data[$Key][$DataDescription["Position"]];
         if ( $DataDescription["Format"]["X"] == "number" )
          $Value = $Value.$DataDescription["Unit"]["X"];
         if ( $DataDescription["Format"]["X"] == "time" )
          $Value = $this->ToTime($Value);        
         if ( $DataDescription["Format"]["X"] == "date" )
          $Value = $this->ToDate($Value);        
         if ( $DataDescription["Format"]["X"] == "metric" )
          $Value = $this->ToMetric($Value);        
         if ( $DataDescription["Format"]["X"] == "currency" )
          $Value = $this->ToCurrency($Value);        

         $Position   = imageftbbox($this->FontSize,$Angle,$this->FontName,$Value);
         $TextWidth  = abs($Position[2])+abs($Position[0]);
         $TextHeight = abs($Position[1])+abs($Position[3]);

         if ( $Angle == 0 )
          {
	   $XPos = $this->GArea_Y2+18;	   imagettftext($this->Picture,$this->FontSize,$Angle,$this->GArea_X1-10-floor($TextWidth),floor($YPos)+5-floor($TextHeight/2),$C_TextColor,$this->FontName,$Value);

          }
         else
          {
	   $XPos = $this->GArea_Y2+10+$TextHeight;
           if ( $Angle <= 90 )
	    imagettftext($this->Picture,$this->FontSize,$Angle,$XPos,floor($YPos)-$TextWidth+5,$C_TextColor,$this->FontName,$Value);
           else
	    imagettftext($this->Picture,$this->FontSize,$Angle,$XPos,floor($YPos)+$TextWidth+5,$C_TextColor,$this->FontName,$Value);
          }
	 if ( $XMax < $XPos || $XMax == NULL ) { $XMax = $XPos; }
        }

       $YPos = $YPos + $this->DivisionWidth;
       $ID++;
      }

    /* Write the X Axis caption if set */ 
    if ( isset($DataDescription["Axis"]["Y"]) )
      {
       $Position = imageftbbox($this->FontSize,90,$this->FontName,$DataDescription["Axis"]["Y"]);
       $TextWidth  = abs($Position[2])+abs($Position[0]);
       $TextLeft   = (($this->GArea_Y2 - $this->GArea_Y1) / 2) + $this->GArea_Y1 + ($TextWidth/2);

       imagettftext($this->Picture,$this->FontSize,0,$TextLeft,$XMax+$this->FontSize+5,$C_TextColor,$this->FontName,$DataDescription["Axis"]["Y"]);
      }
    }


   /* Compute and draw the scale */
   function drawHorGrid($LineWidth,$Mosaic=TRUE,$R=220,$G=220,$B=220,$Alpha=100)
    {
     /* Draw mosaic */
     if ( $Mosaic )
      {
       $LayerWidth  = $this->GArea_Y2-$this->GArea_Y1;
       $LayerHeight = $this->GArea_X2-$this->GArea_X1;

       $this->Layers[0] = imagecreatetruecolor($LayerWidth,$LayerHeight);
       $C_White =$this->AllocateColor($this->Layers[0],255,255,255);
       imagefilledrectangle($this->Layers[0],0,0,$LayerWidth,$LayerHeight,$C_White);
       imagecolortransparent($this->Layers[0],$C_White);

       $C_Rectangle =$this->AllocateColor($this->Layers[0],250,250,250);
       $XPos  = $LayerHeight;
       $LastX = $XPos;

       for($i=0;$i<=$this->DivisionCount;$i++)
        {
	 $LastX = $XPos;
	 $XPos  = $XPos - $this->DivisionHeight;

	 if ( $XPos <= 0 ) { $XPos = 1; }

         if ( $i % 2 == 0 )
          {
	   imagefilledrectangle($this->Layers[0],$XPos,1,$LastX,$LayerWidth-1,$C_Rectangle);
          }
        }
       imagecopymerge($this->Picture,$this->Layers[0],$this->GArea_X1,$this->GArea_Y1,0,0,$LayerWidth,$LayerHeight,$Alpha);
       imagedestroy($this->Layers[0]);
      }

     /* Vertical lines */
     $XPos = $this->GArea_X2 - $this->DivisionHeight;
     for($i=1;$i<=$this->DivisionCount;$i++)
      {
       if ( $XPos > $this->GArea_X1 && $XPos < $this->GArea_X2 )
        $this->drawDottedLine($XPos,$this->GArea_Y1,$XPos,$this->GArea_Y2,$LineWidth,$R,$G,$B);
        $XPos = $XPos - $this->DivisionHeight;
      }

     /* Horizontal lines */
     if ( $this->GAreaYOffset == 0 )
      { $YPos = $this->GArea_Y1 + $this->DivisionWidth + $this->GAreaYOffset;
	$ColCount = $this->DataCount-2; }
     else
      { $YPos = $this->GArea_Y1 + $this->GAreaYOffset;
	//$ColCount = floor( ($this->GArea_X2 - $this->GArea_X1) / $this->DivisionWidth );
	$ColCount = $this->DataCount;
      }

     for($i=1;$i<=$ColCount;$i++)
      {
       if ( $YPos > $this->GArea_Y1 && $YPos < $this->GArea_Y2 )
        $this->drawDottedLine($this->GArea_X1,floor($YPos),$this->GArea_X2,floor($YPos),$LineWidth,$R,$G,$B);

	$YPos = $YPos + $this->DivisionWidth;
      }
    }


/* Compute and draw the scale */
   function drawTreshold($Value,$R,$G,$B,$ShowLabel=FALSE,$ShowOnBottom=FALSE,$TickWidth=4,$FreeText=NULL,$Angle=0)
    {
     if ( $R < 0 ) { $R = 0; } if ( $R > 255 ) { $R = 255; }
     if ( $G < 0 ) { $G = 0; } if ( $G > 255 ) { $G = 255; }
     if ( $B < 0 ) { $B = 0; } if ( $B > 255 ) { $B = 255; }

     $C_TextColor =$this->AllocateColor($this->Picture,$R,$G,$B);
     $X = $this->GArea_X1 + ($Value - $this->VMin) * $this->DivisionRatio;

     if ( $X <= $this->GArea_X1 || $X >= $this->GArea_X2 )
      return(-1);

     if ( $TickWidth == 0 )
      $this->drawLine($X,$this->GArea_Y1,$X,$this->GArea_Y2,$R,$G,$B);
     else
      $this->drawDottedLine($X,$this->GArea_Y1,$X,$this->GArea_Y2,$TickWidth,$R,$G,$B);

     if ( $ShowLabel )
      {
       if ( $FreeText == NULL )
        { $Label = $Value; } else { $Label = $FreeText; }

       $Position = imageftbbox($this->FontSize,$Angle,$this->FontName,$Label);
       $TextWidth  = abs($Position[2])-abs($Position[0]);
       $TextLeft   = abs($Position[3])-abs($Position[1]);

       if ( $ShowOnBottom )
        imagettftext($this->Picture,$this->FontSize,$Angle,$X+9,$this->GArea_Y2,$C_TextColor,$this->FontName,$Label);
       else
        imagettftext($this->Picture,$this->FontSize,$Angle,$X+9,$this->GArea_Y1+$TextLeft,$C_TextColor,$this->FontName,$Label);
      }
    }


  }
?>
