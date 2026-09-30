<?php
// PDF helpers for sales and service reports.
function pdf_text($x, $y, $size, $text) {
    $text = iconv('UTF-8', 'Windows-1252//TRANSLIT', (string)$text);
    $text = str_replace(["\\", "(", ")", "\r", "\n"], ["\\\\", "\\(", "\\)", " ", " "], $text);
    return "0.08 0.18 0.12 rg BT /F1 $size Tf $x $y Td ($text) Tj ET\n";
}
function pdf_rectangle($x, $y, $width, $height, $color) {
    return "$color rg $x $y $width $height re f\n";
}
function report_pdf($report) {
    $pages = []; $offset = 0; $page_number = 1;
    do {
        $content = pdf_text(40,795,22,'Verdant Haven');
        $content .= pdf_text(40,768,16,$report['title']);
        $content .= pdf_text(40,746,10,$report['period'] . ' | Generated ' . date('d M Y, h:i A') . ' (Dhaka)');
        if ($page_number === 1) {
            $content .= pdf_text(40,710,13,'Total sales and service fees: ' . money($report['total']));
            $content .= pdf_text(40,688,9,'Non-cancelled orders and completed services. Dummy payments are simulated.');
            $content .= pdf_text(40,655,12,'Revenue by source (BDT)');
            $maximum = max(1,$report['plants'],$report['services']);
            $y=610;
            foreach (['plants'=>'Plant sales','services'=>'Service fees'] as $key=>$label) {
                $width = round($report[$key] / $maximum * 260,2);
                $content .= pdf_text(40,$y+6,10,$label);
                $content .= pdf_rectangle(135,$y,260,23,'0.9 0.94 0.9');
                if ($width > 0) $content .= pdf_rectangle(135,$y,$width,23,'0.03 0.58 0.39');
                $content .= pdf_text(410,$y+6,10,number_format($report[$key],2));
                $y-=45;
            }
            $table_y=510; $limit=28;
        } else { $table_y=710; $limit=42; }
        $content .= pdf_text(40,$table_y,9,'REFERENCE');
        $content .= pdf_text(115,$table_y,9,'CUSTOMER');
        $content .= pdf_text(270,$table_y,9,'DATE');
        $content .= pdf_text(355,$table_y,9,'CATEGORY');
        $content .= pdf_text(468,$table_y,9,'AMOUNT (BDT)');
        $table_y-=23;
        $chunk = array_slice($report['rows'],$offset,$limit);
        foreach ($chunk as $row) {
            $content .= pdf_text(40,$table_y,9,$row['id']);
            $content .= pdf_text(115,$table_y,9,mb_strimwidth($row['name'],0,25,'...','UTF-8'));
            $content .= pdf_text(270,$table_y,9,substr($row['date'],0,10));
            $content .= pdf_text(355,$table_y,9,$row['category']);
            $content .= pdf_text(468,$table_y,9,number_format($row['amount'],2));
            $table_y-=15;
        }
        if (!$report['rows']) $content .= pdf_text(40,$table_y,11,'No sales or completed services during this period.');
        $content .= pdf_text(40,35,9,'Verdant Haven | Page ' . $page_number);
        $pages[]=$content; $offset+=count($chunk); $page_number++;
    } while ($offset < count($report['rows']));
    $objects = [];
    $objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
    $kids=[];
    foreach ($pages as $index=>$content) $kids[]=(4+$index*2) . ' 0 R';
    $objects[2]='<< /Type /Pages /Kids [' . implode(' ',$kids) . '] /Count ' . count($pages) . ' >>';
    $objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    foreach ($pages as $index=>$content) {
        $page_id=4+$index*2; $stream_id=$page_id+1;
        $objects[$page_id]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $stream_id . ' 0 R >>';
        $objects[$stream_id]='<< /Length ' . strlen($content) . " >>\nstream\n" . $content . 'endstream';
    }
    $pdf="%PDF-1.4\n"; $offsets=[0];
    foreach ($objects as $id=>$object) {
        $offsets[$id]=strlen($pdf);
        $pdf.=$id . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref=strlen($pdf); $count=count($objects)+1;
    $pdf.="xref\n0 $count\n0000000000 65535 f \n";
    for ($id=1;$id<$count;$id++) $pdf.=sprintf("%010d 00000 n \n",$offsets[$id]);
    $pdf.="trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
    return $pdf;
}
