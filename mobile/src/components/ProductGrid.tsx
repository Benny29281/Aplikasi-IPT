import React from 'react';
import { View } from 'react-native';
import { ProductCard } from './ProductCard';
import { useSize } from './UI';

// Let each row share its actual available space. Viewport-based pixel widths
// can wrap a half-width card when scrollbars or rounding consume space.
export function ProductGrid({ products, desktopColumns = 4 }: { products: any[]; desktopColumns?: number }) {
 const { mobile, desktop } = useSize();
 const columns = desktop ? desktopColumns : mobile ? 2 : 3;
 const gap = mobile ? 12 : 18;
 const rows = Array.from({ length: Math.ceil(products.length / columns) }, (_, index) => products.slice(index * columns, (index + 1) * columns));
 return <View style={{ width: '100%', minWidth: 0, gap }}>
  {rows.map((row, rowIndex) => <View key={rowIndex} style={{ flexDirection: 'row', alignItems: 'stretch', gap, minWidth: 0 }}>
   {Array.from({ length: columns }, (_, index) => <View key={row[index]?.id ?? `empty-${index}`} style={{ flex: 1, minWidth: 0 }}>
    {row[index] && <ProductCard product={row[index]} />}
   </View>)}
  </View>)}
 </View>;
}
