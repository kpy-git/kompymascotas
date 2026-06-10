
<li class="{$elementName}__element" {if $element.hasChildren}data-category="{$element.id}"{/if}>
    {if isset($element.template_icon)}
        {include file="components/{$element.template_icon}" classes="{$elementName}__element-icon"}
    {/if}
  <span>{$element.name}</span>

  {if $element.hasChildren}
    <span class="ms-auto"><i class="material-icons">chevron_right</i></span>
  {/if}
</li>