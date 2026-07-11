<?php

namespace App\Libraries;

require_once APPPATH . 'Libraries/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class Pdf
{
    public function load_view2_portrait($filename, $html)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        header("Content-Type: application/pdf");
        header("Content-Disposition: inline; filename=\"{$filename}.pdf\"");
        header("Content-Length: " . strlen($dompdf->output()));

        echo $dompdf->output();
        exit;
    }
}