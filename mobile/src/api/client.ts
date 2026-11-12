import axios from 'axios';
import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';
export const API_URL = process.env.EXPO_PUBLIC_API_URL || (Platform.OS === 'web' && typeof window !== 'undefined' ? (window.location.port==='8081'?`${window.location.protocol}//${window.location.hostname}:8000/api`:`${window.location.origin}/api`) : 'http://10.0.2.2:8000/api');
export const ASSET_URL = API_URL.replace(/\/api\/?$/, '');
export const media = (url?: string) => url ? (/^https?:|^data:/.test(url) ? url : ASSET_URL + url) : '';
export const client = axios.create({ baseURL: API_URL, timeout: 15000 });
let authToken = '';
export const setToken = (token: string) => { authToken = token; };
client.interceptors.request.use(config => { if(authToken) config.headers.Authorization = `Bearer ${authToken}`; return config; });
export const tokenStorage = {
 async get() { return Platform.OS === 'web' ? sessionStorage.getItem('ipt_token') : SecureStore.getItemAsync('ipt_token'); },
 async set(token: string) { if(Platform.OS === 'web') sessionStorage.setItem('ipt_token',token); else await SecureStore.setItemAsync('ipt_token',token); },
 async clear() { if(Platform.OS === 'web') sessionStorage.removeItem('ipt_token'); else await SecureStore.deleteItemAsync('ipt_token'); }
};
export async function api(path: string, method: string = 'GET', data?: any): Promise<any> {
 try { const response = await client.request({ url: path, method, data }); return response.data.data; }
 catch(error: any) { throw new Error(error.response?.data?.message || 'Tidak dapat terhubung ke server. Periksa koneksi dan alamat API.'); }
}
