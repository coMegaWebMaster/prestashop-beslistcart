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

function upgrade_module_1_2_0()
{
    return (
        Db::getInstance()->execute(
            'ALTER TABLE `'._DB_PREFIX_.'beslist_product` ADD `delivery_code_nl` VARCHAR(255) AFTER `status`'
        ) &&
        Db::getInstance()->execute(
            'ALTER TABLE `'._DB_PREFIX_.'beslist_product` ADD `delivery_code_be` VARCHAR(255) AFTER `delivery_code_nl`'
        )
    );
}
