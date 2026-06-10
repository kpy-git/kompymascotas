{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{block name='product_flags'}
  <ul class="product-flags js-product-flags">
    {foreach from=$product.flags item=flag}
      {if $flag.type !== 'discount'}
        <li class="badge {$flag.type}" {if isset($flag.background) && $flag.background != ''}style="background-color:{$flag.background} !important;border-color:{$flag.background} !important" {/if}>
            {if isset($flag.icon)}<img src="{$flag.icon}" alt="{$flag.label}">{/if} {$flag.label}
        </li>
      {/if}
    {/foreach}
  </ul>
{/block}
