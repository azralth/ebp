<?php

namespace LkEbp;

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
class LkWriteLog
{
    public static function addLog($log, $option = null) {
        $textLog = (string)'';
        switch ($log) {
            case 'success_start_import':
                $textLog = '--- ';
                $textLog .= '[#]Date : [' . date("d/m/Y H:i:s") . ']';
                $textLog .= '[##]Start import';
                self::writeLog($textLog);
                break;
            case 'failed_start_import':
                $textLog = '--- ';
                $textLog .= '[##]' . date("d/m/Y H:i:s") . ' : Import failed : The folder import in root web (import-ebp) not exist[##]';
                self::writeLog($textLog);
                break;
            case 'failed_load_files':
                $textLog .= '[#][' . date("H:i:s") . '] : The root folder is empty. Can\'t load import files';
                self::writeLog($textLog);
                break;
            case 'file_missing':
                $textLog .= '[#][' . date("H:i:s") . '] : File'.$option.' are missing. Import can\'t be continue';
                self::writeLog($textLog);
                break;
            case 'module_file_missing':
                $textLog .= '[#][' . date("H:i:s") . '] : File'.$option.' can\'t be move in module folder. Import can\'t be continue';
                self::writeLog($textLog);
                break;
            case 'failed_prepare_import':
                $textLog .= '[#][' . date("H:i:s") . ']  A problem occurs on prepareImport method of class LkEbpImport';
                self::writeLog($textLog);
                break;
            case 'file_no_exist':
                $textLog .= '[#][' . date("H:i:s") . ']  The file : ['.$option.'] can\'t be found in module files folder';
                self::writeLog($textLog);
                break;
            case 'category_file_empty':
                $textLog .= '[#][' . date("H:i:s") . '] The category file : [Export Gammes] is empty';
                self::writeLog($textLog);
                break;
            case 'category_import_success':
                $textLog .= '[#][' . date("H:i:s") . '] : Category import success. '.$option.'';
                self::writeLog($textLog);
                break;
            case 'product_file_empty':
                $textLog .= '[#][' . date("H:i:s") . '] The product file : [Export Marque-Modele] is empty';
                self::writeLog($textLog);
                break;
            case 'product_import_success':
                $textLog .= '[#][' . date("H:i:s") . '] : Product import success. '.$option.'';
                self::writeLog($textLog);
                break;
            case 'img_copy_failed':
                $textLog .= '[#][' . date("H:i:s") . '] : Error copying image : . '.$option.'';
                self::writeLog($textLog);
                break;
            case 'img_copy_success':
                $textLog .= '[#][' . date("H:i:s") . '] : Success copying image : . '.$option.'';
                self::writeLog($textLog);
                break;
            case 'img_wrong_format':
                $textLog .= '[#][' . date("H:i:s") . '] : Wrong format image : . '.$option.'';
                self::writeLog($textLog);
                break;
            case 'group_import_failed':
                $textLog .= '[#][' . date("H:i:s") . '] : Import group comb failed : . '.$option.'';
                self::writeLog($textLog);
                break;
            case 'attribute_import_failed':
                $textLog .= '[#][' . date("H:i:s") . '] : Import attribute comb failed : . '.$option.'';
                self::writeLog($textLog);
                break;
            case 'combination_import_success':
                $textLog .= '[#][' . date("H:i:s") . '] : Combination import success: . '.$option.'';
                self::writeLog($textLog);
                break;
            case 'import_end':
                $textLog .= '[#][' . date("H:i:s") . '] : End import: . '.$option.'';
                self::writeLog($textLog);
                break;
        }
    }

    /**
     * Ecriture de log
     *
     * @author Lk interactive
     * @since 1.0.0
     *
     */
    public static function writeLog( $texte, $file='import' )
    {
        $log_dir = dirname(__FILE__) . '/../logs/';
        $month = date('m-Y');
        $logfile = $log_dir . 'logs-' . $file . '-' . $month . '.log';

        if (!file_exists($logfile)) {
            fopen($logfile, "w") or die('The file caan\'t be created' . $logfile);
        }
        if (file_exists($logfile)) {
            if ($id = fopen($logfile, "a+")) {

                $nouveau_contenu = $texte;
                $nouveau_contenu .= "\r\n";
                fwrite($id, $nouveau_contenu);
                fclose($id);
                return true;
            } else {
                return false;
            }
        }
    }
}
