const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const ts=require('typescript');
const root=path.resolve(__dirname,'..');
const out=path.resolve(root,'../.runtime/i18n-check');
for(const file of ['i18n/core.ts','i18n/translations.ts','i18n/translations-extra.ts','i18n/messages.ts','i18n/campaigns.ts','utils/format.ts']){
 const target=path.join(out,file.replace(/\.ts$/,'.js'));fs.mkdirSync(path.dirname(target),{recursive:true});
 const result=ts.transpileModule(fs.readFileSync(path.join(root,'src',file),'utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022}});
 fs.writeFileSync(target,result.outputText);
}
const {languages,isLocale,translate,interpolate,translatedSources,setCurrentLocale,getLocaleTag}=require(path.join(out,'i18n/core.js'));
const {date,rupiah}=require(path.join(out,'utils/format.js'));
assert.deepEqual(languages.map(l=>l.code),['id','en','zh-HK','ko','zh-CN']);
assert.equal(isLocale('zh-HK'),true);assert.equal(isLocale('fr'),false);assert.equal(isLocale(null),false);
for(const entry of translatedSources()){
 const tokens=s=>(s.match(/\{\w+\}/g)||[]).sort();
 for(let i=1;i<5;i++){
  assert.ok(entry[i]?.trim(),'Missing translation: '+entry[0]+' / '+languages[i].code);
  assert.deepEqual(tokens(entry[i]),tokens(entry[0]),'Interpolation mismatch: '+entry[0]);
 }
}
const expected={en:'Home','zh-HK':'主頁',ko:'홈','zh-CN':'首页'};
for(const [locale,label] of Object.entries(expected)){
 assert.equal(translate('Beranda',locale),label);
 assert.notEqual(translate('Email atau password salah.',locale),'Email atau password salah.');
 assert.notEqual(translate('Ringkasan pesanan',locale),'Ringkasan pesanan');
 assert.notEqual(translate('Pengaturan toko',locale),'Pengaturan toko');
 assert.ok(translate('Selamat datang, Hapus!',locale).includes('Hapus'),'Name must be preserved');
 assert.ok(translate('Lihat Instax SQ40',locale).includes('Instax SQ40'));
 assert.ok(translate('Stok Instax SQ40 tidak mencukupi.',locale).includes('Instax SQ40'));
 assert.ok(interpolate('{count} produk siap menemani momenmu.',{count:12},locale).includes('12'));
 assert.equal(translate('192.168.1.37',locale),'192.168.1.37');
 assert.equal(translate('IPT-20261112-ABC123',locale),'IPT-20261112-ABC123');
 assert.equal(translate('Jl. Merdeka 17, Jakarta',locale),'Jl. Merdeka 17, Jakarta');
 assert.equal(translate('Gigabyte Graphic Card',locale),'Gigabyte Graphic Card');
 assert.ok(translate('  Beranda  ',locale).startsWith('  '),'Preserve text spacing');
 setCurrentLocale(locale);assert.equal(getLocaleTag(),languages.find(l=>l.code===locale).tag);
 assert.equal(date('2026-11-12 00:00:00'),date('2026-11-12'),'Jakarta calendar date must be stable');
 assert.ok(rupiah(2499000).includes('2.499.000'),'Currency stays IDR');
}
setCurrentLocale('id');assert.equal(translate('Beranda'),'Beranda');
setCurrentLocale('en');assert.equal(translate('Beranda'),'Home');
assert.equal(translate('Kolom postal code wajib diisi.'),'The postal code field is required.');
setCurrentLocale('id');
console.log('PASS: 5 locales, '+translatedSources().length+' complete message entries, dynamic messages, user data preservation, date/time and IDR formatting.');
