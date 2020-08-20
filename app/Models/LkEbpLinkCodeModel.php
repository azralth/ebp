<?php
namespace LkEbp\Models;

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

/**
 * Class LkEbpNegoceLinkCodeModel
 */
class LkEbpLinkCodeModel extends \ObjectModel
{
    public $id_ebp;

    /** @var string Type entity */
    public $entity;

    /** @var string Ebp code id */
    public $ref_ebp;

    /** @var int id_entity id */
    public $id_entity;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object creation date */
    public $date_upd;

    /** @var bool Object active */
    public $active;

	public static $definition = array(
		'table' => 'lk_ebp_entity_link',
		'primary' => 'id_ebp',
		'multilang' => false,
		'multilang_shop' => false,
		'fields' => array(
			'entity' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'values' => array('product', 'category', 'combination'), 'default' => 'product'),
			'ref_ebp' => array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 255),
			'id_entity' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'date_add' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
		)
	);

    /**
     * Retrieve all entitty based on $entitty
     * @param string $entity (string) type (product, category etc...)
     * @return array|string|null
     */
	public static function getAllIdEntity($entity)
    {
        $request = "SELECT `id_ebp`, `ref_ebp`, `id_entity` FROM " . _DB_PREFIX_ . "lk_ebp_entity_link WHERE `entity` = '".$entity."' ";
        return \DB::getInstance()->executeS($request);
    }

    /**
     * Add new link model Ebp <-> Prestashop
     *
     * @param array $entity
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function addNewEbpEntity($entity)
    {
        $this->id_entity = $entity['id_entity'];
        $this->ref_ebp = $entity['ref_ebp'];
        $this->entity = $entity['entity'];
        $this->active = 1;
        $this->add();
    }

    /**
     * Update link model Ebp <-> Prestashop
     *
     * @param int $id_ebp
     * @since 1.0.0
     */
    public static function updateEbpEntity($id_ebp)
    {
        $result = \Db::getInstance()->update('lk_ebp_entity_link', array(
            'active' => 1,
            'date_upd' => date('Y-m-d H:i:s'),
        ), 'id_ebp = '.$id_ebp.'', 1, true);

        return $result;
    }

    /**
     * @param string $ref_ebp Internal ebp unique id
     * @param string $entity (string) type (product, category etc...)
     * @return false|string|null
     * @since 1.0.0
     */
	public static function getPsIdCategory($ref_ebp, $entity)
    {
        $request = "SELECT `id_entity` as 'id_category' FROM " . _DB_PREFIX_ . "lk_ebp_entity_link WHERE `ref_ebp` = '" . $ref_ebp . "' AND `entity` = '".$entity."' ";
        return \DB::getInstance()->getValue($request);
    }

    /**
     * @param string $ref_ebp Internal ebp unique id
     * @param string $entity (string) type (product, category etc...)
     * @return false|string|null
     * @since 1.0.0
     */
	public static function getPsIdProduct($ref_ebp, $entity)
    {
        $request = "SELECT `id_entity` as 'id_product' FROM " . _DB_PREFIX_ . "lk_ebp_entity_link WHERE `ref_ebp` = '" . $ref_ebp . "' AND `entity` = '".$entity."' ";
        return \DB::getInstance()->getValue($request);
    }
}
