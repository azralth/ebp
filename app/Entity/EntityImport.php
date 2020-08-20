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
namespace LkEbp\Entity;

use LkEbp\LkWriteLog;
use LkEbp\Models\LkEbpLinkCodeModel;

class EntityImport
{
    /** @var string $entity */
    public $entity;

    /** @var array $data */
    public $data = [];

    /** @var array $entityInPs */
    public $entityInPs = [];

    /** @var string $file */
    public $file;

    /** @var bool $shopIsFeatureActive */
    public $shopIsFeatureActive;

    /** @var object $db */
    public $db;

    /** @var int $langId */
    public $langId;

    /** @var int $shopId */
    public $shopId;

    /** @var string $startScript */
    public $startScript;

    /**
     * EntityImport constructor.
     * @param $data
     * @since 1.0.0
     */
    public function __construct($file, $entity)
    {
        $this->setFile($file);
        $this->setEntity($entity);
        $this->setDb();
        $this->setLangId();
        $this->setShopId();
        $this->setShopIsFeatureActive();
        $this->hydrate();
    }

    /**
     * Hydrate class with all data before import
     * Date is provided by the excel file
     * Load the file with spreadsheet library and hydrate the class with his data
     *
     * @param array $data
     * @since 1.0.0
     */
    public function hydrate()
    {
        $xlsx = \SimpleXLSX::parse($this->file);
        $header_values = [];
        foreach ( $xlsx->rows() as $k => $r ){
            if ( $k === 0 ){
                $header_values = $r;
                continue;
            }
            $this->data[] = array_combine($header_values, $r);
        }

        $this->getEntityInPs();
    }

    /**
     * Rettrive all occurence entity they are already in Prestashop
     * Return an arry use for disable all occurence that are not
     * ine the current entity import
     *
     * @since 1.0.0
     */
    private function getEntityInPs()
    {
        $entityInPs = LkEbpLinkCodeModel::getAllIdEntity($this->entity);
        if (!empty($entityInPs)) {
            foreach ($entityInPs as $data) {
                $this->entityInPs[$data['ref_ebp']] = array(
                    'id_ebp' =>$data['id_ebp'],
                    'id_entity' =>$data['id_entity']
                );
            }
        }
    }

    /**
     * copyImg copy an image located in $url and save it in a path
     * according to $entity->$id_entity .
     * $id_image is used if we need to add a watermark.
     *
     * @param int $id_entity id of product or category (set in entity)
     * @param int $id_image (default null) id of the image if watermark enabled
     * @param string $url path or url to use
     * @param string $entity 'products' or 'categories'
     * @param bool $regenerate
     *
     * @return bool
     */
    protected static function copyImg($id_entity, $id_image = null, $url = '', $entity = 'products', $regenerate = true)
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', \Configuration::get('WATERMARK_TYPES'));

        switch ($entity) {
            default:
            case 'products':
                $image_obj = new \Image($id_image);
                $path = $image_obj->getPathForCreation();

                break;
        }

        $url = urldecode(trim($url));
        $parced_url = parse_url($url);

        if (isset($parced_url['path'])) {
            $uri = ltrim($parced_url['path'], '/');
            $parts = explode('/', $uri);
            foreach ($parts as &$part) {
                $part = rawurlencode($part);
            }
            unset($part);
            $parced_url['path'] = '/' . implode('/', $parts);
        }

        if (isset($parced_url['query'])) {
            $query_parts = array();
            parse_str($parced_url['query'], $query_parts);
            $parced_url['query'] = http_build_query($query_parts);
        }

        if (!function_exists('http_build_url')) {
            require_once _PS_TOOL_DIR_ . 'http_build_url/http_build_url.php';
        }

        $url = http_build_url('', $parced_url);

        $orig_tmpfile = $tmpfile;

        if (\Tools::copy($url, $tmpfile)) {
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!\ImageManager::checkImageMemoryLimit($tmpfile)) {
                @unlink($tmpfile);

                return false;
            }

            $tgt_width = $tgt_height = 0;
            $src_width = $src_height = 0;
            $error = 0;
            \ImageManager::resize($tmpfile, $path . '.jpg', null, null, 'jpg', false, $error, $tgt_width, $tgt_height, 5, $src_width, $src_height);
            $images_types = \ImageType::getImagesTypes($entity, true);

