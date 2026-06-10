<div class= "descripcion-general">
  <!--Tabla de informacion y beneficios-->
  <div class="container-description">
    <div class="Info-container-description">
      <h3 class="purple">
        <img src="{$module_img}ico-info.svg" title="Informacion" alt="producto_informacion" width="57" height="57">
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
            Dieta Veterinara Húmedo
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">03.</span>{l s='Necesidades específicas' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Problemas gastrointestinales
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
            Paté
          </div>
        </div>

        <div class="info-box">
            {if $es_pienso}
              <div class="colname">
                <span class="colnumber">11.</span>{l s='Peso producto' d='Modules.Kpyproductdescription.Shop'}
              </div>

              <div class="colinfo">
                  {$formatos}
              </div>
            {/if}
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
        <img src="{$module_img}ico-benefits.svg" title="Beneficios" alt="product_beneficios" width="57" height="57">
        <span>Beneficios</span>
      </h3>

      <div class="benefits-boxes">
        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosIlustrados-SoporteInmunitario.svg" alt="beneficio1">
          <div>Soporte inmunitario</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosIlustrados-MejoraSistemaDigestivo.svg" alt="beneficio2">
          <div>Alta disponibilidad</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosIlustrados-MejoraSistemaDigestivo.svg" alt="beneficio3">
          <div>Mejora su sistema digestivo</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosIlustrados-EfectoPrebiotico.svg" alt="beneficio4">
          <div>Efecto prebiótico</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosIlustrados-AltaPalatabilidad.svg" alt="beneficio5">
          <div>Especializado en perros con alta palatabilidad</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosIlustrados-EvitaHinchazonEstomago.svg" alt="beneficio6">
          <div>Evita la hinchazón del estómago</div>
        </div>
      </div>
    </div>

  </div>

  <!--Tabla de raciones-->
  <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
    <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Clinical Diet Gastrointestinal Grain Free Alimento Húmedo para Perros</p></div>
      <div class="row">
        <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
          Pollo (carne) 36%, hidrolizado de pollo, patatas, aceite de salmón, XOS, minerales, MOS, L-carnitina, uva
          polifenoles, xilosa.
          Fuentes de ingredientes digeribles que incluyen cualquier tratamiento: hidrolizado de pollo.
        </div>
      </div>
    </div>

    <div style="border: 1px solid #eee;margin-top: 1%;"></div>
    <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Clinical Diet Gastrointestinal Grain Free Alimento Húmedo para Perros</p></div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Humedad
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          80.1 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Proteína bruta
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          6 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Materias grasas
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          8.1 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Fibra bruta
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          1.8 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Potasio
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          0.22 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Sodio
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          0.17 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Cenizas Brutas
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          2.1 %
        </div>
      </div>
    </div>

    <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-RacionDiaria.svg"><p class="purple">Ración diaria Boske Clinical Diet Gastrointestinal Grain Free Alimento Húmedo para Perros</p></div>
      <table id="tabla" data-producto="8473-92790" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">

        <thead>
        <tr><th></th><th style="color: #734e7f;"><center>Peso perro</center></th><th style="color: #734e7f;"><center>Gramos</center></th><th style="color: #734e7f;"><center>Latas 400 gr</center></th></tr></thead>
        <tbody><tr><td style="color: #734e7f;"> </td><td><center>1 - 5 kg</center></td><td><center>150 - 350 gr</center></td><td><center>1/3 - 1</center></td></tr><tr><td style="color: #734e7f;"> </td><td><center>5 - 10 kg</center></td><td><center>350 - 550 gr</center></td><td><center>1 - 1 1/2</center></td></tr><tr><td style="color: #734e7f;"> </td><td><center>10 - 20 kg</center></td><td><center>550 - 850 gr</center></td><td><center>1 1/2 - 2</center></td></tr><tr><td style="color: #734e7f;"> </td><td><center>20 - 30 kg</center></td><td><center>850 - 1150 gr</center></td><td><center>2 - 3</center></td></tr><tr><td style="color: #734e7f;"> </td><td><center>30 - 40 kg</center></td><td><center>1150 - 1400 gr</center></td><td><center>3 - 3 1/2</center></td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- Opinión especialista -->
  <div class="Opinion-expert">

    <!-- Imagen de la especialista -->
    <div class="Opinion-expert-img">
      <img src="{$module_img}BOSKE-Saco-Light-img-Veterinaria.png" alt="especialista">
    </div>

    <div class="expert-text-box">

      <div class="expert-title">
        <div>
          <img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-OpinionEspecialista.svg" title="product_opinion_especialista" alt="producto_opinion_especialista" width="57" height="57">
        </div>

        <div class="Opinion-expert-title">
          <p class="purple">La opinión de nuestra especialista</p>
          <p class="Opinion-expert-subtitle">Por <span class="bold">Fara Duarte</span></p>
        </div>
      </div>


      <!-- Texto de la especialista -->
      <div class="Opinion-expert-text">
        <p>Hay muchos alimentos que prometen mejorar el sistema digestivo, pero muy pocos que realmente estén diseñados para hacerlo desde un enfoque clínico y con ingredientes naturales de verdad.</p>

        <p><strong>Boske Clinical Diet Gastrointestinal</strong> es una de esas excepciones. No es un producto más en el mercado: es un alimento desarrollado para perros que arrastran problemas digestivos reales, desde diarreas intermitentes hasta vómitos sin causa aparente.</p>

        <p>Lo que más valoro de esta fórmula es que no se limita a “suavizar el estómago”. <strong>Contiene prebióticos como XOS y MOS</strong>, que son clave para restaurar una flora intestinal funcional, algo esencial cuando hablamos de perros con desequilibrios digestivos. Además, <strong>la presencia de proteínas hidrolizadas</strong> y el equilibrio en electrolitos permiten una recuperación real del tracto digestivo sin sobrecargarlo.</p>

        <p>Es común pensar que una lata veterinaria será insípida o artificial. Este no es el caso. La fórmula se tolera muy bien, incluso en perros inapetentes o en procesos agudos, y tiene una composición que realmente aporta soporte nutricional en momentos donde el organismo está más comprometido.</p>

        <p>Si tu perro sufre de colitis, vómitos frecuentes o simplemente no digiere bien los piensos normales, <strong>este alimento puede marcar la diferencia.</strong></p>
      </div>
    </div>
  </div>

  <!--Fotos Perro y piensos-->
  <div class="DogCatHeader">
    <img class="DogCatHeader1" src="{$module_img}BOSKE-Lata-Gastrointestinal-img-Cabecera-ClinicalDiet.png" alt="CabeceraPerro">
    <img class="DogCatHeader2" src="{$module_img}BOSKE-Lata-Gastrointestinal-img-BodegonLatas-ClinicalDiet.png" alt="BodegonPiensos">
  </div>

  <!--Informacion de mostrar todo-->
  <div class="show-all">

    <div class="additional-info-box additional-info-box-border-color">
      <div>
        <img src="{$module_img}BOSKE-Lata-Gastrointestinal-img-fotoPerro-01.png" title="perro1" alt="perro1">
      </div>

      <div class="text-additional-info">
        <p>Boske Clinical Diet Gastrointestinal Grain Free es una fórmula veterinaria diseñada para cuidar de la salud digestiva de perros con molestias intestinales, digestiones sensibles o recuperación gástrica. Ha sido desarrollada por un equipo de <strong>nutricionistas veterinarios</strong>, apoyados en estudios clínicos y validaciones de campo, lo que garantiza una solución basada en evidencia.</p>

        <p>Su receta incluye ingredientes de <strong>alta calidad y fácil asimilación</strong>, y está elaborada sin cereales ni alérgenos comunes, lo que favorece una mejor tolerancia digestiva. Además, contiene <strong>probióticos específicos</strong> que actúan restaurando la flora intestinal natural, clave para el bienestar digestivo general.</p>

        <p>Sin inversiones en publicidad ni costes inflados por distribuidores, <strong>la inversión se dedica exclusivamente a tecnología, investigación y calidad de materias primas</strong>, asegurando un producto final eficaz, serio y accesible para quienes de verdad lo necesitan.</p>
      </div>
    </div>

    <div class="additional-info-box">
      <div class="text-additional-info">
        <p><strong>Boske Clinical Diet Gastrointestinal</strong> ofrece una respuesta nutricional real para perros con digestiones delicadas o trastornos intestinales recurrentes. A menudo, quienes conviven con estos problemas tienden a elegir alimentos muy restrictivos o directamente pobres en nutrientes por miedo a “empeorar” el cuadro. Esta dieta, sin embargo, rompe con esa lógica: combina una fórmula completa con ingredientes naturales y seleccionados por su alta tolerancia digestiva y valor nutricional.</p>

        <p>Gracias a su combinación específica de proteínas, fibras solubles y prebióticos, está formulada no solo para aliviar, sino también para <strong>apoyar la recuperación del sistema gastrointestinal sin descuidar la nutrición diaria.</strong> Es una dieta que <strong>no castiga con carencias</strong> ni juega con la improvisación: ha sido diseñada por veterinarios especialistas en nutrición clínica para aportar <strong>eficacia y equilibrio</strong> en cada toma.</p>
      </div>
      <div>
        <img src="{$module_img}BOSKE-Lata-Gastrointestinal-img-fotoPerro-02.png" title="perro2" alt="perro2">
      </div>
    </div>

    <div class="additional-info-box">
      <div>
        <img src="{$module_img}BOSKE-Lata-Gastrointestinal-img-fotoPerro-03.png" title="perro3" alt="perro3">
      </div>

      <div class="text-additional-info">
        <p>Este alimento húmedo es adecuado para perros con problemas digestivos crónicos, especialmente cuando hay alteraciones en la absorción intestinal o desequilibrio en la flora. Muchas veces se recurre a soluciones caseras o restrictivas que no cubren las necesidades reales del perro enfermo. Este producto corrige esa creencia: ofrece un enfoque veterinario basado en evidencia, equilibrando el sistema digestivo sin renunciar a una nutrición de alta calidad.</p>

        <p>Boske Gastrointestinal combina una fórmula desarrollada por expertos con ingredientes cuidadosamente seleccionados:</p>
        <ul style="list-style: unset;padding-left: 1.25rem;">
          <li><strong>Proteínas hidrolizadas:</strong> más fáciles de digerir y con menor riesgo de reacción.</li>
          <li><strong>Prebióticos XOS y MOS:</strong> ayudan a restablecer la flora intestinal y mejorar el sistema inmune.</li>
          <li><strong>Polifenoles de Vitis vinifera:</strong> antioxidantes naturales que combaten el daño celular.</li>
          <li><strong>Textura en paté:</strong> altamente palatable, ideal para perros con bajo apetito o convalecientes.</li>
        </ul>

        <p>Una opción equilibrada, clínicamente segura y con sabor que no compromete el tratamiento.</p>
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
            <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosBeneficios-IngredientesNaturales.svg" alt="icono_1" width="57" height="57">

            <div class="benefit-text benefit-text-chicken">
              Ingredientes naturales
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosBeneficios-TrastornosGastrointestinales.svg" alt="icono_2" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Indicado para la reducción de trastornos intestinales
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosBeneficios-FacilmenteDigerible.svg" alt="icono_3" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Facilmente digerible
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosBeneficios-SistemaInmunitario.svg" alt="icono_4" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Favorece la salud del sistema inmunitario
            </div>
          </div>
        </div>

        <div class=" colum-key colum2-key">
          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosBeneficios-GrainFree.svg" alt="icono_5" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Grain free
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosBeneficios-Prebioticos.svg" alt="icono_6" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Prebióticos como XOS y MOS que ayudan a la flora intestinal, aumentan la respuesta inmune, regulan el tránsito intestinal y favorecen la síntesis de vitaminas
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosBeneficios-AltaPalatabilidad-2.svg" alt="icono_7" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Alta palatabilidad
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Gastrointestinal-iconosBeneficios-RecuperarNutrientes.svg" alt="icono_8" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Nutrición que ayuda a recuperar nutrientes perdidos
            </div>
          </div>
        </div>
      </div>
    </div>

    <!--Principales patologias-->
    <div class="additional-info-box">
      <div class="text-additional-info">
        <p>El alimento húmedo Boske Clinical Diet Gastrointestinal está indicado en perros con alteraciones digestivas concretas. Su fórmula ha sido diseñada para intervenir en los momentos donde el sistema digestivo no responde bien a una alimentación convencional. Estas situaciones suelen venir acompañadas de síntomas molestos y recurrentes que no desaparecen con piensos genéricos o alimentos caseros.</p>

        <p><strong>Indicaciones clínicas frecuentes:</strong>:
        <ul style="list-style:unset;padding-left: 1.25rem;">
          <li><strong>Transtornos de absorción intestinal:</strong> cuando el perro no asimila bien los nutrientes de la comida.</li>
          <li><strong>Animales con digestiones complicadas:</strong> casos en los que cualquier alimento le sienta mal o tarda horas en hacer la digestión.</li>
          <li><strong>Diarreas agudas:</strong> tanto si son puntuales como persistentes.</li>
          <li><strong>Colitis:</strong> inflamación del colon que puede causar dolor, diarrea con mucosidad o sangre.</li>
          <li><strong>Gastritis:</strong> molestias estomacales frecuentes, vómitos o inapetencia.</li>
        </ul>
        </p>

        <p>Esta comida húemda está pensada para esos casos donde la comida convencional ya no es una opción. Si no ves una mejora con otros alimentos, aquí tienes una fórmula clínica, con base veterinaria y diseñada para actuar.</p>
      </div>
      <div>
        <img src="{$module_img}BOSKE-Lata-Gastrointestinal-img-fotoPerro-04.png" title="perro4" alt="perro4">
      </div>
    </div>

    <!--Ingredientes principales-->
    <div class="Ingredients-principales">
      <h4 class="ingredients">
        Ingredientes principales
      </h4>

      <div class="ingredients-boxes">
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-img-Ingredientes-02.png" title="ingrediente1" alt="ingrediente1">
          <h3 class="ingredients-tittle">Patata</h3>
        </div>
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-img-Ingredientes-01.png" title="ingrediente2" alt="ingrediente2">
          <h3 class="ingredients-tittle">Pollo</h3>
        </div>

        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Lata-Gastrointestinal-img-Ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
          <h3 class="ingredients-tittle">Aceite de salmón</h3>
        </div>
      </div>
    </div>

    <div class="product-faq">
      <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

      {include file="components/accordition.tpl" list=$faq class="product-faq"}

    </div>

  </div>
</div>