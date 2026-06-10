{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

{$componentName = 'email-subscription'}

{if in_array($page.page_name, ['product', 'category', 'index', 'cart', 'my-account', 'contact', 'kpybrands', 'kpylanding'])
    || $page.page_name|substr:0:13 === 'module-kpyfaq' }

    <div class="{$componentName}">
      <div class="container px-1">
        <div class="{$componentName}__content">
            <div class="{$componentName}__img">
                <img src="{$urls.img_url}home_newsletter.svg" alt="home newsletter">
            </div>

          <div class="{$componentName}__content__header">
            <div class="{$componentName}__content__title">
              ¡No te suscribas!
            </div>

            <div class="{$componentName}__content__subtitle">
              A menos que quieras descuentos exclusivos y ahorrar <strong>+200€</strong> al año en productos para tu mascota.
            </div>

            <div class="{$componentName}__content__text">
              Te damos <strong>5€</strong> para tu primera compra.*
            </div>
          </div>

          <div class="{$componentName}__content__form">
            <form action="{$urls.current_url}#blockEmailSubscription_{$hookName}" method="post">
              <div class="{$componentName}__content__inputs">
                <input
                  name="email"
                  type="email"
                  class="form-control"
                  value="{if $customer.is_logged}{$customer.email}{/if}"
                  placeholder="{l s='Your email address' d='Shop.Forms.Labels'}"
                  aria-labelledby="block-newsletter-label-{$hookName}"
                  required
               >

                <input
                  class="btn btn-primary ms-3"
                  name="submitNewsletter"
                  type="submit"
                  value="{l s='Subscribe' d='Shop.Theme.Actions'}"
               >
              </div>


                {if isset($id_module)}
                    {hook h='displayGDPRConsent' id_module=$id_module}
                {/if}

              <div class="{$componentName}__content__infos">
                {if $conditions}
                  <p class="{$componentName}__content__infos conditions">*Puedes darte de baja cuando quieras (es gratis, y tardas 1 segundo). Cupón de un sólo uso y válido si al realizar la compra sigues en la lista.</p>
                {/if}

                {if $msg}
                  <p class="alert {if $nw_error}alert-danger{else}alert-success{/if}">
                    {$msg}
                  </p>
                {/if}

                {hook h='displayNewsletterRegistration'}

              </div>

              <input type="hidden" name="blockHookName" value="{$hookName}" />
              <input type="hidden" name="action" value="0">
            </form>
          </div>
        </div>
      </div>
    </div>

{/if}