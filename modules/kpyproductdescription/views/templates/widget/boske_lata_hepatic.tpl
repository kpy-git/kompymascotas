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
                        Dieta veterinara húmeda
                    </div>
                </div>

                <div class="info-box">
                    <div class="colname">
                        <span class="colnumber">03.</span>{l s='Necesidades específicas' d='Modules.Kpyproductdescription.Shop'}
                    </div>

                    <div class="colinfo">
                        Problemas hepáticos
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
                        Pavo
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
                    <img src="{$module_img}BOSKE-Lata-Hepatic-iconosIlustrados-ProteinasCalidad.svg" alt="beneficio1">
                    <div>Proteínas de calidad</div>
                </div>
                
                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-iconosIlustrados-AporteVitaminasMinerales.svg" alt="beneficio2">
                    <div>Aporte de vitaminas y minerales</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-iconosIlustrados-Prebioticos.svg" alt="beneficio3">
                    <div>Prebióticos como XOS y MOS</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-iconosIlustrados-AcidosGrasosOmega3.svg" alt="beneficio4">
                    <div>Ácidos grasos Omega-3</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-iconosIlustrados-BajoSodio.svg" alt="beneficio5">
                    <div>Bajo en sodio y cobre</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-iconosIlustrados-AporteNutrientes.svg" alt="beneficio6">
                    <div>Aporte de nutrientes sin sobrecargar el hígado</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-iconosIlustrados-AltoContenidoEnergetico.svg" alt="beneficio7">
                    <div>Alto contenido energético</div>
                </div>

            </div>
        </div>
    </div>

    <!--Tabla de raciones-->
    <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">                         
        <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Clinical Diet Hepatic Alimento Húmedo para Perros</p></div>
            <div class="row">
                <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
                Pavo (carne) 24%, huevo deshidratado, proteína de guisante, arroz, calabaza, aceite de salmón, XOS, minerales, MOS, polifenoles de uva, xilosa.
                Fuente de proteína: pavo, huevos, proteína de guisante.
                </div>
            </div>
        </div>

        <div style="border: 1px solid #eee;margin-top: 1%;"></div>
        <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Clinical Diet Hepatic Alimento Húmedo para Perros</p></div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Proteína bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                7.2%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Aceites y grasas brutos
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                6.7%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Cenizas Brutas
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                1.4%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Fibra bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                1.2%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Humedad
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                81.3%
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
                <p>Boske Clinical Diet Hepatic es una fórmula diseñada específicamente para apoyar la función hepática en perros con insuficiencia crónica o enfermedades hepáticas asociadas.</p>

                <p>Su perfil nutricional, cuidadosamente equilibrado, incluye proteínas de muy alta digestibilidad en un nivel moderado, con el objetivo de evitar la sobrecarga del hígado sin comprometer el aporte proteico necesario para el mantenimiento de la masa muscular.</p>

                <p>Además, incorpora ingredientes naturales con propiedades antiinflamatorias y antioxidantes, que ayudan a reducir el estrés oxidativo sobre los hepatocitos y a mejorar la calidad de vida del paciente. Su contenido en ácidos grasos Omega 3 favorece la función hepática y contribuye a una mejor respuesta inmunitaria.</p>

                <p>Esta dieta representa una herramienta útil y segura dentro del manejo nutricional de pacientes con enfermedad hepática.</p>
            </div>
        </div>
    </div>

    <!--Fotos Perro y piensos-->
    <div class="DogCatHeader">
        <img class="DogCatHeader1" src="{$module_img}BOSKE-Lata-Hepatic-img-cabecera.png" alt="CabeceraPerro">
        <img class="DogCatHeader2" src="{$module_img}BOSKE-Lata-Hepatic-img-bodegon-latas.jpg" alt="BodegonPiensos">
    </div>

    <!--Informacion de mostrar todo-->
    <div class="show-all">
        
        <div class="additional-info-box additional-info-box-border-color">
            <div>
                <img src="{$module_img}BOSKE-Lata-Hepatic-img-fotoPerro-02.png" title="perro1" alt="perro1">
            </div>            
            
            <div class="text-additional-info">
                <p>Boske Clinical Diet Hepatic ha sido desarrollado por un equipo de veterinarios especializados en nutrición clínica para apoyar la función hepática de perros con patologías crónicas. Se trata de un alimento húmedo formulado con ingredientes naturales, sin cereales, y con una composición diseñada para facilitar la digestión y minimizar la carga metabólica sobre el hígado.</p>

                <p>La calidad del producto no reside en un envoltorio bonito ni en grandes campañas publicitarias. Boske invierte directamente en I+D, tecnología y materias primas, sin intermediarios ni distribuidores que encarezcan el producto. Por eso, es posible ofrecer una fórmula avanzada, testada clínicamente, con ingredientes de alta calidad y al precio que realmente corresponde: el del contenido.</p>

                <p>Esta es la base del concepto Real Cost: pagar solo por la calidad del alimento y no por costes añadidos innecesarios. Cuando la salud hepática está comprometida, la elección de la dieta no es un detalle menor, es parte del tratamiento.</p>
            </div>
        </div>
        
        <div class="additional-info-box">
            <div class="text-additional-info">
                <p>Boske Clinical Diet es una línea nacida con un propósito claro: ofrecer una solución veterinaria especializada, con ingredientes naturales de alta calidad y sin cereales, para cubrir necesidades clínicas concretas de cada perro. A diferencia de los productos comerciales, la inversión se destina a lo que realmente importa: el desarrollo técnico, la investigación y la calidad de los ingredientes.</p>

                <p>Por eso, cada fórmula cuenta con el respaldo de un equipo de expertos veterinarios y nutricionistas clínicos, y se elabora sin aditivos superfluos ni componentes de relleno.</p>

                <p>Boske Clinical Diet Hepatic, en concreto, ha sido diseñado para apoyar la función hepática en casos de insuficiencia hepática crónica, combinando ingredientes naturales con fórmulas precisas y cuidadosamente ajustadas. El objetivo es ofrecer soporte nutricional sin comprometer la digestibilidad ni la biodisponibilidad.</p>

                <p>Y si tu perro no se adapta a la fórmula, Boske ofrece una garantía de satisfacción: si no le gusta, se recoge el producto.</p>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Lata-Hepatic-img-fotoPerro-01.png" title="perro2" alt="perro2">
            </div>
        </div>

        <div class="additional-info-box">
            <div>
                <img src="{$module_img}BOSKE-Lata-Hepatic-img-fotoPerro-03.png" title="perro3" alt="perro3">
            </div>
            <div class="text-additional-info">
                <h2 class="color-title-other-products ">
                    ¿Cómo sé si este producto es apropiado para mi perro?
                </h2>
                <p>
                    Boske Clinical Diet Hepatic ha sido formulado específicamente para perros que presentan problemas hepáticos. Su objetivo no es simplemente alimentar, sino ofrecer una nutrición adaptada que no sobrecargue la función del hígado y contribuya activamente a su recuperación.
                </p>

                <ul style="list-style: unset;place-content: 1.25rem;">
                    <li>Incluye proteínas altamente digestibles y de elevado valor biológico, que permiten al organismo aprovechar al máximo los nutrientes sin generar un esfuerzo metabólico adicional.</li>
                    <li>La inclusión de aceite de salmón —fuente natural de ácidos grasos Omega 3— ayuda a reforzar la barrera cutánea, reducir los niveles de triglicéridos y controlar los procesos inflamatorios hepáticos.</li>
                    <li>Su composición ha sido diseñada para proteger al hepatocito y fomentar su regeneración, manteniendo el equilibrio nutricional en animales que requieren un manejo clínico preciso.</li>
                </ul>

                <p>El resultado es una fórmula específica, desarrollada por expertos en nutrición veterinaria, que acompaña a tu perro en un momento donde cada ingrediente importa.</p>
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
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-ProteinasCalidad.svg" alt="icono_1" width="57" height="57">
                        
                        <div class="benefit-text benefit-text-chicken">
                            Nivel moderado de proteínas de alta calidad y digestibilidad
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-AporteVitaminasMinerales.svg" alt="icono_2" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Aporte de vitaminas y minerales
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-Prebiotics.svg" alt="icono_3" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Prebióticos como XOS y MOS que ayudan a la flora intestinal, aumentan la respuesta inmune, regulan el tránsito intestinal y previenen enfermedades metabólicas
                        </div>
                    </div>
    
                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-AceiteSalmon.svg" alt="icono_4" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Contiene aceite de salmón, fuente natural de ácidos grasos Omega-3
                        </div>
                    </div>
                </div>

                <div class=" colum-key colum2-key">
                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-BajaSodio.svg" alt="icono_5" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Baja en sodio y cobre para prevenir la sobrecarga hepática
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-Antioxidantes.svg" alt="icono_6" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Antioxidantes que reducen la degradación de los hepatocitos
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-ProteinasCalidad.svg" alt="icono_7" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Proteínas de alta calidad que facilitan el aporte de nutrientes sin sobrecargar el hígado
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Hepatic-iconosBeneficios-AltoContenidoEnergetico.svg" alt="icono_8" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Alto contenido energético
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
                    Boske Clinical Diet Hepatic está indicado principalmente para perros con insuficiencia hepática crónica. También resulta útil en otras patologías relacionadas con el hígado, como el shunt portosistémico, la hepatitis crónica, la encefalopatía hepática o alteraciones funcionales que requieren una dieta específica para no sobrecargar el órgano.
                </p>
                
            </div>
            <div>
                <img src="{$module_img}BOSKE-Lata-Hepatic-img-fotoPerro-04.png" title="perro4" alt="perro4">
            </div>
        </div>

        <!--Ingredientes principales-->
        <div class="Ingredients-principales">
            <h4 class="ingredients">
                Ingredientes principales
            </h4>
            
            <div class="ingredients-boxes">
                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-img-ingredientes-01.png" title="ingrediente1" alt="ingrediente1">
                    <h3 class="ingredients-tittle">Pavo</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-img-ingredientes-02.png" title="ingrediente2" alt="ingrediente2">
                    <h3 class="ingredients-tittle">Huevo deshidratado</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Lata-Hepatic-img-ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
                    <h3 class="ingredients-tittle">Proteína de guisante</h3>
                </div>
            </div>
        </div>

        <div class="product-faq">
            <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

            {include file="components/accordition.tpl" list=$faq class="product-faq"}

        </div>
    </div>
</div>