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

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/classes/BeslistProduct.php');
if (!Module::getInstanceByName('beslistcart')->active)
	exit;

$affiliate = '?ac=beslist';
$products = BeslistProduct::getLoadedBeslistProducts((int)$context->language->id);
$deliveryperiod = Configuration::get('BESLIST_CART_DELIVERYPERIOD');
$deliveryperiod_nostock = Configuration::get('BESLIST_CART_DELIVERYPERIOD_NOSTOCK');
$shippingcost = Configuration::get('BESLIST_CART_SHIPPING_COST');

header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<productfeed type="beslist" date="<?php echo date('Y-m-d H:i:s'); ?>">
<?php
  foreach ($products AS $product) {
      echo "\t<product>\n";
      echo "\t\t<title><![CDATA[".$product['name']."]]></title>\n";
      echo "\t\t<price>".number_format((float)Product::getPriceStatic($product['id_product']), 2, ',', '')."</price>\n";
			if(isset($product['attribute_reference'])) {
					echo "\t\t<code><![CDATA[".$product['attribute_reference']."]]></code>\n";
					echo "\t\t<sku>" . $product['attribute_reference'] . "</sku>\n";
					if(isset($product['variant'])) {
		      		echo "\t\t<variantcode>" . $product['reference'] . '-' . $product['variant'] . "</variantcode>\n";
					}
		      echo "\t\t<modelcode>" . $product['reference'] . "</modelcode>\n"; // Grouping id
			} else {
      		echo "\t\t<code><![CDATA[".$product['reference']."]]></code>\n";
					echo "\t\t<sku>" . $product['reference'] . "</sku>\n";
			}
      echo "\t\t<productlink><![CDATA[".str_replace('&amp;', '&', htmlspecialchars($link->getproductLink($product['id_product'], $product['link_rewrite'], Category::getLinkRewrite((int)($product['id_category_default']), $cookie->id_lang)))).$affiliate."]]></productlink>\n";

      $images = Image::getImages((int)$context->language->id, $product['id_product']);
      if (is_array($images) AND sizeof($images))
      {
          foreach ($images as $idx => $image) {
              $imageObj = new Image($image['id_image']);
              $suffix = $idx > 0 ? "_" . $idx : "";
              echo "\t\t<imagelink".$suffix."><![CDATA[".$link->getImageLink($product['link_rewrite'], $image['id_image'], 'thickbox_default')."]]></imagelink".$suffix.">\n";
          }
      }

      echo "\t\t<category>" . htmlspecialchars($product['category_name'], ENT_XML1, 'UTF-8') . "</category>\n";
      echo "\t\t<deliveryperiod>" . ($product['stock'] > 0 ? $deliveryperiod : $deliveryperiod_nostock) . "</deliveryperiod>\n";
      echo "\t\t<shippingcost>" . number_format($shippingcost, 2, ',', '') . "</shippingcost>\n";
      echo "\t\t<eancode>" . $product['ean13'] . "</eancode>\n";
      echo "\t\t<description><![CDATA[" . $product['description_short'] . "]]></description>\n";
      echo "\t\t<display>" . $product['published'] . "</display>\n";
			if (isset($product['manufacturer_name'])) {
      		echo "\t\t<brand>" . $product['manufacturer_name'] . "</brand>\n";
			}
			if (isset($product['size'])) {
      		echo "\t\t<size>" . $product['size'] . "</size>\n";
			}
			if (isset($product['color'])) {
      		echo "\t\t<color>" . $product['color'] . "</color>\n";
			}
      // echo "\t\t<gender> (man/vrouw/ jongen/meisje/baby/unisex) </gender>\n";
      // echo "\t\t<material>?</material>\n";
			echo "\t\t<condition>";
			switch($product['condition']) {
					case 'refurbished':
							echo 'Refurbished';
							break;
					case 'used':
							echo 'Gebruikt';
							break;
					default:
							echo 'Nieuw';
							break;
			}
			echo "</condition>\n";
      echo "\t</product>\n";
  }
?>
</productfeed>