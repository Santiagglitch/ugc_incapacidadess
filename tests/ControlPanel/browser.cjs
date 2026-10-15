'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawn } = require('node:child_process');

module.exports = async ({ base, password, cookies, temporary, root }) => {
  assert.equal(typeof WebSocket, 'function', 'Usa node --experimental-websocket para revisar en Edge');
  const edge = process.env.EDGE_BINARY || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
  assert(fs.existsSync(edge), 'Configura EDGE_BINARY con la ruta de Edge');
  const profile = path.join(temporary, 'edge-profile');
  const browser = spawn(edge, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
    '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], { stdio: 'ignore', windowsHide: true });
  let socket;
  let sequence = 0;
  const waiting = new Map();
  async function until(probe, message) {
    for (let attempt = 0; attempt < 100; attempt++) {
      try { const result = await probe(); if (result) return result; } catch {}
      await new Promise((resolve) => setTimeout(resolve, 100));
    }
    throw new Error(message);
  }
  try {
    const debugPort = await until(() => {
      const file = path.join(profile, 'DevToolsActivePort');
      return fs.existsSync(file) && fs.readFileSync(file, 'utf8').split('\n')[0];
    }, 'Edge no abrio el puerto de depuracion');
    const tabs = await (await fetch(`http://127.0.0.1:${debugPort}/json/list`)).json();
    socket = new WebSocket(tabs.find((tab) => tab.type === 'page').webSocketDebuggerUrl);
    await new Promise((resolve, reject) => {
      socket.addEventListener('open', resolve, { once: true });
      socket.addEventListener('error', reject, { once: true });
    });
    socket.addEventListener('message', (event) => {
      const response = JSON.parse(event.data);
      if (!waiting.has(response.id)) return;
      const { resolve, reject, timeout } = waiting.get(response.id);
      clearTimeout(timeout);
      waiting.delete(response.id);
      if (response.error) reject(new Error(response.error.message)); else resolve(response.result);
    });
    function command(method, params = {}) {
      return new Promise((resolve, reject) => {
        const id = ++sequence;
        const timeout = setTimeout(() => { waiting.delete(id); reject(new Error('Edge no respondio: ' + method)); }, 10000);
        waiting.set(id, { resolve, reject, timeout });
        socket.send(JSON.stringify({ id, method, params }));
      });
    }
    async function evaluate(expression) {
      const result = await command('Runtime.evaluate', { expression, returnByValue: true });
      if (result.exceptionDetails) throw new Error('Fallo la comprobacion JavaScript del navegador');
      return result.result.value;
    }
    await command('Page.enable');
    await command('Network.enable');
    await command('Network.setCookie', { name: 'UGC_CONTROL', value: cookies.get('UGC_CONTROL'), url: base + '/control-incapacidades/', path: '/control-incapacidades/' });
    await command('Emulation.setDeviceMetricsOverride', { width: 1365, height: 900, deviceScaleFactor: 1, mobile: false });
    await command('Page.navigate', { url: base + '/control-incapacidades/' });
    await until(() => evaluate("document.readyState === 'complete' && !!document.querySelector('[data-control-form]')"), 'El panel no termino de cargar');
    assert.equal(await evaluate("document.querySelectorAll('input[data-original]').length"), 12);
    assert.equal(await evaluate("getComputedStyle(document.querySelector('[data-reauth]')).display"), 'none');
    const previews = path.join(root, 'storage/private/control-panel-preview');
    fs.mkdirSync(previews, { recursive: true });
    async function screenshot(name) {
      const capture = await command('Page.captureScreenshot', { captureBeyondViewport: true, fromSurface: true });
      fs.writeFileSync(path.join(previews, name), Buffer.from(capture.data, 'base64'));
    }
    await screenshot('desktop.png');
    await evaluate("document.querySelector('label[for=\"flag-SOLICITUDES_CREAR\"]').click()");
    assert.equal(await evaluate("getComputedStyle(document.querySelector('[data-reauth]')).display"), 'block');
    assert.equal(await evaluate("document.querySelector('[data-reauth-password]').required"), true);
    await screenshot('confirmacion.png');
    for (const expected of ['0', '1']) {
      if (expected === '1') await evaluate("document.querySelector('label[for=\"flag-SOLICITUDES_CREAR\"]').click()");
      await evaluate(`document.querySelector('[data-confirm]').checked = true; document.querySelector('[data-reauth-password]').value = ${JSON.stringify(password)}; document.querySelector('[data-control-form]').requestSubmit()`);
      await until(() => evaluate(`document.readyState === 'complete' && document.getElementById('flag-SOLICITUDES_CREAR')?.dataset.original === '${expected}' && !!document.querySelector('.cp-alert-success')`), 'El cambio del navegador no se guardo');
      if (expected === '0') {
        await command('Page.navigate', { url: base + '/index.php?view=solicitud_crear' });
        await until(() => evaluate("document.readyState === 'complete' && location.search === '?view=solicitud_crear'"), 'La pagina bloqueada no termino de cargar');
        assert.equal(await evaluate('document.body.innerText.trim()'), '', 'El navegador muestra un aviso de bloqueo');
        await screenshot('bloqueo-sin-mensaje.png');
        await command('Page.navigate', { url: base + '/control-incapacidades/' });
        await until(() => evaluate("document.readyState === 'complete' && document.getElementById('flag-SOLICITUDES_CREAR')?.dataset.original === '0'"), 'No se pudo volver al panel');
      }
    }
    await command('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    await new Promise((resolve) => setTimeout(resolve, 200));
    assert.equal(await evaluate('document.documentElement.scrollWidth <= window.innerWidth + 1'), true, 'El panel se desborda en movil');
    await screenshot('movil.png');
    await command('Browser.close');
  } finally {
    if (socket) socket.close();
    for (const pending of waiting.values()) clearTimeout(pending.timeout);
    if (browser.exitCode === null) {
      const stopped = new Promise((resolve) => browser.once('exit', resolve));
      browser.kill();
      await stopped;
    }
  }
};
