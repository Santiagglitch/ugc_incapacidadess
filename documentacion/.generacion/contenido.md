# 1. Alcance, versión y forma de usar este manual

Este documento explica el funcionamiento, la estructura y el mantenimiento del proyecto ugc_incapacidadess. Está dirigido a quien necesita operar el portal, modificar su diseño, ajustar sus reglas o publicarlo en otro servidor. Las explicaciones se basan en los archivos de la copia local C:\xampp\htdocs\ugc_incapacidadess, revisada el 14 de octubre de 2026, zona America/Bogota, con referencia Git f5e3944. Las referencias archivo:línea corresponden a esta revisión y pueden desplazarse después de editar el código.

La revisión cubre los 74 archivos versionados: 57 archivos PHP, la hoja de estilo, los tres scripts JavaScript propios, PDF.js y su worker, seis imágenes, un sonido y cuatro archivos de control/configuración del repositorio. Se inventariaron 28 clases y 382 funciones o métodos PHP con nombre, incluyendo los 38 métodos de SimpleXLSXGen. Las funciones JavaScript propias se describen en un anexo independiente. Los callbacks anónimos y las expresiones flecha se explican dentro de su módulo; no se contabilizan como métodos PHP con nombre.

El manual contiene instrucciones de cambio y hallazgos; no aplica esas modificaciones a la aplicación. No incluye contraseñas, tokens de almacenamiento ni los documentos personales de los usuarios. Los binarios se documentan por su uso y ubicación. Las bibliotecas de terceros se revisan en su integración y API local; no se ofrece una explicación de cada rutina interna del bundle minificado de PDF.js.

Se comprobó la sintaxis de los 57 archivos PHP con PHP CLI 8.2.0. No se ejecutaron conexiones Oracle o LDAP, consultas a producción ni operaciones de negocio. Por tanto, las tablas, restricciones e identidades descritas son contratos deducidos del código, no una certificación del esquema instalado. Tampoco se da por comprobado el comportamiento visual en un navegador. El documento distingue hechos del código, problemas reproducibles mediante inspección y aspectos que requieren una prueba de integración.

Cómo localizar una modificación: busque primero el objetivo en el capítulo 12, después consulte la configuración o módulo correspondiente, y finalmente use el catálogo de métodos para encontrar su firma y línea. En Word, los títulos permiten usar el panel de navegación; el índice inicial enlaza con los capítulos. Las tablas describen relaciones y los bloques monoespaciados contienen ejemplos adaptables, no credenciales reales.

La pestaña del IDE apunta a una copia temporal de FileZilla: C:\Users\KevinS.SaenzR\AppData\Local\Temp\fz3temp-3\acerca_de.php. Su hash difiere de views/auth/acerca_de.php. Este manual toma el repositorio como referencia; no afirma que la copia temporal ni el servidor FTP coincidan con él. Antes de publicar cambios hechos desde FileZilla, compárelos con el archivo del repositorio y conserve una copia de ambas versiones.

# 2. Qué hace el sistema y cómo se usa

El portal registra solicitudes de ausentismo/permisos con fechas, duración, observaciones, jefe revisor y comprobante PDF. Un jefe decide primero y Talento Humano realiza la decisión final. También existen administración de roles, consulta de empleados, auditoría, notas internas, notificaciones y exportaciones Excel/CSV. Aunque la carpeta y algunos mensajes usan «incapacidades», el catálogo actual contiene diez tipos de permiso; no se debe suponer un módulo médico independiente ni reglas legales incorporadas.

## 2.1 Acceso y selección de modo

El usuario ingresa documento y contraseña institucional. authModel::login autentica contra LDAP y además exige encontrar un empleado activo en Oracle ICEBERG. Estar en LDAP o figurar en SUPER_ADMIN_NITS no sustituye la validación laboral. El resultado guarda en sesión cedula, nombre, email, rol, nivel y centro_costo. El jefe no se consulta durante el login.

Los superusuarios, administradores adicionales y usuarios fijos de Talento Humano pasan por un selector de modo. Un superusuario puede elegir super_usuario o empleado. Los otros dos grupos eligen admin o empleado. El modo empleado recalcula el rol usando directamente NITS_JEFES_REVISORES: si pertenece a esa lista será jefe; en caso contrario será solicitante. Las facultades administrativas dependen del rol guardado en sesión y los controles de cada endpoint.

Estado de esta copia: config/app.php:8 tiene VACACIONES_ACTIVAS=true. authController::loginForm y loginPost muestran views/auth/vacaciones.php y bloquean el login. El mensaje estático dice «Volvemos el 13 de octubre»; no existe una comparación de fecha que reactive el acceso automáticamente. Cambiar el texto no cambia la bandera. Esta función tampoco cierra automáticamente las sesiones que ya estaban autenticadas.

## 2.2 Crear, consultar, editar y eliminar

1. Abrir Nueva solicitud desde el panel operativo.
2. Elegir uno de los tipos del catálogo y un jefe de la lista disponible; no se permite seleccionarse a sí mismo.
3. Elegir HORAS, DIAS o activar Días + horas adicionales, equivalente a AMBAS.
4. Indicar fecha o rango. Los días se calculan incluyendo inicio y fin, excluyendo únicamente los domingos. Se incluyen los sábados y no hay calendario de festivos.
5. Ingresar observaciones y adjuntar un PDF válido de hasta 5 MiB, equivalentes a 5.242.880 bytes.
6. Enviar. El estado inicial es PENDIENTE_JEFE y se intenta notificar al jefe asignado.

Los formularios usan JavaScript para mostrar campos según la duración. El servidor repite las validaciones principales y vuelve a calcular los días. Para HORAS fuerza fecha_fin=fecha_inicio y anula los días; para DIAS anula las horas. Se permiten horas en incrementos de 0,5 hasta 5, días hasta 365 y observaciones hasta 2.000 caracteres. El límite del navegador de cinco meses hacia el futuro no está replicado en la validación del servidor.

En la lista, Ver abre el detalle. El dueño puede Editar o Eliminar solamente mientras esté en PENDIENTE_JEFE. Eliminar hace una baja lógica ACTIVO=0: no borra físicamente la fila ni elimina el PDF. En una edición se conserva el PDF actual si no se selecciona otro. Si cambia el comprobante, primero se guarda el nuevo, después se actualiza la solicitud y luego se intenta eliminar el anterior.

## 2.3 Jefe, Talento Humano y cancelación

El jefe asignado, con rol jefe o administrador, puede decidir sobre una solicitud ajena que esté en PENDIENTE_JEFE. Aprobar la pasa a APROBADO_JEFE, mostrado al usuario como Pendiente RRHH. Rechazar exige motivo y la pasa a RECHAZADO_JEFE. Las aprobaciones rápidas de la tabla pueden enviarse sin observación.

Talento Humano o un administrador puede decidir sobre solicitudes ajenas en APROBADO_JEFE: aprobar produce APROBADO_RRHH; rechazar produce RECHAZADO_RRHH y exige motivo. Hay una excepción: cuando el solicitante pertenece a NITS_TALENTO_HUMANO, la aprobación del jefe produce directamente APROBADO_RRHH, guarda un mensaje de aprobación final automática y deja NIT_RRHH en NULL.

El dueño puede cancelar su propia solicitud en APROBADO_JEFE o APROBADO_RRHH, indicando motivo. La cancelación produce CANCELADO_AGENDA y conserva el registro y el documento. La tabla contiene el flujo visible de cancelación. El detalle incluye el formulario/modal, pero en el código revisado falta un botón visible que lo active; use la tabla mientras se revisa ese punto.

## 2.4 Administración y superusuario

El administrador consulta el directorio de empleados, sus perfiles y solicitudes, exporta CSV y puede intervenir o forzar estados de solicitudes ajenas. Intervenir acepta pendiente de jefe, aprobado de jefe y rechazos de jefe/RRHH; la decisión administrativa genera un estado final de RRHH y agrega una observación. Forzar estado admite los cinco estados ordinarios, excluye CANCELADO_AGENDA como destino y requiere motivo. No se observa envío de notificaciones en estas dos acciones administrativas.

Las notas internas se muestran solo a administradores. La asignación y retiro de administradores adicionales exige además ser superusuario. El límite actual es 10 administradores adicionales, definido en adminRolesModel, separado de la lista fija de superusuarios. El panel Super Usuario muestra salud, sesiones, roles y auditoría; su bloque de configuración es informativo y no permite editar valores desde la pantalla.

## 2.5 Matriz práctica de permisos

|Operación|Solicitante|Jefe|Talento Humano|Administrador|Superusuario en modo administrativo|
|Crear solicitud propia|Sí|Sí|Sí|Endpoint habilitado; panel sin botón general|Endpoint habilitado|
|Ver solicitud|Propia|Propia o asignada|Todas las activas|Todas las activas|Todas las activas|
|Editar/eliminar|Propia pendiente jefe|Propia pendiente jefe|Propia pendiente jefe|Propia pendiente jefe|Propia pendiente jefe|
|Gestionar como jefe|No|Ajena asignada y pendiente|Solo al operar como jefe|Ajena asignada y pendiente|Ajena asignada y pendiente|
|Decisión RRHH|No|No|Ajena aprobada jefe|Ajena aprobada jefe|Ajena aprobada jefe|
|Cancelar|Propia aprobada jefe/RRHH|Misma condición|Misma condición|Misma condición|Misma condición|
|Intervenir/forzar/notas|No|No|No en rol RRHH|Sí; propias excluidas en intervenir/forzar|Sí|
|Directorio de empleados|No|No|No en rol RRHH|Sí|Sí|
|Asignar/quitar admin|No|No|No|Solo si también es superusuario|Sí|
|Panel SA y auditoría CSV|No|No|No|Solo si también es superusuario|Sí|

# 3. Arquitectura y organización del código

La aplicación usa un patrón MVC sencillo implementado directamente en PHP. No aparecen composer.json, package.json, un framework de rutas, migraciones SQL ni una suite de pruebas versionada. Los archivos se cargan mediante require_once. El despliegue está pensado para copiar archivos por FTP; config/server.php sustituye el uso de .env según su comentario inicial.

DIAGRAMA_ARQUITECTURA

## 3.1 Responsabilidad por carpeta

