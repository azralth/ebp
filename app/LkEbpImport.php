<?php
namespace LkEbp;

use LkEbp\Entity\EntityCategory;
use LkEbp\Entity\EntityCombination;
use LkEbp\Entity\EntityProduct;
use PrestaShop\PrestaShop\Adapter\Shop\Context;

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

class LkEbpImport
{
    /* File import*/
    private $files;

    /* (array) current line*/
    private $line;

    /* Folder import files*/
    public $rootFolder;

    /* Module Folder import files*/
    public $moduleFilesFolder;

    /* (object) instance of Prestashop db*/
    private $db;

    private $entity = array('import_category','import_product','import_combination');

    /**
     * LkEbpImport constructor.
     * @param $file
     */
    public function __construct($folder)
    {
        $this->setDb(\Db::getInstance());
        $this->setRootFolder($folder);
        $this->setModuleFilesFolder();
    }

    public function prepareImport($entities)
    {
        /* Test if modules folder is not empty*/
        $files = array_slice(scandir($this->moduleFilesFolder), 2);
        if (count($files) >= 3 && count($this->files) == 3){
            foreach ($this->files as $entity => $file) {
                $file = $this->moduleFilesFolder.$this->files[$entity];
                switch ($entity) {
                    case 'import_category':
                        if (LkTools::fileExists($file)) {
                            if (isset($entities[$entity])) {
                                $ImportCategory = new EntityCategory($file, 'category');
                                $ImportCategory->startImport();
                            }
                        }
                        break;
                    case 'import_product':
                        if (LkTools::fileExists($file)) {
                            if (isset($entities[$entity])) {
                                $ImportProduct = new EntityProduct($file, 'product');
                                $ImportProduct->startImport();
                            }
                        }
                        break;
                    case 'import_combination':
                        if (LkTools::fileExists($file)) {
                            if (isset($entities[$entity])) {
                                $ImportProduct = new EntityCombination($file, 'combination');
                                $ImportProduct->startImport();
                            }
                        }
                        break;
                }
            }
        } else {
            LkWriteLog::addLog('failed_prepare_import');
            return false;
        }
    }

    /**
     * Prepare an array with the 3 files we need to start the import
     * Go to the root folder of Prestashop, check if files exists
     * Move files into module folder (because we're delete these files in root folder after success import)
     *
     * @author Lk interactive
     * @since 1.0.0
     * @return array|false
     */
    public function getImportFiles()
    {
        /* Test if import folder exist*/
        LkWriteLog::addLog('success_start_import');

        if (!is_dir($this->rootFolder)) {
            LkWriteLog::addLog('failed_start_import');
            return false;
        } else {
            /* Move file to root prestashop folder from Module folder*/
            /* Check if file exist their date deploy*/
            $files = array_slice(scandir($this->rootFolder), 2);
            if (empty($files)) {
                LkWriteLog::addLog('failed_load_files');
                echo 'failed load import files.';
            } else {
                $date = date('Y-m-d');
                foreach ($files as $file) {
                    switch ($file) {
                        case $date.'_Export gammes.xlsx':
                            $this->files['import_category'] = $date.'_Export gammes.xlsx';
                            break;
                        case $date.'_Export Marque-Modele.xlsx':
                            $this->files['import_product'] = $date.'_Export Marque-Modele.xlsx';
                            break;
                        case $date.'_Export produits.xlsx':
                            $this->files['import_combination'] = $date.'_Export produits.xlsx';
                            break;
                    }
                }

                /* Test if we get all necessary file to import*/
                if (!is_null($this->files) && count($this->files) == 3 ) {
                    /* move file into module folder*/
                    $i = 0;
                    foreach ($this->files as $file) {
                        if (!copy($this->rootFolder.'/'.$file, $this->moduleFilesFolder.$file)) {
                            LkWriteLog::addLog('module_file_missing', $file);
                            \Context::getContext()->smarty->assign(array(
                                'error_import' => 'File'.$file.' can\'t be move in module folder'
                            ));
                            return false;
                        } else {
                            $i++;
                        }
                        if ($i == 3) {
                            return true;
                        }
                    }
                } else {
                    $file_missing = (string)'';
                    foreach ($this->entity as $entity) {
                        if (!isset($this->files[$entity])) {
                            $file_missing .= ' ['.$entity.']';
                        }
                    }
                    LkWriteLog::addLog('file_missing', $file_missing);
                    \Context::getContext()->smarty->assign(array(
                      'error_import' => 'File'.$file_missing.'are missing. Import can\'t be continue'
                    ));
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Setter rootFolder
     *
     * @param $folder
     */
    public function setRootFolder($folder)
    {
        $this->rootFolder = $folder;
    }

    /**
     * Setter moduleFilesFolder
     */
    public function setModuleFilesFolder()
    {
        $this->moduleFilesFolder = dirname(__FILE__) . '/../files/';;
    }

    /**
     * setter DB
     *
     * @param (object) $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }
}
