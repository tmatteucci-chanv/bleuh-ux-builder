<?php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

if (isset($_POST["import-vars"]) && isset($_FILES["xlsx"])) {
    if (bleuh_import_strains($_FILES["xlsx"]["tmp_name"])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <h2>Importation manuelle complétée avec succès.</h2>
        </div>
        <?php
    } else {
        ?>
        <div class="notice notice-error is-dismissible">
            <h2>Importation manuelle échouée.</h2>
        </div>
        <?php
    }

}

if (isset($_POST["import-vars-now"])) {
    if (bleuh_cron_strains_import()) {
        ?>
        <div class="notice notice-success is-dismissible">
            <h2>Importation manuelle complétée avec succès.</h2>
        </div>
        <?php
    } else {
        ?>
        <div class="notice notice-error is-dismissible">
            <h2>Importation manuelle échouée.</h2>
        </div>
        <?php
    }

}

?>

<h1>Importation de variétés</h1>
<h2>Veuillez entrer le fichier XLSX ci-dessous.</h2>
<div class="notice notice-warning">
    LES DONNÉES DE VARIÉTÉS SONT PRISES EN CHARGE À LA <strong>4e FEUILLE</strong>,<br>
    <strong>
    COLONNE EH: GTIN<br>
    COLONNE EI: CODE POSTAL<br>
    COLONNE EV: VARIÉTÉ 1<br>
    COLONNE EW: VARIÉTÉ 2<br>
    COLONNE E[...]: VARIÉTÉ [...]
    </strong>
</div>
<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="import-vars" value="..." />
    <p>Dernier import de variétés: <?php echo get_option( 'bleuh_vars_import' ); ?> (<?php
            global $wpdb;
            echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bleuh_vars WHERE postal_code <> 'WEB'");
        ?>
        variétés non Web)</p>
    <label>
        <input type="file" accept=".xlsx" name="xlsx" />
    </label>
    <p>
        <input type="submit" value="Importer les variétés" />
    </p>
</form>

<label>
    <input type="checkbox" checked="checked" disabled="disabled" /> Importer les données de variété par Google Drive automatiquement tous les jours.
</label>

<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="import-vars-now" value="..." />
    <p>
        <input type="submit" value="Importer les données de Google Drive maintenant" />
    </p>
</form>