|Ubicación|Responsabilidad|Qué suele cambiarse aquí|
|index.php|Router principal y despacho GET/POST|Agregar una pantalla o acción y su controlador|
|config/|Entorno, conexiones, roles, helpers y seguridad|URL base, autenticación, constantes, sesión y catálogo|
|controllers/|Permisos, validación, coordinación y respuestas|Flujos, decisiones, mensajes, datos enviados a vistas|
|models/|SQL y persistencia Oracle|Filtros, columnas, búsquedas, conteos y cambios de estado|
|services/|PDF, notificaciones, notas, bloqueo y sesiones|Integraciones y operaciones reutilizables|
|views/|HTML/PHP de cada pantalla y scripts internos|Textos, estructura, controles, interacción particular|
|assets/css/|Estilo principal y adaptaciones móviles|Colores, medidas, componentes y responsive|
|assets/js/|Selector de jefe, Acerca de, preview móvil|Interacción compartida del navegador|
|assets/img/ y assets/sounds/|Recursos visuales y audio|Logo, favicon, fondo, mascotas y notificación|
|assets/vendor/pdfjs/|PDF.js local y worker|Actualizar ambos componentes de forma coordinada|
|Export/|Reportes, biblioteca XLSX y protección del ZIP|Hojas, columnas, filtros y formato Excel|
|storage/|Datos de ejecución y documentos privados|Permisos, ubicación de archivos y copias de respaldo|
|uploads/|Carpeta existente/legada, protegida para PHP|No es la ruta principal usada por el servicio PDF actual|

## 3.2 Petición de pantalla

Una petición llega a index.php. Este carga helpers y controladores; los helpers cargan seguridad, app y server. Se obtiene action primero desde POST y después GET; view proviene de GET. Se obtiene el método desde _method enviado en POST o REQUEST_METHOD. Luego se inicia sesión, se aplican cabeceras y se despacha el controlador. Las rutas de almacenamiento central PDF se procesan antes de iniciar la sesión común y se autentican con su token interno.

Un controlador invoca requiere_login o requiere_rol, consulta modelos, prepara arrays y llama render_view. Esta función usa extract con EXTR_SKIP, captura el HTML de la vista con output buffering y lo inserta como $content dentro de views/layouts/main.php o auth.php. No hay un motor de plantillas externo. Las vistas compartidas reciben variables del mismo scope: cambiar sus nombres o dejar valores residuales entre includes puede afectar otra tabla del panel.

## 3.3 Herencia y separación de bases

mainModel contiene las operaciones OCI8: preparar, enlazar parámetros, ejecutar, recuperar filas, liberar statements y formar listas IN. mainModelUXXI hereda esos métodos, cambia conectar a ConexionUXXI y define SQL_NOW_CO. empleadoModel hereda mainModel y lee la fuente laboral ICEBERG. solicitudModel, notificacionModel, adminRolesModel, adminAuditoriaModel y varios servicios heredan mainModelUXXI y escriben en la base de permisos.

Los tres ExportController tienen el mismo nombre corto pero namespaces distintos: App\Export\Admin, App\Export\Jefe y App\Export\RRHH. EmpleadoInfoEnricher y SafeXlsx usan App\Export\Shared; SimpleXLSXGen usa Shuchkin. El resto de las clases propias no usa namespace. Al mover archivos se deben ajustar todos los require y no mezclar estos nombres con clases globales.

# 4. Rutas, acciones y contratos de entrada

La URL canónica que generan los helpers es /ugc_incapacidadess/index.php?view=nombre para las vistas, y ?action=nombre para acciones. .htaccess también reescribe rutas cortas, pero no agrega controles de negocio. El valor actual de RewriteBase contiene /sede/bogota/ugc_incapacidadess/, mientras BASE_URL contiene /ugc_incapacidadess; deben alinearse para el servidor que se use.

## 4.1 Pantallas y respuestas GET

TABLA_RUTAS_GET

## 4.2 Acciones que modifican datos

TABLA_RUTAS_POST

Los formularios de modificación ordinarios deben enviar _csrf_token; algunos usan _method=PUT o DELETE, aunque la petición HTTP real sigue siendo POST. No basta agregar un botón en HTML: la acción debe estar en index.php y validar rol, propiedad, estado e inputs en su controlador. El parámetro return_to conserva el regreso a la pantalla; solicitudController lo valida con app_url_interna, mientras varias acciones de adminController usan el valor sin ese mismo filtro.

## 4.3 Endpoints fuera del router principal

|Endpoint|Entrada|Control de acceso y comportamiento|
|Export/Admin/ExportController.php|download=1; fecha_desde y fecha_hasta opcionales|ROL_ADMIN; genera XLSX; no hereda búsqueda q ni tipo del panel|
|Export/Jefe/ExportController.php|download=1|ROL_JEFE o ROL_ADMIN; alcance del jefe actual y solicitudes personales|
|Export/RRHH/ExportController.php|download=1|ROL_RRHH o ROL_ADMIN; reporte completo y solicitudes propias|
|controllers/conflictoJefesController.php|accion y nit opcional|Código de API directa; Apache la bloquea por la regla /controllers; no está en index.php|

Las exportaciones llaman requiere_rol y comprueban vigencia/selector mediante los helpers. El controlador de conflictos no contiene un control equivalente: no debe habilitarse acceso directo como solución a la ruta bloqueada. Su vista también presenta una ruta relativa de include incorrecta y no está conectada al router.

# 5. Configuración: dónde cambiar cada valor

## 5.1 Configuración del servidor

Todas las siguientes constantes están en config/server.php. Conserve los nombres; cambiar un valor es diferente de renombrar la constante. Las credenciales se editan únicamente en el archivo de configuración del entorno y no se copian en este manual.

|Constante|Valor/condición de esta copia|Efecto y precaución|
|APP_NAME|Sistema de Solicitudes - UGC|Título de layouts; algunos títulos de pantalla son literales y requieren editar la vista|
|APP_VERSION|2.0.0-simple|Información del panel; no selecciona una migración ni un algoritmo|
|APP_ENV|development|Muestra errores PHP y activa nombres/datos auxiliares de desarrollo; production oculta errores y habilita HSTS|
|BASE_URL|/ugc_incapacidadess|Prefijo de URLs de vistas, acciones y assets; no configura Apache|
|APP_TIMEZONE|America/Bogota|Fechas PHP, nombres de archivos y textos; UXXI además usa una expresión SQL UTC-05 independiente|
|SESSION_SAVE_PATH|Cadena vacía|Usa el directorio configurado por PHP; una ruta no vacía se usa si existe y es escribible|
|SESSION_IDLE_TIMEOUT|1800 segundos|Vigencia por inactividad; no modifica automáticamente todos los TTL auxiliares|
|APP_ROWS_PER_PAGE|6|Paginación de solicitudes; el directorio de empleados tiene su propio selector 6/12/24|
|SOLICITUDES_STORAGE_PATH|storage/solicitudes dentro del proyecto|Directorio físico principal de comprobantes|
|PDF_STORAGE_CENTRAL_IP|127.0.0.1|Se compara literalmente con SERVER_ADDR para decidir escritura local o remota|
|PDF_STORAGE_CENTRAL_URL|http://localhost/ugc_incapacidadess/index.php|Destino HTTP de upload/file/delete remotos; cada servidor tiene su propio localhost|
|PDF_STORAGE_TOKEN|Secreto configurado; omitido|Debe coincidir entre central y clientes; se envía en X-PDF-Storage-Token|
|PDF_STORAGE_CONNECT_TIMEOUT|5 segundos|Tiempo máximo para establecer conexión cURL|
|PDF_STORAGE_TIMEOUT|20 segundos|Tiempo máximo de operación cURL|
|ORACLE_HOST/PORT/SERVICE|Valores institucionales configurados; omitidos|Conexión ICEBERG laboral; no son los de UXXI|
|ORACLE_USER/PASS|Credenciales configuradas; omitidas|Acceso a la fuente laboral; guardar fuera de documentos compartidos|
|ORACLE_CHARSET|AL32UTF8|Codificación de la conexión|
|ORACLE_PERSISTENT|true|Habilita oci_pconnect si existe; cerrar no cierra una conexión persistente|
|ORACLE_STATEMENT_CACHE_SIZE|40|Caché OCI8 en Conexion; no aplica una caché de datos de negocio|
|LDAP_HOST/PORT|Host configurado; puerto 389|ldap_conectar forma una URI ldap://; cambiar solo el puerto no implementa LDAPS|
|LDAP_USER/PASSWORD|Cuenta de servicio configurada; omitida|Bind inicial obligatorio incluso en modo de login directo|
|LDAP_TREE|Base DN institucional configurada|Base de búsqueda y sufijo del DN cn=documento|
|LDAP_DIRECT_CN_LOGIN|true|Bind del usuario por cn; false usa búsqueda uid y luego bind|
|UXXI_HOST/PORT/SERVICE|Valores institucionales configurados; omitidos|Base operativa de solicitudes y servicios|
|UXXI_USER/PASS|Credenciales configuradas; omitidas|Necesita permisos de escritura de los módulos realmente usados|
|UXXI_CHARSET|AL32UTF8|Codificación de la conexión operativa|

LDAP_NETWORK_TIMEOUT y LDAP_SEARCH_TIMEOUT son puntos de extensión consultados por conexion_ldap.php, pero no están definidos en server.php en esta revisión. Si se agregan, revise el efecto en ldap_set_option y ldap_search y pruebe tiempos de espera reales. No confunda estas opciones con los timeouts del almacenamiento PDF.

## 5.2 Configuración funcional

|Constante en config/app.php|Uso real|Cómo modificar|
|VACACIONES_ACTIVAS|Bloquea GET/POST de login|Cambiar true/false y actualizar el mensaje en views/auth/vacaciones.php|
|ROL_ADMIN/RRHH/JEFE/EMPLEADO|Valores internos de rol|Evitar renombrar sin revisar sesión, vistas, exportaciones y consultas|
|SUPER_ADMIN_NITS|Lista fija normalizada de superusuarios|Agregar/quitar documentos como cadenas; exige además LDAP y empleado autorizado|
|TALENTO_HUMANO_NIT y TALENTO_HUMANO_NIT_2|Dos documentos fijos|NITS_TALENTO_HUMANO deriva de ambos; afecta selector, aprobación especial y avisos|
|NITS_JEFES_REVISORES|Lista de revisores y rol operativo|Editar la lista; debe existir empleado activo y pasar selección/control de formulario|
|CC_RRHH|Array vacío en esta copia|Notificaciones lo interpreta como centros de costo; otros helpers lo comparan con NIT: revisar esa ambigüedad antes de llenarlo|
|CC_APRENDICES|Tres centros de costo|Etiqueta de aprendiz en perfil; no cambia automáticamente permisos ni duración|
|NIVEL_MIN_JEFE/NIVEL_MIN_ADMIN|4 y 7|Se muestran en panel; el rol actual se determina por listas/tabla, no por estas cifras|
|ESTADO_*|Seis valores de estado|Constantes coordinadas con literales SQL, badges, KPIs y exportaciones|
|TIPOS_SOLICITUD|Diez claves con etiquetas|Añadir etiqueta alimenta selects, búsquedas y avisos; cambiar una clave afecta registros existentes|
|USUARIOS_PRUEBA|Datos derivados de superusuarios|Nombres y revisores auxiliares en development; no evita autenticación LDAP ni empleado activo|

## 5.3 Valores que están fuera de server.php

