<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use CpChart\Chart\Scatter;
use UnitTester;

class ScatterTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testBestFitChartRender()
    {
        $data = new Data();
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints(rand(1, 20) * 10 + rand(0, $i), "Probe 1");
        }
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints(rand(1, 2) * 10 + rand(0, $i), "Probe 2");
        }
        $data->setAxisName(0, "X-Index");
        $data->setAxisXY(0, AXIS_X);
        $data->setAxisPosition(0, AXIS_POSITION_TOP);
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints($i, "Probe 3");
        }
        $data->setSerieOnAxis("Probe 3", 1);
        $data->setAxisName(1, "Y-Index");
        $data->setAxisXY(1, AXIS_Y);
        $data->setAxisPosition(1, AXIS_POSITION_LEFT);
        $data->setScatterSerie("Probe 1", "Probe 3", 0);
        $data->setScatterSerieDescription(0, "This year");
        $data->setScatterSerieColor(0, ["R" => 0, "G" => 0, "B" => 0]);
        $data->setScatterSerie("Probe 2", "Probe 3", 1);
        $data->setScatterSerieDescription(1, "Last Year");
        $image = new Image(400, 400, $data);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 400, 400, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 400, 400, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 400, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "drawScatterBestFit() - Linear regression", ["R" => 255, "G" => 255, "B" => 255]);
        $image->drawRectangle(0, 0, 399, 399, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->setGraphArea(50, 60, 350, 360);
        $scatterChart = new Scatter($image, $data);
        $scatterChart->drawScatterScale();
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $scatterChart->drawScatterPlotChart();
        $scatterChart->drawScatterLegend(280, 380, ["Mode" => LEGEND_HORIZONTAL, "Style" => LEGEND_NOBORDER]);
        $scatterChart->drawScatterBestFit();

        $filename = $this->tester->getOutputPathForChart('drawScatterBestFit.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }

    public function testLineChartRender()
    {
        $data = new Data();
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints(cos(deg2rad($i)) * 20, "Probe 1");
        }
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints(sin(deg2rad($i)) * 20, "Probe 2");
        }
        $data->setAxisName(0, "Index");
        $data->setAxisXY(0, AXIS_X);
        $data->setAxisPosition(0, AXIS_POSITION_BOTTOM);
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints($i, "Probe 3");
        }
        $data->setSerieOnAxis("Probe 3", 1);
        $data->setAxisName(1, "Degree");
        $data->setAxisXY(1, AXIS_Y);
        $data->setAxisUnit(1, "°");
        $data->setAxisPosition(1, AXIS_POSITION_RIGHT);
        $data->setScatterSerie("Probe 1", "Probe 3", 0);
        $data->setScatterSerieDescription(0, "This year");
        $data->setScatterSerieTicks(0, 4);
        $data->setScatterSerieColor(0, ["R" => 0, "G" => 0, "B" => 0]);
        $data->setScatterSerie("Probe 2", "Probe 3", 1);
        $data->setScatterSerieDescription(1, "Last Year");
        $image = new Image(400, 400, $data);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 400, 400, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 400, 400, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 400, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "drawScatterLineChart() - Draw a scatter line chart", ["R" => 255, "G" => 255, "B" => 255]);
        $image->drawRectangle(0, 0, 399, 399, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->setGraphArea(50, 50, 350, 350);
        $scatterChart = new Scatter($image, $data);
        $scatterChart->drawScatterScale();
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $scatterChart->drawScatterLineChart();
        $scatterChart->drawScatterLegend(280, 380, ["Mode" => LEGEND_HORIZONTAL, "Style" => LEGEND_NOBORDER]);

        $filename = $this->tester->getOutputPathForChart('drawScatterLine.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }

    public function testPlotChartRender()
    {
        $data = new Data();
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints(cos(deg2rad($i)) * 20, "Probe 1");
        }
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints(sin(deg2rad($i)) * 20, "Probe 2");
        }
        $data->setAxisName(0, "Index");
        $data->setAxisXY(0, AXIS_X);
        $data->setAxisPosition(0, AXIS_POSITION_BOTTOM);
        for ($i = 0; $i <= 360; $i = $i + 10) {
            $data->addPoints($i, "Probe 3");
        }
        $data->setSerieOnAxis("Probe 3", 1);
        $data->setAxisName(1, "Degree");
        $data->setAxisXY(1, AXIS_Y);
        $data->setAxisUnit(1, "°");
        $data->setAxisPosition(1, AXIS_POSITION_RIGHT);
        $data->setScatterSerie("Probe 1", "Probe 3", 0);
        $data->setScatterSerieDescription(0, "This year");
        $data->setScatterSerieColor(0, ["R" => 0, "G" => 0, "B" => 0]);
        $data->setScatterSerie("Probe 2", "Probe 3", 1);
        $data->setScatterSerieDescription(1, "Last Year");
        $data->setScatterSeriePicture(1, sprintf("%s/../_data/accept.png", __DIR__));
        $image = new Image(400, 400, $data);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 400, 400, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 400, 400, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 400, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "drawScatterPlotChart() - Draw a scatter plot chart", ["R" => 255, "G" => 255, "B" => 255]);
        $image->drawRectangle(0, 0, 399, 399, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->setGraphArea(50, 50, 350, 350);
        $scatterChart = new Scatter($image, $data);
        $scatterChart->drawScatterScale();
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $scatterChart->drawScatterPlotChart();
        $scatterChart->drawScatterLegend(260, 375, ["Mode" => LEGEND_HORIZONTAL, "Style" => LEGEND_NOBORDER]);

        $filename = $this->tester->getOutputPathForChart('drawScatterPlot.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }

    public function testSplineChartRender()
    {
        $data = new Data();
        for ($i = 0; $i <= 360; $i = $i + 90) {
            $data->addPoints(rand(1, 30), "Probe 1");
        }
        for ($i = 0; $i <= 360; $i = $i + 90) {
            $data->addPoints(rand(1, 30), "Probe 2");
        }
        $data->setAxisName(0, "Index");
        $data->setAxisXY(0, AXIS_X);
        $data->setAxisPosition(0, AXIS_POSITION_BOTTOM);
        for ($i = 0; $i <= 360; $i = $i + 90) {
            $data->addPoints($i, "Probe 3");
        }
        $data->setSerieOnAxis("Probe 3", 1);
        $data->setAxisName(1, "Degree");
        $data->setAxisXY(1, AXIS_Y);
        $data->setAxisUnit(1, "°");
        $data->setAxisPosition(1, AXIS_POSITION_RIGHT);
        $data->setScatterSerie("Probe 1", "Probe 3", 0);
        $data->setScatterSerieDescription(0, "This year");
        $data->setScatterSerieTicks(0, 4);
        $data->setScatterSerieColor(0, ["R" => 0, "G" => 0, "B" => 0]);
        $data->setScatterSerie("Probe 2", "Probe 3", 1);
        $data->setScatterSerieDescription(1, "Last Year");
        $image = new Image(400, 400, $data);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 400, 400, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 400, 400, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 400, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);
        $image->setFontProperties(["FontName" => "../fonts/Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "drawScatterSplineChart() - Draw a scatter spline chart", ["R" => 255, "G" => 255, "B" => 255]);
        $image->drawRectangle(0, 0, 399, 399, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "../fonts/pf_arma_five.ttf", "FontSize" => 6]);
        $image->setGraphArea(50, 50, 350, 350);
        $scatterChart = new Scatter($image, $data);
        $scatterChart->drawScatterScale();
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $scatterChart->drawScatterSplineChart();
        $scatterChart->drawScatterPlotChart();
        $scatterChart->drawScatterLegend(280, 380, ["Mode" => LEGEND_HORIZONTAL, "Style" => LEGEND_NOBORDER]);

        $filename = $this->tester->getOutputPathForChart('drawScatterSpline.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
