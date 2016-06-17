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
 *  @copyright 2013-2016 Wienk IT
 *  @license   LICENSE.txt
 */

require_once _PS_MODULE_DIR_.'beslistcart/libraries/autoload.php';
require_once _PS_MODULE_DIR_.'beslistcart/beslistcart.php';
require_once _PS_MODULE_DIR_.'beslistcart/classes/BeslistProduct.php';

class AdminBeslistCartProductsController extends AdminController
{
    public function __construct()
    {

        if ($id_product = Tools::getValue('id_product')) {
            Tools::redirectAdmin(
                Context::getContext()
                    ->link
                    ->getAdminLink('AdminProducts').'&updateproduct&id_product='.(int)$id_product
            );
        }

        $this->bootstrap = true;
        $this->table = 'beslist_product';
        $this->className = 'BeslistProduct';

        $this->addRowAction('view');

        $this->identifier = 'id_product';

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'product_lang` pl
                            ON (pl.`id_product` = a.`id_product` AND pl.`id_shop` = a.`id_shop`) ';
        $this->_select .= ' pl.`name` as `product_name`,
                            IF(status = 0, 1, 0) as badge_success,
                            IF(status > 0, 1, 0) as badge_danger ';

        $this->fields_list = array(
            'id_beslist_product' => array(
                'title' => $this->l('Beslist Product ID'),
                'align' => 'text-left',
                'class' => 'fixed-width-xs'
            ),
            'product_name' => array(
                'title' => $this->l('Product'),
                'align' => 'text-left',
                'filter_key' => 'pl!name'
            ),
            'id_product_attribute' => array(
                'title' => $this->l('Product combination'),
                'align' => 'text-left',
            ),
            'price' => array(
                'title' => $this->l('Beslist specific price'),
                'type' => 'price',
                'align' => 'text-right',
            ),
            'published' => array(
                'title' => $this->l('Published'),
                'type' => 'bool',
                'active' => 'published',
                'align' => 'text-center',
                'class' => 'fixed-width-sm'
            ),
            'status' => array(
                'title' => $this->l('Synchronized'),
                'callback' => 'getSychronizedState',
                'badge_danger' => true,
                'badge_success' => true,
                'align' => 'text-center',
                'class' => 'fixed-width-sm'
            )
        );

        $this->shopLinkType = 'shop';

        parent::__construct();
    }

    /**
     * Callback for the static column in the list
     * @param int $status the status
     * @return string the status
     */
    public function getSychronizedState($status)
    {
        switch($status) {
            case BeslistProduct::STATUS_OK:
                return $this->l('OK');
            case BeslistProduct::STATUS_STOCK_UPDATE:
                return $this->l('Stock updated');
            case BeslistProduct::STATUS_INFO_UPDATE:
                return $this->l('Info updated');
            case BeslistProduct::STATUS_NEW:
                return $this->l('New');
            default:
                return $this->l('Unknown');
        }
    }

    /**
     * Overrides parent::displayViewLink
     */
    public function displayViewLink($token = null, $id = 0, $name = null)
    {
        if ($this->tabAccess['view'] == 1) {
            $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
            if (!array_key_exists('View', self::$cache_lang)) {
                self::$cache_lang['View'] = $this->l('View', 'Helper');
            }

            $tpl->assign(array(
                'href' => $this->context->link->getAdminLink('AdminProducts').'&updateproduct&id_product='.(int)$id,
                'action' => self::$cache_lang['View'],
                'id' => $id
            ));

            return $tpl->fetch();
        } else {
            return;
        }
    }

