{**
 * El paso «En la landing» del formulario de Kompy · Promociones (el 6, normalmente: su número lo
 * manda kpycoupons en el parámetro `paso` del hook displayKpyCouponsFormulario). Lo pinta
 * kpypromolanding por ese hook y lo guarda por actionKpyCouponsGuardar. Todo es opcional: en blanco,
 * la landing lo decide sola. Usa las clases de la pantalla de Promociones (kpc-*) para que parezca
 * un paso más, y las suyas (kpl6-*) para lo propio.
 *
 * Ojo con la hoja del back office: `.bootstrap label` y `.bootstrap .form-control` pesan más que una
 * clase sola, así que lo propio va siempre bajo `.kpc-admin` (ver el <style> de abajo).
 *}
{assign var=k value=$kpl6}
<section class="kpc-paso kpl6">
  <h3 class="kpc-paso__titulo"><span class="kpc-num">{$k.paso|intval}</span> En la landing de promociones <span class="kpc-opcional">(se hace sola; aquí solo lo que quieras cambiar)</span></h3>
  <input type="hidden" name="kpl_paso6" value="1">

  <div class="kpc-ayuda kpl6-ayuda">
    Al guardar, la landing <b>/promociones</b> hace sola lo suyo con esta promoción: su <b>categoría</b> (bajo «Promociones»), su <b>dirección</b>, el <b>banner</b> con el logo y el saco más vendido sin fondo, los textos y el bloque en la landing de la marca. Cuando la promoción empieza o termina, aparece o desaparece sola.
    {if $k.id && $k.estado}
      <span class="kpl6-estado kpl6-estado--{$k.estado}">{if $k.estado == 'viva'}En la landing ahora{elseif $k.estado == 'programada'}Saldrá cuando empiece{else}Terminada: su dirección sigue contestando{/if}</span>
    {/if}
  </div>

  {* Con «Solo quien llega de» o «En ningún sitio» (paso 4) no va a la landing: lo decide el CSS por el radio, al momento. *}
  <p class="kpc-ayuda kpl6-fuera" data-kpc-solo="cupon segunda_unidad">Con lo elegido en «¿Dónde se ve?» (paso 4), esta promoción <b>no va a la landing</b>: ni categoría, ni banner, ni bloque en la landing de su marca. Lo de este paso se guarda igual y vale cuando vuelva a «En toda la tienda».</p>

  {if $k.id && ($k.logo || $k.imagenes)}
    <div class="kpl6-banner">
      <span class="kpl6-banner__t">Así sale hoy su banner</span>
      <div class="kpl6-banner__img">
        {if $k.logo}<img class="kpl6-banner__logo" src="{$k.logo|escape:'html':'UTF-8'}" alt="">{/if}
        {foreach $k.imagenes as $im}<img class="kpl6-banner__saco" src="{$im|escape:'html':'UTF-8'}" alt="">{/foreach}
      </div>
      <div class="kpl6-banner__txt">
        <strong>{if $k.titulo}{$k.titulo|escape:'html':'UTF-8'}{else}{$k.titulo_auto|escape:'html':'UTF-8'}{/if}</strong>
        <span>{if $k.texto}{$k.texto|escape:'html':'UTF-8'}{else}{$k.texto_auto|escape:'html':'UTF-8'}{/if}</span>
        {if $k.url}<a href="{$k.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{$k.url|escape:'html':'UTF-8'}</a>{/if}
      </div>
    </div>
  {/if}

  <div class="kpc-checks">
    <label class="kpc-check">
      <input type="checkbox" name="kpl_listar" value="1" {if $k.listar}checked{/if}>
      <span><strong>Sale en /promociones</strong> — en la portada, en su tipo y en la landing de su marca. Sin marcar, la promoción sigue funcionando (precio, cupón, etiquetas) pero la landing no la enseña; su dirección sí existe.</span>
    </label>
  </div>

  <div class="kpc-fila">
    <div class="kpc-campo kpc-campo--ancho">
      <label for="kpl-clave">Dirección <span class="kpc-opcional">(opcional)</span></label>
      <div class="input-group">
        <span class="input-group-addon kpl6-base">{$k.base|escape:'html':'UTF-8'}</span>
        <input type="text" class="form-control" id="kpl-clave" name="kpl_clave" value="{$k.clave_manual|escape:'html':'UTF-8'}" maxlength="120" placeholder="{if $k.clave}{$k.clave|escape:'html':'UTF-8'}{else}se pone sola: marca-tipo-mascota{/if}" pattern="[a-z0-9]+(-[a-z0-9]+)*">
      </div>
      <small class="kpc-nota">Vacío: <code>marca-tipo[-mascota]</code>, como <code>royal-canin-special-price-perro</code>. Escríbela solo para una campaña concreta (<code>boske-navidad</code>). Minúsculas, números y guiones. La dirección se reutiliza mes a mes, aunque cambie la gama.</small>
    </div>
    <div class="kpc-campo kpc-campo--pct">
      <label for="kpl-orden">Orden <span class="kpc-opcional">(opcional)</span></label>
      <input type="number" class="form-control" id="kpl-orden" name="kpl_orden" value="{if $k.orden}{$k.orden|intval}{/if}" min="0" max="999" placeholder="0">
      <small class="kpc-nota">En la landing. Vacío: por rebaja. Mayor = antes.</small>
    </div>
  </div>

  <div class="kpc-fila">
    <div class="kpc-campo kpc-campo--ancho">
      <label for="kpl-titulo">Título del banner <span class="kpc-opcional">(opcional)</span></label>
      <input type="text" class="form-control" id="kpl-titulo" name="kpl_titulo" value="{$k.titulo|escape:'html':'UTF-8'}" maxlength="160" placeholder="{if $k.titulo_auto}{$k.titulo_auto|escape:'html':'UTF-8'}{else}Se escribe solo: «Special Price: −10 % en toda la gama Boske»{/if}">
    </div>
    <div class="kpc-campo kpc-campo--ancho">
      <label for="kpl-texto">Texto del banner <span class="kpc-opcional">(opcional)</span></label>
      <input type="text" class="form-control" id="kpl-texto" name="kpl_texto" value="{$k.texto|escape:'html':'UTF-8'}" maxlength="255" placeholder="{if $k.texto_auto}{$k.texto_auto|escape:'html':'UTF-8'}{else}Se escribe solo: «El precio ya va rebajado en cada ficha…»{/if}">
    </div>
  </div>

  {* La foto: automática, un producto o una de la biblioteca. Elegir una de la biblioteca (o escribir un id) marca su opción sola (JS de abajo). *}
  <p class="kpc-pregunta">Foto del banner</p>
  <div class="kpc-radios kpl6-foto">
    <label class="kpc-check"><input type="radio" name="kpl_imagen_modo" value="auto" {if $k.imagen_modo == 'auto'}checked{/if}>
      <span><strong>Automática</strong> — el saco del producto más vendido de la promoción, sin fondo (perro y gato apilados si es de los dos; el saco Jirafa en kilos gratis). Nunca una foto de transición. Si en la biblioteca hay un saco subido a mano para esta marca (y su gama, si la lleva), ese.</span></label>
    <label class="kpc-check"><input type="radio" name="kpl_imagen_modo" value="producto" {if $k.imagen_modo == 'producto'}checked{/if}>
      <span><strong>Un producto concreto</strong> — el id de su ficha: <input type="number" class="form-control kpl6-inline" name="kpl_imagen_producto" value="{if $k.imagen_modo == 'producto'}{$k.imagen_id|intval}{/if}" min="1" placeholder="7214" aria-label="Id del producto"> (el número que sale en Catálogo → Productos). Se recorta sola.</span></label>
    <label class="kpc-check"><input type="radio" name="kpl_imagen_modo" value="biblioteca" {if $k.imagen_modo == 'biblioteca'}checked{/if}>
      <span><strong>Una imagen subida a mano</strong> — de la biblioteca de la landing{if $k.subidas}: elige cuál{else} (no hay ninguna: se suben en <a href="{$k.url_pantalla|escape:'html':'UTF-8'}" target="_blank" rel="noopener">Kompy · Landing de promociones</a>){/if}.</span></label>
    {if $k.subidas}
      <div class="kpl6-subidas" role="radiogroup" aria-label="Imagen de la biblioteca">
        {foreach $k.subidas as $s}
          <label class="kpl6-subida" title="{$s.etiqueta|escape:'html':'UTF-8'}{if $s.nota} · {$s.nota|escape:'html':'UTF-8'}{/if}">
            <input type="radio" name="kpl_imagen_biblioteca" value="{$s.id|intval}" {if $k.imagen_modo == 'biblioteca' && $k.imagen_id == $s.id}checked{/if}>
            <span class="kpl6-subida__img"><img src="{$s.url|escape:'html':'UTF-8'}" alt="" loading="lazy"></span>
            <span class="kpl6-subida__txt">{$s.etiqueta|escape:'html':'UTF-8'}</span>
            {if $s.suya}<span class="kpl6-subida__suya">de esta marca</span>{/if}
          </label>
        {/foreach}
      </div>
    {/if}
  </div>

  {if $k.campanas}
    <p class="kpc-pregunta">Campañas <small>— Black Friday y las demás: en cuáles entra esta promoción</small></p>
    <div class="kpl6-campanas">
      {foreach $k.campanas as $c}
        <label class="kpc-pastilla"><input type="checkbox" name="kpl_campanas[]" value="{$c.clave|escape:'html':'UTF-8'}" {if $c.marcada}checked{/if}><span>{$c.nombre|escape:'html':'UTF-8'}{if $c.fechas} · {$c.fechas|lower|escape:'html':'UTF-8'}{/if}</span></label>
      {/foreach}
    </div>
    <small class="kpc-nota">Mientras ninguna promoción tenga una campaña marcada, <b>entran todas las vivas</b>. En cuanto marcas alguna, en esa campaña solo entran las marcadas. Las campañas se crean en <a href="{$k.url_pantalla|escape:'html':'UTF-8'}" target="_blank" rel="noopener">Kompy · Landing de promociones</a>.</small>
  {/if}
