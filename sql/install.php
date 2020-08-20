<?php
/**
 *  Copyright (C) Lk Interactive - All Rights Reserved.
 *
 *  This is proprietary software therefore it cannot be distributed or reselled.
 *  Unauthorized copying of this file, via any medium is strictly prohibited.
 *  Proprietary and confidential.
 *
 * @author    Lk Interactive <contact@lk-interactive.fr>
 * @copyright 20220.
 * @license   Commercial license
 */

$sql = array();

// Install table for link ebp > presta code id
$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "lk_ebp_entity_link` (
          `id_ebp`     int(10) unsigned NOT NULL AUTO_INCREMENT,
          `ref_ebp`    VARCHAR(255) NOT NULL,
          `id_entity`  int(10) unsigned NOT NULL DEFAULT '0',
          `entity`       enum('product','category', 'combination') NOT NULL DEFAULT 'product',
          `date_add`   datetime NOT NULL,
          `date_upd`   datetime NOT NULL,
          `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
          PRIMARY KEY (`id_ebp`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=UTF8";

// Install table for multiple product tax
$sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "lk_ebp_tax` (
          `id_ebp_tax`      int(10) unsigned NOT NULL AUTO_INCREMENT,
          `id_product`      int(10) unsigned NOT NULL DEFAULT '0',
          `date_add`        datetime NOT NULL,
          `date_upd`        datetime NOT NULL,
          PRIMARY KEY (`id_ebp_tax`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=UTF8";

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return $sql;
    }
}
