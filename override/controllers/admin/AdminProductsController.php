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
        //require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/vendor/php-sdk/Meli/meli.php');
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/classes/Ml2presta.php');
        $arrStatus="";

        $prefijo="MERCADOLIBRE2PRESTASHOP";
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $siteId = trim(Configuration::get($prefijo.'_PAIS'));
        $meli = new Meli($appId, $secretKey);
        if (!empty(Tools::getValue('ps2ml'))) { //Autentica
            if (empty($appId) or empty($secretKey) or empty($siteId)) {
                $arrStatus["error"][] =  $this->l("Please check the module configuration
                  mercadolibre2prestashop. You have to complete all the configuration");
                die;
            }
            $item = Tools::getValue('productBox');
            foreach ($item as $itemId) {
                $ml2presta = new Ml2presta();
                if (!empty(Ml2presta::getMlIdByIdProduct($itemId))) { //Ya existe en DB
                    $arrStatus["error"][] =  $this->l("Product ID") ." $itemId ".
                    $this->l("Already published in Mercado Libre");
                    continue;
                }
                $arrItem=$this->createItemArray($itemId);
                $arrItem = $this->validarProducto($arrItem);
                if (!$arrItem) {
                    $arrStatus["error"][] =  $this->l("Producto ID") ." $itemId ".
                    $this->l("It has no price or stock");
                    continue;
                }
                if (empty(Ml2presta::getCategoryByIdProduct($itemId))) {
                    $arrStatus["error"][] =  $this->l("Producto ID") ." $itemId ".
                    $this->l("It has no category. Please assign one to be able to publish it");
                    continue;
                }
                try {
                    $meliResp=$meli->post(
                        '/items',
                        $arrItem,
                        array('access_token' => $this->context->cookie->access_token)
                    );
                } catch (Exception $e) {
                    $arrStatus["error"][] =  $this->l("There was an error creating the product: ").  $e->getMessage() . "\n";
                    die;
                }
                    
                if ($meliResp["body"]->status!="active") {
                    $arrStatus["error"][] =  $this->l("Producto ID") . " $itemId " .
                    $this->l("error when publishing in Mercado Libre. 
                         Please try again in 15 minutes.".print_R($meliResp, true));
                        
                    continue;
                }
                if (Ml2presta::existsIdproduct($itemId)) {
                    $ml2presta = new Ml2presta(Ml2presta::existsIdProduct($itemId));
                    $ml2presta->id_ml=$meliResp["body"]->id;
                    $ml2presta->update();
                } else {
                    $ml2presta = new Ml2presta();
                    $ml2presta->id_product=$itemId;
                    $ml2presta->id_ml=$meliResp["body"]->id;
                    $ml2presta->save();
                }
                $arrStatus["success"][] =  $this->l("Product ID") . " $itemId ".  $this->l("Successfully published");
            }
            echo json_encode($arrStatus);
            die;
        } elseif (!empty(Tools::getValue('mercadolibreCategoria'))) { //Asigna categoría
            $item = Tools::getValue('productBox');
            $arrItem=json_decode($item);
            $category = Tools::getValue('mercadolibre_category');
            foreach ($item as $itemId) {
                if (Ml2presta::existsIdproduct($itemId)) { //Ya existe en DB
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
            $result=array( "message"=> $this->l("Category successfully changed in ").
                count($item). $this->l(" productos"),
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
    public function validarProducto($arrProducto)
    {
        if (empty($arrProducto["price"])) { //vacío
            return false;
        } elseif (empty($arrProducto["available_quantity"])) { //vacío
            return false;
        } elseif (Tools::strlen($arrProducto["description"]["plain_text"]) > 50000) { //max chars
            $arrProducto["description"]["plain_text"] =
            Tools::substr($arrProducto["description"]["plain_text"], 0, 50000);
        } elseif (empty($arrProducto["title"])) { //vacío
            $arrProducto["title"] =  $this->l("Product without title");
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
    public function createItemArray($idProduct)
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
            $imageUrl = $link->getImageLink($prod->link_rewrite, $img['id_image'], ImageType::getFormatedName('home'));
            $arrImageUrl[] =
                        array(
                            "source" => $imageUrl
                        );
        }
        $producCategory=Ml2presta::getCategoryByIdProduct($idProduct);
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









// Meli SDK
class Meli {

    /**
     * @version 1.1.0
     */
    const VERSION  = "1.1.0";

    /**
     * @var $API_ROOT_URL is a main URL to access the Meli API's.
     * @var $AUTH_URL is a url to redirect the user for login.
     */
    protected static $API_ROOT_URL = "https://api.mercadolibre.com";
    protected static $OAUTH_URL    = "/oauth/token";
    public static $AUTH_URL = array(
        "MLA" => "https://auth.mercadolibre.com.ar", // Argentina 
        "MLB" => "https://auth.mercadolivre.com.br", // Brasil
        "MCO" => "https://auth.mercadolibre.com.co", // Colombia
        "MCR" => "https://auth.mercadolibre.com.cr", // Costa Rica
        "MEC" => "https://auth.mercadolibre.com.ec", // Ecuador
        "MLC" => "https://auth.mercadolibre.cl", // Chile
        "MLM" => "https://auth.mercadolibre.com.mx", // Mexico
        "MLU" => "https://auth.mercadolibre.com.uy", // Uruguay
        "MLV" => "https://auth.mercadolibre.com.ve", // Venezuela
        "MPA" => "https://auth.mercadolibre.com.pa", // Panama
        "MPE" => "https://auth.mercadolibre.com.pe", // Peru
        "MPT" => "https://auth.mercadolibre.com.pt", // Prtugal
        "MRD" => "https://auth.mercadolibre.com.do"  // Dominicana
    );

    /**
     * Configuration for CURL
     */
    public static $CURL_OPTS = array(
        CURLOPT_USERAGENT => "MELI-PHP-SDK-1.1.0", 
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CONNECTTIMEOUT => 10, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_TIMEOUT => 60
    );

    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;
    protected $access_token;
    protected $refresh_token;

    /**
     * Constructor method. Set all variables to connect in Meli
     *
     * @param string $client_id
     * @param string $client_secret
     * @param string $access_token
     * @param string $refresh_token
     */
    public function __construct($client_id, $client_secret, $access_token = null, $refresh_token = null) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->access_token = $access_token;
        $this->refresh_token = $refresh_token;
    }

    /**
     * Return an string with a complete Meli login url.
     * NOTE: You can modify the $AUTH_URL to change the language of login
     * 
     * @param string $redirect_uri
     * @return string
     */
    public function getAuthUrl($redirect_uri, $auth_url) {
        $this->redirect_uri = $redirect_uri;
        $params = array("client_id" => $this->client_id, "response_type" => "code", "redirect_uri" => $redirect_uri);
        $auth_uri = $auth_url."/authorization?".http_build_query($params);
        return $auth_uri;
    }

    /**
     * Executes a POST Request to authorize the application and take
     * an AccessToken.
     * 
     * @param string $code
     * @param string $redirect_uri
     * 
     */
    public function authorize($code, $redirect_uri) {

        if($redirect_uri)
            $this->redirect_uri = $redirect_uri;

        $body = array(
            "grant_type" => "authorization_code", 
            "client_id" => $this->client_id, 
            "client_secret" => $this->client_secret, 
            "code" => $code, 
            "redirect_uri" => $this->redirect_uri
        );

        $opts = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body
        );
    
        $request = $this->execute(self::$OAUTH_URL, $opts);

        if($request["httpCode"] == 200) {             
            $this->access_token = $request["body"]->access_token;

            if($request["body"]->refresh_token)
                $this->refresh_token = $request["body"]->refresh_token;

            return $request;

        } else {
            return $request;
        }
    }

    /**
     * Execute a POST Request to create a new AccessToken from a existent refresh_token
     * 
     * @return string|mixed
     */
    public function refreshAccessToken() {

        if($this->refresh_token) {
             $body = array(
                "grant_type" => "refresh_token", 
                "client_id" => $this->client_id, 
                "client_secret" => $this->client_secret, 
                "refresh_token" => $this->refresh_token
            );

            $opts = array(
                CURLOPT_POST => true, 
                CURLOPT_POSTFIELDS => $body
            );
        
            $request = $this->execute(self::$OAUTH_URL, $opts);

            if($request["httpCode"] == 200) {             
                $this->access_token = $request["body"]->access_token;

                if($request["body"]->refresh_token)
                    $this->refresh_token = $request["body"]->refresh_token;

                return $request;

            } else {
                return $request;
            }   
        } else {
            $result = array(
                'error' => 'Offline-Access is not allowed.',
                'httpCode'  => null
            );
            return $result;
        }        
    }

    /**
     * Execute a GET Request
     * 
     * @param string $path
     * @param array $params
     * @param boolean $assoc
     * @return mixed
     */
    public function get($path, $params = null, $assoc = false) {
        $exec = $this->execute($path, null, $params, $assoc);

        return $exec;
    }

    /**
     * Execute a POST Request
     * 
     * @param string $body
     * @param array $params
     * @return mixed
     */
    public function post($path, $body = null, $params = array()) {
        $body = json_encode($body);
        $opts = array(
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POST => true, 
            CURLOPT_POSTFIELDS => $body
        );
        
        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     * 
     * @param string $path
     * @param string $body
     * @param array $params
     * @return mixed
     */
    public function put($path, $body = null, $params = array()) {
        $body = json_encode($body);
        $opts = array(
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $body
        );
        
        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     * 
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function delete($path, $params) {
        $opts = array(
            CURLOPT_CUSTOMREQUEST => "DELETE"
        );
        
        $exec = $this->execute($path, $opts, $params);
        
        return $exec;
    }

    /**
     * Execute a OPTION Request
     * 
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function options($path, $params = null) {
        $opts = array(
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        );
        
        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute all requests and returns the json body and headers
     * 
     * @param string $path
     * @param array $opts
     * @param array $params
     * @param boolean $assoc
     * @return mixed
     */
    public function execute($path, $opts = array(), $params = array(), $assoc = false) {
        $uri = $this->make_path($path, $params);

        $ch = curl_init($uri);
        curl_setopt_array($ch, self::$CURL_OPTS);

        if(!empty($opts))
            curl_setopt_array($ch, $opts);

        $return["body"] = json_decode(curl_exec($ch), $assoc);
        $return["httpCode"] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        
        return $return;
    }

    /**
     * Check and construct an real URL to make request
     * 
     * @param string $path
     * @param array $params
     * @return string
     */
    public function make_path($path, $params = array()) {
        if (!preg_match("/^http/", $path)) {
            if (!preg_match("/^\//", $path)) {
                $path = '/'.$path;
            }
            $uri = self::$API_ROOT_URL.$path;
        } else {
            $uri = $path;
        }

        if(!empty($params)) {
            $paramsJoined = array();

            foreach($params as $param => $value) {
               $paramsJoined[] = "$param=$value";
            }
            $params = '?'.implode('&', $paramsJoined);
            $uri = $uri.$params;
        }

        return $uri;
    }
}
