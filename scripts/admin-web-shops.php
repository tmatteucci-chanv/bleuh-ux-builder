<?php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

if (isset($_POST["import-web-vars"]) && isset($_FILES["xlsx"])) {
    if (bleuh_import_lots($_FILES["xlsx"]["tmp_name"])) {
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

} elseif (isset($_POST["import-web-vars-now"])) {
    if (bleuh_cron_lots_import()) {
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
} elseif (isset($_POST["import-web-qty"]) && isset($_FILES["csv"])) {
    if (bleuh_import_lots($_FILES["csv"]["tmp_name"])) {
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
} elseif (isset($_POST["import-web-qty-now"])) {
    if (bleuh_cron()) {
        ?>
        <div class="notice notice-success is-dismissible">
            <h2>Importation complétée avec succès.</h2>
        </div>
        <?php
    } else {
        ?>
        <div class="notice notice-error is-dismissible">
            <h2>Importation Échouée.</h2>
        </div>
        <?php
    }
} elseif (isset($_POST["export-metrogreen"])) {
    @bleuh_cron(true);
}
?>

<h1>Importation d'inventaire et de variétés Web</h1>
<p>Vous devez faire l'importation des lots en deux étapes consécutives</p>
<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="import-web-vars" value="..." />
    <h2>Étape 1: Veuillez entrer le fichier XLSX ci-dessous pour l'attribution de nom de variété par lot.</h2>
    <div class="notice notice-warning">
        LES DONNÉES DE LOTS SONT PRISES EN CHARGE À LA <strong>1ère FEUILLE</strong>,<br>
        <strong>
            COLONNE A: NOM DU LOT<br>
            COLONNE B: NOM DE LA VARIÉTÉ
        </strong>
    </div>
    <p>Dernier import de lots: <?php echo get_option( 'bleuh_lots_import' ); ?> (<?php
        global $wpdb;
        echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bleuh_lots");
        ?>
        lots)</p>
    <label>
        <input type="file" accept=".xlsx" name="xlsx" />
    </label>
    <p>
        <input type="submit" value="Importer les nom de variétés par lots" />
    </p>
</form>


<label>
    <input type="checkbox" checked="checked" disabled="disabled" /> Importer les données d'inventaire et de variétés Web par Google Drive automatiquement tous les jours.
</label>

<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="import-web-vars-now" value="..." />
    <p>
        <input type="submit" value="Importer les données de Google Drive maintenant" />
    </p>
</form>


<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="import-web-qty" value="..." />
    <h2>Étape 2: Veuillez entrer le fichier CSV ci-dessous pour l'attribution d'inventaire Web.</h2>
    <div class="notice notice-warning">
        LES DONNÉES D'INVENTAIRE WEB SONT PRISES EN CHARGE EN CSV,<br>
        <strong>
            COLONNE E: GTIN DE LA SQDC<br>
            COLONNE H: NUMÉRO DE LOT<br>
            COLONNE J: QUANTITÉ
        </strong>
    </div>
    <p>Dernier import de variétés & inventaire Web: <?php echo get_option( 'bleuh_web_vars_import' ); ?> (<?php
        global $wpdb;
        echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bleuh_vars WHERE postal_code LIKE 'WEB'");
        ?>
        variétés Web)</p>
    <label>
        <input type="file" accept=".csv" name="csv" />
    </label>
    <p>
        <input type="submit" value="Importer l'inventaire Web" />
    </p>
</form>

<label>
    <input type="checkbox" checked="checked" disabled="disabled" /> Importer les données de MetroGreen par sFTP automatiquement tous les jours.
</label>

<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="import-web-qty-now" value="..." />
    <p>
        <input type="submit" value="Importer les données de MetroGreen maintenant" />
    </p>
</form>

<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="export-metrogreen" value="..." />
    <p>
        <input type="submit" value="Télécharger les dernières données de MetroGreen" />
    </p>
</form>
