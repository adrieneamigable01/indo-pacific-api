<?php

namespace App\Libraries;

require_once APPPATH . 'Libraries/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

class Pdf
{
    public function load_view2_portrait($filename,$html)
    {
        $dompdf = new Dompdf();

        $dompdf->setPaper('legal');

        $dompdf->loadHtml($html);

        $dompdf->render();

        $dompdf->stream(
            $filename . '.pdf',
            ['Attachment' => false]
        );
    }
}