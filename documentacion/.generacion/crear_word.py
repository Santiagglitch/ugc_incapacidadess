from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
from xml.sax.saxutils import escape
import json, re, struct, hashlib
import xml.etree.ElementTree as ET
from descripciones import purpose, FILE_DESCRIPTIONS, JS

ROOT = Path(__file__).resolve().parents[2]
BASE = Path(__file__).resolve().parent
OUT = ROOT / 'documentacion' / 'Manual_completo_ugc_incapacidadess.docx'
inventory = json.loads((BASE / 'inventario.json').read_text(encoding='utf-8'))
source_md = (BASE / 'contenido.md').read_text(encoding='utf-8')
lint = json.loads((BASE / 'lint.json').read_text(encoding='utf-8-sig'))
js_checks = json.loads((BASE / 'js_validacion.json').read_text(encoding='utf-8'))
sources = {e['path']: e for e in inventory}
body = []
source_symbols = []
bookmarks = []
WIDTH = 9638

def x(text):
    return escape(str(text), {'"': '&quot;'}).replace('\x00', '')

def run(text, bold=False, size=None, color=None, mono=False):
    props = '<w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/>' if mono else ''
    if bold: props += '<w:b/>'
    if color: props += f'<w:color w:val="{color}"/>'
    if size: props += f'<w:sz w:val="{size}"/>'
    props = f'<w:rPr>{props}</w:rPr>' if props else ''
    parts = str(text).split('\n')
    return ''.join(f'<w:r>{props}' + ('<w:br/>' if i else '') + f'<w:t xml:space="preserve">{x(t)}</w:t></w:r>' for i, t in enumerate(parts))

def paragraph(text='', style='Normal', bold=False, mono=False, bookmark=None, align=None, after=None):
    props = f'<w:pStyle w:val="{style}"/>'
    if after is not None: props += f'<w:spacing w:after="{after}"/>'
    if align: props += f'<w:jc w:val="{align}"/>'
    opening = closing = ''
    if bookmark:
        index = len(bookmarks) + 1
        bookmarks.append(bookmark)
        opening = f'<w:bookmarkStart w:id="{index}" w:name="{bookmark}"/>'
        closing = f'<w:bookmarkEnd w:id="{index}"/>'
    body.append(f'<w:p><w:pPr>{props}</w:pPr>{opening}{run(text, bold=bold, mono=mono)}{closing}</w:p>')

def page_break():
    body.append('<w:p><w:r><w:br w:type="page"/></w:r></w:p>')

