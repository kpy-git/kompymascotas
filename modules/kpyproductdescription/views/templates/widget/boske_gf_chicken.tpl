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
                        Alimento natural para perros
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
                        Sin cereales, natural
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
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosIlustrados-Omega3yOmega6.svg" alt="beneficio1">
                    <div>Omega 3 y Omega 6</div>
                </div>
                
                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosIlustrados-ArticulacionesSanas.svg" alt="beneficio2">
                    <div>Articulaciones sanas y fuertes</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosIlustrados-FacilDigestion.svg" alt="beneficio3">
                    <div>Fácil digestión</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosIlustrados-RicoPolloFresco.svg" alt="beneficio4">
                    <div>Rico en pollo fresco</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosIlustrados-PeloPielBrillantes.svg" alt="beneficio5">
                    <div>Piel y pelo brillantes</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosIlustrados-PropiedadesAntioxidantes.svg" alt="beneficio6">
                    <div>Propiedades antioxidantes naturales</div>
                </div>
                
                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosIlustrados-ProteinasCalidad.svg" alt="beneficio7">
                    <div>Proteínas de calidad</div>
                </div>
            </div>
        </div>

    </div>

    <!--Tabla de raciones-->
    <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
        <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Adult Grain Free Pollo Pienso Natural Para Perro</p></div>
            <div class="row">
                <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
                Pollo preparado en fresco 30%. Patata 16,5%. Proteína de pollo deshidratada 14,8%. Guisantes 11,7%. Proteína de guisantes. Aceite de ave 7,1%. Pulpa de remolacha 4,7%. Hidrolizado de hígado de pollo. Pulpa de manzana. Levaduras. Semilla de linaza. Aceite de pescado. Minerales. Zanahoria 0,1%. Brocoli 0,1%. Arándano 0,1%. Inulina. Pared celular hidrolizada de levaduras (fuente de MOS) 0,1%. Extractos de cítricos. Jengibre 0,1%. Glucosamina 0,07%. Yucca schidigera. Caléndula (fuente de luteina) 0,05%. Sulfato de condroitina 0,03%
                </div>
            </div>
        </div>

        <div style="border: 1px solid #eee;margin-top: 1%;"></div>
        <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Adult Grain Free Pollo Pienso Natural Para Perro</p></div>
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
                Omega-3
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                0.45 %
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
                DHA
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                0.4 %
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                EPA
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                0.4 %
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
        </div>

        <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-RacionDiaria.svg"><p class="purple">Ración diaria Boske Adult Grain Free Pollo Pienso Natural Para Perro</p></div>
            <table id="tabla" data-producto="8516-92935" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">

            <thead>
            <tr><th></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th><th style="color: #734e7f;"><center> </center></th></tr></thead>
            <tbody><tr><td style="color: #734e7f;">Peso  del perro</td><td><center>1 kg</center></td><td><center>5 kg</center></td><td><center>10 kg</center></td><td><center>15 kg</center></td><td><center>20 kg</center></td><td><center>25 kg</center></td><td><center>30 kg</center></td><td><center>40 kg</center></td><td><center>50 kg</center></td><td><center>60 kg</center></td></tr><tr><td style="color: #734e7f;"> Baja actividad</td><td><center>25 gr / dia</center></td><td><center>82 gr / dia</center></td><td><center>138 gr / dia</center></td><td><center>188 gr / dia</center></td><td><center>233 gr / dia</center></td><td><center>275 gr / dia</center></td><td><center>315 gr / dia</center></td><td><center>391 gr / dia</center></td><td><center>463 gr / dia</center></td><td><center>531 gr / dia</center></td></tr><tr><td style="color: #734e7f;"> Media actividad</td><td><center>28 gr / dia</center></td><td><center>95 gr / dia</center></td><td><center>160 gr / dia</center></td><td><center>217 gr / dia</center></td><td><center>270 gr / dia</center></td><td><center>319 gr / dia</center></td><td><center>365 gr / dia</center></td><td><center>453 gr / dia</center></td><td><center>536 gr / dia</center></td><td><center>614 gr / dia</center></td></tr><tr><td style="color: #734e7f;"> Alta actividad</td><td><center>35 gr / dia</center></td><td><center>117 gr / dia</center></td><td><center>197 gr / dia</center></td><td><center>267 gr / dia</center></td><td><center>331 gr / dia</center></td><td><center>391 gr / dia</center></td><td><center>448 gr / dia</center></td><td><center>556 gr / dia</center></td><td><center>658 gr / dia</center></td><td><center>754 gr / dia</center></td></tr></tbody>
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
                <p>En la consulta veo cada vez más propietarios que se preocupan por dar a su perro una alimentación más coherente con su biología, pero sin caer en extremos ni modas pasajeras. En ese contexto, la receta <strong>Boske Grain Free Pollo</strong> representa una opción equilibrada: una fórmula sin cereales, con ingredientes naturales y una única fuente de proteína animal bien tolerada.</p>
                <p>El uso de <strong>pollo fresco como base</strong> permite mantener una buena digestibilidad sin sacrificar valor nutricional, algo especialmente relevante en perros con estómago sensible o antecedentes de alergias leves. También valoro que no contenga subproductos ni harinas cárnicas ambiguas, y que incluya fuentes de fibra vegetal funcional, como pulpa de remolacha o linaza, que ayudan a estabilizar el tránsito intestinal.</p>
                <p>No todas las dietas Grain Free son iguales. Algunas eliminan cereales pero cargan la receta de féculas o proteínas vegetales mal balanceadas. Esta, en cambio, mantiene una <strong>estructura nutricional limpia, eficaz y sostenida.</strong><br />No pretende ser revolucionaria. Solo hace bien lo básico. Y eso, en nutrición, es mucho decir.</p>
            </div>
        </div>
    </div>

    <!--Fotos Perro y piensos-->
    <div class="DogCatHeader">
        <img class="DogCatHeader1" src="{$module_img}BOSKE-Saco-GrainFreePollo-img-Cabecera-Pollo.png" alt="CabeceraPerro">
        <img class="DogCatHeader2" src="{$module_img}BOSKE-Saco-GrainFreeSalmon-img-Bodegon-Sacos.png" alt="BodegonPiensos">
    </div>

    <!--Informacion de mostrar todo-->
    <div class="show-all">
        
        <div class="additional-info-box additional-info-box-border-color">
            <div>
                <img src="{$module_img}BOSKE-Saco-GrainFreePollo-img-fotoPerro-01.png" title="perro1" alt="perro1">
            </div>            
            
            <div class="text-additional-info">
                <h5>Hay una gran diferencia entre alimentar y nutrir. Boske Grain Free Pollo hace lo segundo.</h5>
                <p>Formulado con <strong>pollo fresco como ingrediente principal</strong>, este pienso está diseñado para ofrecer a tu perro adulto <strong>una fuente de proteína de alto valor biológico</strong>, fácil de digerir y altamente palatable. Es una receta 100% natural, <strong>sin cereales ni subproductos</strong>, lo que la convierte en una opción ideal tanto para perros con digestión sensible como para quienes simplemente merecen una alimentación limpia, sin artificios.</p>

                <p>A diferencia de otros piensos que combinan múltiples carnes o cargan la fórmula con harinas vegetales, aquí <strong>todo gira alrededor de una única proteína animal de calidad</strong>, lo que mejora la tolerancia digestiva y reduce el riesgo de reacciones adversas.<br />Frutas, verduras y botánicos seleccionados complementan la receta con antioxidantes naturales, vitaminas esenciales y energía funcional. Lo justo, lo necesario, lo que el cuerpo de tu perro reconoce y aprovecha.</p>
            </div>
        </div>
        
        <div class="additional-info-box">
            <div class="text-additional-info">
                <h5>¿Por qué este pienso y no otro? Porque no es una mezcla genérica, es una receta bien pensada.</h5>
                <p>Contiene un <strong>30% de pollo fresco</strong>, una de las fuentes de proteína más digestibles y con mejor aprovechamiento nutricional para el perro. Este tipo de carne aporta un alto valor biológico sin sobrecargar el sistema digestivo, lo que se traduce en heces más firmes, mayor aprovechamiento del alimento y mejor tolerancia incluso en perros sensibles.</p>

                <p>Además, al tratarse de un pienso <strong>Grain Free real (sin trigo, maíz ni cebada)</strong>, reduce el riesgo de intolerancias alimentarias y ayuda a evitar los picos glucémicos que causan ansiedad, sobrepeso o inflamación.<br />No es un “pienso sin cereales” que sustituye grano por relleno. Es una fórmula con sentido: ingredientes útiles, proporciones equilibradas y una palatabilidad alta gracias al contenido en carne fresca.</p>

                <p><strong>Para muchos perros, este tipo de alimentación no es una mejora. Es una solución.</strong></p>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Saco-GrainFreePollo-img-fotoPerro-02.png" title="perro2" alt="perro2">
            </div>
        </div>

        <div class="additional-info-box">
            <div>
                <img src="{$module_img}BOSKE-Saco-GrainFreePollo-img-fotoPerro-03.png" title="perro3" alt="perro3">
            </div>
            
            <div class="text-additional-info">
                <div>
                    <h2 class="bold">
                        Modo de empleo
                    </h2>
                    <p>
                        El cambio de alimento debe hacerse de forma gradual durante 7 días. Suministre
                        la ración diaria, preferiblemente, dividida en 2 o 3 tomas al día, y siempre a las
                        mismas horas. La cantidad de alimento puede variar en función de la actividad,
                        raza del perro y condiciones ambientales. El perro debe disponer siempre de
                        agua limpia y fresca.
                    </p>
                    <h2 class="bold">
                        Aditivos nutricionales
                    </h2>
                    <p>
                        Vitamina A 20000 UI/kg. Vitamina D3 1800 UI/kg. Vitamina E 200 mg/kg. Vitamina C
                        100 mg/kg. Hierro (Sulfato de hierro (II) monohidratado) 75 mg/kg. Iodo (Yoduro
                        potásico) 3,5 mg/kg. Cobre (sulfato de cobre (II) heptahidratado) 10 mg/kg.
                        Manganeso (sulfato manganoso monohidratado) 7,5 mg/kg. Zinc (óxido de zinc)
                        120 mg/kg. Zinc (metionato de zinc) 30 mg/kg. Selenio (selenito sódico) 0,12 mg/kg.
                        Taurina 750 mg/kg
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
                    <div class="benefits-key-boxes benefits-key-boxes-grainfreechicken">
                        <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosBeneficios-Omega3yOmega6.svg" alt="icono_1" width="57" height="57">
                        
                        <div class="benefit-text benefit-text-chicken">
                            Ácidos grasos Omega-3 y Omega-6
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-grainfreechicken">
                        <img src="{$module_img}BOSKE-Saco-Pollo-iconosBeneficios-ArticulacionesSanas-02.svg" alt="icono_2" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Articulaciones sanas y fuertes
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-grainfreechicken">
                        <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosBeneficios-FacilDigestion.svg" alt="icono_3" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Fácil digestión
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-grainfreechicken">
                        <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosBeneficios-PropiedadesAntioxidantes.svg" alt="icono_4" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Propiedades antioxidantes naturales
                        </div>
                    </div>
                </div>

                <div class=" colum-key colum2-key">

                    <div class="benefits-key-boxes benefits-key-boxes-grainfreechicken">
                        <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosBeneficios-RicoPolloFresco.svg" alt="icono_5" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Rico en pollo fresco
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-grainfreechicken">
                        <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosBeneficios-PeloPielBrillantes.svg" alt="icono_6" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Piel y pelo brillantes
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-grainfreechicken">
                        <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosBeneficios-ProteinaAltaCalidad.svg" alt="icono_7" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Proteína de alta calidad
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-grainfreechicken">
                        <img src="{$module_img}BOSKE-Saco-GrainFreePollo-iconosBeneficios-AltaPalatabilidad.svg" alt="icono_8" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Alta palatabilidad
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
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-img-ingredientes-01.png" title="ingrediente1" alt="ingrediente1">
                    <h3 class="ingredients-tittle">Pollo fresco</h3>
                </div>
                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-img-ingredientes-02.png" title="ingrediente2" alt="ingrediente2">
                    <h3 class="ingredients-tittle">Patata</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-GrainFreePollo-img-ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
                    <h3 class="ingredients-tittle">Guisantes</h3>
                </div>
            </div>
        </div>

        <div class="product-faq">
            <h5 class="title">Preguntas frecuentes sobre {$product.name}</h5>

            {include file="components/accordition.tpl" list=$faq class="product-faq"}

        </div>

    </div>
</div>