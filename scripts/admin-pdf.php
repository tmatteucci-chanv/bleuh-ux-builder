<?php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

if (isset($_POST["convert"]) && isset($_FILES["xlsx"])) {
    try {
        // create pdf output
        $mpdf = new \Mpdf\Mpdf([
            // 'mode' => 'c',
            // 'PDFA' => true,
            // 'PDFAauto' => true,
            // 'default_font' => 'sans-serif'
        ]);

        // read XLSX
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($_FILES["xlsx"]["tmp_name"]);

        // manipulate excel file
        $html = '<!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="utf-8">
                    <title>Quantités de variétés</title>
                    <style>
                        body {
                            font-family: sans-serif !important;
                        }
                    </style>
                </head>
                <body>
                <h1>Quantités par variétés</h1>';
        $currentProduct = '';
        $i = 0; // line
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                // skip Excel Header
                if ($i >= 2) {
                    $cells = $row->getCells();

                    $productName = $cells[5]->getValue();
                    if ($currentProduct != $productName) {
                        $html .= '<h2>' . $productName . '</h2>';
                        $html .= '<p>Voici les quantités disponible de chacune des variétés dans les villes suivante ainsi que les numéros de lots</p>';
                        $currentProduct = $productName;
                    }

                    // sample output: Longueuil [6] - Aliens Cookies [8] - 48 [4] - L30211U [3]
                    $html .= '<p>'.$cells[6]->getValue().' - '.$cells[8]->getValue().' - '.$cells[4]->getValue().' - '.$cells[3]->getValue().'</p>';
                }
                $i++;
            }
            break;
        }
        $html .= '</body>';
        $html .= '</html>';
        $mpdf->WriteHTML($html);

        // close reader
        $reader->close();

        // output/download file to user
        $mpdf->Output('ChatGPT-ingest-doc.pdf', \Mpdf\Output\Destination::DOWNLOAD);

        // cancel other WordPress executions.
        die();
    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
        die('Error: ' . $e->getMessage());
    } catch (\Exception $e) {
        die('Error: ' . $e->getMessage());
    }
}
?>

<h1>XLSX to PDF Pour ChatPot</h1>
<h2>Veuillez entrer le fichier XLSX ci-dessous.</h2>
<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="convert" value="..." />
    <label>
        <input type="file" accept=".xlsx" name="xlsx" />
    </label>
    <input type="submit" value="Download as PDF" />
</form>
