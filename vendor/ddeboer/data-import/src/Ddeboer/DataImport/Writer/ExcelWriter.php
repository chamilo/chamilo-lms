<?php

namespace Ddeboer\DataImport\Writer;

use \PHPExcel;
use \PHPExcel_IOFactory;

/**
 * Writes to an Excel file
 *
 */
class ExcelWriter extends AbstractWriter
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
     * @var PHPExcel
     */
    protected $excel;

    /**
     * @var int
     */
    protected $row = 1;

    /**
     * Constructor
     *
     * @param \SplFileObject $file  File
     * @param string         $sheet Sheet title (optional)
     * @param string         $type  Excel file type (optional, defaults to
     *                              Excel2007)
     */
    public function __construct(\SplFileObject $file, $sheet = null, $type = 'Excel2007')
    {
        $this->filename = $file->getPathname();
        $this->sheet = $sheet;
        $this->type = $type;
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
        }

        if (null !== $this->sheet) {
            if (!$this->excel->sheetNameExists($this->sheet)) {
                $this->excel->createSheet()->setTitle($this->sheet);
            }
            $this->excel->setActiveSheetIndexByName($this->sheet);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function writeItem(array $item)
    {
        $count = count($item);
        $values = array_values($item);

        for ($i = 0; $i < $count; $i++) {
            $this->excel->getActiveSheet()->setCellValueByColumnAndRow($i, $this->row, $values[$i]);
        }

        $this->row++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function finish()
    {
        $writer = \PHPExcel_IOFactory::createWriter($this->excel, $this->type);
        $writer->save($this->filename);

        return $this;
    }
}