|Valor|Ubicación|Alcance|
|CSRF_TOKEN_TTL=900 y CSRF_GRACE_PERIOD=90|config/seguridad.php|Expiración y gracia del token; no coinciden automáticamente con timeout de sesión|
|session.gc_maxlifetime=3600 y nombre UGC_SOL|iniciar_sesion_segura|Recolección de sesiones y nombre de cookie|
|MAX_INTENTOS=3 y VENTANA=900|services/loginBloqueoService.php|Bloqueo por documento durante quince minutos|
|MAX_ADMINS=10|models/adminRolesModel.php|Cupo de administradores adicionales|
|TTL de sesiones activas=1800|services/sesionActivaService::__construct|Visibilidad/purga del registro auxiliar|
|cooldown=5|adminController::verificarRateLimit|Frecuencia de cambios de roles por clave/sesión|
|PDF máximo 5 MiB|Controlador de solicitud, controlador storage y ambos formularios/scripts|Debe coordinarse con php.ini y proxy/servidor central|
|Horas 0,5 a 5; paso 0,5|solicitudController y formularios|Servidor fija el límite; form_editar conserva max=8 en HTML, aunque JS/PHP limitan 5|
|Máximo 365 días|solicitudController::validarSolicitud|Tope de días calculados|
|Horizonte visual de 5 meses|sumarMeses(hoy,5) en ambos formularios|Restricción de navegador; el servidor no la replica|
|20 avisos en la lista|notificacionModel::getNoLeidas|El contador puede ser mayor que los avisos desplegados|
|Polling 60.000 ms; sonido volumen 0,8|views/layouts/main.php|Actualización de campana y reproducción local|
|Carrusel cada 20.000 ms|views/auth/acerca_de.php y acerca-de.js|También actualizar el texto «20 segundos»|
|Auditoría 20 por página; perfil 8 entradas|adminController|Separado de APP_ROWS_PER_PAGE|
|Tendencia 6 meses; límite máximo 24|superAdminPanel y solicitudModel::contarPorMes|La serie se consulta, pero no se dibuja un gráfico en panel.php|

# 6. Datos, consultas y persistencia Oracle

## 6.1 Fuente laboral ICEBERG

empleadoModel usa EMPLEADO y, para exportación, CENTRO_COSTO. El filtro laboral repetido exige EMPRESA en BA2 o BDO, ESTADO='A' y DEPARTAMENTO en 11, 25 o 17. Las búsquedas individuales/lotes prefieren el vínculo más reciente por FECHA_INGRESO y EMPLEADO; getTodos no usa esa misma deduplicación, por lo que puede mostrar más de una fila para el mismo NIT si hay vínculos concurrentes.

El nombre completo se concatena desde NOMBRE, PRIMER_APELLIDO y SEGUNDO_APELLIDO. getInfoExportacionPorNits añade NOMBRE_CENTRO_COSTO mediante LEFT JOIN por ORGANIZACION/CENTRO_COSTO y centro activo. Las consultas de nombres/exportación se fragmentan en lotes de 900 NIT; mantenga ese mecanismo al aumentar volumen. buscarNitsPorNombreONit limita resultados a 200 por defecto y como máximo 500.

getJefeInmediato contiene una CTE que busca una persona de mayor nivel en el mismo centro y después en un centro cabecera según un CASE de agrupación. Está disponible como método, pero no aparece una llamada en el flujo actual: los formularios requieren seleccionar jefe de la lista fija validada. Además el centro 2341001 aparece en más de una rama del CASE; prevalece la primera rama coincidente. Modificar esta CTE no cambia automáticamente el selector actual.

## 6.2 Contrato de tablas operativas UXXI

|Tabla|Datos usados por el código|Operaciones y generación de ID|
|SOLICITUDES_PERMISOS|ID, NIT_EMPLEADO, NIT_JEFE, NIT_RRHH, TIPO_SOLICITUD, FECHA_INICIO/FIN, DURACION_HORAS/DIAS, TIPO_DURACION, OBSERVACIONES, OBSERVACION_JEFE/RRHH, RUTA_COMPROBANTE, ESTADO, ACTIVO, FECHA_CREACION/MODIFICACION/GESTION_JEFE/GESTION_RRHH, MOTIVO_CANCELACION, NIT_CANCELADOR, FECHA_CANCELACION|INSERT sin ID explícito, UPDATE, SELECT; mecanismo ID debe verificarse en el esquema|
|NOTIFICACIONES|ID, NIT_DESTINATARIO, TIPO, MENSAJE, ID_SOLICITUD, LEIDA, FECHA_CREACION, FECHA_LECTURA|INSERT sin ID explícito y UPDATE de lectura; JOIN con solicitudes activas|
|ADMIN_ROLES|ID, NIT_ADMIN, ACTIVO, FECHA_ASIGNADO, NIT_ASIGNADO_POR|SEQ_ADMIN_ROLES.NEXTVAL; baja lógica|
|ADMIN_AUDITORIA|ID, NIT_ACTOR, NOMBRE_ACTOR, ACCION, DESCRIPCION, IP, NIT_OBJETIVO, ID_SOLICITUD, META_JSON, FECHA|SEQ_ADMIN_AUDITORIA.NEXTVAL; inserción y lectura/CSV|
|NOTAS_ADMIN|ID, ID_SOLICITUD, NIT_ADMIN, NOMBRE_ADMIN, NOTA, FECHA_CREACION|SEQ_NOTAS_ADMIN.NEXTVAL; inserción y listado|
|LOGIN_BLOQUEOS|CLAVE, INTENTOS, PRIMER_INT, ULTIMA_ACT, IP_ULTIMA, EXPIRA|Clave MD5 del documento; INSERT/UPDATE/DELETE de expirados|
|SESIONES_ACTIVAS|SESSION_ID, NIT, NOMBRE, ROL, CENTRO_COSTO, IP, USER_AGENT, ULTIMA_ACT, FECHA_INICIO|Clave de sesión PHP; INSERT/UPDATE/DELETE auxiliar|

No hay DDL de instalación en el repositorio. Los tipos, longitudes, índices, permisos, claves foráneas, secuencias, triggers y políticas de retención deben confirmarse con el responsable de la base. No cree una tabla «aproximada» basándose únicamente en los nombres. Se pueden preparar consultas de diagnóstico de solo lectura, como los siguientes ejemplos; no se ejecutaron durante esta revisión:

```sql
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, DATA_LENGTH, NULLABLE
FROM USER_TAB_COLUMNS
WHERE TABLE_NAME IN ('SOLICITUDES_PERMISOS', 'NOTIFICACIONES',
                     'ADMIN_ROLES', 'ADMIN_AUDITORIA', 'NOTAS_ADMIN',
                     'LOGIN_BLOQUEOS', 'SESIONES_ACTIVAS')
ORDER BY TABLE_NAME, COLUMN_ID;

SELECT SEQUENCE_NAME FROM USER_SEQUENCES
WHERE SEQUENCE_NAME IN ('SEQ_ADMIN_ROLES', 'SEQ_ADMIN_AUDITORIA',
                         'SEQ_NOTAS_ADMIN');

SELECT ESTADO, ACTIVO, COUNT(*) AS TOTAL
FROM SOLICITUDES_PERMISOS
GROUP BY ESTADO, ACTIVO;
```

Estas consultas describen el esquema del usuario conectado; si usa sinónimos u otro propietario habrá que adaptar la consulta al diccionario ALL_* y sus permisos. La arquitectura SQL utiliza OFFSET/FETCH y funciones analíticas: comprobar compatibilidad en el servidor Oracle real es parte de la instalación.

## 6.3 Fechas, transacciones y errores

mainModelUXXI::SQL_NOW_CO usa CAST(SYSTIMESTAMP AT TIME ZONE '-05:00' AS DATE). Las lecturas convierten fechas a cadenas YYYY-MM-DD HH24:MI:SS, y los helpers/exportaciones presentan DD/MM/YYYY con hora AM/PM. Cambiar APP_TIMEZONE no modifica esa expresión SQL: un cambio de zona debe coordinarse en ambos lugares.

mainModel::ejecutarConsulta enlaza valores por nombre, usa OCI_DEFAULT para lecturas y devuelve false ante error. consultarTodo devuelve [] y consultarUno devuelve NULL si falla, con mensajes en error_log. ejecutar usa OCI_COMMIT_ON_SUCCESS y devuelve éxito de ejecución, sin comprobar filas afectadas. Una lista vacía puede significar ausencia de datos o una consulta fallida; revise logs antes de concluir que no hay registros.

Los cambios de solicitud, sus notificaciones, el archivo PDF y la auditoría se realizan en operaciones separadas; no hay una transacción única que los abarque. Existe compensación para borrar un PDF nuevo si falla el guardado principal. La obtención del último ID por empleado después del INSERT no usa RETURNING: solicitudes simultáneas del mismo empleado pueden asociar la notificación a otra fila. Estos puntos están registrados como hallazgos, no como fallos demostrados en producción.

## 6.4 Listados, búsqueda y conteos

LIST_SELECT contiene columnas suficientes para listados; DETAIL_SELECT añade duración tipo, cancelación, ACTIVO y fechas ampliadas. Si se agrega una columna a la tabla, revisar ambos selectores: añadirla solo en detalle no la hace disponible en XLSX. actualizarSolicitudEmpleado no guarda TIPO_DURACION aunque el formulario lo envía, por lo que ese dato puede quedar desactualizado tras cambiar modalidad.

buildSolicitudWhere reúne los filtros ACTIVO, estado(s), tipo, NIT, dueño, jefe, exclusión del jefe como solicitante y rango de creación. fecha_hasta usa un límite exclusivo al día siguiente. La búsqueda q se recorta a 100 caracteres, escapa %/_ para LIKE e intenta reconocer tanto las claves como las etiquetas de TIPOS_SOLICITUD. El controlador puede buscar NIT por nombre en ICEBERG y pasar esos NIT al filtro UXXI.

Los dashboards paginan en SQL y enriquecen nombres en lotes. La ruta solicitudes, en cambio, obtiene arrays completos y la vista shared/pagination.php los corta en memoria. El directorio de empleados también carga todos los empleados, filtra/ordena en PHP y corta la página; sus conteos de solicitudes se hacen por los NIT de la página. Para grandes volúmenes, mover filtros al SQL requiere mantener los mismos permisos y conteos.

# 7. Estados y reglas de negocio

DIAGRAMA_ESTADOS

|Estado interno|Texto habitual|Significado|
|PENDIENTE_JEFE|Pendiente jefe|Creada y editable por el dueño; espera jefe asignado|
|APROBADO_JEFE|Pendiente RRHH|Superó primera aprobación; requiere decisión final ordinaria|
|RECHAZADO_JEFE|Rechazado jefe|Rechazo del primer revisor; no se edita por el flujo del dueño|
|APROBADO_RRHH|Aprobado RRHH|Aprobación final ordinaria o excepción de TH|
|RECHAZADO_RRHH|Rechazado RRHH|Rechazo final de Talento Humano/administración|
|CANCELADO_AGENDA|Cancelado usuario|Cancelación del dueño tras una aprobación|

