<?php 
$file = '../resource/'.$_GET['file'];
if (file_exists($file))
{
    if (FALSE!== ($handler = fopen($file, 'r')))
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Content-Transfer-Encoding: chunked'); //changed to chunked
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        //header('Content-Length: ' . filesize($file)); //Remove

        //Send the content in chunks
        while(false !== ($chunk = fread($handler,4096)))
        {
            echo $chunk;
        }
    }
    exit;
}
?>