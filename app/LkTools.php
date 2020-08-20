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
class LkTools
{

    /**
     * Get a clean string for slug or seao link
     *
     * @param $string
     * @return string
     * @author Lk interactive
     * @since 1.0.0
     */
    public static function slugify($string)
    {
        return \Tools::strtolower(str_replace(array(' ', '.'), '-', \Tools::replaceAccentedChars($string)));
    }

    /**
     * Get aa random string for ssecurity
     *
     * @param int $length
     * @return string
     * @author Lk interactive
     * @since 1.0.0
     */
    public function genRandomString($length = 8)
    {
        $characters = "0123456789abcdefghijklmnopqrstuvwxyz";
        $string = "";
        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, \Tools::strlen($characters) - 1)];
        }
        return $string;
    }

    /**
     * Check if file exist
     *
     * @param int $file
     * @return bool
     * @author Lk interactive
     * @since 1.0.0
     */
    public static function fileExists($file) {
        if (!file_exists($file)) {
            LkWriteLog::addLog('file_no_exist', $file);
            return false;
        }
        return true;
    }

    /**
     * Ecriture de log
     *
     * @author Lk interactive
     * @since 1.0.0
     *
     */
    public static function writeLog($texte, $file)
    {
        $log_dir = dirname(__FILE__) . '../logs/';
        $month = date('m-Y');
        $logfile = $log_dir . 'logs-' . $file . '-' . $month . '.log';

        if (!file_exists($logfile)) {
            fopen($logfile, "w") or die('The file caan\'t be created' . $logfile);
        }
        if (file_exists($logfile)) {
            if ($id = fopen($logfile, "a+")) {

                $nouveau_contenu = '[#]Date : [' . date("d/m/Y H:i:s") . '] ' . $texte;
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
