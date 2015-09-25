<?php
namespace CpChart\Services;

use CpChart\Classes\pData;
use CpChart\Classes\pImage;

/**
 * A simple service class utilizing the Factory design pattern. It has three 
 * class specific methods, as well as a generic loader for the chart classes.
 *
 * @author szymach @ http://github.com/szymach
 */
class pChartFactory
{
    private $namespace = 'CpChart\Classes\\';
    
    /**
     * Loads a new chart class (scatter, pie etc.). Some classes require instances of
     * pImage and pData classes passed into their constructor. These classes are: 
     * pBubble, pPie, pScatter, pStock, pSurface and pIndicator. Otherwise the 
     * pChartObject and pDataObject parameters are redundant.
     * 
     * ATTENTION! SOME OF THE CHARTS NEED TO BE DRAWN VIA A METHOD FROM THE
     * pIMAGE CLASS (ex. 'drawBarChart'), NOT THROUGH THIS METHOD! READ THE 
     * DOCUMENTATION FOR MORE DETAILS.
     * 
     * @param string $chartType - type of the chart to be loaded (for example 'pie', not 'pPie')
     * @param pImage $pChartObject
     * @param pData $pDataObject
     * @return \CpChart\Classes\$chartName
     */
    public function newChart(
        $chartType,
        pImage $pChartObject = null, 
        pData $pDataObject = null
    ) {
        $this->checkChartType($chartType);
        $className = $this->namespace.'p'.ucfirst($chartType);
        if (!class_exists($className)) {
            throw new \Exception('The requested chart class does not exist!');
        }
        return new $className($pChartObject, $pDataObject);
    }
    
    /**
     * Checks if the requested chart type is created via one of the methods in
     * the pDraw class, instead through a seperate class. If a method in pDraw
     * exists, an exception with proper information is thrown.
     * 
     * @param string $chartType
     * @throws \Exception
     */
    private function checkChartType($chartType)
    {
        $methods = array(
            'draw'.ucfirst($chartType).'Chart',
            'draw'.ucfirst($chartType)
        );
        foreach ($methods as $method) {
            if (method_exists($this->namespace.'pImage', $method)) {
                throw new \Exception(
                    'The requested chart is not a seperate class, to draw it you'
                  . ' need to call the "'.$method.'" method on the pImage object'
                  . ' after populating it with data!'
                  . ' Check the documentation on library\'s website for details.'
                );
            }
        }
    }
    
    /**
     * Creates a new pData class with an option to pass the data to form a serie.
     * 
     * @param array $points - points to be added to serie
     * @param string $serieName - name of the serie
     * @return pData
     */
    public function newData(array $points = array(), $serieName = "Serie1")
    {
        $className = $this->namespace.'pData';
        $data = new $className(); 
        if (count($points) > 0) {
            $data->addPoints($points, $serieName);
        }
        return $data;
    }
    
    /**
     * Create a new pImage class. It requires the size of axes to be properly
     * constructed.
     * 
     * @param integer $XSize - length of the X axis
     * @param integer $YSize - length of the Y axis
     * @param pData $DataSet - pData class populated with points
     * @param boolean $TransparentBackground
     * @return pImage
     */
    public function newImage(
        $XSize,
        $YSize,
        \CpChart\Classes\pData $DataSet = null,
        $TransparentBackground = false
    ) {
        $className = $this->namespace.'pImage';
        return new $className(
            $XSize,
            $YSize,
            $DataSet,
            $TransparentBackground
        );
    }
    
    /**
     * Create one of the pBarcode classes. Only the number is required (39 or 128),
     * the class name is contructed on the fly. Passing the constructor's parameters
     * is also available, but not mandatory.
     * 
     * @param string $number - number identifing the pBarcode class ("39" or "128")
     * @param string $BasePath - optional path for the file containing the class data
     * @param boolean $EnableMOD43
     * @return pBarcode(39|128)
     * @throws \Exception
     */
    public function getBarcode($number, $BasePath = "", $EnableMOD43 = false)
    {
        if ($number != "39" && $number != "128") {
            throw new \Exception(
                'The barcode class for the provided number does not exist!'
            );
        }
        $className = $this->namespace."pBarcode".$number;
        return new $className($BasePath, $EnableMOD43);
    }
}
