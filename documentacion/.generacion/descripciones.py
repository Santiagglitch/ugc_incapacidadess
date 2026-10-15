"""Descripciones editoriales del codigo inspeccionado. No ejecuta la aplicacion."""
COMMON = {}
SPEC = {}

def add(text, path=None):
    target = COMMON if path is None else SPEC.setdefault(path, {})
    for line in text.strip().splitlines():
        name, desc = line.split('|', 1)
        target[name.strip()] = desc.strip()

add('''
__construct|Inicializa las dependencias o el estado interno de la clase. No significa que ya exista una conexión o que se haya ejecutado una operación de negocio.
conectar|Obtiene la conexión Oracle de la capa correspondiente; los modelos UXXI sustituyen la conexión laboral por la operativa.
disponible|Intenta conectar y devuelve true/false, capturando excepciones. Comprueba conexión, no existencia de tablas ni permisos de todas las operaciones.
cerrar|Libera o elimina el recurso de la clase. En conexiones persistentes ICEBERG no cierra físicamente la conexión.
validarPost|Exige el token CSRF mediante csrf_requerido. El permiso de rol/propiedad se comprueba por separado en la acción llamante.
json|Establece estado HTTP/tipo JSON y serializa la respuesta; finaliza la ejecución con exit.
toUpper|Convierte a mayúsculas con mbstring si está disponible, con alternativa strtoupper.
toLower|Convierte a minúsculas con mbstring si está disponible, con alternativa strtolower.
escapeLike|Escapa barra invertida, % y _ para búsquedas LIKE con ESCAPE; se enlaza después como parámetro.
enriquecerSolicitudes|Recoge NIT de empleado/jefe, obtiene nombres por lotes y usa caché por petición; añade NOMBRE_EMPLEADO y NOMBRE_JEFE a las filas.
super_admin_nits|Normaliza los documentos de SUPER_ADMIN_NITS, elimina vacíos/duplicados y conserva una caché estática.
es_super_admin_nit|Normaliza el documento recibido y verifica pertenencia estricta a la lista fija de superusuarios.
es_jefe_revisor_nit|Verifica la lista de revisores excluyendo NIT fijos TH; también compara con CC_RRHH, cuya semántica es ambigua.
get_jefes_revisores_filtrados|Resta CC_RRHH de NITS_JEFES_REVISORES. No resta por sí mismo todos los documentos de NITS_TALENTO_HUMANO.
es_talento_humano_nit|Normaliza documento y comprueba si pertenece a los dos usuarios fijos de Talento Humano.
app_base_url|Une BASE_URL y una ruta quitando barras sobrantes en sus extremos; no configura el servidor web.
app_asset|Forma URL del recurso y añade ?v=filemtime cuando el archivo existe; cachea la marca dentro de la petición.
ldap_diagnostico_archivo|Devuelve la ruta storage/logs/ldap_auth.log del registro de autenticación LDAP.
ldap_diagnostico_sanitizar|Recorre detalles y redacta valores de claves que indican contraseña, secreto o token; normaliza objetos/recursos para el log.
ldap_diagnostico_id|Genera identificador aleatorio del intento; usa uniqid como alternativa si no puede obtener bytes aleatorios.
ldap_diagnostico_registrar|Escribe una línea JSON con etapa, resultado, usuario, configuración no secreta y duraciones. Crea el directorio de logs y registra fallos de escritura.
ldap_diagnostico_error|Traduce códigos/mensajes LDAP en causas probables: credenciales, DN, permisos, timeout o servidor no disponible.
ldap_uid_para_dn|Escapa caracteres del documento para construir un DN LDAP; usa ldap_escape si existe.
ldap_cn_directo_dn|Forma cn=documento seguido de LDAP_TREE mediante el escape de DN.
ldap_login_directo_cn_activo|Devuelve si está definida y activa LDAP_DIRECT_CN_LOGIN.
ldap_conectar|Abre conexión LDAP, fija opciones y autentica la cuenta de servicio; devuelve conexión o false y registra diagnóstico por etapas.
ldap_autenticar_usuario|Valida credenciales del usuario por bind directo cn o por búsqueda uid y bind; devuelve perfil LDAP o NULL y libera la conexión.
url_view|Genera URL index.php?view=… con nombre de vista codificado y BASE_URL.
url_action|Genera URL index.php?action=… para una acción; no determina método ni valida permisos.
redirect_to|Limpia buffers de salida, envía Location y termina. El llamante debe validar el destino.
app_url_interna|Valida esquema/host y que el path pertenezca al prefijo de la aplicación; rechaza controles y destinos externos.
url_retorno_segura|Devuelve la URL recibida solo si app_url_interna la valida; en otro caso usa el destino alternativo.
flash_set|Guarda un mensaje type/message en sesión para la siguiente pantalla.
flash_get|Recupera el mensaje flash y lo elimina de sesión para mostrarlo una sola vez.
fecha_hora_co|Intenta convertir la fecha/hora ISO al formato colombiano DD/MM/YYYY hh:mm AM/PM; usa valor alternativo si falla.
fecha_co|Convierte la parte de fecha ISO a DD/MM/YYYY; usa un valor alternativo para entrada vacía o no interpretable.
render_view|Valida existencia de vista, extrae datos sin sobrescribir variables, captura su HTML y carga el layout con $content.
iniciar_sesion_segura|Configura ruta/nombre/opciones de cookie y abre la sesión solo si aún no está activa.
csp_nonce|Genera un nonce aleatorio único por petición para autorizar bloques script en CSP.
aplicar_headers_seguridad|Envía CSP, no-cache y otras cabeceras; agrega HSTS en production, salvo que ya se hayan enviado headers.
csrf_token|Obtiene/genera el token vigente en $_SESSION['csrf_token']; rota cuando está ausente o vencido.
csrf_generar_token|Forma token aleatorio hexadecimal con timestamp, base de la comprobación de expiración.
csrf_token_expirado|Comprueba estructura y edad respecto a CSRF_TOKEN_TTL; no modifica la sesión.
csrf_input|Devuelve el input hidden _csrf_token con valor escapado para formularios HTML.
validar_csrf|Compara con hash_equals el token actual o anterior dentro de gracia y verifica expiración.
csrf_regenerate_after_login|Conserva brevemente el token previo y genera otro al autenticar; no cambia por sí mismo el ID de sesión.
e|Escapa texto para HTML UTF-8 con comillas; no sustituye json_encode en contexto JavaScript.
limpiar_texto|Convierte a string, elimina etiquetas y recorta espacios exteriores.
normalizar_documento|Elimina todos los caracteres distintos de dígitos del identificador recibido.
usuario_actual|Devuelve el array de usuario guardado en sesión o un array vacío.
usuario_autenticado|Devuelve si existe un usuario no vacío en sesión.
admin_modo_pendiente|Indica si la sesión exige pasar por el selector de modo.
es_peticion_ajax|Reconoce X-Requested-With=XMLHttpRequest para escoger respuesta de sesión vencida.
responder_sesion_expirada|Responde 401 JSON con redirect para AJAX o redirige al login; finaliza ejecución.
csrf_requerido|Exige token de POST/cabecera; si falla cierra sesión y prepara respuesta de acceso vencido.
usuario_tiene_selector_modo|Comprueba superusuario, TH fijo o administrador adicional de BD para mostrar selector/cambio de modo.
rol_actual|Devuelve el rol almacenado en el usuario de sesión o cadena vacía.
validar_vigencia_sesion|Aplica timeout por last_activity, renueva actividad y registra SESIONES_ACTIVAS. El polling también puede pasar por esta función.
requiere_login|Exige autenticación/vigencia y redirige al selector si el modo está pendiente.
requiere_rol|Aplica requiere_login y devuelve 403 si el rol de sesión no pertenece a los admitidos.
cerrar_sesion|Elimina registro auxiliar actual, variables y cookie de sesión, y ejecuta session_destroy.
usuarioTieneSelectorModo|Comprueba los tres grupos con acceso al selector para el recorrido de autenticación.
esSuperAdmin|Comprueba el documento del array usuario contra la lista fija de superusuarios.
esTalentoHumanoFijo|Comprueba si el array usuario pertenece a los documentos TH fijos.
loginForm|Muestra vacaciones si está activo el bloqueo; redirige autenticados o presenta login con error pendiente.
verificarBruteForce|Consulta loginBloqueoService y obtiene mensaje de bloqueo; si el servicio lanza excepción permite continuar.
registrarIntentoFallido|Incrementa intentos por documento e intenta registrar LOGIN_FALLIDO en auditoría.
limpiarIntentosFallidos|Borra bloqueo del documento tras autenticar; captura fallos del servicio.
esperaHtml|Comprueba si Accept incluye text/html. No aparece llamada desde el recorrido activo del controlador revisado.
loginPost|Aplica vacaciones/CSRF/bloqueo, llama al modelo de login, regenera sesión/token y guarda usuario; dirige al selector o dashboard.
seleccionarModoForm|Presenta el selector solo a un usuario autenticado autorizado con admin_modo_pendiente.
seleccionarModoPost|Valida modo permitido, guarda admin_modo y recalcula rol operativo o activa rol TH; dirige al dashboard.
cambiarModo|Alterna explícitamente entre modo administrativo y operativo, ajusta rol, evita revertir por mismo destino y audita el cambio.
calcularRolOperativo|Asigna ROL_JEFE por pertenencia a NITS_JEFES_REVISORES; en otro caso ROL_EMPLEADO. El nivel laboral no determina el resultado.
logout|Exige CSRF, cierra la sesión y redirige al login.
empleados|Carga empleados, aplica filtros/ordenación en PHP, pagina 6/12/24 y añade conteos agrupados de solicitudes para la página.
exportEmpleadosCsv|Exporta directorio con filtros q/rol/cc a CSV UTF-8 con BOM; escapa comillas/separadores.
empleadoDetalle|Obtiene perfil, antigüedad, rol, solicitudes paginadas y, para superusuario, cambios de rol dirigidos al NIT.
hacerAdmin|Exige admin+superusuario, CSRF y cooldown; valida empleado activo/cupo, asigna admin adicional y audita.
quitarAdmin|Exige admin+superusuario, CSRF y cooldown; protege superusuarios, desactiva rol adicional y audita.
intervenirSolicitud|Permite a admin decidir sobre solicitud ajena intervenible con observación; coordina cambio y auditoría. Revisar return_to.
forzarEstadoSolicitud|Valida admin/CSRF/solicitud ajena y motivo, delega estado permitido al modelo y registra auditoría.
guardarNotaAdmin|Exige admin/CSRF y nota no vacía, inserta nota interna y regresa; no valida explícitamente longitud/existencia de la solicitud.
superAdminPanel|Exige superusuario en rol admin y reúne sesiones, roles, auditoría, conteos, tendencia y diagnóstico para el panel.
buscarEmpleadoJson|Busca nombre/NIT en el directorio completo para superusuario, mínimo dos caracteres y máximo ocho resultados con ya_admin.
auditoriaExportCsv|Exige superusuario en rol admin y descarga el CSV completo de auditoría con BOM UTF-8.
asignarAdminDesdePanel|Asigna administrador adicional desde autocompletado SA, con validación de empleado/cupo, CSRF, cooldown y auditoría.
systemHealth|Devuelve JSON del diagnóstico solo al superusuario autenticado en rol admin.
obtenerEstadoSistema|Comprueba conexión ICEBERG, extensiones y carpetas legadas; devuelve entorno/versión/fecha. No comprueba UXXI ni central PDF.
soloSuperAdmin|Impide gestionar administradores a un usuario cuyo documento no pertenece a SUPER_ADMIN_NITS.
verificarRateLimit|Impone cinco segundos entre acciones de una clave específica en la sesión, mediante una clave derivada MD5.
regenerarCsrf|Intenta una función inexistente en esta copia y, como alternativa, escribe _csrf_token en sesión; no coincide con csrf_token real.
rolEmpleado|Clasifica filas del directorio como admin/jefe/empleado usando listas y administradores adicionales.
calcularStats|Cuenta empleados por clasificación del directorio antes de aplicar sus filtros de búsqueda.
calcularAntiguedad|Calcula años/meses o días desde fecha de ingreso; devuelve vacío si no puede interpretar la fecha.
analizarConflictos|Compara listas de jefes y CC_RRHH, obtiene diferencia/intersección y un resumen; revisar mezcla de NIT y centros.
verificarConflictoPorNit|Normaliza documento y devuelve indicadores de pertenencia/conflicto en las dos listas usadas por el módulo legado.
getJefesValidados|Devuelve lista filtrada de revisores, total y timestamp de generación.
generarReporteCompleto|Empaqueta resumen, análisis y recomendaciones del módulo de conflictos como array.
apiAnalisis|Despacha acciones JSON del controlador directo de conflictos; no agrega requiere_login/rol y Apache bloquea esa carpeta.
index|Selecciona el dashboard según rol y prepara filtros, conteos y datos paginados; admin se resuelve en el mismo método.
dashboardRrhh|Prepara pendientes finales, historial seleccionado y solicitudes propias con sus paginaciones y tarjetas de TH.
dashboardJefe|Prepara pendientes ajenas asignadas, historial por decisiones y solicitudes propias del jefe.
dashboardEmpleado|Prepara únicamente solicitudes del dueño, filtros de estado/tipo y tarjetas personales.
resolverHistorialPage|Reutiliza una página ya consultada de pendientes/propias si coincide la selección y número; de lo contrario consulta historial.
estadisticasJefe|Cuenta estados del equipo y solicitudes propias; Aprobadas por ti incluye posteriores rechazos RRHH.
estadisticasEmpleado|Agrupa conteos de solicitudes propias en total, pendiente, aprobadas, rechazadas y canceladas.
sumarConteos|Suma todos los conteos o solo los estados indicados; devuelve entero.
filtrosBase|Valida/arma tipo y q y, si se permite, resuelve documentos por nombre/NIT laboral para la búsqueda de solicitudes.
contador|Devuelve contador de avisos no leídos del usuario, liberando bloqueo de sesión antes de consulta.
validarAjaxCsrf|Valida token AJAX por cabeceras o POST; si falla devuelve 401 JSON con destino login, sin seguir el cierre CSRF común.
upload|Autoriza token interno, verifica PDF y guarda en central; devuelve JSON 201 o 422.
file|Autoriza token interno y sirve PDF central por nombre seguro, o responde 404.
delete|Autoriza token interno y elimina archivo central, respondiendo JSON 200/422.
autorizar|Compara X-PDF-Storage-Token con token configurado mediante hash_equals; responde 403 si no coincide.
pdfValido|Verifica archivo temporal de upload, tamaño entre 1 y 5.242.880 y MIME permitido.
crearForm|Exige login, construye jefes disponibles y token único de envío, y presenta formulario de creación.
crearPost|Valida/normaliza petición y jefe, guarda PDF/solicitud, compensa PDF si falla y notifica; el ID se consulta después del INSERT.
ver|Exige permiso sobre la solicitud activa, añade nombres/notas admin y presenta el detalle.
editarForm|Exige dueño y estado pendiente de jefe, construye lista de revisores y presenta datos actuales.
editarPost|Valida dueño/estado/campos, sustituye PDF si se solicita, actualiza modelo y elimina archivos de compensación o anteriores.
gestionJefePost|Valida rol, asignación y no autorrevisión; decide con motivo para rechazo y aplica excepción de solicitud propia de TH.
gestionRrhhPost|Valida rol y solicitud ajena aprobada jefe; decide, exige motivo de rechazo y coordina avisos.
servirArchivo|Exige login y permiso de ver la solicitud, delega lectura PDF local/remota y devuelve 404 si falta.
validarSolicitud|Valida catálogo, fechas/orden, modo, horas/paso, días, observaciones y presencia de jefe; devuelve errores.
normalizarDuracionSolicitud|Fuerza mismo día para HORAS, anula unidad no usada y recalcula días inclusive excluyendo solo domingos.
calcularDiasHabiles|Cuenta días del rango inclusive sin domingos; incluye sábados y no consulta festivos.
procesarArchivoPDF|Exige PDF, verifica upload/tamaño/extensión/MIME y delega guardado central; devuelve ruta lógica o false con flash.
numeroOpcional|Convierte número no vacío a float, devuelve NULL para vacío y -1 como señal de entrada no numérica.
fechaValida|Compara ida/vuelta del formato YYYY-MM-DD para rechazar fechas normalizadas por PHP.
returnTo|Prioriza destino de POST/GET y luego referer si son internos; de lo contrario usa fallback.
redirigirSolicitudBorrada|Prepara mensaje para solicitud inactiva/inexistente y redirige a destino interno permitido.
puedeVer|Permite dueño, jefe asignado o rol admin/RRHH; compara documentos normalizados.
enriquecerSolicitudDetalle|Resuelve nombres de dueño y jefe en lote o auxiliares de desarrollo, añadiéndolos al detalle.
puedeEditar|Permite únicamente dueño en PENDIENTE_JEFE; rol administrador no amplía esta edición ordinaria.
puedeCancelarAgenda|Permite únicamente dueño en APROBADO_JEFE o APROBADO_RRHH.
puedeGestionarJefe|Exige jefe asignado distinto del dueño y estado PENDIENTE_JEFE; el rol se valida en la acción pública.
puedeGestionarRrhh|Exige admin/RRHH, usuario distinto del dueño y APROBADO_JEFE.
solicitudEsDeTalentoHumano|Normaliza NIT del solicitante y comprueba lista fija TH para activar aprobación final especial.
jefesDisponibles|Carga candidatos de lista fija/empleados, agrega auxiliares development, excluye propio, valida, deduplica y ordena nombres.
puedeActuarComoJefeRevisor|Admite superusuario, admin adicional o lista fija de revisores, incluida la comprobación directa de miembros TH.
validarJefeSeleccionado|Rechaza autoselección y exige que el NIT exista entre candidatos disponibles; añade errores y devuelve documento canónico.
validarObservacionGestion|Exige motivo para rechazar y limita observación a 2.000 caracteres; devuelve al origen con flash si falla.
crearSubmitToken|Genera token de un solo uso en sesión para una acción y purga entradas guardadas de más de una hora.
consumirSubmitToken|Comprueba y elimina token guardado de creación; impide repetir el mismo envío dentro de la sesión.
filtrar|Consulta auditoría con filtro opcional de acción y paginación, normaliza filas y devuelve metadatos de páginas.
getByNitObjetivo|Devuelve últimas entradas de auditoría cuyo NIT_OBJETIVO corresponde al empleado indicado.
getAccionesUnicas|Obtiene claves distintas de acción de auditoría para llenar filtro del panel.
exportCsv|Construye CSV de todas las filas de auditoría con seis columnas y escape de separadores/comillas.
normalizarFilas|Decodifica META_JSON y combina objetivo/solicitud con metadatos para devolver claves de presentación en minúsculas.
resolverNombre|Resuelve nombre desde auxiliares de desarrollo, superusuario o empleado; usa NIT como alternativa.
ipCliente|Valida REMOTE_ADDR y devuelve IP o texto desconocida; no confía por sí misma en cabeceras de proxy.
csvEscape|Duplica comillas y entrecomilla si hay separadores/saltos; no aplica neutralización explícita de fórmulas CSV.
getAdminsAdicionales|Lee ADMIN_ROLES activos y cachea lista de documentos dentro de la petición.
esAdminAdicional|Comprueba pertenencia estricta a la lista activa de administradores adicionales.
agregarAdmin|Normaliza documento, evita duplicado activo/cupo lleno e inserta con SEQ_ADMIN_ROLES; invalida caché si funciona.
quitarAdmin|Desactiva ADMIN_ROLES del documento y limpia caché; no cierra las sesiones existentes del usuario.
getLimiteMaximo|Devuelve MAX_ADMINS, actualmente 10.
alcanzaLimite|Compara cantidad de administradores adicionales activos con el límite configurado.
login|Exige documento/contraseña, autentica LDAP y empleado activo, calcula rol y devuelve ok/user o error. trim modifica espacios exteriores de contraseña.
getByNit|Consulta empleado activo autorizado y devuelve el vínculo laboral más reciente o NULL.
getNombresPorNit|Deduplica NIT y consulta nombres por lotes de 900, eligiendo último vínculo mediante ROW_NUMBER.
getInfoExportacionPorNits|Consulta nombre y centro de costo activo para exportación, por lotes y con último vínculo laboral por NIT.
buscarNitsPorNombreONit|Busca por nombre/documento con LIKE escapado, deduplica y limita la cantidad de NIT candidatos.
getTodos|Consulta todos los vínculos laborales activos de empresas/departamentos admitidos; no deduplica por NIT.
getRol|Prioriza superusuario/admin adicional, después lista de jefes; por defecto solicitante. No asigna TH por centro ni aplica niveles mínimos.
getJefeInmediato|Busca jefe jerárquico por centro/cabecera mediante CTE y nivel; no aparece invocado en el selector de jefe actual.
esAprendiz|Indica pertenencia del centro de costo a CC_APRENDICES para la etiqueta del perfil.
getTodosLosJefes|Consulta empleados activos cuyo NIT aparece en la lista fija filtrada de revisores y deduplica vínculos.
getPorCentrosCosto|Consulta vínculos laborales activos de los centros indicados para destinatarios de notificación TH.
ejecutarConsulta|Prepara SQL OCI8, fija prefetch, enlaza valores y ejecuta con modo indicado; devuelve statement o false y registra error.
consultarTodo|Recupera todas las filas asociativas y libera statement; ante error devuelve array vacío.
consultarUno|Recupera una fila asociativa y libera statement; ante error/ausencia devuelve NULL.
ejecutar|Ejecuta DML con OCI_COMMIT_ON_SUCCESS y devuelve éxito de ejecución sin verificar filas afectadas.
buildInClause|Construye placeholders únicos de una lista y sus binds; no concatena directamente los valores en SQL.
getNoLeidas|Lee hasta veinte avisos no leídos del destinatario unidos a solicitudes activas, ordenados del más reciente.
contarNoLeidas|Cuenta todos los avisos sin leer del destinatario cuyas solicitudes siguen activas.
marcarLeida|Actualiza un aviso por ID y destinatario; evita modificar el aviso de otro usuario.
marcarTodasLeidas|Marca avisos pendientes del destinatario como leídos y registra fecha de lectura.
marcarLeidasPorSolicitud|Marca como leídos los avisos del destinatario asociados a una solicitud concreta.
getUltimoIdByEmpleado|Lee ID más reciente de solicitudes activas del empleado por FECHA_CREACION; puede competir con otra creación simultánea.
getById|Obtiene detalle ampliado de solicitud por ID, únicamente si ACTIVO=1.
getByEmpleado|Lee listado completo de solicitudes activas del dueño, ordenado por creación descendente.
getPendientesJefe|Lee pendientes asignadas al jefe excluyendo solicitudes del propio jefe, ordenadas por ID ascendente.
getGestionadasByJefe|Lee estados gestionados del equipo del jefe, excluyendo propio y ordenando por fecha de gestión.
getPendientesRRHH|Lee solicitudes activas APROBADO_JEFE, pendientes de decisión final.
getHistoricoRRHH|Lee todas las solicitudes activas, no únicamente las ya gestionadas por TH.
getAll|Lee listado completo aplicando buildSolicitudWhere y orden de creación descendente.
getAllPaginado|Aplica filtros, cuenta filas y pagina en Oracle con orden permitido; devuelve rows y pagination.
getByEmpleadoPaginado|Añade nit_empleado a los filtros y delega paginación de solicitudes propias.
getPendientesJefePaginado|Añade jefe, exclusión de propio y pendiente jefe, y pagina por ID ascendente.
contarTotal|Cuenta solicitudes activas que satisfacen los filtros del modelo.
contarPorEstado|Agrupa solicitudes filtradas por ESTADO y devuelve mapa estado→cantidad.
contarPorNits|Cuenta total y estados considerados activos por empleado en una consulta agrupada; inicializa ceros para NIT sin filas.
kpiAdmin|Calcula tipo más frecuente, promedio horas+días*8 y día de semana más frecuente de creación con filtros.
contarPorMes|Forma serie mensual de 1 a 24 meses, completa ceros y consulta conteos de solicitudes activas por creación.
aprobarJefe|Delega gestionarJefe con APROBADO_JEFE.
aprobarJefeTalentoHumano|Actualiza directamente a APROBADO_RRHH una solicitud ajena pendiente del jefe, dejando NIT_RRHH NULL y mensaje automático.
rechazarJefe|Delega gestionarJefe con RECHAZADO_JEFE.
aprobarRRHH|Delega gestionarRRHH con APROBADO_RRHH.
rechazarRRHH|Delega gestionarRRHH con RECHAZADO_RRHH.
adminIntervenir|Valida decisión/motivo y actualiza solicitud ajena de estado intervenible a estado final RRHH con observación administrativa.
adminForzarEstado|Valida cinco estados destino y motivo, actualiza solicitud ajena y fechas/observaciones de la etapa correspondiente.
buildSolicitudWhere|Construye WHERE/binds de actividad, estado(s), tipo, documentos, fechas de creación y búsqueda textual/nombres.
normalizarFiltroBusqueda|Recorta espacios y limita búsqueda a cien caracteres usando mb_substr cuando existe.
tiposSolicitudCoincidentes|Busca coincidencias normalizadas de q en clave y etiqueta del catálogo y devuelve claves de tipo.
normalizarTextoBusqueda|Convierte a minúsculas, intenta transliterar a ASCII, conserva letras/números y compacta espacios.
paginar|Cuenta total, acota página/tamaño, aplica orden seguro y OFFSET/FETCH; devuelve filas y metadatos.
contarSqlBase|Cuenta resultados de la consulta base usando una subconsulta con sus binds.
ordenSeguro|Admite únicamente una lista fija de ORDER BY y usa creación descendente como alternativa.
actualizarSolicitudEmpleado|Actualiza campos/PDF/jefe de solicitud propia permitida; no incluye SET TIPO_DURACION en la versión revisada.
gestionarJefe|Hace UPDATE condicionado a jefe asignado, solicitud ajena activa y pendiente; guarda decisión/fecha/observación.
gestionarRRHH|Hace UPDATE sobre solicitud ajena activa APROBADO_JEFE; guarda gestor, estado, fecha y observación final.
verificar|Consulta bloqueo vigente por MD5 del documento y devuelve mensaje si alcanza tres intentos.
registrarFallido|Purga expirados e inserta/incrementa intentos por documento, conservando ventana inicial de quince minutos e IP.
limpiar|Elimina bloqueo del documento indicado, típicamente después de login correcto.
limpiarExpirados|Elimina LOGIN_BLOQUEOS cuya fecha EXPIRA ya pasó, según la hora SQL Colombia.
guardar|Inserta nota administrativa en NOTAS_ADMIN con secuencia y fecha; devuelve éxito de ejecución.
getByIdSolicitud|Consulta notas por solicitud, ordena más recientes y devuelve datos normalizados para la vista.
notificarNuevaSolicitud|Crea aviso para jefe seleccionado con nombre del solicitante y tipo de permiso.
notificarAprobacionJefe|Avisa al dueño, genera revisión TH y marca avisos del jefe como leídos.
notificarAprobacionFinalJefeTalentoHumano|Avisa aprobación final de solicitud propia TH y marca aviso del jefe; no genera revisión ordinaria adicional.
notificarRechazoJefe|Avisa rechazo al dueño con extracto de observación y marca avisos de la revisión del jefe.
notificarRevisionRRHH|Envía aviso de nueva revisión final a los destinatarios de usuariosRRHH.
notificarAprobacionRRHH|Avisa aprobación final a dueño/jefe y marca avisos de revisión de TH/gestor.
notificarRechazoRRHH|Avisa rechazo final a dueño/jefe con extracto de motivo y marca avisos TH/gestor.
notificarCancelacionUsuario|Avisa cancelación a jefe/TH sin duplicados ni dueño; marca avisos del solicitante y resume motivo.
usuariosRRHH|Resuelve destinatarios desde CC_RRHH, documentos TH fijos y auxiliares development; cachea por petición.
nombreEmpleado|Resuelve y cachea nombre por NIT o datos auxiliares development; usa fallback si no hay nombre.
empleadoModel|Crea perezosamente el modelo laboral y lo reutiliza en el servicio de avisos.
tipoLabel|Obtiene etiqueta de TIPOS_SOLICITUD o devuelve la clave original si no existe.
safeCrear|No inserta aviso para NIT vacío/ID no positivo; captura excepciones de creación y las registra.
guardarArchivoSubido|Genera nombre seguro, elige almacenamiento local/remoto y devuelve ruta lógica o NULL.
servirRuta|Valida nombre, intenta archivo local y después central remoto; envía PDF o devuelve false.
eliminarRuta|Valida nombre y elimina localmente en central o delega petición remota de borrado.
guardarEnCentral|Valida el nombre recibido y guarda el temporal en la ruta física central.
rutaCentral|Convierte nombre seguro en ruta física central o devuelve NULL.
eliminarEnCentral|Elimina archivo central por nombre seguro; considera éxito si ya no existe.
esServidorCentral|Compara SERVER_ADDR exactamente con PDF_STORAGE_CENTRAL_IP.
subirRemoto|Envía CURLFile/name por POST multipart con token y exige HTTP 201/ok=true.
solicitudRemota|Envía campos/acción con token a central y exige HTTP 200/ok=true, usado para borrado.
guardarLocal|Crea carpeta y mueve temporal con move_uploaded_file o rename; devuelve resultado del movimiento.
generarNombre|Compone documento, fecha, uniqid y nombre original saneado/truncado con extensión PDF.
nombreSeguro|Extrae basename y permite únicamente caracteres de nombre PDF seguros; evita usar rutas arbitrarias.
rutaLocal|Une directorio físico configurado y nombre validado.
enviarPdf|Limpia buffers, elimina CSP y envía PDF inline con tipo/longitud/no-cache/nosniff.
getSesionesActivas|Purga filas vencidas, consulta actividad y normaliza datos/tiempo relativo para panel.
purgar|Elimina registro auxiliar de sesiones con ULTIMA_ACT anterior al TTL de la clase.
ip|Valida REMOTE_ADDR y devuelve IP o desconocida para registro de sesión auxiliar.
hace|Convierte segundos de inactividad a texto en segundos, minutos u horas.
_rolBadge|Clasifica fila laboral como administrador, jefe o empleado y devuelve etiqueta/clase para directorio.
ugcPaginateRows|Pagina en memoria un array según parámetro GET y tamaño, devolviendo filas y metadatos.
ugcBuildPageUrl|Conserva $_GET, cambia el parámetro de página y construye URL de index.php.
ugcRenderPagination|Dibuja controles anterior/siguiente/números y tramo visible, solo si existe más de una página.
todasExcel|Exige rol del reporte, reúne grupos de solicitudes y datos laborales y descarga XLSX con varias hojas.
obtenerCanceladasPorUsuario|Detecta estados de cancelación en filas y usa métodos/filtros alternativos si no hay resultados; Admin puede perder filtros de fecha en respaldo.
filtrarSoloEquipoDelJefe|Conserva filas asignadas al jefe actual, excluyendo solicitudes propias tras normalizar documentos.
filtrarPorEstados|Normaliza estados recibidos y conserva filas cuyo ESTADO está en la lista permitida.
ordenarPorFechaCreacionAsc|Ordena por fecha de creación ascendente y usa ID para desempatar, colocando fecha no interpretable al inicio.
unirFilasUnicas|Combina grupos y deduplica por ID o hash de fila; evita duplicados dentro del historial combinado.
generarExcelPorHojas|Crea libro Calibri 11, añade hojas/prepara filas, ajusta anchos después de addSheet y descarga mediante SafeXlsx.
prepararFilas|Transforma rows a matriz de celdas, añade encabezado, traduce tipos y formatea fechas antes de aplicar diseño.
prepararCabecerasConDiseno|Forma encabezados blancos sobre verde #063125, centrados y con borde fino.
celdaConDiseno|Aplica raw, borde/alineación y ajuste de texto según campo; observaciones arriba/izquierda y resto centrado.
aplicarAnchosColumnas|Fija anchos numéricos por posición de columna después de crear hoja; captura problemas de compatibilidad.
limpiarValorParaExcel|Normaliza string/fecha/saltos, antepone apóstrofo a signos de fórmula y escapa HTML para celdas estiladas.
formatearFechaSolo|Convierte fecha a DD/MM/YYYY o conserva texto escalar si no se interpreta.
formatearFechaHoraAmPm|Convierte fecha/hora a DD/MM/YYYY hh:mm AM/PM o conserva texto si falla.
parsearFecha|Acepta objetos DateTime y formatos comunes de fecha; usa constructor como alternativa y NULL ante error.
constante|Obtiene constante definida por nombre o devuelve un valor por defecto.
normalizarEstado|Recorta y convierte estado a mayúsculas para comparación de reportes.
normalizarNit|Deja solo dígitos del documento para comparar alcance de jefe.
limpiarTituloHoja|Quita caracteres no permitidos, garantiza nombre no vacío y recorta a 31 caracteres.
enriquecerHojas|Recoge NIT únicos de todas las hojas y añade nombre/centro de costo laboral en lote, evitando consultas por fila.
valor|Devuelve valor textual o vacío; ante signo de fórmula, enlace/correo/etiqueta añade marcador raw para SimpleXLSXGen. No está invocado en exportadores actuales.
descargar|Guarda XLSX temporal, reempaqueta ZIP descartando contenido activo, envía descarga y limpia temporales; usa fallback si falta ZipArchive.
guardarParaPrueba|Guarda un XLSX reempaquetado en ruta indicada sin enviar respuesta HTTP; elimina origen temporal.
reempaquetar|Copia entradas ZIP a archivo nuevo y aborta si detecta entrada activa o error de lectura/escritura.
esEntradaActiva|Reconoce rutas de macros, externalLinks, embeddings, activeX u oleObjects que se rechazan en reempaquetado.
create|Factoría estática de SimpleXLSXGen; crea un libro y asigna título opcional.
fromArray|Crea libro con una primera hoja a partir de la matriz recibida y título opcional.
addSheet|Añade hoja, normaliza/deduplica nombre y guarda rows; devuelve el libro para encadenar configuración.
__toString|Genera contenido binario XLSX en memoria para representar el libro como string.
save|Guarda libro con nombre derivado del título o fecha UTC, delegando saveAs.
saveAs|Abre archivo destino saneado y escribe XLSX; devuelve booleano de éxito.
download|Descarga usando nombre derivado del título/fecha y delega downloadAs.
downloadAs|Envía cabeceras de descarga y contenido XLSX con el nombre indicado.
_write|Construye workbook, estilos, cadenas y hojas OOXML y los empaqueta como ZIP; rutina interna de la biblioteca.
_writeEntry|Escribe una entrada ZIP y su registro de directorio central; rutina interna de empaquetado.
_sheetToXML|Convierte filas/celdas, estilos, enlaces y dimensiones de hoja a XML; rutina interna de biblioteca.
setDefaultFont|Cambia familia tipográfica predeterminada del libro y devuelve instancia.
setDefaultFontSize|Cambia tamaño predeterminado de fuente del libro y devuelve instancia.
autoFilter|Configura rango del filtro automático de la hoja actual.
mergeCells|Añade rango de celdas combinadas a la hoja actual.
setColWidth|Configura ancho de una columna de la hoja actual; exige haber creado la hoja.
rightToLeft|Configura orientación de lectura derecha a izquierda del libro.
freezePanes|Configura celda de inmovilización de paneles para la hoja actual.
getNumFmtId|Resuelve o registra código de formato numérico y devuelve su identificador.
date2excel|Convierte componentes de fecha/hora al número serial usado por Excel.
esc|Escapa texto XML/HTML para la biblioteca; no es el helper e de la aplicación.
raw|Marca un valor para tratamiento textual sin interpretación automática de la biblioteca.
rawArray|Aplica marcador raw a valores de un array.
col2index|Convierte letras de columna Excel a índice numérico; rutina privada de biblioteca.
cell2coord|Descompone dirección de celda Excel y escribe coordenadas por referencia.
coord2cell|Convierte coordenadas numéricas a referencia de celda Excel.
''')

