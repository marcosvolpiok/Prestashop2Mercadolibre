<?php
class AdminMlImportController extends ModuleAdminController
{
	public function __construct(){
		echo $this->authMl();
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
		print_r($result);
		/* ***** / PARA OBTENER SELLER ID ***** */
		

		/** ****** OBTENER LISTADO DE ITEMS ***** */
		$url = '/users/'.$sellerId.'/items/search'; //get seller id
		$result = $meli->get($url, array('access_token' => $context->cookie->access_token, 'status' => 'active'));
		//	/users/{Cust_id}/items/search?access_token=$ACCESS_TOKEN Retrieves user’s listings. GET 
		//print_r( $result["body"]->results );
		//print_r($result);

		
		/* devolvió: 
		[0] => MLA706808097
		[1] => MLA756013807
		[2] => MLA756013804
		*/                    


        $link = new Link();
        $arrAdminDir = explode("/", PS_ADMIN_DIR);
        $formAction = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST']
        .__PS_BASE_URI__.$arrAdminDir[ count($arrAdminDir) - 1 ]
        .'/'.$link->getAdminLink('AdminMlImport', true).'&post=true';

		?>

		<form method="post" action="<?php echo $formAction; ?>">
		<?php
		foreach($result["body"]->results as $item){
			/** ******* OBTENER INFO DE CADA ITEM */
			$url = '/items/'.$item; //get seller id
			$result = $meli->get($url, array('access_token' => $context->cookie->access_token));
			//print_R($result);
			/*
			echo $result["body"]->id."<br />";
			echo $result["body"]->title."<br />";
			echo $result["body"]->currency_id."<br />";
			echo $result["body"]->price."<br />";
			echo $result["body"]->permalink."<br />";
			echo $result["body"]->secure_thumbnail."<br />";
			*/
			echo '<a href="'.$result["body"]->permalink.'">
			<!-- <img src="'.$result["body"]->secure_thumbnail.'" /> -->
			'.$result["body"]->title.'<br />
			'.$result["body"]->currency_id . ' ' . $result["body"]->price .'<br />

			</a>
			<input type="checkbox" name="item[]" value="'.$result["body"]->id.'" />
			<br />';
			

		}
		?>
		<input type="submit" />
		</form>

		<?php




        if (Tools::getValue('post')=='true') {
        	echo "posttttttttttttt";
        	//Busca datos del producto en Mercadolibre
        	$default_lang = Configuration::get('PS_LANG_DEFAULT');

        	foreach(Tools::getValue('item') as $item){
        		echo $item;
        		$result = $meli->get($url, array('access_token' => $context->cookie->access_token));
        		print_r($result);

        		
        		$product = new Product();
        		$product->name= array((int)Configuration::get('PS_LANG_DEFAULT') => $result["body"]->title);
        		$product->price= $result["body"]->price;
        		$product->link_rewrite=array((int)Configuration::get('PS_LANG_DEFAULT') =>  'test-importu');
        		$product->quantity = (int)$result["body"]->available_quantity;
        		$product->add();


        		$stock = new StockAvailable();
        		$stock->setQuantity($product->id, 0, (int)$result["body"]->available_quantity);
        		
        	}
        	die;
        }		
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
        /*
                echo "traza 1";
                echo '<pre>';
                print_r($_SESSION);
                echo '</pre>';
        */
        $meli = new Meli($appId, $secretKey);
        if (Tools::getValue('code') || $context->cookie->access_token) {
            // If code exist and session is empty
            if (Tools::getValue('code') && !($context->cookie->access_token)) {
                echo "access_token: (".$context->cookie->access_token.")";

                // If the code was in get parameter we authorize
               	$user = $meli->authorize(Tools::getValue('code'), $redirectURI);
                //print_r($user);


                // Now we create the sessions with the authenticated user
                $context->cookie->access_token = $user['body']->access_token;
                $context->cookie->expires_in = time() + $user['body']->expires_in;
                $context->cookie->refresh_token = $user['body']->refresh_token;
                echo "sesión vacía";	
            } else {
                // We can check if the access token in invalid checking the time
                if ($context->cookie->expires_in < time()) {
                    try {
                    	echo "refresca código";
                        // Make the refresh proccess
                        $meli->refreshAccessToken();

                        // Now we create the sessions with the new parameters
                        $context->cookie->access_token = $user['body']->access_token;
                        $context->cookie->expires_in = time() + $user['body']->expires_in;
                        $context->cookie->refresh_token = $user['body']->refresh_token;

                        //echo "Redireccionar a esta misma URL";
                        Tools::redirect("$redirectURI");
                        die;
                    } catch (Exception $e) {
                        echo "Exception: ",  $e->getMessage(), "\n";
                    }
                }
            }
        } else {
            if ($siteId) {
                return '<p><a alt="'.$this->l('Login using Mercado Libre')
                .'" class="btn" href="'
                . $meli->getAuthUrl($redirectURI, Meli::$AUTH_URL[$siteId]) . '">'
                .$this->l('Login using Mercado Libre').'</a></p>';
            } else {
                return '<p>'.$this->l('Complete the configuration information (country field)').'</p>';
            }
        }
        // /Autentificación API

    }
    
}