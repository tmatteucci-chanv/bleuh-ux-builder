<?php
require_once('wp-load.php');
require_once(ABSPATH . 'wp-content/plugins/bleuh-ux-builder/scripts/common.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>Test Téléchargement + Importation</h3>";

try {
    // 1. Initialisation Google
    $client = new Google_Client();
    $client->setAuthConfig(GOOGLE_SA_JSON);
    $client->setScopes([Google_Service_Drive::DRIVE_READONLY]);
    $service = new Google_Service_Drive($client);

    // 2. Téléchargement manuel du fichier vers un dossier temporaire
    echo "Téléchargement de Codebarres.xlsx (ID: " . GTIN_DOC_ID . ")...<br>";
    $file = $service->files->get(GTIN_DOC_ID, ["supportsAllDrives" => true, "alt" => "media"]);

    // On crée un fichier temporaire
    $temp_file = tempnam(sys_get_temp_dir(), 'bleuh_gtin_test');
    file_put_contents($temp_file, $file->getBody()->getContents());

    echo "✅ Fichier enregistré temporairement ici : $temp_file (" . filesize($temp_file) . " octets)<br>";

    // 3. Appel de ta fonction avec le CHEMIN (string) comme elle le demande
    echo "Appel de bleuh_import_GTINS($temp_file)...<br>";
    $gtins = bleuh_import_GTINS($temp_file);
    if (empty($gtins)) {
        bleuh_log("ALERTE : Aucun GTIN extrait de l'Excel. Annulation de la purge pour éviter de vider le site.");
        return false; // On arrête tout AVANT le DELETE    }

    echo "✅ SUCCÈS : " . count($gtins) . " GTINs extraits !<br>";
    echo "<pre>";
    print_r(array_slice($gtins, 0, 3));
    echo "</pre>";

    // Ménage
    unlink($temp_file);

}
catch (Throwable $e) {
    echo "❌ CRASH DÉTECTÉ : " . $e->getMessage() . "<br>";
    echo "Fichier : " . $e->getFile() . " (Ligne " . $e->getLine() . ")<br>";
}