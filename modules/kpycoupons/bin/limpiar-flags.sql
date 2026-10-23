-- kpycoupons 3.0.0 (23/10/2026): borra las etiquetas que las versiones anteriores (hasta la 2.4)
-- escribieron como filas en las tablas de kpyproductflags. Desde la 3.0.0 se calculan por hook y no
-- hace falta ninguna fila; como son tablas de otro módulo, el `upgrade` no las toca: las borra este
-- SQL (o `bin/limpiar-flags.php --borrar`, que hace lo mismo).
--
-- Solo toca los tipos de kpycoupons: «Oferta −X %», «Código −X %», «2ª ud. −X %» (con guion U+2212 y
-- con guion normal) y «+ …» (el regalo). Las etiquetas manuales («Bajada de Precio», «kg Gratis»,
-- «Top Ventas»…) se quedan. En una tienda donde kpycoupons nunca estuvo instalado no borra nada.
--
-- Prefijo de producción: pps22_. Primero el recuento (sin borrar), luego los tres DELETE en ese orden
-- (hay clave foránea de kpy_product_flag y kpy_flag_lang hacia kpy_flag).

-- 1. Cuántas filas son (comprobar antes de borrar):
SELECT
    (SELECT COUNT(*) FROM pps22_kpy_flag f WHERE f.type LIKE 'Oferta −%' OR f.type LIKE 'Oferta -%' OR f.type LIKE 'Código −%' OR f.type LIKE 'Código -%' OR f.type LIKE '2ª ud. −%' OR f.type LIKE '2ª ud. -%' OR f.type LIKE '+ %') AS kpy_flag,
    (SELECT COUNT(*) FROM pps22_kpy_flag_lang fl WHERE fl.id_flag IN (SELECT f.id_flag FROM pps22_kpy_flag f WHERE f.type LIKE 'Oferta −%' OR f.type LIKE 'Oferta -%' OR f.type LIKE 'Código −%' OR f.type LIKE 'Código -%' OR f.type LIKE '2ª ud. −%' OR f.type LIKE '2ª ud. -%' OR f.type LIKE '+ %')) AS kpy_flag_lang,
    (SELECT COUNT(*) FROM pps22_kpy_product_flag pf WHERE pf.id_flag IN (SELECT f.id_flag FROM pps22_kpy_flag f WHERE f.type LIKE 'Oferta −%' OR f.type LIKE 'Oferta -%' OR f.type LIKE 'Código −%' OR f.type LIKE 'Código -%' OR f.type LIKE '2ª ud. −%' OR f.type LIKE '2ª ud. -%' OR f.type LIKE '+ %')) AS kpy_product_flag;

-- 2. Borrar, en este orden:
DELETE pf FROM pps22_kpy_product_flag pf
    INNER JOIN pps22_kpy_flag f ON f.id_flag = pf.id_flag
    WHERE f.type LIKE 'Oferta −%' OR f.type LIKE 'Oferta -%' OR f.type LIKE 'Código −%' OR f.type LIKE 'Código -%' OR f.type LIKE '2ª ud. −%' OR f.type LIKE '2ª ud. -%' OR f.type LIKE '+ %';

DELETE fl FROM pps22_kpy_flag_lang fl
    INNER JOIN pps22_kpy_flag f ON f.id_flag = fl.id_flag
    WHERE f.type LIKE 'Oferta −%' OR f.type LIKE 'Oferta -%' OR f.type LIKE 'Código −%' OR f.type LIKE 'Código -%' OR f.type LIKE '2ª ud. −%' OR f.type LIKE '2ª ud. -%' OR f.type LIKE '+ %';

DELETE FROM pps22_kpy_flag
    WHERE type LIKE 'Oferta −%' OR type LIKE 'Oferta -%' OR type LIKE 'Código −%' OR type LIKE 'Código -%' OR type LIKE '2ª ud. −%' OR type LIKE '2ª ud. -%' OR type LIKE '+ %';
