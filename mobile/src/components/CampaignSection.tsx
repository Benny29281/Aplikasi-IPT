import React, { useEffect, useRef, useState } from 'react';
import { AccessibilityInfo, Animated, AppState, Easing, Platform, Pressable, View } from 'react-native';
import { useLoad } from '../api/useLoad';
import { useStore } from '../context/Store';
import { useLocale } from '../i18n/LocaleProvider';
import { campaignState, countdown, promotionNow, syncPromotionClock } from '../utils/promotion';
import { Btn, C, Icon, s, Tag, Txt, useSize } from './UI';
import { ProductGrid } from './ProductGrid';

export function CampaignSection({ sampleProducts=[] }: { sampleProducts?: any[] }) {
 const {data:c,reload}=useLoad('/campaign');const {go}=useStore();const {t}=useLocale();const {mobile,compact}=useSize();
 const [now,setNow]=useState(promotionNow),[paused,setPaused]=useState(false),[reduceMotion,setReduceMotion]=useState(true),[foreground,setForeground]=useState(AppState.currentState!=='background');
 const progress=useRef(new Animated.Value(0)).current;
 const mode=campaignState(c,now);
 useEffect(()=>{if(c?.server_now){syncPromotionClock(c.server_now);setNow(promotionNow());}},[c]);
 useEffect(()=>{let mounted=true;AccessibilityInfo.isReduceMotionEnabled().then(value=>{if(mounted)setReduceMotion(value);}).catch(()=>{});const subscription=AccessibilityInfo.addEventListener('reduceMotionChanged',setReduceMotion);return()=>{mounted=false;subscription.remove();};},[]);
 useEffect(()=>{
  const sub=AppState.addEventListener('change',state=>{setForeground(state==='active');if(state==='active'){setNow(promotionNow());reload();}});
  if(Platform.OS!=='web')return()=>sub.remove();
  const visible=()=>{setForeground(document.visibilityState==='visible');if(document.visibilityState==='visible'){setNow(promotionNow());reload();}};
  document.addEventListener('visibilitychange',visible);return()=>{sub.remove();document.removeEventListener('visibilitychange',visible);};
 },[]);
 useEffect(()=>{if(!foreground)return;const tick=c?.enabled?setInterval(()=>setNow(promotionNow()),1000):null;const poll=setInterval(reload,30000);return()=>{if(tick)clearInterval(tick);clearInterval(poll);};},[foreground,c?.enabled]);
 useEffect(()=>{if(c&&mode!==c.mode&&['live','ended'].includes(mode))reload();},[mode,c?.mode]);
 useEffect(()=>{
  if(reduceMotion||paused||!foreground||mode==='disabled'){progress.setValue(0);return;}
  const animation=Animated.loop(Animated.sequence([
   Animated.timing(progress,{toValue:1,duration:2600,easing:Easing.inOut(Easing.sin),useNativeDriver:Platform.OS!=='web'}),
   Animated.timing(progress,{toValue:0,duration:2600,easing:Easing.inOut(Easing.sin),useNativeDriver:Platform.OS!=='web'}),
  ]));animation.start();return()=>animation.stop();
 },[reduceMotion,paused,foreground,mode]);
 if(!c||mode==='disabled')return null;
 const preview=mode==='preview';const live=mode==='live';const target=mode==='scheduled'?c.starts_at:live?c.ends_at:null;
 const parts=countdown(target,now);const products=live?c.products:preview?sampleProducts.filter(p=>!p.is_age_restricted&&Number(p.stock)>0).slice(0,4):[];
 return <View style={{marginTop:mobile?20:28,gap:20}}>
  <View style={{backgroundColor:'#17265D',borderRadius:20,overflow:'hidden',padding:mobile?22:32,minHeight:mobile?250:225}}>
   <Animated.View pointerEvents="none" accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={{position:'absolute',right:-55,top:-105,width:350,height:350,borderRadius:180,backgroundColor:'#6C50D8',opacity:progress.interpolate({inputRange:[0,1],outputRange:[.35,.55]}),transform:[{translateY:progress.interpolate({inputRange:[0,1],outputRange:[0,12]})}]}}/>
   <View style={{position:'absolute',bottom:-110,left:'25%',width:290,height:200,borderRadius:150,backgroundColor:'#263D87',transform:[{rotate:'-20deg'}]}} pointerEvents="none"/>
   <View style={{flexDirection:compact?'column':'row',alignItems:compact?'stretch':'center',gap:compact?14:32}}>
    <View style={{flex:1,gap:12,minWidth:0,zIndex:1}}>
     <View style={s.wrap}><View style={{...s.row,gap:7}}><Icon name="zap" size={16} color="#FFE58C"/><Txt style={{fontSize:10,color:'#FFE58C',fontWeight:'700',letterSpacing:1.8}}>IPT SPECIAL EVENT</Txt></View><Tag color={preview?'#FFE58C':'#D6FFE8'} bg={preview?'#66502E':'#215747'}>{preview?'Pratinjau event':live?'Sedang berlangsung':mode==='scheduled'?'Segera hadir':'Event berakhir'}</Tag></View>
     <Txt style={{fontSize:mobile?26:34,lineHeight:mobile?34:42,fontWeight:'700',color:'white',letterSpacing:-.7}}>{c.title}</Txt>
     <Txt style={{color:'#C4CFF0',fontSize:12,lineHeight:21,maxWidth:460}}>{preview?'Contoh tampilan event. Promo belum aktif dan harga belum didiskon.':live?'Harga spesial untuk produk pilihan selama jadwal event.':mode==='scheduled'?'Catat tanggalnya. Pilihan spesial segera hadir.':'Terima kasih sudah merayakan momen ini bersama IPT.'}</Txt>
     <Btn kind="outline" icon="arrow-right" onPress={()=>go(live?{name:'catalog',sale:true}:{name:'catalog'})} style={{alignSelf:'flex-start',backgroundColor:'#FFE58C',borderColor:'#FFE58C',minHeight:42,paddingVertical:9}}>{live?'Belanja event':'Jelajahi produk'}</Btn>
    </View>
    <View style={{alignItems:'center',justifyContent:'center',width:compact?'100%':260,minHeight:compact?95:150}}>
     <Animated.View style={{transform:[{translateY:progress.interpolate({inputRange:[0,1],outputRange:[0,-7]})},{rotate:progress.interpolate({inputRange:[0,1],outputRange:['-3deg','1deg']})}]}}>
      <Txt translate={false} numberOfLines={1} adjustsFontSizeToFit style={{fontSize:Math.min(mobile?65:88,Math.floor(380/Math.max(1,Array.from(String(c.label)).length))),lineHeight:mobile?80:105,fontWeight:'900',color:'#FFE58C',letterSpacing:-4,textAlign:'center',maxWidth:compact?280:260}}>{c.label}</Txt>
     </Animated.View>
     <Txt style={{color:'#D6DCFF',fontSize:10,letterSpacing:2,fontWeight:'700'}}>BIG MOMENT SALE</Txt>
    </View>
   </View>
   {!reduceMotion&&<Pressable accessibilityRole="button" accessibilityLabel={t(paused?'Putar animasi':'Jeda animasi')} onPress={()=>setPaused(v=>!v)} style={{position:'absolute',right:12,bottom:12,padding:12,minWidth:44,minHeight:44,alignItems:'center',justifyContent:'center',borderRadius:24,backgroundColor:'#344474'}}><Icon name={paused?'play':'pause'} size={13} color="white"/></Pressable>}
  </View>
  {(preview||live||mode==='scheduled')&&<View style={{gap:18}}>
   <View style={{...s.wrap,justifyContent:'space-between',gap:14}}>
    <View style={{gap:4}}><View style={{...s.row,gap:8}}><Icon name="zap" size={23} color="#BD6A20"/><Txt accessibilityRole="header" style={{fontSize:23,lineHeight:30,fontWeight:'700',letterSpacing:-.6}}>Flash Sale</Txt></View><Txt style={{fontSize:11,color:C.muted}}>{preview?'Contoh pilihan produk · Harga normal':live?'Berakhir dalam':'Dimulai dalam'}</Txt></View>
    <View style={{...s.row,gap:5}}>{parts.map((part,i)=><React.Fragment key={i}>{i>0&&<Txt style={{fontWeight:'700',color:'#A3AEC2'}}>:</Txt>}<View style={{alignItems:'center',gap:3}}><View style={{backgroundColor:C.navy,minWidth:mobile?38:44,paddingVertical:8,paddingHorizontal:8,borderRadius:8}}><Txt translate={false} style={{color:'white',fontSize:18,lineHeight:24,fontWeight:'700',fontVariant:['tabular-nums'],textAlign:'center'}}>{target?part:'—'}</Txt></View><Txt style={{fontSize:9,lineHeight:14,color:C.muted}}>{['Hari','Jam','Menit','Detik'][i]}</Txt></View></React.Fragment>)}</View>
   </View>
   {preview&&<Txt style={{fontSize:11,lineHeight:18,color:'#8C6A2A'}}>Pratinjau desain. Jadwal dan diskon ditentukan admin.</Txt>}
   {!!products.length&&<ProductGrid products={products.slice(0,4)}/>}
  </View>}
 </View>;
}
