<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

if (isset($_POST["bleuh_fix_seo"])) {
    if ( class_exists( '\Yoast\WP\SEO\Actions\Indexation\Indexable_Indexation_Action' ) ) {
        $indexation = \Yoast\WP\SEO\Actions\Indexation\Indexable_Indexation_Action::get_instance();
        $indexation->index();
    }
}

if (isset($_POST["flush_rules"])) {
    flush_rewrite_rules();
}

if (isset($_POST["clear_sg_cache"])) {
    clear_sg_cache();
}

if (isset($_POST["clear_logs"])) {
    bleuh_clear_logs();
}

if (isset($_POST["bleuh_fix_tables"])) {
    bleuh_fix_tables();
}

if (isset($_POST["bleuh_save_deliveries_data"])) {
    bleuh_save_deliveries_data();
}

if (isset($_POST["bleuh_missing_lots"])) {
    bleuh_missing_lots();
}

if (isset($_POST["bleuh_ON_stores_import"])) {
    bleuh_ON_stores_import();
}

if (isset($_POST["bleuh_fill_store_geo_location"])) {
    bleuh_fill_store_geo_location();
}

if (isset($_POST["bleuh_cron_lots_import"])) {
    bleuh_cron_lots_import();
}

if (isset($_POST["bleuh_cron"])) {
    try {
        bleuh_cron();
    } catch (Exception $e) {
        bleuh_log("Metrogreen cron error: ".$e->getMessage());
    }
}

if (isset($_GET["metrogreen-download"])) {
    bleuh_cron(true);
}
if (isset($_POST["bleuh_save_store_data"])) {
    bleuh_save_store_data();
}

if (isset($_POST["bleuh_phpinfo"])) {
    phpinfo();
}

if (isset($_POST["bleuh_del_on_geo"])) {
    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bleuh_stores';
        $res =$wpdb->get_results("SELECT * FROM $table_name WHERE number LIKE 'ONTARIO-%';", ARRAY_A);
        $pc_list = [];
        foreach ($res as $store) {
            delete_transient("PC_".$store['postal_code']);
            $pc_list[]= $store['postal_code'];
        }
        $pc_list_sql = array_map(function($pc) use ($wpdb) {
            return "'" . esc_sql($pc) . "'";
        }, $pc_list);
        $res =$wpdb->query("UPDATE $table_name SET location = POINT(0, 0) WHERE postal_code IN (".implode(', ', $pc_list_sql).");");
    } catch (Exception $e) {
        bleuh_log("Delete geo location error: ".$e->getMessage());
    }
}
if (isset($_POST["bleuh_display_geos"])) {
    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bleuh_stores';
        $res =$wpdb->get_results("SELECT *, ST_X(location) AS x_longitude, ST_Y(location) y_latitude FROM $table_name ORDER BY number;", ARRAY_A);
        echo "<pre>";
        print_r($res);
        echo "</pre>";
    } catch (Exception $e) {
        bleuh_log("Display geo locations error: ".$e->getMessage());
    }
}
if (isset($_POST['bleuh_csv_upload_web']) && !empty($_FILES['csv']['name'])) {
    bleuh_update_option_with_date("bleuh_web_inventory_csv_import");
    $file = $_FILES['csv'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = $file['tmp_name'];
        if (bleuh_import_web_inventories($filename)) {
            bleuh_update_option_with_date("bleuh_web_inventory_csv_import_success");
            bleuh_log("CSV file imported successfully (".$_FILES['csv']['name'].").");
        } else {
            bleuh_log("Failed importing CSV file..");
        }
    } else {
        bleuh_log("Error uploading CSV file.");
    }
}

if (isset($_POST["bleuh_fix_vars_forms"])) {
    bleuh_fix_vars_forms();
}

if (isset($_POST["bleuh_fix_prod_forms"])) {
    bleuh_fix_prod_forms();
}

?>

