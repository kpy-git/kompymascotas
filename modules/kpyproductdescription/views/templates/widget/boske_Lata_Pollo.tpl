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
                        Dieta húmeda complementaria
                    </div>
                </div>

                <div class="info-box">
                    <div class="colname">
                        <span class="colnumber">03.</span>{l s='Necesidades específicas' d='Modules.Kpyproductdescription.Shop'}
                    </div>

                    <div class="colinfo">
                        Para todas las razas
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
                    <img src="{$module_img}BOSKE-Lata-AdultoPollo-iconosIlustrados-PolloYPavo.svg" alt="beneficio1">
                    <div>Hasta 60% de pollo y pavo</div>
                </div>
                
                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-AdultoPollo-iconosIlustrados-AporteExtraHidratacion.svg" alt="beneficio2">
                    <div>Aporte extra de hidratación</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-AdultoPollo-iconosIlustrados-AltaPalatabilidad.svg" alt="beneficio3">
                    <div>Alta palatabilidad</div>
                </div>

                <div class="benefit-box">
                    <img src="{$module_img}BOSKE-Lata-AdultoPollo-iconosIlustrados-VitaminasMinerales.svg" alt="beneficio4">
                    <div>Vitaminas y minerales</div>
                </div>

            </div>
        </div>

    </div>

    <!--Tabla de raciones-->


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
                <p>
                    La alimentación complementaria es ideal para completar los
                    <span class="bold">requerimientos nutricionales</span> que tiene tu mascota. Estas latas, al tener
                    un <span class="bold">60% de proteína animal</span>, en concreto pollo y pavo proporcionan un
                    aporte de proteína de alto nivel biológico, así como verduras que aportan
                    minerales y vitaminas. Buena opción para hacer la comida más palatable a
                    los perros con paladar delicado.
                </p>
            </div>
        </div>
    </div>

    <!--Fotos Perro y piensos-->
    <div class="DogCatHeader">
        <img class="DogCatHeader1" src="{$module_img}BOSKE-Lata-AdultoPollo-img-cabecera-01.png" alt="CabeceraPerro">
        <img class="DogCatHeader2" src="{$module_img}BOSKE-Lata-AdultoPollo-img-latas-01.png" alt="BodegonPiensos">
    </div>

    <!--Informacion de mostrar todo-->
    <div class="show-all">
        
        <div class="additional-info-box additional-info-box-border-color">
            <div>
                <img src="{$module_img}BOSKE-Lata-AdultoPollo-img-fotoPerro-02.png" title="perro1" alt="perro1">
            </div>            
            
            <div class="text-additional-info">
                <p>
                    Las <span class="bold">latas naturales de Boske</span> son el complemento ideal para la dieta de tu
                    mascota. Su receta contiene ingredientes naturales con un <span class="bold">60% de pollo y pavo y
                    verduras</span> que aportan vitaminas y minerales. Ideal para perros de todos los
                    tamaños y razas.
                </p>
                <p>
                    Su textura las hace altamente apetecibles. Formuladas sin saborizantes ni
                    conservantes artificiales hacen de este producto un alimento natural, que aportará
                    proteínas de alta calidad. Son ideales para perros de todas las razas y tamaños.
                </p>
                <p>
                    Energía metabolizable 1610 kcal/kg.
                </p>
            </div>
        </div>
        
        <div class="additional-info-box">
            <div class="text-additional-info">
                <h2 class="color-title-other-products ">
                    Modo de empleo y conservación
                </h2>
                <p>
                    Administrar al perro junto con una dieta equilibrada y variada. Ideales para dar a
                    modo de premio a tu mascota o como complemento nutricional diario. No
                    superar el 50% de la ración diaria total recomendada para su mascota. Si la
                    mezcla en la misma toma de pienso, aporte un 10% de lata del total de la ración
                    de comida.
                </p>
                <p>
                    Una vez abierto, conservar refrigerado y consumir en 24h. Servir a temperatura
                    ambiente. Almacenar en un lugar fresco y seco.
                </p>
                <h2 class="color-title-other-products ">
                    Elaboración
                </h2>
                <p>
                    Esta comida húmeda ha sido formulada por expertos y fabricada en España
                    con ingredientes de alta calidad.
                </p>
            </div>
            <div>
                <img src="{$module_img}BOSKE-Lata-AdultoPollo-img-fotoPerro-01.png" title="perro2" alt="perro2">
            </div>
        </div>

        <!--Beneficios clave-->
        <div class="Benefits-key-container">

            <h3 class="Benefits-key-tittle">
                <span>Beneficios clave</span>
            </h3>

            <div class="Benefits-key-table">

                <div class="colum-key colum1-key colum1-key-color ">
                    <div class="benefits-key-boxes benefits-key-boxes-pollolata">
                        <img src="{$module_img}BOSKE-Lata-AdultoPollo-iconosBeneficios-PolloYPavo.svg" alt="icono_1" width="57" height="57">
                        
                        <div class="benefit-text benefit-text-chicken">
                            Hasta un 60% de pollo y pavo, alto porcentaje de productos cárnicos que aporta proteínas de calidad
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-pollolata">
                        <img src="{$module_img}BOSKE-Lata-AdultoPollo-iconosBeneficios-AporteExtraHidratacion.svg" alt="icono_2" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Proporciona un aporte extra de hidratación necesaria en la dieta de nuestras mascotas
                        </div>
                    </div>
                </div>

                <div class=" colum-key colum2-key">
                    <div class="benefits-key-boxes benefits-key-boxes-pollolata">
                        <img src="{$module_img}BOSKE-Lata-AdultoPollo-iconosBeneficios-AltaPalatabilidad.svg" alt="icono_3" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Muy palatables con un exquisito aroma y sabor
                        </div>
                    </div>

                    <div class="benefits-key-boxes benefits-key-boxes-pollolata">
                        <img src="{$module_img}BOSKE-Lata-AdultoPollo-iconosBeneficios-AporteVitaminasMineralesd.svg" alt="icono_4" width="57" height="57">
                        <div class="benefit-text benefit-text-chicken">
                            Aporte de vitaminas y minerales gracias a su composición rica en verduras
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
                    <img src="{$module_img}BOSKE-Lata-AdultoPollo-img-ingredientes-02.png" title="ingrediente1" alt="ingrediente1">
                    <h3 class="ingredients-tittle">Pollo y pavo</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Lata-AdultoPollo-img-ingredientes-01.png" title="ingrediente2" alt="ingrediente2">
                    <h3 class="ingredients-tittle">Zanahoria</h3>
                </div>

                <div class="ingredient-box">
                    <img src="{$module_img}BOSKE-Lata-AdultoPollo-img-ingredientes-03.png" title="ingrediente3" alt="ingrediente3">
                    <h3 class="ingredients-tittle">Proteína de guisante</h3>
                </div>
            </div>
        </div>
    </div>
</div>