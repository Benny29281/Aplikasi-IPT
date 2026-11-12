export type Route = { name: 'home'|'welcome'|'catalog'|'product'|'cart'|'checkout'|'orders'|'order'|'profile'|'addresses'|'auth'|'about'|'help'|'age'|'admin'; id?: number; category?: string; q?: string; sale?:boolean };
export const routeHash = (route: Route) => '#'+route.name+(route.id?'/'+route.id:'')+((route.category||route.q||route.sale)?'?'+new URLSearchParams({...route.category?{category:route.category}:{},...route.q?{q:route.q}:{},...route.sale?{sale:'1'}:{}}).toString():'');
export const parseHash = (): Route => {
 if(typeof window==='undefined') return {name:'home'};
 const [path,query] = window.location.hash.slice(1).split('?'); const [name,id]=path.split('/');
 const names=['home','welcome','catalog','product','cart','checkout','orders','order','profile','addresses','auth','about','help','age','admin'];
 return {name:(names.includes(name)?name:'home') as Route['name'],id:id?Number(id):undefined,category:new URLSearchParams(query).get('category')||undefined,q:new URLSearchParams(query).get('q')||undefined,sale:new URLSearchParams(query).get('sale')==='1'};
};
