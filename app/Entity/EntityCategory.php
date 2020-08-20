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
class EntityCategory extends EntityImport
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
     * Start import category based on excel file
     * This method use an array where we push category already in PS
     * At each iteration we check if category exist and update or create
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
            LkWriteLog::addLog('category_file_empty');
            return false;
        }
        $i = 0;
        foreach ($this->data as $data) {
            $Category = new \Category(null, $this->langId);
            $Category->name = $data['Marque'];
            $Category->meta_title = LkTools::slugify($data['Marque']);
            $Category->link_rewrite = LkTools::slugify($data['Marque']);
            $Category->id_parent = \Configuration::get('PS_HOME_CATEGORY');
            $Category->active = 1;

            if (!$this->shopIsFeatureActive) {
                $Category->id_shop_default = 1;
            } else {
                $Category->id_shop_default = (int)$this->shopId;
            }

            // Cheeck if category already in ps
            if (isset($this->entityInPs[$data['id']])) {
                $Category->id = $this->entityInPs[$data['id']]['id_entity'];
                $Category->update();

                // Update link model
                LkEbpLinkCodeModel::updateEbpEntity($this->entityInPs[$data['id']]['id_ebp']);

                // Remove entry from entity already in ps
                unset($this->entityInPs[$data['id']]);
                $i++;
            } else {
                $Category->add();

                // Add new entity object in link model.
                $entity = array(
                    'id_entity' => $Category->id,
                    'ref_ebp' => $data['id'],
                    'entity' => $this->entity
                );
                $LinkEbpModel = new LkEbpLinkCodeModel();
                $LinkEbpModel->addNewEbpEntity($entity);
                $i++;
            }
        }

        // Add record to log
        $nbCatDisable = count($this->entityInPs);
        $optionLog = 'Category imported / updated : '.$i;
        $optionLog .= '. Category disable : '.$nbCatDisable;
        LkWriteLog::addLog('category_import_success', $optionLog);

        if (!empty($this->entityInPs)) {
            $this->disableEntity($this->entityInPs);
        }

        // Delete file after import.
        $this->importEnd();
    }

    /**
     * Disable category when they're not in
     * import file but they're in PS
     *
     * @param $categories
     * @since 1.0.0
     */
    private function disableEntity($categories)
    {
        foreach ($categories as $category) {
            $this->db->update('category', array(
                'active' => pSQL(0),
                'date_upd' => date('Y-m-d H:i:s'),
            ), 'id_category = '.$category['id_entity'].'', 1, true);

            $this->db->update('lk_ebp_entity_link', array(
                'active' => pSQL(0),
                'date_upd' => date('Y-m-d H:i:s'),
            ), 'id_ebp = '.$category['id_ebp'].'', 1, true);
        }
    }
}
