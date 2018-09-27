<?php

if (!defined('_PS_VERSION_'))
	exit;

require_once (dirname(__FILE__) . '/classes/Formulario.php');



class Mercadolibre2prestashop extends Module
{


	protected $config_form = false;
	
	public function __construct()//constructor
	{
		//acerca del modulo en si
		$this->name = 'mercadolibre2prestashop';
		$this->tab = 'shipping_logistics';
		$this->version = '1';
		$this->author = 'Fullcart';
		$this->bootstrap = true;

        $this->autoload_class();


		parent::__construct();
		
		//lo que se muestra en el listado de modulos en el backoffice
		$this->displayName = $this->l('Mercadolibre integración');//nombre
		$this->description = $this->l('Mercadolibre integración');//descripcion
		$this->confirmUninstall = $this->l('Realmente quiere desinstalar este modulo?');//mensaje que aparece al momento de desinstalar el modulo
	}

    public function autoload_class($dir = 'classes')
    {
        if ($files = Tools::scandir(_PS_MODULE_DIR_.$this->name, 'php', $dir))
        {
            foreach($files as $file)
            {
                if(strpos($file,'index.php')==false) 
                {
                    require_once $file;
                }                
            }                        
        }
    }


	public function install()
	{//instalacion del modulo
		if (Module::isInstalled('mercadolibre2prestashop'))
		{
		  Module::disableByName($this->name);   //note during testing if this is not done, your module will show as installed in modules
		  die(Tools::displayError('Primero debe desinstalar la version anterior del modulo.'));
		}

		$this->createConfigVariables();

		// SQL -------------------------------
		$sql = array(
				//tabla para guardar informacion sobre las transacciones
				'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ml2presta` (
				  `id_ml2presta` int(11) NOT NULL,
				  `id_product` int(11) NOT NULL,
				  `id_ml` VARCHAR(13) NOT NULL,
				  `id_ml_category` VARCHAR(13) NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',

				'ALTER TABLE `'._DB_PREFIX_.'ml2presta`
				ADD PRIMARY KEY (`id_ml2presta`),
				ADD KEY `id_product` (`id_product`)',

				'ALTER TABLE `'._DB_PREFIX_.'ml2presta`
				MODIFY `id_ml2presta` int(11) NOT NULL AUTO_INCREMENT'
		);

		foreach ($sql as $query){
			try{
				Db::getInstance()->execute($query);
			} catch (Exception $e) {
				echo 'Excepción capturada: ',  $e->getMessage(), "\n";
				return false;		
			}
		}
		// /SQL -------------------------------
		
		
		// Sobreescitura
		if(!file_Exists(_PS_ROOT_DIR_ . "/override/controllers/admin/AdminProductsController.php")){
			mkdir(_PS_ROOT_DIR_ . "/override/controllers");
			mkdir(_PS_ROOT_DIR_ . "/override/controllers/admin");

			$arch=fopen(_PS_ROOT_DIR_ . "/override/controllers/admin/AdminProductsController.php", "w");
			fputs($arch, file_get_contents(dirname(__FILE__) . "/override/controllers/admin/AdminProductsController.php"));
			fclose($arch);

		}

		// /Sobreesctitura


		return parent::install() &&
					$this->registerHook('displayAdminListAfter');
	}

	public function uninstall()
	{//desinstalacion
		return parent::uninstall();
	}
	