for method, label in {'setTitle':'título','setSubject':'asunto','setAuthor':'autor','setCompany':'empresa','setManager':'responsable','setKeywords':'palabras clave','setDescription':'descripción','setCategory':'categoría','setLanguage':'idioma','setApplication':'aplicación generadora','setLastModifiedBy':'último modificador'}.items():
    COMMON[method] = f'Establece metadato {label} del libro SimpleXLSXGen y devuelve instancia para encadenar.'

add('''
crear|Inserta SOLICITUDES_PERMISOS con fechas normalizadas, duración, comprobante, estado y ACTIVO=1. No incluye ID explícito.
cancelarAgenda|Lee elegibilidad y actualiza cancelación con motivo/actor/fecha; admite estado residual y parámetro gestor, aunque el controlador restringe a dueño.
eliminar|Realiza baja lógica ACTIVO=0 de solicitud propia pendiente; parámetro opcional admite aprobado jefe, no usado por la acción ordinaria.
''', 'models/solicitudModel.php')
add('''
crear|Inserta NOTIFICACIONES para destinatario/tipo/mensaje/solicitud con LEIDA=0 y fecha Colombia; ID depende del esquema.
''', 'models/notificacionModel.php')
add('''
listar|Devuelve JSON con hasta veinte avisos del usuario, liberando el bloqueo de sesión antes de consultar.
marcarLeida|Valida login/CSRF, limita destinatario al usuario actual y devuelve success JSON.
marcarTodasLeidas|Valida login/CSRF y marca avisos del usuario actual; devuelve success JSON.
''', 'controllers/notificacionController.php')
add('''
listar|Selecciona solicitudes según rol/categoría, carga array completo y presenta vista shared/solicitudes; la vista pagina en memoria.
cancelarAgenda|Exige dueño en estado aprobado y motivo no vacío, delega actualización/cancelación y avisa a jefe/TH. No limita longitud en PHP.
eliminar|Exige dueño en pendiente jefe y CSRF, aplica baja lógica y regresa con mensaje. No borra físicamente PDF.
''', 'controllers/solicitudController.php')
add('''
registrar|Inserta evento administrativo con actor/nombre/acción/objetivo/metadatos JSON/IP/fecha mediante SEQ_ADMIN_AUDITORIA.
''', 'models/adminAuditoriaModel.php')
add('''
registrar|Purga sesiones vencidas e inserta/actualiza actividad de session_id actual con datos del usuario y navegador.
cerrar|Elimina la fila SESIONES_ACTIVAS de la sesión indicada o la actual; no elimina por sí mismo $_SESSION.
__construct|Guarda TTL de actividad recibido, 1800 segundos por defecto; independiente de SESSION_IDLE_TIMEOUT.
''', 'services/sesionActivaService.php')
add('''
contarNoLeidas|Envuelve conteo del modelo y devuelve 0 ante excepción; un cero no certifica ausencia real si falla la BD.
getNoLeidas|Envuelve lista del modelo y devuelve [] ante excepción.
marcarLeida|Envuelve actualización de lectura por ID/destinatario y devuelve false ante excepción.
marcarTodasLeidas|Envuelve marcado de todos los avisos del destinatario y devuelve false ante excepción.
marcarLeidasPorSolicitud|Envuelve marcado por solicitud/destinatario, capturando excepciones.
''', 'services/notificacionService.php')

