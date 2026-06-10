{extends file='customer/page.tpl'}

{block name='page_title'}
    <h1 class="h4">
        <span class="icon-title"><img src="{$module_img}lover-house.svg" alt="{l s='My pets' d='Modules.Kpypets.Shop'}"></span>
        {$page_title}
    </h1>
{/block}

{block name='page_content'}

    <form method="post" action="{url entity='module' name='kpypets' controller='manage'}" class="row">

        {foreach from=$form_fields item=$field}
            {if $field.name|substr:0:7 != 'disease'}
                {form_field field=$field}
            {/if}
        {/foreach}

        <p>{l s='Specific requirements or diseases' d='Modules.Kpypets.Form'}</p>
        {foreach from=$form_fields item=$field}
            {if $field.name|substr:0:7 == 'disease'}
                {form_field field=$field}
            {/if}
        {/foreach}

        {if $show_voucher_info}
            <div class="newsletter-advice">
                <div class="kpy-form-group">
                    <div class="form-check">
                        <input id="newsletter" type="checkbox" name="newsletter" class="form-check-input" value="1">
                        <label for="newsletter" class="form-check-label kpy-form-check-label">
                            {l s='Subscribe to the newsletter and receive my voucher %voucher% €' sprintf=['%voucher%' => $voucher_amount] d='Modules.Kpypets.Form'}
                        </label>
                    </div>
                </div>
            </div>
        {/if}

        <input type="hidden" name="token" value="{$csrf_token}">

        <div class="text-center">
            <button class="btn btn-primary" type="submit" name="pet-form">{l s='Save' d='Shop.Theme.Actions'}</button>
        </div>
    </form>

{/block}
