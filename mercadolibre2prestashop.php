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

require_once(dirname(__FILE__) . '/classes/Formulario.php');



class Mercadolibre2prestashop extends Module
{
    protected $config_form = false;
    
    public function __construct()//constructor
    {
        //acerca del modulo en si
        $this->name = 'mercadolibre2prestashop';
        $this->tab = 'administration';

        $this->version = '1.1.5';


        $this->author = 'Marcos volpi';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = '2790f66e5d1b812c247ebf44d6447b37';

        $this->autoloadClass();


        parent::__construct();
        
        //lo que se muestra en el listado de modulos en el backoffice
        $this->displayName = $this->l('Mercadolibre integration');//nombre
        $this->description = $this->l('Export products from Prestashop to Mercado Libre');//descripcion
        $this->confirmUninstall = $this->l(
            'Really you want to uninstall this module?'
        );//mensaje que aparece al momento de desinstalar el modulo
    }

    public function autoloadClass($dir = 'classes')
    {
        if ($files = Tools::scandir(_PS_MODULE_DIR_.$this->name, 'php', $dir)) {
            foreach ($files as $file) {
                if (strpos($file, 'index.php')==false) {
                    require_once $file;
                }
            }
        }
    }


    public function install()
    {
        if (Module::isInstalled('mercadolibre2prestashop')) {
            Module::disableByName($this->name);
            die(Tools::displayError($this->l('First you need to uninstal the old version of this module')));
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

        foreach ($sql as $query) {
            try {
                Db::getInstance()->execute($query);
            } catch (Exception $e) {
                echo $this->l('Exception message:'),  $e->getMessage(), "\n";
                return false;
            }
        }
        // /SQL -------------------------------
        
        
        // Sobreescitura
        if (!file_Exists(_PS_ROOT_DIR_ . "/override/controllers/admin/AdminProductsController.php")) {
            mkdir(_PS_ROOT_DIR_ . "/override/controllers");
            mkdir(_PS_ROOT_DIR_ . "/override/controllers/admin");

            $arch=fopen(_PS_ROOT_DIR_ . "/override/controllers/admin/AdminProductsController.php", "w");
            fputs($arch, Tools::file_get_contents(dirname(__FILE__) .
                "/override_copy/controllers/admin/AdminProductsController.php"));
            fclose($arch);
        }

        // /Sobreesctitura


        //Tab
        $parent_tab = new Tab();
        $parent_tab->name = array();
            $parent_tab->name[1] = $this->l('Import from Mercado Libre');

        $parent_tab->class_name = 'AdminMlGenerateCsv';
        $parent_tab->id_parent = 0;
        $parent_tab->module = $this->name;
        $parent_tab->add();
        // /Tab



        return parent::install() &&
                    $this->registerHook('displayAdminListAfter');
    }

    public function uninstall()
    {
        if (!parent::uninstall() || !$this->unregisterHook('displayAdminListAfter')) {
            return false;
        }

        unlink(_PS_ROOT_DIR_ . "/override/controllers/admin/AdminProductsController.php");

        $idTab = Tab::getIdFromClassName('AdminMlGenerateCsv');
        $tab = new Tab($idTab);
        $tab->delete();

        

        return true;
    }
    

    public function hookDisplayAdminListAfter()
    {
        if (Tools::getValue('controller')!='AdminProducts') {
            return;
        }

        // Autentificación API
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/vendor/mercadolibre-php-sdk/Meli/meli.php');
        require_once(_PS_ROOT_DIR_ . '/modules/mercadolibre2prestashop/classes/Ml2presta.php');
 
        $prefijo="MERCADOLIBRE2PRESTASHOP";
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $siteId = trim(Configuration::get($prefijo.'_PAIS'));

        $link = new Link();
        $arrAdminDir = explode("/", PS_ADMIN_DIR);

        if (!empty($_SERVER['HTTPS'])) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }
         
        $redirectURI = $protocol . '://'.$_SERVER['HTTP_HOST']
        .__PS_BASE_URI__.$arrAdminDir[ count($arrAdminDir) - 1 ]
        .'/'.$link->getAdminLink('AdminProducts', true).'&authFin=true';
        /*
                echo "traza 1";
                echo '<pre>';
                print_r($_SESSION);
                echo '</pre>';
        */
        $meli = new Meli($appId, $secretKey);
        if (Tools::getValue('code') || $this->context->cookie->access_token) {
            // If code exist and session is empty
            if (Tools::getValue('code') && !($this->context->cookie->access_token)) {
                // If the code was in get parameter we authorize
                $user = $meli->authorize(Tools::getValue('code'), $redirectURI);

                // Now we create the sessions with the authenticated user
                $this->context->cookie->access_token = $user['body']->access_token;
                $this->context->cookie->expires_in = time() + $user['body']->expires_in;
                $this->context->cookie->refresh_token = $user['body']->refresh_token;
            } else {
                // We can check if the access token in invalid checking the time
                if ($this->context->cookie->expires_in < time()) {
                    try {
                        // Make the refresh proccess
                        $meli->refreshAccessToken();

                        // Now we create the sessions with the new parameters
                        $this->context->cookie->access_token = $user['body']->access_token;
                        $this->context->cookie->expires_in = time() + $user['body']->expires_in;
                        $this->context->cookie->refresh_token = $user['body']->refresh_token;

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


        $this->context->smarty->assign(array(
            'pais_seleccionado' => trim(Configuration::get($prefijo.'_PAIS')),
        ));

        return $this->display(__FILE__, 'views/templates/admin/hook-display-admin.tpl');
    }
    

    /**
     * Carga el formulario de configuration del modulo.
     */
    public function getContent()
    {
        $this->postear();

        //Autentificación api
        $prefijo = 'MERCADOLIBRE2PRESTASHOP';
        Configuration::updateValue($prefijo.'_', null);
        $appId = trim(Configuration::get($prefijo.'_APPID'));
        $secretKey = trim(Configuration::get($prefijo.'_SECRETKEY'));
        $imageTypeSelected = trim(Configuration::get($prefijo.'_IMAGETYPE'));
        

        // /Autentificación api
        $arrPaises = array(
            "MLA" => "Argentina",
            "MLB" => "Brasil",
            "MCO" => "Colombia",
            "MCR" =>"Costa Rica",
            "MEC" =>"Ecuador",
            "MLC" =>"Chile" ,
            "MLM" =>"Mexico",
            "MLU" =>"Uruguay",
            "MLV" =>"Venezuela" ,
            "MPA" =>"Panama",
            "MPE" =>"Peru" ,
            "MPT" =>"Portugal" ,
            "MRD" =>"Dominicana"
        );

        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'version' => $this->version,
            'url_base' => "//".Tools::getHttpHost(false).__PS_BASE_URI__,
            'config_general' => $this->renderConfigForms(),
            'pais_seleccionado' => trim(Configuration::get($prefijo.'_PAIS')),
            'moneda_seleccionada' => trim(Configuration::get($prefijo.'_MONEDA')),
            'paises' => $arrPaises,
            'appId' => $appId,
            'secretKey' => $secretKey,
            'imagesTypes' => ImageType::getImagesTypes('products'),
            'imageTypeSelected' => $imageTypeSelected,
            'submitForm' => Tools::isSubmit('btnSubmitLogin')
        ));
        $output = $this->context->smarty->fetch($this->local_path.
        'views/templates/admin/configure.tpl');//recupero el template de configuracion
        
        return $output;
    }


    public function postear()
    {
        if (Tools::isSubmit('btnSubmitLogin')) {
            Mercadolibre2prestashop\Formulario::postProcessFormularioConfigs(
                $this->getPrefijo('PREFIJO_CONFIG'),
                Mercadolibre2prestashop\Formulario::getFormInputsNames(
                    Mercadolibre2prestashop\Formulario::getLoginCredenciales()
                )
            );
        } elseif (Tools::isSubmit('btnSubmitImage')) {
            //echo "submit image" . Tools::getValue('imageType');
            //die;

            \Configuration::updateValue($this->getPrefijo('PREFIJO_CONFIG').'_'
                .\Tools::strtoupper('imageType'), Tools::getValue('imageType'));
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
     * Genera el  formulario que corresponda segun la tabla ingresada
     * @param string $tabla nombre de la tabla
     * @param array $fields_value
     */
    public function renderForm($tabla)
    {
        $form_fields="";

        switch ($tabla) {
            case 'login':
                $form_fields = Mercadolibre2prestashop\Formulario::getFormFields(
                    $this->l('Credentials'),
                    $this->l('Save'),
                    Mercadolibre2prestashop\Formulario::getLoginCredenciales()
                );
                $prefijo = $this->getPrefijo('CONFIG_LOGIN_CREDENCIAL');
                $prefijo = 'MERCADOLIBRE2PRESTASHOP';

                break;
        }

        if (isset($prefijo)) {
            $fields_value= Mercadolibre2prestashop\Formulario::getConfigs(
                $prefijo,
                Mercadolibre2prestashop\Formulario::getFormInputsNames($form_fields['form']['input'])
            );
        }

        return $this->getHelperForm($tabla, $fields_value)->generateForm(array($form_fields));
    }
    


    /**
     * Genera un formulario
     * @param String $tabla nombre de la tabla que se usa para generar el formulario
     */
    public function getHelperForm($tabla, $fields_value = null)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;//no mostrar el toolbar
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;//el idioma por defecto
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit'.
        Tools::ucfirst($tabla);//nombre del boton de submit
        //Util al momento de procesar el formulario

        //mejorar este codigo, solo para el form de login de credenciales remueve la url y token de action
        if ($tabla != "login") {
            $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
        } else {
            $helper->currentIndex = "#";
            $helper->token = "";
        }

        if ($tabla == "login") {
            $fields_value['id_user'] = " ";
        }


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
        
        if (strcasecmp($nombre, 'PREFIJO_CONFIG') == 0) {
            return $prefijo;
        }
        
        return '';
    }
    
    /**
     * Crea las variables de configuracion, asi se encuentran todas juntas en la base de datos
     */
    public function createConfigVariables()
    {
        $prefijo = 'MERCADOLIBRE2PRESTASHOP';
        $loginCredentials = Mercadolibre2prestashop\Formulario::getLoginCredenciales();
        $inputs = Mercadolibre2prestashop\Formulario::getFormInputsNames($loginCredentials);

        foreach ($inputs as $nombre) {
            //print_r($nombre);
            Configuration::updateValue($prefijo.'_'.Tools::strtoupper($nombre), null);
        }
    }
}
