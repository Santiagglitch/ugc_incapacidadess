'use strict';

// Pruebas HTTP del panel en una copia temporal. Sin SQLite, Oracle ni LDAP.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn, spawnSync } = require('node:child_process');
const { randomBytes } = require('node:crypto');

const root = path.resolve(__dirname, '../..');
const temporary = fs.mkdtempSync(path.join(os.tmpdir(), 'ugc-control-web-test-'));
const php = process.env.PHP_BINARY || 'php';
const password = randomBytes(20).toString('hex');
const key = randomBytes(32).toString('hex');
const cookies = new Map();
let server;
let serverLog = '';
let base;
let checks = 0;
function passed(name) { checks++; console.log('OK ' + checks + ': ' + name); }
function cli(args) {
  const result = spawnSync(php, ['-n', ...args], { cwd: temporary, encoding: 'utf8', windowsHide: true });
  assert.equal(result.status, 0, result.stderr || result.error?.message);
  const issue = result.stdout.match(/(?:Fatal error|Warning:)[^\r\n]*/);
  assert(!issue, issue?.[0]);
  return result.stdout;
}
async function request(route, body, authenticated = true) {
  const headers = {};
  if (authenticated && cookies.size) headers.Cookie = [...cookies].map(([name, value]) => name + '=' + value).join('; ');
  const response = await fetch(base + route, {
    method: body ? 'POST' : 'GET', body: body ? new URLSearchParams(body) : undefined,
    headers, redirect: 'manual', signal: AbortSignal.timeout(10000),
  });
  if (authenticated) {
    for (const cookie of response.headers.getSetCookie()) {
      const pair = cookie.split(';')[0];
      const separator = pair.indexOf('=');
      cookies.set(pair.slice(0, separator), pair.slice(separator + 1));
    }
  }
  const html = await response.text();
  const issue = html.match(/(?:Fatal error|<b>Warning<\/b>)[^\r\n]*/);
  assert(!issue, issue?.[0] || 'PHP devolvio un error de ejecucion');
  return { status: response.status, html };
}
function fields(html) {
  const result = {};
  for (const input of html.matchAll(/<input\b[^>]*>/g)) {
    const attr = Object.fromEntries([...input[0].matchAll(/([\w[\]-]+)="([^"]*)"/g)].map((match) => [match[1], match[2]]));
    if (attr.name && ['hidden', 'checkbox'].includes(attr.type)) result[attr.name] = attr['data-original'] ?? attr.value;
  }
  return result;
}
async function panel() {
  const response = await request('/control-incapacidades/index.php');
  assert.equal(response.status, 200);
  assert(response.html.includes('data-control-form'));
  return response.html;
}
async function save(changes, original, secret = password, confirmed = true) {
  const page = original || await panel();
  const body = fields(page);
  for (const [name, value] of Object.entries(changes)) body['flags[' + name + ']'] = value ? '1' : '0';
  body.control_action = 'update';
  body.reauth_password = secret;
  body.confirm_critical = confirmed ? '1' : '0';
  assert.equal((await request('/control-incapacidades/index.php', body)).status, 303);
  return panel();
}
function value(page, name) { return fields(page)['flags[' + name + ']'] === '1'; }
function assertSilentBlock(response) {
  assert.equal(response.status, 503);
  assert.equal(response.html.replace(/<[^>]*>/g, '').trim(), '', 'El bloqueo muestra texto al usuario');
  assert(!/<(?:script|link|img)\b/i.test(response.html), 'El bloqueo carga contenido del portal');
}

(async () => {
  try {
    for (const directory of ['config', 'controllers', 'models', 'services', 'views', 'Export', 'control-incapacidades', 'tests/ControlPanel']) {
      fs.cpSync(path.join(root, directory), path.join(temporary, directory), {
        recursive: true, filter: (source) => path.basename(source) !== 'server.php',
      });
    }
    fs.copyFileSync(path.join(root, 'index.php'), path.join(temporary, 'index.php'));
    fs.mkdirSync(path.join(temporary, 'storage/sessions'), { recursive: true });
    fs.mkdirSync(path.join(temporary, 'storage/private/control-panel'), { recursive: true });
    const keyPath = path.join(temporary, 'storage/private/control-panel/instalacion.key');
    const statePath = path.join(temporary, 'storage/private/control-panel/estado.json');
    fs.writeFileSync(path.join(temporary, 'config/server.php'), [
      '<?php',
      "define('APP_NAME', 'Prueba aislada');",
      "define('APP_ENV', 'development');",
      "define('BASE_URL', '');",
      "define('APP_TIMEZONE', 'America/Bogota');",
      "define('SESSION_SAVE_PATH', dirname(__DIR__) . '/storage/sessions');",
      "define('SESSION_IDLE_TIMEOUT', 1800);",
    ].join('\n'));
    const listener = net.createServer();
    await new Promise((resolve) => listener.listen(0, '127.0.0.1', resolve));
    const port = listener.address().port;
    await new Promise((resolve) => listener.close(resolve));
    base = 'http://127.0.0.1:' + port;
    server = spawn(php, ['-n', '-d', 'session.save_path="' + path.join(temporary, 'storage/sessions').replaceAll('\\', '/') + '"',
      '-S', '127.0.0.1:' + port, '-t', temporary], { cwd: temporary, stdio: ['ignore', 'ignore', 'pipe'], windowsHide: true });
    server.stderr.on('data', (chunk) => { serverLog = (serverLog + chunk.toString()).slice(-4000); });
    let ready = false;
    let startupError = '';
    for (let attempt = 0; attempt < 60; attempt++) {
      try { await request('/control-incapacidades/'); ready = true; break; } catch (error) { startupError = error.message; }
      await new Promise((resolve) => setTimeout(resolve, 100));
    }
    assert(ready, 'El servidor PHP temporal no inicio: ' + startupError + '\n' + serverLog.slice(-1200));
    let response = await request('/control-incapacidades/');
    assert(!response.html.includes('name="username"'));
    assert.equal((await request('/control-incapacidades/index.php', { control_action: 'update' }, false)).status, 401);
    fs.writeFileSync(keyPath, key);
    response = await request('/control-incapacidades/');
    assert(response.html.includes('name="setup_key"'));
    assert.equal((await request('/control-incapacidades/index.php', { control_action: 'setup', username: 'control_test', password, password_confirmation: password, setup_key: key })).status, 403);
    response = await request('/control-incapacidades/index.php', {
      control_action: 'setup', username: 'control_test', password, password_confirmation: password,
      setup_key: 'incorrecta', csrf_token: fields(response.html).csrf_token,
    });
    assert(response.html.includes('llave de instalacion no es valida'));
    passed('La configuracion inicial exige llave de instalacion y CSRF');

    response = await request('/control-incapacidades/index.php', {
      control_action: 'setup', username: 'control_test', password, password_confirmation: password,
      setup_key: key, csrf_token: fields(response.html).csrf_token,
    });
    assert.equal(response.status, 303);
    assert(!fs.existsSync(keyPath));
    assert(!fs.readFileSync(statePath, 'utf8').includes(password));
    let page = await panel();
    assert.equal(Object.keys(fields(page)).filter((name) => name.startsWith('flags[')).length, 12);
    assert.equal(JSON.parse(cli(['tests/ControlPanel/fixture.php', 'values'])).sqlite, false);
    passed('El navegador crea el acceso, consume la llave y muestra 12 interruptores sin SQLite');

    response = await request('/control-incapacidades/index.php', {
      control_action: 'setup', username: 'otro_usuario', password, password_confirmation: password,
      setup_key: key, csrf_token: fields(page).csrf_token,
    });
    assert(response.html.includes('acceso ya fue configurado'));
    assert.equal(JSON.parse(fs.readFileSync(statePath, 'utf8')).account.username, 'control_test');
    passed('La llave no permite reemplazar una cuenta existente');

    page = await save({ LOGIN_HABILITADO: false }, undefined, 'incorrecta');
    assert(value(page, 'LOGIN_HABILITADO'));
    page = await save({ LOGIN_HABILITADO: false }, page, password, false);
    assert(value(page, 'LOGIN_HABILITADO'));
    assert.equal((await request('/control-incapacidades/index.php', {
      ...fields(page), control_action: 'update', csrf_token: 'invalido',
    })).status, 403);
    const incomplete = fields(page);
    delete incomplete['flags[LOGIN_HABILITADO]'];
    assert.equal((await request('/control-incapacidades/index.php', incomplete)).status, 303);
    assert(value(await panel(), 'LOGIN_HABILITADO'));
    passed('No guarda sin contrasena, confirmacion, CSRF o formulario completo');

    page = await save({ LOGIN_HABILITADO: false });
    assertSilentBlock(await request('/index.php?view=login&pruebas=1'));
    assertSilentBlock(await request('/index.php', { action: 'login', cedula: 'fixture', password: 'fixture' }));
    assert.equal((await request('/control-incapacidades/')).status, 200);
    const sourcePath = path.join(temporary, 'config/funcionalidades.php');
    const source = fs.readFileSync(sourcePath, 'utf8');
    assert(!source.includes('const APP_FUNCIONALIDADES'));
    fs.appendFileSync(sourcePath, "\nconst APP_FUNCIONALIDADES = ['LOGIN_HABILITADO' => true];\n");
    assert.equal((await request('/index.php?view=login&pruebas=1')).status, 503);
    fs.writeFileSync(sourcePath, source);
    assert.equal((await request('/index.php?view=login&pruebas=1')).status, 503);
    page = await save({ LOGIN_HABILITADO: true });
    passed('El login se controla por la pagina y los valores antiguos del codigo no lo sobrescriben');

    page = await save({ VACACIONES_ACTIVAS: false });
    assert((await request('/')).html.includes('name="cedula"'));
    page = await save({ VACACIONES_ACTIVAS: true });
    assert((await request('/')).html.includes('Estamos de vacaciones'));
    assert((await request('/index.php?view=login&pruebas=1')).html.includes('name="cedula"'));
    page = await save({ USUARIO_PRUEBA_HABILITADO: false });
    assert(!(await request('/index.php?view=login&pruebas=1')).html.includes('name="cedula"'));
    page = await save({ USUARIO_PRUEBA_HABILITADO: true });
    passed('Vacaciones y acceso de prueba responden al estado guardado desde la pagina');

    for (const [name, views, action] of [
      ['SOLICITUDES_CREAR', ['solicitud_crear'], 'solicitud_create'],
      ['SOLICITUDES_EDITAR', ['solicitud_editar'], 'solicitud_update'],
      ['SOLICITUDES_ELIMINAR', [], 'solicitud_delete'],
      ['SOLICITUDES_CANCELAR', [], 'solicitud_cancelar_agenda'],
      ['GESTION_JEFES', [], 'solicitud_jefe'],
      ['GESTION_RRHH', [], 'solicitud_rrhh'],
      ['EXPORTACIONES', ['admin_empleados_export', 'superadmin_auditoria_export'], null],
    ]) {
      await save({ [name]: false });
      for (const view of views) assertSilentBlock(await request('/index.php?view=' + view));
      if (action) assertSilentBlock(await request('/index.php', { action, id: '1' }));
      if (name === 'EXPORTACIONES') {
        for (const role of ['Admin', 'Jefe', 'RRHH']) assertSilentBlock(await request('/Export/' + role + '/ExportController.php?download=1'));
      }
      await save({ [name]: true });
      for (const view of views) assert.notEqual((await request('/index.php?view=' + view)).status, 503);
      if (action) assert.notEqual((await request('/index.php', { action, id: '1' })).status, 503);
      passed(name + ': la pagina permite apagar y reactivar sus rutas');
    }

    await save({ NOTIFICACIONES: false });
    assert.deepEqual(JSON.parse((await request('/index.php?view=notif_count')).html), { contador: 0 });
    assert.deepEqual(JSON.parse((await request('/index.php?view=notif_list')).html), { notificaciones: [] });
    assert.deepEqual(JSON.parse((await request('/index.php', { action: 'notif_read_all' })).html), { success: false });
    await save({ NOTIFICACIONES: true });
    assert.notEqual((await request('/index.php?view=notif_list')).status, 200);
    passed('El control de notificaciones pausa y restaura sus rutas');

    await save({ SELECCION_JEFES: false });
    for (const form of ['create', 'edit']) {
      const html = cli(['tests/ControlPanel/fixture.php', 'form', form]);
      assert(!html.includes('<select name="nit_jefe_seleccionado"'));
      assert(html.includes('readonly'));
      assert(!/seleccion de jefes esta deshabilitada|Se conservara el jefe|No tienes un jefe asignado/.test(html));
    }
    const withoutJefe = cli(['tests/ControlPanel/fixture.php', 'form', 'create', 'no-jefe']);
    assert(!/Sin jefe asignado|No tienes un jefe asignado|seleccion de jefes esta deshabilitada/.test(withoutJefe));
    await save({ SELECCION_JEFES: true });
    for (const form of ['create', 'edit']) {
      assert(cli(['tests/ControlPanel/fixture.php', 'form', form]).includes('<select name="nit_jefe_seleccionado"'));
    }
    passed('Seleccionar jefes se apaga y restaura desde la pagina en ambos formularios');

    const stale = await panel();
    page = await save({ EXPORTACIONES: false }, stale);
    page = await save({ SOLICITUDES_CREAR: false }, stale);
    assert(page.includes('cambiaron en otra sesion'));
    assert(value(page, 'SOLICITUDES_CREAR'));
    assert(!value(page, 'EXPORTACIONES'));
    assert(page.includes('Ultimos cambios'));
    await save({ EXPORTACIONES: true });
    passed('Los cambios simultaneos no se sobrescriben y quedan en el historial');

    if (process.argv.includes('--browser')) {
      await require('./browser.cjs')({ base, password, cookies, temporary, root });
      passed('Interruptores y guardado verificados en Edge para escritorio y movil');
    }

    page = await panel();
    assert.equal((await request('/control-incapacidades/index.php', {
      control_action: 'logout', csrf_token: fields(page).csrf_token,
    })).status, 303);
    assert(!(await request('/control-incapacidades/')).html.includes('data-control-form'));
    let login = await request('/control-incapacidades/');
    assert(!login.html.includes('name="setup_key"'));
    response = await request('/control-incapacidades/index.php', {
      control_action: 'login', username: 'control_test', password, csrf_token: fields(login.html).csrf_token,
    });
    assert.equal(response.status, 303);
    assert((await panel()).includes('data-control-form'));
    passed('La cuenta creada vuelve a ingresar y cerrar sesion revoca el acceso');

    assert.equal((await request('/control-incapacidades/index.php', {
      control_action: 'logout', csrf_token: fields(await panel()).csrf_token,
    })).status, 303);
    for (let attempt = 0; attempt < 5; attempt++) {
      login = await request('/control-incapacidades/');
      await request('/control-incapacidades/index.php', {
        control_action: 'login', username: 'wrong_' + attempt, password: 'incorrecta', csrf_token: fields(login.html).csrf_token,
      });
    }
    login = await request('/control-incapacidades/');
    assert.equal((await request('/control-incapacidades/index.php', {
      control_action: 'login', username: 'nuevo_usuario', password: 'incorrecta', csrf_token: fields(login.html).csrf_token,
    })).status, 429);
    passed('Cinco intentos fallidos bloquean el acceso aunque cambie el usuario');

    const healthy = fs.readFileSync(statePath, 'utf8');
    fs.writeFileSync(statePath, '{invalido');
    assert.equal((await request('/')).status, 503);
    assert.equal((await request('/control-incapacidades/')).status, 503);
    fs.writeFileSync(statePath, healthy);
    assert((await request('/')).html.includes('Estamos de vacaciones'));
    passed('Un error de almacenamiento no activa controles ni restaura valores del codigo');

    console.log('Completadas ' + checks + ' comprobaciones. Estados reales intactos; sin consultas Oracle/LDAP.');
  } finally {
    if (server && server.exitCode === null) {
      const stopped = new Promise((resolve) => server.once('exit', resolve));
      server.kill();
      await stopped;
    }
    const tempRoot = path.resolve(os.tmpdir()) + path.sep;
    assert(path.resolve(temporary).startsWith(tempRoot) && path.basename(temporary).startsWith('ugc-control-web-test-'));
    fs.rmSync(temporary, { recursive: true, force: true });
  }
})().catch((error) => { console.error(error); process.exitCode = 1; });
