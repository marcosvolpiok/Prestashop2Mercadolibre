<?php

if (!defined('_PS_VERSION_'))
	exit;

require_once (dirname(__FILE__) . '/classes/Formulario.php');



class Mercadolibre2prestashop extends PaymentModule
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
		parent::__construct();
		
		//lo que se muestra en el listado de modulos en el backoffice
		$this->displayName = $this->l('Mercadolibre integración');//nombre
		$this->description = $this->l('Mercadolibre integración');//descripcion
		$this->confirmUninstall = $this->l('Realmente quiere desinstalar este modulo?');//mensaje que aparece al momento de desinstalar el modulo
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
				'CREATE TABLE `'._DB_PREFIX_.'ml2presta` (
				  `id_ml2presta` int(11) NOT NULL,
				  `id_product` int(11) NOT NULL,
				  `id_ml` int(11) NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',

				'ALTER TABLE `'._DB_PREFIX_.'ml2presta`
				ADD PRIMARY KEY (`id_ml2presta`),
				ADD KEY `id_product` (`id_product`)',

				'ALTER TABLE `'._DB_PREFIX_.'ml2presta`
				MODIFY `id_ml2presta` int(11) NOT NULL AUTO_INCREMENT'
		);

		foreach ($sql as $query)
			if (Db::getInstance()->execute($query) == false)
				return false;		
		// /SQL -------------------------------
						
		return parent::install() &&
					$this->registerHook('displayAdminListAfter');
	}

	public function uninstall()
	{//desinstalacion
		return parent::uninstall();
	}
	

	public function hookDisplayAdminListAfter()
	{
		return $this->display(__FILE__, 'views/templates/admin/hook-display-admin.tpl');
	}
	

	/**
	 * Carga el formulario de configuration del modulo.
	 */
	public function getContent()
	{
		$this->_postProcess();

		$this->context->smarty->assign(array(
			'module_dir' 	 	  => $this->_path,
			'version'    	 	  => $this->version,
			'url_base'			  => "//".Tools::getHttpHost(false).__PS_BASE_URI__,
			'config_general' 	  => $this->renderConfigForms(),
			//'config_mediosdepago' => $this->renderMediosdePagoForm(),
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
			/*
			case 'config':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('general ', Mercadolibre2prestashop\Formulario::getConfigFormInputs($this->getOptions($this->segmento), $this->getOptions($this->canal)));
				$prefijo = $this->getPrefijo('PREFIJO_CONFIG');
				break;
			*/

			case 'login':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('Obtener credenciales', Mercadolibre2prestashop\Formulario::getLoginCredenciales($tabla));
				$prefijo = $this->getPrefijo('CONFIG_LOGIN_CREDENCIAL');
				$prefijo = 'MERCADOLIBRE2PRESTASHOP';

				break;
/*
			case 'test':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('ambiente developers', Mercadolibre2prestashop\Formulario::getAmbienteFormInputs($tabla));
				$prefijo = $this->getPrefijo('CONFIG_TEST');
				break;
			
			case 'produccion':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('ambiente '.$tabla, Mercadolibre2prestashop\Formulario::getAmbienteFormInputs($tabla));
				$prefijo = $this->getPrefijo('CONFIG_PRODUCCION');
				break;

			case 'proxy':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('configuracion - proxy', Mercadolibre2prestashop\Formulario::getProxyFormInputs());
				$prefijo = $this->getPrefijo('CONFIG_PROXY');
				break;
			
			case 'estado':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('estados del pedido', Mercadolibre2prestashop\Formulario::getEstadosFormInputs($this->getOrderStateOptions()));
				$prefijo = $this->getPrefijo('CONFIG_ESTADOS');
				break;
				
			case 'servicio':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('configuracion - servicio', Mercadolibre2prestashop\Formulario::getServicioConfFormInputs());
				$prefijo = $this->getPrefijo('PREFIJO_CONFIG');
				break;
				
			case 'embebed':
				$form_fields = Mercadolibre2prestashop\Formulario::getFormFields('configuracion - formulario hibrido', Mercadolibre2prestashop\Formulario::getEmbebedFormInputs());
				$prefijo = $this->getPrefijo('CONFIG_EMBEBED');
				break;		
				*/		
		}

		if (isset($prefijo))
			$fields_value= Mercadolibre2prestashop\Formulario::getConfigs($prefijo, Mercadolibre2prestashop\Formulario::getFormInputsNames($form_fields['form']['input']));		

		//obtiene el authorization code desde el json guardado
		//$fields_value=$this->getAuthorizationKeyFromJSON($fields_value, $tabla);

		//print_r($fields_value);
		//die;

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
		//$variables = parse_ini_file('config.ini');
		
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
		//$variables = parse_ini_file('config.ini');
		
		foreach ( Mercadolibre2prestashop\Formulario::getFormInputsNames( Mercadolibre2prestashop\Formulario::getLoginCredenciales() ) as $nombre)
		{
			Configuration::updateValue($prefijo.'_'.strtoupper( $nombre ));
		}
	}	


}
