<?php
declare(strict_types=1);
// Inspeccion lexica: no carga ni ejecuta archivos de la aplicacion.
$root = dirname(__DIR__, 2);
$paths = preg_split('/\R/', trim((string)shell_exec('git -C ' . escapeshellarg($root) . ' ls-files')));
$result = [];
foreach ($paths as $relative) {
    $path = $root . '/' . $relative;
    if (!is_file($path)) continue;
    $source = file_get_contents($path);
    $entry = ['path'=>$relative, 'bytes'=>filesize($path), 'lines'=>substr_count($source, "\n") + 1, 'sha256'=>hash('sha256',$source), 'classes'=>[], 'functions'=>[]];
    if (pathinfo($relative, PATHINFO_EXTENSION) === 'php') {
        $tokens = token_get_all($source);
        $namespace = ''; $class = ''; $lastDoc = ''; $n = count($tokens);
        for ($i=0; $i<$n; $i++) {
            $t = $tokens[$i];
            if (!is_array($t)) continue;
            if ($t[0] === T_DOC_COMMENT) $lastDoc = $t[1];
            if ($t[0] === T_NAMESPACE) {
                $namespace='';
                for ($j=$i+1; $j<$n && $tokens[$j]!==';' && $tokens[$j]!=='{'; $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0],[T_STRING,T_NAME_QUALIFIED,T_NS_SEPARATOR],true)) $namespace.=$tokens[$j][1];
                }
            }
            if ($t[0] === T_CLASS) {
                for ($j=$i+1; $j<$n; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0]===T_WHITESPACE) continue;
                    if (is_array($tokens[$j]) && $tokens[$j][0]===T_STRING) {
                        $class=$tokens[$j][1];
                        $entry['classes'][]=['name'=>($namespace!==''?$namespace.'\\':'').$class,'line'=>$t[2]];
                    }
                    break;
                }
            }
            if ($t[0] !== T_FUNCTION) continue;
            $name='';
            for ($j=$i+1; $j<$n; $j++) {
                $v=$tokens[$j];
                if (is_array($v) && ($v[0]===T_WHITESPACE || in_array($v[0],[T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG],true))) continue;
                if (is_array($v) && $v[0]===T_STRING) $name=$v[1];
                break;
            }
            if ($name==='') continue;
            $visibility=''; $static=false;
            for ($k=$i-1; $k>=0; $k--) {
                $v=$tokens[$k];
                if (!is_array($v)) break;
                if ($v[0]===T_WHITESPACE) continue;
                if (in_array($v[0],[T_PUBLIC,T_PROTECTED,T_PRIVATE],true)) $visibility=$v[1];
                elseif ($v[0]===T_STATIC) $static=true;
                elseif ($v[0]!==T_FINAL && $v[0]!==T_ABSTRACT) break;
            }
            $sig='';
            for ($j=$i; $j<$n; $j++) {
                $v=$tokens[$j]; if ($v==='{' || $v===';') break;
                $sig.=is_array($v)?$v[1]:$v;
            }
            $sig=trim(preg_replace('/\s+/', ' ', $sig));
            $entry['functions'][]=['name'=>$name,'line'=>$t[2],'class'=>$class,'namespace'=>$namespace,'visibility'=>$visibility,'static'=>$static,'signature'=>$sig];
        }
    }
    $result[]=$entry;
}
file_put_contents(__DIR__.'/inventario.json', json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo json_encode(['files'=>count($result),'php_files'=>count(array_filter($result,fn($e)=>str_ends_with($e['path'],'.php'))),'classes'=>array_sum(array_map(fn($e)=>count($e['classes']),$result)),'functions'=>array_sum(array_map(fn($e)=>count($e['functions']),$result))],JSON_UNESCAPED_SLASHES),PHP_EOL;
