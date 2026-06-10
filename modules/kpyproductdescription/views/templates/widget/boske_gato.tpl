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
                        Óptima nutrición para gatos esterilizados
                    </div>
                </div>

                <div class="info-box">
                    <div class="colname">
                        <span class="colnumber">04.</span>{l s='Mascota' d='Modules.Kpyproductdescription.Shop'}
                    </div>

                    <div class="colinfo">
                        Gato
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
                    <img src="{$urls.img_url}beneficios/muscular.svg" alt="beneficio1">
                    <div>Óptima actividad muscular</div>
                </div>
                
                <div class="benefit-box">
                    <img src="{$urls.img_url}beneficios/grasa_corporal.svg" alt="beneficio2">
                    <div>Controla la grasa corporal</div>
                </div>

                <div class="benefit-box">
                    <img src="{$urls.img_url}beneficios/pelaje.svg" alt="beneficio3">
                    <div>Favorece al pelaje</div>
                </div>

                <div class="benefit-box">
                    <img src="{$urls.img_url}beneficios/inmunitario.svg" alt="beneficio4">
                    <div>Refuerzo inmunitario</div>
                </div>

                <div class="benefit-box">
                    <img src="{$urls.img_url}beneficios/defensas.svg" alt="beneficio5">
                    <div>Fortalece las defensas</div>
                </div>

                <div class="benefit-box">
                    <img src="{$urls.img_url}beneficios/riñon.svg" alt="beneficio6">
                    <div>Contribuyo al normal funcionamiento del riñón</div>
                </div>

                <div class="benefit-box">
                    <img src="{$urls.img_url}beneficios/vias_urinarias.svg" alt="beneficio7">
                    <div>Inhibición de la formación de cristales en las vías urinarias</div>
                </div>

                <div class="benefit-box">
                    <img src="{$urls.img_url}beneficios/tracto_urinario.svg" alt="beneficio8">
                    <div>Ayuda al correcto funcionamiento del tracto urinario</div>
                </div>

                <div class="benefit-box">
                    <img src="{$urls.img_url}beneficios/peso.svg" alt="beneficio9">
                    <div>Controla el peso</div>
                </div>
            </div>
        </div>

    </div>

    <!--Tabla de raciones-->
    <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
        <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
            <div class="ration_expert_title"><img src="{$urls.img_url}PYM-ico-Ingredientes.svg"><p class="purple">{$product.name}</p></div>
            <div class="row">
                <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">                    
                    Pollo preparado en fresco 30 %. Proteína deshidratada de pollo 25 %. Arroz 14 %. Maíz. Gluten de maíz. Hidrolizado de hígado de pollo 5 %. Proteína de arroz. Pulpa de remolacha. Celulosa. Aceite de pollo. Proteína de patata. Levaduras. Aceite de pescado. Cloruro de sodio. Polifosfatos de sodio. Inulina (fuente de FOS). Pared celular hidrolizada de levaduras (fuente de MOS). Cloruro de potasio. Yucca schidigera. Extractos de cítricos.
                </div>
            </div>
        </div>

        <div style="border: 1px solid #eee;margin-top: 1%;"></div>
        <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
            <div class="ration_expert_title"><img src="{$urls.img_url}PYM-ico-Analitica.svg"><p class="purple">{$product.name}</p></div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Proteína bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                36.5 %
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Materias grasas
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                10.80 %
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Fibra bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                5.80 %
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Cenizas brutas
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                7.10 %
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Calcio
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                1.25 %
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Fósforo
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                1.05 %
                </div>
            </div>
        </div>

        <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto">
            <div class="ration_expert_title"><img src="{$urls.img_url}PYM-ico-RacionDiaria.svg"><p class="purple">Ración diaria {$product.name}</p></div>
            <table id="tabla" data-producto="9208-94016" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">
            <thead>
            <tr><th></th><th style="color: #734e7f;"><center>3kg</center></th><th style="color: #734e7f;"><center> 4kg</center></th><th style="color: #734e7f;"><center> 5kg</center></th><th style="color: #734e7f;"><center> 6kg</center></th><th style="color: #734e7f;"><center> 7kg</center></th><th style="color: #734e7f;"><center>8kg</center></th></tr></thead>
            <tbody><tr><td style="color: #734e7f;">Mantenimiento</td><td><center>44g</center></td><td><center>53g</center></td><td><center>61g</center></td><td><center>69g</center></td><td><center>77g</center></td><td><center>84g</center></td></tr><tr><td style="color: #734e7f;">Pérdida peso</td><td><center>30g</center></td><td><center>37g</center></td><td><center>42g</center></td><td><center>48g</center></td><td><center>53g</center></td><td><center>58g</center></td></tr></tbody>
        </table>
        </div>
    </div>

    <!-- Opinión especialista -->
    <div class="Opinion-expert">

        <!-- Imagen de la especialista -->
        <div class="Opinion-expert-img">
            <img src="{$module_img}especialista-gato.png" alt="especialista">
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
                <p>Uno de los errores más comunes que veo en consulta es mantener al gato con el mismo pienso durante años, aunque esté esterilizado. La frase suele ser: “le va bien, así que no lo cambio”. Lo entiendo, es una reacción lógica. Pero el hecho de que el gato no presente síntomas evidentes no significa que su alimentación siga siendo la más adecuada. A medio plazo, ese tipo de decisiones favorecen el sobrepeso, el descenso de actividad y los problemas urinarios, que en gatos esterilizados son bastante frecuentes.</p>
                <p>Boske Sterilised Cat tiene una formulación que, desde el punto de vista técnico, está bien resuelta: utiliza proteínas de alta calidad, un contenido graso ajustado, y aporta ingredientes funcionales como la L-carnitina, los prebióticos naturales o el extracto de yuca, que ayudan tanto a la digestión como al control de peso.
