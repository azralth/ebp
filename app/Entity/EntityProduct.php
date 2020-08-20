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

use LkEbp\LkTools;
use LkEbp\LkWriteLog;
use LkEbp\Models\LkEbpLinkCodeModel;

define('UNFRIENDLY_ERROR', false);

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
class EntityProduct extends EntityImport
{
    /**
     * EntityImport constructor.
     * @param $data
     * @since 1.0.0
     */
    public function __construct($file, $entity)
    {
        parent::__construct($file, $entity);
    }

    /**
     * Start import product based on excel file
     * This method use an array where we push product already in PS
     * At each iteration we check if product exist and update or create
     * and then disable or enable.
     *
     * @return bool
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function startImport()
    {
        parent::startImport();
        if (empty($this->data)) {
            LkWriteLog::addLog('product_file_empty');
            return false;
        }
        $i = 0;
        foreach ($this->data as $data) {
            if (!empty($data['id_modele'])) {
                $id_product = null;
                $id_category = LkEbpLinkCodeModel::getPsIdCategory($data['id_marque'], 'category');
                $link_rewrite = LkTools::slugify($data['Modele']);
                $id_lang = (int)\Configuration::get('PS_LANG_DEFAULT');

                // Check for link rewrite
                if (!\Validate::isLinkRewrite($link_rewrite)) {
                    $link_rewrite = (string)'product-' . \Tools::link_rewrite($link_rewrite);
                }

                $Product = new \Product($id_product, false, $id_lang);
                $Product->name = $data['Modele'];
                $Product->meta_title = LkTools::slugify($data['Modele']);
                $Product->link_rewrite = $link_rewrite;
                $Product->id_category = $id_category;
                $Product->id_category_default = $id_category;
                $Product->active = 1;

                // description_short
                if (isset($data['Resume']) && !empty($data['Resume'])) {
                    $Product->description_short = $data['Resume'];
                }

                // Long description
                if (isset($data['Description']) && !empty($data['Description'])) {
                    $Product->description = $data['Description'];
                }

                if (!$this->shopIsFeatureActive) {
                    $Product->id_shop_default = 1;
                } else {
                    $Product->id_shop_default = (int)$this->shopId;
                }

                // Cheeck if category already in ps
                if (isset($this->entityInPs[$data['id_modele']])) {
                    $Product->id = $this->entityInPs[$data['id_modele']]['id_entity'];
                    $Product->update();

                    // Update link model
                    LkEbpLinkCodeModel::updateEbpEntity($this->entityInPs[$data['id_modele']]['id_ebp']);

                    // Remove entry from entity already in ps
                    unset($this->entityInPs[$data['id_modele']]);
                    $i++;
                } else {
                    $Product->add();

                    // Add new entity object in link model.
                    $entity = array(
                        'id_entity' => $Product->id,
                        'ref_ebp' => $data['id_modele'],
                        'entity' => $this->entity
                    );
                    $LinkEbpModel = new LkEbpLinkCodeModel();
                    $LinkEbpModel->addNewEbpEntity($entity);
                    $i++;
                }

                // If features
                if (isset($data['Caracteristique']) && !empty($data['Caracteristique'])) {
                    foreach (explode(',', $data['Caracteristique']) as $single_feature) {
                        if (empty($single_feature)) {
                            continue;
                        }
                        $tab_feature = explode(':', $single_feature);
                        $feature_name = isset($tab_feature[0]) ? trim($tab_feature[0]) : '';
                        $feature_value = isset($tab_feature[1]) ? trim($tab_feature[1]) : '';
                        $position = isset($tab_feature[2]) ? (int)$tab_feature[2] - 1 : false;
                        $custom = false;
                        if (!empty($feature_name) && !empty($feature_value)) {
                            $id_feature = (int)\Feature::addFeatureImport($feature_name, $position);
                            $id_product = (int)$Product->id;
                            $id_feature_value = (int)\FeatureValue::addFeatureValueImport($id_feature, $feature_value, $id_product, $id_lang, $custom);
                            \Product::addFeatureProductImport($Product->id, $id_feature, $id_feature_value);
                        }
                    }
                }

                // Image
                if (isset($data['Image']) && !empty($data['Image'])) {
                    $Product->deleteImages();
                    $images = explode(',', $data['Image']);

                    $product_has_images = (bool)\Image::getImages($id_lang, (int)$Product->id);
                    foreach ($images as $key => $url) {
                        $url = trim(\Tools::getHttpHost(true) . __PS_BASE_URI__ . '/import-ebp/images/' . $url);
                        if (!empty($url)) {
                            $url = str_replace(' ', '%20', $url);

                            $image = new \Image(null, $id_lang);
                            $image->id_product = (int)$Product->id;
                            $image->position = \Image::getHighestPosition($Product->id) + 1;
                            $image->cover = (!$key && !$product_has_images) ? true : false;
                            $alt = $Product->name;
                            if (\Tools::strlen($alt) > 0) {
                                $image->legend = $alt;
                            }
                            if (!isset($this->entityInPs[$data['id_modele']])) {
                                $result = $image->add();
                                $image->associateTo(\Shop::getContextListShopID());
                            } else {
                                $result = $image->update();
                            }
                            if ($result) {
                                // associate image to selected shops
                                if (!parent::copyImg($Product->id, $image->id, $url, 'products', true)) {
                                    $image->delete();
                                    LkWriteLog::addLog('img_copy_failed', $url);
                                }
                            }
                        } else {
                            LkWriteLog::addLog('img_copy_success', $url);
                        }
                    }
                }
            }
        }

        // Add record to log
        $nbCatDisable = count($this->entityInPs);
        $optionLog = 'Product imported / updated : ' . $i;
        $optionLog .= '. Product disable : ' . $nbCatDisable;
        LkWriteLog::addLog('product_import_success', $optionLog);

        if (!empty($this->entityInPs)) {
            $this->disableEntity($this->entityInPs);
        }

        // Delete file after import.
        $this->importEnd();
    }

    /**
     * Disable product when they're not in
     * import file but they're in PS
     *
     * @param $categories
     * @since 1.0.0
     */
    private function disableEntity($products)
    {
        foreach ($products as $product) {
            $this->db->update('product', array(
                'active' => pSQL(0),
                'date_upd' => date('Y-m-d H:i:s'),
            ), 'id_product = '.$product['id_entity'].'', 1, true);

            $this->db->update('lk_ebp_entity_link', array(
                'active' => pSQL(0),
                'date_upd' => date('Y-m-d H:i:s'),
            ), 'id_ebp = '.$product['id_ebp'].'', 1, true);
        }
    }
}
