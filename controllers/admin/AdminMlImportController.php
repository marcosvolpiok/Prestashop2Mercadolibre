<?php
class AdminMlImportController extends ModuleAdminController
{

    public function initContent()
    {
        parent::initContent();

        $smarty = $this->context->smarty;




        $productosBuscados = $this->buscarProductos();
        $smarty->assign(array(
            'formAction' => $productosBuscados['formAction'],
            'idItems' => $productosBuscados['items'],
            'items' => $productosBuscados['itemResult']
        ));        
        //print_R( $productosBuscados['items']);
        //print_r($productosBuscados['itemResult']);
        //var_dump(get_object_vars($productosBuscados['itemResult'][0]['body']));
        //die;
    }

	

    public function buscarProductos(){
        $this->authMl();

        $prefijo="MERCADOLIBRE2PRESTASHOP";
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $meli = new Meli($appId, $secretKey);
        $context=Context::getContext();

        $params = array();
        /* ***** PARA OBTENER SELLER ID ***** */
        $url = '/users/me'; //get seller id
        $result = $meli->get($url, array('access_token' => $context->cookie->access_token)
        );
        $sellerId=$result["body"]->id;
        //echo $result["body"]->id;
        //echo "<br />";
        //print_r($result);
        /* ***** / PARA OBTENER SELLER ID ***** */
        

        /** ****** OBTENER LISTADO DE ITEMS ***** */
        $url = '/users/'.$sellerId.'/items/search'; //get seller id
        $result = $meli->get($url, array('access_token' => $context->cookie->access_token, 'status' => 'active'));
        //  /users/{Cust_id}/items/search?access_token=$ACCESS_TOKEN Retrieves user’s listings. GET 
        //print_r( $result["body"]->results );
        //print_r($result);

        
        /* devolvió: 
        [0] => MLA706808097
        [1] => MLA756013807
        [2] => MLA756013804
        */                    

        foreach($result["body"]->results as $item){
            // OBTENER INFO DE CADA ITEM
            $url = '/items/'.$item;
            $itemResult[$item] = $meli->get($url, array('access_token' => $context->cookie->access_token));
            
            if (Ml2presta::existsMlProduct($item)) {
                $itemResult[$item]['existe']=true;
            } else {
                $itemResult[$item]['existe']=false;
            }
        }


        $link = new Link();
        $arrAdminDir = explode("/", PS_ADMIN_DIR);
        $formAction = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST']
        .__PS_BASE_URI__.$arrAdminDir[ count($arrAdminDir) - 1 ]
        .'/'.$link->getAdminLink('AdminMlImport', true).'&post=true';


        $smarty = $this->context->smarty;
        return array(
            'formAction' => $formAction,
            'items' => $result["body"]->results,
            'itemResult' => $itemResult
        );        
    }

    public function __construct(){
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/vendor/mercadolibre-php-sdk/Meli/meli.php');
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/classes/Ml2presta.php');


        $prefijo="MERCADOLIBRE2PRESTASHOP";
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $meli = new Meli($appId, $secretKey);
        $context=Context::getContext();

        if (Tools::getValue('post')=='true') {
        	//Busca datos del producto en Mercadolibre
        	$default_lang = Configuration::get('PS_LANG_DEFAULT');

        	foreach(Tools::getValue('item') as $item){
        		echo $item;
                $url = '/items/'.$item;
        		$result = $meli->get($url, array('access_token' => $context->cookie->access_token));
        		//print_r($result);
                //die;

                if (Ml2presta::existsMlProduct($result["body"]->id)) {
                    echo "producto ya existe en la db<br />";
                    continue;
                }

        		
        		$product = new Product();
        		$product->name= array((int)Configuration::get('PS_LANG_DEFAULT') => $result["body"]->title);
        		$product->price= $result["body"]->price;
        		$product->link_rewrite=array((int)Configuration::get('PS_LANG_DEFAULT') =>  'test-importu');
        		$product->quantity = (int)$result["body"]->available_quantity;
        		$product->add();

                $ml2presta = new Ml2presta();
                $ml2presta->id_product=$product->id;
                $ml2presta->id_ml=$result["body"]->id;
                $ml2presta->save();                

        		$stock = new StockAvailable();
        		$stock->setQuantity($product->id, 0, (int)$result["body"]->available_quantity);
        		

				$image = new Image();
				$image->id_product = $product->id;
				$image->position = Image::getHighestPosition($product->id) + 1;
				$image->cover = true; // or false;
				if (($image->validateFields(false, true)) === true &&
				($image->validateFieldsLang(false, true)) === true && $image->add())
				{
					$shops = Shop::getShops(true, null, true); 
				    $image->associateTo($shops);
				    if (!self::copyImg($product->id, null, "http://mla-s1-p.mlstatic.com/605495-MLA27486697441_062018-O.jpg", 'products', false))
				    {
				        $image->delete();
				    }
				}        		
        	}
        	//die;
        }


        return parent::__construct();		
	}



    public function authMl()
    {

    	$context=Context::getContext();


        // Autentificación API
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/vendor/mercadolibre-php-sdk/Meli/meli.php');
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/classes/Ml2presta.php');
 
        $prefijo="MERCADOLIBRE2PRESTASHOP";
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $siteId = trim(Configuration::get($prefijo.'_PAIS'));

        $link = new Link();
        $arrAdminDir = explode("/", PS_ADMIN_DIR);
        $redirectURI = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST']
        .__PS_BASE_URI__.$arrAdminDir[ count($arrAdminDir) - 1 ]
        .'/'.$link->getAdminLink('AdminMlImport', true).'&post=true';

        $meli = new Meli($appId, $secretKey);
        if (Tools::getValue('code') || $context->cookie->access_token) {
            // If code exist and session is empty
            if (Tools::getValue('code') && !($context->cookie->access_token)) {
               // echo "access_token: (".$context->cookie->access_token.")";

                // If the code was in get parameter we authorize
               	$user = $meli->authorize(Tools::getValue('code'), $redirectURI);


                // Now we create the sessions with the authenticated user
                $context->cookie->access_token = $user['body']->access_token;
                $context->cookie->expires_in = time() + $user['body']->expires_in;
                $context->cookie->refresh_token = $user['body']->refresh_token;
            } else {
                // We can check if the access token in invalid checking the time
                if ($context->cookie->expires_in < time()) {
                    try {
                        // Make the refresh proccess
                        $meli->refreshAccessToken();

                        // Now we create the sessions with the new parameters
                        $context->cookie->access_token = $user['body']->access_token;
                        $context->cookie->expires_in = time() + $user['body']->expires_in;
                        $context->cookie->refresh_token = $user['body']->refresh_token;

                        Tools::redirect("$redirectURI");
                        die;
                    } catch (Exception $e) {
                        echo "Exception: ",  $e->getMessage(), "\n";
                    }
                }
            }
        } else {
            if ($siteId) {
                header('location: ' . $meli->getAuthUrl($redirectURI, Meli::$AUTH_URL[$siteId]));
            } else {
                return '<p>'.$this->l('Complete the configuration information (country field)').'</p>';
            }
        }
        // /Autentificación API

    }
    
}