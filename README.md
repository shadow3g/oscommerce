pagamastarde-oscommerce
====================

Módulo de pago de pagamastarde.com para OsCommerce (v.2.2.x - 2.3.x)

## Instrucciones de Instalación

1. Crea tu cuenta en pagamastarde.com si aún no la tienes [desde aquí](https://bo.pagamastarde.com/users/sign_up)
2. Descarga el módulo de [aquí](https://github.com/pagantis/pagamastarde-oscommerce/releases)
3. Instala el módulo en tu tienda OsCommerce. Para ello sube los ficheros de la carpeta con tu version de este módulo a la carpeta de tu instalación de osCOmmerce, manteniendo la estructura de directorios existente
  - ext/modules/payment/pagamastarde/callback.php
  - includes/modules/payment/pagamastarde.php
  - includes/languages/english/modules/payment/pagamastarde.php
4. Si tu tienda tiene más idiomas, copia el fichero de idiomas en la carpeta correspondiente y edítalo para ajustar los textos
  - includes/languages/OTRO_IDIOMA/modules/payment/pagamastarde.php
5. Desde el panel de OsCommerce de tu tienda, accede a Modulos > Pago. Pulsa el botón Instalar Módulo y selecciona el módulo PagaMasTarde.
6. Una vez instalado, selecciona el módulo pagamastarde de la lista de módulos disponibles y pulsa Editar.
7. Configura el código de cuenta y la clave de firma con la información de tu cuenta que encontrarás en [el panel de gestión de Pagamastarde](https://bo.pagamastarde.com/shop). Ten en cuenta que para hacer cobros reales deberás activar tu cuenta de pagamastarde.com.

### Soporte

Si tienes alguna duda o pregunta no tienes más que escribirnos un email a soporte@pagamastarde.com.


### Release notes

#### 2.0.0

- compatible con las versiones 2.2 y 2.3 de OsCommerce.
- Elimina necesidad de editar ficheros del template.
- Solución de Bugs.
- mejora de la compatibilidad con OsCommerce.
- manda más información a la página de paga+tarde para augmentar la conversión.

#### 1.0.0

- Versión inicial
