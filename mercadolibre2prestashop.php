<?php

if (!defined('_PS_VERSION_'))
	exit;


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
	

}
