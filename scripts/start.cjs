const fs=require('node:fs');
const path=require('node:path');
const net=require('node:net');
const crypto=require('node:crypto');
const {spawn,spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'..');
process.chdir(root);
const php=process.env.IPT_PHP || 'C:/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe';
const mysql=process.env.IPT_MYSQL || 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqld.exe';
const runtime=path.join(root,'.runtime');fs.mkdirSync(runtime,{recursive:true});
function listening(port){return new Promise(resolve=>{const s=net.createConnection({host:'127.0.0.1',port});s.once('connect',()=>{s.destroy();resolve(true);});s.once('error',()=>resolve(false));s.setTimeout(1000,()=>{s.destroy();resolve(false);});});}
function run(exe,args){const r=spawnSync(exe,args,{cwd:root,stdio:'inherit',windowsHide:true});if(r.error)throw r.error;if(r.status!==0)throw new Error('Perintah gagal: '+path.basename(exe));}
function start(exe,args,name){const out=fs.openSync(path.join(runtime,name+'-out.log'),'a'),err=fs.openSync(path.join(runtime,name+'-err.log'),'a');const p=spawn(exe,args,{cwd:root,detached:true,windowsHide:true,stdio:['ignore',out,err]});p.on('error',e=>console.error(e.message));p.unref();fs.closeSync(out);fs.closeSync(err);return p;}
async function waitFor(port){for(let i=0;i<40;i++){if(await listening(port))return;await new Promise(r=>setTimeout(r,400));}throw new Error('Server pada port '+port+' tidak siap. Periksa .runtime/*-err.log.');}
(async()=>{
 if(!fs.existsSync(php))throw new Error('PHP tidak ditemukan. Atur variabel IPT_PHP ke lokasi php.exe.');
 const envFile=path.join(root,'backend/.env');
 if(!fs.existsSync(envFile))fs.writeFileSync(envFile,['DB_HOST=127.0.0.1','DB_PORT=3307','DB_DATABASE=ipt_store','DB_USERNAME=root','DB_PASSWORD=','JWT_SECRET='+crypto.randomBytes(48).toString('base64'),'DATA_KEY='+crypto.randomBytes(48).toString('base64'),'APP_ORIGINS=http://localhost:8081,http://localhost:8000,http://127.0.0.1:8000',''].join('\n'));
 const env=Object.fromEntries(fs.readFileSync(envFile,'utf8').split(/\r?\n/).filter(l=>l.includes('=')&&!l.startsWith('#')).map(l=>{const i=l.indexOf('=');return [l.slice(0,i).trim(),l.slice(i+1).trim()];}));
 const port=Number(env.DB_PORT||3306);
 if(!await listening(port)){
  if(port!==3307||(env.DB_HOST&&env.DB_HOST!=='127.0.0.1'))throw new Error('Nyalakan MySQL Laragon sesuai backend/.env terlebih dahulu.');
  if(!fs.existsSync(mysql))throw new Error('MySQL tidak ditemukan. Atur IPT_MYSQL ke lokasi mysqld.exe.');
  const datadir=path.join(runtime,'mysql'),base=path.resolve(path.dirname(mysql),'..');
  if(!fs.existsSync(path.join(datadir,'mysql'))){fs.mkdirSync(datadir,{recursive:true});run(mysql,['--no-defaults','--initialize-insecure','--basedir='+base,'--datadir='+datadir,'--console']);}
  start(mysql,['--no-defaults','--basedir='+base,'--datadir='+datadir,'--port=3307','--bind-address=127.0.0.1','--mysqlx=OFF','--console'],'mysql');await waitFor(3307);
 }
 run(php,['backend/database/setup.php']);
 if(!fs.existsSync(path.join(root,'mobile/dist/index.html')))run(process.execPath,['mobile/scripts/build-web.cjs']);
 if(!await listening(8000)){start(php,['-S','0.0.0.0:8000','-t','backend/public','backend/router.php'],'php');await waitFor(8000);}
 const response=await fetch('http://127.0.0.1:8000/api/health');const result=await response.json();if(!result.success)throw new Error('API belum siap. Periksa backend/.env.');
 console.log('\nIPT Store siap: http://localhost:8000\nPanduan akun admin: README.md\n');
})().catch(e=>{console.error('IPT Store: '+e.message);process.exitCode=1;});
