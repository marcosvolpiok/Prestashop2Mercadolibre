{*
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2018 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div id="mercadolibre_result_success" class="bootstrap">
	<div id="mercadolibre_result_success_text" class="alert alert-success">
		<button type="button" class="close" data-dismiss="alert">×</button>
			
	</div>
</div>

<div id="mercadolibre_result_error" class="bootstrap">
	<div id="mercadolibre_result_error_text" class="alert alert-info">
		<button type="button" class="close" data-dismiss="alert">×</button>
			
	</div>
</div>


<div>
	<input class="btn btn-default" name="ps2ml" type="button" 
	onclick="send_form_product()"
	value="{l s='Exportar a Mercado Libre' mod='mercadolibre2prestashop'}" />
</div>


<div style="display: inline; margin-left: 12.5%; height: 25%">
	<input type="hidden" name="mercadolibre_category" id="mercadolibre_category">
	<div id="mercadolibre_container" style="display: inline">
		<label style="display: inline" for="mercadolibre_id_categ">{l s='Category' mod='mercadolibre2prestashop'} </label>
			<select id="mercadolibre_id_categ" onchange="buscar_hijos(this.value, 1)" data-orden="1" style="width: 30%; display: inline">
		</select>
	</div>

	<div id="mercadolibre_container2" style="display: inline">
		<input class="btn btn-default" type="button" name="mercadolibreCategoria" value="{l s='Assign categories' mod='mercadolibre2prestashop'}" style="display: inline" onclick="send_form_category()">
	</div>
</div>

<br />
<br />
<br />
<br />
<br />


<script>
	$("#mercadolibre_container2").hide();
	$("#mercadolibre_result_success").hide();
	$("#mercadolibre_result_error").hide();
	var ultimoSeleccionado;


	$.ajax({
		type: 'GET',
		url: 'https://api.mercadolibre.com/sites/{$pais_seleccionado|escape:'htmlall':'UTF-8'}/categories',
		success: function(data){
			$( ".result" ).html( data );
			$("#mercadolibre_id_categ").append('<option value="">{l s='Select category' mod='mercadolibre2prestashop'}</option>');

			data.forEach(function(entry) {
				$("#mercadolibre_id_categ").append('<option value="'+entry.id+'">'+entry.name+'</option>');
			}, this);	  
		},

		error: function(){
			console.log("{l s='There is a problem connecting with Mercado Libre. Please try in 15 minutes' mod='mercadolibre2prestashop'}");
			console.log("{l s='Error connecting to Mercado Libre' mod='mercadolibre2prestashop'}");
		}
	});		



	function buscar_hijos(id_padre, data_orden, detectar_click_anterior=true){
		var data_orden_actual = data_orden + 1;
		var sig = data_orden + 1;

		//Detecta si clickeó en una categoría anterior
		if(detectar_click_anterior==true){
			if(ultimoSeleccionado > data_orden_actual || ultimoSeleccionado == data_orden_actual){
				console.log('Está clickeando en uno anterior');
				console.log('Clickeó en: ' + data_orden);
				console.log('Hay que borrar desde: ' + sig + ', hasta: ' + ultimoSeleccionado);

				for(var i=sig; i <= ultimoSeleccionado; i++){
					console.log('Elimino: ' + i);
					$('*[data-orden="'+ i +'"]').remove();
				}
				$("#mercadolibre_container2").hide();

				buscar_hijos(id_padre, data_orden, false);
				return true;				
			}
		}


		//Busca categorías hijos
		console.log('Busca categorías hijos - id_padre: ' + id_padre);
		$.get( "https://api.mercadolibre.com/categories/"+id_padre, function( data ) {
			console.log('data: ' + data);
			console.log(data.children_categories);

			//Muestra hijos
			if(data.children_categories.length>0){
		    	var select = $("<select id='"+id_padre+"' onchange='buscar_hijos(this.value, " + data_orden_actual + ")' data-orden='" + data_orden_actual + "' style='width: 30%; display: inline'></select>");
		    	$("#mercadolibre_container").append( select );
			    $("#"+id_padre)
			    .append('<option value="">{l s='Selecciona categoría' mod='mercadolibre2prestashop'}</option>');



				data.children_categories.forEach(function(entry) {
				     $("#"+id_padre).append('<option value="'+entry.id+'">'+entry.name+'</option>');
				     console.log('id_padre: ' + id_padre);
				}, this);	

				ultimoSeleccionado = data_orden_actual;
			}else{
				$("#mercadolibre_container2").show();
				$("#mercadolibre_category").val(id_padre);
				
			}
					 
		});
	}

	function send_form_category(){
		console.log(window.location.href);
		//form-product
		$.ajax({
			type: 'POST',
			url: window.location.href+'&mercadolibreCategoria='+true,
			data: $("#form-product").serialize(),
		  success: function(result){ 
		  	var json = jQuery.parseJSON( result );

		  	//alert(result);
		  	$("#mercadolibre_result_success").show();
		  	$("#mercadolibre_result_success_text").append(json.message+'<br />');
		  },

		  error: function(result){ 
		  	//alert(result);
		  	console.log('resultado posteando categoría cn error: ' + result);
		  	console.log(JSON.stringify(result));
		  	$("#mercadolibre_result_error").show();
		  	$("#mercadolibre_result_error_text").append(result+'<br />');
		  }

		});		
	}

	function send_form_product(){
		console.log(window.location.href);
		//form-product
		$.ajax({
		  type: 'POST',
		  url: window.location.href+'&ps2ml=true',
		  data: $("#form-product").serialize(),
		  success: function(result){
		  	console.log('result: ' + result);
 
		  	var json = jQuery.parseJSON( result );
		  	console.log('json product: ' + json);

		  	if(json.success){
				json.success.forEach(function(entry) {
			  		$("#mercadolibre_result_success").show();
			  		$("#mercadolibre_result_success_text").append(entry+'<br />');

				}, this);	
			}

		  	if(json.error){
				json.error.forEach(function(entry) {
			  		$("#mercadolibre_result_error").show();
			  		$("#mercadolibre_result_error_text").append(entry+'<br />');

				}, this);	
			}

		  	//alert(result);
		  },

		  error: function(result){ 
		  	//alert(result);
			data.success.forEach(function(entry) {
			  	$("#mercadolibre_result_error").show();
			  	$("#mercadolibre_result_error_text").append(entry+'<br />');
			}, this);

		  }

		});		

	}
</script>
