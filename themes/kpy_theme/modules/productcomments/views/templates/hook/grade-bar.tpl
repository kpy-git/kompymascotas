<div class="product-comments-grade-bar">
    {foreach from=$commentsByGrade item=$comment}
        <div class="grade-bar">
            <div class="grade">{$comment.grade}</div>
            <div class="star-content">
                <div class="star-on"></div>
            </div>
            <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="{$comment.width}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {$comment.width}%"></div>
            </div>
            <div class="grade-nb-comments">{$comment.nb_comments}</div>
        </div>
    {/foreach}
</div>