La categoría Aprobadas del solicitante incluye APROBADO_JEFE y APROBADO_RRHH: no equivale siempre a aprobación final. La tarjeta Aprobadas por ti del jefe incluye también RECHAZADO_RRHH, porque describe la decisión anterior del jefe. «Solicitudes activas» en el directorio cuenta pendiente de jefe, aprobado jefe y aprobado RRHH entre registros ACTIVO=1; no es una comprobación de si la fecha del permiso está en el futuro.

Para cambiar una regla de permisos, coordine la condición en la vista, el método puede* del controlador y el WHERE del modelo. Ocultar un botón no es suficiente para impedir una petición directa. Mantenga la prohibición de gestionar la propia solicitud y la comparación normalizada de documentos; añada pruebas de acceso por URL/POST además de revisar el diseño.

El modelo cancelarAgenda acepta también el literal PENDIENTE_APROBACION_RRHH, pero el controlador y las vistas no habilitan ese estado y config/app.php no lo define. Es una compatibilidad residual: no se documenta como un séptimo estado ordinario. Los parámetros permitirAprobadoJefe de editar/eliminar y esGestor de cancelar amplían opciones del modelo, pero la interfaz y el controlador actuales aplican permisos más restrictivos.

# 8. Autenticación, sesión y controles de seguridad

## 8.1 LDAP y autorización laboral

ldap_conectar abre ldap://, configura protocolo v3, desactiva referrals y hace bind con la cuenta de servicio. Si LDAP_DIRECT_CN_LOGIN=true, ldap_autenticar_usuario construye cn=documento,LDAP_TREE, hace bind del usuario y devuelve un perfil básico. Si es false, escapa el documento para filtro uid, busca una entrada y vuelve a hacer bind con su DN. En ambos casos libera la conexión en finally. El modo directo mejora el recorrido de búsqueda, pero depende de la estructura real del directorio.

authModel recorta tanto documento como contraseña con trim. Una contraseña institucional que incluya espacios al principio o final no se transmite idéntica. Después exige empleado activo y calcula rol. No hay recuperación/cambio de contraseña, registro de cuentas locales, MFA ni login ficticio implementado en este código.

El diagnóstico LDAP se escribe en storage/logs/ldap_auth.log en formato JSON por línea, con intento_id, etapa, resultado, duración y causa probable. ldap_diagnostico_sanitizar elimina campos cuyo nombre indica contraseña/secret/token, pero el registro conserva documento, DN, cuenta de servicio e IP. Limite su acceso y defina retención; no lo trate como un log público de depuración.

## 8.2 Sesión y CSRF

La cookie se llama UGC_SOL, tiene HttpOnly, SameSite=Lax y secure según $_SERVER['HTTPS']. El path es /; dos aplicaciones del mismo host que usen ese mismo nombre pueden interferir. SESSION_SAVE_PATH vacío usa la ruta temporal de PHP. El código permite crear una ruta configurada y solo la usa cuando es escribible.

requiere_login comprueba autenticación, aplica el timeout de inactividad y redirige al selector si hay admin_modo_pendiente. validar_vigencia_sesion registra actividad en SESIONES_ACTIVAS; la consulta periódica de notificaciones también pasa por requiere_login y puede renovar last_activity mientras la pestaña visible hace polling. El timeout observado por un usuario puede diferir de «treinta minutos sin tocar el teclado».

csrf_token genera una cadena aleatoria con timestamp y expiración de 900 segundos; el login regenera ID de sesión y token, conservando una gracia de 90 segundos para el anterior. csrf_requerido acepta hidden _csrf_token o cabecera X-CSRF-Token; ante fallo cierra sesión y responde redirección/401 según AJAX. notificacionController::validarAjaxCsrf devuelve 401 JSON sin seguir exactamente el mismo cierre de sesión.

adminController::regenerarCsrf intenta llamar regenerar_csrf, que no existe en la copia revisada, y en su alternativa escribe $_SESSION['_csrf_token']. Los helpers reales leen $_SESSION['csrf_token']; esa alternativa no regenera el token usado por la aplicación. Si se corrige, coordine los formularios y la campana que mantienen un token en memoria.

## 8.3 Escape y cabeceras

e aplica htmlspecialchars a salidas HTML; limpiar_texto recorta y elimina etiquetas; normalizar_documento deja solo dígitos. Mantenga e para texto en HTML, json_encode para valores insertados en JavaScript y encodeURIComponent/http_build_query para parámetros URL. Son contextos distintos; escapar HTML no sustituye serializar JSON.

aplicar_headers_seguridad define nosniff, SAMEORIGIN, políticas de referencias/permisos, no-cache y CSP con nonce. Los bloques script propios suelen llevar nonce; los scripts externos locales son admitidos por 'self'. Los atributos inline onclick, onchange y onsubmit no quedan autorizados por poner nonce en otro script: varias confirmaciones/autosubmits existentes pueden ser bloqueadas por esa CSP. Migre esos handlers a addEventListener dentro de scripts permitidos.

HSTS se emite con APP_ENV=production. La detección HTTPS depende de la variable del servidor; detrás de un proxy debe verificarse el esquema que recibe PHP. El servicio PDF elimina la cabecera CSP al servir el binario y añade tipo PDF, longitud, inline y no-cache.

## 8.4 Controles de repetición y protección de archivos

La creación usa _submit_token de un solo uso en sesión, independiente del CSRF; se consume antes de validar/guardar. Si hay error, se debe abrir de nuevo el formulario para obtener otro. Los layouts bloquean botones/enlaces mientras se envía y los recuperan al volver desde la caché del navegador. Los cambios de roles tienen además un cooldown de cinco segundos por acción/NIT dentro de la sesión.

LOGIN_BLOQUEOS limita tres intentos por documento en una ventana de 900 segundos. El registro de IP no convierte el bloqueo en una política por IP. Los catches del controlador permiten continuar si este servicio falla; una falla de base puede dejar la protección sin efecto visible.

Apache bloquea acceso directo a .git/config/controllers/models/services/data/database/logs/storage/uploads y desactiva listado. storage/.htaccess deniega toda lectura y uploads/.htaccess deniega ejecución de PHP/phtml/phar. No se bloquea de igual forma el directorio views; no publique como pantallas autónomas los parciales, en especial la vista de conflictos que tiene una ruta de include incorrecta y carece de controles equivalentes al router.

# 9. PDFs: carga, almacenamiento y visualización

## 9.1 Validación y contrato

Al crear, documento_pdf es obligatorio. procesarArchivoPDF verifica error de upload, tamaño, extensión .pdf, MIME application/pdf o application/x-pdf y is_uploaded_file. El controlador central verifica tamaño mayor que cero, límite y MIME. Los formularios usan un control accept y verifican file.type; ese chequeo de navegador no reemplaza el MIME del servidor.

El servicio genera un nombre con documento normalizado, fecha, identificador uniqid y hasta 30 caracteres seguros del nombre original. Guarda en la base la ruta lógica storage/solicitudes/nombre.pdf. nombreSeguro toma basename y permite solo letras, números, guion, guion bajo y extensión PDF; no usa la ruta enviada para navegar fuera del directorio. El nombre puede contener el documento del empleado, de modo que el directorio tampoco debe ser público.

## 9.2 Servidor central y clientes

Si SERVER_ADDR es igual a PDF_STORAGE_CENTRAL_IP, se guarda localmente. En caso contrario, subirRemoto manda un POST multipart al central con name, documento_pdf y X-PDF-Storage-Token. Se espera HTTP 201 y JSON ok=true. Para borrar se espera HTTP 200 y ok=true. Para leer se espera HTTP 200 y contenido binario. El servicio intenta primero leer una copia local y solo después solicita la central.

La URL localhost solo funciona como central de la misma instalación. En varias máquinas debe ser una dirección alcanzable del servidor que realmente guarda los archivos. Verifique también IPv4/IPv6: SERVER_ADDR=::1 no es igual a 127.0.0.1, por lo que la instalación puede enviar una petición remota a sí misma. Los comentarios del archivo mencionan otra IP histórica; las constantes ejecutadas prevalecen sobre esos comentarios.

Los endpoints internos se protegen por token compartido y se despachan antes de la sesión normal. No validan el permiso de un usuario sobre una solicitud; ese control se hace en solicitudController::servirArchivo para la ruta pública autenticada. Proteja el token y el canal de transporte. Cambiar solo la constante no agrega HTTPS al servidor ni modifica LDAP.

## 9.3 Vista previa y lectura

En escritorio, crear/editar usa URL.createObjectURL para un iframe de preview antes del envío. En móvil o puntero coarse, pdf-mobile-preview.js intercepta el change y renderiza la primera página en canvas con PDF.js 3.11.174, su worker local e isEvalSupported=false. Si no logra renderizar, deja el archivo seleccionado y muestra un aviso; no afirma que la carga haya fallado. Ver documento abre la ruta solicitud_archivo con ID, autenticación y permiso sobre la fila.

PDF.js y pdf.worker.min.js deben actualizarse juntos. Preserve la ruta de worker calculada desde el script, el orden de carga de la biblioteca antes del adaptador y la CSP. El adaptador móvil no es un visor completo de todas las páginas del adjunto ni un mecanismo de aprobación.

## 9.4 Cómo cambiar tamaño o ubicación

1. Cambiar el límite en solicitudController::procesarArchivoPDF y pdfStorageController::MAX_FILE_SIZE.
2. Cambiar MAX_FILE_SIZE en ambos formularios y el literal de pdf-mobile-preview.js; actualizar los textos de ayuda.
3. Ajustar upload_max_filesize y post_max_size en el php.ini que usa Apache, dando a POST espacio para archivo y campos; revisar también límites de proxy/central.
4. Si cambia ubicación, modificar SOLICITUDES_STORAGE_PATH y trasladar/copiar comprobantes mediante un procedimiento de respaldo, conservando sus nombres. La ruta lógica de la base sigue siendo storage/solicitudes/nombre salvo cambio coordinado.
5. Probar creación, sustitución, lectura y borrado compensatorio en central y cliente con archivos de tamaño límite y mayor al permitido.

No hay tarea automática de limpieza de PDFs ni política de retención en el repositorio. El borrado lógico deja el adjunto. Antes de eliminar huérfanos, compare nombres con referencias activas y eliminadas, respaldos y obligaciones internas de conservación.

# 10. Notificaciones, auditoría, notas y sesiones activas

## 10.1 Eventos y destinatarios

