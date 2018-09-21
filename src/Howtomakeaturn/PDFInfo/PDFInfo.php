<?php
namespace Howtomakeaturn\PDFInfo;

/*
* Inspired by http://stackoverflow.com/questions/14644353/get-the-number-of-pages-in-a-pdf-document/14644354
* @author howtomakeaturn
*/

class PDFInfo
{
    protected $file;
    protected $page;
    public $output;

    public $title;
    public $author;
    public $creator;
    public $producer;
    public $creationDate;
    public $modDate;
    public $tagged;
    public $form;
    public $pages;
    public $encrypted;
    public $pageSize;
    public $pageRot;
    public $fileSize;
    public $optimized;
    public $PDFVersion;

    public static $bin;

    public function __construct($file, $page = null)
    {
        $this->file = $file;
        $this->page = $page;

        $this->loadOutput();

        $this->parseOutput();
    }

    public function getBinary()
    {
        if (empty(static::$bin)) {
            static::$bin = trim(trim(getenv('PDFINFO_BIN'), '\\/" \'')) ?: 'pdfinfo';
        }

        return static::$bin;
    }

    private function loadOutput()
    {
        $cmd = escapeshellarg($this->getBinary()); // escapeshellarg to work with Windows paths with spaces.

        $file = escapeshellarg($this->file);

        $pageOptions = '';
        if ($this->page) {
            $pageOptions = "-f $this->page -l $this->page";
        }

        // Parse entire output
        // Surround with double quotes if file name has spaces
        exec("$cmd $file $pageOptions", $output, $returnVar);

        if ( $returnVar === 1 ){
            throw new Exceptions\OpenPDFException();
        } else if ( $returnVar === 2 ){
            throw new Exceptions\OpenOutputException();
        } else if ( $returnVar === 3 ){
            throw new Exceptions\PDFPermissionException();
        } else if ( $returnVar === 99 ){
            throw new Exceptions\OtherException();
        }

        $this->output = $output;
    }

    private function parseOutput()
    {
        $this->title = $this->parse('Title');
        $this->author = $this->parse('Author');
        $this->creator = $this->parse('Creator');
        $this->producer = $this->parse('Producer');
        $this->creationDate = $this->parse('CreationDate');
        $this->modDate = $this->parse('ModDate');
        $this->tagged = $this->parse('Tagged');
        $this->form = $this->parse('Form');
        $this->pages = $this->parse('Pages');
        $this->encrypted = $this->parse('Encrypted');
        $this->fileSize = $this->parse('File size');
        $this->optimized = $this->parse('Optimized');
        $this->PDFVersion = $this->parse('PDF version');

        // Page specific properties
        if ($this->page) {
            $this->pageSize = $this->parse('Page ' . $this->page . ' size');
            $this->pageRot = $this->parse('Page ' . $this->page . ' rot');
        } else {
            $this->pageSize = $this->parse('Page size');
            $this->pageRot = $this->parse('Page rot');
        }
    }

    private function parse($attribute)
    {
        // Iterate through lines
        $result = null;
        foreach($this->output as $op)
        {
            // Clean multiple spaces in the key
            // It happens when we use pdfinfo with a specific page
            $cleanedOp = preg_replace('!\s+!', ' ', $op);
            // Extract the number
            if(preg_match("/" . $attribute . ":\s*(.+)/i", $cleanedOp, $matches) === 1)
            {
                $result = $matches[1];
                break;
            }
        }

        return $result;
    }

}