También valoro que trabaje con pollo fresco como ingrediente principal, algo que no es habitual en esta gama de productos, y que el equilibrio mineral esté específicamente ajustado para gatos esterilizados.
</p>
                <p>Como veterinaria, no me interesa recomendar marcas. Me interesa que los gatos estén bien alimentados, y que los propietarios entiendan lo que hay detrás de una etiqueta. En ese sentido, este producto cumple con lo que yo espero de un alimento para esta etapa.</p>
            </div>
        </div>
    </div>

    <!--Fotos Perro y piensos-->
    <div class="DogCatHeader">
        <img class="DogCatHeader1" src="{$module_img}CabeceraBoskeGato.png" alt="gama Boske para gatos">
    </div>

    <!--Informacion de mostrar todo-->
    <div class="show-all">
        
        <div class="additional-info-box additional-info-box-border-color">
            <div>
                <img src="{$module_img}boske-gato-parrafo1.png" title="perro1" alt="perro1">
            </div>            
            
            <div class="text-additional-info">
                <h5>Hay vínculos que no necesitan palabras. Solo cuidado real.</h5>

                <p>Boske Sterilised Cat Fresh Chicken es una fórmula pensada para proteger ese vínculo invisible que tienes con tu gato: el de cuidarlo incluso cuando él no lo nota. Por eso contiene un 60% de proteínas de alta calidad, con <strong>pollo fresco deshuesado como primer ingrediente</strong>, acompañado de arroz, patata y extractos vegetales. Además, incluye <strong>hígado de pollo hidrolizado</strong>, que mejora el sabor de forma natural, y <strong>aceite de pescado rico en Omega 3</strong>, para reforzar su sistema inmunológico y mantener su piel y pelo en perfecto estado.</p>
                
                <p>No hay aditivos innecesarios ni subproductos. Solo ingredientes naturales que ayudan a <strong>mantener su peso, su vitalidad y su bienestar urinario</strong> después de la esterilización.</p>

            </div>
        </div>
        
        <div class="additional-info-box">
            <div class="text-additional-info">
                <h5>Natural no es una etiqueta, es una forma de hacer las cosas.</h5>

                <p>El metabolismo de un gato esterilizado cambia. Por eso Boske no se limita a reducir calorías: ajusta los niveles de grasa, incorpora <strong>L-carnitina para optimizar el uso de esa grasa como energía</strong> y regula el contenido mineral para proteger el sistema urinario, un punto especialmente delicado tras la esterilización. Además, contiene una combinación efectiva de <strong>prebióticos naturales como FOS y MOS</strong>, que alimentan la flora intestinal beneficiosa, y <strong>extracto de yuca y pulpa de remolacha</strong>, que favorecen un tránsito digestivo estable y menos olor en las heces.
