const {spawn}=require('node:child_process');const path=require('node:path');
const ip=process.argv[2];
if(!ip||!/^\d{1,3}(\.\d{1,3}){3}$/.test(ip)){console.error('Pemakaian: Start-Mobile.cmd 192.168.1.10');process.exit(1);}
const root=path.resolve(__dirname,'../mobile');
const child=spawn(process.execPath,[path.join(root,'node_modules/expo/bin/cli'),'start','--lan'],{cwd:root,stdio:'inherit',env:{...process.env,EXPO_PUBLIC_API_URL:`http://${ip}:8000/api`}});
child.on('error',e=>{console.error(e.message);process.exitCode=1;});child.on('exit',code=>{process.exitCode=code||0;});
