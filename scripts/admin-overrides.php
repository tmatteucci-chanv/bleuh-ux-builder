<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'delete_var_override') {
        $store_number = $_POST['store_number'];
        $GTIN = $_POST['GTIN'];
        $lot = $_POST['lot'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'bleuh_store_lot_override';
        $wpdb->delete($table_name, array('store_number' => $store_number, 'GTIN' => $GTIN, 'lot' => $lot));
    }
}

?>

<h1>Variétés temporaires (Overrides)</h1>
<h2>Vous trouverez ici-bas les variétés temporaires</h2>
<p>Les variétés en liste remplacent les données automatisées</p>
<p>À noté: Les ordres d'affichage sont a titre d'indication lors de l'enregistrement.</p>
<p>Par exemple: Si une variété est enregistrée avec un ordre d'affichage de 1 et qu'elle est épuisée, la variété suivante sera affichée, mais l'ordre d'affichage du tableau sera tout de même 1.</p>

<table class="lots">
    <tr>
        <th>Date d'écriture</th>
        <th>Magasin</th>
        <th>Produit</th>
        <th>Lot</th>
        <th>Unités de produits (Semi-live/Approximatives)</th>
        <th>Unités à vendre avant<br>expiration de la règle</th>
        <th>Ordre d'affichage</th>
        <th>Quantité affichée</th>
        <th>Supprimer</th>
    </tr>
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'bleuh_store_lot_override';

    $results = $wpdb->get_results("SELECT o.*, v.variety_name, p.collection, p.name product, p.blend, p.format, s.name store
                                          FROM $table_name o
                                          LEFT JOIN {$wpdb->prefix}bleuh_lots v
                                              ON o.lot = v.lot
                                          LEFT JOIN {$wpdb->prefix}bleuh_products p
                                              ON o.GTIN = p.GTIN
                                          LEFT JOIN {$wpdb->prefix}bleuh_store_products sp
                                              ON o.GTIN = sp.GTIN
                                              AND o.store_number = sp.store_number
                                          LEFT JOIN {$wpdb->prefix}bleuh_stores s
                                              ON o.store_number = s.number
                                          ORDER BY o.store_number, o.GTIN, o.weight;");

    $qty_of_store_products = [];
    $previous_key = '';
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . $row->added_date . "</td>";
        echo "<td>" . $row->store_number  . " ({$row->store}) </td>";
        echo "<td>{$row->GTIN}: {$row->product} ({$row->blend} {$row->format})</td>";
        echo "<td>" . $row->lot . " (". $row->variety_name . ")</td>";
        echo "<td>{$row->new_live_qty}</td>";
        echo "<td>{$row->depleted}</td>";
        echo "<td>{$row->weight}</td>";
        echo "<td>{$row->displayed_qty}</td>";
        if ($row->depleted > 0) {
            echo "<td>
                <form action='' method='post'>
                    <input type='hidden' name='action' value='delete_var_override'>
                    <input type='hidden' name='store_number' value='{$row->store_number}'>
                    <input type='hidden' name='GTIN' value='{$row->GTIN}'>
                    <input type='hidden' name='lot' value='{$row->lot}'>
                    <input type='submit' value='Supprimer'>
                </form>
              </td>";
        } else {
            echo "<td>Expiré</td>";
        }
        echo "</tr>";
    }

    ?>
</table>