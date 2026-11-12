import React, { createContext, useContext, useEffect, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';
import { isLocale, Locale, setCurrentLocale, translate } from './core';

type LocaleValue = { locale: Locale; changeLocale: (locale: Locale) => Promise<boolean>; t: (text: string) => string; languageReady: boolean; persistenceError: string };
const Context = createContext<LocaleValue>({ locale:'id', changeLocale:async()=>false, t:translate, languageReady:false, persistenceError:'' });
export const useLocale = () => useContext(Context);
const storageKey = 'ipt_language';

export function LocaleProvider({ children }: { children: React.ReactNode }) {
 const [locale, setLocale] = useState<Locale>('id');
 const [languageReady, setReady] = useState(false);
 const [persistenceError, setPersistenceError] = useState('');
 useEffect(() => {
  let active = true;
  AsyncStorage.getItem(storageKey).then(value => {
   if (active && isLocale(value)) { setCurrentLocale(value); setLocale(value); }
  }).catch(() => {}).finally(() => { if(active) setReady(true); });
  return () => { active = false; };
 }, []);
 useEffect(() => {
  if (Platform.OS === 'web') {
   document.documentElement.lang = locale;
   document.title = 'IPT Store | Indonesia Pacific Trade';
  }
 }, [locale]);
 const changeLocale = async (next: Locale) => {
  if (!isLocale(next)) return false;
  setCurrentLocale(next); setLocale(next); setPersistenceError('');
  try { await AsyncStorage.setItem(storageKey, next); return true; }
  catch { setPersistenceError('Bahasa berubah untuk sesi ini. Penyimpanan perangkat tidak tersedia.'); return false; }
 };
 return <Context.Provider value={{ locale, changeLocale, t:text=>translate(text,locale), languageReady, persistenceError }}>{children}</Context.Provider>;
}
