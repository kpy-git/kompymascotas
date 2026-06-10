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
                        Problemas gastrointestinales
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
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosIlustrados-MejoraSistemaDigestivo.svg" alt="beneficio1">
                    <div>Mejora su sistema digestivo</div>
                </div>
                
                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosIlustrados-ReduceMolestiasDigestivas.svg" alt="beneficio2">
                    <div>Reduce las molestias digestivas</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosIlustrados-SoporteInmunitario.svg" alt="beneficio3">
                    <div>Soporte inmunitario</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosIlustrados-Prebiotico.svg" alt="beneficio4">
                    <div>Efecto prebiótico</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosIlustrados-ReposicionNutrientesPerdidos.svg" alt="beneficio5">
                    <div>Reposición de nutrientes perdidos</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosIlustrados-AltoSodioPotasio.svg" alt="beneficio6">
                    <div>Alto en sodio y potasio</div>
                </div>
                
            </div>
        </div>

    </div>

    <!--Tabla de raciones-->
    <div class="product-info-label product-info-label-2 container-fluid nopadding" style="background: #fafafaa8; border-radius: 10px;display: flex;flex-direction: row;border: 1px solid #eee;flex-wrap: wrap-reverse">
        <div class="product-info-col-2 col-md-4 col-sm-12 col-xs-12 col-12" style="background: transparent; margin-left: 8%">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Ingredientes.svg"><p class="purple">Ingredientes Boske Clinical Diet Gastrointestinal Pienso para Perros</p></div>
            <div class="row">
                <div class="col-lg-12 col-xs-12 nopadding" style="color: #734e7f; font-size: 12px; font-weight: 400">
                Proteína de ave deshidratada 26%. Patata 12%. Guisantes 10%. Tapioca 10%. Proteína de guisantes 9,7%. Patata dulce 9,5%. Grasa de ave 5,75%. Pulpa de manzana 5,5%. Hidrolizado de hígado de ave 2,8%. Aceite de pescado 2,5%. Garrofa micronizada 2%. Levaduras 1,5%. Semilla de linaza 0,7%. Pulpa de remolacha 0,7%. Cloruro potásico 0,3%. Cloruro sódico 0,2%. Inulina 0,2%. Pared celular hidrolizada de levaduras (fuente de MOS) 0,2%. Calabaza 0,1%. Manzanilla 0,05%. Salvia 0,05%. Yucca Schidigera 0,05%.
                </div>
            </div>
        </div>

        <div style="border: 1px solid #eee;margin-top: 1%;"></div>
        <div class="product-info-col-2 col-md-6 col-sm-12 col-xs-12 col-12" style="background: transparent; font-weight: 400; font-size: 14px">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-Analitica.svg"><p class="purple">Analítica Boske Clinical Diet Gastrointestinal Pienso para Perros</p></div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Proteína bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                27%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Materias grasas
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                13.5%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Fibra bruta
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                4.5%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Cenizas Brutas
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                7%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Calcio
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                1.3%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-6 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                Fósforo
                </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f"> ----- </div>
                <div class="col-lg-3 col-xs-3 nopadding" style="color: #734e7f; font-weight: 400; font-size: 14px">
                0.95%
                </div>
            </div>
        </div>

        <div class="product-info-col-2 col-md-10 col-sm-12 col-xs-12 col-12" style="background: transparent;margin: auto">
            <div class="ration_expert_title"><img src="https://piensoymascotas.com/themes/pym_template/assets/img/PYM-ico-RacionDiaria.svg"><p class="purple">Ración diaria Boske Clinical Diet Gastrointestinal Pienso para Perros</p></div>
            <table id="tabla" data-producto="8854-93384" data-lang="3" class="table table-striped table-hover" style="color: #3bb29b;font-weight: 600;font-size: 14px;">

            <thead>
            <tr><th></th><th style="color: #734e7f;"><center>1kg</center></th><th style="color: #734e7f;"><center> 5kg</center></th><th style="color: #734e7f;"><center> 10kg</center></th><th style="color: #734e7f;"><center> 15kg</center></th><th style="color: #734e7f;"><center> 20kg</center></th><th style="color: #734e7f;"><center>25kg</center></th><th style="color: #734e7f;"><center> 30kg</center></th><th style="color: #734e7f;"><center> 40kg</center></th><th style="color: #734e7f;"><center> 50kg</center></th><th style="color: #734e7f;"><center>60kg</center></th></tr></thead>
            <tbody><tr><td style="color: #734e7f;">actividad baja</td><td><center>26g</center></td><td><center>88g</center></td><td><center>148g</center></td><td><center>200g</center></td><td><center>248g</center></td><td><center>293g</center></td><td><center>336g</center></td><td><center>417g</center></td><td><center>493g</center></td><td><center>566g</center></td></tr><tr><td style="color: #734e7f;"> actividad media</td><td><center>30g</center></td><td><center>102g</center></td><td><center>171g</center></td><td><center>232g</center></td><td><center>287g</center></td><td><center>340g</center></td><td><center>390g</center></td><td><center>483g</center></td><td><center>571g</center></td><td><center>655g</center></td></tr><tr><td style="color: #734e7f;"> actividad alta</td><td><center>37g</center></td><td><center>125g</center></td><td><center>210g</center></td><td><center>284g</center></td><td><center>353g</center></td><td><center>417g</center></td><td><center>478g</center></td><td><center>593g</center></td><td><center>701g</center></td><td><center>804g</center></td></tr></tbody>
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
                <p>Desde un punto de vista veterinario, <strong>Boske Clinical Diet Gastrointestinal</strong> destaca por ofrecer una <strong>alternativa natural y efectiva</strong> frente a los alimentos dietéticos tradicionales. Su fórmula ha sido desarrollada específicamente para ayudar a perros con problemas digestivos, pero sin renunciar a la calidad de los ingredientes ni al sabor, algo clave en animales con poco apetito.</p>

                <p>Contiene ingredientes de <strong>alta digestibilidad</strong>, fuentes proteicas bien toleradas y prebióticos como <strong>MOS e inulina</strong>, que ayudan a reequilibrar la microbiota intestinal. Esto no solo mejora el tránsito, sino que también fortalece el sistema inmunitario digestivo, algo que a menudo pasa desapercibido.</p>

                <p>Es un alimento recomendable en casos de <strong>diarreas recurrentes, digestiones pesadas, gastritis o perros con historial de intolerancias alimentarias.</strong> Pero lo que más valoro como veterinaria es que está formulado sin ingredientes artificiales, algo poco común en este tipo de gamas clínicas, lo que lo convierte en una opción <strong>más limpia y más segura a largo plazo.</strong></p>

                <p>Por tanto, no solo trata los síntomas, sino que <strong>aporta calidad de vida</strong> al animal. Y eso, en medicina preventiva y terapéutica, marca la diferencia.</p>
            </div>
        </div>
    </div>

    <!--Fotos Perro y piensos-->
    <div class="DogCatHeader">
        <img class="DogCatHeader1" src="{$module_img}BOSKE-Saco-Gastrointestinal-img-cabecera.png" alt="CabeceraPerro">
        <img class="DogCatHeader2" src="{$module_img}BOSKE-Saco-Gastrointestinal-img-bodegon-sacos-GRANDE.png" alt="BodegonPiensos">
    </div>

    <!--Informacion de mostrar todo-->
    <div class="show-all">
        
        <div class="additional-info-box additional-info-box-border-color">
            <div>
                <img src="{$module_img}BOSKE-Saco-Gastrointestinal-img-fotoPerro-01.png" title="perro1" alt="perro1">
            </div>            
            
            <div class="text-additional-info">
                <p><strong>Boske Clinical Diet Gastrointestinal</strong> ha sido diseñada para abordar con precisión una de las <strong>patologías más habituales</strong> —y a menudo más <strong>desesperantes</strong>— en los perros: los <strong>trastornos digestivos persistentes</strong>. Cuando un perro presenta <strong>diarreas continuas, vómitos intermitentes, gases excesivos o digestiones pesadas</strong>, muchos propietarios tienden a pensar que es “normal” o achacarlo al estrés, sin intervenir. Pero detrás de estos síntomas puede haber un <string>desequilibrio intestinal crónico</string> que, si no se trata bien, deteriora su salud general y calidad de vida.</p>

                <p>Esta <strong>dieta veterinaria de fácil digestión</strong> está <strong>clínicamente formulada</strong> por expertos para ayudar a <strong>estabilizar el sistema digestivo desde dentro</strong>, gracias a una combinación efectiva de <strong>proteínas seleccionadas, fibras solubles e insolubles y probióticos naturales</strong> como los <strong>MOS y XOS</strong>. No es solo una comida que “sienta bien”; es una <strong>herramienta nutricional</strong> que actúa desde la <strong>raíz del problema</strong>. Ideal tanto para <strong>cuadros puntuales</strong> como para tratamientos de <trong>mantenimiento</trong> en perros con <strong>estómagos sensibles o enfermedades gastrointestinales diagnosticadas.</strong></p>
            </div>
        </div>
        
        <div class="additional-info-box">
            <div class="text-additional-info">
                <p><strong>Boske Clinical Diet Gastrointestinal es una dieta veterinaria natural</strong>, diseñada específicamente para perros con <strong>trastornos digestivos o alteraciones pancreáticas</strong>. Su fórmula libre de cereales se ha desarrollado para abordar no solo los síntomas, sino también las <strong>causas funcionales</strong> de estas patologías. Se compone de <strong>ingredientes naturales</strong> seleccionados por su <strong>alta digestibilidad</strong> y capacidad para <strong>reparar y proteger la flora intestinal</strong>, al tiempo que respeta el criterio más importante para muchos tutores: que el alimento les guste.</p>

                <p>Porque cuando el perro rechaza la comida, <strong>ninguna fórmula funciona</strong>. Aquí se combinan <strong>texturas suaves, aromas naturales</strong> y un <strong>perfil nutricional que permite recuperar la vitalidad</strong> sin sobrecargar el sistema digestivo. Una solución eficaz cuando el objetivo es mantener al perro estable, sin recaídas, y con una alimentación segura a largo plazo.</p>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Saco-Gastrointestinal-img-fotoPerro-02.png" title="perro2" alt="perro2">
            </div>
        </div>

        <div class="additional-info-box">
            <div>
                <img src="{$module_img}BOSKE-Saco-Gastrointestinal-img-fotoPerro-03.png" title="perro3" alt="perro3">
            </div>
            
            <div class="text-additional-info">
                <div>
                    <h2 class="color-title-other-products ">
                        Aditivos nutricionales
                    </h2>
                    <p>
                        Vitamina A 18500 UI/kg. Vitamina D3 1650 UI/kg. Vitamina E 250 mg/kg. Vitamina C
                        100 mg/kg. Hierro (sulfato de hierro (II) monohidratado) 75 mg/kg. Yodo (yoduro
                        potásico) 3,5 mg/kg. Cobre (sulfato de cobre (II) pentahidratado). Manganeso
                        (sulfato manganoso monohidratado) 7,5 mg/kg. Zinc (óxido de zinc) 120 mg/kg.
                        Selenio (selenito de sodio) 0,12 mg/kg.
                    </p>
                    <p>
                        <span class="bold">Aditivos tecnológicos:</span>
                    </p>
                    <p>
                        Bentonita 1000 mg/kg. Antioxidantes: extractos de tocoferol de aceites vegetales.
                    </p>
                    <p>
                        <span class="bold">Aditivos zootécnicos:</span>
                    </p>
                    <p>
                        Probiótico: Bacillus subtilis C-3102 (DSM 15544) 1x109 CFU/kg.
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
                        <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosBeneficios-MejoraDigestion.svg" alt="icono_1" width="57" height="57">
                        
                        <div class="benefit-text benefit-text-chicken">
                            Mejora la digestión y favorece heces de buena calidad
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosBeneficios-ReduceMolestiasDigestivas.svg" alt="icono_2" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Reduce la reaparición de molestias digestivas
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosBeneficios-SaludSistemaDigestivo.svg" alt="icono_3" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Favorece la salud del sistema inmunitario
                        </div>
                    </div>
                </div>

                <div class=" colum-key colum2-key">
                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosBeneficios-FibraPrebiotica.svg" alt="icono_4" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Restablece la flora intestinal con fibra prebiótica
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosBeneficios-ReponerNutrientesPerdidos.svg" alt="icono_5" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Ayuda a reponer nutrientes perdidos
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-cdproducts">
                        <img src="{$module_img}BOSKE-Saco-Gastrointestinal-iconosBeneficios-AltoSodioPotasio.svg" alt="icono_6" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Alto en sodio y potasio
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
                <p>Cuando un perro sufre molestias digestivas, la alimentación juega un papel determinante en su recuperación. <strong>Boske Clinical Diet Gastrointestinal</strong> está diseñado para apoyar de forma específica a perros con patologías digestivas comunes, gracias a su <strong>formulación clínica</strong>, sus <strong>ingredientes naturales</strong> y su capacidad para <strong>mejorar la flora intestinal.</strong><br /><br />
                    Está indicado en los siguientes casos:</p>
                <ul style="list-style: unset;padding-left: 1.25rem;">
                    <li><strong>Transtornos de absorción intestinal:</strong> cuando el sistema digestivo no asimila correctamente los nutrientes, provocando pérdida de peso o debilidad general. La fórmula ayuda a estabilizar la absorción gracias a su digestibilidad alta.</li>
                    <li><strong>Animales con digestiones complicadas:</strong> en perros con sensibilidad gástrica, digestiones pesadas o vómitos frecuentes. El alimento reduce la carga digestiva y respeta el equilibrio intestinal.</li>
                    <li><strong>Diarreas agudas:</strong> en perros con sensibilidad gástrica, digestiones pesadas o vómitos frecuentes. El alimento reduce la carga digestiva y respeta el equilibrio intestinal.</li>
                    <li><strong>Diarreas agudas:</strong> ya sean provocadas por cambios en la dieta, estrés o sensibilidad alimentaria. Los probióticos y fibras naturales como la pulpa de remolacha y la inulina ayudan a restablecer la flora intestinal.</li>
                    <li><strong>Colitis:</strong> inflamación del colon que puede dar lugar a heces blandas o con moco. Este pienso contribuye a calmar la irritación intestinal y mejorar la consistencia de las deposiciones.</li>
                    <li><strong>Gastritis:</strong> inflamación del estómago que se manifiesta con arcadas, inapetencia o dolor abdominal. Al ser un alimento suave, bajo en irritantes y fácil de digerir, protege y alivia la mucosa gástrica.</li>
                </ul>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Saco-Gastrointestinal-img-fotoPerro-04.png" title="perro4" alt="perro4">
            </div>
        </div>

        <!--Ingredientes principales-->
        <div class="Ingredients-principales">
            <h4 class="ingredients">
                Ingredientes principales
            </h4>
            
            <div class="ingredients-boxes">
                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-img-ingredientes-02.png" title="ingrediente1" alt="ingrediente1">
                    <h3 class="ingredients-tittle">Proteína de ave</h3>
                </div>
                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-img-ingredientes-01.png" title="ingrediente2" alt="ingrediente2">
                    <h3 class="ingredients-tittle">Patata</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Saco-Gastrointestinal-img-ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
                    <h3 class="ingredients-tittle">Proteína de guisante</h3>
                </div>
            </div>
        </div>
    </div>
</div>