<?php
/**
 *  Copyright (C) Lk Interactive - All Rights Reserved.
 *
 *  This is proprietary software therefore it cannot be distributed or reselled.
 *  Unauthorized copying of this file, via any medium is strictly prohibited.
 *  Proprietary and confidential.
 *
 * @author    Lk Interactive <contact@lk-interactive.fr>
 * @copyright 2020.
 * @license   Commercial license
 */


/*
 * This file can be called using a cron to import entity from EBP files automatically
 */
use LkEbp\LkEbpImport;

include(dirname(__FILE__) . '/../../config/config.inc.php');

/* Check security token */
if (!Tools::isPHPCLI()) {
    include(dirname(__FILE__) . '/../../init.php');

    if (Tools::substr(Tools::encrypt('lkebp/cron'), 0, 10) != Tools::getValue('token') || !Module::isInstalled('lkebp')) {
        die('Bad token');
    }
}

$lkebp = Module::getInstanceByName('lkebp');

/* Check if the module is enabled */
if ($lkebp->active) {
    /* Check if the requested shop exists */
    $shops = Db::getInstance()->ExecuteS('SELECT id_shop FROM `' . _DB_PREFIX_ . 'shop`');
    $list_id_shop = array();
    foreach ($shops as $shop) {
        $list_id_shop[] = (int) $shop['id_shop'];
    }

    // If multiple shop
    $id_shop = (Tools::getIsset('id_shop') && in_array(Tools::getValue('id_shop'), $list_id_shop)) ? (int) Tools::getValue('id_shop') : (int) Configuration::get('PS_SHOP_DEFAULT');
    $lkebp->cron = true;

    /* Get entity ask */
    if (!Tools::getValue('entity')) {
        die('Bad entity');
    }
    $entity = 'import_'.Tools::getValue('entity');

    $import_entity = [];
    $import_entity[$entity] = $entity;

    $LkEpImport = new LkEbpImport(_PS_ROOT_DIR_ . '/import-ebp');
    if ($LkEpImport->getImportFiles()) {
        $LkEpImport->prepareImport($import_entity);
    }
}
