<section id="videos" class="video-section section-gap">
    <div class="container">

        <h2 class="section-title">
            {l s='Video guides and recommendations' d='Modules.Kpymanufacturer.Shop'}
        </h2>
        <p class="section-sub">
            {l s='Our experts explain how to choose the right pet food for every situation.' d='Modules.Kpymanufacturer.Shop'}
        </p>

        <div class="scroll-track-wrap">
            <div class="scroll-track" id="video-track">

                {foreach from=$videos item=video}
                <div class="video-card">
                    <div class="video-card__thumb">
                        <!-- <iframe src="https://www.youtube.com/embed/VIDEO_ID" allowfullscreen></iframe> -->
                        {$video.url nofilter}
                        <span class="video-overlay-title">{$video.name}</span>
                    </div>
                    <div class="video-card__body">
                        <p class="video-card__title">{if $video.title == ''}{$video.name}{else}{$video.title}{/if}</p>
                        <p class="video-card__desc">{$video.subtitle}</p>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>

    </div>
</section>