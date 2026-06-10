{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

  <div class="product-comments-additional-info">
    {include file='module:productcomments/views/templates/hook/average-grade-stars.tpl' grade=$average_grade showGradeAverage=true}

    <div class="me-auto ms-1 pt-1">
      | <span id="product-comments-link" class="link-comment">{$nb_comments} {l s='Read user reviews' d='Modules.Productcomments.Shop'}
    </span></div>

    {*<div class="additional-links">
      {if $post_allowed}
        <a class="link-comment post-product-comment" href="#product-comments-list-header">
          <i class="material-icons edit" data-icon="edit"></i>
          {l s='Write your review' d='Modules.Productcomments.Shop'}
        </a>
      {/if}
    </div> 
    *}   
  </div>