</section>
<style>{literal}
.kpl6-estado{display:inline-block;margin-left:8px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#EEF1F3;color:#44505C;vertical-align:middle}
.kpl6-estado--viva{background:#EAF6EC;color:#2F6B34}.kpl6-estado--programada{background:#E6F1FA;color:#1D5C8C}
.kpl6-banner{display:grid;grid-template-columns:auto 1fr;gap:6px 16px;align-items:center;margin:0 0 14px;padding:12px 14px;border:1px solid #DDE2E6;border-radius:12px;background:#FFF5D8}
.kpl6-banner__t{grid-column:1/-1;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6B5F43}
.kpl6-banner__img{display:flex;align-items:flex-end;gap:10px}
.kpl6-banner__logo{height:28px;width:auto;background:#fff;border-radius:6px;padding:3px 8px}
.kpl6-banner__saco{height:70px;width:auto}
.kpl6-banner__txt{display:flex;flex-direction:column;gap:2px;min-width:0}
.kpl6-banner__txt strong{color:#25303B}.kpl6-banner__txt span{color:#5C6873;font-size:12.5px}.kpl6-banner__txt a{font-size:12px;word-break:break-all}
.kpl6-base{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;color:#5C6873}
.kpc-admin .kpc-check .kpl6-inline{display:inline-block;width:96px;height:28px;margin:0 4px;padding:2px 8px;vertical-align:middle}
.kpl6-foto .kpc-check{margin-bottom:10px}
/* La biblioteca: tarjetas con la foto sobre crema (como en la landing), el texto debajo y la elegida marcada */
.kpc-admin .kpl6-subidas{display:flex;flex-wrap:wrap;gap:10px;margin:-2px 0 12px 24px}
.kpc-admin .kpl6-subida{position:relative;display:flex;flex-direction:column;gap:6px;width:136px;margin:0;padding:8px;border:2px solid #DDE2E6;border-radius:12px;background:#fff;cursor:pointer;font-weight:400;transition:border-color .15s,background-color .15s}
.kpc-admin .kpl6-subida input{position:absolute;opacity:0;pointer-events:none;margin:0}
.kpc-admin .kpl6-subida__img{display:flex;align-items:center;justify-content:center;height:88px;border-radius:8px;background:#FFF5D8}
.kpc-admin .kpl6-subida__img img{display:block;max-height:76px;max-width:104px;width:auto;height:auto}
.kpc-admin .kpl6-subida__txt{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.6em;color:#44505C;font-size:11.5px;line-height:1.3;text-align:center}
.kpc-admin .kpl6-subida__suya{align-self:center;padding:1px 8px;border-radius:999px;background:#EAF6EC;color:#2F6B34;font-size:10.5px;font-weight:700}
.kpc-admin .kpl6-subida:hover{border-color:#B9C2CA}
.kpc-admin .kpl6-subida:has(input:checked){border-color:#25303B;background:#F6F8F9}
.kpc-admin .kpl6-subida:has(input:checked)::after{content:"✓";position:absolute;top:6px;right:6px;width:22px;height:22px;border-radius:999px;background:#25303B;color:#fff;font-size:13px;font-weight:700;line-height:22px;text-align:center}
.kpc-admin .kpl6-subida:has(input:focus-visible){outline:2px solid #25303B;outline-offset:2px}
.kpl6-campanas{display:flex;flex-wrap:wrap;gap:2px 0;margin:0 0 6px}
/* El aviso de «no va a la landing», solo con «solo quien llega de» o «en ningún sitio» (y «en ningún sitio» + «a todo el mundo» sí va, solo en un cupón: en la 2ª unidad el radio escondido «entra» no cuenta) */
.kpc-admin .kpl6-fuera{background:#FFF5D8;color:#6B5F43}
.kpc-form:has(input[name="ver"][value="todos"]:checked) .kpl6-fuera,
.kpc-form:has(input[name="tipo"][value="cupon"]:checked):has(input[name="ver"][value="nadie"]:checked):has(input[name="entra"][value="todos"]:checked) .kpl6-fuera{display:none}
{/literal}</style>
<script>{literal}
(function () {
  // Elegir una imagen de la biblioteca, o escribir un id, marca su opción de «Foto del banner» sola.
  var form = document.getElementById('kpc-form') || document;
  function marcar(valor) {
    var radio = form.querySelector('input[name="kpl_imagen_modo"][value="' + valor + '"]');
    if (radio && !radio.checked) {
      radio.checked = true;
      radio.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }
  Array.prototype.forEach.call(form.querySelectorAll('input[name="kpl_imagen_biblioteca"]'), function (r) {
    r.addEventListener('change', function () { marcar('biblioteca'); });
  });
  var id = form.querySelector('input[name="kpl_imagen_producto"]');
  if (id) {
    id.addEventListener('input', function () { if (id.value !== '') { marcar('producto'); } });
  }
})();
{/literal}</script>
