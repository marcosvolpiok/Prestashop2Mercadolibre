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

class AdminMlGenerateCsvController extends ModuleAdminController
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

    

    public function buscarProductos()
    {
        $this->authMl();

        $prefijo="MERCADOLIBRE2PRESTASHOP";
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $meli = new Meli($appId, $secretKey);
        $context=Context::getContext();

        /* ***** PARA OBTENER SELLER ID ***** */
        $url = '/users/me'; //get seller id
        $result = $meli->get(
            $url,
            array('access_token' => $context->cookie->access_token)
        );
        $sellerId=$result["body"]->id;
        //echo $result["body"]->id;
        //echo "<br />";
        //print_r($result);
        /* ***** / PARA OBTENER SELLER ID ***** */
        

        /** ****** OBTENER LISTADO DE ITEMS ***** */
        $url = '/users/'.$sellerId.'/items/search'; //get seller id
        $result = $meli->get($url, array('access_token' => $context->cookie->access_token, 'status' => 'active'));
        //  /users/{Cust_id}/items/search?access_token=$ACCESS_TOKEN Retrieves user???s listings. GET
        //print_r( $result["body"]->results );
        //print_r($result);

        
        /* devolvi??:
        [0] => MLA706808097
        [1] => MLA756013807
        [2] => MLA756013804
        */

        $itemResult = array();
        foreach ($result["body"]->results as $item) {
            // OBTENER INFO DE CADA ITEM
            $url = '/items/'.$item;
            $itemResult[$item] = $meli->get($url, array('access_token' => $context->cookie->access_token));
        }


        $link = new Link();
        $arrAdminDir = explode("/", PS_ADMIN_DIR);
        $formAction = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST']
        .__PS_BASE_URI__.$arrAdminDir[ count($arrAdminDir) - 1 ]
        .'/'.$link->getAdminLink('AdminMlGenerateCsv', true).'&post=true';

        return array(
            'formAction' => $formAction,
            'items' => $result["body"]->results,
            'itemResult' => $itemResult
        );
    }

    public function __construct()
    {
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/vendor/mercadolibre-php-sdk/Meli/meli.php');
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/classes/Ml2presta.php');


        $prefijo="MERCADOLIBRE2PRESTASHOP";
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $meli = new Meli($appId, $secretKey);
        $context=Context::getContext();

        if (Tools::getValue('post')=='true') {
            //Busca datos del producto en Mercadolibre
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=data.csv');
            echo "sku;title;description;price;quantity;images\n";

            foreach (Tools::getValue('item') as $item) {
                $url = '/items/'.$item;
                $result = $meli->get($url, array('access_token' => $context->cookie->access_token));

                $imag=array();
                foreach ($result["body"]->pictures as $pic) {
                    $imag[]=$pic->secure_url;
                }
                
                echo $result["body"]->id.";".$result["body"]->title.";"
                .$result["body"]->title.";".$result["body"]->price
                .";".$result["body"]->available_quantity . ";"
                . implode(",", $imag)."\n";
            }
            die;
        }


        return parent::__construct();
    }



    public function authMl()
    {
        $context=Context::getContext();


        // Autentificaci??n API
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
        .'/'.$link->getAdminLink('AdminMlGenerateCsv', true);

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
                Tools::redirect('location: ' . $meli->getAuthUrl($redirectURI, Meli::$AUTH_URL[$siteId]));
            } else {
                return '<p>'.$this->l('Complete the configuration information (country field)').'</p>';
            }
        }
        // /Autentificaci??n API
    }
}
