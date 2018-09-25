<div>
	<input name="ps2ml" type="submit" value="Publicar en MercadoLibre" />
</div>




<div id="mercadolibre_container">
	<select id="mercadolibre_id_categ" onchange="buscar_hijos(this.value, 1)" data-orden="1">
	</select>
</div>

<div id="mercadolibre_container2"></div>


<script>
	var arrAnterior;

	
	$.get( "https://api.mercadolibre.com/sites/MLA/categories", function( data ) { //Crea categorías padres
	  $( ".result" ).html( data );
	 // alert(data);
	  //alert( "Load was performed." );
	     $("#mercadolibre_id_categ")
	    .append('<option value="">Selecciona categoría</option>');


	  data.forEach(function(entry) {
	   // console.log(entry);
	    //console.log(entry.id);
	    //console.log(entry.name);

	    //$( ".result" ).html( data );

	     $("#mercadolibre_id_categ")
	    .append('<option value="'+entry.id+'">'+entry.name+'</option>');


	  }, this);	  
	});

/*
	function mercadolibre_add_categ(id){
		console.log('++++++++ ' + id);
    	var select = $("<select id='"+id+"' onchange='buscar_hijos('"+id+"')'></select>");
    	$("#mercadolibre_container").html( $("#mercadolibre_container").html() + select);
    	buscar_hijos(id);
	}
	*/

	function buscar_hijos(id_padre, data_orden){
		//Busca categorías hijos
		console.log('Busca categorías hijos - id_padre: ' + id_padre);
		$.get( "https://api.mercadolibre.com/categories/"+id_padre, function( data ) {
			console.log('data: ' + data);
			console.log(data.children_categories);

			if(data.children_categories.length>0){
		    	//var select = $("<select id='"+id_padre+"' onchange='console.log(\"this.value = \" + this.value)'></select>");
		    	var data_orden_actual = data_orden + 1;
		    	var select = $("<select id='"+id_padre+"' onchange='buscar_hijos(this.value, " + data_orden_actual + ")' data-orden='" + data_orden_actual + "'></select>");
		    	$("#mercadolibre_container").append( select );
			    $("#"+id_padre)
			    .append('<option value="">Selecciona categoría</option>');



				data.children_categories.forEach(function(entry) {
				    //console.log(entry);
				    //console.log(entry.id);
				    //console.log(entry.name);

				    //$( ".result" ).html( data );

				     $("#"+id_padre).append('<option value="'+entry.id+'">'+entry.name+'</option>');
				     //$("#"+id_padre).append('<option value="MLA4624">aaaa</option>');

				     console.log('id_padre: ' + id_padre);

				}, this);	
			}else{
				$("#mercadolibre_container2").html("Continuar");
			}
					 
		});

		//Si tiene hijos, llama a función mercadolibre_add_categ(id)
		//Si no tiene hijos, dibuja un botón de continuar
	}
</script>