    /**
     * Overrides parent::initPageHeaderToolbar
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        if (!Configuration::get('BESLIST_CART_ENABLED')) {
            return;
        }
        $this->page_header_toolbar_btn['sync_products'] = array(
            'href' => self::$currentIndex.'&token='.$this->token.'&sync_products=1',
            'desc' => $this->l('Sync products'),
            'icon' => 'process-icon-update'
        );
    }

    /**
     * Processes the request
     */
    public function postProcess()
    {
        if ((bool)Tools::getValue('sync_products')) {
            if (!Configuration::get('BESLIST_CART_ENABLED')) {
                $this->errors[] = Tools::displayError(
                    'The Beslist Cart functionality isn\'t enabled for the current store.'
                );
                return;
            }
            self::synchronize($this->context);
            $this->confirmations[] = $this->l('Beslist products fully synchronized.');
        }
    }

    /**
     * Synchronize changed products
     */
    public static function synchronize($context)
    {
        $beslistProducts = BeslistProduct::getUpdatedProducts();
        foreach ($beslistProducts as $beslistProduct) {
            switch($beslistProduct->status) {
                case BeslistProduct::STATUS_NEW:
                    self::processBeslistProductCreate($beslistProduct, $context);
                    break;
                case BeslistProduct::STATUS_INFO_UPDATE:
                    self::processBeslistProductUpdate($beslistProduct, $context);
                    break;
                case BeslistProduct::STATUS_STOCK_UPDATE:
                    self::processBeslistStockUpdate($beslistProduct, $context);
                    break;
            }
        }
    }

    /**
     * Set the synchronization status of a product
     * @param BeslistProduct $beslistProduct
     * @param int $status
     */
    public static function setProductStatus($beslistProduct, $status)
    {
        DB::getInstance()->update('beslist_product', array(
            'status' => (int)$status
        ), 'id_beslist_product = ' . (int)$beslistProduct->id);
    }

    /**
     * Delete a product from Beslist
     * @param BeslistProduct $beslistProduct
     * @param Context $context
     */
    public static function processBeslistProductDelete($beslistProduct, $context)
    {
        // $Plaza = BolPlaza::getClient();
        // try {
        //     $Plaza->deleteOffer($beslistProduct->id);
        // } catch (Exception $e) {
        //     $context->controller->errors[] = Tools::displayError(
        //         'Couldn\'t send update to Bol.com, error: ' . $e->getMessage() . 'You have to correct this manually.'
        //     );
        // }
    }

    /**
     * Update the stock on Bol.com
     * @param BeslistProduct $beslistProduct
     * @param Context $context
     */
    public static function processBeslistStockUpdate($beslistProduct, $context)
    {
        // $product = new Product($beslistProduct->id_product, false, $context->language->id, $context->shop->id);
        // $quantity = StockAvailable::getQuantityAvailableByProduct(
        //     $product->id_product,
        //     $beslistProduct->id_product_attribute
        // );
        // self::processBeslistQuantityUpdate($beslistProduct, $quantity, $context);
    }

    /**
     * Update the stock on Bol.com
     * @param BeslistProduct $beslistProduct
     * @param int $quantity
     * @param Context $context
     */
    public static function processBeslistQuantityUpdate($beslistProduct, $quantity, $context)
    {
        // $Plaza = BolPlaza::getClient();
        // $stockUpdate = new Picqer\BolPlazaClient\Entities\BolPlazaStockUpdate();
        // $stockUpdate->QuantityInStock = $quantity;
        // try {
        //     $Plaza->updateOfferStock($beslistProduct->id, $stockUpdate);
        //     self::setProductStatus($beslistProduct, (int)BeslistProduct::STATUS_OK);
        // } catch (Exception $e) {
        //     $context->controller->errors[] = Tools::displayError(
        //         '[bolplaza] Couldn\'t send update to Bol.com, error: ' . $e->getMessage()
        //     );
        // }
    }

