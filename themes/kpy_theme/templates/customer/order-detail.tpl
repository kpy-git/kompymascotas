{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Order details' d='Shop.Theme.Customeraccount'}
{/block}
{block name='page_content'}
    {block name='order_infos'}
      <div class="order__details">
        <div class="order-header">
          <div class="order-header__details">
            <div class="order-header__item">
              <div class="label">{l s='Order number' d='Shop.Theme.Customeraccount'}</div>
              <div class="value">{$order.details.id}</div>
            </div>

            <div class="order-header__item">
              <div class="label">{l s='Placed on' d='Shop.Theme.Customeraccount'}</div>
              <div class="value">{$order.details.order_date}</div>
            </div>

            <div class="order-header__item">
              <div class="label">{l s='Payment method' d='Shop.Theme.Customeraccount'}</div>
              <div class="value">{$order.details.payment}</div>
            </div>

              <div class="order-header__item">
                <div class="label">{l s='Carrier' d='Shop.Theme.Customeraccount'}</div>
                <div class="value"><img src="{$order.carrier.id}" alt="{$order.carrier.name}"></div>
              </div>

              {if $order.shipping}
                  {if $order.follow_up}

                  <div class="tracking">
                    <p>{l s='Click the following link to track the delivery of your order' d='Shop.Theme.Customeraccount'}</p>

                    <a href="{$order.follow_up}">{$order.follow_up}</a>
                  </div>
                {/if}

              {/if}

            {if $order.details.recyclable}
              <p>
                  {l s='You have given permission to receive your order in recycled packaging.' d='Shop.Theme.Customeraccount'}
              </p>
            {/if}

            {if $order.details.gift_message}
              <p>{l s='You have requested gift wrapping for this order.' d='Shop.Theme.Customeraccount'}</p>
              <p>{l s='Message' d='Shop.Theme.Customeraccount'} {$order.details.gift_message nofilter}</p>
            {/if}
          </div>

          <div class="order-header__actions pt-xs-4 pt-md-0">
            {if $order.details.reorder_url}
                <a href="{$order.details.reorder_url}" class="btn btn-outline-secondary">{l s='Reorder' d='Shop.Theme.Actions'}</a>
              {/if}

              {if $order.details.invoice_url}
                <a href="{$order.details.invoice_url}" class="btn btn-outline-secondary">
                    {l s='Download your invoice' d='Shop.Theme.Customeraccount'}
                </a>
              {/if}


              {hook h='displayOrderDetailActions' order=$order}

          </div>
        </div>
      </div>
    {/block}


    {block name='order_history'}
      <section id="order-history" class="box pt-5">
        <h3 class="h4">{l s='Follow your order\'s status step-by-step' d='Shop.Theme.Customeraccount'}</h3>

        <div class="table-wrapper overflow-auto">
          <table class="table">
            <thead class="thead-default">
            <tr>
              <th>{l s='Date' d='Shop.Theme.Global'}</th>
              <th>{l s='Status' d='Shop.Theme.Global'}</th>
            </tr>
            </thead>

            <tbody>
            {foreach from=$order.history item=state}
              <tr>
                <td>{$state.history_date}</td>
                <td>
                  <span class="badge {$state.contrast}" style="background-color:{$state.color}">
                    {$state.ostate_name}
                  </span>
                </td>
              </tr>
            {/foreach}
            </tbody>
          </table>
        </div>
      </section>
    {/block}



    {block name='addresses'}

      <div class="order-detail__addresses my-4">
          {if $order.addresses.delivery}
              <article id="delivery-address" class="address card">
                <div class="card-body">
                  <p class="address__alias h6 card-title">
                      {l s='Delivery address' d='Shop.Theme.Checkout'}
                  </p>
                  <address class="address__content">{$order.addresses.delivery.formatted nofilter}</address>
                </div>
              </article>
          {/if}

          <article id="invoice-address" class="address card">
            <div class="card-body">
              <p class="address__alias h6 card-title">
                  {l s='Invoice address' d='Shop.Theme.Checkout'}
              </p>
              <address class="address__content">{$order.addresses.invoice.formatted nofilter}</address>
            </div>
          </article>
      </div>
    {/block}

  <div class="order__detail__products">
    <h3 class="h4">{l s='Products' d='Shop.Theme.Customeraccount'}</h3>

      {block name='order_detail'}
          {include file='customer/_partials/order-products.tpl'}
      {/block}
  </div>

  {capture name='displayOrderDetail'}{hook h='displayOrderDetail'}{/capture}
  {if $smarty.capture.displayOrderDetail}
    {$smarty.capture.displayOrderDetail nofilter}
  {/if}

{/block}
