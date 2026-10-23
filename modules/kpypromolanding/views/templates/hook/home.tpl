{**
 * El bloque de la campaña en la portada de la tienda (hook displayContentWrapperTop, solo en la
 * portada). Viva: la banda con el título, el lema, los días que quedan, los accesos por tipo y las
 * marcas, en el sitio del carrusel (que se esconde si la campaña lo pide: el bloque hace de portada),
 * y debajo los productos de la campaña (las marcas prioritarias primero). Programada (dos semanas
 * antes): una línea con cuándo empieza. Estilos dentro: en la portada no se carga la hoja de la
 * landing. Los colores salen de la paleta de la campaña (`KplCampanas::estiloPortada`), en variables
 * del propio bloque. El ancho es el del carrusel (el contenedor de la tienda).
 *}
{assign var=c value=$kpl_home.c}
<style>{literal}
.kpl-home,.kpl-home-productos{box-sizing:border-box;width:100%;max-width:1140px;margin:12px auto 10px;font-family:'Nunito',sans-serif}
.kpl-home{padding:clamp(22px,3.4vw,40px) clamp(18px,3.4vw,44px);border-radius:22px;background:radial-gradient(720px 340px at 0% 0%,var(--h-brillo),transparent 60%),linear-gradient(135deg,var(--h-bg-2),var(--h-bg) 60%);border:1px solid var(--h-borde);box-shadow:0 18px 40px rgba(0,0,0,.18);color:var(--h-fg);display:grid;grid-template-columns:1.25fr .75fr;gap:clamp(16px,3vw,44px);align-items:center;overflow:hidden}
.kpl-home *,.kpl-home-productos *{box-sizing:border-box;min-width:0}
.kpl-home a{text-decoration:none}
.kpl-home__pill{display:inline-flex;align-items:center;gap:8px;max-width:100%;background:var(--h-acc);color:var(--h-acc-fg);border-radius:999px;padding:5px 12px;font-size:11px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;line-height:1.3}
.kpl-home__pill i{width:8px;height:8px;border-radius:50%;background:var(--h-acc-fg);display:inline-block;flex:0 0 auto}
.kpl-home h2{margin:14px 0 0;font-family:'Raleway',sans-serif;font-size:clamp(28px,3.8vw,48px);font-weight:800;letter-spacing:-.9px;line-height:1.04;color:var(--h-fg)}
.kpl-home p{margin:10px 0 0;font-size:16px;color:var(--h-mute);font-weight:600;line-height:1.5;max-width:52ch}
.kpl-home__lema{display:inline-flex;align-items:center;gap:8px;max-width:100%;margin-top:14px;background:var(--h-acc);color:var(--h-acc-fg);border-radius:999px;padding:7px 14px;font-family:'Raleway',sans-serif;font-weight:800;font-size:13px;line-height:1.3}
.kpl-home__btns{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px;align-items:center}
.kpl-home__btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:10px 20px;border-radius:.75rem;background:var(--h-acc-2);color:var(--h-acc-2-fg);font-family:'Raleway',sans-serif;font-weight:800;font-size:15px;letter-spacing:.03em;box-shadow:0 8px 20px rgba(0,0,0,.22)}
.kpl-home__btn:hover{filter:brightness(1.08);color:var(--h-acc-2-fg)}
.kpl-home__tipos{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.kpl-home__tipo{display:inline-flex;align-items:center;gap:6px;min-height:38px;padding:6px 13px;border-radius:999px;border:1.5px solid var(--h-acc);color:var(--h-acc);font-weight:800;font-size:13px;background:transparent;white-space:nowrap}
.kpl-home__tipo:hover{background:var(--h-acc);color:var(--h-acc-fg)}
.kpl-home__tipo b{font-weight:800;opacity:.75}
/* Las marcas: a dos columnas (cuatro logos, dos y dos); si queda una suelta, a todo el ancho. */
.kpl-home__marcas{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.kpl-home__marca{display:flex;align-items:center;justify-content:center;min-height:74px;padding:12px 14px;border-radius:14px;background:#fff;border:1px solid var(--h-borde);box-shadow:0 8px 20px rgba(0,0,0,.18)}
.kpl-home__marca:last-child:nth-child(odd){grid-column:1 / -1}
.kpl-home__marca img{max-height:calc(44px * var(--kpl-logo-k,1));max-width:100%;width:auto;object-fit:contain}
.kpl-home__marca span{font-family:'Raleway',sans-serif;font-weight:800;color:#2A5C68;font-size:14px;text-align:center}
.kpl-home--aviso{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 16px;padding:14px clamp(16px,3vw,28px);min-height:0;background:var(--h-bg)}
.kpl-home--aviso strong{font-family:'Raleway',sans-serif;font-size:17px;font-weight:800;color:var(--h-fg)}
.kpl-home--aviso span{color:var(--h-mute);font-weight:600;font-size:14px}
/* Los productos de la campaña, debajo del bloque: la tarjeta de siempre de la tienda. */
.kpl-home-productos{margin-top:18px}
.kpl-home-productos__h{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:8px 16px;margin:0 0 12px}
.kpl-home-productos__h h2{margin:0;font-family:'Raleway',sans-serif;font-size:clamp(20px,2.2vw,26px);font-weight:800;letter-spacing:-.3px;color:#2A5C68}
.kpl-home-productos__h small{display:block;margin-top:3px;font-size:13.5px;font-weight:700;color:#5B6E75}
.kpl-home-productos__ver{font-weight:800;font-size:14px;color:#2A5C68;text-decoration:underline;text-underline-offset:3px}
/* Rejilla propia (sin la clase `row` del tema, cuyos márgenes negativos la sacaban 12 px por cada lado). */
.kpl-home__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:0}
@media (max-width:1199px){.kpl-home,.kpl-home-productos{max-width:960px}}
@media (max-width:991px){.kpl-home,.kpl-home-productos{max-width:720px}.kpl-home__grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:767px){
  .kpl-home{max-width:none;grid-template-columns:1fr;padding:16px 16px 14px;border-radius:0;border-left:0;border-right:0;gap:12px;margin:0 0 4px}
  .kpl-home h2{font-size:27px;margin-top:10px}
  .kpl-home p{font-size:15px}
  .kpl-home__fechas{display:none}
  .kpl-home__pill{font-size:10px;letter-spacing:1px}
  .kpl-home__lema{font-size:12.5px;margin-top:10px}
  .kpl-home__btns{margin-top:14px}
  .kpl-home__btn{width:100%;min-height:48px;font-size:16px}
  /* Los accesos por tipo y las marcas, en una fila que se desliza: el bloque no se hace eterno. */
  .kpl-home__tipos{flex-wrap:nowrap;overflow-x:auto;margin:10px -16px 0;padding:2px 16px;scrollbar-width:none;-webkit-overflow-scrolling:touch}
  .kpl-home__tipos::-webkit-scrollbar{display:none}
  .kpl-home__tipo{flex:0 0 auto}
  .kpl-home__marcas{display:flex;gap:8px;overflow-x:auto;margin:0 -16px;padding:2px 16px 6px;scrollbar-width:none}
  .kpl-home__marcas::-webkit-scrollbar{display:none}
  .kpl-home__marca{flex:0 0 38%;min-height:56px;padding:8px 10px}
  .kpl-home__marca img{max-height:calc(30px * var(--kpl-logo-k,1))}
  .kpl-home-productos{max-width:none;margin:12px 0 0;padding:0 16px}
  .kpl-home__grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
}
{/literal}{if $kpl_home.sin_carrusel}{literal}
/* Mientras dura la campaña, el bloque hace de portada: el carrusel grande no sale (ni el de móvil). */
.page-content--home .banner-slider-section,.page-content--home .banner-slider-mobile{display:none}
.page-content--home .container-home > .py-4:has(+ .banner-slider-section){display:none}
{/literal}{/if}</style>
{if $c.estado == 'viva'}
<section class="kpl-home" style="{$kpl_home.estilo}" aria-label="{$c.nombre|escape:'html':'UTF-8'}">
  <div>
    <span class="kpl-home__pill"><i></i> {$c.nombre}{if $c.fechas}<span class="kpl-home__fechas"> · {$c.fechas|lower}</span>{/if}</span>
    <h2>{if $c.titulo}{$c.titulo}{else}{$c.nombre} en Kompy{/if}</h2>
    {if $c.texto}<p>{$c.texto}</p>{/if}
    {if $c.lema}<span class="kpl-home__lema">{$c.lema}{if $c.quedan_texto} · {$c.quedan_texto|lower}{/if}</span>{/if}
    <div class="kpl-home__btns">
      <a class="kpl-home__btn" href="{$kpl_home.url}">Ver todo el {$c.nombre} →</a>
    </div>
    {if $kpl_home.tipos}
      <div class="kpl-home__tipos">
        {foreach $kpl_home.tipos as $t}<a class="kpl-home__tipo" href="{$t.url}">{$t.nombre} <b>{$t.n}</b></a>{/foreach}
      </div>
    {/if}
  </div>
  {if $kpl_home.marcas}
    <div class="kpl-home__marcas">
      {foreach $kpl_home.marcas as $m}<a class="kpl-home__marca" href="{$m.url}" title="{$m.marca|escape:'html':'UTF-8'}">{if $m.logo}<img src="{$m.logo}" alt="{$m.marca|escape:'html':'UTF-8'}" loading="lazy" style="--kpl-logo-k:{$m.logo_k|default:1}">{else}<span>{$m.marca}</span>{/if}</a>{/foreach}
    </div>
  {/if}
</section>
{if $kpl_home.productos}
<section class="kpl-home-productos" aria-label="Productos del {$c.nombre|escape:'html':'UTF-8'}">
  <div class="kpl-home-productos__h">
    <div><h2>{$c.nombre}: los productos con más descuento</h2><small>Las marcas de la casa primero. Cuando se acaba, se acaba.</small></div>
    <a class="kpl-home-productos__ver" href="{$kpl_home.url_todas}">Ver todo lo que está en promoción →</a>
  </div>
  {include file='catalog/_partials/productlist.tpl' products=$kpl_home.productos cssClass='kpl-home__grid'}
</section>
{/if}
{else}
<a class="kpl-home kpl-home--aviso" style="{$kpl_home.estilo}" href="{$kpl_home.url}">
  <strong>{$c.nombre}{if $c.faltan_texto}: {$c.faltan_texto|lower}{/if}</strong>
  <span>{if $c.fechas}{$c.fechas}. {/if}Apúntate al boletín y entérate antes que nadie →</span>
</a>
{/if}