FILE_DESCRIPTIONS = {
    '.gitignore': 'Exclusiones de sesiones, archivos subidos, datos privados, logs, herramientas e IDE; no evita copiar por FTP y server.php está versionado.',
    '.htaccess': 'Bloqueo de directorios privados, caché/compresión de assets y reescritura hacia index.php; RewriteBase requiere ajuste por entorno.',
    'index.php': 'Punto de entrada principal y mapa explícito de pantallas/acciones; rutas internas PDF se despachan antes de sesión común.',
    'config/app.php': 'Roles, estados, catálogo, bloqueo vacaciones, listas especiales, datos auxiliares y URL/versionado de assets.',
    'config/server.php': 'Constantes de entorno, conexiones, sesiones y central PDF. Contiene secretos; no se reproducen sus valores en el manual.',
    'config/helpers.php': 'URLs, redirección segura, flash, fechas colombianas y renderizado de vistas/layouts.',
    'config/seguridad.php': 'Sesión, CSRF, CSP/cabeceras, escape, autenticación vigente y autorización por rol.',
    'config/conexion.php': 'Clase Conexion para Oracle laboral ICEBERG con opción persistente y caché de statements.',
    'config/conexion_uxxi.php': 'Clase ConexionUXXI para la base operativa de permisos con oci_connect.',
    'config/conexion_ldap.php': 'Conexión/bind/búsqueda LDAP y diagnóstico por etapas con redacción de campos secretos.',
    'controllers/adminController.php': 'Directorio/perfiles, CSV, roles, intervención, notas, panel SA, auditoría y diagnóstico.',
    'controllers/authController.php': 'Login, vacaciones, bloqueo por intentos, selector/cambio de modo y logout.',
    'controllers/conflictoJefesController.php': 'Análisis/API legado de superposición de listas; sin ruta principal y acceso directo bloqueado por Apache.',
    'controllers/dashboardController.php': 'Paneles por rol con conteos, filtros, paginación SQL y enriquecimiento de nombres.',
    'controllers/notificacionController.php': 'Endpoints JSON de conteo/lista/lectura de avisos del usuario actual.',
    'controllers/pdfStorageController.php': 'Endpoints internos tokenizados para upload, lectura y borrado central de PDFs.',
    'controllers/solicitudController.php': 'CRUD, aprobaciones, cancelación, permisos, duración, comprobantes y token único de creación.',
    'models/adminAuditoriaModel.php': 'Persistencia/consulta y CSV de acciones administrativas en UXXI.',
    'models/adminRolesModel.php': 'Administradores adicionales activos, caché por petición y cupo máximo.',
    'models/authModel.php': 'Une autenticación LDAP, autorización laboral ICEBERG y perfil de sesión.',
    'models/empleadoModel.php': 'Fuente laboral activa, nombres por lotes, centros, candidatos y consulta jerárquica legada.',
    'models/mainModel.php': 'Clase base de acceso OCI8: statements, binds, lecturas y ejecución con commit.',
    'models/mainModelUXXI.php': 'Hereda acceso OCI8 y sustituye conexión por UXXI; expresión SQL de hora Colombia.',
    'models/notificacionModel.php': 'Avisos por destinatario vinculados a solicitudes activas y marcas de lectura.',
    'models/solicitudModel.php': 'SQL de solicitudes, estados, filtros, estadísticas, paginación y baja lógica.',
    'services/loginBloqueoService.php': 'Bloqueo de tres intentos por documento en ventana de quince minutos, persistido en UXXI.',
    'services/notaAdminService.php': 'Notas internas por solicitud mediante tabla/secuencia UXXI.',
    'services/notificacionService.php': 'Mensajes/destinatarios por evento y acceso tolerante a errores del modelo.',
    'services/pdfStorageService.php': 'Nombres/rutas PDF, escritura local/remota, lectura protegida y compensación de borrado.',
    'services/sesionActivaService.php': 'Registro/purga auxiliar de actividad de sesión; no almacena variables PHP de sesión.',
    'Export/Admin/ExportController.php': 'XLSX administrativo con seis hojas y filtro de fecha de creación; revisar respaldo de canceladas.',
    'Export/Jefe/ExportController.php': 'XLSX de equipo del jefe y solicitudes personales, con seis hojas.',
    'Export/RRHH/ExportController.php': 'XLSX de Talento Humano con siete hojas y fecha de decisión final.',
    'Export/Shared/EmpleadoInfoEnricher.php': 'Enriquecimiento laboral de todas las hojas con consultas por lotes de NIT.',
    'Export/Shared/SafeXlsx.php': 'Protección de valores y reempaquetado de XLSX para rechazar partes activas.',
    'Export/Libraries/SimpleXLSXGen.php': 'Biblioteca Shuchkin de generación OOXML/ZIP y formato Excel; 38 métodos incluidos en catálogo.',
    'assets/css/ugc.css': 'Estilos globales, componentes, login, Acerca de, PDFs, campana, tema oscuro y múltiples overrides móviles.',
    'assets/js/acerca-de.js': 'Carrusel de equipo/contacto con foco, pausa y copia de correo.',
    'assets/js/jefe-picker.js': 'Select de jefe convertido en buscador con teclado y sincronización del campo original.',
    'assets/js/pdf-mobile-preview.js': 'Preview móvil de primera página PDF en canvas mediante PDF.js local.',
    'assets/vendor/pdfjs/pdf.min.js': 'Bundle PDF.js 3.11.174; API pdfjsLib usada por adaptador móvil.',
    'assets/vendor/pdfjs/pdf.worker.min.js': 'Worker de la misma distribución PDF.js para procesar documentos; mantener versión compatible.',
    'assets/img/Logo ULGC.png': 'Logo institucional referenciado por login, selector y cabecera.',
    'assets/img/favicon.png': 'Icono del navegador en ambos layouts.',
    'assets/img/login-fondo.png': 'Fondo ilustrado de login y de header/footer por CSS.',
    'assets/img/capibara-login.png': 'Mascota del login visible junto al logo.',
    'assets/img/capibara-login1.png': 'Variante de mascota sin referencia encontrada en fuentes propias.',
    'assets/img/capibaras-equipo.webp': 'Imagen de equipo en la segunda diapositiva de Acerca de.',
    'assets/sounds/notificacion.mp3': 'Audio local reproducido por la campana según contador/entrada al sistema.',
    'storage/.htaccess': 'Deniega todo acceso HTTP al árbol storage.',
    'uploads/.htaccess': 'Desactiva listado y deniega ejecución PHP/phtml/phar en uploads.',
    'views/admin/conflictos/index.php': 'Vista autónoma legada de conflictos con include/rutas incorrectos; no conectada al router.',
    'views/admin/dashboard.php': 'Panel general de solicitudes, tarjetas y rango de creación/indicadores adicionales.',
    'views/admin/empleados/index.php': 'Directorio, filtros/ordenación, tabla/tarjetas, paginación y exportación CSV.',
    'views/admin/empleados/detalle.php': 'Perfil con pestañas, historial de solicitudes y gestión de roles por superusuario.',
    'views/auth/acerca_de.php': 'Parcial compartido por login/vacaciones: contactos, historia y controles de carrusel.',
    'views/auth/login.php': 'Formulario institucional, logo/mascota, toggle contraseña y estado de envío.',
    'views/auth/seleccionar_modo.php': 'Selector de modo para SA/admin adicional/TH; estilos y envío locales.',
    'views/auth/vacaciones.php': 'Aviso estático de bloqueo de login con Acerca de.',
    'views/empleado/dashboard.php': 'Panel personal con cinco tarjetas de estado e historial paginado.',
    'views/empleado/form_crear.php': 'Formulario multipart con modalidad de duración, selector de jefe y preview PDF.',
    'views/empleado/form_editar.php': 'Formulario con datos iniciales, ajuste duración y comprobante opcional de sustitución.',
    'views/jefe/dashboard.php': 'Panel de pendientes de equipo, historial de decisiones y solicitudes propias.',
    'views/layouts/auth.php': 'Marco de páginas de acceso con fuentes/favicon/fondo y protección de envío/drag.',
    'views/layouts/main.php': 'Marco autenticado, header/buscador/campana/modo/logout/footer y JavaScript transversal.',
    'views/rrhh/dashboard.php': 'Panel de pendientes finales, historial completo y solicitudes propias de TH.',
    'views/shared/404.php': 'Pantalla de ruta inexistente en layout de acceso.',
    'views/shared/badge_estado.php': 'Mapeo de estado interno a etiqueta y clase visual.',
    'views/shared/dashboard_kpi_cards.php': 'Renderer común de tarjetas KPI con SVG, valor y URL recibidos de dashboard.',
    'views/shared/detalle.php': 'Detalle y decisiones, comprobante, observaciones, notas/intervención/estado forzado y modal de cancelación.',
    'views/shared/pagination.php': 'Paginación en memoria, enlaces conservando GET y componente visual común.',
    'views/shared/solicitudes.php': 'Contenedor de título y tabla para listado independiente.',
    'views/shared/tabla_solicitudes.php': 'Tabla común con acciones por rol/propiedad/estado y panel visible de cancelación.',
    'views/superadmin/panel.php': 'Panel SA con sesiones, salud, cuota/admins, auditoría y autocompletado.'
}

