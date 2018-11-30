<form method="post" action="{$formAction}">
	{foreach from=$items item=item}
		<img src="{$item.body->thumbnail}" alt="Imagen" />
		<label id="item_{$item.body->id}">
			<input for="item_{$item.body->id}" type="checkbox" name="item[]" value="{$item.body->id}"
			{if $item.existe}
				disabled
			{/if}
			 />
			{$item.body->title} - {$item.body->currency_id}  {$item.body->price}
		</label>
		<br />
	{/foreach}

	{* idItems|@print_r *} 
	{* $items|@print_r *} 

	<input type="submit" value="{l s='Create CSV' mod='mercadolibre2prestashop'}" />
</form>