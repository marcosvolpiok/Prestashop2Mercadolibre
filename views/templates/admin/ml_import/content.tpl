<form method="post" action="{$formAction}">
	<input type="submit" />
</form>


{foreach from=$items item=item}
	<img src="{$item.body->thumbnail}" alt="Imagen" /><br />
	<input type="checkbox" name="item[]" value="{$item.body->id}" />
	{$item.body->title} - {$item.body->currency_id}  {$item.body->price} 
	<br />
{/foreach}





{$idItems|@print_r} 
{$items|@print_r} 



