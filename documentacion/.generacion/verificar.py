from pathlib import Path
import json, re, subprocess

ROOT = Path(__file__).resolve().parents[2]
BASE = Path(__file__).resolve().parent
data = json.loads((BASE / 'inventario.json').read_text(encoding='utf-8'))
node = r'C:\Program Files\nodejs\node.exe'
scratch = BASE / 'js_sintaxis'
scratch.mkdir(exist_ok=True)
checks = []
for item in data:
    path = item['path']
    text = (ROOT / path).read_text(encoding='utf-8', errors='replace') if path.endswith(('.php', '.js')) else ''
    if path.endswith('.js'):
        variants = [(path, ROOT / path)]
    elif path.endswith('.php'):
        variants = []
        template = re.sub(r'<\?(?:php|=).*?\?>', lambda m: '0' + '\n' * m.group(0).count('\n'), text, flags=re.S)
        for i, match in enumerate(re.finditer(r'<script\b[^>]*>(.*?)</script>', template, re.S | re.I)):
            body = match.group(1)
            if not body.strip(): continue
            # Solo comprueba sintaxis del template: no ejecuta PHP ni JavaScript.
            body = re.sub(r'<\?(?:php|=).*?\?>', '0', body, flags=re.S)
            target = scratch / (path.replace('/', '_') + f'_{i}.js')
            target.write_text(body, encoding='utf-8')
            variants.append((path + f':script{i+1}', target))
    else:
        continue
    for label, target in variants:
        result = subprocess.run([node, '--check', str(target)], capture_output=True, text=True, encoding='utf-8', errors='replace')
        checks.append({'source': label, 'ok': result.returncode == 0, 'detail': result.stderr.strip() if result.returncode else ''})

report = {'external_js': sum(c['source'].endswith('.js') for c in checks), 'inline_scripts': sum(':script' in c['source'] for c in checks), 'checks': checks, 'limitations': 'Sintaxis de JavaScript externo y scripts de templates con expresiones PHP reemplazadas por placeholders; no prueba DOM, CSP ni comportamiento en navegador.'}
(BASE / 'js_validacion.json').write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'external_js': report['external_js'], 'inline_scripts': report['inline_scripts'], 'failures': [c for c in checks if not c['ok']]}, ensure_ascii=False))
