{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{extends 'customer/page.tpl'}

{block name='page_title'}
    {l s='My orders' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}
    {if $orders}
      {*<p>{l s='Here are the orders you\'ve placed since your account was created.' d='Shop.Theme.Customeraccount'}</p>*}

      <div class="orders-date-selector">
        <div class="kpy-form-group">
          <select name="year" id="order-year" class="form-select form-control">
            {foreach from=$modules.kpycustomerhistory.orders_history key=$year item=$months}
              <option value="{$year}" {if isset($modules.kpycustomerhistory.selected_year) and $modules.kpycustomerhistory.selected_year == $year}selected{/if}>{$year}</option>
            {/foreach}
          </select>
        </div>

        <div class="kpy-form-group">
          <select name="month" id="order-month" class="form-select form-control">
            {* si no se ha seleccionado ningún año/mes se crean las options de los meses de primer año del select de años *}
            {if !isset($modules.kpycustomerhistory.selected_year)}
              {foreach from=$modules.kpycustomerhistory.orders_history item=$months}
                {if $months@first}
                  {foreach from=$months item=$month}
                    <option value="{$month@key}" {if isset($modules.kpycustomerhistory.selected_month) and $modules.kpycustomerhistory.selected_month == $month@key}selected{/if}>{$month}</option>
                  {/foreach}
                {/if}
              {/foreach}
            {else}
              {foreach from=$modules.kpycustomerhistory.orders_history[$modules.kpycustomerhistory.selected_year] item=$month}
                <option value="{$month@key}" {if isset($modules.kpycustomerhistory.selected_month) and $modules.kpycustomerhistory.selected_month == $month@key}selected{/if}>{$month}</option>
              {/foreach}
            {/if}

          </select>
        </div>
      </div>

      <div class="orders-history">
        {foreach from=$orders item=$order}
          <div class="order-preview">
            <div class="order-preview__field">
              <div class="order-preview__label">{l s='Order number' d='Shop.Theme.Checkout'}</div>
              <div class="order-preview__value">{$order.id_order}</div>
            </div>

            <div class="order-preview__field">
              <div class="order-preview__label">{l s='Date' d='Shop.Theme.Checkout'}</div>
              <div class="order-preview__value">{$order.date_order}</div>
            </div>

            <div class="order-preview__field">
              <div class="order-preview__label">{l s='Total' d='Shop.Theme.Checkout'}</div>
              <div class="order-preview__value">{$order.total_price}</div>
            </div>

            <div class="order-preview__field order-status">
              <div class="order-preview__label">{l s='Status' d='Shop.Theme.Checkout'}</div>
              <div class="order-preview__value">
                <span class="badge {$order.contrast}" style="background-color:{$order.order_state_color}" >
                  {$order.order_state}
              </span>
              </div>
            </div>

            <div class="order-actions">
              <a class="btn btn-outline-secondary order-details" href="{$order.details_url}" data-link-action="view-order-details">{l s='View details' d='Shop.Theme.Checkout'}</a>
            </div>

          </div>
        {/foreach}
      </div>

    {else}
      <div class="alert alert-info" role="alert" data-alert="info">{l s='You have not placed any orders.' d='Shop.Notifications.Warning'}</div>
    {/if}
{/block}
