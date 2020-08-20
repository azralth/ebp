<?php

namespace LkEbp\Entity;

use LkEbp\LkTools;
use LkEbp\LkWriteLog;
use LkEbp\Models\LkEbpLinkCodeModel;
use LkEbp\Models\LkEbpTax;
use phpDocumentor\Reflection\Types\Parent_;

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
class EntityCombination extends EntityImport
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
     * Start import combination based on excel file
     * This method use an array where we push combination already in PS
     * At each iteration we check if combination exist and update or create
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
            LkWriteLog::addLog('combination_file_empty');
            return false;
        }

        self::setLocale();

        $i = 0;
        foreach ($this->data as $data) {
            // Defaault on
            $idModele = $data['id_Modele'];
            if (!isset($modele)) {
                $modele = $idModele;
                $default_on = 1;
            } else {
                if ($idModele == $modele) {
                    $default_on = "NULL";
                } else {
                    $modele = $idModele;
                    $default_on = 1;
                }
            }

            // Price
            $price = (float)150.00;
            $wholesale_price = (float)100.00;

            // Quantity
            $quantity = 5;

            // get Product ID
            $productId = LkEbpLinkCodeModel::getPsIdProduct($data['id_Modele'], 'product');
            $product = new \Product($productId);

            // First get all groups
            $groupsAttributes = $data['SKU2'];
            $groups = [];
            foreach (\AttributeGroup::getAttributesGroups($this->langId) as $group) {
                $groups[$group['name']] = (int)$group['id_attribute_group'];
            }

            // Get all attributes
            $attributes = [];
            foreach (\Attribute::getAttributes($this->langId) as $attribute) {
                $attributes[$attribute['attribute_group'] . '_' . $attribute['name']] = (int)$attribute['id_attribute'];
            }

            // Prepare group and atttribute based on ebp data
            $infos = [];
            $groupsAttributes = explode('_', $groupsAttributes);
            foreach ($groupsAttributes as $key => $groupAAttribute) {
                switch ($key) {
                    case 2:
                        $infos['group'][] = array('Couleur', 'color');
                        $infos['attribute'][] = array($groupAAttribute);
                        break;
                    case 3:
                        $infos['group'][] = array('Capacité', 'radio');
                        $infos['attribute'][] = array($groupAAttribute);
                        break;
                    case 4:
                        $infos['group'][] = array('Grade', 'select');
                        $infos['attribute'][] = array($groupAAttribute);
                        break;
                }
            }

            // Import group and attribute
            $id_attribute_group = 0;
            $groups_attributes = array();
            foreach ($infos['group'] as $key => $tab_group) {

                $group = trim($tab_group[0]);
                if (!isset($tab_group[1])) {
                    $type = 'select';
                } else {
                    $type = trim($tab_group[1]);
                }

                // sets group
                $groups_attributes[$key]['group'] = $group;

                if (!isset($groups[$group])) {
                    $obj = new \AttributeGroup();
                    $obj->is_color_group = false;
                    $obj->group_type = pSQL($type);
                    $obj->name[$this->langId] = $group;
                    $obj->public_name[$this->langId] = $group;
                    $obj->position = \AttributeGroup::getHigherPosition() + 1;
                    $obj->add();
                    $obj->associateTo(1);
                    $groups[$group] = $obj->id;

                    // fills groups attributes
                    $id_attribute_group = $obj->id;
                    $groups_attributes[$key]['id'] = $id_attribute_group;
                } else {
                    // already exists
                    $id_attribute_group = $groups[$group];
                    $groups_attributes[$key]['id'] = $id_attribute_group;
                }
            }

            // Attribute import
            $id_product_attribute = 0;
            $id_product_attribute_update = false;
            $attributes_to_add = array();
            $i = 0;
            foreach ($infos['attribute'] as $key => $tab_attribute) {
                if (empty($tab_attribute)) {
                    continue;
                }
                $attribute = trim($tab_attribute[0]);

                if (isset($groups_attributes[$key])) {
                    $group = $groups_attributes[$key]['group'];
                    if (!isset($attributes[$group . '_' . $attribute]) && count($groups_attributes[$key]) == 2) {
                        $id_attribute_group = $groups_attributes[$key]['id'];
                        $obj = new \Attribute();
                        // sets the proper id (corresponding to the right key)
                        $obj->id_attribute_group = $groups_attributes[$key]['id'];
                        $obj->name[$this->langId] = str_replace('\n', '', str_replace('\r', '', $attribute));
                        $obj->position = \Attribute::getHigherPosition($groups[$group]) + 1;

                        if (($field_error = $obj->validateFields(UNFRIENDLY_ERROR, true)) === true &&
                            ($lang_field_error = $obj->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true) {
                            $obj->add();
                            $obj->associateTo(1);
                            $attributes[$group . '_' . $attribute] = $obj->id;
                        } else {
                            LkWriteLog::addLog('attribute_import_failed', $groupsAttributes);
                        }
                    }
                }

                // Begin import
                $reference = $data['xx_SKU'];
                $ebp_tax = $data['id_taxe'];
                $reference = $ebp_tax == 2 ? $reference . '_CT' : $reference;
                if (!empty($reference)) {
                    $id_product_attribute = \Combination::getIdByReference($productId, (string)($reference));

                    // updates the attribute
                    if ($id_product_attribute) {
                        // gets all the combinations of this product
                        $attribute_combinations = $product->getAttributeCombinations($this->langId);
                        foreach ($attribute_combinations as $attribute_combination) {
                            if ($id_product_attribute && in_array($id_product_attribute, $attribute_combination)) {
                                $result = \Db::getInstance()->update('product_attribute', array(
                                    'id_product' => (int)$product->id,
                                    'reference' => pSQL($reference),
                                    'wholesale_price' => $wholesale_price,
                                    'price' => (float)$price,
                                    'quantity' => (int)$quantity
                                ), 'id_product_attribute = ' . $id_product_attribute . '', 1, true);

                                $i++;

                                // Update link model
                                if (isset($this->entityInPs[$reference]['id_ebp'])) {
                                    LkEbpLinkCodeModel::updateEbpEntity($this->entityInPs[$reference]['id_ebp']);
                                }

                                // Remove entry from entity already in ps
                                unset($this->entityInPs[$reference]);
                                $id_product_attribute_update = true;
                            }
                        }
                    }
                }

                if (!$id_product_attribute) {

                    // Check if reference not exist
                    $request = "SELECT `id_product_attribute` FROM " . _DB_PREFIX_ . "product_attribute WHERE `reference` = '" . $reference . "'";
                    $result = $this->db->getValue($request);

                    if (!$result) {
                        $request = "SELECT `id_product_attribute` FROM " . _DB_PREFIX_ . "product_attribute WHERE `id_product` = " . $product->id . " AND `default_on` = 1 ";
                        if ($this->db->getValue($request)) {
                            $default_on = "NULL";
                        }
                        // Add to db
                        $request = "INSERT INTO `" . _DB_PREFIX_ . "product_attribute`
                            (`id_product`, `reference`, `wholesale_price`, `price`, `default_on`, `quantity`)
                                VALUES (" . (int)$product->id . ", '" . pSQL($reference) . "', " . $wholesale_price . ", " . (float)$price . ", " . $default_on . ", " . (int)$quantity . ")";
                        $result = $this->db->execute($request);
                        if ($result) {
                            $id_product_attribute = $this->db->Insert_ID();
                            $request = "INSERT INTO `" . _DB_PREFIX_ . "product_attribute_shop`
                                (`id_product`, `id_product_attribute`, `id_shop`, `wholesale_price`, `price`, `default_on`)
                                    VALUES (" . (int)$product->id . ", " . (int)$id_product_attribute . ", '1', " . $wholesale_price . ", " . (float)$price . ", " . $default_on . ")";
                            $result = $this->db->execute($request);
                            $i++;
                        }
                    }

                    $entity = array(
                        'id_entity' => $id_product_attribute,
                        'ref_ebp' => $reference,
                        'entity' => $this->entity
                    );
                    $LinkEbpModel = new LkEbpLinkCodeModel();
                    $LinkEbpModel->addNewEbpEntity($entity);

                    // Tax
                    if ($ebp_tax == 2) {
                        $LkEbpTax = new LkEbpTax();
                        $LkEbpTax->addNewTax($id_product_attribute, 2);
                    }
                }

                // fills our attributes array, in order to add the attributes to the product_attribute afterwards
                if (isset($attributes[$group . '_' . $attribute])) {
                    $attributes_to_add[] = (int)$attributes[$group . '_' . $attribute];
                }

                // after insertion, we clean attribute position and group attribute position
                $obj = new \Attribute();
                $obj->cleanPositions((int)$id_attribute_group, false);
                \AttributeGroup::cleanPositions();
            }

            $product->checkDefaultAttributes();
            if (!$product->cache_default_attribute) {
                \Product::updateDefaultAttribute($product->id);
            }

            if ($id_product_attribute) {
                // now adds the attributes in the attribute_combination table
                if ($id_product_attribute_update) {
                    $this->db->execute('
						DELETE FROM ' . _DB_PREFIX_ . 'product_attribute_combination
						WHERE id_product_attribute = ' . (int)$id_product_attribute);
                }

                foreach ($attributes_to_add as $attribute_to_add) {
                    $this->db->execute('
						INSERT IGNORE INTO ' . _DB_PREFIX_ . 'product_attribute_combination (id_attribute, id_product_attribute)
						VALUES (' . (int)$attribute_to_add . ',' . (int)$id_product_attribute . ')', false);
                }
            }
            // Quantity
            \StockAvailable::setQuantity((int)$product->id, $id_product_attribute, (int)5, $this->shopId);

        }

        // Add record to log
        $nbCatDisable = count($this->entityInPs);
        $optionLog = 'Combination imported / updated : ' . $i;
        $optionLog .= '. Combination disable : ' . $nbCatDisable;
        LkWriteLog::addLog('combination_import_success', $optionLog);

        if (!empty($this->entityInPs)) {
            $this->disableEntity($this->entityInPs);
        }

        // Delete file after import.
        $this->importEnd();
    }

    /**
     * Disable combination when they're not in
     * import file but they're in PS
     *
     * @param $categories
     * @since 1.0.0
     */
    private function disableEntity($combinations)
    {
        foreach ($combinations as $combination) {
            $id_product = $this->db->getValue("SELECT `id_product` FROM " . _DB_PREFIX_ . "product_aatttribute WHERE id_product_attributte LIKE " . $combination['id_entity'] . "");
            \StockAvailable::setQuantity((int)$id_product, $combination['id_entity'], (int)0, $this->shopId);
        }
    }
}
