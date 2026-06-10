<div class= "descripcion-general">
  <!--Tabla de informacion y beneficios-->
  <div class="container-description">
    <div class="Info-container-description">
      <h3 class="purple">
        <img src="{$module_img}informacion-producto.svg" title="Informacion" alt="producto_informacion" width="57" height="57">
        <span>{l s='Información' d='Shop.Theme.Catalog'}</span>
      </h3>

      <div class="colum colum1">
        <div class="info-box">
          <div class="colname">
            <span class="colnumber">01.</span>{l s='Marca' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Boske
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">02.</span>{l s='Tipo de producto' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Dieta Veterinaria Seca
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">03.</span>{l s='Necesidades específicas' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Problemas de pelo y piel, problemas alérgicos e intolerancias
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">04.</span>{l s='Mascota' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Perro
          </div>
        </div>
      </div>

      <div class="colum colum2">
        <div class="info-box">
          <div class="colname">
            <span class="colnumber">05.</span>{l s='Tamaño' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Todos los tamaños
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">06.</span>{l s='Edad' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Adulto
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">07.</span>{l s='Raza' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Todas las razas
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">08.</span>{l s='Opción nutricional' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Sin cereales
          </div>
        </div>
      </div>

      <div class="colum colum3">
        <div class="info-box">
          <div class="colname">
            <span class="colnumber">09.</span>{l s='Ingrediente principal' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Pollo
          </div>
        </div>
        <div class="info-box">
          <div class="colname">
            <span class="colnumber">10.</span>{l s='Textura de alimentos' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Croqueta
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">11.</span>{l s='Peso producto' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
              {$formatos}
          </div>
        </div>

        <div class="info-box">
            {if isset($product.reference_to_display)}
              <div class="colname">
                <span class="colnumber">12.</span>{l s='Referencia' d='Modules.Kpyproductdescription.Shop'}
              </div>

              <div class="colinfo">
                  {$product.reference_to_display}
              </div>
            {/if}
        </div>
      </div>

    </div>

    <div class="benefits-container-description">
      <h3 class="purple">
        <img src="{$module_img}beneficios.svg" title="Beneficios" alt="product_beneficios" width="57" height="57">
        <span>Beneficios</span>
      </h3>

      <div class="benefits-boxes">
        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosIlustrados-FuenteUnicaProteina.svg" alt="beneficio1">
          <div>Fuente única de proteína</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosIlustrados-AcidosGrasosOmega3.svg" alt="beneficio2">
          <div>Ácidos grasos Omega-3</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosIlustrados-FacilDigestion.svg" alt="beneficio3">
          <div>Fácil digestión</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosIlustrados-AltaPalatabilidad.svg" alt="beneficio4">
          <div>Alta palatabilidad</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosIlustrados-BajoAlergenos.svg" alt="beneficio5">
          <div>Ingredientes bajos en alérgenos</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosIlustrados-Levaduras.svg" alt="beneficio6">
          <div>Levaduras (fuente de MOS)</div>
        </div>

      </div>
    </div>

  </div>

  <!--Tabla de raciones-->


  <!-- Opinión especialista -->
  <div class="Opinion-expert">

    <!-- Imagen de la especialista -->
    <div class="Opinion-expert-img">
      <img src="{$module_img}BOSKE-Saco-Light-img-Veterinaria.png" alt="especialista">
    </div>

    <div class="expert-text-box">

      <div class="expert-title">
        <div>
          <img src="{$module_img}PYM-ico-OpinionEspecialista.svg" title="product_opinion_especialista" alt="producto_opinion_especialista" width="57" height="57">
        </div>

        <div class="Opinion-expert-title">
          <p class="purple">La opinión de nuestra especialista</p>
          <p class="Opinion-expert-subtitle">Por <span class="bold">Fara Duarte</span></p>
        </div>
      </div>


      <!-- Texto de la especialista -->
      <div class="Opinion-expert-text">
        <p>En perros con alergias alimentarias o intolerancias digestivas, la dieta se convierte en parte activa del tratamiento. Necesitamos algo más que eliminar ciertos ingredientes: buscamos reducir la inflamación, estabilizar el sistema digestivo y dar al organismo lo necesario sin sobrecargarlo. En este contexto, <strong>Boske Clinical Diet Hypoallergenic representa una propuesta muy interesante.</strong></p>

        <p>¿Por qué? Porque es de las <strong>pocas dietas veterinarias que combinan criterios clínicos con ingredientes naturales reales.</strong> Aquí no hay harinas genéricas ni subproductos cárnicos; la base es <strong>pescado blanco como única fuente proteica</strong>, combinado con <strong>patata como almidón de fácil digestión</strong>, y enriquecido con <strong>aceites funcionales y prebióticos naturales</strong> como MOS y XOS.</p>

        <p>Este enfoque permite utilizarla tanto en procesos de exclusión diagnóstica como en tratamientos prolongados, sabiendo que no solo estamos evitando el problema, sino también <strong>apoyando la salud intestinal, inmunológica y dérmica</strong> desde la nutrición.</p>

        <p>En consulta, esto no pasa desapercibido. Poder trabajar con una dieta formulada por veterinarios, pero con ingredientes naturales de calidad, es una opción clínica poco común… y muy valiosa.</p>
      </div>
    </div>
  </div>

  <!--Fotos Perro y piensos-->
  <div class="DogCatHeader">
    <img class="DogCatHeader1" src="{$module_img}BOSKE-Saco-Hypoallergenic-img-cabecera.png" alt="CabeceraPerro">
    <img class="DogCatHeader2" src="{$module_img}BOSKE-Saco-Hypoallergenic-img-bodegon-sacos-GRANDE.png" alt="BodegonPiensos">
  </div>

  <!--Informacion de mostrar todo-->
  <div class="show-all">

    <div class="additional-info-box additional-info-box-border-color">
      <div>
        <img src="{$module_img}BOSKE-Saco-Hypoallergenic-img-fotoPerro-01.png" title="perro1" alt="perro1">
      </div>

      <div class="text-additional-info">
        <h4>Cuando la alimentación deja de ser bienestar y se convierte en una fuente de malestar, es el momento de cambiar.</h4>

        <p>Boske Clinical Diet Hypoallergenic ha sido diseñado para responder a esa necesidad crítica: ofrecer una fórmula de alta tolerancia que no solo alimente, sino que <strong>minimice la carga alérgica del organismo y restablezca el equilibrio intestinal y cutáneo.</strong></p>

        <p>Formulado por <strong>veterinarios especialistas en nutrición clínica</strong>, este alimento utiliza una combinación precisa de ingredientes seleccionados por su bajo potencial alergénico: <strong>proteína única de pescado blanco</strong>, almidón de patata como fuente de energía de alta digestibilidad, y un perfil lipídico equilibrado con <strong>aceite de pescado y linaza</strong>, rico en Omega 3 y Omega 6.</p>

        <p>Todo ello con un único objetivo: <strong>romper el círculo inflamatorio</strong>, tanto digestivo como dermatológico, que sufren muchos perros con intolerancias.</p>
      </div>
    </div>

    <div class="additional-info-box">
      <div class="text-additional-info">
        <p>Además de cuidar qué entra, Boske cuida cómo se absorbe. Por eso incorpora <strong>prebióticos funcionales como MOS (Manano-Oligosacáridos) y XOS (Xilo-Oligosacáridos)</strong>, que actúan reforzando la microbiota intestinal, mejorando el tránsito y contribuyendo a restaurar una barrera inmunitaria fuerte desde el intestino.</p>

        <p>Esta dieta ha sido <strong>clínicamente testada</strong>, no solo por su perfil hipoalergénico, sino por su capacidad de mejorar visiblemente el confort digestivo, reducir episodios de diarrea, controlar el picor y favorecer la recuperación de la piel y el pelaje en perros con dermatitis alimentaria.</p>

        <p><strong>No es un pienso más: es un producto técnico, desarrollado con inversión en I+D, sin márgenes innecesarios ni intermediarios.</strong> Todo el coste va directo a lo que importa: la calidad de las materias primas y la fiabilidad de los resultados.</p>
      </div>
      <div>
        <img src="{$module_img}BOSKE-Saco-Hypoallergenic-img-fotoPerro-02.png" title="perro2" alt="perro2">
      </div>
    </div>

    <div class="additional-info-box">
      <div>
        <img src="{$module_img}BOSKE-Saco-Hypoallergenic-img-fotoPerro-03.png" title="perro3" alt="perro3">
      </div>
      <div class="text-additional-info">
        <div>
          <h2 class="color-title-other-products ">
            ¿Cuáles son las principales patologías para poder dar este producto a mi perro?
          </h2>

          <p><strong>Boske Clinical Diet Hypoallergenic está indicado para perros que presentan síntomas digestivos o cutáneos asociados a la alimentación</strong>, como diarreas frecuentes, vómitos, dermatitis, picores persistentes o pérdida de pelo. Son casos en los que el organismo reacciona de forma anómala ante ciertos componentes alimentarios, bien por alergia (involucra al sistema inmunológico) o por intolerancia (respuesta fisiológica no inmunitaria).</p>

          <p>Esta fórmula permite reducir la carga inflamatoria, eliminar posibles alérgenos comunes y ayudar al veterinario a descartar causas nutricionales en el proceso diagnóstico. Además, puede utilizarse en protocolos de <strong>dieta de eliminación</strong>, gracias a su <strong>composición monoproteica y altamente digestible.</strong></p>

          <p>Como siempre, se recomienda su uso bajo seguimiento veterinario, especialmente si se utiliza como herramienta clínica en perros con diagnóstico activo o sintomatología persistente.</p>
        </div>

        <h5>
          Aditivos nutricionales
        </h5>
        <p>
          Vitamina A 18500 UI/kg. Vitamina D3 1500 UI/kg. Vitamina E 250 mg/kg. Vitamina C
          125 mg/kg. Hierro (sulfato de hierro (II) monohidratado) 68 mg/kg. Yodo (yoduro
          potásico) 3,2 mg/kg. Cobre (sulfato de cobre (II) pentahidratado) 9 mg/kg.
          Manganeso (sulfato manganoso monohidratado) 6,8 mg/kg. Zinc (óxido de zinc)
          108 mg/kg. Selenio (selenito de sodio) 0,11 mg/kg.
        </p>
        <h5>
          <span class="bold">Aditivos tecnológicos:</span>
        </h5>
        <p>
          Antioxidantes: Extractos de tocoferoles de aceites vegetales.
        </p>

      </div>

    </div>

    <!--Beneficios clave-->
    <div class="Benefits-key-container">

      <h3 class="Benefits-key-tittle">
        <span>Beneficios clave</span>
      </h3>

      <div class="Benefits-key-table">

        <div class="colum-key colum1-key colum1-key-color ">
          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosBeneficios-FuenteUnicaProteina.svg" alt="icono_1" width="57" height="57">

            <div class="benefit-text benefit-text-chicken">
              Fuente única de proteína
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosBeneficios-AcidosGrasosOmega3.svg" alt="icono_2" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Riqueza en ácidos grasos omega 3 que controlan y reducen la inflamación
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosBeneficios-AltaDigestibilidad.svg" alt="icono_3" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Alta digestibilidad
            </div>
          </div>
        </div>

        <div class=" colum-key colum2-key">
          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosBeneficios-AltaPalatabilidad.svg" alt="icono_4" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Alta palatabilidad
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosBeneficios-LowAllergen.svg" alt="icono_5" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Low allergen
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Saco-Hypoallergenic-iconosBeneficios-Levaduras.svg" alt="icono_6" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Levaduras (fuente de MOS) mejoran salud intestinal y la respuesta inmunitaria
            </div>
          </div>
        </div>
      </div>
    </div>

    <!--Principales patologias-->


    <!--Ingredientes principales-->
    <div class="Ingredients-principales">
      <h4 class="ingredients">
        Ingredientes principales
      </h4>

      <div class="ingredients-boxes">
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-img-ingredientes-01.png" title="ingrediente1" alt="ingrediente1">
          <h3 class="ingredients-tittle">Patata</h3>
        </div>
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-img-ingredientes-03.png" title="ingrediente2" alt="ingrediente2">
          <h3 class="ingredients-tittle">Pescado blanco</h3>
        </div>

        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Saco-Hypoallergenic-img-ingredientes-02.png" title="ingrediente3" alt="ingrediente3">
          <h3 class="ingredients-tittle">Aceite de pescado</h3>
        </div>
      </div>
    </div>

    <div class="product-faq">
      <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

      {include file="components/accordition.tpl" list=$faq class="product-faq"}

    </div>

  </div>
</div>