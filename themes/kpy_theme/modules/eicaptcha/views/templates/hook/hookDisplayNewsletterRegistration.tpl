{*
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Hennes Hervé <contact@h-hennes.fr>
*  @copyright Hennes Hervé
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  http://www.h-hennes.fr/blog/
*}

{* Kompy · Igual que la plantilla de eicaptcha, pero con la v3 la librería de Google no se carga con la
   página: se pide al tocar el formulario y el token se saca al enviarlo (así tampoco caduca). Ahorra
   ~390 KB y su JavaScript en todas las páginas. Para volver a la de eicaptcha, borrar este fichero. *}
<div class="col-xs-12 pull-right">
    {if $captchaVersion == 2}
    <div class="g-recaptcha" data-sitekey="{$publicKey|escape:'html'}" id="captcha-box-newsletter" data-theme="{$captchatheme}"></div>
    <script src="https://www.google.com/recaptcha/api.js?hl={$captchalang}" async defer></script>
    {else}
        <input type="hidden" id="captcha-box-newsletter" name="g-recaptcha-response"/>
        <script>
            (function () {ldelim}
                var clave = '{$publicKey|escape:'javascript'}';
                var caja = document.getElementById('captcha-box-newsletter');
                var form = caja && caja.form;
                if (!form) {ldelim} return; {rdelim}
                var pedida = false;
                function cargar(listo) {ldelim}
                    if (!pedida) {ldelim}
                        pedida = true;
                        var s = document.createElement('script');
                        s.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(clave);
                        s.async = true;
                        document.head.appendChild(s);
                    {rdelim}
                    var espera = setInterval(function () {ldelim}
                        if (window.grecaptcha && window.grecaptcha.ready) {ldelim}
                            clearInterval(espera);
                            window.grecaptcha.ready(listo);
                        {rdelim}
                    {rdelim}, 50);
                {rdelim}
                form.addEventListener('focusin', function () {ldelim} cargar(function () {ldelim}{rdelim}); {rdelim}, {ldelim} once: true {rdelim});
                // Va antes que el envío del módulo de la newsletter: primero el token y luego se envía
                // como siempre.
                form.addEventListener('submit', function (e) {ldelim}
                    if (form.getAttribute('data-kpy-captcha') === 'ok') {ldelim}
                        form.removeAttribute('data-kpy-captcha');
                        return;
                    {rdelim}
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var boton = e.submitter || null;
                    var hecho = false;
                    function enviar() {ldelim}
                        if (hecho) {ldelim} return; {rdelim}
                        hecho = true;
                        form.setAttribute('data-kpy-captcha', 'ok');
                        if (form.requestSubmit) {ldelim}
                            form.requestSubmit(boton && boton.form === form ? boton : undefined);
                        {rdelim} else {ldelim}
                            form.submit();
                        {rdelim}
                    {rdelim}
                    // Si Google no contesta, se envía igual (el módulo dirá que falta la verificación).
                    setTimeout(enviar, 8000);
                    cargar(function () {ldelim}
                        window.grecaptcha.execute(clave, {ldelim}action: 'contact'{rdelim}).then(function (token) {ldelim}
                            caja.value = token;
                            enviar();
                        {rdelim});
                    {rdelim});
                {rdelim}, true);
            {rdelim})();
        </script>
    {/if}
</div>
