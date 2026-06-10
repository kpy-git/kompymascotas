{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

<section class="contact-form">
    <form action="{$urls.pages.contact}" method="post" {if $contact.allow_file_upload}enctype="multipart/form-data"{/if}>
        {if $notifications}
            <div class="alert {if $notifications.nw_error}alert-danger{else}alert-success{/if}">
                <ul>
                    {foreach $notifications.messages as $notif}
                        <li>{$notif}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        {if !$notifications || $notifications.nw_error}
            <section class="form-fields">

                <h1>{l s='Contact us' d='Shop.Theme.Global'}</h1>

                <div class="kpy-form-group">
                    <select name="id_contact" class="form-select form-control">
                        {foreach from=$contact.contacts item=contact_elt}
                            <option value="{$contact_elt.id_contact}">{$contact_elt.name}</option>
                        {/foreach}
                    </select>
                    <div class="underline"></div>
                    <label class="form-label kpy-form-label">{l s='Subject' d='Shop.Forms.Labels'}</label>
                </div>

                <div class="kpy-form-group">
                    <input
                            class="form-control"
                            name="from"
                            type="email"
                            value="{$contact.email}"
                            placeholder=" "
                    >
                    <label class="form-label kpy-form-label">{l s='Email address' d='Shop.Forms.Labels'}</label>
                    <div class="underline"></div>
                </div>

                {if $contact.orders}
                    <div class="kpy-form-group">
                        <select name="id_order" class="form-select form-control">
                            <option value="">{l s='Select order' d='Shop.Forms.Help'}</option>
                            {foreach from=$contact.orders item=order}
                                <option value="{$order.id_order}">{$order.id_order}</option>
                            {/foreach}
                        </select>
                        <label class="form-label kpy-form-label">{l s='Order number' d='Shop.Forms.Labels'}</label>
                        <span class="form-text optional">
              {l s='optional' d='Shop.Forms.Help'}
            </span>
                    </div>
                {/if}

                {if $contact.allow_file_upload}
                    <div class="kpy-form-group">
                        <input type="file" name="fileUpload" class="form-control">
                        <label class="form-label kpy-form-label" for="fileUpload">{l s='Attachment' d='Shop.Forms.Labels'}</label>
                        <span class="form-text">
              {l s='optional' d='Shop.Forms.Help'}
            </span>
                    </div>
                {/if}

                <div class="kpy-form-group">
                    <label class="form-label kpy-form-label-textarea">{l s='Message' d='Shop.Forms.Labels'}</label>
                    <textarea
                            class="form-control"
                            name="message"
                            rows="4"
                    >{if $contact.message}{$contact.message}{/if}</textarea>

                    <small class="ps-2 text-warning">* {l s='If your query is about a product, please copy the link to the product.' d='Shop.Forms.Labels'}</small>
                </div>

                {if isset($id_module)}
                    <div>
                        {hook h='displayGDPRConsent' id_module=$id_module}
                    </div>
                {/if}

            </section>

            <footer class="form-footer">
                <style>
                    input[name=url] {
                        display: none !important;
                    }
                </style>
                <input type="text" name="url" value=""/>
                <input type="hidden" name="token" value="{$token}" />
                <input class="btn btn-primary" type="submit" name="submitMessage" value="{l s='Send' d='Shop.Theme.Actions'}">
            </footer>
        {/if}
    </form>
</section>