|Evento|Destinatarios|Efecto adicional|
|Nueva solicitud|Jefe seleccionado|Se crea NUEVA_SOLICITUD|
|Jefe aprueba ordinaria|Solicitante y usuarios RRHH|Marca leídos avisos del jefe para esa solicitud|
|Jefe aprueba solicitud propia de TH|Solicitante|Aprobación final; marca aviso del jefe; no crea revisión RRHH ordinaria|
|Jefe rechaza|Solicitante|Incluye extracto de observación y marca avisos del jefe|
|RRHH aprueba/rechaza|Solicitante y jefe si son distintos|Marca avisos de usuarios RRHH y del gestor|
|Dueño cancela|Jefe y usuarios RRHH sin duplicados ni dueño|Incluye motivo resumido y marca avisos del dueño|
|Intervención/estado forzado admin|No se observa llamada al servicio|Se registra auditoría; revisar si se necesita aviso adicional|

usuariosRRHH reúne empleados de CC_RRHH, los NITS_TALENTO_HUMANO y determinados datos de prueba cuando APP_ENV=development. Un administrador adicional de ADMIN_ROLES no se añade automáticamente a esa lista. Los mensajes y tipos se editan en notificacionService; los estilos/iconos de tipos se determinan en main.php y ugc.css.

Los avisos son internos al portal, persistidos en NOTIFICACIONES. No existe envío de correo, SMTP, WhatsApp ni push del sistema operativo en este código. La campana consulta notif_count cada 60 segundos solo con la pestaña visible; notif_list devuelve hasta veinte avisos sin leer de solicitudes activas. El sonido depende de la interacción y políticas de audio del navegador y de si aumenta el contador; no garantiza un sonido por cada evento concurrente.

sessionStorage conserva un contador por documento y una marca de retorno silencioso de hasta cinco minutos. Se usa para evitar sonido repetido al regresar del PDF. Marcar un aviso lo cambia a leído y navega al detalle; Marcar todas actúa únicamente sobre el destinatario autenticado. notificacionController libera el bloqueo de sesión con session_write_close antes de consultar/actualizar avisos.

## 10.2 Auditoría

adminAuditoriaModel registra actor, nombre, acción, descripción, IP válida, objetivo, ID de solicitud y META_JSON. El controlador de autenticación registra ciertos logins privilegiados, cambios de modo e intentos fallidos; adminController registra asignación/retiro, intervención y estado forzado. No es un historial exhaustivo de todos los INSERT/UPDATE del sistema: la creación/edición ordinaria no aparece registrada en esta clase.

El panel filtra por acción y pagina veinte eventos. El perfil puede mostrar ocho entradas de cambios sobre el NIT objetivo. El CSV de auditoría exporta todos los eventos, no los filtros visibles del panel. Si agrega una acción, registre una clave estable y revise iconAccion/labelAccion/iconCls en panel.php; una acción desconocida se muestra con su nombre literal.

## 10.3 Notas y sesiones

notaAdminService inserta notas con secuencia y devuelve un array normalizado fecha/nit/nombre/nota. El controlador exige nota no vacía; el textarea limita a 1.000 caracteres, pero no hay un límite equivalente de longitud en guardarNotaAdmin. Además ese método no comprueba explícitamente la existencia/actividad de la solicitud: puede depender de las restricciones del esquema.

SESIONES_ACTIVAS es un registro auxiliar, no el almacenamiento real de las variables $_SESSION. Registra session_id, usuario, rol, IP y user agent; purga entradas con inactividad superior a su TTL y elimina la actual al cerrar sesión. El panel lista estas filas, pero no implementa un botón para cerrar remotamente sesiones ajenas. Retirar un administrador adicional no invalida inmediatamente el rol ya guardado en sus sesiones existentes.

# 11. Diseño y pantallas: mapa de edición

## 11.1 Estilo global y cascada

assets/css/ugc.css tiene aproximadamente 2.900 líneas físicas en esta revisión. Arranca con variables --green, --green2, --green3, --green4, --red, --border, --text, --muted, --surface, --gray, --bg, --shadow, --shadow2, --r, --r2 y --tr. Hay variaciones automáticas para prefers-color-scheme:dark. No existe un selector de tema persistido en sesión.

La hoja contiene varias redefiniciones de botones, cabecera, badges y celdas al final, algunas con !important. Por ejemplo, cambiar solo --green no cambia necesariamente el verde #0B5A2B de botones/encabezados, y header/footer fuerzan un fondo con login-fondo.png y una capa de color #063125. Antes de editar una regla, busque todos los selectores iguales y revise en el navegador cuál gana por orden/especificidad. Evite sumar otra copia de la misma regla sin revisar las existentes.

Mapa aproximado por línea: variables/tema 1; header 60; layout/footer 146/150; tabla 214; badges 237; botones/paginación 262; formularios/jefe picker 286; duración 326; detalle 361; login 458; Acerca de 483; mascota 609; tarjetas/KPIs 834; preview/documento 930/1061; notificaciones 1159; header con búsqueda 1518; overrides de header/botones/tablas 1775; badges nuevos 2082; responsive ampliado 2183; panel de cancelación 2610; ajustes finales de móviles 2761. El anexo CSS enumera los bloques detectados con sus líneas.

Se usan media queries para 1700, 1279, 1023/1024, 980, 819/820, 768, 767, 600, 575/576, 480/479, 414 y 374 px, además de tema oscuro. No modifique el desktop ignorando las reglas finales para móvil: las tablas se transforman en tarjetas con data-label; las acciones y paneles de cancelación requieren conservar esa semántica.

## 11.2 Layouts y componentes compartidos

|Archivo|Qué se ve|Puntos concretos de edición|
|views/layouts/main.php|Cabecera, buscador, campana, usuario, modos, salir, pie|Textos de navegación, fuentes Inter, acciones globales, polling/audio y bloqueos de envío|
|views/layouts/auth.php|Marco login/vacaciones/selector|Fondo mediante --ugc-login-bg-image, título, favicon, fuente y bloqueos de envío|
|views/shared/tabla_solicitudes.php|Tabla reutilizada en varios paneles|Encabezados, columnas, fechas, botones, permisos de visibilidad y panel de cancelación|
|views/shared/badge_estado.php|Etiqueta y clase de estado|Texto por estado y asociación badge-*|
|views/shared/dashboard_kpi_cards.php|Tarjetas enlazadas de resumen|Estructura común; valores/colores/SVG se definen en cada dashboard|
|views/shared/pagination.php|Paginación común|Ventana de páginas, rótulos y preservación de $_GET|
|views/shared/detalle.php|Información, decisiones, PDF, observaciones, admin|Distribución de tarjetas y formularios; coordinar con los métodos puede*|
|views/shared/solicitudes.php|Título de lista independiente|Contenedor que incluye tabla_solicitudes|
|views/shared/404.php|Error de ruta|Mensaje, enlaces y datos escapados de view|

La tabla tiene siete columnas, aunque el panel de cancelación conserva colspan=8. Algunos dashboards incluyen la tabla tres veces; una misma solicitud puede producir IDs repetidos de panel-cancelar-ID y funciones/listeners repetidos. Al reutilizar una tabla con scripts globales, use identificadores por instancia y registre listeners una vez. No se ha comprobado en navegador el efecto específico de estas duplicaciones.

## 11.3 Pantallas específicas

|Pantalla/archivo|Cambio típico|Dependencia funcional|
|auth/login.php|Título Portal Solicitudes De Ausentismo, labels, ojo contraseña, logo/mascota|authController/authModel; incluye Acerca de|
|auth/vacaciones.php|Mensaje y fecha de retorno|VACACIONES_ACTIVAS; también incluye Acerca de|
|auth/seleccionar_modo.php|Tarjetas, textos y estilos de modos|seleccionarModoPost; valores radio no deben cambiarse aisladamente|
|auth/acerca_de.php|Contactos, historia, imagen y temporizador|acerca-de.js y selectores .about-*|
|empleado/dashboard.php|Saludo y cinco tarjetas personales|estadisticasEmpleado e historial personal|
|jefe/dashboard.php|Tarjetas, pendientes/historial/propias, exportar|dashboardJefe y Export/Jefe|
|rrhh/dashboard.php|Tarjetas, pendientes finales/historial/propias|dashboardRrhh y Export/RRHH|
|admin/dashboard.php|Tarjetas, rangos fecha, indicadores extra, exportar|dashboardController::index, kpiAdmin y Export/Admin|
|empleado/form_crear.php|Campos, duración, preview, ayudas|crearPost, validación PHP, jefe-picker y preview móvil|
|empleado/form_editar.php|Valores iniciales, cambio de duración y PDF|editarPost y actualizarSolicitudEmpleado|
|admin/empleados/index.php|Directorio tabla/tarjetas, filtros, CSV|adminController::empleados; localStorage ugc_emp_view|
|admin/empleados/detalle.php|Perfil, pestañas, roles e historial|empleadoDetalle, cambio de roles y hash #solicitudes/#admin|
|superadmin/panel.php|Salud, sesiones, administradores, auditoría|superAdminPanel, health y autocompletado del superusuario|
|admin/conflictos/index.php|Informe legado de conflictos|No integrado; include/rutas/control de acceso requieren reparación previa|

## 11.4 Imágenes, sonido y caché

|Recurso|Uso observado|Cómo cambiarlo|
|assets/img/Logo ULGC.png|Login, selector y header|Reemplazar archivo o ajustar app_asset en las vistas; respetar transparencia/proporción|
|assets/img/login-fondo.png|Fondo del login y header/footer|Actualizar imagen y revisar cover/posición/capa de color en CSS|
|assets/img/favicon.png|Ambos layouts|Mantener un icono cuadrado; comprobar caché del navegador|
|assets/img/capibara-login.png|Mascota visible del login|Cambiar recurso y reglas logo-capibara-wrap/login-capibara|
|assets/img/capibara-login1.png|Sin referencia encontrada en código propio|Variante disponible; no asumir que se usa en pantalla|
|assets/img/capibaras-equipo.webp|Historia en Acerca de|Imagen con width/height en parcial y reglas .about-team-image|
|assets/sounds/notificacion.mp3|Audio de la campana|Reemplazar archivo y comprobar volumen/política de reproducción|

app_asset agrega ?v=filemtime si existe el archivo. .htaccess configura caché prolongada e immutable para assets; la versión en URL permite publicar cambios sin conservar el mismo recurso en caché, pero la marca de modificación tiene que cambiar en el servidor. Use app_asset al incorporar scripts/imágenes; varias URLs de exportación son relativas y no usan BASE_URL, lo que puede fallar si se navega mediante rutas reescritas profundas.

## 11.5 JavaScript que debe preservarse

jefe-picker.js transforma el select en buscador con opciones, teclado, Escape, focus, aria-expanded y selección sincronizada. No quite el select: es el campo que finalmente envía nit_jefe_seleccionado. initJefePicker devuelve select, focus e isValid. La lista procede del HTML generado por el servidor y no consulta un API mientras se escribe.

