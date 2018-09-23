<?php

namespace Mercadolibre2prestashop;

require_once(dirname(__FILE__).'../../../../config/config.inc.php');
require_once(dirname(__FILE__).'../../../../init.php');

class Formulario {
	/**
	 * Genera los form fields necesarios para crear un formulario
	 */
	public static function getFormFields($titulo, $inputs)
	{	
			$elements = array(
						'form' => array(
								'legend' => array(
										'title' => $titulo,//titulo del form
										'icon' => 'icon-cogs',//icono
								),
								'input' =>$inputs,
								'submit' => array(
										'title' => 'Guardar',
										'class' => 'button'
								)
						)		
								
			);

		return $elements;
	}
	

	
	/**
	 * @return un array con los campos del formulario
	 */
	public static function getLoginCredenciales($tabla=null)
	{	

		return  array(
					array(
							'type' => 'text',
							'label' =>'AppId',
							'name' =>  'appId',
							'required' => true
					),
					array(
							'type' => 'text',
							'label' =>'SecretKey',
							'name' =>  'secretKey',
							'required' => true
					),
				array(
						'type' => 'select',
						'label' =>'País',
						'name' =>  'pais',
						'desc' => 'Seleccione el país con el cual opera con MercadoLibre',
						'required' => false,
						'options' => array(
								'query' => array(
									array("id_option" => null,  "name" => "Seleccione su país"),
									array("id_option" => "MLA",  "name" => "Argentina"),
									array("id_option" => "MLB",  "name" => "Brasil"),
									array("id_option" => "MCO",  "name" => "Colombia"),
									array("id_option" => "MCR",  "name" => "Costa Rica"),
									array("id_option" => "MEC",  "name" => "Ecuador"),
									array("id_option" => "MLC",  "name" => "Chile"),
									array("id_option" => "MLM",  "name" => "Mexico"),
									array("id_option" => "MLU",  "name" => "Uruguay"),
									array("id_option" => "MLV",  "name" => "Venezuela"),
									array("id_option" => "MPA",  "name" => "Panama"),
									array("id_option" => "MPE",  "name" => "Peru"),
									array("id_option" => "MPT",  "name" => "Portugal"),
									array("id_option" => "MRD",  "name" => "Dominicana"),
								),
								'id' => 'id_option',
								'name' => 'name'
						),
						'required' => true
				),
		);
	}



	
	
	/**
	 * Devuelve los nombres de los inputs que existen en el form
	 * @param array $inputs campos de un formulario
	 * @return un array con los nombres
	 */
	public static function getFormInputsNames($inputs)
	{
		$nombres=array();
		
		foreach ($inputs as $campo)
		{
			if (array_key_exists('name', $campo))
			{
				$nombres[] = $campo['name'];
			}
		}
		
		return $nombres;
	}
	
	/**
	 * Escribe en la base de datos los valores de tablas de configuraciones
	 * @param string $prefijo prefijo con el que se identifica al formulario en la tabla de configuraciones. Ejemplo: DECIDIR_TEST
	 * @param array $inputsName resultado de la funcion getFormInputsNames
	 */
	public static function postProcessFormularioConfigs($prefijo, $inputsName)
	{	
		foreach ($inputsName as $nombre)
		{	
			//mejorarlo este codigo
			if($nombre == "authorization"){

				$auth = \Tools::getValue($nombre);
				if(json_decode($auth) == NULL) {
					//armo json de autorization        
					$autorizationId = new \stdClass();
					$autorizationId->Authorization = $auth;
					$auth = json_encode($autorizationId);
				}

				$valueField = $auth;

			}else{
				$valueField = \Tools::getValue($nombre);
			}
			
			\Configuration::updateValue( $prefijo.'_'.strtoupper( $nombre ), $valueField);

		}
	}
	
	/**
	 * Trae de los valores de configuracion del modulo, listos para ser usados como fields_value en un form
	 * @param string $prefijo prefijo con el que se identifica al formulario en la tabla de configuraciones. Ejemplo: DECIDIR_TEST
	 * @param array $inputsName resultado de la funcion getFormInputsNames
	 */
	public static function getConfigs($prefijo, $inputsName)
	{
		$configs = array();
		
		foreach ($inputsName as $nombre)
		{
			$configs[$nombre] = \Configuration::get( $prefijo.'_'.strtoupper( $nombre ));
		}
		
		return $configs;
	}




}
