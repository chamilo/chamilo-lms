<?php

namespace Ddeboer\DataImport\Writer;

use Ddeboer\DataImport\Writer;
use PHPExcel;
use PHPExcel_IOFactory;

/**
 * Writes to an Excel file
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class ExcelWriter implements Writer
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var null|string
     */
    protected $sheet;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var boolean
     */
    protected $prependHeaderRow;

    /**
     * @var PHPExcel
     */
    protected $excel;

    /**
     * @var integer
     */
    protected $row = 1;

    /**
     * @param \SplFileObject $file  File
     * @param string         $sheet Sheet title (optional)
     * @param string         $type  Excel file type (defaults to Excel2007)
     * @param boolean        $prependHeaderRow
     */
    public function __construct(\SplFileObject $file, $sheet = null, $type = 'Excel2007', $prependHeaderRow = false)
    {
        $this->filename = $file->getPathname();
        $this->sheet = $sheet;
        $this->type = $type;
        $this->prependHeaderRow = $prependHeaderRow;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $reader = PHPExcel_IOFactory::createReader($this->type);
        if ($reader->canRead($this->filename)) {
            $this->excel = $reader->load($this->filename);
        } else {
            $this->excel = new PHPExcel();
            if(null !== $this->sheet && !$this->excel->sheetNameExists($this->sheet))
            {
                $this->excel->removeSheetByIndex(0);
            }
        }

        if (null !== $this->sheet) {
            if (!$this->excel->sheetNameExists($this->sheet)) {
                $this->excel->createSheet()->setTitle($this->sheet);
            }
            $this->excel->setActiveSheetIndexByName($this->sheet);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeItem(array $item)
    {
        $count = count($item);

        if ($this->prependHeaderRow && 1 == $this->row) {
            $headers = array_keys($item);

            for ($i = 0; $i < $count; $i++) {
                $this->excel->getActiveSheet()->setCellValueByColumnAndRow($i, $this->row, $headers[$i]);
            }
            $this->row++;
        }

        $values = array_values($item);

        for ($i = 0; $i < $count; $i++) {
            $this->excel->getActiveSheet()->setCellValueByColumnAndRow($i, $this->row, $values[$i]);
        }

        $this->row++;
    }

    /**
     * {@inheritdoc}
     */
    public function finish()
    {
        $writer = \PHPExcel_IOFactory::createWriter($this->excel, $this->type);
        $writer->save($this->filename);
    }
}
