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
                        Diabetes
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
                    <img src="{$module_img}BOSKE-Lata-Diabetic-iconosIlustrados-IngredientesNaturales.svg" alt="beneficio1">
                    <div>Ingredientes naturales</div>
                </div>
                
                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Diabetic-iconosIlustrados-TratamientoDiabetes.svg" alt="beneficio2">
                    <div>Indicado para el tratamiento de la diabetes</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Diabetic-iconosIlustrados-RefuerzaBarreraCutanea.svg" alt="beneficio3">
                    <div>Refuerza la barrera cutánea</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Diabetic-iconosIlustrados-EfectoPrebiotico.svg" alt="beneficio4">
                    <div>Efecto prebiótico</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Diabetic-iconosIlustrados-AntioxidantesLCarnitina.svg" alt="beneficio5">
                    <div>Antioxidantes y L-Carnitina</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-Diabetic-iconosIlustrados-ReducirAzucarSangre.svg" alt="beneficio6">
                    <div>Ayuda a reducir el azúcar en sangre</div>
                </div>

            </div>
        </div>

    </div>

    <!--Tabla de raciones-->
    <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
        <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Clinical Diet Diabetic Alimento Húmedo para Perros</p></div>
            <div class="row">
                <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
                Pollo (carne) 31%, cerdo (hígado, pulmón), pescado blanco (bacalao, dorada, lubina), cebada, aceite de coco, Plantago psyllium, XOS, minerales, MOS, polifenoles de uva, L-carnitina, xilosa.
                Fuente de carbohidratos: cebada.
                Fuente de ácidos grasos de cadena corta/media: aceite de coco.
                </div>
            </div>
        </div>

        <div style="border: 1px solid #eee;margin-top: 1%;"></div>
        <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Clinical Diet Diabetic Alimento Húmedo para Perros</p></div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Proteína bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                6.9%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Aceites y grasas brutos
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                4.3%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Cenizas Brutas
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                2.2%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Fibra bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                0.4%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Almidón
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                1.6%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Humedad
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                80.1%
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
                <p>Cuando hablamos de perros con <strong>diabetes mellitus</strong> o con tendencia a la <strong>obesidad</strong>, lo más importante no es solo controlar los picos de glucosa, sino garantizar una nutrición que sea segura, efectiva y sostenible en el tiempo. Uno de los errores más comunes que veo en consulta es pensar que cualquier alimento “light” es adecuado para estos casos. No lo es.</p>

                <p><strong>Boske Clinical Diet Diabetic</strong> ha sido desarrollado con un enfoque clínico muy claro: estabilizar la glucemia postpandrial mediante el uso de <strong>carbohidratos seleccionados de bajo índice glucémico y proteínas de alta calidad.</strong> Esto no solo contribuye a una mejor gestión de la diabetes, sino que también ayuda a reducir molestias digestivas asociadas como el dolor gástrico.</p>

                <p>Además, su contenido en <strong>L-Carnitina</strong> apoya el metabolismo de las grasas, lo que facilita una pérdida de peso más eficiente en perros con sobrepeso, sin comprometer su masa muscular.</p>

                <p>La mayoría de tutores buscan “algo natural que funcione”. Aquí lo tienen: una receta formulada por expertos, sin cereales ni ingredientes superfluos, y orientada a mejorar el bienestar real del animal.</p>
            </div>
        </div>
    </div>

    <!--Fotos Perro y piensos-->
    <div class="DogCatHeader">
        <img class="DogCatHeader1" src="{$module_img}BOSKE-Lata-Diabetic-img-cabecera.png" alt="CabeceraPerro">
        <img class="DogCatHeader2" src="{$module_img}BOSKE-Lata-Diabetic-img-bodegon-latas.jpg" alt="BodegonPiensos">
    </div>

    <!--Informacion de mostrar todo-->
    <div class="show-all">
        
        <div class="additional-info-box additional-info-box-border-color">
            <div>
                <img src="{$module_img}BOSKE-Lata-Diabetic-img-fotoPerro-03-630x540.png" title="perro1" alt="perro1">
            </div>            
            
            <div class="text-additional-info">
                <p><strong>Boske Clinical Diet Diabetic</strong> es un alimento húmedo formulado por <strong>veterinarios expertos</strong> con un solo objetivo: <strong>ayudar a regular los niveles de glucosa en sangre</strong> de forma eficaz y segura. Cada lata está desarrollada bajo estrictos controles de calidad y testada clínicamente, <strong>sin cereales ni azúcares añadidos</strong>, para asegurar una digestión ligera, una absorción lenta y estable de la glucosa, y una palatabilidad excelente.</p>

                <p>En lugar de gastar en campañas o intermediarios, <strong>Boske invierte en ingredientes funcionales, I+D y tecnología veterinaria real.</strong> Por eso, a diferencia de muchos alimentos “light” o “dietéticos”, este no es solo bajo en calorías: <strong>está pensado para proteger al perro diabético desde dentro,</strong> aportando energía de forma controlada, regulando el metabolismo y respetando su salud digestiva.</p>

                <p><strong>Sin adornos, sin atajos, sin rellenos. Solo nutrición clínica natural.</strong></p>
            </div>
        </div>
        
        <div class="additional-info-box">
            <div class="text-additional-info">
                <h2 class="color-title-other-products ">
                    Beneficios
                </h2>
                <p><strong>Boske Clinical Diet</strong> nace con el objetivo de ofrecer una solución de alimentación veterinaria natural, desarrollada con ingredientes de la máxima calidad y pensada para animales con requerimientos clínicos concretos. Esta fórmula, como todas las de la gama, ha sido diseñada sin cereales y sin ingredientes innecesarios, buscando siempre la mayor pureza y funcionalidad posible.</p>

                <p>Ofrece una <strong>garantía 100% de satisfacción</strong>, resultado del compromiso de la marca con la innovación técnica y el respaldo de su equipo de veterinarios especialistas en nutrición clínica.</p>

                <h2 class="color-title-other-products ">
                    Innovaciones en la receta
                </h2>

                <p><strong>Boske Clinical Diet Diabetic</strong> está formulado para contribuir al control dietético de la diabetes mellitus y ayudar en casos de obesidad canina. A través de fórmulas específicas y cuidadosamente equilibradas, permite <strong>regular los niveles de glucosa en sangre</strong> sin comprometer la nutrición general del animal.</p>

                <p>Para ello se utilizan únicamente <strong>ingredientes 100% naturales y seleccionados</strong>, con el objetivo de asegurar una absorción lenta de los carbohidratos y favorecer un perfil metabólico más estable, ofreciendo una nutrición óptima incluso en situaciones clínicas exigentes.</p>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Lata-Diabetic-img-fotoPerro-04-630x540.png" title="perro2" alt="perro2">
            </div>
        </div>

        <div class="additional-info-box">
            <div>
                <img src="{$module_img}BOSKE-Lata-Diabetic-img-fotoPerro-01-630x540.png" title="perro3" alt="perro3">
            </div>
            <div class="text-additional-info">
                <h2 class="color-title-other-products ">
                    ¿Cómo sé si este producto es apropiado para mi perro?
                </h2>
                <p>
                    <strong>Boske Clinical Diet Diabetic</strong> está diseñado específicamente para <strong>perros con diabetes mellitus</strong> o con dificultades en el control glucémico. Su fórmula clínica contribuye a <strong>regular la ingesta de glucosa</strong>, reduciendo el riesgo de picos tras las comidas y mejorando la respuesta posprandial del animal.
                </p>
                
                <ul style="list-style: unset;padding-left: 1.25rem;">
                    <li>Contiene <strong>carbohidratos de bajo índice glucémico</strong>, que favorecen la estabilidad de los niveles de azúcar en sangre y ayudan a minimizar molestias digestivas, como el dolor gástrico.</li>
                    <li>Incorpora <strong>proteínas de alta calidad</strong>, esenciales para proteger la masa magra y asegurar una nutrición completa sin comprometer el equilibrio metabólico.</li>
                    <li>Su contenido en <strong>L-Carnitina</strong> favorece la conversión de grasa en energía, ayudando a reducir el tejido adiposo sin pérdida muscular y promoviendo un <strong>mejor manejo del peso.</strong></li>
                    <li>Gracias a su formulación húmeda y palatable, es bien aceptado por perros con necesidades nutricionales exigentes.</li>
                </ul>

                <p>Este alimento no solo apoya el tratamiento clínico, sino que lo hace sin añadir ingredientes innecesarios ni encarecer el producto, manteniendo la línea de <strong>nutrición funcional sin artificios</strong> propia de Boske Clinical.</p>
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
                        <img src="{$module_img}BOSKE-Lata-Diabetic-iconosBeneficios-IngredientesNaturales.svg" alt="icono_1" width="57" height="57">
                        
                        <div class="benefit-text benefit-text-chicken">
                            Ingredientes naturales
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Diabetic-iconosBeneficios-TratamientoDiabetes.svg" alt="icono_2" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Indicado para el tratamiento de la diabetes mellitus
                            y problemas de colitis o relacionados con el tránsito
                            intestinal
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Diabetic-iconosBeneficios-RefuerzaBarreraCutanea.svg" alt="icono_3" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Refuerza la barrera cutánea
                        </div>
                    </div>
                </div>

                <div class=" colum-key colum2-key">
                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Diabetic-iconosBeneficios-Prebiotics.svg" alt="icono_4" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Prebióticos como XOS y MOS que ayudan a la flora
                            intestinal, aumentan la respuesta inmune, regulan el
                            tránsito intestinal y favorecen la síntesis de vitaminas
                        </div>
                    </div>
                    
                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Diabetic-iconosBeneficios-AtioxidantesLCarnitina.svg" alt="icono_5" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Antioxidantes y L-Carnitina que que reducen los
                            radicales libres
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Lata-Diabetic-iconosBeneficios-BajoCarbohidratos.svg" alt="icono_6" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Bajo en carbohidratos de bajo índice glucémico para
                            ayudar a reducir el azúcar en sangre posprandial y el
                            dolor gástrico
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
                <p>Boske Clinical Diet Diabetic está indicado principalmente para perros diagnosticados con diabetes mellitus. Su formulación específica contribuye a controlar la glucemia y evitar fluctuaciones críticas tras las comidas.</p>

                <p>También puede ser útil como soporte nutricional en casos de:</p>
                <ul style="list-style: unset; place-content: 1.25rem;">
                    <li><strong>Sobrepeso o tendencia a la obesidad</strong>, por su contenido en L-Carnitina que favorece la conversión de grasas en energía.</li>
                    <li><strong>Tránsito intestinal lento o alterado</strong>, gracias a su perfil de carbohidratos de bajo índice glucémico que mejoran la digestión sin elevar los niveles de azúcar.</li>
                </ul>

                <p>En resumen, es una fórmula enfocada no solo a tratar la patología principal, sino a <strong>prevenir complicaciones asociadas</strong> y favorecer un perfil metabólico más saludable.</p>

            </div>
            <div>
                <img src="{$module_img}BOSKE-Lata-Diabetic-img-fotoPerro-02-630x540.png" title="perro4" alt="perro4">
            </div>
        </div>

        <!--Ingredientes principales-->
        <div class="Ingredients-principales">
            <h4 class="ingredients">
                Ingredientes principales
            </h4>
            
            <div class="ingredients-boxes">
                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Lata-Diabetic-img-ingredientes-01.png" title="ingrediente1" alt="ingrediente1">
                    <h3 class="ingredients-tittle">Pollo</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Lata-Diabetic-img-ingredientes-02.png" title="ingrediente2" alt="ingrediente2">
                    <h3 class="ingredients-tittle">Pescado blanco</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Lata-Diabetic-img-ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
                    <h3 class="ingredients-tittle">Hígado de cerdo</h3>
                </div>
            </div>
        </div>

        <div class="product-faq">
            <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

            {include file="components/accordition.tpl" list=$faq class="product-faq"}

        </div>
    </div>
</div>