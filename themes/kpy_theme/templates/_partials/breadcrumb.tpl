{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{$componentName = 'breadcrumb'}

<nav data-depth="{$breadcrumb.count}" class="{$componentName}__wrapper" aria-label="{$componentName}">
  <div class="container">
        <ol class="{$componentName}">
          {block name='breadcrumb'}
            {foreach from=$breadcrumb.links item=path name=breadcrumb}
              {block name='breadcrumb_item'}
                {if $path.url == $urls.pages.index}
                  <li class="{$componentName}-item home-breadcrumb">
                    <a href="{$path.url}" class="{$componentName}-link"><span><i class="material-icons">home</i></span></a>
                  </li>

                {* en las páginas de producto/categorías nos ahorramos poner el último enlace y el segundo (comprar por mascota) *}
                {elseif (($page.page_name == 'product' or $page.page_name == 'category') and ($smarty.foreach.breadcrumb.last or $smarty.foreach.breadcrumb.iteration == 2))}

                {else}
                    <li class="{$componentName}-item">
                        <a href="{$path.url}" class="{$componentName}-link"><span>{$path.title}</span></a>
                    </li>
                {/if}
              {/block}
            {/foreach}
          {/block}
        </ol>
  </div>
</nav>
