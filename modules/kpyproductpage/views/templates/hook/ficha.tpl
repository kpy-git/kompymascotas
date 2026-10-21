{**
 * Kompy · Ficha de producto: parte de abajo de la ficha.
 * Datos en $kpp (classes/KppFicha.php). Cada bloque sale solo si tiene datos.
 *
 * Móvil primero: en móvil las secciones largas son desplegables (<details>, sin JavaScript) y
 * enseñan un resumen cerradas; en PC salen todas abiertas. El texto está siempre en la página,
 * abierto o cerrado, así que Google lo lee igual.
 *}
<div class="kpp" id="kpp">

  {if $kpp.hay.caracteristicas || $kpp.hay.beneficios}
    <div class="kpp-fila kpp-fila--car">

      {if $kpp.hay.caracteristicas}
        <section class="kpp-sec kpp-car" aria-labelledby="kpp-h-car">
          <h2 class="kpp-h2" id="kpp-h-car">{l s='Características' d='Modules.Kpyproductpage.Shop'}</h2>

          {if $kpp.para}
            <p class="kpp-para">
              <span class="kpp-para__et">{l s='Para' d='Modules.Kpyproductpage.Shop'}</span>
              {foreach $kpp.para as $p}
                <span class="kpp-pill{if $p.principal} kpp-pill--mascota{/if}">{$p.texto}</span>
              {/foreach}
            </p>
          {/if}

          {if $kpp.fichas}
            <dl class="kpp-fichas">
              {foreach $kpp.fichas as $f}
                <div class="kpp-ficha">
                  <dt>{$f.nombre}</dt>
                  <dd>{if $f.url}<a href="{$f.url}">{$f.valor}</a>{else}{$f.valor}{/if}</dd>
                </div>
              {/foreach}
            </dl>
          {/if}

          {if $kpp.referencia || $kpp.ean13 || $kpp.categoria.url}
            <p class="kpp-pie">
              {if $kpp.referencia}<span>{l s='Ref.' d='Modules.Kpyproductpage.Shop'} {$kpp.referencia}</span>{/if}
              {if $kpp.ean13}<span>EAN {$kpp.ean13}</span>{/if}
              {if $kpp.categoria.url}<a href="{$kpp.categoria.url}">{l s='Ver más: %s' sprintf=[$kpp.categoria.nombre] d='Modules.Kpyproductpage.Shop'}</a>{/if}
            </p>
          {/if}
        </section>
      {/if}

      {if $kpp.hay.beneficios}
        <section class="kpp-sec kpp-ben" aria-labelledby="kpp-h-ben">
          <h2 class="kpp-h2" id="kpp-h-ben">{if $kpp.protege}{l s='Protege contra' d='Modules.Kpyproductpage.Shop'}{else}{l s='Beneficios clave' d='Modules.Kpyproductpage.Shop'}{/if}</h2>
          <ul class="kpp-ben__lista">
            {foreach $kpp.beneficios as $b}
              <li class="kpp-ben__item">
                {if $b.icono}<img class="kpp-ben__icono" src="{$b.icono}" alt="" width="56" height="56" loading="lazy" decoding="async">{/if}
                <span>{$b.nombre}</span>
              </li>
            {/foreach}
          </ul>
        </section>
      {/if}

    </div>
  {/if}

  {if $kpp.hay.ingredientes || $kpp.hay.composicion}
    <div class="kpp-fila kpp-fila--comp">

      {if $kpp.hay.ingredientes}
        {assign var=ing value=$kpp.ingredientes}
        <section class="kpp-sec kpp-ing" id="kpp-ingredientes">
          <details class="kpp-acc" data-kpp-acc{if $kpp.abierta.ingredientes} open{/if}>
            <summary class="kpp-acc__cab">
              <h2 class="kpp-h2">{l s='Ingredientes' d='Modules.Kpyproductpage.Shop'}</h2>
              {if $kpp.resumen.ingredientes}<span class="kpp-acc__res">{$kpp.resumen.ingredientes}</span>{/if}
            </summary>
            <div class="kpp-acc__cuerpo">
              {if $ing.barra}
                <p class="kpp-ing__titular">{$ing.titular}</p>
                <div class="kpp-barra" aria-hidden="true">
                  {foreach $ing.barra as $i => $b}<span class="kpp-barra__seg kpp-c{$i}" style="width:{$b.pct}%"></span>{/foreach}
                </div>
                <ul class="kpp-leyenda">
                  {foreach $ing.barra as $i => $b}
                    <li><span class="kpp-punto kpp-c{$i}" aria-hidden="true"></span>{$b.nombre} <strong>{$b.pct_txt}</strong></li>
                  {/foreach}
                </ul>
              {/if}

              {if $ing.sellos}
                <ul class="kpp-sellos">
                  {foreach $ing.sellos as $s}<li>{$s}</li>{/foreach}
                </ul>
              {/if}

              <p class="kpp-ing__texto">{if $ing.negrita}<strong>{$ing.negrita}</strong>{/if}{$ing.resto}</p>
              <p class="kpp-nota">{l s='Ordenados de más a menos cantidad.' d='Modules.Kpyproductpage.Shop'}</p>

              {if $ing.aditivos}
                <details class="kpp-mas">
                  <summary>{l s='Ver aditivos (vitaminas y minerales)' d='Modules.Kpyproductpage.Shop'}</summary>
                  <p>{$ing.aditivos}</p>
                </details>
              {/if}
            </div>
          </details>
        </section>
      {/if}

      {if $kpp.hay.composicion}
        {assign var=comp value=$kpp.composicion}
        <section class="kpp-sec kpp-comp" id="kpp-composicion">
          <details class="kpp-acc" data-kpp-acc{if $kpp.abierta.composicion} open{/if}>
            <summary class="kpp-acc__cab">
              <h2 class="kpp-h2">{l s='Composición analítica' d='Modules.Kpyproductpage.Shop'}</h2>
              {if $kpp.resumen.composicion}<span class="kpp-acc__res">{$kpp.resumen.composicion}</span>{/if}
            </summary>
            <div class="kpp-acc__cuerpo">
              {if $comp.principales}
                <ul class="kpp-cifras">
                  {foreach $comp.principales as $p}
                    <li class="kpp-cifra kpp-cifra--{$p.clave}">
                      {if $p.clave == 'proteina'}
                        <span class="kpp-cifra__n">{l s='Proteína' d='Modules.Kpyproductpage.Shop'}</span><span class="kpp-cifra__v">{$p.valor}</span><span class="kpp-cifra__ayuda">{l s='Construye músculo' d='Modules.Kpyproductpage.Shop'}</span>
                      {elseif $p.clave == 'grasa'}
                        <span class="kpp-cifra__n">{l s='Grasa' d='Modules.Kpyproductpage.Shop'}</span><span class="kpp-cifra__v">{$p.valor}</span><span class="kpp-cifra__ayuda">{l s='La energía del día' d='Modules.Kpyproductpage.Shop'}</span>
                      {elseif $p.clave == 'fibra'}
                        <span class="kpp-cifra__n">{l s='Fibra' d='Modules.Kpyproductpage.Shop'}</span><span class="kpp-cifra__v">{$p.valor}</span><span class="kpp-cifra__ayuda">{l s='Buen tránsito intestinal' d='Modules.Kpyproductpage.Shop'}</span>
                      {else}
                        <span class="kpp-cifra__n">{l s='Ceniza' d='Modules.Kpyproductpage.Shop'}</span><span class="kpp-cifra__v">{$p.valor}</span><span class="kpp-cifra__ayuda">{l s='Son los minerales, no ceniza' d='Modules.Kpyproductpage.Shop'}</span>
                      {/if}
                    </li>
                  {/foreach}
                </ul>
              {/if}
              {if $comp.resto}
                <table class="kpp-tabla">
                  <tbody>
                    {foreach $comp.resto as $r}
                      <tr><th scope="row">{$r.nombre}</th><td>{$r.valor}</td></tr>
                    {/foreach}
                  </tbody>
                </table>
              {/if}
            </div>
          </details>
        </section>
      {/if}

    </div>
  {/if}

  {if $kpp.hay.racion}
    {assign var=rac value=$kpp.racion}
    <section class="kpp-sec kpp-rac" id="kpp-racion">
      <details class="kpp-acc" data-kpp-acc{if $kpp.abierta.racion} open{/if}>
        <summary class="kpp-acc__cab">
          <h2 class="kpp-h2">{l s='Ración diaria' d='Modules.Kpyproductpage.Shop'}</h2>
          <span class="kpp-acc__res">{l s='Cuánto le toca al día' d='Modules.Kpyproductpage.Shop'}</span>
        </summary>
        <div class="kpp-acc__cuerpo">
          {if $rac.tablas}
            {assign var=t0 value=$rac.tablas[0]}
            <div class="kpp-calc" data-kpp-calc>
              {if count($rac.tablas) > 1}
                <div class="kpp-tabs" role="group" aria-label="{l s='Tipo de ración' d='Modules.Kpyproductpage.Shop'}">
                  {foreach $rac.tablas as $ti => $t}
                    <button type="button" class="kpp-tab" aria-pressed="{if $ti == 0}true{else}false{/if}" data-kpp-tabla="{$ti}">{$t.pestana|default:$t.titulo}</button>
                  {/foreach}
                </div>
              {/if}
              {* Sin espacios dentro: vacío, se oculta solo (:empty) *}
              <p class="kpp-calc__det" data-kpp-detalle>{$t0.detalle|default:''}</p>
              <p class="kpp-calc__preg" id="kpp-calc-preg" data-kpp-titulo>{$t0.titulo}</p>
              <div class="kpp-chips" role="group" aria-labelledby="kpp-calc-preg" data-kpp-filas>
                {foreach $t0.filas as $fi => $f}
                  <button type="button" class="kpp-chip" aria-pressed="{if $fi == $rac.fila}true{else}false{/if}" data-kpp-fila="{$fi}">{$f.etiqueta}</button>
                {/foreach}
              </div>
              {if count($t0.columnas) > 1}
                <div class="kpp-chips kpp-chips--col" role="group" data-kpp-columnas>
                  {foreach $t0.columnas as $ci => $c}
                    <button type="button" class="kpp-chip" aria-pressed="{if $ci == 0}true{else}false{/if}" data-kpp-col="{$ci}">{$c}</button>
                  {/foreach}
                </div>
              {/if}
              <div class="kpp-calc__res" aria-live="polite">
                <strong class="kpp-calc__valor" data-kpp-valor>{$t0.filas[$rac.fila].valores[0]}</strong>
                {* Sin espacios dentro: vacía, la lista se oculta sola (:empty) *}
                <ul class="kpp-calc__dias" data-kpp-dias data-titulo="{l s='Cuánto le dura:' d='Modules.Kpyproductpage.Shop'}">{foreach $rac.dias as $d}<li>{$d.texto}</li>{/foreach}</ul>
              </div>
              <script type="application/json" data-kpp-datos>{$rac.datos nofilter}</script>
            </div>
            <details class="kpp-mas">
              <summary>{l s='Ver la tabla completa' d='Modules.Kpyproductpage.Shop'}</summary>
              <div class="kpp-rac__tabla">{$rac.html_tablas nofilter}</div>
            </details>
            {if $rac.nota}<p class="kpp-nota">{$rac.nota}</p>{/if}
          {else}
            <div class="kpp-rac__tabla">{$rac.html nofilter}</div>
          {/if}
        </div>
      </details>
    </section>
  {/if}

  {if $kpp.hay.descripcion || $kpp_marca}
    <section class="kpp-sec kpp-desc" id="kpp-descripcion">
      <details class="kpp-acc" data-kpp-acc{if $kpp.abierta.descripcion} open{/if}>
        <summary class="kpp-acc__cab">
          {* El id es el del enlace «Mostrar descripción completa» de arriba de la ficha (tema). *}
          <h2 class="kpp-h2" id="product-description-heading">{l s='Descripción' d='Modules.Kpyproductpage.Shop'}</h2>
          {if $kpp.resumen.descripcion}<span class="kpp-acc__res">{$kpp.resumen.descripcion}</span>{elseif $kpp_marca && $kpp_marca.opinion}<span class="kpp-acc__res">{l s='La opinión de nuestra especialista, fotos y más' d='Modules.Kpyproductpage.Shop'}</span>{/if}
        </summary>
        <div class="kpp-acc__cuerpo kpp-desc__texto rich-text">
          {$kpp.descripcion nofilter}
          {* Contenido propio de la marca (classes/KppMarca.php): lo que tenía hecho a mano. *}
          {if $kpp_marca}
            {assign var=km value=$kpp_marca}
            <div class="kpp-marca" style="--kpp-marca-color:{$km.color};--kpp-marca-tinta:{$km.tinta}">
            {if $km.opinion}
              <section class="kpp-marca__opinion">
                {if $km.opinion.foto}<img class="kpp-marca__opinion-foto" src="{$km.opinion.foto.url}" alt="{$km.opinion.firma|escape:'html':'UTF-8'}" width="{$km.opinion.foto.ancho}" height="{$km.opinion.foto.alto}" loading="lazy" decoding="async">{/if}
                <div>
                  <h3>{l s='La opinión de nuestra especialista' d='Modules.Kpyproductpage.Shop'}</h3>
                  {if $km.opinion.firma}<p class="kpp-marca__firma">{l s='Por' d='Modules.Kpyproductpage.Shop'} <strong>{$km.opinion.firma|escape:'html':'UTF-8'}</strong></p>{/if}
                  {$km.opinion.texto nofilter}
                </div>
              </section>
            {/if}
            {foreach $km.fotos as $f}
              <img class="kpp-marca__foto" src="{$f.url}" alt="{$kpp.nombre|escape:'html':'UTF-8'}" width="{$f.ancho}" height="{$f.alto}" loading="lazy" decoding="async">
            {/foreach}
            {foreach $km.cajas as $ci => $c}
              <section class="kpp-marca__caja{if $ci % 2 == 1} kpp-marca__caja--der{/if}">
                {if $c.foto}<img src="{$c.foto.url}" alt="" width="{$c.foto.ancho}" height="{$c.foto.alto}" loading="lazy" decoding="async">{/if}
                <div>{$c.html nofilter}</div>
              </section>
            {/foreach}
            {if $km.claves}
              <h3 class="kpp-marca__titulo">{l s='Por qué elegirlo' d='Modules.Kpyproductpage.Shop'}</h3>
              <ul class="kpp-marca__claves">
                {foreach $km.claves as $b}
                  <li>{if $b.icono}<img src="{$b.icono}" alt="" width="48" height="48" loading="lazy" decoding="async">{/if}<p>{$b.html nofilter}</p></li>
                {/foreach}
              </ul>
            {/if}
            {if $km.ingredientes}
              <h3 class="kpp-marca__titulo kpp-marca__titulo--rojo">{l s='Ingredientes principales' d='Modules.Kpyproductpage.Shop'}</h3>
              <ul class="kpp-marca__ingredientes">
                {foreach $km.ingredientes as $i}
                  <li><img src="{$i.foto.url}" alt="" width="{$i.foto.ancho}" height="{$i.foto.alto}" loading="lazy" decoding="async"><span>{$i.nombre|escape:'html':'UTF-8'}</span></li>
                {/foreach}
              </ul>
            {/if}
            </div>
          {/if}
        </div>
      </details>
    </section>
  {/if}

  {if $kpp_marca && $kpp_marca.faq}
    <section class="kpp-sec kpp-faq" id="kpp-preguntas">
      <details class="kpp-acc" data-kpp-acc{if $kpp.abierta.descripcion} open{/if}>
        <summary class="kpp-acc__cab">
          <h2 class="kpp-h2">{l s='Preguntas frecuentes' d='Modules.Kpyproductpage.Shop'}</h2>
          <span class="kpp-acc__res">{$kpp_marca.faq|count} {l s='preguntas de otros clientes' d='Modules.Kpyproductpage.Shop'}</span>
        </summary>
        <div class="kpp-acc__cuerpo">
          {foreach $kpp_marca.faq as $q}
            <details class="kpp-faq__item">
              <summary>{$q.pregunta|escape:'html':'UTF-8'}</summary>
              <div class="kpp-faq__resp">{$q.respuesta nofilter}</div>
            </details>
          {/foreach}
        </div>
      </details>
    </section>
  {/if}

</div>
