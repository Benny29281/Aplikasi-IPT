import React, { useCallback, useState } from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StoreProvider, useStore } from './src/context/Store';
import { Layout } from './src/components/Layout';
import { Loading } from './src/components/UI';
import { Home } from './src/screens/Home';
import { Catalog, Product } from './src/screens/Catalog';
import { Cart, Checkout, Orders, Order } from './src/screens/Shopping';
import { Auth, Profile, Addresses, Age } from './src/screens/Account';
import { About, Help, Welcome } from './src/screens/Information';
import { Admin } from './src/screens/Admin';
import { LocaleProvider, useLocale } from './src/i18n/LocaleProvider';
import { IntroSplash, introSeenInSession, rememberIntro } from './src/components/IntroSplash';
function Router(){const {route,ready}=useStore();const {locale,languageReady}=useLocale();const [introDone,setIntroDone]=useState(introSeenInSession);const finishIntro=useCallback(()=>{rememberIntro();setIntroDone(true);},[]);if(!introDone)return <IntroSplash ready={ready&&languageReady} onFinish={finishIntro}/>;const pages:any={home:Home,welcome:Welcome,catalog:Catalog,product:Product,cart:Cart,checkout:Checkout,orders:Orders,order:Order,profile:Profile,addresses:Addresses,auth:Auth,age:Age,about:About,help:Help,admin:Admin};const Screen=pages[route.name]||Home;return <Layout>{!ready||!languageReady?<Loading/>:<Screen key={route.name+'-'+route.id}/>}</Layout>;}
export default function App(){return <SafeAreaProvider><LocaleProvider><StoreProvider><StatusBar style="dark"/><Router/></StoreProvider></LocaleProvider></SafeAreaProvider>;}
