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
                        Light & fit, low grain
                    </div>
                </div>

                <div class="info-box">
                    <div class="colname">
                        <span class="colnumber">03.</span>{l s='Necesidades específicas' d='Modules.Kpyproductdescription.Shop'}
                    </div>

                    <div class="colinfo">
                        Pérdida de peso o mantenimiento de peso óptimo
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
                        Bajo en calorías
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
                    <img src="{$module_img}BOSKE-Saco-Light-iconosIlustrados-ControlPeso.svg" alt="beneficio1">
                    <div>Controla el peso</div>
                </div>
                
                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Light-iconosIlustrados-LCarnitina.svg" alt="beneficio2">
                    <div>Contiene L-carnitina</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Light-iconosIlustrados-IngredientesNaturales.svg" alt="beneficio3">
                    <div>Ingredientes naturales</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Light-iconosIlustrados-ProteinasCalidad.svg" alt="beneficio4">
                    <div>Proteínas de calidad</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Light-iconosIlustrados-Omega.svg" alt="beneficio5">
                    <div>Omega-3 y Omega-6</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Light-iconosIlustrados-ProteccionDigestivo.svg" alt="beneficio6">
                    <div>Protección al sistema digestivo</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Light-iconosIlustrados-Tocoferoles.svg" alt="beneficio7">
                    <div>Vitamina E (o tocoferoles)</div>
                </div>
            </div>
        </div>

    </div>

    <!--Tabla de raciones-->
    <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">                
        <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Adult Light &amp; Fit Low Grain Pienso Natural para Perro</p></div>
            <div class="row">
                <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
                Proteína de pollo deshidratada 30%. Arroz 29%. Salvado de arroz. Pulpa de manzana 10%. Pulpa de remolacha 9%. Hidrolizado de hígado de pollo 3%. Grasa de ave. Semilla de linaza. Aceite de pescado 1%. Huevo entero deshidratado 0,5%. Levaduras. Minerales. Pared celular hidrolizada de levaduras (fuente de MOS), extractos de cítricos. Yucca schidigera 0,05%
                </div>
            </div>
        </div>

        <div style="border: 1px solid #eee;margin-top: 1%;"></div>
            <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
                <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Adult Light &amp; Fit Low Grain Pienso Natural para Perro</p></div>
                
                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Proteína bruta
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    26.5 %
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Materias grasas
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    10 %
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Fibra bruta
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    5.5 %
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Cenizas Brutas
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    8 %
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Calcio
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    1.4 %
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
                    0.4 %
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    Omega-6
                    </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                    <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                    2.5 %
                    </div>
                </div>
            </div>

            <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto">
                <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-RacionDiaria.svg"><p class="purple">Ración diaria Boske Adult Light &amp; Fit Low Grain Pienso Natural para Perro</p></div>
                <table id="tabla" data-producto="8300-92470" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">
                    <thead>
                    <tr><th></th><th style="color: #734e7f;"><center>2kg </center></th><th style="color: #734e7f;"><center>5kg</center></th><th style="color: #734e7f;"><center>10kg</center></th><th style="color: #734e7f;"><center>15kg</center></th><th style="color: #734e7f;"><center>20kg</center></th><th style="color: #734e7f;"><center>30kg</center></th><th style="color: #734e7f;"><center>40kg</center></th><th style="color: #734e7f;"><center>60kg</center></th><th style="color: #734e7f;"><center>80kg</center></th></tr></thead>
                    <tbody><tr><td style="color: #734e7f;">Mantenimiento</td><td><center>45g</center></td><td><center>90g</center></td><td><center>151g</center></td><td><center>205g</center></td><td><center>254g</center></td><td><center>344g</center></td><td><center>427g</center></td><td><center>579g</center></td><td><center>719g</center></td></tr><tr><td style="color: #734e7f;"> Pérdida de peso</td><td><center>27g</center></td><td><center>54g</center></td><td><center>91g</center></td><td><center>123g</center></td><td><center>152g</center></td><td><center>207g</center></td><td><center>256g</center></td><td><center>348g</center></td><td><center>431g </center></td></tr></tbody>
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
                    <img src="{$urls.img_url}PYM-ico-OpinionEspecialista.svg" title="product_opinion_especialista" alt="producto_opinion_especialista" width="57" height="57">
                </div>

                <div class="Opinion-expert-title">                    
                    <p class="purple">La opinión de nuestra especialista</p>
                    <p class="Opinion-expert-subtitle">Por <span class="bold">Fara Duarte</span></p>
                </div>
            </div>
            
            
            <!-- Texto de la especialista -->
            <div class="Opinion-expert-text">
                <p>"Hay dos momentos en los que más errores se cometen con la alimentación: cuando el perro tiene sobrepeso, y cuando empieza a envejecer."</p>
                <p>En consulta veo constantemente dos tipos de dueños.<br />Uno, el que llega preocupado porque su perro ha ganado peso, y no sabe cómo ayudarle sin quitarle lo que le gusta.<br />Otro, el que ha notado que su perro mayor ya no digiere igual, que pierde músculo, que tiene menos energía… y teme que todo vaya a peor.</p>
                <p>En ambos casos, la respuesta no puede ser “menos comida” o “pienso light cualquiera”. Tiene que ser un alimento con criterio, adaptado, y que no castigue ni la salud ni el disfrute.</p>
                <p><strong>Boske Light & Fit</strong> es una opción que recomiendo porque <strong>no se limita a bajar grasa.</strong> Protege la musculatura, regula el sistema digestivo, incluye L-carnitina para el metabolismo de las grasas, y tiene un perfil de ingredientes limpio y funcional.<br /> Es bajo en calorías, sí. Pero sigue siendo un pienso completo, <strong>natural y bien equilibrado</strong>, que no hace que el perro coma por obligación, sino porque le sienta bien.</p>
                <p>He visto perros recuperar su movilidad, reducir el peso poco a poco sin debilitarse, y perros mayores con mejor pelo, mejores cacas, y mejor ánimo.<br />Y eso —más allá de los gramos o los años— es lo que realmente cuenta.</p>
                <p>"No se trata solo de vivir más. Se trata de vivir mejor. Por eso recomiendo Boske Light & Fit."</p>
            </div>
        </div>
    </div>

    <!--Fotos Perro y piensos-->
    <div class="DogCatHeader">
        <img class="DogCatHeader1" src="{$module_img}BOSKE-Saco-Light-img-Cabecera-Light.png" alt="CabeceraPerro">
        <img class="DogCatHeader2" src="{$module_img}BOSKE-Saco-Light-img-Bodegon.png" alt="BodegonPiensos">
    </div>

    <!--Informacion de mostrar todo-->
    <div class="show-all">
        
        <div class="additional-info-box additional-info-box-border-color">
            <div>
                <img src="{$module_img}BOSKE-Saco-Light-img-fotoPerro-01.png" title="perro1" alt="perro1">
            </div>            
            
            <div class="text-additional-info">
                <h5>Perder peso o envejecer no debería significar comer peor.</h5>
                <p>Por eso existe Boske Light & Fit: un alimento 100% natural, formulado para perros con sobrepeso o perros senior que necesitan <strong>más ligereza sin menos nutrición.</strong></p>
                <p>Esta receta <strong>Súper Premium</strong> combina <strong>pollo de corral y huevo entero deshidratado</strong> con frutas y verduras frescas, para ofrecer <strong>una alimentación equilibrada, baja en calorías, pero rica en lo que realmente importa</strong>: proteínas de calidad, energía estable, y digestiones fáciles.</p>
                <p>Es natural en origen (todos los ingredientes son aptos para consumo humano), y natural en lógica: <strong>nada de trigo, maíz o soja, ni conservantes ni colorantes artificiales. Solo lo que le sienta bien.</strong></p>
                <p>Porque moverse menos no significa vivir menos. Y comer menos no significa comer peor.</p>
            </div>
        </div>
        
        <div class="additional-info-box">
            <div class="text-additional-info">
                <h5>Boske Light & Fit no solo reduce calorías, regula el cuerpo.</h5>
                <p> Su fórmula incluye <strong>fuentes naturales de fibra</strong> que ayudan a que el perro se sienta saciado tras las comidas, sin hinchazón ni pesadez. Y gracias a la <strong>L-carnitina</strong>, favorece el metabolismo normal de las grasas, ayudando a preservar la masa muscular mientras se pierde peso.</p>
                <p>Contiene ingredientes funcionales como <strong>pulpa de remolacha</strong>, que protege el sistema digestivo y aporta energía sostenida; y <strong>manzana y garrofa</strong>, que ayudan a <strong>reducir el colesterol y estabilizar el azúcar en sangre.</strong></p>
                <p>A eso se suma el <strong>aceite de pescado</strong>, rico en omega-3, omega-6, EPA y DHA, fundamentales para mantener una piel sana, un pelaje brillante y un cerebro activo. Y su croqueta, de 1,5 cm, está diseñada para adaptarse al ritmo y a la mordida de perros adultos, sin forzar sus dientes ni mandíbulas.</p>
                <p><strong>Porque en esta etapa de su vida, todo tiene que sumar. Nunca restar.</strong></p>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Saco-Light-img-fotoPerro-02.png" title="perro2" alt="perro2">
            </div>
        </div>

        <div class="additional-info-box">
            <div>
                <img src="{$module_img}BOSKE-Saco-Light-img-fotoPerro-03.png" title="perro3" alt="perro3">
            </div>
            
            <div class="text-additional-info">
                <div>
                    <h2 class="bold">
                        Modo de empleo
                    </h2>
                    <p>
                        El cambio de comida debe hacerse gradualmente durante 7 días. Proporcionar la
                        ración diaria, preferiblemente, dividida en 2 o 3 tomas al día y siempre a la misma
                        hora. La cantidad de alimento puede variar según la actividad, la raza del perro y
                        las condiciones ambientales. El perro debe tener siempre agua limpia y fresca. Se
                        recomienda una vida activa y saludable y caminar varios días a la semana.
                    </p>
                    <h2 class="bold">
                        Aditivos nutricionales
                    </h2>
                    <p>
                        Vitamina A 20000 UI/kg. Vitamina D3 2000 mg/kg. Vitamina E 600 mg/kg. Hierro
                        (Hierro (II) sulfato monohidrato) 75 mg/kg. Yodo(yoduro de potasio) 3,5 mg/kg.
                        Cobre (sulfato de cobre (II) heptahidratado) 10 mg/kg. Manganeso (sulfato de
                        manganeso monohidrato) 7,5 mg/kg. Zinc(óxido de zinc) 120 mg/kg. Selenio
                        (selenito de sodio) 0,12 mg/kg. Taurina 750 mg/kg. L-carnitina 70 mg/kg.
                        Energía metabolizable 3.350 kcal/kg
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
                    <div class="benefits-key-boxes benefits-key-boxes-light">
                        <img src="{$module_img}BOSKE-Saco-Light-iconosBeneficios-PesoOptimo.svg" alt="icono_1" width="57" height="57">
                        
                        <div class="benefit-text benefit-text-light">
                            Mantiene el peso óptimo. Gracias a su composición baja en grasa.
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-light">
                        <img src="{$module_img}BOSKE-Saco-Light-iconosBeneficios-LCarnitina-02.svg" alt="icono_2" width="57" height="57">
                        <div class="benefit-text benefit-text-light">
                            Contiene L-carnitina para apoyar el metabolismo
                            normal de las grasas y la masa muscular magra.
                            Ayuda a mantener un estilo de vida activo.
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-light">
                        <img src="{$module_img}BOSKE-Saco-Light-iconosBeneficios-Omega-02.svg" alt="icono_3" width="57" height="57">
                        <div class="benefit-text benefit-text-light">
                            Omega 3 (0,4%) y Omega 6 (2,5%), EPA y DHA (400
                            mg/kg) para una piel y un pelo saludable y brillante.
                            Con aceites y grasas esenciales de pescado y pollo.
                        </div>
                    </div>
                </div>

                <div class=" colum-key colum2-key">
                    <div class="benefits-key-boxes benefits-key-boxes-light">
                        <img src="{$module_img}BOSKE-Saco-Light-iconosBeneficios-FuenteFibra.svg" alt="icono_4" width="57" height="57">
                        <div class="benefit-text benefit-text-light">
                            Fuentes naturales de fibra. Ayudan a los perros a
                            sentirse saciados. Reduce el colesterol y normaliza los
                            niveles de azúcar en sangre.
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-light">
                        <img src="{$module_img}BOSKE-Saco-Light-iconosBeneficios-ProteccionDigestivo-02.svg" alt="icono_5" width="57" height="57">
                        <div class="benefit-text benefit-text-light">
                            Protección al sistema digestivo. Con pulpa de
                            remolacha (9%) que aporta energía y fibra.
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-light">
                        <img src="{$module_img}BOSKE-Saco-Light-iconosBeneficios-Tocoferoles-02.svg" alt="icono_6" width="57" height="57">
                        <div class="benefit-text benefit-text-light">
                            Vitamina E (o tocoferoles). Conservador natural que
                            ayuda a mantener la piel sana y sirve como
                            antioxidante natural.
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
                    <img src="{$module_img}BOSKE-Saco-Light-img-ingredientes-ProteinaPollo.png" title="ingrediente1" alt="ingrediente1">
                    <h3 class="ingredients-tittle">Proteína de pollo</h3>
                </div>
                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-Light-img-ingredientes-Arroz.png" title="ingrediente2" alt="ingrediente2">
                    <h3 class="ingredients-tittle">Arroz</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-Light-img-ingredientes-Manzana.png" title="ingrediente3" alt="ingrediente3">
                    <h3 class="ingredients-tittle">Manzana</h3>
                </div>
            </div>
        </div>

        <div class="product-faq">
            <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

            {include file="components/accordition.tpl" list=$faq class="product-faq"}

        </div>

    </div>
</div>