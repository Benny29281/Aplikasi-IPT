import React, { useEffect, useRef, useState } from 'react';
import { AccessibilityInfo, Animated, Easing, Image, Platform, View } from 'react-native';
import { C, Txt, useSize } from './UI';

const sessionKey='ipt_intro_seen_v1';
export function introSeenInSession() {
 if(Platform.OS!=='web'||typeof window==='undefined')return false;
 try{return window.sessionStorage.getItem(sessionKey)==='1';}catch{return false;}
}
export function rememberIntro() {
 if(Platform.OS==='web')try{window.sessionStorage.setItem(sessionKey,'1');}catch{}
}

export function IntroSplash({ready,onFinish}:{ready:boolean;onFinish:()=>void}) {
 const {width,mobile}=useSize();
 const [reduceMotion,setReduceMotion]=useState<boolean|null>(null);
 const [imageReady,setImageReady]=useState(false),[imageFailed,setImageFailed]=useState(false),[entranceDone,setEntranceDone]=useState(false);
 const logo=useRef(new Animated.Value(0)).current;
 const accent=useRef(new Animated.Value(0)).current;
 const exit=useRef(new Animated.Value(1)).current;
 const logoWidth=Math.max(160,Math.min(width-80,300));

 useEffect(()=>{
  let active=true;
  AccessibilityInfo.isReduceMotionEnabled().then(value=>{if(active)setReduceMotion(value);}).catch(()=>{if(active)setReduceMotion(true);});
  const fallback=setTimeout(()=>{if(active)setReduceMotion(value=>value??true);},250);
  const subscription=AccessibilityInfo.addEventListener('reduceMotionChanged',value=>{if(active)setReduceMotion(value);});
  return()=>{active=false;clearTimeout(fallback);subscription.remove();};
 },[]);
 useEffect(()=>{
  // Never hold the app behind a missing or slow logo request.
  const fallback=setTimeout(()=>setImageReady(true),1200);return()=>clearTimeout(fallback);
 },[]);
 useEffect(()=>{
  if(reduceMotion===null||!imageReady||entranceDone)return;
  if(reduceMotion){logo.setValue(1);accent.setValue(1);const timer=setTimeout(()=>setEntranceDone(true),400);return()=>clearTimeout(timer);}
  const animation=Animated.sequence([
   Animated.parallel([
    Animated.timing(logo,{toValue:1,duration:700,easing:Easing.out(Easing.cubic),useNativeDriver:Platform.OS!=='web'}),
    Animated.timing(accent,{toValue:1,duration:1100,easing:Easing.inOut(Easing.cubic),useNativeDriver:Platform.OS!=='web'}),
   ]),
   Animated.delay(650),
  ]);
  animation.start(({finished})=>{if(finished)setEntranceDone(true);});
  return()=>animation.stop();
 },[reduceMotion,imageReady,entranceDone]);
 useEffect(()=>{
  if(!ready||!entranceDone)return;
  if(reduceMotion){onFinish();return;}
  const animation=Animated.timing(exit,{toValue:0,duration:240,easing:Easing.out(Easing.quad),useNativeDriver:Platform.OS!=='web'});
  animation.start(({finished})=>{if(finished)onFinish();});return()=>animation.stop();
 },[ready,entranceDone,reduceMotion,onFinish]);

 return <Animated.View style={{flex:1,backgroundColor:'white',justifyContent:'center',alignItems:'center',padding:32,opacity:exit}}>
  <View pointerEvents="none" style={{position:'absolute',width:mobile?280:440,height:mobile?280:440,borderRadius:250,backgroundColor:'#F4F8FF'}}/>
  <Animated.View style={{alignItems:'center',gap:22,maxWidth:400,width:'100%',opacity:logo,transform:[{translateY:logo.interpolate({inputRange:[0,1],outputRange:[14,0]})},{scale:logo.interpolate({inputRange:[0,1],outputRange:[.92,1]})}]}}>
   <View style={{backgroundColor:'white',padding:18,borderRadius:18}}>
    {!imageFailed&&<Image source={require('../../assets/logo.png')} accessibilityLabel="Indonesia Pacific Trade" resizeMode="contain" onLoad={()=>setImageReady(true)} onError={()=>{setImageFailed(true);setImageReady(true);}} style={{width:logoWidth,height:logoWidth*104/288}}/>}
    {imageFailed&&<Txt translate={false} style={{fontSize:23,lineHeight:32,fontWeight:'800',color:C.blue,textAlign:'center'}}>INDONESIA{`\n`}PACIFIC TRADE</Txt>}
   </View>
   <View style={{alignItems:'center',gap:6}}><Txt translate={false} style={{fontSize:27,lineHeight:34,fontWeight:'700',letterSpacing:-.8,color:C.navy}}>IPT store<Txt translate={false} style={{fontSize:27,color:C.blue}}>.</Txt></Txt><Txt style={{fontSize:10,lineHeight:18,letterSpacing:1.8,color:C.muted,textAlign:'center'}}>MOMENMU, CERITAMU.</Txt></View>
   <View style={{width:64,height:3,backgroundColor:'#E8EFFB',borderRadius:4,overflow:'hidden'}} accessibilityElementsHidden><Animated.View style={{height:3,width:64,backgroundColor:C.blue,borderRadius:4,transform:[{scaleX:accent}]}}/></View>
  </Animated.View>
  {entranceDone&&!ready&&<View style={{position:'absolute',bottom:55,left:24,right:24,alignItems:'center'}}><Txt accessibilityLiveRegion="polite" style={{fontSize:12,color:C.muted}}>Menyiapkan toko...</Txt></View>}
 </Animated.View>;
}
