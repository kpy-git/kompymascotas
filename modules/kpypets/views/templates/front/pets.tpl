{extends file='customer/page.tpl'}

{block name='page_title'}
    <h1 class="h4">
        <span class="icon-title"><img src="{$module_img}lover-house.svg" alt="{l s='My pets' d='Modules.Kpypets.Shop'}"></span>
        {l s='My pets' d='Modules.Kpypets.Shop'}
    </h1>

{/block}

{block name='page_content'}
    {if empty($pets)}
        <a href="{url entity='module' name='kpypets' controller='manage'}" class="new-pet exclusive">
            <div class="left">
                <span><i class="material-icons">add</i></span>
                <span>{$page_title}</span>
            </div>
            <div class="right">
                {l s='Introduce us to your pets and get better recommendations and exclusive offers' d='Modules.Kpypets.Shop'}
            </div>
        </a>

    {else}
        <div class="pets">
            {foreach from=$pets item=$pet}
                <div class="pet-card">
                    <div class="icon"><img src="{$module_img}{$pet.icon}" alt="{$pet.kind}"></div>
                    <div class="info">
                        <span class="h5">{$pet.name}</span>
                        <span>{$pet.kind} · {$pet.sex}</span>
                        <span>{$pet.race} · {$pet.hair_color}</span>
                        <span>{$pet.age} · {$pet.weight} Kg</span>
                    </div>
                    <div class="actions">
                        <a href="{url entity='module' name='kpypets' controller='manage' params=['id' => $pet.id, 'token' => $csrf_token]}" class="edit">{l s='Edit' d='Modules.Kpypets.Shop'}</a>
                        <a href="#" data-target="{url entity='module' name='kpypets' controller='manage' params=['id' => $pet.id, 'delete' => 1, 'token' => $csrf_token]}" class="open-delete">{l s='Delete' d='Modules.Kpypets.Shop'}</a>
                    </div>
                </div>
            {/foreach}

            <a class="new-pet" href="{url entity='module' name='kpypets' controller='manage'}">
                <span><i class="material-icons">add</i></span>
                <span>{l s='Add new pet' d='Modules.Kpypets.Shop'}</span>
            </a>
        </div>


        <dialog class="delete-pet">
            <h4 class="h4">{l s='Are you sure you want to delete this pet?' d='Modules.Kpypets.Shop'}</h4>
            <p>{l s='This action cannot be undone' d='Modules.Kpypets.Shop'}</p>

            <div class="delete-pet__actions">
                <span class="btn btn-primary delete">{l s='Yes' d='Modules.Kpypets.Form'}</span>
                <span class="btn btn-outline-secondary close-dialog">{l s='No' d='Modules.Kpypets.Form'}</span>
            </div>
        </dialog>
    {/if}
{/block}