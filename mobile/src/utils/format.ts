import { getLocaleTag } from '../i18n/core';
// Currency remains IDR for every language; dates follow the selected locale.
export const rupiah = (value: number | string) => new Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR',maximumFractionDigits:0}).format(Number(value));
export const date = (value: string) => new Date(value.includes('T')?value:value.replace(' ','T')+(value.includes(' ')?'+07:00':'T00:00:00+07:00')).toLocaleDateString(getLocaleTag(), { day:'numeric',month:'long',year:'numeric',timeZone:'Asia/Jakarta' });
export const statuses: Record<string,string> = { menunggu_pembayaran:'Menunggu pembayaran',menunggu_verifikasi:'Menunggu verifikasi',diproses:'Sedang diproses',dikirim:'Dalam pengiriman',selesai:'Selesai',dibatalkan:'Dibatalkan' };
