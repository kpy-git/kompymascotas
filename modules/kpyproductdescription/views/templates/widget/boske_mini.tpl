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
                        Alimento para perros de raza pequeña
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
                        Pequeño
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
                        Pollo y pavo
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
                <img src="{$module_img}ico-benefits.svg" title="Beneficios" alt="product_beneficios" width="57" height="57">
                <span>Beneficios</span>
            </h3>

            <div class="benefits-boxes">
                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-PolloMini-iconosIlustrados-AporteEnergiaOptimo.svg" alt="beneficio1">
                    <div>Aporte de energía óptimo</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-PolloMini-iconosIlustrados-IngredientesNaturales.svg" alt="beneficio2">
                    <div>Ingredientes naturales</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-PolloMini-iconosIlustrados-ProteinasCalidad.svg" alt="beneficio3">
                    <div>Proteínas de calidad</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-PolloMini-iconosIlustrados-ProteccionSistemaDigestivo.svg" alt="beneficio4">
                    <div>Protección al sistema digestivo</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-PolloMini-iconosIlustrados-EPAyDHA.svg" alt="beneficio5">
                    <div>EPA y DHA. Pelo y piel sana y brillante</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-PolloMini-iconosIlustrados-VitaminaE.svg" alt="beneficio6">
                    <div>Vitamina E. Conservador natural</div>
                </div>
            </div>
        </div>

    </div>

    <!--Tabla de raciones-->
    <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
        <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Adult Mini Chicken &amp; Turkey Low Grain Pienso Natural para Perro</p></div>
            <div class="row">
                <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
                Proteína de ave deshidratada 37% (pollo 24%, pavo 13%). Arroz integral 34%. Grasa de ave 8.5%. Pulpa de remolacha 6%. Harina de algarroba 5%. Pulpa de manzana 4%. Hidrolizado de hígado de pollo 3%. Huevo entero deshidratado 1%. Levaduras 0.5%. Aceite de pescado 0,5%. Minerales. Glucosamina 0,1%. Sulfato de condroitina 0,04%. Yucca schidigera 0,03%.
                </div>
            </div>
        </div>

        <div style="border: 1px solid #eee;margin-top: 1%;"></div>
        <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Adult Mini Chicken &amp; Turkey Low Grain Pienso Natural para Perro</p></div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Proteína bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                30%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Materias grasas
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                16%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Omega-3
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                0.25%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Omega-6
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                2.8%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Fibra bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                3.75%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Cenizas Brutas
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                9%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Calcio
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                2.1%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Fósforo
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                1.4%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                DHA
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                410 mg/kg
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                EPA
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                410 mg/kg
                </div>
            </div>
        </div>

        <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-RacionDiaria.svg"><p class="purple">Ración diaria Boske Adult Mini Chicken &amp; Turkey Low Grain Pienso Natural para Perro</p></div>
            <table id="tabla" data-producto="8852-93380" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">

            <thead>
            <tr><th></th><th style="color: #734e7f;"><center>0.5kg</center></th><th style="color: #734e7f;"><center> 1kg</center></th><th style="color: #734e7f;"><center>2kg</center></th><th style="color: #734e7f;"><center>4kg</center></th><th style="color: #734e7f;"><center>6kg</center></th><th style="color: #734e7f;"><center>8kg</center></th><th style="color: #734e7f;"><center>10kg</center></th></tr></thead>
            <tbody><tr><td style="color: #734e7f;">actividad baja</td><td><center>15g</center></td><td><center>26g</center></td><td><center>43g</center></td><td><center>72g</center></td><td><center>98g</center></td><td><center>121g</center></td><td><center>143g</center></td></tr><tr><td style="color: #734e7f;"> actividad media</td><td><center>18g</center></td><td><center>30g</center></td><td><center>50g</center></td><td><center>84g</center></td><td><center>113g</center></td><td><center>140g</center></td><td><center>166g</center></td></tr><tr><td style="color: #734e7f;"> actividad alta</td><td><center>22g</center></td><td><center>36g</center></td><td><center>61g</center></td><td><center>103g</center></td><td><center>139g</center></td><td><center>172g</center></td><td><center>204g</center></td></tr></tbody>
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
                <p>Muchos dueños creen que están alimentando bien a su perro solo porque compran un pienso ‘de marca’. Y luego vienen las sorpresas: alergias, problemas digestivos, piel seca, falta de energía... Lo que de verdad importa no es la marca, sino lo que hay dentro del saco.</p>

                <p>Por eso recomiendo <strong>Boske Mini</strong>. Es un pienso pensado para perros pequeños, pero con exigencias grandes. Aporta <strong>proteína de verdad</strong> (pollo, pavo y huevo entero deshidratado), no harinas de dudosa procedencia. Está formulado con <strong>arroz integral en lugar de cereales pesados</strong> como el trigo o el maíz, lo que lo hace mucho más digestivo y reduce las alergias. Además, contiene <strong>glucosamina y condroitina</strong>, algo que pocos piensos incluyen en su versión para razas mini, pero que es clave para prevenir problemas articulares.</p>

                <p>Si buscas un pienso que de verdad marque la diferencia en la salud de tu perro, Boske Mini es una apuesta segura. No se trata de pagar más o menos, sino de pagar por calidad real. Y eso, al final, se nota en la vitalidad, el pelaje y la salud digestiva de tu perro. Como veterinaria, prefiero prevenir problemas antes que tratarlos. Y este pienso ayuda precisamente a eso.</p>
            </div>
        </div>
    </div>

    <!--Fotos Perro y piensos-->
    <div class="DogCatHeader">
        <img class="DogCatHeader1" src="{$module_img}BOSKE-Saco-PolloMini-img-Cabecera-PolloMini.png" alt="CabeceraPerro">
        <img class="DogCatHeader2" src="{$module_img}BOSKE-Saco-PolloMini-img-Bodegon-Sacos.png" alt="BodegonPiensos">
    </div>

    <!--Informacion de mostrar todo-->
    <div class="show-all">

        <div class="additional-info-box additional-info-box-border-color">
            <div>
                <img src="{$module_img}BOSKE-Saco-PolloMini-img-Cabecera-Perro01.png" title="perro1" alt="perro1">
            </div>

            <div class="text-additional-info">
                <p>Alimenta las aventuras diarias de tu perro de raza mini con <strong>Boske Mini</strong>, el alimento <strong>Súper Premium</strong> formulado con <strong>ingredientes naturales de alta calidad</strong>. Su exclusiva combinación de <strong>pollo, pavo y huevo entero deshidratado</strong> proporciona la cantidad óptima de proteínas para mantener su energía y vitalidad a lo largo del día.</p>
                <p>A diferencia de otros piensos, <strong>Boske Mini apuesta por una nutrición que respeta el bienestar digestivo de tu mascota</strong>. Su fórmula <strong>"Low Grain"</strong>, elaborada por expertos veterinarios y nutricionistas, reduce el riesgo de alergias alimentarias gracias al uso de <strong>arroz integral</strong>, un cereal mucho más fácil de digerir que el trigo o el maíz. Además, está <strong>libre de soja, conservantes artificiales y colorantes</strong>, garantizando un alimento puro y equilibrado.</p>
                <p>Para ofrecer la mejor calidad, todos los ingredientes provienen de granjas locales, lo que no solo asegura la frescura de cada bocado, sino que también minimiza el impacto ecológico. <strong>Con Boske Mini, tu perro disfruta de un alimento saludable, natural y adaptado a sus necesidades.</strong></p>
            </div>
        </div>

        <div class="additional-info-box">
            <div class="text-additional-info">
                <p><strong>Boske Mini</strong> está formulado con ingredientes naturales cuidadosamente seleccionados para <strong>reforzar la salud y el bienestar de tu perro mini en cada bocado</strong>. Su composición incluye <strong>pulpa de remolacha</strong>, un ingrediente clave que no solo protege su sistema digestivo, sino que también le proporciona un extra de energía para su día a día.</p>
                <p>Además, contiene <strong>manzana</strong>, un superalimento natural que contribuye a <strong>reducir los niveles de colesterol y estabilizar el azúcar en sangre</strong>, favoreciendo un metabolismo equilibrado y una mejor salud general. Para el cuidado de sus articulaciones, Boske Mini incorpora <strong>glucosamina y condroitina</strong>, esenciales para la formación y reparación del cartílago, ayudando a prevenir el desgaste y asegurando una movilidad óptima a lo largo de los años.</p>
                <p><strong>El aceite de pescado y los ácidos grasos Omega 3 y 6</strong> presentes en la fórmula ayudan a mantener la piel sana y el pelaje fuerte y brillante, mientras que el <strong>DHA</strong> es fundamental para el desarrollo del cerebro y la salud ocular, especialmente en las primeras etapas de vida.</p>
                <p>Para garantizar una alimentación equilibrada y libre de ingredientes innecesarios, <strong>Boske Mini no contiene colorantes ni conservantes artificiales</strong>, asegurando así una dieta más natural y saludable para tu perro.</p>
                <p>Además, su <strong>tamaño de croqueta está adaptado (1 cm)</strong>, diseñado específicamente para facilitar la masticación y digestión en perros de raza pequeña, con una <strong>textura agradable</strong> que hará de cada comida un auténtico placer.</p>
                <p><strong>Boske Mini no es solo un pienso, es una nutrición pensada para su felicidad y bienestar.</strong></p>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Saco-PolloMini-img-Cabecera-Perro02.png" title="perro2" alt="perro2">
            </div>
        </div>

        <div class="additional-info-box">
            <div>
                <img src="{$module_img}BOSKE-Saco-PolloMini-img-Cabecera-Perro03.png" title="perro3" alt="perro3">
            </div>

            <div class="text-additional-info">
                <div>
                    <h2 class="bold">
                        Modo de empleo
                    </h2>
                    <p>
                        El cambio de alimento debe hacerse de forma gradual durante 7 días. Suministre
                        la ración diaria,preferiblemente, dividida en 2 o 3 tomas al día, y siempre a las
                        mismas horas. La cantidad de alimento puede variar en función de la actividad,
                        raza del perro y condiciones ambientales. El perro debe disponer siempre de
                        agua limpia y fresca.
                    </p>
                    <h2 class="bold">
                        Aditivos nutricionales
                    </h2>
                    <p>
                        Vitamina A 18500 UI/kg. Vitamina D3 1500 mg/kg. Vitamina E 200 mg/kg. Vitamina C
                        75 mg/kg. Hierro (Sulfato de hierro (II) monohidratado) 60 mg/kg. Iodo (Yoduro
                        potásico) 2,8 mg/kg. Cobre (sulfato de cobre (II) heptahidratado) 8 mg/kg.
                        Manganeso (sulfato manganoso monohidratado) 6 mg/kg. Zinc (óxido de zinc) 96
                        mg/kg. Selenio (selenito sódico) 0,1 mg/kg.
                    </p>
                    <p>
                        <span class="bold">Aditivos tecnológicos:</span>
                    </p>
                    <p>
                        Antioxidantes: Extractos de tocoferoles de aceites vegetales 90 mg/kg.
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
                    <div class="benefits-key-boxes benefits-key-boxes-chicken-mini">
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-AltoContenidoEnergetico.svg" alt="icono_1" width="57" height="57">

                        <div class="benefit-text benefit-text-chicken">
                            Aporte de energía óptimo
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-mini">
                        <img src="{$module_img}BOSKE-Saco-PolloMini-iconosBeneficios-IngredientesNaturales.svg" alt="icono_2" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Ingredientes naturales
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-mini">
                        <img src="{$module_img}BOSKE-Saco-PolloMini-iconosBeneficios-ProteinasCalidad.svg" alt="icono_3" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Proteínas de calidad
                        </div>
                    </div>
                </div>

                <div class=" colum-key colum2-key">
                    <div class="benefits-key-boxes benefits-key-boxes-chicken-mini">
                        <img src="{$module_img}BOSKE-Saco-PolloMini-iconosBeneficios-ProteccionSistemaDigestivo.svg" alt="icono_4" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Protección al sistema digestivo
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-mini">
                        <img src="{$module_img}BOSKE-Saco-PolloMini-iconosBeneficios-Omega3.svg" alt="icono_5" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            EPA y DHA. Pelo y piel sana y brillante
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-mini">
                        <img src="{$module_img}BOSKE-Saco-PolloMini-iconosBeneficios-VitaminaE.svg" alt="icono_6" width="57" height="57">
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
                    <img src="{$module_img}BOSKE-Saco-PolloMini-img-ingredientes-01.png" title="ingrediente1" alt="ingrediente1">
                    <h3 class="ingredients-tittle">Pollo y pavo</h3>
                </div>
                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-PolloMini-img-ingredientes-02.png" title="ingrediente2" alt="ingrediente2">
                    <h3 class="ingredients-tittle">Arroz integral</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-PolloMini-img-ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
                    <h3 class="ingredients-tittle">Huevos enteros</h3>
                </div>
            </div>
        </div>

        <div class="product-faq">
            <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

            <p>Desde que lanzamos Boske Mini, hemos recibido una gran cantidad de preguntas de dueños preocupados por la alimentación de sus perros. Sabemos lo importante que es elegir el mejor alimento para su salud y bienestar, por eso hemos recopilado y respondido las dudas más frecuentes. Aquí tienes las respuestas por nuestra consultara nutricional a las preguntas que más se repiten. </p>

            {include file="components/accordition.tpl" list=$faq class="product-faq"}

                
            </div>
        </div>
    </div>
</div>