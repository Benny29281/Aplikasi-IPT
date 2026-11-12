import React, { useState } from 'react';
import { Modal, Pressable, ScrollView, View } from 'react-native';
import { languages } from '../i18n/core';
import { useLocale } from '../i18n/LocaleProvider';
import { C, Icon, Txt } from './UI';

export function LanguagePicker({ expanded = false }: { expanded?: boolean }) {
 const { locale, changeLocale, t, persistenceError } = useLocale();
 const [open, setOpen] = useState(false);
 const selected = languages.find(l => l.code === locale)!;
 return <>
  <Pressable accessibilityRole="button" accessibilityLabel={t('Pilih bahasa')} accessibilityState={{expanded:open}}
   onPress={()=>setOpen(true)} style={{ minHeight:40, flexDirection:'row', alignItems:'center', gap:6, paddingHorizontal:expanded?12:7, paddingVertical:8, borderWidth:expanded?1:0, borderColor:C.line, borderRadius:10 }}>
   <Icon name="globe" size={18} color={C.blue}/>
   <Txt translate={false} style={{ fontSize:12, fontWeight:'600', color:C.blue, flexShrink:1 }}>{expanded?selected.name:selected.short}</Txt>
   {expanded&&<Icon name="chevron-down" size={15} color={C.muted}/>}
  </Pressable>
  <Modal visible={open} transparent animationType="fade" onRequestClose={()=>setOpen(false)}>
   <View style={{flex:1,backgroundColor:'rgba(18,36,66,0.45)',alignItems:'center',justifyContent:'center',padding:20}}>
    <Pressable accessibilityRole="button" accessibilityLabel={t('Tutup')} onPress={()=>setOpen(false)} style={{position:'absolute',top:0,left:0,right:0,bottom:0}}/>
    <View accessibilityViewIsModal style={{backgroundColor:'white',borderRadius:18,width:'100%',maxWidth:420,maxHeight:'85%',padding:22,gap:16}}>
     <View style={{flexDirection:'row',alignItems:'center',justifyContent:'space-between',gap:12}}>
      <View style={{flex:1}}><Txt accessibilityRole="header" style={{fontSize:21,fontWeight:'700',lineHeight:29}}>Pilih bahasa</Txt><Txt style={{fontSize:12,color:C.muted,marginTop:3}}>Bahasa aplikasi</Txt></View>
      <Pressable accessibilityRole="button" accessibilityLabel={t('Tutup')} onPress={()=>setOpen(false)} style={{padding:10}}><Icon name="x" size={20}/></Pressable>
     </View>
     <ScrollView contentContainerStyle={{gap:8}}>
      {languages.map(language=><Pressable key={language.code} accessibilityRole="radio" accessibilityLabel={language.name} accessibilityState={{checked:locale===language.code}}
       onPress={async()=>{if(await changeLocale(language.code))setOpen(false);}} style={{flexDirection:'row',alignItems:'center',gap:14,padding:15,minHeight:56,borderRadius:11,borderWidth:1,borderColor:locale===language.code?C.blue:C.line,backgroundColor:locale===language.code?C.pale:'white'}}>
       <View style={{flex:1}}><Txt translate={false} style={{fontSize:15,fontWeight:locale===language.code?'700':'500'}}>{language.name}</Txt></View>
       <Icon name={locale===language.code?'check-circle':'circle'} size={20} color={locale===language.code?C.blue:'#BAC4D4'}/>
      </Pressable>)}
     </ScrollView>
     <Txt style={{fontSize:11,lineHeight:18,color:C.muted}}>Pilihan bahasa disimpan di perangkat ini. Harga tetap dalam Rupiah.</Txt>
     {!!persistenceError&&<Txt accessibilityRole="alert" style={{fontSize:11,color:'#A14646'}}>{persistenceError}</Txt>}
    </View>
   </View>
  </Modal>
 </>;
}
