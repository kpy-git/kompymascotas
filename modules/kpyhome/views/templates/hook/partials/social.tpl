<section class="social-proof">
  <div class="container">
    <h2 class="section-title">{l s='More than 10,000 owners no longer worry about running out of feed' d='Modules.Kpyhome.Shop'}</h2>

    <!-- Trustpilot Widget -->
    <div class="trustpilot-widget">
      <div class="trustpilot-score">{$comments.grade}/5</div>
      <div class="trustpilot-stars">★★★★★</div>
      <div class="trustpilot-count">{l s='Based on %s verified reviews' sprintf=[$comments.nb] d='Modules.Kpyhome.Shop'} | {l s='Truspilot' d='Modules.Kpyhome.Shop'}</div>
    </div>

    <!-- Testimonios reales -->
    <div class="testimonials-grid">
      {foreach from=$comments.comments item='comment'}
        <div class="testimonial-card">
          <p class="testimonial-text">
            "{$comment.text}"
          </p>
          <p class="testimonial-author">— {$comment.author}</p>
        </div>
      {/foreach}
    </div>
  </div>
</section>