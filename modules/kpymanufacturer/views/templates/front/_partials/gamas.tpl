{* Solo las pestañas de la mascota que tiene gamas: una marca solo de gatos (Gourmet) no enseña «Para perros», y una solo
   de perros no enseña «Para gatos». Sin gamas, la sección no sale. isset() evita el aviso «Undefined array key».
   Una marca que no es de perro ni de gato (Bunny, roedores: gamas con pet = ALL) enseña sus gamas en rejilla, sin pestañas. *}
{$gamasPerro = []}{if isset($categories.dog)}{$gamasPerro = $categories.dog}{/if}
{$gamasGato = []}{if isset($categories.cat)}{$gamasGato = $categories.cat}{/if}
{$gamasTodas = []}{if isset($categories.all)}{$gamasTodas = $categories.all}{/if}
{if $gamasPerro|count == 0 && $gamasGato|count == 0 && $gamasTodas|count > 0}
<<<<<<< HEAD
<section id="gamas" class="tabs-section section-gap">
    <div class="container">
        <h2 class="section-title">{l s='Choose the right product line for your pet' d='Modules.Kpymanufacturer.Shop'}</h2>
        <p class="section-sub">
            {l s="Find the food that best suits your pet's needs." d='Modules.Kpymanufacturer.Shop'}
        </p>
        <div class="tab-content active">
            <div class="gama-grid">
                {foreach from=$gamasTodas item=category}
                    <a href="{$category.url}" class="gama-card">
                        <span class="gama-card__icon"><img src="{$category.image}" alt="{$category.title}"></span>
                        <h3 class="gama-card__title">{$category.title}</h3>
                        <p class="gama-card__desc">{$category.subtitle}</p>
                        <span class="gama-card__link">{l s='View product line' d='Modules.Kpymanufacturer.Shop'} <span class="material-icons">chevron_right</span></span>
                    </a>
                {/foreach}
            </div>
        </div>
    </div>
</section>
{/if}
{if $gamasPerro|count > 0 || $gamasGato|count > 0}
<section id="gamas" class="tabs-section section-gap">
    <div class="container">
        <h2 class="section-title">{l s='Choose the right product line for your pet' d='Modules.Kpymanufacturer.Shop'}</h2>
        <p class="section-sub">
            {l s="Find the food that best suits your pet's needs." d='Modules.Kpymanufacturer.Shop'}
        </p>

        <!-- Tabs -->
        <div class="tabs-header" role="tablist">
            {if $gamasPerro|count > 0}
            <button class="tab-btn active" role="tab" aria-selected="true" onclick="switchTab('dog', this)">
                <img src="{$urls.img_url}dog-face.svg" alt="dog"> {l s='For dogs' d='Modules.Kpymanufacturer.Shop'}
            </button>
            {/if}
            {if $gamasGato|count > 0}
            <button class="tab-btn{if $gamasPerro|count == 0} active{/if}" role="tab" aria-selected="{if $gamasPerro|count == 0}true{else}false{/if}" onclick="switchTab('cat', this)">
                <img src="{$urls.img_url}cat-face.svg" alt="cat"> {l s='For cats' d='Modules.Kpymanufacturer.Shop'}
            </button>
            {/if}
=======
    <section id="gamas" class="tabs-section section-gap">
        <div class="container">
            <h2 class="section-title">{l s='Choose the right product line for your pet' d='Modules.Kpymanufacturer.Shop'}</h2>
            <p class="section-sub">
                {l s="Find the food that best suits your pet's needs." d='Modules.Kpymanufacturer.Shop'}
            </p>
            <div class="tab-content active">
                <div class="gama-grid">
                    {foreach from=$gamasTodas item=category}
                        <a href="{$category.url}" class="gama-card">
                            <span class="gama-card__icon"><img src="{$category.image}" alt="{$category.title}"></span>
                            <h3 class="gama-card__title">{$category.title}</h3>
                            <p class="gama-card__desc">{$category.subtitle}</p>
                            <span class="gama-card__link">{l s='View product line' d='Modules.Kpymanufacturer.Shop'} <span class="material-icons">chevron_right</span></span>
                        </a>
                    {/foreach}
                </div>
            </div>
