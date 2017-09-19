<?php 
$file = '../resource/'.$_GET['file'];
if(is_file($file)) {
    $fi = new finfo(FILEINFO_MIME_TYPE); 
    $mime_type = $fi->file($file);
    $fsize = filesize($file);
    $fp = fopen($file, 'r');

    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header("Content-Description: File Transfer");
    header("Content-Transfer-Encoding: chunked");
    header("Content-type: ".$mime_type);
    header("Accept-Ranges: 0-".$fsize);
    header('Content-Disposition: attachment; filename='.basename($file));

    if (isset($_SERVER['HTTP_RANGE'])) {
        if ($_SERVER['HTTP_RANGE']=='') {
            $start = 0;
            $end = $fsize-1;
        } else {
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/".$fsize);
                exit;
            }
            if ($range == '-') {
                $start = 0;
                $end = $fsize-1;
            }else{
                $range = explode('-', $range);
                $start = $range[0];
                $end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $fsize-1;
            }
            $end = ($end >= $fsize) ? $fsize-1 : $end;

            if ($start > $end || $start > $fsize - 1 || $end >= $fsize) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$fsize");
                exit;
            }
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: ".($end - $start + 1));
            header("Content-Range: bytes $start-$end/$fsize");                
        }

    } else {
        header("Content-Length: ".$fsize);
    }

    fpassthru($fp);
    exit;
}

?>