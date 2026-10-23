{* Nada que listar: la promoción ha terminado, aún no empieza, o este mes no hay de eso. *}
<div class="kpl-vacio">
  <div class="kpl-vacio__ico">{if isset($kpl.vacio.icono)}{$kpl.vacio.icono}{else}🐾{/if}</div>
  <h2>{if isset($kpl.vacio.titulo)}{$kpl.vacio.titulo}{else}Aquí no hay nada ahora mismo{/if}</h2>
  <p>{if isset($kpl.vacio.texto)}{$kpl.vacio.texto}{else}Pero hay más: estas son las promociones de ahora mismo.{/if}</p>
  <div class="kpl-vacio__btns">
    <a class="btn btn-primary" href="{$kpl.url_landing}">Ver todas las promociones</a>
    {if isset($kpl.c) && $kpl.c.marca_url}<a class="btn btn-outline-primary" href="{$kpl.c.marca_url}">Ir a la marca {$kpl.c.marca}</a>{/if}
  </div>
  {if isset($kpl.otras) && $kpl.otras}
    <div class="kpl-minis">
      {foreach $kpl.otras as $o}
        <a class="kpl-mini kpl-mini--{$o.tipo}" href="{$o.url}">
          <div><span class="kpl-et kpl-et--{$o.tipo}">{$o.nombre_tipo}</span><b>{$o.titulo}</b><small>{$o.hasta_texto}</small></div>
          {if $o.imagenes}<img src="{$o.imagenes[0]}" alt="" loading="lazy">{/if}
        </a>
      {/foreach}
    </div>
  {elseif isset($kpl.tipos) && $kpl.tipos}
    {include file='module:kpypromolanding/views/templates/front/_partials/tipos.tpl' tipos=$kpl.tipos}
  {/if}
</div>
