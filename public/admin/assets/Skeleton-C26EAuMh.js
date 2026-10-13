import{$ as e,Y as t}from"./php-modules-GekPVnxA.js";import{L as n,O as r,a as i,d as a,h as o,w as s,x as c}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{F as l,L as u,Xt as d,Yt as f,ct as p,l as m,pt as h,tn as g}from"./Button-BtFx7EYM.js";var _=!1;function v(){if(m&&window.CSS&&!_&&(_=!0,`registerProperty`in window?.CSS))try{CSS.registerProperty({name:`--n-color-start`,syntax:`<color>`,inherits:!1,initialValue:`#0000`}),CSS.registerProperty({name:`--n-color-end`,syntax:`<color>`,inherits:!1,initialValue:`#0000`})}catch{}}function y(e){let{heightSmall:t,heightMedium:n,heightLarge:r,borderRadius:i}=e;return{color:`#eee`,colorEnd:`#ddd`,borderRadius:i,heightSmall:t,heightMedium:n,heightLarge:r}}var b={name:`Skeleton`,common:u,self:y},x=f([d(`skeleton`,`
 height: 1em;
 width: 100%;
 transition:
 --n-color-start .3s var(--n-bezier),
 --n-color-end .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 animation: 2s skeleton-loading infinite cubic-bezier(0.36, 0, 0.64, 1);
 background-color: var(--n-color-start);
 `),f(`@keyframes skeleton-loading`,`
 0% {
 background: var(--n-color-start);
 }
 40% {
 background: var(--n-color-end);
 }
 80% {
 background: var(--n-color-start);
 }
 100% {
 background: var(--n-color-start);
 }
 `)]),S={...l.props,text:Boolean,round:Boolean,circle:Boolean,height:[String,Number],width:[String,Number],size:String,repeat:{type:Number,default:1},animated:{type:Boolean,default:!0},sharp:{type:Boolean,default:!0}},C=c({name:`Skeleton`,inheritAttrs:!1,props:S,setup(t){v();let{mergedClsPrefixRef:n,mergedComponentPropsRef:r}=h(t),i=a(()=>t.size||r?.value?.Skeleton?.size),o=l(`Skeleton`,`-skeleton`,x,b,t,n);return{mergedClsPrefix:n,style:a(()=>{let n=o.value,{common:{cubicBezierEaseInOut:r}}=n,a=n.self,{color:s,colorEnd:c,borderRadius:l}=a,u,{circle:d,sharp:f,round:p,width:m,height:h,text:_,animated:v}=t,y=i.value;y!==void 0&&(u=a[g(`height`,y)]);let b=d?m??h??u:m,x=(d?m??h:h)??u;return{display:_?`inline-block`:``,verticalAlign:_?`-0.125em`:``,borderRadius:d?`50%`:p?`4096px`:f?``:l,width:typeof b==`number`?e(b):b,height:typeof x==`number`?e(x):x,animation:v?``:`none`,"--n-bezier":r,"--n-color-start":s,"--n-color-end":c}})}},render(){let{repeat:e,style:a,mergedClsPrefix:c,$attrs:l}=this,u=s(`div`,r({class:`${c}-skeleton`,style:a},l));return e>1?(n(),o(i,{key:1},[p(()=>t(e,null).map(e=>[u,`
`]))],64)):u}});export{C as t};