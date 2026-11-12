import { useEffect, useState } from 'react';

let serverOffset = 0;
export function syncPromotionClock(iso: string) { const time=Date.parse(iso); if(Number.isFinite(time))serverOffset=time-Date.now(); }
export const promotionNow=()=>Date.now()+serverOffset;
export function saleIsActive(product: any, now=promotionNow()): boolean {
 return !!product && product.sale_price != null && Number(product.discount_percent)>0 && Date.parse(product.sale_ends_at)>now;
}
export function salePrice(product: any, base=Number(product?.price||0), now=promotionNow()): number {
 return saleIsActive(product,now)?Math.floor(base*(100-Number(product.discount_percent))/100):base;
}
export function useSaleClock(products: any[]) {
 const expires=products.map(p=>p?.sale_ends_at||'').join('|');
 const [now,setNow]=useState(promotionNow);
 useEffect(()=>{setNow(promotionNow());if(!expires.replace(/\|/g,''))return;const id=setInterval(()=>setNow(promotionNow()),1000);return()=>clearInterval(id);},[expires]);
 return now;
}
export function campaignState(c: any, now: number): string {
 if(!c)return 'disabled';if(c.mode==='preview')return 'preview';if(!c.enabled)return 'disabled';
 const start=Date.parse(c.starts_at),end=Date.parse(c.ends_at);
 if(!Number.isFinite(start)||!Number.isFinite(end)||end<=start)return 'disabled';
 return now<start?'scheduled':now<end?'live':'ended';
}
export function countdown(target: string | null, now: number) {
 const seconds=Math.max(0,Math.ceil(((target?Date.parse(target):now)-now)/1000));
 return [Math.floor(seconds/86400),Math.floor(seconds%86400/3600),Math.floor(seconds%3600/60),seconds%60].map(v=>String(v).padStart(2,'0'));
}