            if ($regenerate) {
                $path_infos = array();
                $path_infos[] = array($tgt_width, $tgt_height, $path . '.jpg');
                foreach ($images_types as $image_type) {
                    $tmpfile = self::getBestPath($image_type['width'], $image_type['height'], $path_infos);

                    if (\ImageManager::resize(
                        $tmpfile,
                        $path . '-' . stripslashes($image_type['name']) . '.jpg',
                        $image_type['width'],
                        $image_type['height'],
                        'jpg',
                        false,
                        $error,
                        $tgt_width,
                        $tgt_height,
                        5,
                        $src_width,
                        $src_height
                    )) {
                        // the last image should not be added in the candidate list if it's bigger than the original image
                        if ($tgt_width <= $src_width && $tgt_height <= $src_height) {
                            $path_infos[] = array($tgt_width, $tgt_height, $path . '-' . stripslashes($image_type['name']) . '.jpg');
                        }
                        if ($entity == 'products') {
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) \Context::getContext()->shop->id . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) \Context::getContext()->shop->id . '.jpg');
                            }
                        }
                    } else {
                        LkWriteLog::addLog('img_wrong_format', $image_obj->id_product);
                    }
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        \Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                    }
                }
            }
        } else {
            @unlink($orig_tmpfile);

            return false;
        }
        unlink($orig_tmpfile);

        return true;
    }

    /**
     * Start import entity based on excel file
     *
     * @since 1.0.0
     */
    protected function startImport()
    {
        $time = microtime();
        $time = explode(' ', $time);
        $start = $time[1] + $time[0];
        $this->startScript = $start;
    }

    /**
     * En import.
     * Delette file and add log
     *
     * @sincee 1.0.0
     */
    protected function importEnd()
    {
        // Delete file
        unlink($this->file);

        // Calculate scripts time
        $time = microtime();
        $time = explode(' ', $time);
        $end = $time[1] + $time[0];
        $text = 'Import end '.round(($end - $this->startScript), 6).' seconds.';
        LkWriteLog::addLog('import_end', $text);
        \Context::getContext()->smarty->assign(array(
            'success_import' => true
        ));
    }

    /**
     * @param $tgt_width
     * @param $tgt_height
     * @param $path_infos
     * @return mixed|string
     * @since 1.0.0
     */
    protected static function getBestPath($tgt_width, $tgt_height, $path_infos)
    {
        $path_infos = array_reverse($path_infos);
        $path = '';
        foreach ($path_infos as $path_info) {
            list($width, $height, $path) = $path_info;
            if ($width >= $tgt_width && $height >= $tgt_height) {
                return $path;
            }
        }

        return $path;
    }

    /**
     * Add support for utf-8
     * @since 1.0.0
     */
    public static function setLocale()
    {
        $iso_lang = trim(Tools::getValue('iso_lang'));
        setlocale(LC_COLLATE, \Tools::strtolower($iso_lang) . '_' .\Tools:: strtoupper($iso_lang) . '.UTF-8');
        setlocale(LC_CTYPE, \Tools::strtolower($iso_lang) . '_' . \Tools::strtoupper($iso_lang) . '.UTF-8');
    }

    /**
     * Setter $db
     * @since 1.0.0
     */
    public function setDb()
    {
        $this->db = \Db::getInstance();
    }

    /**
     * Setter $file
     * @param string $file
     * @since 1.0.0
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Setter $entity
     * @param string $entity
     * @since 1.0.0
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Setter $langId
     * @param string $laangId
     * @since 1.0.0
     */
    public function setLangId()
    {
        $this->langId = (int)\Configuration::get('PS_LANG_DEFAULT');;
    }

    /**
     * Setter $langId
     * @param string $laangId
     * @since 1.0.0
     */
    public function setShopId()
    {
        $this->shopId = \Context::getContext()->shop->id;
    }

    /**
     * Setter $shopIsFeatureActive
     * @since 1.0.0
     */
    public function setShopIsFeatureActive()
    {
        $this->shopIsFeatureActive = \Shop::isFeatureActive();
    }
}
