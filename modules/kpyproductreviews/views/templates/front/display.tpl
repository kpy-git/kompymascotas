{extends file='page.tpl'}

{block name='breadcrumb'}
    <nav data-depth="1" class="breadcrumb__wrapper" aria-label="breadcrumb">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{$product.link}" class="breadcrumb-link"><span><i class="material-icons">chevron_left</i> {l s='Back to product page' d='Modules.Kpyproductreviews.Shop'}</span></a>
                </li>
            </ol>
        </div>
    </nav>
{/block}

{block name='content'}
  {block name='page_header_container'}
    {block name='page_title'}
      <div class="page-header">
        <h1 class="page-title">{l s='Review - %product%' sprintf=['%product%' => $product.name] d='Modules.Kpyproductreviews.Shop'}</h1>
      </div>

    {/block}
  {/block}

  {block name="page_content"}
    <div class="layout">
      <aside class="sidebar">
        <div class="product-img">

            {include file='catalog/_partials/product-flags.tpl'}

          <a href="{$product.link}">
              {if $product.cover}
                  <picture>
                      {if isset($product.cover.bySize.default_md.sources.avif)}
                          <source
                                  srcset="
                {$product.cover.bySize.default_sm.sources.avif} 216w,
                {$product.cover.bySize.default_md.sources.avif} 261w,
                {$product.cover.bySize.default_lg.sources.avif} 336w"
                                  sizes="(min-width: 992px) 25vw, (min-width: 360px) 50vw, 100vw"
                                  type="image/avif"
                          >
                      {/if}

                      {if isset($product.cover.bySize.default_md.sources.webp)}
                          <source
                                  srcset="
                {$product.cover.bySize.default_sm.sources.webp} 216w,
                {$product.cover.bySize.default_md.sources.webp} 261w,
                {$product.cover.bySize.default_lg.sources.webp} 336w"
                                  sizes="(min-width: 992px) 25vw, (min-width: 360px) 50vw, 100vw"
                                  type="image/webp"
                          >
                      {/if}

                      <img
                              class="product-miniature__image"
                              srcset="
              {$product.cover.bySize.default_sm.url} 216w,
              {$product.cover.bySize.default_md.url} 261w,
              {$product.cover.bySize.default_lg.url} 336w"
                              sizes="(min-width: 992px) 25vw, (min-width: 360px) 50vw, 100vw"
                              src="{$product.cover.bySize.default_md.url}"
                              width="{$product.cover.bySize.default_md.width}"
                              height="{$product.cover.bySize.default_md.height}"
                              loading="lazy"
                              alt="{$product.cover.legend}"
                              title="{$product.cover.legend}"
                              data-full-size-image-url="{$product.cover.bySize.home_default.url}"
                      >
                  </picture>
              {/if}
          </a>
        </div>

        <h3>{l s='Customer reviews' d='Modules.Kpyproductreviews.Shop'}</h3>
        <a href="{$product.link}" class="sidebar-product-name">{$product.name}</a>

        <div class="global-score product-comments-additional-info">
          <div class="score-num">{$average_grade|number_format:1}</div>
          <div class="score-right comments-note">
              <div class="grade-stars" data-grade="{$average_grade}">
              </div>
          </div>
        </div>

        <p class="total-reviews">{l s='%nb% verified reviews' sprintf=['%nb%' => $comments_number] d='Modules.Kpyproductreviews.Shop'}</p>

          {include file="module:productcomments/views/templates/hook/grade-bar.tpl" commentsByGrade=$nb_grade}

        {if $post_allowed}

          <button class="btn btn-outline-secondary post-product-comment">
            <i class="material-icons" aria-hidden="true">&#xE3C9;</i>
              {l s='Write your review' d='Modules.Productcomments.Shop'}
          </button>
        {/if}
      </aside>

      <div class="main-content page-product">
          {if isset($vet_review)}
            <div class="vet-card">
              <div class="vet-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                {l s="Our veterinarian's opinion" d='Modules.Kpyproductreviews.Shop'}
              </div>
              <h4>{$vet_review.title}</h4>
              <p>{$vet_review.review}</p>
              <div class="vet-footer">
                <div class="vet-avatar">👩‍</div>
                <div class="vet-info">
                  <strong>{l s='Dra. Laura Martínez' d='Modules.Kpyproductreviews.Shop'}</strong>
                  <span>{l s='Veterinarian'  d='Modules.Kpyproductreviews.Shop'}</span>
                </div>
              </div>
            </div>
          {/if}

        <div id="product-comments-list">
          {foreach from=$comments item=comment}
            <div class="product-comment-list-item comment" data-comment="{$comment.id}">

              <div class="comment-infos">
                <div class="grade-stars" data-grade="{$comment.grade}"></div>
              </div>

              <div class="comment-top">
                <div class="comment-author">
                  {l s='By %name%' sprintf=['%name%' => $comment.customer_name] d='Modules.Kpyproductreviews.Shop'}
                </div>

                <div class="comment-date">
                  {$comment.date_add}
                </div>
              </div>

              <div class="comment-content">
                <p class="comment-text">{$comment.content}</p>
              </div>

              <div class="comment-buttons">
                  {if $usefulness_enabled}
                    <span>{l s='Did you find this helpful?' d='Modules.Productcomments.Shop'}</span>
                    <a class="useful-review">
                      <i class="material-icons thumb_up" data-icon="thumb_up"></i>
                      <span class="useful-review-value">{$comment.usefulness1}</span>
                    </a>
                    <a class="not-useful-review">
                      <i class="material-icons thumb_down" data-icon="thumb_down"></i>
                      <span class="not-useful-review-value">{$comment.usefulness0}</span>
                    </a>
                  {/if}
              </div>
            </div>
          {/foreach}
        </div>


      </div>

    </div>

      {$comment_modal nofilter}
  {/block}
{/block}