import React, { createContext, useContext, useEffect, useState } from 'react';
import { BackHandler, Platform } from 'react-native';
import { api, setToken, tokenStorage } from '../api/client';
import { parseHash, Route, routeHash } from '../navigation/routes';
type StoreValue = { user:any; cart:any[]; config:any; route:Route; ready:boolean; message:string; notify:(s:string)=>void; go:(r:Route)=>void; login:(data:any)=>Promise<void>; logout:()=>Promise<void>; refresh:()=>Promise<void>; refreshCart:()=>Promise<void>; add:(variant:number,qty?:number)=>Promise<void>; requireUser:()=>boolean; };
const Context=createContext<StoreValue>(null!);
export const useStore=()=>useContext(Context);
export function StoreProvider({children}:{children:React.ReactNode}) {
 const [user,setUser]=useState<any>(null),[cart,setCart]=useState<any[]>([]),[config,setConfig]=useState<any>(null),[ready,setReady]=useState(false),[route,setRoute]=useState<Route>(Platform.OS==='web'?parseHash():{name:'home'}),[message,setMessage]=useState('');
 const notify=(s:string)=>setMessage(s);
 const go=(r:Route)=>{setRoute(r);if(Platform.OS==='web') window.location.hash=routeHash(r);};
 const refreshCart=async()=>{setCart(await api('/cart'));};
 const refresh=async()=>{const [u,c]=await Promise.all([api('/me'),api('/config')]);setUser(u);setConfig(c);await refreshCart();};
 useEffect(()=>{let active=true;(async()=>{try{const c=await api('/config');if(active)setConfig(c);const token=await tokenStorage.get();if(token){setToken(token);try{await refresh();}catch{setToken('');await tokenStorage.clear();}}}catch(e:any){notify(e.message);}finally{if(active)setReady(true);}})();return()=>{active=false;};},[]);
 useEffect(()=>{if(!message)return;const timer=setTimeout(()=>setMessage(''),5000);return()=>clearTimeout(timer);},[message]);
 useEffect(()=>{if(Platform.OS==='web'){const handler=()=>setRoute(parseHash());window.addEventListener('hashchange',handler);return()=>window.removeEventListener('hashchange',handler);}const h=BackHandler.addEventListener('hardwareBackPress',()=>{if(route.name!=='home'){go({name:'home'});return true;}return false;});return()=>h.remove();},[route.name]);
 const login=async(data:any)=>{setToken(data.token);await tokenStorage.set(data.token);setUser(data.user);await refreshCart();go({name:'home'});notify('Selamat datang, '+data.user.name.split(' ')[0]+'!');};
 const logout=async()=>{try{await api('/auth/logout','POST');}catch{}setToken('');await tokenStorage.clear();setUser(null);setCart([]);go({name:'home'});};
 const requireUser=()=>{if(!user){go({name:'auth'});notify('Masuk terlebih dahulu untuk melanjutkan.');return false;}return true;};
 const add=async(variant:number,qty=1)=>{if(!requireUser())return;setCart(await api('/cart','POST',{variant_id:variant,qty}));notify('Produk ditambahkan ke keranjang.');};
 return <Context.Provider value={{user,cart,config,route,ready,message,notify,go,login,logout,refresh,refreshCart,add,requireUser}}>{children}</Context.Provider>;
}
