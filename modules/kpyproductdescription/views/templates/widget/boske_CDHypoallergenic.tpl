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
                        Dieta Veterinara Seca
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
                        Pescado
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
    <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
        <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
            <div class="ration_expert_title">
                <img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Clinical Diet Hypoallergenic Pienso para Perro</p>
            </div>

            <div class="row">
                <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
                    Patata. Proteína de pescado blanco deshidratada 23%. Aceite de pescado. Guisantes. Semilla de linaza. Hidrolizado de pescado 3%. Proteína de guisantes. Pulpa de remolacha. Proteína de patata. Hidrolizado vegetal. Cloruro potásico. Inulina. Pared celular hidrolizada de levaduras (fuente de MOS).
                </div>
            </div>
        </div>

        <div style="border: 1px solid #eee;margin-top: 1%;"></div>
            <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
                <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Clinical Diet Hypoallergenic Pienso para Perro</p></div>

                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Proteína bruta
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    23 %
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
                    6.5 %
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Calcio
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    1.2 %
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Fósforo
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    3 %
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Opinión especialista -->
    <div class="Opinion-expert">

        <!-- Imagen de la especialista -->
        <div class="Opinion-expert-img">
            <img src="{$module_img}BOSKE-Saco-Light-img-Veterinaria.png" alt="especialista">
        </div>

        <div class="expert-text-box">

            <div class="ration_expert_title">
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
                <p>
                    Las <span class="bold">dietas veterinarias</span> son de gran importancia para la alimentación de las
                    mascotas con algún tipo de patología, problema o alergia. <span class="bold"> Boske Clinical
                    Diet Hypoallergenic</span> tiene como filosofía dar una alternativa saludable y de
                    calidad a los perros con alergias o intolerancias a algún tipo de alimento.
                </p>
                <p>
                    En su composición destaca el pescado blanco como principal ingrediente. Es
                    un tipo de proteína muy digestible y adecuada para perros con digestiones
                    sensibles.
                </p>
                <p>
                    Como componentes podemos destacar también ácidos grasos <span class="bold">omega 3 y
                    omega 6</span>, que disminuyen la inflamación y fortalecen la barrera cutánea.
                    Esta comida húmeda específica es una muy buena elección en el caso de
                    que tu mascotas sea alérgica o tenga estómago sensible.
                </p>
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
                <p>
                    <span class="bold">Boske Clinical Diet Hypoallergenic</span> es un alimento completo dietético de fácil
                    digestión y con un sabor exquisito, indicado para animales con alergias o
                    intolerancias alimentarias. Su receta está pensada para fortalecer la barrera
                    cutánea, gracias a los ácidos grasos Omega 3 y Omega 6 y mejorar la flora
                    intestinal mejorando así el tránsito intestinal con prebióticos como XOS Y MOS.
                    Formuladas con una única fuente proteica combinada con un componente para
                    aporte de almidón seleccionado pensando en evitar la probabilidad de
                    intolerancias y alergias.
                </p>

                <p>
                    La línea veterinaria de Boske está formulada por expertos veterinarios
                    nutricionistas, a través de exhaustivos controles de campo y rigurosas
                    investigaciones pensando en dar una alternativa saludable a las mascotas con
                    patologías específicas.
                </p>
            </div>
        </div>

        <div class="additional-info-box">
            <div class="text-additional-info">
                <p>
                    Los problemas de alergias o intolerancias alimentarias se dan cada vez con más
                    frecuencia en nuestras mascotas. El pescado blanco es un ingrediente bajo en
                    alérgenos, muy rico en ácidos grasos Omega 3 que reducen y controlan la
                    inflamación.
                </p>
                <p>
                    <span class="bold">Boske Clinical Diet Hypoallergenic</span> es una dieta natural, ideal para perros
                    intolerancias y alergias. La receta, libre de grano ha sido ideada con ingredientes
                    bajos en alérgenos, y potenciadores del sistema inmunitario. Su fórmula con
                    ingredientes naturales de gran calidad aporta los nutrientes adecuados para
                    apoyar la salud sin olvidar la principal preocupación de tu mascota “el sabor”,
                    combinando sabrosos ingredientes con texturas y aromas tentadoras a los que
                    tu mascota no se podrá resistir.
                </p>
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
                        Aditivos nutricionales
                    </h2>
                    <p>
                        Vitamina A 18500 UI/kg. Vitamina D3 1500 UI/kg. Vitamina E 250 mg/kg. Vitamina C
                        125 mg/kg. Hierro (sulfato de hierro (II) monohidratado) 68 mg/kg. Yodo (yoduro
                        potásico) 3,2 mg/kg. Cobre (sulfato de cobre (II) pentahidratado) 9 mg/kg.
                        Manganeso (sulfato manganoso monohidratado) 6,8 mg/kg. Zinc (óxido de zinc)
                        108 mg/kg. Selenio (selenito de sodio) 0,11 mg/kg.
                    </p>
                    <p>
                        <span class="bold">Aditivos tecnológicos:</span>
                    </p>
                    <p>
                        Antioxidantes: Extractos de tocoferoles de aceites vegetales.
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
        <div class="additional-info-box">
            <div class="text-additional-info">
                <h3 class="color-title-other-products ">
                    ¿Cuáles son las principales patologías para poder dar este producto a mi perro?
                </h3>
                <p>
                    <span class="bold">Boske Clinical Diet Hypoallerfenic</span> está indicado cuando nuestra mascota sufre
                    alergias o intolerancias alimentarias.
                </p>
                <p>
                    La diferencia más notable entre una alergia y una intolerancia es que en las
                    alergias está involucrado el sistema inmune, mientras que las intolerancias son
                    reacciones fisiológicas anormales del organismo frente a un alimento. Ambas
                    patologías normalmente producen síntomas gastrointestinales como diarrea o
                    problemas de piel.
                </p>
                <p>
                    Recomendamos consultar a un especialista cualquiera de las patologías
                    anteriormente descritas.
                </p>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Saco-Hypoallergenic-img-fotoPerro-04.png" title="perro4" alt="perro4">
            </div>
        </div>

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
    </div>
</div>