acerca-de.js muestra/oculta portada, cambia diapositivas, conserva foco, aplica inert/aria-hidden, permite pausar y detiene el temporizador si la pestaña se oculta. Para copiar correo usa clipboard en contexto seguro con fallback de selección. data-about-interval y el texto de interfaz deben mantenerse coordinados. Si añade diapositivas, actualizar dots, aria-label y el mensaje de estado que hoy habla de «1 de 2/2 de 2».

main.php concentra notificaciones, búsquedas móviles, bloqueo de drag/drop en campos y protección de doble envío. Los scripts de los formularios repiten reglas de duración y preview; corregir solo crear no corrige editar. panel.php contiene el autocompletado administrativo con retardo de 250 ms y máximo de ocho resultados del backend. empleados/index.php filtra también las filas de la página actual con retardo de 200 ms; ese filtro instantáneo no consulta todas las páginas hasta que se envía Filtrar.

# 12. Guía de cambios con pasos y pruebas

Cada procedimiento identifica el archivo de origen y las piezas dependientes. Los ejemplos describen modificaciones futuras: no fueron aplicados como parte del manual.

## 12.1 Reactivar el login o cambiar el aviso

Editar config/app.php, cambiar VACACIONES_ACTIVAS a false y guardar sin BOM. Editar el mensaje de views/auth/vacaciones.php si se conserva la función. Para reactivación por fecha sería necesario programar una condición de fecha explícita; el sistema actual no lo hace. Prueba: abrir sesión nueva sin cookie y verificar el formulario, probar POST de login y conservar una sesión existente para evaluar el alcance de la bandera.

```php
define('VACACIONES_ACTIVAS', false);
```

## 12.2 Cambiar nombre, logo, favicon o fondo

APP_NAME modifica títulos globales, mientras el encabezado visible del login está en views/auth/login.php. Logo y favicon se cargan desde ambos layouts/login/selector mediante app_asset. Fondo se define en auth.php y también se referencia en ugc.css para header/footer. Reemplazar archivos conservando nombre reduce cambios de ruta; cambiar nombres requiere ajustar todas las referencias. Prueba: login, vacaciones, selector y un dashboard en escritorio/móvil, favicon y versión del asset después de publicar.

## 12.3 Cambiar colores, tipografía y tamaños

Modificar variables :root y el bloque de tema oscuro en ugc.css. Buscar además #0B5A2B, #063125, .btn-green, .btn-outline, .header-logo y las reglas con !important. Inter se carga desde Google Fonts en main.php y auth.php; para otra familia actualizar enlaces y font-family, conservando fallbacks. Anchura general está en .ugc-wrap y formularios en .form-card. Prueba: contraste, foco, hover, contenido largo, tema oscuro y anchos 360/414/768/1024/1440 px. Si cambia el dominio de fuentes, coordinar CSP.

## 12.4 Editar Acerca de y sus contactos

Modificar views/auth/acerca_de.php: nombres, iniciales de avatar, texto de correo, aria-label, IDs únicos de copiar, historia e imagen. Mantener data-about-email-value, data-about-copy y sus relaciones. Para cambiar el tiempo, modificar data-about-interval y el rótulo «Cambio automático cada…». Si agrega una tercera diapositiva, agregar su dot y actualizar aria-label/anuncios de acerca-de.js. Prueba: abrir/cerrar, flechas, dots, pausa, Escape, foco y copiar correo en HTTPS y en el entorno local. Trabajar sobre la ruta del repositorio y comparar la copia temporal de FileZilla antes de publicar.

## 12.5 Agregar o renombrar un tipo de solicitud

Editar TIPOS_SOLICITUD en config/app.php, manteniendo una clave estable en mayúsculas. Los formularios, filtro de header, detalle, avisos y exportaciones consumen ese catálogo. Cambiar solo la etiqueta es compatible con las claves almacenadas; renombrar una clave exige revisar datos existentes y cualquier restricción de base antes de migrar. Prueba: crear/editar con el nuevo tipo, buscar por etiqueta, mostrar detalle, avisos y XLSX. Una regla especial de duración por tipo debe implementarse tanto en JavaScript como en validarSolicitud; el catálogo no la añade por sí solo.

```php
// Ejemplo ilustrativo de una nueva entrada del catálogo:
'PERMISO_INSTITUCIONAL' => 'Permiso institucional',
```

## 12.6 Cambiar horas, días o calendario

Actualizar condiciones de validarSolicitud, normalizarDuracionSolicitud/calcularDiasHabiles y los dos scripts de formulario. Eliminar la discrepancia max=8 de editar al coordinar el tope real. Si se excluyen sábados, cambiar tanto PHP format('N') como JavaScript getDay; si se excluyen festivos, hace falta una fuente/calendario compartido que hoy no existe. Agregar validación servidor para fecha pasada/horizonte si esa es la regla requerida. Prueba: 0,5; 5; valor fuera de paso; exceso; sábado; domingo; rango de un día; AMBAS y cambio de modalidad al editar. Revisar el factor día*8 de kpiAdmin si cambia la jornada de referencia.

## 12.7 Cambiar jefes, Talento Humano o administradores

Jefes: editar NITS_JEFES_REVISORES y verificar empleados activos en los filtros ICEBERG. La lista inicial de getTodosLosJefes se filtra por esa lista; un administrador adicional de base no entra automáticamente en el select solo por tener permiso teórico de revisor. TH: editar las dos constantes y revisar superposición con superusuarios/jefes, selector y excepción de aprobación final. Administradores adicionales: usar el panel autorizado; cupo en MAX_ADMINS. Superusuarios: editar SUPER_ADMIN_NITS, recordar autenticación LDAP y empleado activo. Prueba: login nuevo, selección/cambio de modo, rol operativo, selección de jefe y prohibición de decisión sobre solicitud propia. Revisar sesiones existentes tras retirar privilegios.

## 12.8 Cambiar filas, historial, columnas o filtros

Solicitudes: APP_ROWS_PER_PAGE y datos de paginationData. Directorio: permitir nuevos valores pp en adminController y el select de index.php. Auditoría: límite en superAdminPanel/modelo. Para agregar columna a listados, actualizar LIST_SELECT/DETAIL_SELECT, enriquecer si requiere ICEBERG y luego th/td/data-label en tabla_solicitudes.php. Para un filtro, validar entrada en controlador, añadir bind en buildSolicitudWhere, conservar parámetros en enlaces/paginación y replicarlo en exportación si corresponde. Prueba: conteos y primera/última página, filtro vacío, límites inválidos, combinación de filtros y regreso desde detalle.

## 12.9 Cambiar estados o permisos de acciones

Cambiar un texto de estado: badge_estado.php; revisar tarjetas y nombres de hojas. Agregar estado interno: config/app.php, SQL con literales en solicitudModel, condiciones puede*, vistas, conteos y exportaciones. Definir transición, actor, motivo, notificación y trazabilidad antes de implementar. Para autorizar otra acción, aplicar la misma regla en frontend/controlador/modelo. Prueba: petición directa con rol indebido, dueño, jefe distinto, estado final y solicitud inactiva, además del recorrido permitido. Evitar añadir un permiso solo ocultando/mostrando el botón.

## 12.10 Cambiar PDF central o límite de archivo

Seguir capítulo 9.4. En un despliegue distribuido, configurar central IP/URL/token por instalación, dar escritura al central y lectura mediante endpoint a clientes. Mantener copias de comprobantes y conservar la relación con RUTA_COMPROBANTE. Prueba: upload desde cliente, lectura desde otro servidor, reemplazo, token inválido, archivo faltante, caída de central y tamaño excedido. No probar borrado sobre comprobantes reales de producción.

## 12.11 Cambiar notificaciones, sonido o refresco

Mensajes/destinatarios: notificacionService. Límite de lista: notificacionModel. Frecuencia, reproducción, badge 99+, refresco silencioso y estilos/iconos: main.php y ugc.css. Sonido: assets/sounds/notificacion.mp3. Si añade evento administrativo, invocar el servicio solo tras verificar resultado y definir destinatarios. Prueba: dos usuarios con roles distintos, lectura individual/todas, pestaña oculta/visible, retorno del PDF y políticas de audio; verificar que un fallo de avisos no se confunda con fallo de solicitud.

## 12.12 Cambiar Excel/CSV

Excel: modificar cabeceras y campos en el ExportController del rol, mantener mismo orden/cantidad; actualizar aplicarAnchosColumnas y prepararFilas para formatos. Agregar una hoja en el array data de todasExcel con permiso/filtro correcto. CSV de empleados: exportEmpleadosCsv; CSV de auditoría: adminAuditoriaModel::exportCsv. Si requiere exportar exactamente la búsqueda visible, transmitir q/tipo y aplicarlos: la implementación actual no lo hace para Excel. Prueba: abrir en Excel, caracteres acentuados, observaciones largas, fechas, documentos con ceros, texto que empieza por =/+/−/@ y una hoja sin registros.

## 12.13 Añadir un nuevo campo de solicitud

1. Definir significado, tipo, longitud, obligatoriedad, visibilidad por rol y validación; confirmar un cambio de esquema con el responsable de Oracle.
2. Preparar la migración y reversión del esquema por separado; el proyecto no proporciona un sistema de migraciones.
3. Añadir name/id y valor inicial en form_crear/form_editar y su validación de navegador.
4. Recibir/limpiar/validar en crearPost/editarPost; añadir al array que se envía al modelo.
5. Añadir columna y bind en crear/actualizarSolicitudEmpleado, y SELECT en LIST_SELECT/DETAIL_SELECT según su alcance.
6. Mostrar con e en tabla/detalle y decidir si se exporta, notifica o audita.
7. Probar crear, editar, lectura de registros antiguos, valores nulos, límites y acceso por rol; publicar esquema/código de forma coordinada.

## 12.14 Añadir pantalla o acción

Crear la vista, un método de controlador y un case explícito en index.php. Para pantalla, generar enlaces con url_view; para mutación, usar url_action y CSRF. Llamar requiere_login/rol antes de leer datos, validar parámetros y devolver render_view/JSON/redirección según contrato. Usar layout existente y app_asset; los scripts inline llevan csp_nonce. Prueba: ruta correcta, 404, ID inexistente, usuario no autenticado, rol incorrecto y envío repetido. No exponer directamente un controlador dentro de /controllers para saltarse el router.

# 13. Exportaciones en profundidad

SimpleXLSXGen genera XLSX desde arrays y admite estilos etiquetados. Los tres controladores construyen hojas completas en memoria; no exportan únicamente la página visible. Los nombres de hojas se limpian de caracteres no permitidos y se recortan a 31 caracteres. Las columnas de observaciones usan wraptext; documentos y valores se formatean como texto con <raw>. ID se usa internamente para deduplicar pero no se exporta como columna.

