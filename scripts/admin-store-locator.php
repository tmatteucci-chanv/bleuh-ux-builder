<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

if (isset($_POST["import_stores"])) {
    if (bleuh_save_store_data()) {
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
?><h1>Localisateur de magasin</h1>

<h2>Importer tous les produits et magasins par Google Drive et SQDC.</h2>
<form action="" method="post">
    <input type="hidden" name="import_stores" value="true">
    <p><input type="submit" value="Importer produits & magasins" /></p>
</form>

<label>
    <input type="checkbox" checked="checked" disabled="disabled" /> Importer les données de magasins par Google Drive & SQDC automatiquement tous les jours.
</label>
