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

if (!defined('_PS_VERSION_')) {
    exit;
}

require dirname(__FILE__) . '/vendor/autoload.php';

use LkEbp\LkEbpImport;

class LkEbp extends Module
{
    private $_html;

    private $importFolder;

    /**
     * Lk_Neonegoce constructor.
     */
    public function __construct()
    {
        $this->name = 'lkebp';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Lk Interactive';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->importFolder = _PS_ROOT_DIR_ . '/import-ebp';

        parent::__construct();

        $this->displayName = $this->trans('Lk Interactive - EBP connector.', array(), 'Modules.Lkebp.Admin');
        $this->description = $this->trans('This module is made for import data from ebp crm.', array(), 'Modules.Lkebp.Admin');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall ?', 'ebp');
    }

    /**
     * @return mixed
     */
    public function install()
    {
        include dirname(__FILE__) . '/sql/install.php';
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $this->createImportFolder();

        return parent::install();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        include dirname(__FILE__) . '/sql/uninstall.php';

        $this->deleteImportFolder();

        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    /**
     * Create import folder
     * @since 1.0.0
     */
    public function createImportFolder()
    {
        /* Test if import folder exist*/
        if (!is_dir($this->importFolder)) {
            mkdir($this->importFolder, 0755, true);
            chmod($this->importFolder, 0755);
        }
    }

    /**
     * Delete import folder
     * @since 1.0.0
     */
    public function deleteImportFolder()
    {
        /* Test if import folder exist*/
        if (is_dir($this->importFolder)) {
            rmdir($this->importFolder);
        }
    }

    /**
     * Return content in admin config module area
     *
     * @return string
     * @since 1.00.0
     */
    public function getContent()
    {
        $this->_html .= '';
        $this->_html .= $this->_postProcess();
        $this->_html .= $this->displayForm();

        return $this->_html;
    }

    /**
     * @return string|null
     */
    public function _postProcess()
    {
        if (Tools::isSubmit('SubmitImportEntity')) {
            if (Configuration::get('PS_SHOP_ENABLE')) {
                Tools::redirectAdmin('index.php?tab=AdminModules&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&importnotcontinue');
            } else {
                $entities = Tools::getValue('entity');
                $import_entity = [];
                if (is_array($entities) && !empty($entities)) {
                    foreach ($entities as $entity) {
                        $import_entity[$entity] = $entity;
                    }

                    $LkEpImport = new LkEbpImport($this->importFolder);
                    if ($LkEpImport->getImportFiles()) {
                        if ($LkEpImport->prepareImport($import_entity)) {
                            Tools::redirectAdmin('index.php?tab=AdminModules&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&validation');
                        }
                    }
                }
            }
        }
    }

    /**
     * Prepare smarty variable cor config tpl
     *
     * @return mixed
     */
    public function displayForm()
    {
        $store_url = $this->context->link->getBaseLink();
        $this->context->smarty->assign(array(
            'cron_url' => array(
                'category' => $store_url . 'modules/'.$this->name.'/'.$this->name.'-cron.php?token=' . Tools::substr(Tools::encrypt($this->name.'/cron'), 0, 10) . '&entity=category&id_shop=' . $this->context->shop->id,
                'product' => $store_url . 'modules/'.$this->name.'/'.$this->name.'-cron.php?token=' . Tools::substr(Tools::encrypt($this->name.'/cron'), 0, 10) . '&entity=product&id_shop=' . $this->context->shop->id,
                'combination' => $store_url . 'modules/'.$this->name.'/'.$this->name.'-cron.php?token=' . Tools::substr(Tools::encrypt($this->name.'/cron'), 0, 10) . '&entity=combination&id_shop=' . $this->context->shop->id,

            ),
            'logs' => $this->getLogs(),
            'shop_enable' => Configuration::get('PS_SHOP_ENABLE'),
            'import_form' => './index.php?tab=AdminModules&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '',
        ));
        return $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
    }

    /**
     * Get formatted logs based on current month file
     *
     * @return mixed
     * @since 1.0.0
     */
    private function getLogs()
    {
        $file = dirname(__FILE__) . '/logs/logs-import-' . date('m-Y') . '.log';
        $Logs = [];
        if (file_exists($file)) {
            $handle = Tools::file_get_contents($file);
            $logsArray = explode('---', $handle);
            foreach ($logsArray as $key => $logs) {
                if (!empty($logs)) {
                    $contentLog = explode('[#]', $logs);
                    $Logs[$key]['Date'] = explode('[##]', $contentLog[1]);
                    foreach ($contentLog as $k => $log) {
                        if (!empty(trim($log)) && $k != 1) {
                            $Logs[$key]['infos'][] = $log;
                        }
                    }
                }
            }

            return $Logs;
        }
    }
}