|Reporte|Hojas|Columnas destacadas y filtros|
|Admin|Historial completo; Pendientes jefe; Pendientes RRHH; Aprobadas RRHH; Rechazadas RRHH; Cancelado por el usuario|15 columnas; NIT RRHH; fechas de creación; admite fecha_desde/hasta de creación|
|Jefe|Historial Gestionado; Pendientes de Aprobacion; Aprobadas por ti; Rechazadas por ti; Cancelado por el usuario; Mis Solicitudes|15 columnas; fecha gestión jefe; limitado al jefe actual y sus solicitudes propias|
|RRHH|Historial completo; Pendientes RRHH; En Revision Jefe; Aprobadas RRHH; Rechazadas RRHH; Cancelado por el usuario; Mis Solicitudes|16 columnas; NIT RRHH y fecha gestión RRHH; no toma filtros de búsqueda/fecha del panel|

EmpleadoInfoEnricher recoge NIT únicos de todas las hojas, consulta nombres y centro de costo por lotes y añade ambos campos antes de formar las filas. Si ICEBERG no está disponible, pueden quedar celdas vacías aunque UXXI devuelva solicitudes.

limpiarValorParaExcel recorta, normaliza saltos de línea, antepone apóstrofo a textos que comienzan por signos de fórmula y escapa HTML. SafeXlsx::descargar guarda un archivo temporal, reempaqueta el ZIP y rechaza entradas tipo macros, externalLinks, embeddings, activeX u oleObjects; limpia temporales en finally. Si ZipArchive no existe usa downloadAs sin ese reempaquetado. SafeXlsx::valor añade un prefijo raw a valores susceptibles de interpretación, pero no aparece llamado desde los tres controladores actuales; no atribuya a ese método una protección que la ruta no usa.

Hallazgo de filtros: si la exportación Admin no encuentra canceladas dentro de las filas filtradas por fecha, obtenerCanceladasPorUsuario hace consultas de respaldo getAll(['estado'=>...]) sin esas fechas. Así puede incorporar canceladas fuera del período. Revise este comportamiento antes de usar el reporte como una extracción estrictamente acotada. La hoja Admin tampoco separa Rechazadas jefe aunque el panel sí tiene esa tarjeta; esas filas siguen presentes en Historial completo.

# 14. Instalación, configuración local y publicación

## 14.1 Requisitos deducidos del código

El código central usa características de PHP 8, como tipos, match y str_contains. El comentario de server.php indica PHP 8.0 como objetivo; la validación local se hizo con 8.2.0, sin certificar todos los entornos. Apache debe permitir las directivas de .htaccess y tener mod_rewrite para rutas cortas. Deflate, expires y headers se usan condicionalmente. Las extensiones relevantes son OCI8, LDAP, mbstring, fileinfo, cURL para central remoto, y ZIP para reempaquetar XLSX, además de las funciones estándar JSON/date/session.

OCI8 requiere un cliente Oracle compatible con PHP/arquitectura del servidor. Su instalación y el esquema Oracle no están incluidos en el proyecto. En esta máquina PHP CLI reportó las extensiones relevantes, pero Apache puede cargar otro php.ini o un entorno distinto: comprobar en el proceso web antes de dar por operativa la integración. No hay instalación de dependencias Composer/npm que ejecutar en esta copia.

## 14.2 Preparación local

1. Copiar el árbol completo respetando mayúsculas/minúsculas, especialmente Export y los nombres con espacios como Logo ULGC.png.
2. Configurar BASE_URL para la carpeta local y RewriteBase para la misma ruta. Verificar que Apache lea .htaccess; las URLs index.php?view=… funcionan sin depender de rutas cortas, pero la protección de directorios necesita la configuración Apache.
3. Ajustar conexiones LDAP, ICEBERG y UXXI al entorno autorizado. Mantener credenciales fuera de archivos compartidos/documentación.
4. Confirmar con el responsable Oracle tablas, secuencias, mecanismo de ID y permisos. No existe un instalador que cree automáticamente estos objetos.
5. Habilitar extensiones en el php.ini de Apache y reiniciar el servicio si se cambia su configuración.
6. Dar permisos de escritura al usuario de Apache/PHP en storage/solicitudes del central, storage/logs, la ruta de sesión elegida y los temporales de XLSX. Configurar logs/ si se usa error_log de producción.
7. Configurar central PDF y límites upload/post; revisar SERVER_ADDR frente a central IP.
8. Ajustar VACACIONES_ACTIVAS según la operación prevista. Probar login real con cuenta de prueba autorizada y recorrido completo en una base de pruebas.

## 14.3 Comandos de diagnóstico local

```powershell
# Ejecutar desde la raiz del proyecto.
php -v
php -m
php --ini
git status --short
git diff --check
git diff -- config/server.php config/app.php
php -l index.php
php -l controllers/solicitudController.php
```

No compartir el resultado del diff de server.php si contiene secretos. php -l revisa sintaxis sin ejecutar el archivo ni conectarse a bases; php -m comprueba extensiones del CLI. Para comprobar todos los PHP se puede usar el listado versionado:

```powershell
$archivosPhp = git ls-files '*.php'
foreach ($archivoPhp in $archivosPhp) {
    php -l $archivoPhp
}
```

## 14.4 Publicación mediante FileZilla/FTP

Preparar una lista concreta de archivos cambiados con git diff --name-only y revisar la diferencia. Respaldar esos archivos en el destino y la configuración específica de producción. Publicar cada archivo a su ruta relativa exacta; no subir automáticamente la copia temporal del editor ni trasladar server.php de desarrollo sobre la configuración productiva.

Para un despliegue completo deben existir index.php, .htaccess, config, controllers, models, services, views, Export y assets, además de los directorios de ejecución preparados. Los uploads y storage existentes se conservan con sus respaldos; .git no se publica. .gitignore no impide una copia FTP manual y tampoco protege las credenciales de server.php, que está versionado en esta copia.

Antes de activar el sitio, alinear BASE_URL/RewriteBase, APP_ENV=production, conexiones, central PDF, HTTPS y permisos. No hace falta publicar el manual ni las herramientas de documentación al directorio web público. Después comprobar páginas/acciones, acceso denegado a config/storage/.git, links de exportación, caché de assets y logs. Si hay un fallo, restaurar los archivos respaldados; un cambio de esquema/datos necesita su propia reversión, no basta deshacer PHP.

## 14.5 Respaldos y recuperación

Respaldar conjuntamente el código/configuración protegida, la base operativa y storage/solicitudes del central. Un respaldo de Oracle sin archivos no recupera los comprobantes; una copia de archivos sin las rutas de la base no recupera el vínculo con la solicitud. Los registros de sesión/bloqueo son auxiliares; documentar si se restauran o regeneran. La política de retención debe definirse para solicitudes, adjuntos, auditoría y logs, pues el repositorio no implementa una política general.

# 15. Hallazgos y límites del análisis

Los siguientes puntos son observaciones de la copia local. Una inconsistencia visible en el código se considera confirmada por inspección; su frecuencia, impacto en usuarios o explotación no se da por comprobada sin pruebas de integración. Las soluciones propuestas no se aplicaron a la aplicación.

|Prioridad|Hallazgo y evidencia|Consecuencia / cambio sugerido|
|Alta|VACACIONES_ACTIVAS=true y mensaje «13 de octubre»; config/app.php:8 y auth/vacaciones.php|Login bloqueado sin reactivación por fecha. Ajustar bandera/texto según operación|
|Alta|Secretos incrustados en config/server.php versionado|El archivo y su historial deben tratarse como sensibles. Separar configuración por entorno y gestionar cambio/rotación de secretos con el responsable|
|Alta|BASE_URL /ugc_incapacidadess frente a RewriteBase /sede/bogota/ugc_incapacidadess|Posibles rutas/recursos incorrectos en despliegue. Alinear prefijos y probar Apache real|
|Alta|PDF central configurado como localhost/127.0.0.1; comentarios históricos distintos|Una máquina cliente puede apuntarse a sí misma; IPv6 altera detección. Configurar central real por instalación|
|Alta|mainModel::ejecutar devuelve éxito sin oci_num_rows|Un UPDATE sin filas puede notificarse como exitoso tras cambio concurrente. Verificar filas afectadas y diseñar resultado claro|
|Alta|crearPost obtiene último ID por empleado luego del INSERT|Posible carrera en solicitudes simultáneas; usar ID retornado por la propia inserción|
|Alta|Gestión de roles se decide al login y permanece en sesión|Quitar admin no revoca necesariamente sesiones ya abiertas. Revisar autorización vigente en operaciones sensibles|
|Media|CSP con nonce y handlers inline en tabla/empleados/panel/detalle|Confirmaciones y onchange pueden bloquearse. Sustituir por addEventListener y validar en navegador|
|Media|adminController usa return_to sin app_url_interna en varias acciones|Destino de redirección controlado por input. Usar url_retorno_segura en todas esas rutas|
|Media|regenerarCsrf usa clave _csrf_token; helpers leen csrf_token|No regenera el token real. Unificar función/claves y probar formularios abiertos|
|Media|actualizarSolicitudEmpleado omite TIPO_DURACION|Modalidad guardada puede quedar desactualizada; incluir bind/SET coordinado|
|Media|Form editar max=8, validación JS/PHP máximo 5|Mensajes/restricción HTML inconsistentes. Uniformar límite|
|Media|Fechas del formulario limitadas a hoy/cinco meses, servidor no replica|Peticiones directas pueden pasar otras fechas válidas. Definir y aplicar política en PHP|
|Media|Cancelación 500 caracteres en tabla y 2.000 en modal; sin tope PHP equivalente|Resultado depende de la pantalla y longitud Oracle. Uniformar límites servidor/cliente|
|Media|No hay activador visible del modal de cancelar en detalle|Flujo disponible desde tabla; agregar control coherente si se desea cancelar desde detalle|
|Media|Export Admin recupera canceladas sin fechas en su fallback|Puede incluir registros fuera del período. Propagar filtros al respaldo|
|Media|Excel no toma q/tipo del dashboard; auditoría CSV exporta todos|Exportado puede diferir de lo visible. Explicar alcance o implementar filtros coordinados|
|Media|CC_RRHH tratado como centros de costo y como lista de NIT|Reglas/detección de conflictos no comparten significado. Separar listas y revisar exclusiones TH|
|Media|getRol no asigna RRHH por CC_RRHH; rol fijo TH se activa en selector|Llenar CC_RRHH no crea por sí solo revisores finales. Implementar criterio explícito si es requerido|
|Media|getTodosLosJefes filtra lista fija antes de comprobar admins adicionales|Un admin de BD fuera de lista puede no aparecer. Revisar construcción del universo de candidatos|
|Media|NIVEL_MIN_JEFE/ADMIN solo informativos; getJefeInmediato sin llamada actual|Cambiar esos valores/CTE no produce el rol o asignación esperados por su nombre|
|Media|vista conflictos include ../../controllers desde views/admin/conflictos|Resuelve hacia views/controllers, que no existe. Reparar ruta y conectar por router con permisos si se recupera el módulo|
|Media|API directa de conflictos sin requiere_login; bloqueada por Apache|No abrir /controllers para hacerla funcionar. Integrar autenticación/autorización y escape de respuestas|
|Media|Tablas compartidas repetidas pueden duplicar panel-cancelar-ID/listeners|Una fila puede apuntar a otro panel de la misma solicitud. IDs por instancia y scripts únicos|
|Media|getTodos sin deduplicación de NIT; consultas individuales sí deduplican|Totales/directorio pueden contar vínculos en vez de personas. Acordar unidad de conteo|
|Media|Panel health comprueba ICEBERG, uploads y storage/sessions|No certifica UXXI, PDF central ni sesión real configurada. Extender diagnóstico y rótulos|
|Media|Modelo/servicios convierten errores a vacío/false y algunos callers continúan|Datos vacíos y protección de login degradada pueden ocultar fallos. Mejorar observabilidad y contratos|
|Media|Operaciones de solicitud/PDF/avisos/auditoría no son atómicas|Posible éxito parcial. Diseñar transacciones locales y compensación/reintento de integraciones|
|Baja|super_admin_modo en dashboard frente a admin_modo del controlador|Condiciones UI consultan una clave distinta; limpiar referencias heredadas|
|Baja|getHistoricoRRHH devuelve todas las activas; tendenciaMeses no renderizada|Nombre/variable no garantizan historial exclusivo ni gráfico visible|
|Baja|Eliminación lógica conserva PDFs, sin tarea de limpieza|Definir retención y procedimiento de huérfanos con respaldo|
|Baja|Motivos/nota tienen maxlength solo en algunas vistas|Validar longitud en controlador y confirmar límites de columnas|
|Baja|getJefeInmediato CASE repite un centro en ramas distintas|Prevalece primera rama; revisar mapa antes de activar asignación automática|

