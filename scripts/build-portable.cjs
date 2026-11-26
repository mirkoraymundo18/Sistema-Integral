// Offline fallback for this application's simple JS entry point.
// Standard builds continue to use Vite inside Docker.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {spawnSync} = require('node:child_process');
const root = path.resolve(__dirname,'..');
const read = name => fs.readFileSync(path.join(root,name),'utf8');
const modules=[];
let alpine=false;
for(const raw of read('resources/js/app.js').split(/\r?\n/)) {
    const line=raw.trim();
    if(!line) continue;
    const relative=line.match(/^import ['"](\.\/[^'"]+)['"];$/);
    if(relative) {
        const filename=path.resolve(root,'resources/js',relative[1]+'.js');
        if(!filename.startsWith(path.resolve(root,'resources/js')+path.sep)) throw new Error('Import fuera de resources/js.');
        const source=fs.readFileSync(filename,'utf8');
        if(/^\s*(?:import|export)\b/m.test(source)) throw new Error('Usa Vite para módulos con imports o exports internos.');
        modules.push('(()=>{\n'+source+'\n})();');
    } else if(/^import Alpine from ['"]alpinejs['"];$/.test(line)) alpine=true;
    else if(!['window.Alpine = Alpine;','Alpine.start();'].includes(line)) throw new Error('El punto de entrada cambió. Usa la compilación normal con Vite.');
}
if(!alpine) throw new Error('No se encontró el módulo Alpine esperado.');
const output=path.join(root,'public/build');
fs.mkdirSync(path.join(output,'assets'),{recursive:true});
const staging=path.join(root,'storage/framework/portable-build.css');
const build=spawnSync(process.execPath,[path.join(root,'node_modules/tailwindcss/lib/cli.js'),'-i','resources/css/app.css','-o',staging,'--minify'],{cwd:root,stdio:'inherit',windowsHide:true});
if(build.status!==0) process.exit(build.status||1);
// Alpine's installed CDN bundle is self-contained and starts once after these handlers are registered.
const javascript='/* Local offline bundle; source: resources/js and installed Alpine.js. */\n'+modules.join('\n')+'\n'+read('node_modules/alpinejs/dist/cdn.min.js')+'\n';
new (require('node:vm').Script)(javascript,{filename:'app.js'});
const css=fs.readFileSync(staging);
const hash=data=>crypto.createHash('sha256').update(data).digest('hex').slice(0,12);
const jsFile='assets/app-'+hash(javascript)+'.js';
const cssFile='assets/app-'+hash(css)+'.css';
fs.writeFileSync(path.join(output,jsFile),javascript);
fs.writeFileSync(path.join(output,cssFile),css);
const manifest={
    'resources/css/app.css':{file:cssFile,name:'app',src:'resources/css/app.css',isEntry:true},
    'resources/js/app.js':{file:jsFile,name:'app',src:'resources/js/app.js',isEntry:true}
};
fs.writeFileSync(path.join(output,'manifest.json.tmp'),JSON.stringify(manifest,null,2)+'\n');
fs.renameSync(path.join(output,'manifest.json.tmp'),path.join(output,'manifest.json'));
fs.unlinkSync(staging);
console.log('Assets portables generados y manifest actualizado.');
