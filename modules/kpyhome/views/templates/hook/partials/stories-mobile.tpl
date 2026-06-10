<div class="stories-container">
  {foreach from=$categories item=category}
    <a href="{$category.link}" class="story-item">
      <div class="story-ring">
        <div class="story-img"><img src="{$category.image}" alt="{$category.name}"></div>
      </div>
      <span class="story-name">{$category.name}</span>
    </a>
  {/foreach}
</div>