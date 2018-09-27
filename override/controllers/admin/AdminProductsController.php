<?php
/*
* 2007-2017 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/


if (!defined('_PS_VERSION_'))
    exit;
session_start();

$domain = $_SERVER['HTTP_HOST'];
$appName = explode('.', $domain)[0];

/**
 * @property Product $object
 */
class AdminProductsController extends AdminProductsControllerCore
{
	public function __construct()
    {
        $this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'ml2presta` ml ON (ml.`id_product` = a.`id_product`)
		';
        $this->_select = 'ml.`id_ml`, ';
    	parent::__construct();

        $this->fields_list['id_ml'] = array(
            'title' => $this->l('ML'),
            //'filter_key' => 'b!ml2presta'
        );

    }



    public function postProcess()
    {   
		// Autentificación API	
    	require_once (_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/vendor/php-sdk/Meli/meli.php');
		require_once (_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/classes/Ml2presta.php');
    	
    	$prefijo="MERCADOLIBRE2PRESTASHOP";
    	$appId = trim(Configuration::get($prefijo.'_APPID'));
		$secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
		$siteId = trim(Configuration::get($prefijo.'_PAIS'));
        $meli = new Meli($appId, $secretKey);

        if(!empty(Tools::getValue('ps2ml'))){ //Autentica
			if(empty($appId) OR empty($secretKey) OR empty($siteId)){
				echo "Por favor revisa la configuración del módulo mercadolibre2prestashop. Tienes que completar toda la configuración";
				die;
			}
			        
            if(!empty(Tools::getValue('authFin')) OR !empty($_SESSION['access_token'])){ //Publica item
            		$item = Tools::getValue('productBox');
            		foreach($item as $itemId){
          				$ml2presta = new Ml2presta();

            			if(!empty(Ml2presta::get_Mlid_By_Idproduct($itemId))){ //Ya existe en DB
            				echo "Producto ID $itemId ya está publicado en Mercado Libre";
            				continue;
            			}
            			$arrItem=$this->create_item_array($itemId);

            			$arrItem = $this->validar_producto($arrItem);
            			if(!$arrItem){
              				echo "Producto ID $itemId no tiene precio o stock";
            				continue;
            			}



            			if(empty(Ml2presta::get_category_by_idproduct($itemId))){
            				echo "Producto ID $itemId no tiene categoría. Por favor asígnale una para poder publicarlo";
            				continue;
            			}


					    //echo '<pre>';
					    try{
					    	$meliResp=$meli->post('/items', $arrItem, array('access_token' => $_SESSION['access_token']));
						} catch (Exception $e) {
	                        echo "Hubo un error al crear el producto: ",  $e->getMessage(), "\n";
	                        die;
	                    }

	                    if($meliResp["body"]->status!="active"){
							echo "Producto ID $itemId error al publicar en Mercado Libre. Por favor inténtalo en 15 minutos nuevamente.";
							if($meliResp["body"]->cause){
								//print_R($meliResp["body"]->cause);
							}
	                    	continue;
	                    }

					    //print_r($meliResp);
					    //echo '</pre>';
					    //echo "-".$meliResp["body"]->id.".";



					    if($itemExists=Ml2presta::exists_idproduct($itemId)){
					    	$ml2presta = new Ml2presta(Ml2presta::exists_idproduct($itemId));
						    $ml2presta->id_ml=$meliResp["body"]->id;
						    $ml2presta->update();
					    }else{
					    	$ml2presta = new Ml2presta();
						    $ml2presta->id_product=$itemId;
						    $ml2presta->id_ml=$meliResp["body"]->id;
						    $ml2presta->save();
					    }
					}




            }
			    		//die('xxx');

			}elseif(!empty(Tools::getValue('mercadolibreCategoria'))){ //Asigna categoría
				//Busca ml2presta
          		//$ml2presta = new Ml2presta();
            	$item = Tools::getValue('productBox');
            	$category = Tools::getValue('mercadolibre_category');

            	foreach($item as $itemId){
	            	if($itemExists=Ml2presta::exists_idproduct($itemId)){ //Ya existe en DB
	            		$ml2presta = new Ml2presta(Ml2presta::exists_idproduct($itemId));
	            		$ml2presta->id_ml_category=$category;
	            		$ml2presta->update();
	            	}else{
	            		$ml2presta = new Ml2presta();
	            		$ml2presta->id_product=$itemId;	            		
	            		$ml2presta->id_ml_category=$category;
	            		$ml2presta->add();
	            	}
            	}
            	$result=array( "message"=>"Categoría cambiada exitosamente en ".count($item)." productos",
            		"status"=>"200"
            	);
            	echo json_encode($result);
				die;
			}

        parent::postProcess();
    }


    public function publishItem(){

    }


    public function initSdk(){

    }

    // Revisa si los datos del proudcto son aptos para al API de Mercado Libre
    public function validar_producto($arrProducto){
    	if(strlen($arrProducto["description"]["plain_text"]) > 50000 ){ //max chars
    		$arrProducto["description"]["plain_text"] = substr($arrProducto["description"]["plain_text"], 0, 500009);
    		echo "adassad";	

    	}elseif(empty($arrProducto["title"])){ //vacío
			$arrProducto["description"]["title"] = "Producto sin título";

    	}elseif(strlen($arrProducto["title"]) > 60 ){ //max chars
			 $arrProducto["description"]["title"] = substr($arrProducto["description"]["title"], 0, 60);

    	}elseif(empty($arrProducto["price"])){ //vacío
    		return false;

    	}elseif(empty($arrProducto["available_quantity"])){ //vacío
			return false;
    	}

    	return $arrProducto;

    }

    public function create_item_array($idProduct){
				$prod = new Product((int) $idProduct);
				$image = Image::getImages(1, $idProduct);
				$arrImageUrl= array();

				foreach($image as $img){
					$link = new Link();
					$imageUrl = $link->getImageLink($prod->link_rewrite, $img['id_image'], 'home_default');

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
