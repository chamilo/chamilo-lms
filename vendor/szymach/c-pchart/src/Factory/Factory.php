<?php
namespace CpChart\Factory;

use CpChart\Chart\Barcode128;
use CpChart\Chart\Barcode39;
use CpChart\Chart\Data;
use CpChart\Chart\Image;
use CpChart\Exception\ChartIsAMethodException;
use CpChart\Exception\IncorrectBarcodeNumberException;
use CpChart\Exception\NotSupportedChartException;

/**
 * A simple service class utilizing the Factory design pattern. It has three
 * class specific methods, as well as a generic loader for the chart classes.
 *
 * @author szymach @ http://github.com/szymach
 */
class Factory
{
    private $namespace;

    public function __construct($namespace = 'CpChart\Chart')
    {
        $this->namespace = $namespace;
    }
    
    /**
     * Loads a new chart class (scatter, pie etc.). Some classes require instances of
     * Image and Data classes passed into their constructor. These classes are:
     * Bubble, Pie, Scatter, Stock, Surface and Indicator. Otherwise the
     * pChartObject and DataObject parameters are redundant.
     *
     * ATTENTION! SOME OF THE CHARTS NEED TO BE DRAWN VIA A METHOD FROM THE
     * 'Image' CLASS (ex. 'drawBarChart'), NOT THROUGH THIS METHOD! READ THE
     * DOCUMENTATION FOR MORE DETAILS.
     *
     * @param string $chartType - type of the chart to be loaded (for example 'pie', not 'pPie')
     * @param Image $chartObject
     * @param Data $dataObject
     * @return \CpChart\Chart\{$chartType}
     * @throws NotSupportedChartException
     */
    public function newChart(
        $chartType,
        Image $chartObject = null,
        Data $dataObject = null
    ) {
        $this->checkChartType($chartType);
        $className = $this->prependNamespace(ucfirst($chartType));
        
        if (!class_exists($className)) {
            throw new NotSupportedChartException();
        }
        return new $className($chartObject, $dataObject);
    }

    /**
     * Checks if the requested chart type is created via one of the methods in
     * the Draw class, instead through a seperate class. If a method in Draw
     * exists, an exception with proper information is thrown.
     *
     * @param string $chartType
     * @throws ChartIsAMethodException
     */
    private function checkChartType($chartType)
    {
        $chart = ucfirst($chartType);
        $methods = [sprintf('draw%sChart', $chart), sprintf('draw%s', $chart)];
        
        foreach ($methods as $method) {
            if (method_exists($this->prependNamespace('Image'), $method)) {
                throw new ChartIsAMethodException($method);
            }
        }
    }

    /**
     * Creates a new Data class with an option to pass the data to form a serie.
     *
     * @param array $points - points to be added to serie
     * @param string $serieName - name of the serie
     * @return Data
     */
    public function newData(array $points = [], $serieName = "Serie1")
    {
        $className = $this->prependNamespace('Data');
        $data = new $className();
        if (!empty($points)) {
            $data->addPoints($points, $serieName);
        }
        return $data;
    }

    /**
     * Create a new Image class. It requires the size of axes to be properly
     * constructed.
     *
     * @param integer $XSize - length of the X axis
     * @param integer $YSize - length of the Y axis
     * @param Data $DataSet - Data class populated with points
     * @param boolean $TransparentBackground
     * @return Image
     */
    public function newImage(
        $XSize,
        $YSize,
        Data $DataSet = null,
        $TransparentBackground = false
    ) {
        $className = $this->prependNamespace('Image');
        return new $className(
            $XSize,
            $YSize,
            $DataSet,
            $TransparentBackground
        );
    }

    /**
     * Create one of the Barcode classes. Only the number is required (39 or 128),
     * the class name is contructed on the fly. Passing the constructor's parameters
     * is also available, but not mandatory.
     *
     * @param int $number - Barcode class number (39 or 128)
     * @param string $BasePath - optional path for the file containing the class data
     * @param boolean $EnableMOD43
     * @return Barcode39|Barcode128
     * @throws IncorrectBarcodeNumberException
     */
    public function getBarcode($number, $BasePath = "", $EnableMOD43 = false)
    {
        if ($number != 39 && $number != 128) {
            throw new IncorrectBarcodeNumberException($number);
        }
        $className = sprintf("%s%s", $this->prependNamespace('Barcode'), $number);

        return new $className($BasePath, $EnableMOD43);
    }
    
    private function prependNamespace($class)
    {
        return sprintf('%s\%s', $this->namespace, $class);
    }
}