JS = {}
for line in '''
stopTimer|Cancela temporizador del carrusel.
scheduleNext|Programa la siguiente diapositiva si el panel está visible, sin pausa y con pestaña activa.
showSlide|Activa diapositiva/dot, sincroniza aria/inert/foco y programa el siguiente cambio.
closePanel|Oculta Acerca de, recupera portada y devuelve foco al botón de apertura.
copyWithSelection|Copia correo con textarea temporal y execCommand como alternativa.
copyEmail|Intenta Clipboard API en contexto seguro y usa copia por selección como fallback.
getOptions|Extrae opciones no vacías del select de jefe como value/label.
closeOtherPickers|Cierra otros selectores abiertos y sincroniza aria-expanded.
window.initJefePicker|Construye interfaz de búsqueda y devuelve API select/focus/isValid; conserva el campo nativo que se envía.
selectedOption|Obtiene opción seleccionada del select nativo.
syncTrigger|Actualiza texto y estilo de placeholder del botón según select.
close|Oculta panel del selector y actualiza estado accesible.
render|Dibuja opciones filtradas del jefe; en preview móvil, procesa primera página PDF en canvas.
open|Abre buscador del jefe, limpia filtro y lleva foco a búsqueda.
choose|Selecciona valor nativo, dispara change, actualiza botón y cierra panel.
focusOption|Avanza/retrocede entre opciones por teclado con rotación de extremos.
focus|API pública del picker que abre y enfoca el buscador.
isValid|Indica si el select conserva un value no vacío.
mobile|Detecta agente móvil o puntero coarse para activar preview canvas.
size|Formatea tamaño del PDF en Bytes/KB/MB en el adaptador móvil.
clear|Elimina canvas/contenedor de preview móvil anterior.
box|Obtiene o crea contenedor/canvas/mensaje del preview móvil.
recargarAnalisis|Recarga vista legada de conflictos.
exportarReporte|Abre URL de reporte de conflictos; ruta relativa no coincide con integración actual.
verificarNit|Consulta API legada y dibuja diagnóstico; necesita corregir ruta/permisos/escape si se reactiva.
detTab|Activa pestaña/panel del perfil de empleado.
setView|Alterna tabla/tarjetas del directorio y persiste preferencia en localStorage.
parseFechaLocal|Forma Date local a partir de YYYY-MM-DD evitando interpretación automática UTC.
formatoISO|Convierte Date local a cadena YYYY-MM-DD.
sumarMeses|Calcula fecha visual máxima con setMonth; conserva el comportamiento de rollover de JavaScript.
esDomingo|Comprueba getDay()==0.
calcularDiasHabiles|Cuenta rango inclusive excluyendo domingos, sin calendario de festivos.
obtenerModoSeleccionado|Devuelve HORAS/DIAS/AMBAS según radio seleccionado.
limpiarDuracion|Vacía unidades/resumen y limpia validez personalizada.
validarMensajeHorasNativo|Asigna mensajes de validación a horas según rango/paso.
actualizarEstiloOpciones|Resalta tarjetas de duración y sincroniza toggle AMBAS.
actualizarResumen|Presenta texto de duración según unidades y modo.
actualizarVistaDuracion|Muestra/oculta campos y cambia required, labels y unidades según tipo/modo.
calcularDuracionDias|Normaliza rango del formulario y calcula días; HORAS fuerza fecha fin igual.
validarHorasPermitidas|Valida valor/paso de horas y devuelve true/false con mensajes/foco.
validarDiasPermitidos|Impide rango sin días contables y devuelve true/false.
formatFileSize|Formatea tamaño del adjunto para la cabecera de preview.
showPdfError|Muestra error, oculta preview y vacía archivo seleccionado.
hidePdfError|Oculta aviso de error del PDF.
resetPdfPreview|Oculta preview, limpia iframe/input y errores.
closest|Envuelve Element.closest para la protección global drag/drop.
isProtectedDropTarget|Reconoce campos de texto/editables donde se bloquea drop; permite inputs de archivo.
readStoredNotifCount|Obtiene último contador en sessionStorage, con tolerancia a storage no disponible.
writeStoredNotifCount|Guarda contador de avisos por usuario en sessionStorage.
markSilentNotificationRefresh|Guarda timestamp para refresco sin sonido al volver del PDF.
consumeSilentNotificationRefresh|Consume marca y comprueba vigencia de cinco minutos.
getNotificationAudio|Crea perezosamente Audio, configura preload y volumen 0,8.
playNotificationSound|Reinicia/reproduce sonido y tolera bloqueo de autoplay.
unlockNotificationSound|Intenta habilitar audio con una interacción del usuario sin sonido audible.
finishUnlock|Pausa/reinicia audio y restaura mute después de desbloqueo.
fetchJson|Envía fetch con sesión/cabecera AJAX, interpreta JSON y maneja 401/redirección.
post|Construye FormData y adjunta CSRF al POST de avisos.
setCount|Actualiza badge, 99+ y clase de campana con nuevos avisos.
handleCount|Decide si suena según primer sondeo/incremento/refresco silencioso y guarda contador.
escapeHtml|Escapa valores insertados en la lista de notificaciones mediante innerHTML.
formatFechaCo|Formatea fecha/hora ISO a presentación colombiana AM/PM en navegador.
iconClass|Escoge estilo de icono según el tipo del evento.
drawList|Dibuja botones de avisos y mensaje de lista vacía.
loadCount|Consulta contador y coordina actualización/sonido; tolera fallos.
loadList|Consulta y dibuja avisos no leídos de la campana.
startPolling|Consulta inmediatamente y programa sondeo cada 60 segundos.
stopPolling|Cancela intervalo cuando se oculta la pestaña.
ugcAbrirPanelCancelacion|Abre una fila de cancelación, cierra otras y enfoca motivo.
ugcCerrarPanelCancelacion|Oculta panel de cancelación asociado al botón.
ugcValidarCancelacion|Exige motivo no vacío y solicita confirmación de cancelación.
getItems|Obtiene sugerencias seleccionables del autocompletado SA, excluyendo ya admins.
clearActive|Quita estilo de sugerencia activa del autocompletado SA.
selectItem|Guarda NIT en hidden, muestra nombre y habilita botón Asignar.
renderDropdown|Construye sugerencias escapadas, distingue ya admins y conecta selección.
esc|Escapa texto de sugerencias del panel SA para innerHTML.
buscar|Consulta autocompletado SA con mínimo dos caracteres y presenta carga/resultados.
'''.strip().splitlines():
    name, desc = line.split('|', 1)
    JS[name] = desc

def purpose(path, name):
    if name in SPEC.get(path, {}): return SPEC[path][name]
    if name in COMMON: return COMMON[name]
    raise KeyError(f'Descripcion pendiente: {path}:{name}')
