import React, { useState } from 'react';
import { Pressable, View } from 'react-native';
import { api } from '../api/client';
import { useLoad } from '../api/useLoad';
import { useStore } from '../context/Store';
import { Btn, C, ErrorBox, Field, Heading, Icon, Loading, Photo, s, Tag, Txt } from '../components/UI';
import { rupiah } from '../utils/format';

export function CampaignAdmin() {
 const state=useLoad('/admin/campaign');const products=useLoad('/admin/products',[]);const categories=useLoad('/admin/categories',[]);
 const {notify,go}=useStore();const [busy,setBusy]=useState(false);const d=state.data;
 const save=async()=>{setBusy(true);try{await api('/admin/campaign','PUT',d);state.reload();notify('Event tersimpan.');}catch(e:any){notify(e.message);}finally{setBusy(false);}};
 if(state.loading||products.loading||categories.loading)return <Loading/>;
 const error=state.error||products.error||categories.error;
 if(error)return <ErrorBox message={error} retry={()=>{state.reload();products.reload();categories.reload();}}/>;
 if(!d)return null;
 const eligible=products.data.filter((p:any)=>p.is_active&&categories.data.some((c:any)=>c.id===p.category_id&&c.is_active&&!c.is_age_restricted));
 return <View style={{...s.panel,maxWidth:960}}>
  <Heading title="Event & Flash Sale" subtitle="Atur tampilan, jadwal, dan harga promo dalam satu tempat."/>
  <Tag bg="#FFF5DD" color="#946D2C">Semua jadwal menggunakan WIB (UTC+7)</Tag>
  <Field label="Label event" placeholder="11.11 / 12.12 / Weekend Sale" value={d.label} onChangeText={(v:string)=>state.setData({...d,label:v})}/>
  <Field label="Judul event" value={d.title} onChangeText={(v:string)=>state.setData({...d,title:v})}/>
  <View style={s.wrap}>
   <Field label="Mulai (YYYY-MM-DD HH:mm)" placeholder="2026-12-12 00:00" value={d.starts_at} onChangeText={(v:string)=>state.setData({...d,starts_at:v})}/>
   <Field label="Selesai (YYYY-MM-DD HH:mm)" placeholder="2026-12-13 00:00" value={d.ends_at} onChangeText={(v:string)=>state.setData({...d,ends_at:v})}/>
  </View>
  <Field label="Diskon event (%)" value={String(d.discount_percent)} keyboardType="numeric" onChangeText={(v:string)=>state.setData({...d,discount_percent:v})}/>
  <Txt style={{fontSize:12,color:C.muted}}>Diskon berlaku pada harga dasar atau harga varian produk terpilih, tanpa penumpukan promo.</Txt>
  <View style={s.divider}/>
  <Txt style={{fontSize:18,fontWeight:'700'}}>Pilih produk event</Txt>
  <Txt style={{fontSize:12,color:C.muted}}>Maksimal 20 produk aktif. Kategori terbatas tidak dapat mengikuti event.</Txt>
  <View style={{gap:10}}>{eligible.map((p:any)=>{
   const checked=d.product_ids.includes(p.id);
   return <Pressable key={p.id} accessibilityRole="checkbox" accessibilityState={{checked}} onPress={()=>state.setData({...d,product_ids:checked?d.product_ids.filter((id:number)=>id!==p.id):[...d.product_ids,p.id]})}
    style={{...s.row,padding:13,borderWidth:1,borderColor:checked?C.blue:C.line,borderRadius:10,backgroundColor:checked?'#F5F8FF':'white',flexWrap:'wrap'}}>
    <Icon name={checked?'check-square':'square'} color={checked?C.blue:C.muted}/><Photo src={p.image} style={{width:44,height:44,borderRadius:6}}/>
    <View style={{flex:1,minWidth:120,gap:3}}><Txt translate={false} style={{fontWeight:'600',fontSize:13}}>{p.name}</Txt><Txt style={{fontSize:11,color:C.muted}}>Stok: {p.stock}</Txt></View>
    <View style={{alignItems:'flex-end',gap:2}}><Txt style={{fontSize:11,color:C.muted,textDecorationLine:checked?'line-through':'none'}}>{rupiah(p.price)}</Txt>{checked&&<Txt style={{fontSize:13,fontWeight:'700',color:C.blue}}>{rupiah(Math.floor(Number(p.price)*(100-Math.max(0,Math.min(90,Number(d.discount_percent)||0)))/100))}</Txt>}</View>
   </Pressable>;
  })}</View>
  <Pressable accessibilityRole="switch" accessibilityState={{checked:!!d.enabled}} onPress={()=>state.setData({...d,enabled:!d.enabled})} style={{...s.row,paddingVertical:12}}><Icon name={d.enabled?'check-square':'square'} color={C.blue}/><Txt style={{flex:1,fontWeight:'600'}}>Aktifkan event sesuai jadwal</Txt></Pressable>
  <Txt style={{fontSize:12,color:C.muted}}>Saat waktu habis, harga normal kembali otomatis. Pesanan yang sudah dibuat tetap memakai harga saat checkout.</Txt>
  <View style={s.wrap}><Btn loading={busy} onPress={save} icon="save">Simpan Event</Btn><Btn kind="outline" onPress={()=>go({name:'home'})}>Lihat Beranda</Btn></View>
 </View>;
}
