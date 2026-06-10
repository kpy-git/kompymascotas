{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{$componentName = 'subcategories'}

{if !empty($subcategories)}
    {if (isset($display_subcategories) && $display_subcategories eq 1) || !isset($display_subcategories) }
        <div id="subcategories" class="{$componentName} ">
            {foreach from=$subcategories item=subcategory}
                <div class="subcategory__wrapper">

                    {if !$subcategory.id_twin_category}
                        {assign var=subcategory_url value=$subcategory.url}
                    {else}
                        {assign var=subcategory_url value={url entity=category id=$subcategory.id_twin_category}}
                    {/if}

                    <a class="subcategory" href="{$subcategory_url}" title="{$subcategory.name|escape:'html':'UTF-8'}">
                        <div class="subcategory__image">
                            {if !$subcategory.has_image_fixed && !empty($subcategory.image_link)}
                              <img
                                      class="img-fluid"
                                      src="{$subcategory.image_link}"
                                      alt="{$subcategory.name|escape:'html':'UTF-8'}"
                                      loading="lazy"
                              >
                            {else}
                                {if $subcategory.has_image_fixed && !empty($subcategory.image.medium.url)}
                                    <img
                                          class="img-fluid"
                                          src="{$subcategory.image.medium.url}"
                                          alt="{$subcategory.name|escape:'html':'UTF-8'}"
                                          width="{$subcategory.image.medium.width}"
                                          height="{$subcategory.image.medium.height}"
                                          loading="lazy"
                                    >
                                {else}
                                    <img
                                          class="img-fluid"
                                          src="{$urls.no_picture_image.small.url}"
                                          width="{$urls.no_picture_image.small.width}"
                                          height="{$urls.no_picture_image.small.height}"
                                          alt="{$subcategory.name|escape:'html':'UTF-8'}"
                                          loading="lazy"
                                    >
                                {/if}
                            {/if}
                        </div>

                        <p class="subcategory__name">{$subcategory.name|escape:'html':'UTF-8'}</p>
                    </a>
                </div>
            {/foreach}
        </div>
    {/if}
{/if}
