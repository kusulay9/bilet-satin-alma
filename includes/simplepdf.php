<?php

class SimplePDF {
    private $content = '';
    private $objects = [];
    private $objectCount = 0;
    
    public function __construct() {
        $this->addObject('<< /Type /Catalog /Pages 2 0 R >>');
        $this->addObject('<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
    }
    
    private function addObject($data) {
        $this->objectCount++;
        $this->objects[$this->objectCount] = $data;
        return $this->objectCount;
    }
    
    public function addText($text, $x = 50, $y = 250, $size = 12) {
        $pageObj = $this->addObject('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents ' . ($this->objectCount + 1) . ' 0 R >>');
        
        $content = "BT\n/F1 $size Tf\n$x $y Td\n($text) Tj\nET";
        $this->addObject($content);
        
        return $this;
    }
    
    public function addLine($x1, $y1, $x2, $y2) {
        $content = "$x1 $y1 m\n$x2 $y2 l\nS";
        $this->addObject($content);
        return $this;
    }
    
    public function output($filename = 'document.pdf') {
        $pdf = "%PDF-1.4\n";
        

        foreach ($this->objects as $num => $obj) {
            $pdf .= "$num 0 obj\n$obj\nendobj\n";
        }
        

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . ($this->objectCount + 1) . "\n0000000000 65535 f \n";
        
        $offset = 9;
        for ($i = 1; $i <= $this->objectCount; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
            $offset += strlen($i . " 0 obj\n" . $this->objects[$i] . "\nendobj\n");
        }
        

        $pdf .= "trailer\n<<\n/Size " . ($this->objectCount + 1) . "\n/Root 1 0 R\n>>\n";
        $pdf .= "startxref\n$xrefPos\n%%EOF";
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $pdf;
        exit;
    }
}
?>
