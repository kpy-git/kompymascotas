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
            <span class="colnumber">03.</span>{l s='Necesidades específicas' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Problemas alérgicos e intolerancias, problemas de pelo y piel
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
            Sin cereales, hipoalergénico
          </div>
        </div>
      </div>

      <div class="colum colum3">
        <div class="info-box">
          <div class="colname">
            <span class="colnumber">09.</span>{l s='Ingrediente principal' d='Modules.Kpyproductdescription.Shop'}
          </div>

          <div class="colinfo">
            Salmón
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
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosIlustrados-CarneFrescaSalmon.svg" alt="beneficio1">
          <div>Carne fresca de salmón</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosIlustrados-ProteinaAnimal.svg" alt="beneficio2">
          <div>Proteína animal de alto valor biológico</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosIlustrados-IngredientesBajosAlergenos.svg" alt="beneficio3">
          <div>Ingredientes bajos en alérgenos</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosIlustrados-PielPeloBrillantes.svg" alt="beneficio4">
          <div>Piel y pelo brillantes</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosIlustrados-VitaminasyMinerales.svg" alt="beneficio5">
          <div>Vitaminas y minerales</div>
        </div>

        <div class="benefit-box">
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosIlustrados-NoLlevaGrano.svg" alt="beneficio6">
          <div>No lleva grano de ningún tipo</div>
        </div>

      </div>
    </div>

  </div>

  <!--Tabla de raciones-->
  <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
    <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Adult Grain Free Salmón Pienso Natural para Perro</p></div>
      <div class="row">
        <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
          Salmón preparado en fresco 30%. Patata. Proteína de salmón deshidratada. Aceite de pescado. Pulpa de remolacha. Semilla de linaza. Proteína de patata. Hidrolizado de pescado. Pulpa de manzana. Levaduras. Zanahoria. Brocoli. Arándano. Minerales. Inulina. Pared celular hidrolizada de levaduras (fuente de MOS). Extractos de cítricos. Jengibre. Yucca schidigera. Caléndula (fuente de luteina).
        </div>
      </div>
    </div>

    <div style="border: 1px solid #eee;margin-top: 1%;"></div>
    <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Adult Grain Free Salmón Pienso Natural para Perro</p></div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Proteína bruta
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          27 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Materias grasas
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          18 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Fibra bruta
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          4 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Cenizas Brutas
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          7.5 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Calcio
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          1.5 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Fósforo
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          1 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Omega-3
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          3.75 %
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          Omega-6
        </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
        <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
          2.20 %
        </div>
      </div>
    </div>

    <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto">
      <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-RacionDiaria.svg"><p class="purple">Ración diaria Boske Adult Grain Free Salmón Pienso Natural para Perro</p></div>
      <table id="tabla" data-producto="8299-92467" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">

        <thead>
        <tr><th></th><th style="color: #734e7f;"><center>1kg</center></th><th style="color: #734e7f;"><center> 5kg</center></th><th style="color: #734e7f;"><center> 10kg</center></th><th style="color: #734e7f;"><center> 15kg</center></th><th style="color: #734e7f;"><center> 20kg</center></th><th style="color: #734e7f;"><center>25kg</center></th><th style="color: #734e7f;"><center> 30kg</center></th><th style="color: #734e7f;"><center> 40kg</center></th><th style="color: #734e7f;"><center> 50kg</center></th><th style="color: #734e7f;"><center>60kg</center></th></tr></thead>
        <tbody><tr><td style="color: #734e7f;">Baja actividad</td><td><center>25g</center></td><td><center>82</center></td><td><center>138g</center></td><td><center>188g</center></td><td><center>233g</center></td><td><center>275g</center></td><td><center>315g</center></td><td><center>391g</center></td><td><center>463g</center></td><td><center>531g</center></td></tr><tr><td style="color: #734e7f;"> Media actividad</td><td><center>28g</center></td><td><center>85g</center></td><td><center>160g</center></td><td><center>217g</center></td><td><center>270g</center></td><td><center>319g</center></td><td><center>365g</center></td><td><center>453g</center></td><td><center>536g</center></td><td><center>614g</center></td></tr><tr><td style="color: #734e7f;"> Alta actividad</td><td><center>35g</center></td><td><center>117g</center></td><td><center>197g</center></td><td><center>267g</center></td><td><center>331g</center></td><td><center>391g</center></td><td><center>448g</center></td><td><center>556g</center></td><td><center>658g</center></td><td><center>754g</center></td></tr></tbody>
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
        <p>En los últimos años he atendido cada vez más perros con problemas de intolerancias digestivas, picores recurrentes o sensibilidades alimentarias sin diagnóstico claro. En muchos casos, el cambio a un <strong>pienso monoproteico, sin cereales y con proteína de alta calidad</strong> marca una diferencia real.</p>
        <p>La fórmula de Boske Grain Free de Salmón me parece muy equilibrada: combina <strong>proteína animal fresca de alto valor biológico</strong> con ingredientes funcionales que no solo nutren, sino que previenen. El <strong>contenido en Omega 3 y 6</strong> —procedente del salmón y del aceite de pescado— apoya directamente la salud del pelaje y la piel, que suelen ser los primeros indicadores visibles cuando un perro no tolera bien su alimento.</p>
        <p>Además, al ser una receta sin cereales y sin alérgenos comunes, suele funcionar bien en perros con historial de alergias o digestiones sensibles.
          No es un alimento “ligero” o simplificado. Es una fórmula <strong>nutricionalmente completa</strong>, bien construida y pensada para quienes no buscan lo mínimo, sino lo adecuado.</p>
      </div>
    </div>
  </div>

  <!--Fotos Perro y piensos-->
  <div class="DogCatHeader">
    <img class="DogCatHeader1" src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-Cabecera-Salmon.png" alt="CabeceraPerro">
    <img class="DogCatHeader2" src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-Bodegon-Sacos.png" alt="BodegonPiensos">
  </div>

  <!--Informacion de mostrar todo-->
  <div class="show-all">

    <div class="additional-info-box additional-info-box-border-color">
      <div>
        <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-fotoPerro-01.png" title="perro1" alt="perro1">
      </div>

      <div class="text-additional-info">
        <h5>Más cerca de su instinto. Más lejos de los ingredientes que no necesita</h5>
        <p>Boske Grain Free Salmón es un alimento completo y 100% natural, formulado sin cereales y con <strong>salmón fresco deshuesado como primer ingrediente.</strong> Una fuente de proteína animal de alto valor biológico, rica en <strong>Omega 3, EPA y DHA</strong>, ideal para mantener una musculatura firme, unas articulaciones protegidas y un pelaje visiblemente más brillante.</p>
        <p>Esta receta ha sido diseñada para alimentar a tu perro de forma instintiva, como lo haría la naturaleza, pero con el respaldo de la ciencia nutricional moderna. Por eso no contiene trigo, maíz, ni otros cereales que puedan inflamar o alterar la digestión. Es una opción especialmente recomendada para perros con <strong>alergias alimentarias, piel sensible o digestiones delicadas.</strong></p>
      </div>
    </div>

    <div class="additional-info-box">
      <div class="text-additional-info">
        <h5>Alimento limpio, energía visible.</h5>
        <p>La receta incluye <strong>vegetales frescos y botánicos silvestres</strong> que aportan antioxidantes, vitaminas y minerales esenciales, además de ayudar a eliminar toxinas de forma natural. El resultado es una fórmula que <strong>reduce los niveles de colesterol, estabiliza el azúcar en sangre</strong> y aporta una energía constante durante todo el día, sin picos ni bajones.</p>
        <p>Gracias a ingredientes como la <strong>linaza (4%) y el aceite de pescado</strong>, Boske Grain Free Salmón proporciona un perfil lipídico rico en <strong>Omega 3 (3,75%) y Omega 6 (2,20%)</strong>, incluyendo <strong>DHA (7.500 mg/kg)</strong>, que favorece la salud de la piel, el brillo del pelaje y el desarrollo del sistema nervioso y ocular.<br />
          Además, la inclusión de <strong>condroprotectores</strong> naturales apoya la salud articular, permitiendo que tu perro se mantenga activo, ágil y con un movimiento fluido, incluso con el paso de los años.<br />
          Sin colorantes ni conservantes artificiales. Solo lo que aporta.</p>
      </div>
      <div>
        <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-fotoPerro-02.png" title="perro2" alt="perro2">
      </div>
    </div>

    <div class="additional-info-box">
      <div>
        <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-fotoPerro-03.png" title="perro3" alt="perro3">
      </div>

      <div class="text-additional-info">
        <div>
          <h2 class="bold">
            Modo de empleo
          </h2>
          <p>
            El cambio de alimentación debe realizarse de forma paulatina a lo largo de 7 días.
            Proporcionar la ración diaria, preferiblemente 2 o 3 veces al día y siempre ala
            misma hora. La cantidad de alimento puede variar según la actividad, la raza del
            perro y las condiciones ambientales. El perro siempre debe tener agua limpia y
            fresca.
          </p>
          <h2 class="bold">
            Aditivos nutricionales
          </h2>
          <p>
            Vitamina A 20.000 UI/kg. Vitamina D3 1800 mg/kg. Vitamina E 200 mg/kg. Vitamina
            C 100 mg/kg. Ferro (sulfato de ferro (II) mono-hidratado) 75 mg/kg. Iodo (iodeto de
            potasio) 3,5 mg/kg. Cobre (sulfato de cobre (II) heptahidratado) 10 mg/kg.
            Manganês (monohidrato desulfato de manganés) 7,5 mg/kg. Zinc (óxido de zinc)
            120 mg/kg. Zinc (metionato de zinc) 30 mg/kg. Selenio (selenito sódico) 0,12 mg/kg.
            Taurina 750 mg/kg.
          </p>
          <p>
            <span class="bold">Aditivos tecnológicos:</span>
          </p>
          <p>
            Energía metabolizable: 3.860 kcal/kg
          </p>
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
          <div class="benefits-key-boxes benefits-key-boxes-salmon">
            <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosBeneficios-CarneFrescaSalmon.svg" alt="icono_1" width="57" height="57">

            <div class="benefit-text benefit-text-salmon">
              <span class="bold">Carne fresca de salmón</span> que aporta proteínas,
              aminoácidos y ácidos grasos esenciales.
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-salmon">
            <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosBeneficios-ProteinaAnimal.svg" alt="icono_2" width="57" height="57">
            <div class="benefit-text benefit-text-salmon">
              <span class="bold">Proteína animal de alto valor biológico</span> como principal ingrediente.
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-salmon">
            <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosBeneficios-NoLlevaGrano.svg" alt="icono_3" width="57" height="57">
            <div class="benefit-text benefit-text-salmon">
              <span class="bold">No lleva grano</span> de ningún tipo.
            </div>
          </div>
        </div>

        <div class=" colum-key colum2-key">
          <div class="benefits-key-boxes benefits-key-boxes-salmon">
            <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosBeneficios-PielPeloBrillantes.svg" alt="icono_4" width="57" height="57">
            <div class="benefit-text benefit-text-salmon">
              <span class="bold">Piel y pelo brillante</span> gracias al aceite de pescado rico en omega 3 (8%).
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-salmon">
            <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosBeneficios-IngredientesBajosAlergenos.svg" alt="icono_5" width="57" height="57">
            <div class="benefit-text benefit-text-salmon">
              Contiene ingredientes <span class="bold">bajos en alérgenos.</span>
            </div>
          </div>

          <div class="benefits-key-boxes benefits-key-boxes-salmon">
            <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosBeneficios-FrutasVerduras.svg" alt="icono_6" width="57" height="57">
            <div class="benefit-text benefit-text-salmon">
              <span class="bold">Frutas y verduras</span> como aporte de vitaminas y minerales.
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
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-Ingredientes-Salmon.png" title="ingrediente1" alt="ingrediente1">
          <h3 class="ingredients-tittle">Salmón fresco</h3>
        </div>
        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-Ingredientes-AceitePescado.png" title="ingrediente2" alt="ingrediente2">
          <h3 class="ingredients-tittle">Aceite de pescado</h3>
        </div>

        <div class="ingredient-box">
          <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-Ingredientes-Remolacha.png" title="ingrediente3" alt="ingrediente3">
          <h3 class="ingredients-tittle">Pulpa de remolacha</h3>
        </div>
      </div>
    </div>

    <div class="product-faq">
      <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>
        {include file="components/accordition.tpl" list=$faq class="product-faq"}

    </div>
  </div>
</div>