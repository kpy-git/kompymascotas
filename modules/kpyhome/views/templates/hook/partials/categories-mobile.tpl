<div class="stories-container">
  {foreach from=$categories item=category}
    <a href="{url entity='category' id=$category.id}" class="story-item">
      <div class="story-ring">
        <div class="story-img"><img src="{$category.image}" alt="{$category.title}"></div>
      </div>
      <span class="story-name">{$category.title}</span>
    </a>
  {/foreach}
</div>