</p>

                <p>Gracias al <strong>hígado de pollo hidrolizado y aceites esenciales de pescado</strong>, el sabor es muy apetecible incluso para los gatos más exigentes. El resultado: una dieta que no solo nutre, sino que respeta la biología real de tu gato.</p>

            </div>

            <div>
                <img src="{$module_img}boske-gato-parrafo2.png" title="perro2" alt="perro2">
            </div>
        </div>

        <div class="additional-info-box">
            <div>
                <img src="{$module_img}boske-gato-parrafo3.png" title="perro3" alt="perro3">
            </div>
            
            <div class="text-additional-info">
                <div>
                    <h2 class="bold">Un buen alimento también se da bien.</h2>

                    <p>La transición hacia Boske Sterilised Cat debe hacerse con sentido común: de forma gradual, durante al menos siete días, combinando con el alimento anterior en proporciones crecientes. Lo ideal es dividir la ración diaria en dos tomas y evitar dejar restos del día anterior, para preservar frescura y apetencia.
                    </p>

                    <p>Cada gato es distinto, por eso la cantidad de alimento puede variar según su nivel de actividad, su edad o incluso el clima. Lo que no cambia nunca es esto: <strong>debe tener siempre agua limpia y fresca a su disposición.</strong></p>

                    <p>Además de ingredientes naturales, este pienso incorpora un perfil nutricional completo con <strong>vitaminas A, D3, E y C</strong>, minerales esenciales como <strong>zinc, selenio y yodo</strong>, así como <strong>taurina</strong>, vital para la salud ocular y cardiaca del gato. También incluye <strong>L-carnitina y DL-metionina</strong>, que ayudan al metabolismo de las grasas y refuerzan la función urinaria.
 Un equilibrio que no se ve, pero se nota. Día tras día.
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
                    <div class="benefits-key-boxes benefits-key-boxes-chicken-adult">
                        <img src="{$module_img}BOSKE-Saco-GrainFreeSalmon-iconosBeneficios-PielPeloBrillantes.svg" alt="icono_1" width="57" height="57">
                        
                        <div class="benefit-text benefit-text-chicken">
                            EPA y DHA. Pelo y piel sana y brillante
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-adult">
                        <img src="{$module_img}BOSKE-Saco-Pollo-iconosBeneficios-ArticulacionesSanas-02.svg" alt="icono_2" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Articulaciones sanas y fuertes
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-adult">
                        <img src="{$module_img}BOSKE-Saco-Pollo-iconosBeneficios-FacilDigestion.svg" alt="icono_3" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Fácil digestión
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-adult">
                        <img src="{$module_img}BOSKE-Saco-Pollo-iconosBeneficios-VitaminasMinerales.svg" alt="icono_4" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Probióticos naturales
                        </div>
                    </div>
                </div>

                <div class=" colum-key colum2-key">

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-adult">
                        <img src="{$module_img}BOSKE-Saco-Pollo-iconosBeneficios-IngredientesNaturales.svg" alt="icono_5" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Ingredientes naturales
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-adult">
                        <img src="{$module_img}BOSKE-Saco-Pollo-iconosBeneficios-ProteinasCalidad-02.svg" alt="icono_6" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Proteínas de calidad
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-adult">
                        <img src="{$module_img}BOSKE-Saco-Pollo-iconosBeneficios-ProteccionDigestivo-02.svg" alt="icono_7" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Protección al sistema digestivo
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-chicken-adult">
                        <img src="{$module_img}BOSKE-Saco-Pollo-iconosBeneficios-Tocoferoles-02.svg" alt="icono_8" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Propiedades antioxidantes naturales
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
                    <img src="{$module_img}boske-gato-ingrediente1.png" title="ingrediente1" alt="ingrediente1">
                    <h3 class="ingredients-tittle">Pollo ce corral</h3>
                </div>
                <div class="ingredient-box">
                    <img src="{$module_img}boske-gato-ingrediente2.png" title="ingrediente2" alt="ingrediente2">
                    <h3 class="ingredients-tittle">Hígado de pollo</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}boske-gato-ingrediente3.png" title="ingrediente3" alt="ingrediente3">
                    <h3 class="ingredients-tittle">Aceite de pollo y pescado</h3>
                </div>
            </div>
        </div>

        <div class="product-faq">
            <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

            {include file="components/accordition.tpl" list=$faq class="product-faq"}
        </div>
    </div>
</div>