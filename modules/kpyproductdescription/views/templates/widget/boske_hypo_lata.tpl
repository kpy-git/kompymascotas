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
            Dieta Veterinara Húmedo
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
            Sin cereales, hipoalergénicos
          </div>
        </div>
      </div>

      <div class="colum colum3">
        <div class="info-box">
          <div class="colname">
            <span class="colnumber">09.</span>{l s='Ingrediente principal' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Conejo
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
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosIlustrados-SoporteInmunitario.svg" alt="beneficio1">
          <div>Soporte inmunitario</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosIlustrados-PrevieneAlergias.svg" alt="beneficio2">
          <div>Previene reacciones alérgicas</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosIlustrados-PielSana.svg" alt="beneficio3">
          <div>Piel sana</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosIlustrados-FavorecePelaje.svg" alt="beneficio4">
          <div>Favorece el pelaje</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosIlustrados-EfectoPrebiotico.svg" alt="beneficio5">
          <div>Efecto prebiótico</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosIlustrados-EvitaHinchazonEstomago.svg" alt="beneficio6">
          <div>Evita la hinchazón del estómago</div>
        </div>

      </div>
    </div>

  </div>

  <!--Tabla de raciones-->
  <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse;padding-bottom: 1rem">
    <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%;margin-right: 2%;">
      <h3><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><span>Ingredientes Boske Clinical Diet Hypoallergenic Grain Free Alimento Húmedo para Perros</span></h3>

      <div class="row" style="border-bottom:none;">
        <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
          Conejo (carne) 47%, guisantes, aceite de linaza, aceite de salmón, XOS, minerales, MOS, polifenoles de uva, xilosa.
          Fuente de proteína: conejo.
        </div>
      </div>
    </div>

    <div style="border: 1px solid #eee;margin-top: 1%;"></div>
    <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
      <h3><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><span>Analítica Boske Clinical Diet Hypoallergenic Grain Free Alimento Húmedo para Perros</span></h3>
      <div class="row">
        <div class="col-lg-6 col-xs-6" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Humedad
        </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f; font-weight: 400; font-size: 14px">
          82.1 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Proteína bruta
        </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f; font-weight: 400; font-size: 14px">
          6.7 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Materias grasas
        </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f; font-weight: 400; font-size: 14px">
          5.8 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Fibra bruta
        </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f; font-weight: 400; font-size: 14px">
          0.5 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Omega-3
        </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f; font-weight: 400; font-size: 14px">
          0.2 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Cenizas Brutas
        </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3" style="color: #734e7f; font-weight: 400; font-size: 14px">
          1.8 %
        </div>
      </div>
    </div>

    <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto; padding: 1rem;">
      <h3><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-RacionDiaria.svg"><span>Ración diaria Boske Clinical Diet Hypoallergenic Grain Free Alimento Húmedo para Perros</span></h3>
      <table id="tabla" data-producto="8474-92794" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">

        <thead>
        <tr><th></th><th style="color: #734e7f;"><center>Peso perro</center></th><th style="color: #734e7f;"><center>Gramos</center></th><th style="color: #734e7f;"><center>Latas 400 gr</center></th></tr></thead>
        <tbody><tr><td style="color: #734e7f;"> </td><td><center>1 - 5 Kg</center></td><td><center>110 - 280 gr</center></td><td><center>1/3 - 1/2</center></td></tr><tr><td style="color: #734e7f;"> </td><td><center>5 - 10 Kg</center></td><td><center>280 - 470 gr</center></td><td><center>1/2 - 1</center></td></tr><tr><td style="color: #734e7f;"> </td><td><center>10 - 20 Kg</center></td><td><center>470 - 790 gr</center></td><td><center>1 - 2</center></td></tr><tr><td style="color: #734e7f;"> </td><td><center>20 - 30 Kg</center></td><td><center>790 - 1070 gr</center></td><td><center>2 - 2 1/2</center></td></tr><tr><td style="color: #734e7f;"></td><td><center>30 - 40 Kg</center></td><td><center>1070 - 1350 gr</center></td><td><center>2 1/2 - 3 1/2</center></td></tr></tbody>
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
          <img src="{$module_img}PYM-ico-OpinionEspecialista.svg" title="product_opinion_especialista" alt="producto_opinion_especialista" width="57" height="57">
        </div>

        <div class="Opinion-expert-title">
          <p class="purple">La opinión de nuestra especialista</p>
          <p class="Opinion-expert-subtitle">Por <span class="bold">Fara Duarte</span></p>
        </div>
      </div>


      <!-- Texto de la especialista -->
      <div class="Opinion-expert-text">
        <p>Como veterinaria, veo con frecuencia a dueños de perros desesperados porque su mascota “no tolera nada”. Llegan después de probar piensos, tratamientos y cambios continuos, sin resultados duraderos. Y muchas veces, el problema no es tanto lo que comen… sino lo que su cuerpo <strong>no puede gestionar bien.</strong></p>

        <p>En estos casos, siempre insisto en algo: hay que simplificar. Y eso implica usar <strong>dietas hipoalergénicas de verdad</strong>, formuladas con <strong>una única proteína animal</strong>, sin cereales y con ingredientes que reduzcan al mínimo el riesgo de reacción.</p>

        <p>Esta dieta húmeda de Boske, basada en conejo y guisante, es precisamente eso: sencilla, eficaz y diseñada con cabeza. Es importante recalcarlo, porque no todo lo que dice “hipoalergénico” lo es. Muchos productos incluyen trazas de múltiples ingredientes o aditivos innecesarios.</p>

        <p>Otro punto que valoro es que sea <strong>comida húmeda</strong>, no solo porque mejora la palatabilidad, sino porque permite una <strong>digestión más ligera</strong>, ideal para perros con estómagos sensibles, colitis o problemas de piel derivados de intolerancias. Además, <strong>aporta hidratación</strong>, algo muy beneficioso en dietas de exclusión o perros que beben poco.</p>

        <p>¿Mi consejo? No cambies cada dos semanas. Si un alimento está bien formulado, como este, y se adapta a las necesidades reales del animal, lo mejor es mantenerlo, observar con atención, y sobre todo, darle tiempo al organismo para recuperarse. La prisa es el peor enemigo en estos procesos.</p>
      </div>
    </div>
  </div>

  <!--Fotos Perro y piensos-->
  <div class="DogCatHeader">
    <img class="DogCatHeader1" src="{$module_img}BOSKE-Lata-Hypoallergenic-img-Cabecera-ClinicalDiet.png" alt="CabeceraPerro">
    <img class="DogCatHeader2" src="{$module_img}BOSKE-Lata-Hypoallergenic-img-BodegonLatas-ClinicalDiet.png" alt="BodegonPiensos">
  </div>

  <!--Informacion de mostrar todo-->
  <div class="show-all">

    <div class="additional-info-box additional-info-box-border-color">
      <div>
        <img src="{$module_img}BOSKE-Lata-Hypoallergenic-img-fotoPerro-01.png" title="perro1" alt="perro1">
      </div>

      <div class="text-additional-info">
        <p><strong>Boske Clinical Diet Hypoallergenic Grain Free</strong> es una dieta húmeda <strong>específicamente formulada para perros con alergias o intolerancias alimentarias</strong>, desarrollada por veterinarios expertos en nutrición clínica. Gracias a su contenido de <strong>conejo como única fuente de proteína animal</strong> y a la total <strong>ausencia de cereales</strong>, esta receta reduce de forma efectiva el riesgo de reacciones adversas al alimento.</p>

        <p>Con una textura suave, alta palatabilidad y <strong>digestibilidad mejorada</strong>, esta fórmula se convierte en una herramienta terapéutica para perros con <strong>picores, diarreas recurrentes o procesos inflamatorios digestivos</strong>. Los <strong>ingredientes naturales</strong>, como los <strong>guisantes, aceites funcionales (linaza y salmón)</strong>, y <strong>prebióticos como MOS y XOS, ayudan a fortalecer la barrera cutánea</strong>, mejorar la salud intestinal y promover una respuesta inmune equilibrada.</p>

        <p>Una elección sólida cuando se busca una <strong>alimentación veterinaria funcional, pero con ingredientes reconocibles, naturales y de alto valor biológico</strong>, sin artificios ni rellenos innecesarios.</p>
      </div>
    </div>

    <div class="additional-info-box">
      <div class="text-additional-info">
        <p><strong>Una dieta veterinaria hipoalergénica solo tiene sentido si combina eficacia clínica con naturalidad real.</strong> Y eso es precisamente lo que ofrece <strong>Boske Clinical Diet Hypoallergenic Grain Free:</strong> una fórmula desarrollada para actuar sobre el origen del problema, sin comprometer la calidad nutricional. Cada ingrediente ha sido cuidadosamente seleccionado no solo por su tolerancia digestiva, sino también por su valor biológico.</p>

        <p><strong>El conejo (47%) como fuente única de proteína animal</strong> es altamente digestible y con bajo potencial alergénico, ideal para animales con sensibilidades múltiples. A esto se suma una base de guisantes como fuente de carbohidrato alternativo y aceites funcionales (linaza y salmón) que aportan Omega 3 y Omega 6, claves para la recuperación cutánea.</p>

        <p>Pero lo que realmente marca la diferencia es la inclusión de <strong>prebióticos funcionales como MOS, XOS y polifenoles de uva</strong>, que ayudan a regenerar la flora intestinal, calmar el sistema digestivo y equilibrar la inmunidad intestinal. Esta combinación no solo reduce la aparición de síntomas, sino que contribuye activamente a restaurar el bienestar digestivo y dermatológico.</p>
      </div>
      <div>
        <img src="{$module_img}BOSKE-Lata-Hypoallergenic-img-fotoPerro-02.png" title="perro2" alt="perro2">
      </div>
    </div>

    <div class="additional-info-box">
      <div>
        <img src="{$module_img}BOSKE-Lata-Hypoallergenic-img-fotoPerro-03.png" title="perro3" alt="perro3">
      </div>

      <div class="text-additional-info">
        <div>
          <h2 class="color-title-other-products ">
            ¿Cómo sé si este producto es apropiado para mi perro?
          </h2>

          <p>La clave está en entender la causa del problema. Cuando un perro sufre de <strong>alergias o intolerancias alimentarias</strong>, los síntomas suelen manifestarse a través de <strong>problemas cutáneos</strong> (como picor o enrojecimiento) o <strong>digestivos</strong> (diarrea, vómitos, flatulencias). En las alergias está implicado el sistema inmune, mientras que las intolerancias se producen por una <strong>reacción fisiológica adversa</strong> a determinados ingredientes. En ambos casos, la alimentación puede ser un factor decisivo para aliviar o empeorar los síntomas.</p>

          <p><strong>Boske Clinical Diet Hypoallergenic Grain Free Húmedo</strong> está diseñado para actuar sobre estos escenarios. Utiliza <strong>una única fuente de proteína animal (conejo)</strong>, seleccionada por su <strong>bajo potencial alergénico y su alta digestibilidad</strong>. A esto se suma una fórmula rica en Omega 3 y 6, que ayudan a reforzar la <strong>barrera cutánea, y prebióticos funcionales (XOS y MOS)</strong> que favorecen una flora intestinal sana.</p>

          <p>Es una opción especialmente útil si tu perro ha mostrado <strong>signos de sensibilidad</strong> a otros piensos o comidas húmedas, o si buscas una dieta de exclusión segura y natural, clínicamente formulada. Siempre, por supuesto, bajo la orientación de tu veterinario de confianza.</p>
        </div>
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
            <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosBeneficios-IngredientesNaturales.svg" alt="icono_1" width="57" height="57">

            <div class="benefit-text benefit-text-chicken">
              Ingredientes naturales
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosBeneficios-ReduccionIntolerancias.svg" alt="icono_2" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Indicado para la reducción de intolerancias y alergias a ingredientes y componentes alimenticios
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosBeneficios-Monoproteico.svg" alt="icono_3" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Monoproteico
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosBeneficios-BarreraCutanea.svg" alt="icono_4" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Refuerza la barrera cutánea
            </div>
          </div>

        </div>

        <div class=" colum-key colum2-key">

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosBeneficios-GrainFree.svg" alt="icono_5" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Grain free
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosBeneficios-Prebioticos.svg" alt="icono_6" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Prebióticos como XOS y MOS que ayudan a la flora intestinal, aumentan la respuesta inmune, regulan el tránsito intestinal y favorecen la síntesis de vitaminas
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosBeneficios-Omega.svg" alt="icono_7" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Omega 3 y Omega 6 previenen las reacciones inflamatorias y mejoran la barrera cutánea
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
            <img src="{$module_img}BOSKE-Lata-Hypoallergenic-iconosBeneficios-Antioxidantes.svg" alt="icono_8" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Antioxidantes que reducen los radicales libres
            </div>
          </div>
        </div>
      </div>
    </div>

    <!--Principales patologias-->
    <div class="additional-info-box">
      <div class="text-additional-info">
        <h3 class="color-title-other-products ">
          ¿Cuáles son las principales patologías para poder dar este producto a mi perro?
        </h3>
        <p>
          Aunque cada caso debe ser valorado por un veterinario, la fórmula de Boske Clinical Diet Hypoallergenic húmedo ha sido cuidadosamente diseñada para adaptarse a muchos perfiles de perros con intolerancias alimentarias o digestiones delicadas. Estas son algunas de las ventajas concretas que puede aportar en su día a día:
        </p>

        <ul style="list-style: unset;padding-left: 1.25rem;">
          <li><strong>Rica en carne de conejo (47%)</strong>, una proteína altamente digestible y poco alergénica.</li>
          <li><strong>Sin cereales</strong>, lo que reduce la carga digestiva y minimiza los riesgos de intolerancias.</li>
          <li><strong>Aceite de salmón y linaza</strong>, dos fuentes naturales de ácidos grasos Omega 3 y 6 para reforzar la piel, el pelaje y el sistema inmunitario.</li>
          <li><strong>Prebióticos naturales (XOS y MOS)</strong>, que ayudan a equilibrar la flora intestinal y mejorar las digestiones.</li>
          <li><strong>Polifenoles de uva y xilosa</strong>, antioxidantes naturales que protegen las células del estrés oxidativo.</li>
        </ul>

        <p>Este alimento no solo está formulado para evitar reacciones adversas, sino también para ofrecer un soporte nutricional completo y de calidad clínica para perros que requieren un cuidado especial.</p>
      </div>
      <div>
        <img src="{$module_img}BOSKE-Lata-Hypoallergenic-img-fotoPerro-04.png" title="perro4" alt="perro4">
      </div>
    </div>

    <!--Ingredientes principales-->
    <div class="Ingredients-principales">
      <h4 class="ingredients">
        Ingredientes principales
      </h4>

      <div class="ingredients-boxes">
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-img-Ingredientes-01.png" title="ingrediente1" alt="ingrediente1">
          <h3 class="ingredients-tittle">Conejo</h3>
        </div>
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-img-Ingredientes-02.png" title="ingrediente2" alt="ingrediente2">
          <h3 class="ingredients-tittle">Guisantes</h3>
        </div>

        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Lata-Hypoallergenic-img-Ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
          <h3 class="ingredients-tittle">Aceite de linaza</h3>
        </div>
      </div>
    </div>

    <div class="product-faq">
      <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

      {include file="components/accordition.tpl" list=$faq class="product-faq"}
    </div>

  </div>
</div>