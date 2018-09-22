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
	public static function getLoginCredenciales($tabla)
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
					/*
					array(
		                    'type' => 'html',
		                    'name' => 'html_data',
		                    'html_content' => '<div class="loader"><img class="loader-image" src="'._PS_BASE_URL_.__PS_BASE_URI__.'/modules/todopago/imagenes/loader.gif" alt="loading.."></div>
		                    <div id="error_message"></div>'
				    )	
				    */		
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
