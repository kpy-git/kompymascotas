{foreach from=$list item=$item}
  <details class="{$class}-item" name="item">
    <summary class="{$class}-question">{$item.question nofilter}</summary>
    <div class="{$class}-answer">
      <p>{$item.answer nofilter}</p>
    </div>
  </details>
{/foreach}