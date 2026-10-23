{**
 * Kompy · «Caprichitos»
 *
 * Una tarjeta por sugerencia, con su «+». El «+» la **añade directo a la cesta**, sin pasos en
 * medio, y la tarjeta se queda en verde con un ✓. Lo que ya está en la cesta sale en verde desde
 * el principio. Solo el botón añade: tocar la tarjeta no mete nada por accidente.
 *
 * La foto y el nombre llevan a la ficha de la sugerencia, en la misma pestaña, para que «Atrás»
 * vuelva aquí con todo como estaba (formato, cantidad, peso elegido y la altura de la página).
 *}
<section class="kcs" data-kcs data-kcs-principal="{$kcs.principal.id_product|intval}-{$kcs.principal.id_product_attribute|intval}"
         data-kcs-url="{$kcs.url|escape:'html':'UTF-8'}" data-kcs-limite="{$kcs.limite|intval}">

  <header class="kcs__cabecera">
    <h2 class="kcs__titulo">
      {$kcs.titulo}
      {if $kcs.juntos}<span class="kcs__premio">{l s='y un %pct% % en los dos' sprintf=['%pct%' => $kcs.juntos.pct] d='Modules.Kpycrosssell.Shop'}</span>{/if}
    </h2>
    <p class="kcs__sub">{$kcs.sub}</p>
  </header>

  <ul class="kcs__lista">
    {foreach $kcs.articulos as $a}
      <li class="kcs__art{if $a.en_cesta} is-puesto{/if}"{if !empty($a.oculto) || !empty($a.sobra)} hidden{/if}{if !empty($a.oculto)} data-kcs-oculto{/if}
              {if !empty($a.pesos)} data-kcs-pesos="{$a.pesos.json|escape:'html':'UTF-8'}"{/if}>
        <div class="kcs__fila">
          {* La foto lleva al mismo sitio que el nombre: para el lector de pantalla basta con el nombre. *}
          <a class="kcs__ir kcs__ir--foto" href="{$a.url|escape:'html':'UTF-8'}" data-kcs-ir tabindex="-1" aria-hidden="true"><img class="kcs__foto" src="{$a.foto|escape:'html':'UTF-8'}" alt="" loading="lazy" width="72" height="72"></a>
          <span class="kcs__texto">
            <a class="kcs__nombre kcs__ir" href="{$a.url|escape:'html':'UTF-8'}" data-kcs-ir>{$a.nombre}</a>
            {if !empty($a.pesos)}
              {* Va por peso (pastilla, pipeta): si con lo que se mira encajan varios, se elige. *}
              <select class="kcs__peso" data-kcs-peso data-kcs-por="{$a.pesos.por}"
                      aria-label="{if $a.pesos.por == 'talla'}{l s='Talla' d='Modules.Kpycrosssell.Shop'}{else}{l s='Peso de tu mascota' d='Modules.Kpycrosssell.Shop'}{/if}">
                {if !$a.id_product_attribute}<option value="0">{if $a.pesos.por == 'talla'}{l s='Elige su talla' d='Modules.Kpycrosssell.Shop'}{else}{l s='Elige su peso' d='Modules.Kpycrosssell.Shop'}{/if}</option>{/if}
                {foreach $a.pesos.opciones as $op}
                  <option value="{$op.ipa|intval}"{if $op.ipa == $a.id_product_attribute} selected{/if}>{$op.etiqueta|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
            {elseif $a.formato}<span class="kcs__formato">{$a.formato}</span>{/if}
          </span>
          <span class="kcs__precio">
            <b>{if !empty($a.desde)}<small data-kcs-desde>{l s='desde' d='Modules.Kpycrosssell.Shop'}</small> {/if}{$a.precio_txt}</b>
            <s{if !$a.antes_txt} hidden{/if}>{$a.antes_txt}</s>
          </span>
          {* El «+» va siempre a la derecha, pegado al precio. Añade directo a la cesta. *}
          <button type="button" class="kcs__palanca" data-kcs-mas
                  value="{$a.id_product|intval}-{$a.id_product_attribute|intval}"
                  data-kcs-nombre="{$a.nombre|escape:'html':'UTF-8'}"
                  aria-pressed="{if $a.en_cesta}true{else}false{/if}"{if $a.en_cesta} disabled{/if}
                  aria-label="{if $a.en_cesta}{l s='%producto%: en la cesta' sprintf=['%producto%' => $a.nombre] d='Modules.Kpycrosssell.Shop'}{else}{l s='Añadir %producto% a la cesta' sprintf=['%producto%' => $a.nombre] d='Modules.Kpycrosssell.Shop'}{/if}"></button>
        </div>
      </li>
    {/foreach}
  </ul>

  {* Lo que se dice al añadir: para quien no ve el verde (lector de pantalla) y para los errores. *}
  <p class="kcs__aviso" data-kcs-aviso role="status" aria-live="polite"></p>
  <script type="application/json" data-kcs-textos>{ldelim}
    "puesto": "{l s='Añadido a la cesta' d='Modules.Kpycrosssell.Shop'|escape:'javascript'}",
    "en_cesta": "{l s='%producto%: en la cesta' d='Modules.Kpycrosssell.Shop'|escape:'javascript'}",
    "error": "{l s='No se ha podido añadir. Inténtalo otra vez.' d='Modules.Kpycrosssell.Shop'|escape:'javascript'}",
    "elige": "{l s='Elige su peso para añadirlo' d='Modules.Kpycrosssell.Shop'|escape:'javascript'}",
    "elige_opcion": "{l s='Elige su peso' d='Modules.Kpycrosssell.Shop'|escape:'javascript'}",
    "elige_talla": "{l s='Elige su talla para añadirlo' d='Modules.Kpycrosssell.Shop'|escape:'javascript'}",
    "elige_opcion_talla": "{l s='Elige su talla' d='Modules.Kpycrosssell.Shop'|escape:'javascript'}",
    "desde": "{l s='desde' d='Modules.Kpycrosssell.Shop'|escape:'javascript'}"
    {rdelim}</script>
</section>