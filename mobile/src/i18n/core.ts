import { entries } from './translations';
import { extraEntries } from './translations-extra';
import { messages } from './messages';
import { campaigns } from './campaigns';

export const languages = [
 { code: 'id', name: 'Bahasa Indonesia', short: 'ID', tag: 'id-ID' },
 { code: 'en', name: 'English', short: 'EN', tag: 'en-GB' },
 { code: 'zh-HK', name: '繁體中文（香港）', short: '繁', tag: 'zh-HK' },
 { code: 'ko', name: '한국어', short: 'KO', tag: 'ko-KR' },
 { code: 'zh-CN', name: '简体中文', short: '简', tag: 'zh-CN' },
] as const;
export type Locale = typeof languages[number]['code'];
export type Entry = readonly [string, string, string, string, string];
export const isLocale = (value: unknown): value is Locale => languages.some(l => l.code === value);
const normalize = (text: string) => text.trim().replace(/\s+/g, ' ').toLowerCase();
const catalog = new Map([...entries, ...extraEntries, ...messages, ...campaigns].map(entry => [normalize(entry[0]), entry]));
let current: Locale = 'id';
export const getLocale = () => current;
export const setCurrentLocale = (locale: Locale) => { current = locale; };
export const getLocaleTag = () => languages.find(l => l.code === current)!.tag;

// Source phrases are stable message keys, shared by UI and API notifications.
// Never replace words inside arbitrary product names, addresses or user data.
export function translate(text: string, locale: Locale = current): string {
 if (locale === 'id' || !text.trim()) return text;
 const index = languages.findIndex(l => l.code === locale);
 const entry = catalog.get(normalize(text));
 if (entry) return (text.match(/^\s*/)?.[0] || '') + entry[index] + (text.match(/\s*$/)?.[0] || '');
 const patterns: [RegExp, string, (match: RegExpMatchArray) => Record<string, string>][] = [
  [/^Halo, (.+)\.$/, 'Halo, {name}.', m => ({ name: m[1] })],
  [/^Selamat datang, (.+)!$/, 'Selamat datang, {name}!', m => ({ name: m[1] })],
  [/^(\d+) produk siap menemani momenmu\.$/, '{count} produk siap menemani momenmu.', m => ({ count: m[1] })],
  [/^● Tersedia (\d+) produk$/, 'Tersedia {count} produk', m => ({ count: m[1] })],
  [/^Masuk sebagai (.+) · (admin|staff)$/, 'Masuk sebagai {name} · {role}', m => ({ name: m[1], role: m[2] })],
  [/^Stok (.+) tidak mencukupi\.$/, 'Stok {name} tidak mencukupi.', m => ({ name: m[1] })],
  [/^Kolom (.+) wajib diisi\.$/, 'Kolom {field} wajib diisi.', m => ({ field: translate(m[1], locale) })],
  [/^Lihat (.+)$/, 'Lihat {name}', m => ({ name: m[1] })],
  [/^Hapus (.+)$/, 'Hapus {name}', m => ({ name: m[1] })],
  [/^Keranjang, (\d+) produk$/, 'Keranjang, {count} produk', m => ({ count: m[1] })],
 ];
 for (const [pattern, key, values] of patterns) {
  const match = text.match(pattern);
  if (match) return interpolate(key, values(match), locale);
 }
 return text;
}
export function interpolate(key: string, values: Record<string, string | number>, locale: Locale = current) {
 return translate(key, locale).replace(/\{(\w+)\}/g, (token, name) => name in values ? String(values[name]) : token);
}
export const translatedSources = () => [...catalog.values()];
