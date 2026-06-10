{extends file='page.tpl'}

{block name='breadcrumb'}{/block}

{block name='notifications'}{/block}

{block name='content_columns'}
    <main>
      <section class="hero">
        <div class="container hero-grid">
          <div class="hero-content">
            <span class="pill-label">100% Garantía de Satisfacción</span>
            <h1>Alarga la vida de tu perro hasta 3 años con alimentación 100% natural</h1>
            <p>Digestiones estables en 10 días. Pelaje espectacular en 3 semanas. Articulaciones fuertes para paseos largos. Y la tranquilidad de saber que haces lo correcto por tu ser querido.</p>
            <div class="cta-group">
              <a href="{$boske_category_url}" class="btn btn-primary">Prueba sin riesgo</a>
            </div>
          </div>
          <div class="hero-visual">
            <div class="soft-glow"></div>
            <img src="{$module_img}imagen1-comp.webp" alt="Saco Boske" class="hero-img">
          </div>
        </div>
      </section>

      <section id="beneficios" class="section bg-soft">
        <div class="container">
          <div class="ingredients-card">
            <div class="ing-text">
              <h2>La mejor decisión que puedes tomar para tu mejor amigo</h2>

              <p class="mb-2">Cuando el veterinario te dice "todo está bien", pero tú ves las <strong>heces blandas cada mañana</strong>. Cuando tu casa está llena de pelos y sabes que algo no va del todo bien. Cuando le miras y piensas "ojalá supiera que lo estoy haciendo bien".</p>

              <p class="mb-2">Boske no es solo cambiar de pienso.</p>

              <p class="mb-4">Es la <strong>tranquilidad</strong> de verle comer con ganas, correr <strong>sin dolores</strong> y despertar <strong>sin sustos</strong> en la alfombra. Es ahorrarte meses de prueba y error. Y saber que, pase lo que pase, tienes <strong>garantía real</strong> si algo no va bien.</p>

              <ul class="check-list">
                <li>
                  <span class="material-icons icon-list">check</span>
                  <span>Duermes <strong>tranquilo</strong> sabiendo que come lo que su cuerpo necesita.</span>
                </li>
                <li>
                  <span class="material-icons icon-list">check</span>
                  <span>Paseos <strong>sin cojear</strong> gracias a articulaciones más fuertes.</span>
                </li>
                <li>
                  <span class="material-icons icon-list">check</span>
                  <span>Tu casa <strong>sin pelos</strong> por todas partes en pocas semanas.</span>
                </li>
              </ul>

            </div>
            <div class="ing-img">
              <img src="{$module_img}imagen2-comp.webp" alt="Hombre con perro y saco Boske">
            </div>
          </div>

          <div class="tech-icons-grid">
            <div class="tech-item">
              <div class="icon-circle bg-red">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 588.82 588.82" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g id="Layer_1_47_"> <path d="M529.952,44.206c-65.024-61.2-167.024-58.65-228.225,6.375c-52.275,54.825-118.575,201.45-98.175,281.775l-90.525,95.625 c-2.55-2.55-5.1-6.375-7.65-8.925c-22.95-21.675-59.925-20.4-81.6,2.55c-21.675,22.95-20.4,59.925,2.55,81.601 c16.575,15.3,38.25,19.125,58.65,12.75c-5.1,20.399,0,42.074,15.3,57.375c22.95,21.675,59.925,20.399,81.6-2.551 c21.675-22.949,20.4-59.925-2.55-81.6c-2.55-2.55-5.1-5.1-8.925-6.375l90.525-95.625c81.6,15.3,224.399-59.925,275.399-114.75 C597.527,207.406,594.978,105.406,529.952,44.206z"></path> </g> </g> </g></svg>
              </div>
              <h4>Proteína de Alto Valor Biológico</h4>
              <span class="tech-subtitle">Músculo fuerte, órganos vitales protegidos</span>
              <p class="tech-copy">Carne fresca como primer ingrediente. No harinas ni subproductos. Proteína que su cuerpo aprovecha de verdad.</p>
            </div>

            <div class="tech-item">
              <div class="icon-circle bg-green">
                <svg viewBox="0 -64 640 640" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M528 336c-48.6 0-88 39.4-88 88s39.4 88 88 88 88-39.4 88-88-39.4-88-88-88zm0 112c-13.23 0-24-10.77-24-24s10.77-24 24-24 24 10.77 24 24-10.77 24-24 24zm80-288h-64v-40.2c0-14.12 4.7-27.76 13.15-38.84 4.42-5.8 3.55-14.06-1.32-19.49L534.2 37.3c-6.66-7.45-18.32-6.92-24.7.78C490.58 60.9 480 89.81 480 119.8V160H377.67L321.58 29.14A47.914 47.914 0 0 0 277.45 0H144c-26.47 0-48 21.53-48 48v146.52c-8.63-6.73-20.96-6.46-28.89 1.47L36 227.1c-8.59 8.59-8.59 22.52 0 31.11l5.06 5.06c-4.99 9.26-8.96 18.82-11.91 28.72H22c-12.15 0-22 9.85-22 22v44c0 12.15 9.85 22 22 22h7.14c2.96 9.91 6.92 19.46 11.91 28.73l-5.06 5.06c-8.59 8.59-8.59 22.52 0 31.11L67.1 476c8.59 8.59 22.52 8.59 31.11 0l5.06-5.06c9.26 4.99 18.82 8.96 28.72 11.91V490c0 12.15 9.85 22 22 22h44c12.15 0 22-9.85 22-22v-7.14c9.9-2.95 19.46-6.92 28.72-11.91l5.06 5.06c8.59 8.59 22.52 8.59 31.11 0l31.11-31.11c8.59-8.59 8.59-22.52 0-31.11l-5.06-5.06c4.99-9.26 8.96-18.82 11.91-28.72H330c12.15 0 22-9.85 22-22v-6h80.54c21.91-28.99 56.32-48 95.46-48 18.64 0 36.07 4.61 51.8 12.2l50.82-50.82c6-6 9.37-14.14 9.37-22.63V192c.01-17.67-14.32-32-31.99-32zM176 416c-44.18 0-80-35.82-80-80s35.82-80 80-80 80 35.82 80 80-35.82 80-80 80zm22-256h-38V64h106.89l41.15 96H198z"></path></g></svg>
              </div>
              <h4>Ingredientes KM.0</h4>
              <span class="tech-subtitle">De la granja a su plato</span>
              <p class="tech-copy">Proveedores locales, trazabilidad total. Sin viajes de miles de kilómetros. Frescura que se nota en cada bocado.</p>
            </div>

            <div class="tech-item">
              <div class="icon-circle bg-orange">
                <svg  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 550.692 550.692" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M77.296,277.792c0-63,30-118.7,75.9-155.4c9.8-6.7,17.1,0,14.1,4.9c-31.8,74.7-8.6,148.1,45.3,162.8 c-15.9-37.9-23.3-80.8-18.4-124.8c7.3-64.9,39.2-120.6,85.1-159.7c4.3-3.7,8.6-6.1,11.6-5.5l0,0c3.101,0.6,4.9,2.4,3.7,6.7 c-3.7,12.9-6.1,26.3-7.3,39.8c-6.1,54.5,6.1,105.9,31.2,149.9c37.899,0,53.899-44.1,58.1-74.7c0-7.3,5.5-12.2,16.5-3.7 c49,36.1,81.4,93.6,81.4,159.101c0,109.5-88.7,198.3-198.9,198.3C166.096,476.092,77.296,387.292,77.296,277.792z M481.196,507.292 h-411.2c-12.2,0-22,9.8-22,22s9.8,21.4,22,21.4h411.3c12.2,0,21.4-9.801,21.4-21.4 C502.696,517.092,493.496,507.292,481.196,507.292z"></path> </g> </g></svg>
              </div>
              <h4>Cocinado a Fuego Lento</h4>
              <span class="tech-subtitle">Nutrientes intactos, sabor natural</span>
              <p class="tech-copy">Sin altas temperaturas que destruyen vitaminas. Proceso artesanal que respeta cada ingrediente para máxima digestibilidad.</p>
            </div>

            <div class="tech-item">
              <div class="icon-circle bg-purple">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M3.37752 5.08241C3 5.62028 3 7.21907 3 10.4167V11.9914C3 17.6294 7.23896 20.3655 9.89856 21.5273C10.62 21.8424 10.9807 22 12 22C13.0193 22 13.38 21.8424 14.1014 21.5273C16.761 20.3655 21 17.6294 21 11.9914V10.4167C21 7.21907 21 5.62028 20.6225 5.08241C20.245 4.54454 18.7417 4.02996 15.7351 3.00079L15.1623 2.80472C13.595 2.26824 12.8114 2 12 2C11.1886 2 10.405 2.26824 8.83772 2.80472L8.26491 3.00079C5.25832 4.02996 3.75503 4.54454 3.37752 5.08241ZM15.0595 10.4995C15.3353 10.1905 15.3085 9.71642 14.9995 9.44055C14.6905 9.16467 14.2164 9.19151 13.9405 9.50049L10.9286 12.8739L10.0595 11.9005C9.78358 11.5915 9.30947 11.5647 9.00049 11.8405C8.69151 12.1164 8.66467 12.5905 8.94055 12.8995L10.3691 14.4995C10.5114 14.6589 10.7149 14.75 10.9286 14.75C11.1422 14.75 11.3457 14.6589 11.488 14.4995L15.0595 10.4995Z" fill="#ffffff"></path> </g></svg>
              </div>
              <h4>100% Garantía Satisfacción</h4>
              <span class="tech-subtitle">Si no funciona, no pagas</span>
              <p class="tech-copy">Prueba durante 30 días. Si no ves resultados o no le gusta, te devolvemos todo tu dinero. Así de simple.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="container">
          <div class="section-header text-center">
            <h2>Lo que cambia cuando cambias a Boske</h2>
          </div>
          <div class="problems-grid">
            <div class="problem-card">
              <div class="icon-main">
                <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M451.36 369.14C468.66 355.99 480 335.41 480 312c0-39.77-32.24-72-72-72h-14.07c13.42-11.73 22.07-28.78 22.07-48 0-35.35-28.65-64-64-64h-5.88c3.57-10.05 5.88-20.72 5.88-32 0-53.02-42.98-96-96-96-5.17 0-10.15.74-15.11 1.52C250.31 14.64 256 30.62 256 48c0 44.18-35.82 80-80 80h-16c-35.35 0-64 28.65-64 64 0 19.22 8.65 36.27 22.07 48H104c-39.76 0-72 32.23-72 72 0 23.41 11.34 43.99 28.64 57.14C26.31 374.62 0 404.12 0 440c0 39.76 32.24 72 72 72h368c39.76 0 72-32.24 72-72 0-35.88-26.31-65.38-60.64-70.86z"></path></g></svg>
              </div>
              <h3>1. Se acabó limpiar desastres</h3>
              <p>Heces firmes y pequeñas en <strong>7-10 días</strong>. Sin diarreas de madrugada. Digestión estable para que ambos durmáis mejor.</p>
            </div>
            <div class="problem-card">
              <div class="icon-main">
                <svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12.52.55l-5,5h0L.55,12.51l3,3,12-12Zm-4,6,4-4,1,1-4,4.05ZM2.77,3.18A3.85,3.85,0,0,1,5.32,5.73h0A3.85,3.85,0,0,1,7.87,3.18h0A3.82,3.82,0,0,1,5.32.64h0A3.82,3.82,0,0,1,2.77,3.18ZM8.5,2.55h0A2,2,0,0,1,9.78,1.27h0A1.92,1.92,0,0,1,8.5,0h0A1.88,1.88,0,0,1,7.23,1.27h0A1.92,1.92,0,0,1,8.5,2.55Zm-6.36,0h0A1.92,1.92,0,0,1,3.41,1.27h0A1.88,1.88,0,0,1,2.14,0h0A1.92,1.92,0,0,1,.86,1.27h0A2,2,0,0,1,2.14,2.55ZM14.73,6.22h0a1.94,1.94,0,0,1-1.28,1.27h0a1.94,1.94,0,0,1,1.28,1.27h0A1.9,1.9,0,0,1,16,7.49h0A1.9,1.9,0,0,1,14.73,6.22Z"></path> </g></svg>
              </div>
              <h3>2. Un pelaje que llama la atención</h3>
              <p>Menos pelos en el sofá. <strong>Brillo visible en 3 semanas</strong> que hace que te paren por la calle. Orgullo de cuidador.</p>
            </div>
            <div class="problem-card">
              <div class="icon-main">
                <svg viewBox="-48 0 512 512" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M272 96c26.51 0 48-21.49 48-48S298.51 0 272 0s-48 21.49-48 48 21.49 48 48 48zM113.69 317.47l-14.8 34.52H32c-17.67 0-32 14.33-32 32s14.33 32 32 32h77.45c19.25 0 36.58-11.44 44.11-29.09l8.79-20.52-10.67-6.3c-17.32-10.23-30.06-25.37-37.99-42.61zM384 223.99h-44.03l-26.06-53.25c-12.5-25.55-35.45-44.23-61.78-50.94l-71.08-21.14c-28.3-6.8-57.77-.55-80.84 17.14l-39.67 30.41c-14.03 10.75-16.69 30.83-5.92 44.86s30.84 16.66 44.86 5.92l39.69-30.41c7.67-5.89 17.44-8 25.27-6.14l14.7 4.37-37.46 87.39c-12.62 29.48-1.31 64.01 26.3 80.31l84.98 50.17-27.47 87.73c-5.28 16.86 4.11 34.81 20.97 40.09 3.19 1 6.41 1.48 9.58 1.48 13.61 0 26.23-8.77 30.52-22.45l31.64-101.06c5.91-20.77-2.89-43.08-21.64-54.39l-61.24-36.14 31.31-78.28 20.27 41.43c8 16.34 24.92 26.89 43.11 26.89H384c17.67 0 32-14.33 32-32s-14.33-31.99-32-31.99z"></path></g></svg>
              </div>
              <h3>3. Articulaciones todoterreno</h3>
              <p>Sube escaleras sin quejarse y corre como antes. <strong>Envejecer sin dolores</strong> es el mejor regalo que le puedes dar.</p>
            </div>
          </div>
        </div>
      </section>

      <section id="promociones" class="section pt-0">
        <div class="container">
          <div class="promo-wrapper">
            <div class="promo-banner main-promo">
              <div class="promo-content">
                <span class="badge-promo">Oferta Marzo</span>
                <h3>+3KG GRATIS</h3>
                <p>En formatos seleccionados. Porque queremos que lo pruebes de verdad, sin prisa.</p>
                <a href="{$boske_category_url}" class="btn btn-primary">Conseguir mi oferta</a>
              </div>
              <img src="{$module_img}boske-regalo-comp.webp" class="promo-img" alt="Regalo">
            </div>

            <div class="promo-banner secondary-promo">
              <div class="promo-gift-icon">
                <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M2,19H9V10H2Zm9,0h7V10H11ZM13,6a3,3,0,1,0-3-3A3,3,0,1,0,7,6H1V9H9V6h2V9h8V6ZM8.5,4.5H7A1.5,1.5,0,1,1,8.5,3Zm3-1.5A1.5,1.5,0,1,1,13,4.5H11.5Z"></path> </g> </g></svg>
              </div>
              <h3>Regalo Sorpresa</h3>
              <p>En formatos seleccionados</p>
              <small>Ver condiciones <i class="material-icons">chevron_right</i></small>
            </div>
          </div>
        </div>
      </section>

      <section id="gamas" class="section products-section">
        <div class="container">
          <div class="section-header text-center">
            <h2>Encuentra la receta perfecta</h2>
            <p>Opciones reales para cada necesidad, sin complicaciones.</p>
          </div>

          <div class="products-grid">
            <article class="product-item">
              <div class="product-thumb">
                <img src="{$module_img}boskelow-comp.webp" alt="Saco">
              </div>
              <div class="product-details">
                <h3>Low Grain Perro</h3>
                <p>Arroz integral para estómagos sensibles.</p>
                <a href="{$boske_category_low_grain}" class="btn-product">Descubre la gama</a>
              </div>
            </article>

            <article class="product-item">
              <div class="product-thumb">
                <img src="{$module_img}boskecat-comp.webp" alt="Saco">
              </div>
              <div class="product-details">
                <h3>Low Grain Gato</h3>
                <p>Suave con su digestión, irresistible.</p>
                <a href="{$boske_category_low_grain_cat}" class="btn-product">Descubre la gama</a>
              </div>
            </article>

            <article class="product-item featured-item">
              <span class="badge-top">Top Ventas</span>
              <div class="product-thumb">
                <img src="{$module_img}boskegf-low.webp" alt="Saco">
              </div>
              <div class="product-details">
                <h3>Grain Free</h3>
                <p>Instinto salvaje. 0% Cereales.</p>
                <a href="{$boske_category_grain_free}" class="btn-product btn-product-primary">Descubre la gama</a>
              </div>
            </article>

            <article class="product-item">
              <div class="product-thumb">
                <img src="{$module_img}boskeclinical-comp.webp" alt="Saco">
              </div>
              <div class="product-details">
                <h3>Clinical Diet Seco</h3>
                <p>Cuando necesita cuidado extra.</p>
                <a href="{$boske_category_clinical}" class="btn-product">Descubre la gama</a>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="section transition-section">
        <div class="container">
          <div class="section-header text-center">
            <h2 style="margin-bottom: 10px;">Transición a comida natural</h2>
            <p style="max-width: 700px; margin: 0 auto;">Tu peludo tiene un sistema digestivo sensible y necesita una transición para aprender a asimilar los nutrientes de la carne fresca.</p>
          </div>

          <div class="transition-grid">
            <div class="transition-card">
              <div class="pie-chart p25"></div>
              <h4>Días 1 y 2</h4>
              <p><strong>25%</strong> Boske</p>
              <p class="small-text">75% Pienso actual</p>
            </div>
            <div class="transition-card">
              <div class="pie-chart p50"></div>
              <h4>Días 3 y 4</h4>
              <p><strong>50%</strong> Boske</p>
              <p class="small-text">50% Pienso actual</p>
            </div>
            <div class="transition-card">
              <div class="pie-chart p75"></div>
              <h4>Días 5 y 6</h4>
              <p><strong>75%</strong> Boske</p>
              <p class="small-text">25% Pienso actual</p>
            </div>
            <div class="transition-card">
              <div class="pie-chart p100">
                <i class="material-icons" style="font-size: 2rem">check</i>
              </div>
              <h4>Día 7</h4>
              <p><strong>100%</strong> Boske</p>
              <p class="small-text">¡Transición completa!</p>
            </div>
          </div>
        </div>
      </section>

      <section class="section bg-soft">
        <div class="container">
          <h2 class="text-center mb-4">Dueños que ya duermen tranquilos</h2>
          <div class="reviews-slider">
            {foreach from=$comments item=comment}
              <div class="review-card">
                <div class="stars">★★★★★</div>
                <p>"{$comment.content}"</p>
                <strong>{$comment.customer_name}</strong>
              </div>
            {/foreach}
          </div>

          <div class="mt-4 text-center">
            <a href="#" class="btn btn-outline-secondary">{l s='View all comments' d='Modules.Kpymanufacturer.Shop'} <span><i class="material-icons">chevron_right</i></span></a>
          </div>
        </div>
      </section>

      <section id="faq" class="section faq-section text-center">
        <div class="container narrow">
          <h2>Pruébalo. Si no funciona, no pagas.</h2>
          <p>No más dudas de "¿estaré haciendo lo correcto?". Solo la tranquilidad de saber que le das lo mejor.</p>
          <a href="{$boske_category_url}" class="btn btn-primary">Quiero probarlo sin riesgo</a>

          <div class="accordion mt-60 text-left">

            <details class="faq-item" name="item">
              <summary>
                <span class="question-text">¿Y si le cambio el pienso y le sienta mal?</span>
                <i class="material-icons toggle-icon">keyboard_arrow_down</i>
              </summary>
              <div class="faq-content">
                <p>Es la pregunta que más escucho. Y te entiendo perfectamente. Hemos visto casos de clientes que han pasado noches horribles limpiando diarreas después de cambios bruscos. Es normal que tengas miedo, sobre todo si ya te ha pasado antes.</p>
                <p>La verdad es que <strong>el 95% de los problemas vienen de hacer el cambio mal, demasiado rápido</strong>. Por eso te damos una <strong>guía paso a paso de 7 días</strong> donde mezclas progresivamente su pienso actual con Boske. Así su estómago se adapta sin sustos.</p>
                <p>Boske está formulado específicamente para estómagos sensibles. Llevamos años ayudando a perros que "no toleraban nada" y ahora tienen digestiones perfectas.</p>
                <p>Pero mira, si después de hacer la transición correcta ves que no funciona, <strong>te devolvemos tu dinero sin preguntas</strong>. Y si durante el proceso tienes cualquier duda, puedes escribirnos directamente. No te vamos a dejar solo con esto.</p>
                <p>El único riesgo real es seguir con las cacas blandas cada mañana y preguntarte si podrías haber hecho algo más.</p>
              </div>
            </details>

            <details class="faq-item" name="item">
              <summary>
                <span class="question-text">Mi veterinario me recomienda otra marca. ¿Puedo cambiar igualmente?</span>
                <i class="material-icons toggle-icon">keyboard_arrow_down</i>
              </summary>
              <div class="faq-content">
                <p>Claro que sí. Y además, está muy bien que confíes en tu veterinario. Lo que pasa es que muchos veterinarios recomiendan siempre las mismas 2-3 marcas comerciales, no porque sean mejores, sino porque las conocen de toda la vida o porque les visitan los comerciales cada mes. No suelen ser expertos en nutrición animal, es algo muy específico.</p>
                <p>Nosotros tenemos veterinarios expertos en nutrición en el equipo. De hecho, <strong>si tu veterinario quiere ver la ficha técnica de Boske, se la mandamos encantados</strong>. Muchos, cuando ven los ingredientes y la formulación, nos dan el visto bueno.</p>
                <p>Mira, te voy a ser sincera: si tu veterinario te ha recetado una dieta específica por algún problema de salud (renal, hepático, alergias graves), respétala. Eso es lo primero. Pero si solo te dijo "dale esta marca porque es buena", tienes todo el derecho a buscar alternativas de mayor calidad. <strong>Y siempre puedes enseñarle la etiqueta de Boske para que lo valore. Los ingredientes hablan solos.</strong></p>
              </div>
            </details>

            <details class="faq-item" name="item">
              <summary>
                <span class="question-text">¿Por qué Boske cuesta menos que otras marcas "premium"? ¿Será de peor calidad?</span>
                <i class="material-icons toggle-icon">keyboard_arrow_down</i>
              </summary>
              <div class="faq-content">
                <p>Buena pregunta. A mí también me pasaría por la cabeza. La realidad es que <strong>tu dinero va a la receta, no a anuncios de televisión ni patrocinios millonarios</strong>. Las marcas caras no pagan por mejores ingredientes, pagan por marketing.</p>
                <p>Te pongo un ejemplo: un saco de Royal Canin o Hills cuesta 70-80€ y la mitad de su presupuesto se va en publicidad, comerciales y comisiones. Nosotros invertimos ese dinero en proteína de alto valor biológico, cocción lenta y proveedores locales. Pagas por lo que come tu perro, no por el logo del saco.</p>
                <p>Ahora, si quieres estar seguro, haz esto: <strong>compara etiquetas</strong>. Pon la de tu pienso actual al lado de la de Boske y mira el primer ingrediente, el porcentaje de proteína, si lleva subproductos o harinas. Ahí verás la diferencia.</p>
                <p>Y si después de probarlo piensas "esto no vale lo que cuesta", te devolvemos el dinero. Pero hasta ahora, el 92,5% de los clientes que prueban Boske no vuelven a su marca anterior. No es más barato porque sea peor. Es más honesto.</p>
              </div>
            </details>

            <details class="faq-item" name="item">
              <summary>
                <span class="question-text">¿Low Grain o Grain Free? No sé cuál elegir.</span>
                <i class="material-icons toggle-icon">keyboard_arrow_down</i>
              </summary>
              <div class="faq-content">
                <p>Tranquilo, esto lo resolvemos en 30 segundos.</p>
                <p><strong>Low Grain</strong> lleva arroz integral. Es perfecto para la mayoría de perros, especialmente si tienen el estómago sensible. El arroz integral ayuda a estabilizar la digestión, aporta energía sostenida y es súper digestible.</p>
                <p><strong>Grain Free</strong> es sin cereales. Ideal si tu perro tiene intolerancia real al grano (confirmada por un veterinario, no por San Google) o si prefieres una dieta más cercana a lo que comeran en estado salvaje.</p>
                <p>Te voy a ser clara: <strong>el 90% de los perros funcionan perfectamente con Low Grain</strong>. A no ser que tu perro tenga una intolerancia diagnosticada o que ya hayas probado varios piensos con cereales y ninguno le haya ido bien, empieza por Low Grain. Si después ves que no termina de ir fino, cambiamos a Grain Free. Pero no te compliques desde el principio. ¿Tienes dudas? Escríbenos y te ayudamos a elegir según su caso específico.</p>
              </div>
            </details>

            <details class="faq-item" name="item">
              <summary>
                <span class="question-text">Ya intenté cambiar antes y fue un desastre. No quiero volver a pasar por eso.</span>
                <i class="material-icons toggle-icon">keyboard_arrow_down</i>
              </summary>
              <div class="faq-content">
                <p>Lo entiendo perfectamente. Y me da rabia, porque seguramente nadie te explicó cómo hacerlo bien. Mira, hace unos meses nos escribió una clienta, Ana, que había intentado cambiar a su golden de pienso tres veces. Las tres acabó con diarreas, vómitos y una factura del veterinario de 400€. Estaba aterrada.</p>
                <p>Le explicamos paso a paso cómo hacer la transición: <strong>7 días mezclando progresivamente, empezando con un 25% de Boske y 75% del antiguo</strong>. A la semana siguiente, 50-50. Y así hasta llegar al 100%. ¿Sabes qué pasó? A los 10 días su perro tenía las heces más firmes que nunca. A las 3 semanas, el pelaje brillaba. Ahora nos escribe cada dos meses para decirnos que no vuelve a su marca anterior "ni loca".</p>
                <p>El problema no era el cambio. Era cómo se hizo el cambio. Te damos una guía detallada, tienes acceso a nuestro equipo veterinario por WhatsApp si surge cualquier duda, y si aun así no funciona, te devolvemos tu dinero. No estás solo en esto. Y esta vez lo vamos a hacer bien.</p>
              </div>
            </details>

            <details class="faq-item" name="item">
              <summary>
                <span class="question-text">Mi perro está bien con su pienso actual. ¿Para qué cambiar?</span>
                <i class="material-icons toggle-icon">keyboard_arrow_down</i>
              </summary>
              <div class="faq-content">
                <p>Buena pregunta. Y te voy a dar una respuesta honesta. Si tu perro hace cacas perfectas todos los días, tiene el pelo brillante, sube escaleras sin quejarse, mantiene su peso ideal y se lo come con ganas... entonces no cambies. <strong>En serio. Si funciona, no lo toques.</strong></p>
                <p>Pero déjame que te pregunte algo: ¿Sus heces son siempre firmes o a veces blandas? ¿Encuentras pelos por toda la casa constantemente? ¿Le cuesta levantarse después de dormir o cojea un poco después del parque? ¿Has notado que últimamente come con menos ganas?</p>
                <p>Si has respondido "sí" a alguna, <strong>"está bien" no es lo mismo que "está en su mejor versión"</strong>. Muchos clientes nos decían lo mismo: "Mi perro está bien". Hasta que probaron Boske y vieron resultados que no esperaban. Uno nos escribió hace poco: "No sabía que las cacas podían ser TAN firmes. Pensaba que era normal que fueran un poco blandas". Mira, <strong>si de verdad está perfecto, no cambies. Pero si en el fondo sabes que podría estar mejor, tienes 30 días con garantía para comprobarlo.</strong> La decisión es tuya. Nosotros solo te damos la opción.</p>
              </div>
            </details>

            <details class="faq-item" name="item">
              <summary>
                <span class="question-text">No conozco Boske. ¿Por qué debería confiar en una marca que no he visto en el veterinario?</span>
                <i class="material-icons toggle-icon">keyboard_arrow_down</i>
              </summary>
              <div class="faq-content">
                <p>Es normal que desconfíes. Hay mucho marketing engañoso en el mundo del pienso. Te voy a contar algo: nosotros no estamos en las clínicas veterinarias porque <strong>no pagamos comisiones a veterinarios por recomendarnos</strong>. Esa es la realidad de muchas marcas "de veterinario". No las recomiendan porque sean mejores, las recomiendan porque hay un acuerdo comercial detrás.</p>
                <p>Nosotros preferimos invertir ese dinero en ingredientes de verdad. Por eso tenemos <strong>trazabilidad total, proveedores locales y cocción artesanal</strong>. Todo lo que ponemos en la etiqueta es real. Nada de trucos. ¿Sabes cuántos clientes nos han probado viniendo de marcas "conocidas"? Miles. Y el <strong>92,5% no ha vuelto a su marca anterior</strong>. No porque se lo digamos nosotros, sino porque ven los resultados en casa.</p>
                <p>Te voy a ser clara: <strong>si no confías, no compres</strong>. No quiero que gastes tu dinero en algo que no te da seguridad. Pero si estás dispuesto a darle una oportunidad, tienes <strong>30 días con garantía 100%</strong>. Si no funciona, te devolvemos hasta el último céntimo. Sin preguntas, sin excusas, sin letra pequeña. Las marcas que no cumplen no se atreven a dar esta garantía. Nosotros sí.</p>
              </div>
            </details>

            <details class="faq-item" name="item">
              <summary>
                <span class="question-text">¿Cuánto tarda en llegar? ¿Y cuándo veré resultados reales?</span>
                <i class="material-icons toggle-icon">keyboard_arrow_down</i>
              </summary>
              <div class="faq-content">
                <p><strong>Envío gratis en 24-48h</strong> si tu pedido es de más de 49€. Lo tienes en casa antes de que se te acabe el saco actual.</p>
                <p>Ahora, lo que de verdad te importa: ¿cuándo vas a ver resultados? Te lo pongo claro, sin promesas mágicas:</p>
                <ul style="margin-left: 20px; margin-bottom: 15px;">
                  <li><strong>Digestión estable:</strong> 7-10 días. Heces más firmes, menos gases, menos sustos por la mañana.</li>
                  <li><strong>Pelaje más brillante y menos caída:</strong> 3 semanas. Lo notas cuando lo acaricias, y tu sofá también lo agradece.</li>
                  <li><strong>Más energía y mejor movilidad:</strong> 4-6 semanas. Sube escaleras sin quejarse, corre en el parque como antes.</li>
                </ul>
                <p>Pero hay algo que empieza desde el <strong>primer día</strong>: la tranquilidad de saber que le estás dando algo de verdad. Sin subproductos, sin harinas raras, sin químicos que no sabes ni pronunciar. Esa sensación de "estoy haciendo lo correcto" no tiene precio. ¿Listo para comprobarlo?</p>
              </div>
            </details>

          </div>
        </div>
      </section>

      <section class="section bg-soft text-center final-cta-bottom">
        <div class="container narrow">
          <h3>¿Sigues con dudas? Escríbenos por WhatsApp o email.</h3>
          <p style="margin-bottom: 30px;">Te respondemos en menos de 24 horas (normalmente en 2 horas). No queremos venderte algo que no necesitas. Queremos que tomes la mejor decisión para tu perro.</p>
          <a href="{$boske_category_url}" class="btn btn-primary">Pruébalo sin riesgo - Garantía 30 días</a>
        </div>
      </section>
    </main>

{/block}