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
            Comida Seca
          </div>
        </div>

        <div class="info-box">
          <div class="colname">
            <span class="colnumber">03.</span>{l s='Característica principal' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Cachorros en crecimiento
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
            Cachorro
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
            Natural
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
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosIlustrados-AyudaSistemaInmunologico.svg" alt="beneficio1">
          <div>Ayuda al sistema inmunológico</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosIlustrados-CrecimientoDesarrolloOptimo.svg" alt="beneficio2">
          <div>Crecimiento y desarrollo óptimo</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosIlustrados-FacilDigestion.svg" alt="beneficio3">
          <div>Fácil digestión</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosIlustrados-DesarrolloCerebroVision.svg" alt="beneficio4">
          <div>Desarrollo del cerebro y la visión</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosIlustrados-ProteinasCalidad.svg" alt="beneficio5">
          <div>Proteínas de calidad</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosIlustrados-IngredientesNaturales.svg" alt="beneficio6">
          <div>Ingredientes naturales</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosIlustrados-Omega3yOmega6.svg" alt="beneficio7">
          <div>Omega-3 y Omega-6</div>
        </div>
      </div>
    </div>

  </div>

  <!--Tabla de raciones-->
  <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
    <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Puppy &amp; Junior Chicken Low Grain Pienso Natural para Perro</p></div>
      <div class="row">
        <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
          Proteína de pollo deshidratada 40 %. Arroz integral 20 %. Guisantes. Grasa de ave. Pulpa de manzana. Hidrolizado de hígado de pollo. Huevo entero deshidratado. Garrofa. Semilla de linaza. Aceite de pescado. Levadura. Minerales. Zanahoria. Glucosamina. Sulfato de condroitina. Yucca schidigera.&nbsp;
        </div>
      </div>
    </div>

    <div style="border: 1px solid #eee;margin-top: 1%;"></div>
    <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Puppy &amp; Junior Chicken Low Grain Pienso Natural para Perro</p></div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Proteína bruta
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          32.5 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Materias grasas
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          16 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Fibra bruta
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          3 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Cenizas Brutas
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          9 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Calcio
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          1.9 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Fósforo
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          1.3 %
        </div>
      </div>
    </div>

    <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-RacionDiaria.svg"><p class="purple">Ración diaria Boske Puppy &amp; Junior Chicken Low Grain Pienso Natural para Perro</p></div>
      <table id="tabla" data-producto="7215-90686" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">

        <thead>
        <tr><th></th><th style="color: #734e7f;"><center>2 meses</center></th><th style="color: #734e7f;"><center>3 meses</center></th><th style="color: #734e7f;"><center>4 meses</center></th><th style="color: #734e7f;"><center> 6meses</center></th><th style="color: #734e7f;"><center> 12 meses </center></th></tr></thead>
        <tbody><tr><td style="color: #734e7f;">1kg</td><td><center>76g</center></td><td><center>66g</center></td><td><center>55g</center></td><td><center>47g</center></td><td><center>30g</center></td></tr><tr><td style="color: #734e7f;">2kg</td><td><center>129g</center></td><td><center>111g</center></td><td><center>95g</center></td><td><center>79g</center></td><td><center>51g</center></td></tr><tr><td style="color: #734e7f;">5kg</td><td><center>256g</center></td><td><center>220g</center></td><td><center>189g</center></td><td><center>156g</center></td><td><center>101g</center></td></tr><tr><td style="color: #734e7f;">10kg</td><td><center>430g</center></td><td><center>370g</center></td><td><center>317g</center></td><td><center>263g</center></td><td><center>170g</center></td></tr><tr><td style="color: #734e7f;">15kg</td><td><center></center></td><td><center>502g</center></td><td><center>430g</center></td><td><center>356g</center></td><td><center>230g</center></td></tr><tr><td style="color: #734e7f;">20kg</td><td><center></center></td><td><center></center></td><td><center>534g</center></td><td><center>442g</center></td><td><center>286g</center></td></tr><tr><td style="color: #734e7f;">30kg</td><td><center></center></td><td><center></center></td><td><center></center></td><td><center>599g</center></td><td><center>387g</center></td></tr><tr><td style="color: #734e7f;">40kg</td><td><center></center></td><td><center></center></td><td><center></center></td><td><center></center></td><td><center>480g</center></td></tr><tr><td style="color: #734e7f;">50kg</td><td><center></center></td><td><center></center></td><td><center></center></td><td><center></center></td><td><center>568g</center></td></tr><tr><td style="color: #734e7f;">60kg</td><td><center></center></td><td><center></center></td><td><center></center></td><td><center></center></td><td><center>651g</center></td></tr></tbody>
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
        <p>"Siempre digo lo mismo en consulta: alimentar bien a un cachorro no es darle algo que le guste. Es darle algo que lo forme."</p>

        <p>Como veterinaria, me encuentro a diario con cachorros que llegan con diarreas recurrentes, picores en la piel, orejas rojas o simplemente apatía. En muchos casos, no es una enfermedad. Es el pienso.<br/>Y no porque el dueño no se preocupe, al contrario: elige lo que ve anunciado, lo que le recomendaron sin mucha base, o lo que simplemente “le va bien”.</p>

        <p>El problema es que en esa etapa, <strong>todo lo que entra por la boca está construyendo algo</strong>: huesos, sistema digestivo, defensas, piel, carácter. Un pienso mediocre no se nota en una semana… pero sí se nota en un año.</p>

        <p>Cuando conocí la composición de <strong>Boske Puppy & Junior</strong>, pensé: “Aquí hay alguien que ha entendido lo importante que es hacer las cosas bien desde el principio”.<br />Proteína real de pollo, huevo entero, verduras frescas, un solo cereal (arroz integral, fácil de digerir), sin maíz, sin trigo, sin rellenos...Y, además, una fórmula adaptada con glucosamina, condroitina, DHA y ácidos grasos para acompañar el desarrollo neurológico, articular y digestivo. <br />Tu cachorro no puede elegir lo que come. Pero tú sí. Y en un mercado lleno de promesas vacías, <strong>elegir un pienso honesto, bien formulado y diseñado para la etapa más delicada de su vida, no es un lujo. Es una responsabilidad.</strong></p>

        <p>"Recomiendo Boske porque he visto cómo marca la diferencia. Y porque prevenir con una buena base siempre será más inteligente que curar después."</p>
      </div>
    </div>
  </div>

  <!--Fotos Perro y piensos-->
  <div class="DogCatHeader">
    <img class="DogCatHeader1" src="{$module_img}BOSKE-Saco-PolloPuppy-img-Cabecera-PolloPuppy.png" alt="CabeceraPerro">
    <img class="DogCatHeader2" src="{$module_img}BOSKE-Saco-PolloPuppy-img-Bodegon-Sacos.png" alt="BodegonPiensos">
  </div>

  <!--Informacion de mostrar todo-->
  <div class="show-all">

    <div class="additional-info-box additional-info-box-border-color">
      <div>
        <img src="{$module_img}BOSKE-Saco-PolloPuppy-img-fotoPerro-01.png" title="perro1" alt="perro1">
      </div>

      <div class="text-additional-info">
        <p><strong>Los primeros meses de vida no son una fase cualquiera.</strong> Son la base sobre la que se va a construir la salud de tu perro para siempre. Y si estás aquí, es porque no quieres improvisar con algo tan importante como su alimentación.</p>

        <p><strong>Boske Puppy & Junior</strong> es un pienso natural <strong>Súper Premium</strong>, creado para darle a tu cachorro lo que realmente necesita: <strong>proteína real, ingredientes frescos y una fórmula diseñada por especialistas en nutrición canina.</strong></p>

        <p>Elaborado con <strong>pollo, verdura fresca y huevo entero deshidratado</strong>, todos ingredientes aptos para consumo humano en su origen, no solo le aporta energía y sabor. Le aporta equilibrio. Estimula su sistema digestivo, fortalece su crecimiento, y <strong>lo prepara desde dentro para ser un perro sano, fuerte y activo.</strong><br />Desde el primer cuenco, Boske se convierte en una forma de cuidarlo. No de alimentar, de proteger.</p>
      </div>
    </div>

    <div class="additional-info-box">
      <div class="text-additional-info">
        <h5>Un cachorro necesita mucho más que energía: necesita construir su salud desde cero.</h5>
        <p>Por eso, Boske Puppy & Junior incorpora ingredientes que hacen algo más que llenar: <strong>ayudan a formar huesos fuertes, un sistema inmunológico robusto y un sistema digestivo equilibrado.</strong></p>
        <p>Su fórmula incluye <strong>glucosamina y condroitina</strong>, fundamentales para el desarrollo óseo y articular en esta etapa tan delicada. El <strong>aceite de pescado y la linaza</strong> aportan ácidos grasos omega y DHA, que favorecen un crecimiento sano del cerebro, la vista y la piel. Además, su croqueta está adaptada para facilitar la masticación: <strong>0,8 cm, ideal para sus primeros mordiscos.</strong></p>
        <p>¿El resultado? Una comida que les encanta y que tú eliges con la tranquilidad de saber que cada ración cuenta.<br />Sin colorantes. Sin conservantes artificiales. <strong>Y sin nada que no tenga sentido para un cachorro.</strong></p>
      </div>
      <div>
        <img src="{$module_img}BOSKE-Saco-PolloPuppy-img-fotoPerro-02.png" title="perro2" alt="perro2">
      </div>
    </div>

    <div class="additional-info-box">
      <div>
        <img src="{$module_img}BOSKE-Saco-PolloPuppy-img-fotoPerro-03.png" title="perro3" alt="perro3">
      </div>

      <div class="text-additional-info">
        <div>
          <h5>Modo de empleo</h5>
          <p>
            El cambio de alimento debe hacerse de forma gradual durante 7 días. Cuando el
            cachorro es muy joven (destete) hasta los 4meses, suministre el alimento 4 ó 5
            veces al día, mezclándolo con agua templada en proporción de 1 parte de agua
            por 3 partes de alimento. Retire el producto sobrante al poco tiempo. Cuando el cachorro esté próximo al
            año de vida, reduzca de forma gradual el número de comidas hasta llegar a 2
            comidas al día. Los perros deben disponer siempre de agua limpia y fresca.
          </p>

          <h5>Aditivos nutricionales</h5>
          <p>
            Vitamina A 18500UI/kg. Vitamina D3 1500 mg/kg. Vitamina E 200 mg/kg. Vitamina C
            100mg/kg. Hierro (Sulfato de hierro (II) monohidratado) 68 mg/kg. Iodo
            (Yoduropotásico) 3,2 mg/kg. Cobre (sulfato de cobre (II) heptahidratado) 9 mg/kg.
            Manganeso (sulfato manganoso monohidratado) 6,8 mg/kg. Zinc (óxido de zinc)
            108mg/kg. Selenio (selenito sódico) 0,11 mg/kg. Taurina 400 mg/kg. Glucosamina y condroitina que ayuda a la correcta formación de los huesos y la
            reparación del cartílago para lograr un crecimiento óseo óptimo y aceite de
            pescado y la linaza que ayudan a mantener la piel y el pelaje brillante, además
            de fortalecer el sistema inmune. Los ácidos grasos omega son abundantes,
            incluido el DHA, omega-3 y omega-6 que apoya el desarrollo saludable del
            cerebro y los ojos.
          </p>
          <p>
            <span class="bold">Aditivos tecnológicos:</span>
          </p>
          <p>
            Antioxidantes: Extractos de tocoferoles de aceites vegetales 95 mg/kg.
          </p>
          <p>
            Energía metabolizante: 3.900 kcal/kg
          </p>
          <p><strong>Sin colorantes o conservantes artificiales.</strong></p>
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
          <div class="benefits-key-boxes benefits-key-boxes-chicken-puppy">
            <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosBeneficios-AyudaSistemaInmunologico.svg" alt="icono_1" width="57" height="57">

            <div class="benefit-text benefit-text-chicken">
              Ayuda al sistema inmunológico
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-chicken-puppy">
            <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosBeneficios-CrecimientoDesarrolloOptimo.svg" alt="icono_2" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Crecimiento y desarrollo óptimo
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-chicken-puppy">
            <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosBeneficios-FacilDigestion.svg" alt="icono_3" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Fácil digestión
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-chicken-puppy">
            <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosBeneficios-DesarrolloCerebroVision.svg" alt="icono_4" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Desarrollo de cerebro y visión
            </div>
          </div>
        </div>

        <div class=" colum-key colum2-key">
          <div class="benefits-key-boxes benefits-key-boxes-chicken-puppy">
            <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosBeneficios-IngredientesNaturales.svg" alt="icono_5" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Ingredientes naturales
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-chicken-puppy">
            <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosBeneficios-ProteinasCalidad.svg" alt="icono_6" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Proteínas de calidad
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-chicken-puppy">
            <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosBeneficios-Omega3yOmega6.svg" alt="icono_7" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Omega-3 y omega-6
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-chicken-puppy">
            <img src="{$module_img}BOSKE-Saco-PolloPuppy-iconosBeneficios-VitaminaE.svg" alt="icono_8" width="57" height="57">
            <div class="benefit-text benefit-text-chicken">
              Vitamina E (o tocoferoles)
            </div>
          </div>

        </div>
      </div>
    </div>

    <!--Ingredientes principales-->
    <div class="Ingredients-principales">
      <h4 class="ingredients">
        Ingredientes principales
      </h4>

      <div class="ingredients-boxes">
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-img-ingredientes-01.png" title="ingrediente1" alt="ingrediente1">
          <h3 class="ingredients-tittle">Pollo</h3>
        </div>
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-img-ingredientes-02.png" title="ingrediente2" alt="ingrediente2">
          <h3 class="ingredients-tittle">Arroz integral</h3>
        </div>

        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Saco-PolloPuppy-img-ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
          <h3 class="ingredients-tittle">Huevos enteros</h3>
        </div>
      </div>
    </div>

    <div class="product-faq">
      <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

      <div class="product-faq-item">
        <div class="product-faq-question">
          <span class="question">¿Y si no le gusta? ¿Y si lo rechaza?</span>
          <div class="product-faq-icon material-icons">expand_more</div>
        </div>
        <div class="product-faq-answer d-none">
          <p>Es una preocupación comprensible. Muchos cachorros vienen de piensos industriales con sabores artificiales, y les cuesta adaptarse a un alimento natural y honesto.<br />Boske está formulado con pollo, huevo y verdura fresca, no necesita disfrazarse. Tiene un perfil natural que a la mayoría de cachorros les encanta desde el primer cuenco.<br />Y si no le gusta, al menos sabrás que no es porque sea comida de baja calidad con saborizantes que engañan.</p>
        </div>
      </div>

      <div class="product-faq-item">
        <div class="product-faq-question">
          <span class="question">Mi cachorro tiene diarrea frecuente… ¿puede ayudar este pienso?</span>
          <div class="product-faq-icon material-icons">expand_more</div>
        </div>
        <div class="product-faq-answer d-none">
          <p>Sí. De hecho, es una de las razones por las que muchos veterinarios y dueños lo eligen. Las cacas blandas, los gases o el olor fuerte son signos de una digestión sobrecargada o ingredientes mal tolerados.<br />Boske contiene arroz integral como único cereal (nada de trigo o maíz), pulpa de remolacha, garrofa y otros ingredientes que favorecen la estabilidad digestiva.<br />En la mayoría de los casos, el cambio se nota en pocos días.</p>
        </div>
      </div>

      <div class="product-faq-item">
        <div class="product-faq-question">
          <span class="question">¿Es seguro cambiar de pienso si acaba de llegar a casa?</span>
          <div class="product-faq-icon material-icons">expand_more</div>
        </div>
        <div class="product-faq-answer d-none">
          <p>Sí. Siempre que se haga la transición correctamente, cambiar a mejor nunca es un error.
            Siete días mezclando poco a poco el pienso anterior con Boske es suficiente. Cuanto antes empiece con un alimento formulado con criterio, mejor para su salud a largo plazo.</p>
        </div>
      </div>

      <div class="product-faq-item">
        <div class="product-faq-question">
          <span class="question">Tiene picores o se rasca mucho. ¿Puede venir del pienso?</span>
          <div class="product-faq-icon material-icons">expand_more</div>
        </div>
        <div class="product-faq-answer d-none">
          <p>Puede que sí. Muchos piensos incluyen trigo, maíz, soja o subproductos animales que no solo no aportan, sino que generan inflamación, alergias o intolerancias.
            Boske elimina todo eso. Es un pienso limpio, directo, con ingredientes que ayudan a calmar el sistema digestivo y reducir reacciones cutáneas desde el interior.</p>
        </div>
      </div>

      <div class="product-faq-item">
        <div class="product-faq-question">
          <span class="question">¿Un pienso Low Grain es adecuado para cachorros?</span>
          <div class="product-faq-icon material-icons">expand_more</div>
        </div>
        <div class="product-faq-answer d-none">
          <p>Sí. Porque Low Grain no significa “sin energía”, significa “sin cereales innecesarios”.
            Boske contiene arroz integral, fácil de digerir, y lo acompaña con proteínas de alta calidad, verdura y frutas. No sobrecarga al cachorro. Le da lo que necesita, sin exceso.</p>
        </div>
      </div>

      <div class="product-faq-item">
        <div class="product-faq-question">
          <span class="question">¿Vale para razas pequeñas? ¿Y si mi cachorro es de raza grande?</span>
          <div class="product-faq-icon material-icons">expand_more</div>
        </div>
        <div class="product-faq-answer d-none">
          <p>Sí. Boske Puppy & Junior está diseñado para cachorros de cualquier tamaño.<br />Solo varía la cantidad diaria, pero la fórmula está equilibrada para dar soporte a su crecimiento en todas las fases.<br />Y su croqueta, de 0,8 cm, está pensada para que puedan masticarla sin dificultad, sea cual sea su tamaño.</p>
        </div>
      </div>

      <div class="product-faq-item">
        <div class="product-faq-question">
          <span class="question">Estoy usando otro pienso más conocido. ¿Merece la pena cambiar?</span>
          <div class="product-faq-icon material-icons">expand_more</div>
        </div>
        <div class="product-faq-answer d-none">
          <p>Depende de lo que estés buscando.<br />Si te interesa seguir comprando por marca, por costumbre o porque alguien te lo recomendó sin mirar el etiquetado, quizás Boske no es para ti.
            Boske no compite con la publicidad, compite con lo que hay dentro del saco.<br />Si eres de los que <strong>leen etiquetas y comparan ingredientes con sentido</strong>, aquí vas a encontrar una diferencia real.</p>
        </div>
      </div>

      <div class="product-faq-item">
        <div class="product-faq-question">
          <span class="question">¿Ofrecen garantía de satisfacción?</span>
          <div class="product-faq-icon material-icons">expand_more</div>
        </div>
        <div class="product-faq-answer d-none">
          <p>Sí, porque estamos seguros de lo que hacemos.<br />Boske no se sostiene sobre una promesa comercial, sino sobre resultados. Si después de probarlo tu cachorro no lo acepta o no le sienta bien, <strong>la marca garantiza la devolución.</strong><br /> No buscamos clientes indecisos. Buscamos cachorros sanos y dueños tranquilos. Y si eso significa que algunos prefieren otra marca, está bien. Boske no es para todos. Es para quienes saben lo que están buscando.</p>
        </div>
      </div>

    </div>

  </div>
</div>