>>>>>>> refs/remotes/origin/main
        </div>
    </section>
{/if}
{if $gamasPerro|count > 0 || $gamasGato|count > 0}
    <section id="gamas" class="tabs-section section-gap">
        <div class="container">
            <h2 class="section-title">{l s='Choose the right product line for your pet' d='Modules.Kpymanufacturer.Shop'}</h2>
            <p class="section-sub">
                {l s="Find the food that best suits your pet's needs." d='Modules.Kpymanufacturer.Shop'}
            </p>

<<<<<<< HEAD
        <!-- PERROS -->
        {if $gamasPerro|count > 0}
            <div id="tab-dog" class="tab-content active">
                <div class="gama-grid">
                    {foreach from=$gamasPerro item=category}
                        <a href="{$category.url}" class="gama-card">
                            <span class="gama-card__icon"><img src="{$category.image}" alt="{$category.title}"></span>
                            <h3 class="gama-card__title">{$category.title}</h3>
                            <p class="gama-card__desc">{$category.subtitle}</p>
                            <span class="gama-card__link">{l s='View product line' d='Modules.Kpymanufacturer.Shop'} <span class="material-icons">chevron_right</span></span>
                        </a>
                    {/foreach}
                </div>
=======
            <!-- Tabs -->
            <div class="tabs-header" role="tablist">
                {if $gamasPerro|count > 0}
                    <button class="tab-btn active" role="tab" aria-selected="true" onclick="switchTab('dog', this)">
                        <img src="{$urls.img_url}dog-face.svg" alt="dog"> {l s='For dogs' d='Modules.Kpymanufacturer.Shop'}
                    </button>
                {/if}
                {if $gamasGato|count > 0}
                    <button class="tab-btn{if $gamasPerro|count == 0} active{/if}" role="tab" aria-selected="{if $gamasPerro|count == 0}true{else}false{/if}" onclick="switchTab('cat', this)">
                        <img src="{$urls.img_url}cat-face.svg" alt="cat"> {l s='For cats' d='Modules.Kpymanufacturer.Shop'}
                    </button>
                {/if}
>>>>>>> refs/remotes/origin/main
            </div>

<<<<<<< HEAD
        {if $gamasGato|count > 0}
            <!-- GATOS -->
            <div id="tab-cat" class="tab-content{if $gamasPerro|count == 0} active{/if}">
                <div class="gama-grid">
                    {foreach from=$gamasGato item=category}
                        <a href="{$category.url}" class="gama-card">
                            <span class="gama-card__icon"><img src="{$category.image}" alt="{$category.title}"></span>
                            <h3 class="gama-card__title">{$category.title}</h3>
                            <p class="gama-card__desc">{$category.subtitle}</p>
                            <span class="gama-card__link">{l s='View product line' d='Modules.Kpymanufacturer.Shop'} <span class="material-icons">chevron_right</span></span>
                        </a>
                    {/foreach}
=======
            <!-- PERROS -->
            {if $gamasPerro|count > 0}
                <div id="tab-dog" class="tab-content active">
                    <div class="gama-grid">
                        {foreach from=$gamasPerro item=category}
                            <a href="{$category.url}" class="gama-card">
                                <span class="gama-card__icon"><img src="{$category.image}" alt="{$category.title}"></span>
                                <h3 class="gama-card__title">{$category.title}</h3>
                                <p class="gama-card__desc">{$category.subtitle}</p>
                                <span class="gama-card__link">{l s='View product line' d='Modules.Kpymanufacturer.Shop'} <span class="material-icons">chevron_right</span></span>
                            </a>
                        {/foreach}
                    </div>
>>>>>>> refs/remotes/origin/main
                </div>
            {/if}

<<<<<<< HEAD
    </div>
</section>
=======
            {if $gamasGato|count > 0}
                <!-- GATOS -->
                <div id="tab-cat" class="tab-content{if $gamasPerro|count == 0} active{/if}">
                    <div class="gama-grid">
                        {foreach from=$gamasGato item=category}
                            <a href="{$category.url}" class="gama-card">
                                <span class="gama-card__icon"><img src="{$category.image}" alt="{$category.title}"></span>
                                <h3 class="gama-card__title">{$category.title}</h3>
                                <p class="gama-card__desc">{$category.subtitle}</p>
                                <span class="gama-card__link">{l s='View product line' d='Modules.Kpymanufacturer.Shop'} <span class="material-icons">chevron_right</span></span>
                            </a>
                        {/foreach}
                    </div>
                </div>
            {/if}

        </div>
    </section>
>>>>>>> refs/remotes/origin/main
{/if}
