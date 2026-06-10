Hay que desactivar el envío del email de bienvenida desde el back office. El envío del email se hace antes de la 
llamada al hook `actionCustomerAccountAdd` (classes/form/CustomerPersister.php).

Lo ideal sería enviar el email de bienvenida desde este módulo. He intentado extender la plantilla 'account'
pero no he sido capaz. Siguiendo la documentación oficial se pueden extender plantillas, pero como ya tenemos un módulo
que crea un nuevo tema para los emails no sé como hacerlo funcionar (con el hook `actionListMailThemes` no viene 
ningún parámetro usable).