<div class="admin-landing-page">
    <h1>Administration Bleuh</h1>
    <p>Module d'administration Bleuh.</p>

    <h2>Logs</h2>
    <?php bleuh_display_logs(); ?>
    <form action="" method="post">
        <input type="hidden" name="clear_logs" value="true">
        <p><input type="submit" value="Clear Logs" /></p>
    </form>

    <hr />

    <h2>Tâches automatisées (Cron jobs)</h2>

    <h3>Téléchargements des détaillants d'Ontario:</h3>
    <p>(Prends les données de <a href="https://docs.google.com/spreadsheets/d/<?php echo ON_STORES_DOC_ID; ?>/edit" target="_blank">Master Bleuh Ontario - SKU_Store distribution</a>)</p>
    <div class="notice notice-warning">
        Les données sont prises en charge à la <strong>1ère feuille</strong>,<br>
        <strong>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_ADDRESS); ?>: Adresse du détaillant<br>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_NAME); ?>: Nom du détaillant<br>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_GTIN); ?>: GTIN du produit<br>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_QTY); ?>: Quantité du produit<br>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_P_COLLECTION); ?>: Collection du produit<br>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_P_NAME); ?>: Nom du produit<br>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_P_BLEND); ?>: Mélange<br>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_P_FORMAT); ?>: Format<br>
            Colonne <?php echo bleuh_number_to_col(ON_STORES_P_WEIGHT); ?>: Poids<br>
        </strong>
    </div>
    <p>Fréquence de déclenchement: <strong>Une fois par jour</strong></p>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_on_stores_import_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_on_stores_import_success_run"); ?></strong></p>
    <br>
    <p>Sub-script: Ontario weekly rotation data</p>
    <div class="notice notice-warning">
        Les données sont prises en charge à la <strong><?php echo ON_LOTS_DOC_SHEET; ?>e feuille</strong>,<br>
        <strong>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_SKU); ?>: GTIN<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_PRODUCT_NAME); ?>: Nom du produit<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_PRODUCT_TYPE); ?>: Type de produit<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_PRODUCT_FORMAT); ?>: Format de produit<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_PRODUCT_WRAP_DATE); ?>: Date d'emballage<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_PRODUCT_LOT); ?>: Lot<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_VARIETY_NAME); ?>: Nom de variété<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_UNIT_THC); ?>: THC<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_UNIT_CBD); ?>: CBD<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_UNIT_COLLECTION); ?>: Collection<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_CATEGORY); ?>: Catégorie<br>
            Colonne <?php echo bleuh_number_to_col(ON_LOTS_DISPLAY); ?>: Afficher<br>
        </strong>
    </div>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_on_ingest_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_on_ingest_success"); ?></strong></p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_ON_stores_import" value="true">
        <p><input type="submit" value="Télécharger les détaillants d'Ontario maintenant" /></p>
    </form>

    <hr />

    <h3>Téléchargements des geo-localisations d'Ontario:</h3>
    <p>(Prends les données des détaillants d'Ontario et associe les code postal à latitude et longitude)</p>
    <div class="notice notice-warning">
        Les données sont prises en charge en groupe de <strong><?php echo ON_STORES_POSTAL_CODE_UPDATE_BATCH_SIZE; ?> (cached), <?php echo ON_STORES_POSTAL_CODE_BATCH_SIZE; ?> (non-cached) </strong><br>
    </div>
    <p>Fréquence de déclenchement: <strong>Tous les 10 minutes</strong></p>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_on_geo_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_on_geo_success"); ?></strong></p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_fill_store_geo_location" value="true">
        <p><input type="submit" value="Télécharger les geo-localisations d'Ontario" /></p>
    </form>

    <hr />

    <h3>Effacer les geo-localisations d'Ontario:</h3>
    <p>(Efface le cache de latitude et longitude des détaillants d'Ontario)</p>
    <p>Fréquence de déclenchement: <strong>Manuelle (Non-automatisée)</strong></p>
    <p>Aucun logs pour cette tâche.</p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_del_on_geo" value="true">
        <p><input type="submit" value="Effacer les geo-localisations d'Ontario" /></p>
    </form>
    <form action="" method="post">
        <input type="hidden" name="bleuh_display_geos" value="true">
        <p><input type="submit" value="Afficher tous les geo-localisations" /></p>
    </form>

    <hr />

    <h3>Téléchargements des lots du Québec:</h3>
    <p>(Prends les données de <a href="https://docs.google.com/spreadsheets/d/<?php echo LOTS_DOC_ID; ?>/edit" target="_blank">Master Lots</a>)</p>
    <div class="notice notice-warning">
        Les données sont prises en charge à la <strong>1ère feuille</strong>,<br>
        <strong>
            Colonne <?php echo bleuh_number_to_col(LOTS_DOC_COL_LOT); ?>: Lot<br>
            Colonne <?php echo bleuh_number_to_col(LOTS_DOC_COL_VAR); ?>: Variété<br>
            Colonne <?php echo bleuh_number_to_col(LOTS_DOC_COL_THC); ?>: THC<br>
            Colonne <?php echo bleuh_number_to_col(LOTS_DOC_COL_CBD); ?>: CBD<br>
            Colonne <?php echo bleuh_number_to_col(LOTS_DOC_COL_WRAP_DATE); ?>: Date d'emballage<br>
        </strong>
    </div>
    <p>Fréquence de déclenchement: <strong>Toutes les heures</strong></p>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_lots_imports_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_lots_imports_success_run"); ?></strong></p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_cron_lots_import" value="true">
        <p><input type="submit" value="Télécharger les lots du Québec maintenant" /></p>
    </form>

    <hr />

    <h3>Téléchargements de l'inventaire Web par Metrogreen (via FTP):</h3>
    <p>(Prends les données de <a href="?page=bleuh-logs&metrogreen-download" target="_blank">Metrogreen</a>)</p>
    <div class="notice notice-warning">
        Les données sont prises en charge à la <strong>1ère feuille</strong>,<br>
        <strong>
            Colonne <?php echo bleuh_number_to_col(WEB_INV_DOC_COL_SKU); ?>: GTIN<br>
            Colonne <?php echo bleuh_number_to_col(WEB_INV_DOC_COL_LOT); ?>: Lot<br>
            Colonne <?php echo bleuh_number_to_col(WEB_INV_DOC_COL_QTY); ?>: Quantité<br>
            Colonne <?php echo bleuh_number_to_col(WEB_INV_DOC_COL_DATE); ?>: Date<br>
        </strong>
    </div>
    <p>Fréquence de déclenchement: <strong>Toutes les heures</strong></p>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_lots_metrogreen_import_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_lots_metrogreen_import_success_run"); ?></strong></p>
    <form action="/wp-admin/admin.php?page=bleuh-logs&nocachne=<?php echo md5(time()); ?>" method="post">
        <input type="hidden" name="bleuh_cron" value="true">
        <p><input type="submit" value="Télécharger l'inventaire Web par Metrogreen Maintenant (via FTP)" /></p>
    </form>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="bleuh_csv_upload_web" value="true">

        <p>Dernier déclenchement manuel: <strong><?php echo get_option("bleuh_web_inventory_csv_import"); ?></strong></p>
        <p>Dernier success: <strong><?php echo get_option("bleuh_web_inventory_csv_import_success"); ?></strong></p>

        <p>
            <input type="file" name="csv" accept=".csv" />
            <input type="submit" value="Téléverser (Upload) CSV d'inventaire Web" />
        </p>
    </form>

    <hr />

    <h3>Envoie de courriels pour contenu manquant:</h3>
    <p>(Vérifie le contenu qui manque pour traductions, lots, etc et envoie un courriel à: <?php echo BLEUH_EMAIL_MISSING_LOTS; ?> )</p>
    <p>Fréquence de déclenchement: <strong>Tous les jours à 8am</strong></p>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_missing_lots_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_missing_lots_success"); ?></strong></p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_missing_lots" value="true">
        <p><input type="submit" value="Envoyer un courriel maintenant" /></p>
    </form>

    <hr />

    <h3>Bug Fix: Quand la page de catalogue n'affiche pas de produit:</h3>
    <p>(Enregistrement des réglages des permaliens résolu cette problématique)</p>
    <p>Fréquence de déclenchement: <strong>Tous les 10 minutes</strong></p>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_permalinks_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_permalinks_success"); ?></strong></p>
    <form action="" method="post">
        <input type="hidden" name="flush_rules" value="true">
        <p><input type="submit" value="Enregistrer les réglages des permaliens maintenant" /></p>
    </form>

    <hr />

    <h3>Effacer la mémoire cache du coté serveur:</h3>
    <p>(Siteground cache clear)</p>
    <p>Fréquence de déclenchement: <strong>Manuelle (Non-automatisée)</strong></p>
    <p>Aucun logs pour cette tâche.</p>
    <form action="" method="post">
        <input type="hidden" name="clear_sg_cache" value="true">
        <p><input type="submit" value="Effacer la mémoire cache du coté serveur maintenant" /></p>
    </form>

    <hr />

    <h3>MySQL Fix:</h3>
    <p>Lorsque les tables MySQL change, ajuster la structure avec le delta.</p>
    <p>Fréquence de déclenchement: <strong>Manuelle (Non-automatisée)</strong></p>
    <p>Aucun logs pour cette tâche.</p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_fix_tables" value="true">
        <p><input type="submit" value="Ajuster les tables MySQL maintenant" /></p>
    </form>

    <hr />

    <h3>Télécharger les livraisons dans le système.</h3>
    <p>(Prends les données du <a href="https://drive.google.com/drive/u/0/folders/<?php echo DELIVERIES_DIR_ID; ?>" target="_blank">Dossier de livraisons</a>)</p>
    <div class="notice notice-warning">
        Les données sont prises en charge en groupe de <strong><?php echo DELIVERIES_UPDATE_BATCH_COUNT; ?> (automatisé), <?php echo DELIVERIES_MANUAL_UPDATE_BATCH_COUNT; ?> (manuel) </strong><br>
        <strong>
            Colonne <?php echo bleuh_number_to_col(DELIVERIES_DOC_STORE_NUMBER); ?>: Numéro de magasin<br>
            Colonne <?php echo bleuh_number_to_col(DELIVERIES_DOC_GTIN); ?>: GTIN<br>
            Colonne <?php echo bleuh_number_to_col(DELIVERIES_DOC_LOT); ?>: Lot<br>
            Colonne <?php echo bleuh_number_to_col(DELIVERIES_DOC_QTY); ?>: Quantité<br>
        </strong>
    </div>
    <p>Fréquence de déclenchement: <strong>Toutes les heures</strong></p>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_deliveries_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_deliveries_success"); ?></strong></p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_save_deliveries_data" value="true">
        <p><input type="submit" value="Télécharger les livraisons dans le système" /></p>
    </form>

    <hr />

    <h3>Télécharger les infos de localisation de magasin du Québec.</h3>
    <p>(Prends les données du <a href="https://docs.google.com/spreadsheets/d/<?php echo GTIN_DOC_ID; ?>" target="_blank">Fichier de google drive et de l'API de la SQDC</a>)</p>
    <div class="notice notice-warning">
        Les données sont prises en charge en groupe de <strong><?php echo DELIVERIES_UPDATE_BATCH_COUNT; ?> (automatisé), <?php echo DELIVERIES_MANUAL_UPDATE_BATCH_COUNT; ?> (manuel) </strong><br>
        <strong>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_COLL); ?>: Collection<br>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_PROD); ?>: Produit<br>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_GTIN); ?>: GTIN<br>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_WEIGHT); ?>: Poids<br>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_FORMAT); ?>: Format (Français)<br>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_FORMAT_EN); ?>: Format (Anglais)<br>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_BLEND); ?>: Mélange (Français)<br>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_BLEND_EN); ?>: Mélange (Anglais)<br>
            Colonne <?php echo bleuh_number_to_col(GTIN_DOC_COL_DISPLAY); ?>: Affichage<br>
        </strong>
    </div>
    <p>Fréquence de déclenchement: <strong>Toutes les heures</strong></p>
    <p>Dernier déclenchement: <strong><?php echo get_option("bleuh_store_locator_trigger"); ?></strong></p>
    <p>Dernière exécution: <strong><?php echo get_option("bleuh_store_locator_success"); ?></strong></p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_save_store_data" value="true">
        <p><input type="submit" value="Télécharger les infos de localisation de magasin du Québec" /></p>
    </form>

    <hr />

    <h3>PHPINFO</h3>
    <p>Afficher les infos serveur de PHP</p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_phpinfo" value="true">
        <p><input type="submit" value="Afficher" /></p>
    </form>

    <hr />

    <h3>Fix pour les champs sans data</h3>
    <p>Populer les champs de ACF avec des données de la DB</p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_fix_vars_forms" value="true">
        <p><input type="submit" value="Fix pour les variétés" /></p>
    </form>
    <form action="" method="post">
        <input type="hidden" name="bleuh_fix_prod_forms" value="true">
        <p><input type="submit" value="Fix pour les produits" /></p>
    </form>

    <hr />

    <h3>YOAST SEO re-indexing</h3>
    <p>Re-indexer le site avec YOAST SEO</p>
    <form action="" method="post">
        <input type="hidden" name="bleuh_fix_seo" value="true">
        <p><input type="submit" value="Fix pour le SEO" /></p>
    </form>

</div>