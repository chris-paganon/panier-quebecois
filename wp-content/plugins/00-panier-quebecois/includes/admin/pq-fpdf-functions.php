<?php

require_once PQ_VENDOR_DIR . '/fpdf184/WriteTag.php';

class PQ_FPDF extends PDF_WriteTag {

    public $col = 0;
    public $col_left_x;
    public $max_col;
    public $y0;
    public $col_width;
    public $margin;
    public $padding;
    public $page_width;

    function SetCol($col) {
        // Set position at a given column
        $this->col = $col;
        $x = $this->margin + $col * ($this->col_width + $this->padding);
        $this->col_left_x = $x;
        $this->Xini = $x; //Set X for WriteTag extension
        $this->SetLeftMargin($x);
        $this->SetX($x);
    }

    function AcceptPageBreak() {
        // Method accepting or not automatic page break
        if($this->col < ($this->max_col - 1))
        {
            // Go to next column
            $this->SetCol($this->col+1);
            // Set ordinate to top
            $this->SetY($this->y0);
            // Keep on page
            return false;
        }
        else
        {
            // Go back to first column
            $this->SetCol(0);
            // Page break
            return true;
        }
    }
}