    /**
     * Update a product on Beslist
     * @param BeslistProduct $beslistProduct
     * @param Context $context
     */
    public static function processBeslistProductUpdate($beslistProduct, $context)
    {
        // $price_calculator    = Adapter_ServiceLocator::get('Adapter_ProductPriceCalculator');
        //
        // $offerUpdate = new Picqer\BolPlazaClient\Entities\BolPlazaOfferUpdate();
        // $offerUpdate->DeliveryCode = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        // $offerUpdate->Publish = $beslistProduct->published == 1 ? 'true' : 'false';
        //
        // $product = new Product($beslistProduct->id_product, false, $context->language->id, $context->shop->id);
        // if ($beslistProduct->id_product_attribute) {
        //     $combination = new Combination($beslistProduct->id_product_attribute);
        //     $offerUpdate->ReferenceCode = $combination->reference;
        // } else {
        //     $offerUpdate->ReferenceCode = $product->reference;
        // }
        // $offerUpdate->Description = !empty($product->description) ? $product->description : $product->name;
        // $price = $beslistProduct->price;
        // if ($price == 0) {
        //     $price = $price_calculator->getProductPrice(
        //         (int)$beslistProduct->id_product,
        //         true,
        //         (int)$beslistProduct->id_product_attribute
        //     );
        // }
        // $offerUpdate->Price = $price;
        //
        // $Plaza = BolPlaza::getClient();
        // try {
        //     $Plaza->updateOffer($beslistProduct->id, $offerUpdate);
        //     self::setProductStatus($beslistProduct, (int)BeslistProduct::STATUS_OK);
        // } catch (Exception $e) {
        //     $context->controller->errors[] = Tools::displayError(
        //         '[bolplaza] Couldn\'t send update to Bol.com, error: ' . $e->getMessage()
        //     );
        // }
    }

    /**
     * Add a product from Bol.com
     * @param BeslistProduct $beslistProduct
     * @param Context $context
     */
    public static function processBeslistProductCreate($beslistProduct, $context)
    {
        // $price_calculator    = Adapter_ServiceLocator::get('Adapter_ProductPriceCalculator');
        //
        // $offerCreate = new Picqer\BolPlazaClient\Entities\BolPlazaOfferCreate();
        // $offerCreate->DeliveryCode = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        // $offerCreate->Publish = $beslistProduct->published == 1 ? 'true' : 'false';
        //
        // $product = new Product($beslistProduct->id_product, false, $context->language->id, $context->shop->id);
        // if ($beslistProduct->id_product_attribute) {
        //     $combination = new Combination($beslistProduct->id_product_attribute);
        //     $offerCreate->EAN = $combination->ean13;
        //     $offerCreate->QuantityInStock = StockAvailable::getQuantityAvailableByProduct(
        //         $product->id_product,
        //         $beslistProduct->id_product_attribute
        //     );
        //     $offerCreate->ReferenceCode = $combination->reference;
        // } else {
        //     $offerCreate->EAN = $product->ean13;
        //     $offerCreate->QuantityInStock = StockAvailable::getQuantityAvailableByProduct($beslistProduct->id_product);
        //     $offerCreate->ReferenceCode = $product->reference;
        // }
        // switch($product->condition) {
        //     case 'refurbished':
        //         $offerCreate->Condition = 'AS_NEW';
        //         break;
        //     case 'used':
        //         $offerCreate->Condition = 'GOOD';
        //         break;
        //     default:
        //         $offerCreate->Condition = 'NEW';
        //         break;
        // }
        //
        // $offerCreate->Description = !empty($product->description) ? $product->description : $product->name;
        // $price = $beslistProduct->price;
        // if ($price == 0) {
        //     $price = $price_calculator->getProductPrice(
        //         (int)$beslistProduct->id_product,
        //         true,
        //         (int)$beslistProduct->id_product_attribute
        //     );
        // }
        // $offerCreate->Price = $price;
        //
        // $Plaza = BolPlaza::getClient();
        // try {
        //     $Plaza->createOffer($beslistProduct->id, $offerCreate);
        //     self::setProductStatus($beslistProduct, (int)BeslistProduct::STATUS_OK);
        // } catch (Exception $e) {
        //     $context->controller->errors[] = Tools::displayError(
        //         '[bolplaza] Couldn\'t send update to Bol.com, error: ' . $e->getMessage()
        //     );
        // }
    }
}