<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
$domain = $_SERVER['HTTP_HOST'];
$appName = explode('.', $domain)[0];
class AdminProductsController extends AdminProductsControllerCore
{
    /*
    * module: mercadolibre2prestashop
    * date: 2018-09-28 18:41:53
    * version: 1
    */
    public function __construct()
    {
        $this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'ml2presta` ml ON (ml.`id_product` = a.`id_product`)
		';
        $this->_select = 'ml.`id_ml`, ';
        parent::__construct();
        $this->fields_list['id_ml'] = array(
            'title' => $this->l('ML'),
        );
    }
    /*
    * module: mercadolibre2prestashop
    * date: 2018-09-28 18:41:53
    * version: 1
    */
    public function postProcess()
    {
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/vendor/php-sdk/Meli/meli.php');
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/classes/Ml2presta.php');
        $arrStatus="";

        $prefijo="MERCADOLIBRE2PRESTASHOP";
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $siteId = trim(Configuration::get($prefijo.'_PAIS'));
        $meli = new Meli($appId, $secretKey);
        if (!empty(Tools::getValue('ps2ml'))) { //Autentica
            if (empty($appId) or empty($secretKey) or empty($siteId)) {
                $arrStatus["error"][] =  $this->l("Por favor revisa la configuración del módulo mercadolibre2prestashop. Tienes que completar toda la configuración");
                die;
            }
            $item = Tools::getValue('productBox');
            foreach ($item as $itemId) {
                $ml2presta = new Ml2presta();
                if (!empty(Ml2presta::get_Mlid_By_Idproduct($itemId))) { //Ya existe en DB
                    $arrStatus["error"][] =  $this->l("Producto ID") ." $itemId ". $this->l("ya está publicado en Mercado Libre");
                    continue;
                }
                $arrItem=$this->create_item_array($itemId);
                $arrItem = $this->validar_producto($arrItem);
                if (!$arrItem) {
                    $arrStatus["error"][] =  $this->l("Producto ID") ." $itemId ".  $this->l(" no tiene precio o stock");
                    continue;
                }
                if (empty(Ml2presta::get_category_by_idproduct($itemId))) {
                    $arrStatus["error"][] =  $this->l("Producto ID") ." $itemId ".  $this->l("no tiene categoría. Por favor asígnale una para poder publicarlo");
                    continue;
                }
                try {
                    $meliResp=$meli->post('/items', $arrItem, array('access_token' => $this->context->cookie->access_token));
                } catch (Exception $e) {
                    $arrStatus["error"][] =  $this->l("Hubo un error al crear el producto: ").  $e->getMessage() . "\n";
                    die;
                }
                    
                if ($meliResp["body"]->status!="active") {
                    $arrStatus["error"][] =  $this->l("Producto ID") . " $itemId " .  $this->l("error al publicar en Mercado Libre. Por favor inténtalo en 15 minutos nuevamente.".print_R($meliResp, true));
                        
                    continue;
                }
                if (Ml2presta::exists_idproduct($itemId)) {
                    $ml2presta = new Ml2presta(Ml2presta::exists_idproduct($itemId));
                    $ml2presta->id_ml=$meliResp["body"]->id;
                    $ml2presta->update();
                } else {
                    $ml2presta = new Ml2presta();
                    $ml2presta->id_product=$itemId;
                    $ml2presta->id_ml=$meliResp["body"]->id;
                    $ml2presta->save();
                }
                $arrStatus["success"][] =  $this->l("Producto ID") . " $itemId ".  $this->l("publicado exitosamente");
            }
            echo json_encode($arrStatus);
            die;
        } elseif (!empty(Tools::getValue('mercadolibreCategoria'))) { //Asigna categoría
            $item = Tools::getValue('productBox');
            $arrItem=json_decode($item);
            $category = Tools::getValue('mercadolibre_category');
            foreach ($item as $itemId) {
                if (Ml2presta::exists_idproduct($itemId)) { //Ya existe en DB
                    $ml2presta = new Ml2presta(Ml2presta::exists_idproduct($itemId));
                    $ml2presta->id_ml_category=$category;
                    $ml2presta->update();
                } else {
                    $ml2presta = new Ml2presta();
                    $ml2presta->id_product=$itemId;
                    $ml2presta->id_ml_category=$category;
                    $ml2presta->add();
                }
            }
            $result=array( "message"=> $this->l("Categoría cambiada exitosamente en ").count($item). $this->l(" productos"),
            "status"=>"200"
            );
            echo json_encode($result);
            die;
        }
        parent::postProcess();
    }
    /*
    * module: mercadolibre2prestashop
    * date: 2018-09-28 18:41:53
    * version: 1
    */
    public function publishItem()
    {
    }
    /*
    * module: mercadolibre2prestashop
    * date: 2018-09-28 18:41:53
    * version: 1
    */
    public function initSdk()
    {
    }
    /*
    * module: mercadolibre2prestashop
    * date: 2018-09-28 18:41:53
    * version: 1
    */
    public function validar_producto($arrProducto)
    {
        if (empty($arrProducto["price"])) { //vacío
            return false;
        } elseif (empty($arrProducto["available_quantity"])) { //vacío
            return false;
        } elseif (Tools::strlen($arrProducto["description"]["plain_text"]) > 50000) { //max chars
            $arrProducto["description"]["plain_text"] = Tools::substr($arrProducto["description"]["plain_text"], 0, 50000);
        } elseif (empty($arrProducto["title"])) { //vacío
            $arrProducto["title"] =  $this->l("Producto sin título");
        } elseif (Tools::strlen($arrProducto["title"]) > 60) { //max chars
            $arrProducto["title"] = Tools::substr($arrProducto["title"], 0, 60);
        }
        return $arrProducto;
    }
    /*
    * module: mercadolibre2prestashop
    * date: 2018-09-28 18:41:53
    * version: 1
    */
    public function create_item_array($idProduct)
    {
        $prod = new Product((int) $idProduct);
        $image = Image::getImages(1, $idProduct);
        $arrImageUrl= array();
        $i=0;
                
        foreach ($image as $img) {
            $i++;
            if ($i > 12) {
                continue;
            }
                                        
            $link = new Link();
            $imageUrl = $link->getImageLink($prod->link_rewrite, $img['id_image'], ImageType::getFormattedName('home'));
            $arrImageUrl[] =
                        array(
                            "source" => $imageUrl
                        );
        }
        $producCategory=Ml2presta::get_category_by_idproduct($idProduct);
        $item = array(
                    "title" => $prod->name[1],
                    "category_id" => $producCategory,
                    "price" => str_replace(",", "", number_Format($prod->price, 2)),
                    "currency_id" => "ARS",
                    "available_quantity" => StockAvailable::getQuantityAvailableByProduct($idProduct),
                    "buying_mode" => "buy_it_now",
                    "listing_type_id" => "bronze",
                    "condition" => "new",
                    "description" =>
                        array(
                            "plain_text" => strip_tags($prod->description[1])
                        ),
                    "warranty" => "12 month",
                    "pictures" => $arrImageUrl
                );
                

        return $item;
    }
}
