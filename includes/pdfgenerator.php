<?php
class PDFGenerator {
    private $content = '';
    private $objects = [];
    private $objectCount = 0;
    private $currentY = 750;
    
    public function __construct() {
        $this->objects[++$this->objectCount] = "<< /Type /Catalog /Pages 2 0 R >>";
        $this->objects[++$this->objectCount] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $this->objects[++$this->objectCount] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
        $this->objects[++$this->objectCount] = ""; 
        $this->objects[++$this->objectCount] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    }
    
    public function addText($text, $x = 50, $y = null, $size = 12) {
        if ($y === null) {
            $y = $this->currentY;
        }
        

        $text = $this->escapeTurkishChars($text);
        
        $content = "BT\n";
        $content .= "/F1 $size Tf\n";
        $content .= "$x $y Td\n";
        $content .= "($text) Tj\n";
        $content .= "ET\n";
        
        $this->objects[4] .= $content;
        $this->currentY = $y - $size - 5;
        return $this;
    }
    
    private function escapeTurkishChars($text) {

        $replacements = [
            'ç' => 'c', 'Ç' => 'C',
            'ğ' => 'g', 'Ğ' => 'G',
            'ı' => 'i', 'İ' => 'I',
            'ö' => 'o', 'Ö' => 'O',
            'ş' => 's', 'Ş' => 'S',
            'ü' => 'u', 'Ü' => 'U',
            'â' => 'a', 'Â' => 'A',
            'ê' => 'e', 'Ê' => 'E',
            'î' => 'i', 'Î' => 'I',
            'ô' => 'o', 'Ô' => 'O',
            'û' => 'u', 'Û' => 'U'
        ];
        
        return strtr($text, $replacements);
    }
    
    public function addLine($x1, $y1, $x2, $y2) {
        $content = "$x1 $y1 m\n$x2 $y2 l\nS\n";
        $this->objects[4] .= $content;
        return $this;
    }
    
    public function addRect($x, $y, $width, $height) {
        $content = "$x $y $width $height re\nS\n";
        $this->objects[4] .= $content;
        return $this;
    }
    
    public function newLine($spacing = 15) {
        $this->currentY -= $spacing;
        return $this;
    }
    
    public function output($filename = 'document.pdf') {

        $this->objects[4] = "<< /Length " . strlen($this->objects[4]) . " >>\nstream\n" . $this->objects[4] . "\nendstream";
        
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