	public function hookDisplayAdminListAfter()
	{
		// Autentificación API	
    	require_once (_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/vendor/php-sdk/Meli/meli.php');
		require_once (_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/classes/Ml2presta.php');
 
    	$prefijo="MERCADOLIBRE2PRESTASHOP";
    	$appId = trim(Configuration::get($prefijo.'_APPID'));
		$secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
		$siteId = trim(Configuration::get($prefijo.'_PAIS'));

		$link = new Link(); 
		$arrAdminDir = explode("/", PS_ADMIN_DIR);
		$redirectURI = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.$arrAdminDir[ count($arrAdminDir) - 1 ].'/'.$link->getAdminLink('AdminProducts', true).'&ps2ml=true&authFin=true';

        $meli = new Meli($appId, $secretKey);
        if($_GET['code'] || $_SESSION['access_token']) {
	        // If code exist and session is empty
	        if($_GET['code'] && !($_SESSION['access_token'])) {
	            // If the code was in get parameter we authorize
	            $user = $meli->authorize($_GET['code'], $redirectURI);

	            // Now we create the sessions with the authenticated user
	            $_SESSION['access_token'] = $user['body']->access_token;
	            $_SESSION['expires_in'] = time() + $user['body']->expires_in;
	            $_SESSION['refresh_token'] = $user['body']->refresh_token;
	        } else {
	            // We can check if the access token in invalid checking the time
	            if($_SESSION['expires_in'] < time()) {
		            try {
		                // Make the refresh proccess
		                $refresh = $meli->refreshAccessToken();

		                // Now we create the sessions with the new parameters
		                $_SESSION['access_token'] = $refresh['body']->access_token;
		                $_SESSION['expires_in'] = time() + $refresh['body']->expires_in;
		                $_SESSION['refresh_token'] = $refresh['body']->refresh_token;

		                //echo "Redireccionar a esta misma URL";
		                header("location: $redirectURI");
		                die;
		            } catch (Exception $e) {
		                echo "Exception: ",  $e->getMessage(), "\n";
		            }
	            }
	        }
	    } else {
            return '<p><a alt="Login using MercadoLibre oAuth 2.0" class="btn" href="' . $meli->getAuthUrl($redirectURI, Meli::$AUTH_URL[$siteId]) . '">Loguearse con Mercado Libre</a></p>' . print_r($_SESSION, true);
        }
            /*
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';
        die ('zzzzzzzzzzzzzz'); */

        // /Autentificación API	

		return $this->display(__FILE__, 'views/templates/admin/hook-display-admin.tpl');
	}
	

	/**
	 * Carga el formulario de configuration del modulo.
	 */
	public function getContent()
	{
		$this->_postProcess();

		//Autentificación api
		$prefijo = 'MERCADOLIBRE2PRESTASHOP';
		Configuration::updateValue($prefijo.'_', null);
		// /Autentificación api		

		$this->context->smarty->assign(array(
			'module_dir' 	 	  => $this->_path,
			'version'    	 	  => $this->version,
			'url_base'			  => "//".Tools::getHttpHost(false).__PS_BASE_URI__,
			'config_general' 	  => $this->renderConfigForms(),
		));
		$output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');//recupero el template de configuracion
		
		return $output;
	}


    public function _postProcess()
    {    
		if (Tools::isSubmit('btnSubmitLogin'))
		{
			Mercadolibre2prestashop\Formulario::postProcessFormularioConfigs(
				$this->getPrefijo('PREFIJO_CONFIG'), 
				Mercadolibre2prestashop\Formulario::getFormInputsNames( Mercadolibre2prestashop\Formulario::getLoginCredenciales(null) ) 
			);
		} 
    }		

	/**
	 * @return el html de todos los formularios
	 */
	public function renderConfigForms()
	{
		/*
		return $this->renderForm('config')
					.$this->renderForm('login')
					.$this->renderForm('test')
					.$this->renderForm('produccion')
					.$this->renderForm('estado')
					.$this->renderForm('proxy')
					.$this->renderForm('servicio')
					.$this->renderForm('embebed');
					*/
		return $this->renderForm('login');
	}  

	/**
	 * 	Genera el  formulario que corresponda segun la tabla ingresada
	 * @param string $tabla nombre de la tabla
	 * @param array $fields_value 
	 */
	public function renderForm($tabla)
	{


		$form_fields;

		switch ($tabla)
		{
			case 'login':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('Credenciales', Mercadolibre2prestashop\Formulario::getLoginCredenciales($tabla));
				$prefijo = $this->getPrefijo('CONFIG_LOGIN_CREDENCIAL');
				$prefijo = 'MERCADOLIBRE2PRESTASHOP';

				break;	
		}

		if (isset($prefijo))
			$fields_value= Mercadolibre2prestashop\Formulario::getConfigs($prefijo, Mercadolibre2prestashop\Formulario::getFormInputsNames($form_fields['form']['input']));		

		return $this->getHelperForm($tabla,$fields_value)->generateForm(array($form_fields));
	}
	


	/**
	 * Genera un formulario
	 * @param String $tabla nombre de la tabla que se usa para generar el formulario
	 */
	public function getHelperForm($tabla, $fields_value=NULL)
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;//no mostrar el toolbar
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;//el idioma por defecto es el que esta configurado en prestashop
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
		
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit'.ucfirst($tabla);//nombre del boton de submit. Util al momento de procesar el formulario

		//mejorar este codigo, solo para el form de login de credenciales remueve la url y token de action
		if($tabla != "login"){
			$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
			$helper->token = Tools::getAdminTokenLite('AdminModules');
		}else{
			$helper->currentIndex = "#";
			$helper->token = "";
		}	

		if($tabla == "login")
			$fields_value['id_user'] = " ";


		$helper->tpl_vars = array(
				'fields_value' => $fields_value,
				'languages' => $this->context->controller->getLanguages(),
				'id_language' => $this->context->language->id
		);

		return $helper;
	}




	public function getPrefijo($nombre)
	{
		$prefijo = 'MERCADOLIBRE2PRESTASHOP';
		
		if ( strcasecmp($nombre, 'PREFIJO_CONFIG') == 0)
			return $prefijo;
		
		foreach($variables as $key => $value){
			if ( strcasecmp($key, $nombre) == 0 )
				return $prefijo.'_'.$value;
		}
		return '';
	}
	
	/**
	 * Crea las variables de configuracion, asi se encuentran todas juntas en la base de datos
	 */
	public function createConfigVariables()
	{
		$prefijo = 'MERCADOLIBRE2PRESTASHOP';
		
		foreach ( Mercadolibre2prestashop\Formulario::getFormInputsNames( Mercadolibre2prestashop\Formulario::getLoginCredenciales() ) as $nombre)
		{
			//print_r($nombre);
			Configuration::updateValue($prefijo.'_'.strtoupper( $nombre ), null);
		}
	}	


}
