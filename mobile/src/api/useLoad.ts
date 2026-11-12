import { useEffect, useState } from 'react';
import { api } from './client';
export function useLoad(path: string, initial: any = null) {
 const [data,setData]=useState<any>(initial),[error,setError]=useState(''),[loading,setLoading]=useState(true),[version,setVersion]=useState(0);
 const reload=()=>setVersion(v=>v+1);
 useEffect(()=>{let valid=true;setLoading(true);setError('');api(path).then(d=>{if(valid)setData(d);}).catch(e=>{if(valid)setError(e.message);}).finally(()=>{if(valid)setLoading(false);});return()=>{valid=false;};},[path,version]);
 return {data,setData,error,loading,reload};
}