def table(headers, rows, widths=None):
    widths = widths or [WIDTH // len(headers)] * len(headers)
    widths[-1] += WIDTH - sum(widths)
    borders = ''.join(f'<w:{k} w:val="single" w:sz="4" w:color="D4E0D8"/>' for k in ['top','left','bottom','right','insideH','insideV'])
    props = f'<w:tblW w:w="{WIDTH}" w:type="dxa"/><w:tblBorders>{borders}</w:tblBorders><w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="70" w:type="dxa"/><w:left w:w="90" w:type="dxa"/><w:bottom w:w="70" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar>'
    contents = []
    for i, values in enumerate([headers] + list(rows)):
        cells = []
        for col, value in enumerate(values):
            shade = '0B5A2B' if i == 0 else ('F1F7F3' if i % 2 == 0 else 'FFFFFF')
            cp = f'<w:tcW w:w="{widths[col]}" w:type="dxa"/><w:shd w:fill="{shade}"/><w:vAlign w:val="top"/>'
            lines = str(value).split('\n')
            paras = []
            for line in lines:
                # Permite dividir rutas largas sin cambiar su significado visible.
                line = line.replace('/', '/\u200b').replace('\\', '\\\u200b')
                paras.append('<w:p><w:pPr><w:pStyle w:val="TableText"/><w:spacing w:after="30"/></w:pPr>' + run(line, bold=i==0, size=18, color='FFFFFF' if i==0 else '23392B') + '</w:p>')
            cells.append(f'<w:tc><w:tcPr>{cp}</w:tcPr>{"".join(paras)}</w:tc>')
        rowprops = '<w:tblHeader/>' if i == 0 else ''
        contents.append(f'<w:tr><w:trPr><w:cantSplit/>{rowprops}</w:trPr>{"".join(cells)}</w:tr>')
    body.append(f'<w:tbl><w:tblPr>{props}</w:tblPr><w:tblGrid>' + ''.join(f'<w:gridCol w:w="{w}"/>' for w in widths) + '</w:tblGrid>' + ''.join(contents) + '</w:tbl>')
    paragraph('', after=70)

def code(lines):
    for line in lines:
        paragraph(line, style='Code', mono=True, after=0)
    paragraph('', after=60)

def php_catalog():
    order = {'config':0,'controllers':1,'models':2,'services':3,'Export':4,'views':5}
    entries = sorted([e for e in inventory if e['functions']], key=lambda e:(order.get(e['path'].split('/')[0],9), e['path']))
    for i, entry in enumerate(entries, 1):
        p = entry['path']
        paragraph(f'17.{i}. {p}', 'Heading2')
        paragraph(FILE_DESCRIPTIONS[p], 'Small')
        if entry['classes']:
            txt = (ROOT/p).read_text(encoding='utf-8')
            inherited = re.search(r'\bclass\s+\w+\s+extends\s+(\w+)', txt)
            classes = '; '.join(c['name'] + ' (línea '+str(c['line'])+')' for c in entry['classes'])
            paragraph('Clase: ' + classes + ('. Hereda '+inherited.group(1)+'.' if inherited else '.'), 'Small')
        rows = []
        for f in entry['functions']:
            desc = purpose(p, f['name'])
            vis = f['visibility'] or 'función global'
            sig = f['signature'].removeprefix('function ')
            if f['static']: vis += ' static'
            rows.append([f["name"]+'\nLínea '+str(f['line'])+' · '+vis, sig, desc])
            source_symbols.append(p + ':' + f['name'] + ':' + str(f['line']))
        table(['Símbolo / ubicación','Entradas y retorno declarado','Propósito y condiciones'], rows, [2350,3150,4138])

def js_catalog():
    count = 0
    for entry in inventory:
        p = entry['path']
        if '/vendor/' in p or not p.endswith(('.php','.js')): continue
        text = (ROOT/p).read_text(encoding='utf-8')
        # Reconoce > dentro de atributos entrecomillados como nonce con expresión PHP.
        rx = r'<script\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*?>(.*?)</script>'
        blocks = [(0,text)] if p.endswith('.js') else [(m.start(1),m.group(1)) for m in re.finditer(rx,text,re.S|re.I)]
        funcs = []
        for off, block in blocks:
            for m in re.finditer(r'(?:async\s+)?function\s+([A-Za-z_$][\w$]*)\s*\(',block):
                funcs.append((m.group(1),text.count('\n',0,off+m.start())+1))
            for m in re.finditer(r'([A-Za-z_$][\w$]*(?:\.[A-Za-z_$][\w$]*)*)\s*(?:=|:)\s*(?:async\s+)?function\s*\(',block):
                funcs.append((m.group(1),text.count('\n',0,off+m.start())+1))
        if not funcs: continue
        paragraph(p, 'Heading2')
        rows = []
        for name, line in sorted(set(funcs),key=lambda f:f[1]):
            if name not in JS: raise KeyError(f'Descripción JS pendiente: {p}:{name}')
            rows.append([name, str(line), JS[name]])
            count += 1
        table(['Función/API','Línea','Responsabilidad'],rows,[2500,650,6488])
    return count

def css_index():
    css = (ROOT/'assets/css/ugc.css').read_text(encoding='utf-8')
    rows = []
    for m in re.finditer(r'/\*(.*?)\*/',css,re.S):
        title = re.sub(r'\s+', ' ', m.group(1)).strip().strip('= ').strip()
        rows.append([str(css.count('\n',0,m.start())+1),title])
    table(['Línea','Comentario de bloque en ugc.css'],rows,[800,8838])
    return len(rows)

def file_inventory():
    rows = []
    for e in inventory:
        p = e['path']
        if p not in FILE_DESCRIPTIONS: raise KeyError('Archivo sin documentar '+p)
        binary = p.endswith(('.png','.webp','.mp3'))
        rows.append([p,'Binario' if binary else str(e['lines']),FILE_DESCRIPTIONS[p]])
    table(['Archivo relativo','Líneas','Finalidad / observación'],rows,[3650,700,5288])

def routes_get():
    rows = [
        ['login / cadena vacía','authController::loginForm','Público; bandera vacaciones; layout auth'],
        ['dashboard','dashboardController::index','Login y modo; panel por rol'],
        ['solicitudes','solicitudController::listar','Login; tipo/estado; alcance por rol'],
        ['solicitud_crear','solicitudController::crearForm','Login; revisores y token de creación'],
        ['solicitud_editar','solicitudController::editarForm','id; dueño pendiente jefe'],
        ['solicitud_ver','solicitudController::ver','id y return_to opcional; permiso de ver'],
        ['solicitud_archivo','solicitudController::servirArchivo','id; comprobante autorizado'],
        ['notif_count','notificacionController::contador','Login; JSON contador'],
        ['notif_list','notificacionController::listar','Login; JSON notificaciones'],
        ['seleccionar_modo','authController::seleccionarModoForm','Usuario privilegiado con modo pendiente'],
        ['admin_empleados','adminController::empleados','ROL_ADMIN; q/rol/cc/ord/dir/pagina/pp'],
        ['admin_empleado','adminController::empleadoDetalle','ROL_ADMIN; nit/pag_solicitudes'],
        ['admin_empleados_export','adminController::exportEmpleadosCsv','ROL_ADMIN; q/rol/cc; CSV'],
        ['superadmin_panel','adminController::superAdminPanel','ROL_ADMIN y superusuario; log_accion/log_pagina'],
        ['superadmin_health','adminController::systemHealth','ROL_ADMIN y superusuario; JSON'],
        ['superadmin_buscar_empleado','adminController::buscarEmpleadoJson','ROL_ADMIN y superusuario; q; hasta ocho'],
        ['superadmin_auditoria_export','adminController::auditoriaExportCsv','ROL_ADMIN y superusuario; CSV completo'],
        ['pdf_storage_file','pdfStorageController::file','name; token interno; antes de sesión común']]
    table(['view GET','Destino','Entradas / condición'],rows,[2550,3650,3438])

def routes_post():
    rows = [
        ['login','authController::loginPost','cedula/password/CSRF; vacaciones y bloqueo'],
        ['logout','authController::logout','CSRF; destruye sesión'],
        ['seleccionar_modo','authController::seleccionarModoPost','modo/CSRF; selector pendiente'],
        ['cambiar_modo','authController::cambiarModo','modo_destino/CSRF; usuario con selector'],
        ['solicitud_create','solicitudController::crearPost','Campos/PDF/_submit_token/CSRF; login'],
        ['solicitud_update','solicitudController::editarPost','id/campos/PDF opcional/CSRF; dueño pendiente'],
        ['solicitud_jefe','solicitudController::gestionJefePost','id/decision/observacion_jefe/CSRF; asignado'],
        ['solicitud_rrhh','solicitudController::gestionRrhhPost','id/decision/observacion_rrhh/CSRF; ajena aprobado jefe'],
        ['solicitud_cancelar_agenda','solicitudController::cancelarAgenda','id/observacion_cancelacion/CSRF; dueño aprobado'],
        ['solicitud_delete','solicitudController::eliminar','id/CSRF; dueño pendiente; _method=DELETE'],
        ['admin_hacer_admin','adminController::hacerAdmin','nit/CSRF; admin+SA; _method=PUT en vista'],
        ['admin_quitar_admin','adminController::quitarAdmin','nit/CSRF; admin+SA; _method=DELETE'],
        ['superadmin_asignar_admin','adminController::asignarAdminDesdePanel','nit/CSRF; admin+SA'],
        ['admin_intervenir_solicitud','adminController::intervenirSolicitud','id/decision/observacion_admin/return_to/CSRF'],
        ['admin_forzar_estado','adminController::forzarEstadoSolicitud','id/nuevo_estado/motivo/return_to/CSRF'],
        ['admin_guardar_nota','adminController::guardarNotaAdmin','id/nota_admin/return_to/CSRF; admin'],
        ['notif_read','notificacionController::marcarLeida','id y CSRF; destinatario actual'],
        ['notif_read_all','notificacionController::marcarTodasLeidas','CSRF; destinatario actual'],
        ['pdf_storage_upload','pdfStorageController::upload','name/documento_pdf; token interno; sin sesión usuario'],
        ['pdf_storage_delete','pdfStorageController::delete','name; token interno; sin sesión usuario']]
    table(['action','Destino','Contrato principal'],rows,[2550,3650,3438])

def architecture():
    table(['Presentación','Coordinación','Integraciones/datos'],[
        ['Navegador → index.php\nGET view / POST action','Controladores\nLogin, permisos, validación','LDAP\nIdentidad institucional'],
        ['views + layouts\nHTML/JS por rol','helpers + seguridad\nSesión, CSRF, render_view','Oracle ICEBERG\nEMPLEADO / CENTRO_COSTO'],
        ['assets\nCSS, imágenes, audio, PDF.js','modelos / servicios\nSQL y operaciones compartidas','Oracle UXXI\nSolicitudes, roles, avisos, auditoría'],
        ['Excel/CSV / PDF\nDescargas autorizadas','Export + pdfStorageService\nFormato y transporte','Central PDF\nstorage/solicitudes + token HTTP']],[2850,3388,3400])
    paragraph('Figura 1. Flujo conceptual elaborado a partir del código. No representa una captura de ejecución.', 'Caption')

def states():
    table(['Origen','Actor / decisión','Destino'],[
        ['Formulario válido','Solicitante crea →','PENDIENTE_JEFE'],
        ['PENDIENTE_JEFE','Jefe asignado aprueba →','APROBADO_JEFE'],
        ['PENDIENTE_JEFE','Jefe asignado rechaza con motivo →','RECHAZADO_JEFE'],
        ['APROBADO_JEFE','TH/admin ajeno aprueba o rechaza →','APROBADO_RRHH / RECHAZADO_RRHH'],
        ['PENDIENTE_JEFE de TH fijo','Jefe aprueba; excepción automática →','APROBADO_RRHH'],
        ['APROBADO_JEFE / APROBADO_RRHH','Dueño cancela con motivo →','CANCELADO_AGENDA'],
        ['Estados intervenibles','Admin ajeno decide con motivo →','APROBADO_RRHH / RECHAZADO_RRHH'],
        ['Solicitud activa ajena','Admin fuerza con motivo →','Uno de cinco estados ordinarios'],
        ['PENDIENTE_JEFE propio','Dueño elimina →','ACTIVO=0; estado conservado']],[3100,3538,3000])
    paragraph('Figura 2. Transiciones ordinarias y excepciones administrativas; baja lógica es un cambio de actividad, no un estado adicional.', 'Caption')

def validation_text():
    paragraph(f'PHP: {lint["files"]} archivos versionados revisados con php -l, sin errores de sintaxis. Entorno CLI: PHP 8.2.0. No se ejecutaron archivos de negocio.', 'Normal')
    failures = [c for c in js_checks['checks'] if not c['ok']]
    paragraph(f'JavaScript: node --check sobre {js_checks["external_js"]} archivos externos (incluidos PDF.js/worker) y {js_checks["inline_scripts"]} bloques inline con expresiones PHP sustituidas por placeholders, {len(failures)} fallos de sintaxis. Node local: 21.6.2. Esta sustitución no comprueba valores reales generados por PHP, DOM, CSP o comportamiento.', 'Normal')
    paragraph('Word: documento DOCX construido como paquete OOXML, validado por integridad ZIP, XML, relaciones internas, cobertura del inventario y ausencia de secretos configurados. La generación no utiliza Word para paginar/renderizar: la inspección estructural no equivale a una revisión visual de cada página en Microsoft Word.', 'Normal')
    paragraph('Código: los archivos versionados conservan los hashes del inventario inicial. Solo se agregaron documentación y herramientas de generación dentro de documentacion/.generacion. Sin cambios de configuración, conexiones, solicitudes, roles ni archivos de usuarios.', 'Normal')

# Cubierta.
paragraph('UNIVERSIDAD LA GRAN COLOMBIA', 'CoverEyebrow', align='center')
png = (ROOT/'assets/img/Logo ULGC.png').read_bytes()
pw,ph = struct.unpack('>II',png[16:24])
cx = 1800000
cy = int(cx * ph/pw)
drawing = f'''<w:p><w:pPr><w:spacing w:before="300" w:after="360"/><w:jc w:val="center"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="{cx}" cy="{cy}"/><wp:docPr id="1" name="Logo institucional" descr="Universidad La Gran Colombia"/><wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="logo.png"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rIdLogo"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>'''
body.append(drawing)
paragraph('MANUAL COMPLETO', 'CoverTitle', align='center')
paragraph('Funcionamiento, configuración\ny mantenimiento del proyecto', 'CoverSubtitle', align='center')
paragraph('ugc_incapacidadess', 'CoverProject', align='center')
paragraph('PHP · Oracle · LDAP · Diseño · PDFs · Exportaciones', 'CoverSubtitle', align='center')
paragraph('Revisión: 14 de octubre de 2026\nReferencia del código: f5e3944\nCopia local: C:\\xampp\\htdocs\\ugc_incapacidadess', 'CoverMeta', align='center')
paragraph('Guía de cambios con rutas concretas\nCatálogo de 28 clases y 382 funciones/métodos PHP', 'CoverMeta', align='center')
paragraph('Documento técnico. Credenciales y tokens omitidos.', 'CoverMeta', align='center')
page_break()
paragraph('Índice de navegación', 'Heading1')
paragraph('Seleccione un capítulo para ir a su contenido. También puede usar Vista → Panel de navegación en Word. Los números de página del pie se calculan al abrir el documento.', 'Small')
chapters = re.findall(r'^# (\d+\..+)$',source_md,re.M)
for i,title in enumerate(chapters,1):
    body.append(f'<w:p><w:pPr><w:pStyle w:val="TOC1"/></w:pPr><w:hyperlink w:anchor="cap_{i}" w:history="1">{run(title,color="0B5A2B")}</w:hyperlink></w:p>')
paragraph('Cómo usarlo: objetivo → guía de cambios → módulo → catálogo con línea/firma. Las líneas identifican esta versión del código y se desplazan con nuevas ediciones.', 'Small')
page_break()

callbacks = {
    'DIAGRAMA_ARQUITECTURA': architecture,
    'DIAGRAMA_ESTADOS': states,
    'TABLA_RUTAS_GET': routes_get,
    'TABLA_RUTAS_POST': routes_post,
    'CATALOGO_PHP': php_catalog,
    'INDICE_CSS': css_index,
    'INVENTARIO_ARCHIVOS': file_inventory,
    'VALIDACION_ACTUAL': validation_text,
}
js_count = 0
chapter = 0
lines = source_md.splitlines()
i = 0
while i < len(lines):
    line = lines[i]
    if not line.strip(): i += 1; continue
    if line.startswith('# '):
        chapter += 1
        if chapter > 1: page_break()
        paragraph(line[2:],'Heading1',bookmark=f'cap_{chapter}')
        i += 1; continue
    if line.startswith('## '):
        paragraph(line[3:],'Heading2'); i += 1; continue
    if line in callbacks:
        callbacks[line](); i += 1; continue
    if line == 'CATALOGO_JS':
        js_count = js_catalog(); i += 1; continue
    if line.startswith('```'):
        block = []; i += 1
        while i < len(lines) and not lines[i].startswith('```'):
            block.append(lines[i]); i += 1
        code(block); i += 1; continue
    if line.startswith('|'):
        headers = [c.strip() for c in line.strip('|').split('|')]
        rows = []; i += 1
        while i < len(lines) and lines[i].startswith('|'):
            row = [c.strip() for c in lines[i].strip('|').split('|')]
            if not all(re.fullmatch(r'[-: ]+',c) for c in row): rows.append(row)
            i += 1
        if any(len(r) != len(headers) for r in rows): raise ValueError('Tabla desigual: '+str(headers))
        table(headers,rows); continue
    block = [line]; i += 1
    if re.match(r'^\d+\. ',line):
        paragraph(line,'ListText'); continue
    while i < len(lines) and lines[i].strip() and not lines[i].startswith(('#','|','```')) and lines[i] not in callbacks and lines[i]!='CATALOGO_JS':
        block.append(lines[i]); i += 1
    paragraph(' '.join(block))

ns = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"'
sect = '<w:sectPr><w:headerReference w:type="default" r:id="rIdHeader"/><w:footerReference w:type="default" r:id="rIdFooter"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="567" w:footer="567" w:gutter="0"/><w:titlePg/></w:sectPr>'
document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' + f'<w:document {ns}><w:body>{"".join(body)}{sect}</w:body></w:document>'

style_defs = {
    'Normal': ('Normal',21,'23392B',False,None),
    'Heading1': ('Título 1',30,'0B5A2B',True,0),
    'Heading2': ('Título 2',25,'175F3B',True,1),
    'Small': ('Nota',18,'5C6F62',False,None),
    'TableText': ('Tabla',18,'23392B',False,None),
    'Code': ('Código',17,'16432B',False,None),
    'Caption': ('Leyenda',18,'5C6F62',False,None),
    'ListText': ('Paso',21,'23392B',False,None),
    'TOC1': ('Índice',22,'0B5A2B',False,None),
    'CoverTitle': ('Portada título',48,'0B5A2B',True,None),
    'CoverSubtitle': ('Portada subtítulo',27,'466653',False,None),
    'CoverProject': ('Portada proyecto',33,'0B5A2B',True,None),
    'CoverMeta': ('Portada referencia',20,'5C6F62',False,None),
    'CoverEyebrow': ('Portada institución',23,'0B5A2B',True,None),
}
styles = []
for key,(name,size,color,bold,level) in style_defs.items():
    props = '<w:widowControl/><w:spacing w:after="130" w:line="270" w:lineRule="auto"/>'
    if level is not None: props = f'<w:keepNext/><w:keepLines/><w:widowControl/><w:spacing w:before="240" w:after="120" w:line="270" w:lineRule="auto"/><w:outlineLvl w:val="{level}"/>'
    if key == 'TableText': props = '<w:spacing w:after="30" w:line="230" w:lineRule="auto"/>'
    if key == 'Code': props = '<w:keepLines/><w:shd w:fill="F0F5F2"/><w:spacing w:after="0" w:line="225" w:lineRule="auto"/><w:ind w:left="120" w:right="120"/>'
    if key.startswith('Cover'): props = '<w:spacing w:before="180" w:after="220"/>'
    rpr = f'<w:rFonts w:ascii="{"Consolas" if key=="Code" else "Calibri"}" w:hAnsi="{"Consolas" if key=="Code" else "Calibri"}"/>' + ('<w:b/>' if bold else '') + f'<w:color w:val="{color}"/><w:sz w:val="{size}"/><w:lang w:val="es-CO"/>'
    styles.append(f'<w:style w:type="paragraph" w:styleId="{key}"'+(' w:default="1"' if key=='Normal' else '')+f'><w:name w:val="{x(name)}"/>'+('' if key=='Normal' else '<w:basedOn w:val="Normal"/>')+f'<w:next w:val="Normal"/><w:qFormat/><w:pPr>{props}</w:pPr><w:rPr>{rpr}</w:rPr></w:style>')
styles_xml = f'<?xml version="1.0" encoding="UTF-8"?><w:styles {ns}><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="21"/><w:lang w:val="es-CO"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:widowControl/></w:pPr></w:pPrDefault></w:docDefaults>{"".join(styles)}</w:styles>'
header = f'<?xml version="1.0" encoding="UTF-8"?><w:hdr {ns}><w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:color="C8DED0"/></w:pBdr></w:pPr>{run("UGC  |  Manual de ugc_incapacidadess",size=17,color="466653")}</w:p></w:hdr>'
footer = f'''<?xml version="1.0" encoding="UTF-8"?><w:ftr {ns}><w:p><w:pPr><w:jc w:val="right"/></w:pPr>{run("Revisión 14/10/2026  ·  Página ",size=17,color="5C6F62")}<w:fldSimple w:instr="PAGE">{run("1",size=17)}</w:fldSimple></w:p></w:ftr>'''
rel_ns = 'http://schemas.openxmlformats.org/package/2006/relationships'
relationship_base = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/'
document_rels = f'<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="{rel_ns}">' + ''.join(f'<Relationship Id="{rid}" Type="{relationship_base}{typ}" Target="{target}"/>' for rid,typ,target in [('rIdStyles','styles','styles.xml'),('rIdSettings','settings','settings.xml'),('rIdHeader','header','header1.xml'),('rIdFooter','footer','footer1.xml'),('rIdLogo','image','media/logo.png')]) + '</Relationships>'
root_rels = f'<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="{rel_ns}"><Relationship Id="rIdDoc" Type="{relationship_base}officeDocument" Target="word/document.xml"/><Relationship Id="rIdCore" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rIdApp" Type="{relationship_base}extended-properties" Target="docProps/app.xml"/></Relationships>'
content_types = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/>' + ''.join(f'<Override PartName="/{part}" ContentType="{mime}"/>' for part,mime in [('word/document.xml','application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'),('word/styles.xml','application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml'),('word/settings.xml','application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml'),('word/header1.xml','application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml'),('word/footer1.xml','application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml'),('docProps/core.xml','application/vnd.openxmlformats-package.core-properties+xml'),('docProps/app.xml','application/vnd.openxmlformats-officedocument.extended-properties+xml')]) + '</Types>'
core = '''<?xml version="1.0" encoding="UTF-8"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Manual completo de ugc_incapacidadess</dc:title><dc:subject>Funcionamiento, configuración, diseño y mantenimiento</dc:subject><dc:creator>Documentación del proyecto UGC</dc:creator><dc:description>Revisión de fuentes locales f5e3944. Credenciales y tokens omitidos.</dc:description><dcterms:created xsi:type="dcterms:W3CDTF">2026-10-14T17:00:00Z</dcterms:created><dc:language>es-CO</dc:language></cp:coreProperties>'''
app = '<?xml version="1.0" encoding="UTF-8"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Generador de documentación OOXML</Application></Properties>'
settings = f'<?xml version="1.0" encoding="UTF-8"?><w:settings {ns}><w:zoom w:percent="100"/><w:defaultTabStop w:val="720"/><w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat><w:themeFontLang w:val="es-CO"/></w:settings>'
parts = {'[Content_Types].xml':content_types,'_rels/.rels':root_rels,'word/document.xml':document,'word/_rels/document.xml.rels':document_rels,'word/styles.xml':styles_xml,'word/settings.xml':settings,'word/header1.xml':header,'word/footer1.xml':footer,'word/media/logo.png':png,'docProps/core.xml':core,'docProps/app.xml':app}
for name,content in parts.items():
    if name.endswith(('.xml','.rels')): ET.fromstring(content)
OUT.parent.mkdir(exist_ok=True)
with ZipFile(OUT,'w',ZIP_DEFLATED) as z:
    for name,content in parts.items(): z.writestr(name,content)

# Comprobaciones de contenido y preservación del código.
expected = [p+':'+f['name']+':'+str(f['line']) for p,e in sources.items() for f in e['functions']]
assert len(source_symbols)==len(expected) and set(source_symbols)==set(expected)
assert len(bookmarks)==20 and len(chapters)==20
for entry in inventory:
    assert hashlib.sha256((ROOT/entry['path']).read_bytes()).hexdigest()==entry['sha256'], 'Fuente modificada '+entry['path']
plain = ''.join(ET.fromstring(document).itertext()).replace('\u200b','')
for marker in list(callbacks)+['CATALOGO_JS']: assert marker not in plain
server = (ROOT/'config/server.php').read_text(encoding='utf-8')
for m in re.finditer(r"define\(\s*'([^']*(?:PASS|PASSWORD|TOKEN)[^']*)'\s*,\s*'([^']*)'",server):
    assert m.group(2) not in plain, 'Secreto encontrado en documento: '+m.group(1)
with ZipFile(OUT) as z:
    assert z.testzip() is None
    for name in z.namelist():
        if name.endswith(('.xml','.rels')): ET.fromstring(z.read(name))
    for elem in ET.fromstring(z.read('word/_rels/document.xml.rels')):
        assert 'word/'+elem.attrib['Target'] in z.namelist()
stats = {'file':str(OUT),'size_bytes':OUT.stat().st_size,'chapters':len(chapters),'source_files':len(inventory),'php_files':lint['files'],'php_classes':sum(len(e['classes']) for e in inventory),'php_named_functions':len(source_symbols),'js_named_functions':js_count,'word_count_approx':len(re.findall(r'\S+',plain)),'tables':document.count('<w:tbl>'),'php_lint_errors':len(lint['failures']),'js_external_checks':js_checks['external_js'],'js_inline_checks':js_checks['inline_scripts'],'js_syntax_errors':sum(not c['ok'] for c in js_checks['checks']),'unchanged_sources':True,'zip_and_xml_valid':True,'secret_scan_passed':True,'rendered_in_word':False}
(BASE/'resultado.json').write_text(json.dumps(stats,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps(stats,ensure_ascii=False))
