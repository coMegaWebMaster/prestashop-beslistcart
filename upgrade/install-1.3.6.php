<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Mark Wienk
 *  @copyright 2013-2018 Wienk IT
 *  @license   LICENSE.txt
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_6()
{
    return Configuration::updateValue('BESLIST_CART_CUSTOMER_GROUP', Configuration::get('PS_CUSTOMER_GROUP'))
        && Configuration::updateValue('BESLIST_CART_ATTRIBUTES_IN_TITLE', false)
        && Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'beslist_product` DROP COLUMN `id_beslist_category`')
        && Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'beslist_category`')
        && Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'beslist_categories`');
}
