<section class="proof-section section-gap">
    <div class="container">

        <h2 class="section-title">
            {if !isset($comments_title)}
                {l s='What our customers say' d='Modules.Kpymanufacturer.Shop'}</h2>
            {else}
                {$comments_title}
            {/if}

        <!-- Stats: solo 2 -->
        <div class="proof-stats">
            <div class="stat-item">
                <div class="stat-num">15.000+</div>
                <div class="stat-label">{l s='satisfied customers' d='Modules.Kpymanufacturer.Shop'}</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">{$average_grade} ⭐</div>
                <div class="stat-label">{l s='Average raiting' d='Modules.Kpymanufacturer.Shop'}</div>
            </div>
        </div>

        <!-- Scroll lateral -->
        <div class="scroll-track-wrap">
            <div class="scroll-track active" id="proof-track">
                {foreach from=$comments item=comment}
                <div class="proof-card">
                    <div class="proof-card__stars">★★★★★</div>
                    <p class="proof-card__text">"{$comment.content}"</p>
                    <div class="proof-card__author">
                        <div class="proof-card__name">{$comment.customer_name}</div>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>

        <div class="mt-5 text-center">
            <a href="#" class="btn btn-outline-secondary">{l s='View all comments' d='Modules.Kpymanufacturer.Shop'} <span><i class="material-icons">chevron_right</i></span></a>
        </div>

    </div>
</section>