Orden sugerido de trabajo: primero operación de login, ruta/entorno y central PDF; después consistencia de permisos y persistencia; luego flujos de interfaz/CSP, exportaciones y mantenimiento de estilos. Prioridad no significa que se haya ejecutado una reparación o que el servidor publicado comparta exactamente estas condiciones.

# 16. Pruebas útiles y solución de problemas

## 16.1 Matriz de comprobación de una publicación

|Área|Prueba|Resultado esperado|
|Rutas|Login/dashboard, ruta inexistente y enlaces internos|Pantalla correcta o 404; prefijos coherentes|
|Protección Apache|Solicitar config/server.php, .git y storage|Acceso denegado, sin contenido privado|
|Acceso|LDAP válido + empleado activo; usuario no autorizado|Ingreso permitido únicamente con ambos requisitos|
|Vacaciones|GET y POST con bandera activa/inactiva|Bloqueo/control acorde al valor explícito|
|Modo|SA, admin adicional, TH fijo y usuario común|Solo opciones habilitadas; rol operativo coherente|
|Solicitud|HORAS, DIAS, AMBAS; sábado/domingo; datos límite|Duración normalizada y errores de validación correctos|
|Jefe|Asignado, otro jefe y dueño|Solo asignado decide sobre ajena pendiente|
|RRHH|Pendiente jefe, aprobado jefe y solicitud propia|Solo decisión permitida por estado/propiedad|
|Excepción TH|Solicitud propia de TH aprobada por jefe|Aprobación final automática y campos coherentes|
|Edición/eliminación|Propia pendiente y propia ya gestionada|Solo pendiente; baja lógica verificable en entorno de prueba|
|Cancelación|Dueño aprobado, otro usuario, motivo vacío/largo|Solo autorizado; límites uniformes tras la reparación|
|PDF|5 MiB, exceso, tipo falso, reemplazo y central caído|Validación/errores claros; sin pérdida del archivo anterior|
|Avisos|Nueva/aprobada/rechazada/cancelada y lectura|Destinatarios correctos, sin acceso a avisos ajenos|
|Admin|Intervenir/forzar propia y ajena; cupo; retiro|Prohibición propia, auditoría y efectos definidos|
|XLSX/CSV|Vacío, caracteres, fechas, período y texto fórmula|Archivo válido y alcance de filtros comprobado|
|Sesión/CSRF|Token vencido, navegador atrás, AJAX y doble envío|Respuesta controlada y sin solicitudes duplicadas|
|Diseño|360/414/768/1024/1440 px, oscuro, teclado|Controles accesibles, tablas/preview/cancelación utilizables|

Las pruebas que escriben solicitudes/roles se realizan en un entorno de pruebas con datos autorizados. No se ejecutaron como parte de la elaboración del manual. No confunda éxito de php -l con validación de los permisos en ejecución.

## 16.2 Diagnóstico por síntoma

|Síntoma|Primero revisar|Siguiente paso|
|Solo aparece vacaciones|config/app.php y vacaciones.php|Cambiar bandera según operación; fecha del mensaje no tiene lógica automática|
|Login siempre incorrecto|storage/logs/ldap_auth.log y authModel|Distinguir bind servicio/usuario, empleado laboral y bloqueo por documento|
|ORA o listas vacías|OCI8, conexiones y error_log|Modelo oculta fallos como []; verificar esquema/consulta en acceso de lectura autorizado|
|Archivo central no guarda|SERVER_ADDR, central IP/URL, token, cURL y permisos|Comparar HTTP 201/422/403; revisar timeouts y límites de POST en ambos servidores|
|PDF figura pero da 404|RUTA_COMPROBANTE y existencia central/local|Confirmar nombre/ruta y conexión de lectura; revisar permisos de usuario|
|Preview móvil vacío|Carga PDF.js/worker, CSP y archivo|La selección puede ser válida pese a fallo de render; revisar consola sin exponer adjunto|
|Botón/confirmación no responde|CSP y handlers inline|Mover a listener y revisar eventos duplicados/scripts|
|Logo/CSS sigue antiguo|URL ?v= y filemtime en destino|Verificar upload real y caché; no editar solo la copia temporal|
|Sesión caduca al enviar|Token 900 s, sesión 1800 s y polling|Recargar formulario; coordinar TTL si se modifica política|
|Sesiones activas/health engañosos|TTL auxiliar y rutas reales|No usar health como prueba de UXXI/PDF/LDAP conectado|
|Exporta más que el panel|URL de export y filtros efectivos|q/tipo no transmitidos; revisar fallback canceladas y alcance por rol|
|No aparece un jefe|Lista fija, empleado activo y excluir propio|Cambiar solo nivel no basta; revisar universo del selector|
|Pantalla de conflictos falla|Include relativo y ausencia de ruta|Recuperar módulo mediante router/permiso antes de habilitarlo|
|Error tras subir a Linux|Mayúsculas y rutas de require/assets|Comparar Export, nombre de logo y rutas relativas con el repositorio|

Logs: en APP_ENV=production se configura logs/debug.log; LDAP escribe storage/logs/ldap_auth.log en ambos modos; errores de SQL/PDF usan error_log. Asegurar que el destino existe/escribible y que el acceso web está bloqueado. Evitar publicar un phpinfo abierto o compartir logs sin sanear datos personales/secretos.

# 17. Catálogo completo de clases y funciones PHP

El catálogo que sigue se obtiene del tokenizer de PHP sobre los archivos versionados, sin require ni ejecución de la aplicación. Para cada símbolo se indica archivo, clase/namespace cuando corresponde, visibilidad, firma y propósito. La firma documenta entradas y retorno declarado; no implica que el modelo haya tenido éxito en una base real. Cuando el retorno no está declarado se explica el contrato observado. Los métodos privados también se incluyen para orientar modificaciones internas.

CATALOGO_PHP

# 18. Funciones JavaScript, eventos y bloques CSS

Las funciones nombradas propias del navegador se enumeran por archivo y línea. Las IIFE y callbacks de eventos se describen por su flujo en los capítulos 8 a 11; PDF.js minificado se documenta como dependencia externa y no como código propio modificable. Cambiar IDs/data-* sin coordinar scripts rompe el enlace entre estructura y comportamiento.

CATALOGO_JS

## 18.1 Índice de bloques de estilo

INDICE_CSS

# 19. Inventario de archivos y trazabilidad

El siguiente inventario lista cada archivo versionado con sus líneas físicas aproximadas y finalidad. Los archivos binarios no se interpretan como líneas de código. Las carpetas de sesión, adjuntos, archivos privados, logs y .git quedan fuera del catálogo de fuentes porque contienen datos de ejecución o metadatos del repositorio. Las herramientas usadas para elaborar este manual están en documentacion/.generacion y no forman parte del código de la aplicación analizado.

INVENTARIO_ARCHIVOS

## 19.1 Resultado de validación

VALIDACION_ACTUAL

## 19.2 Mantener el manual vigente

Después de una modificación funcional, actualizar el capítulo de configuración/reglas, el procedimiento de cambio y el catálogo del método afectado. Registrar fecha y commit de la nueva revisión. Si se mueve código, regenerar referencias de línea e inventario. Si cambia el esquema Oracle, actualizar el contrato con DDL verificado y conservar la diferencia entre lectura del código y evidencia del entorno. No añadir contraseñas/tokens al documento al actualizarlo.

# 20. Glosario de mantenimiento

|Término|Significado en este proyecto|
|MVC|Separación práctica entre controlador, acceso a datos y vista; implementada sin framework|
|Endpoint|Pantalla, descarga o acción accesible por una URL concreta|
|Bind Oracle|Parámetro enlazado con oci_bind_by_name para no concatenar valores en SQL|
|Bind LDAP|Autenticación de cuenta de servicio o usuario ante el directorio|
|CSRF|Token de sesión exigido para operaciones que modifican datos|
|CSP/nonce|Política de navegador y autorización de bloques script de la respuesta|
|Baja lógica|ACTIVO=0; conserva fila y comprobante|
|RRHH/TH|Talento Humano, revisor final ordinario|
|NIT/documento|Identificador usado para relacionar usuario, empleado, revisor y destinatario|
|Centro de costo|Código organizacional laboral; no debe confundirse con un documento|
|UXXI|Conexión operativa de permisos usada por mainModelUXXI|
|ICEBERG|Conexión de fuente laboral usada por empleadoModel|
|PDF central|Instalación que guarda archivos físicos y atiende clientes con token interno|
|MIME|Tipo de archivo detectado por servidor; distinto de confiar en su nombre|
|Polling|Consulta periódica de la campana mientras la pestaña está visible|
|Asset versionado|Recurso con ?v=filemtime para actualizar caché al cambiar archivo|
|XLSX|Libro Excel generado con hojas y formato; no es el CSV del directorio/auditoría|
|Secuencia|Objeto Oracle usado explícitamente para IDs de roles, auditoría y notas|
|Validación estática|Comprobación de código sin ejecutar un recorrido con servicios externos|
|Prueba de integración|Evidencia de funcionamiento con Apache, navegador, LDAP, Oracle y archivos reales de prueba|
