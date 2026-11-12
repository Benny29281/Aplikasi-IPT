import React from 'react';
import { Pressable, View } from 'react-native';
import { C, Icon, Photo, Tag, Txt, useSize } from './UI';
import { rupiah } from '../utils/format';
import { useStore } from '../context/Store';
import { useLocale } from '../i18n/LocaleProvider';
import { saleIsActive, salePrice, useSaleClock } from '../utils/promotion';
export function ProductCard({ product: p, width = '100%' }: any) {
 const { go } = useStore();
 const {t}=useLocale();
 const { mobile } = useSize();
 const available = Number(p.stock) > 0;
 const now=useSaleClock([p]);const onSale=saleIsActive(p,now);
 return <Pressable accessibilityRole="button" accessibilityLabel={t(`Lihat ${p.name}`)} onPress={() => go({ name: 'product', id: p.id })}
  style={({ hovered }: any) => ({ width, minWidth: 0, flexGrow: 1, backgroundColor: 'white', borderWidth: 1, borderColor: hovered ? '#B5C9F0' : C.line, borderRadius: mobile ? 12 : 14, overflow: 'hidden' })}>
  <View style={{ backgroundColor: '#F0F3F8', padding: mobile ? 8 : 14, aspectRatio: 1 }}>
   <Photo src={p.image} label={p.name} style={{ width: '100%', height: '100%', borderRadius: 7 }} />
   {!available && <View style={{ position: 'absolute', top: 9, left: 9 }}><Tag bg="#ECEEF2" color={C.muted}>Stok habis</Tag></View>}
   {available&&onSale&&<View style={{position:'absolute',top:9,left:9}}><Tag color="#7E421F" bg="#FFE58C">−{p.discount_percent}%</Tag></View>}
  </View>
  <View style={{ padding: mobile ? 11 : 16, gap: mobile ? 5 : 7, flex: 1 }}>
   <Txt numberOfLines={1} style={{ color: C.muted, fontSize: mobile ? 9 : 10, lineHeight: 15, letterSpacing: .6, textTransform: 'uppercase' }}>{p.category_name}</Txt>
   <Txt translate={false} numberOfLines={2} style={{ fontSize: mobile ? 13 : 14, fontWeight: '600', minHeight: 38, lineHeight: 19 }}>{p.name}</Txt>
   {onSale&&<Txt style={{fontSize:11,lineHeight:16,color:C.muted,textDecorationLine:'line-through'}}>{rupiah(p.price)}</Txt>}
   <Txt numberOfLines={1} adjustsFontSizeToFit minimumFontScale={.85} style={{ fontWeight: '700', fontSize: mobile ? 15 : 17, lineHeight: 24, letterSpacing: -.5, marginTop: 3,color:onSale?'#B05C29':C.text }}>{rupiah(salePrice(p,Number(p.price),now))}</Txt>
   <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 5, marginTop: 3 }}>
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 5, flex: 1, minWidth: 0 }}>
     <View style={{ width: 5, height: 5, borderRadius: 3, backgroundColor: available ? C.green : '#A9B1BD' }} />
     <Txt numberOfLines={1} style={{ fontSize: 10, lineHeight: 16, flexShrink: 1, color: available ? C.green : C.muted }}>{available ? 'Tersedia' : 'Stok habis'}</Txt>
    </View>
    <View style={{ width: 26, height: 26, borderRadius: 8, backgroundColor: C.pale, alignItems: 'center', justifyContent: 'center' }}><Icon name="arrow-up-right" size={15} color={C.blue} /></View>
   </View>
  </View>
 </Pressable>;
}
