{$elementName = 'kpy-menu'}
<nav>

<ul id="{$elementName}">
{foreach from=$kpymenu.elements item=$element}
	<li class="{$elementName}__element">
		<div class="kpy-backdrop"></div>


		<a href="{$element.link}" class="top-link">
			{include file="components/{$element.template_icon}" classes="{$elementName}__icon"}
			<span>{$element.name}</span>
			<i class="material-icons">keyboard_arrow_down</i>
		</a>

		<div class="{$elementName}__toggle">
			<div class="{$elementName}__elements">
				{foreach from=$element.children item=$category}
					<div class="{$elementName}__title-category">
						<a class="link-menu primary" href="{$category.link}">{$category.name} <i class="material-icons">chevron_right</i></a>
						{foreach from=$category.children item=$child}
							<a href="{$child.link}" class="link-menu secondary">{$child.name}</a>
						{/foreach}
					</div>			
				{/foreach}
			</div>
		</div>
	</li>
{/foreach}

	<li class="{$elementName}__element">
		<div class="kpy-backdrop"></div>
		<a href="{url entity='module' name='kpymanufacturer' controller='brands'}" class="top-link">
			{include file="components/svg-brands.tpl" classes="{$elementName}__icon"}
			{l s='Brands' d='Modules.Kpymenu.Shop'}
			<i class="material-icons">keyboard_arrow_down</i></a>
		<div class="{$elementName}__toggle">
			<div class="{$elementName}__brands">
				<div class="{$elementName}__brands-letters">
					{foreach from=$kpymenu.brands.letters item=$letter}
						<div class="{$elementName}__brand-letter">{$letter}</div>
					{/foreach}
				</div>
				<div class="{$elementName}__brands-list">
					{foreach from=$kpymenu.brands.brands item=$brands key=$letter}
						<div id="{$elementName}__brand-{$letter}" class="{$elementName}__letter-separator">{$letter}</div>
						{foreach from=$brands item=$brand}
							<a class="{$elementName}__brand" href="{$brand.link}">{$brand.name}</a>
						{/foreach}
					{/foreach}
				</div>
			</div>
		</div>
	</li>
	
	<li class="{$elementName}__element">
		<a href="#" class="top-link">
			{include file="components/svg-offer.tpl" classes="{$elementName}__icon"}
			<span>{l s='Offers and promotions' d='Modules.Kpymenu.Shop'}</span>
			<i class="material-icons">keyboard_arrow_down</i>
		</a>
	</li>
</ul>
</nav>