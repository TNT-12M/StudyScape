import{D as e}from"./php-modules-GekPVnxA.js";import{L as t,Nt as n,P as r,Y as i,d as a,f as o,h as s,ht as c,k as l,p as u,ut as d,x as f,y as p}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as m,C as h,E as g,F as _,I as v,L as y,Qt as b,S as x,T as S,Xt as C,Yt as w,at as T,ct as E,dt as D,g as O,lt as k,o as A,on as j,pt as M,s as N,sn as P,tn as F}from"./Button-BtFx7EYM.js";import{t as I}from"./attribute-CYKlYlWW.js";var L=f({name:`SlotMachineNumber`,props:{clsPrefix:{type:String,required:!0},value:{type:[Number,String],required:!0},oldOriginalNumber:{type:Number,default:void 0},newOriginalNumber:{type:Number,default:void 0}},setup(e){let n=d(null),r=d(e.value),u=d(e.value),f=d(`up`),p=d(!1),m=a(()=>p.value?`${e.clsPrefix}-base-slot-machine-current-number--${f.value}-scroll`:null),h=a(()=>p.value?`${e.clsPrefix}-base-slot-machine-old-number--${f.value}-scroll`:null);i(c(e,`value`),(e,t)=>{r.value=t,u.value=e,l(g)});function g(){let t=e.newOriginalNumber,n=e.oldOriginalNumber;n!==void 0&&t!==void 0&&(t>n?_(`up`):n>t&&_(`down`))}function _(e){f.value=e,p.value=!1,l(()=>{n.value?.offsetWidth,p.value=!0})}return()=>{let{clsPrefix:i}=e;return t(),s(`span`,{ref:n,class:T(`${i}-base-slot-machine-number`)},[r.value===null?E(()=>null):(t(),s(`span`,{key:0,class:T([`${i}-base-slot-machine-old-number ${i}-base-slot-machine-old-number--top`,h.value])},[E(()=>r.value)],2)),o(`span`,{class:T([`${i}-base-slot-machine-current-number`,m.value])},[o(`span`,{ref:`numberWrapper`,class:T([`${i}-base-slot-machine-current-number__inner`,typeof e.value!=`number`&&`${i}-base-slot-machine-current-number__inner--not-number`])},[E(()=>u.value)],2)],2),r.value===null?E(()=>null):(t(),s(`span`,{key:2,class:T([`${i}-base-slot-machine-old-number ${i}-base-slot-machine-old-number--bottom`,h.value])},[E(()=>r.value)],2))],2)}}}),{cubicBezierEaseOut:R}=D;function z({duration:e=`.2s`}={}){return[w(`&.fade-up-width-expand-transition-leave-active`,{transition:`
 opacity ${e} ${R},
 max-width ${e} ${R},
 transform ${e} ${R}
 `}),w(`&.fade-up-width-expand-transition-enter-active`,{transition:`
 opacity ${e} ${R},
 max-width ${e} ${R},
 transform ${e} ${R}
 `}),w(`&.fade-up-width-expand-transition-enter-to`,{opacity:1,transform:`translateX(0) translateY(0)`}),w(`&.fade-up-width-expand-transition-enter-from`,{maxWidth:`0 !important`,opacity:0,transform:`translateY(60%)`}),w(`&.fade-up-width-expand-transition-leave-from`,{opacity:1,transform:`translateY(0)`}),w(`&.fade-up-width-expand-transition-leave-to`,{maxWidth:`0 !important`,opacity:0,transform:`translateY(60%)`})]}var B=w([w(`@keyframes n-base-slot-machine-fade-up-in`,`
 from {
 transform: translateY(60%);
 opacity: 0;
 }
 to {
 transform: translateY(0);
 opacity: 1;
 }
 `),w(`@keyframes n-base-slot-machine-fade-down-in`,`
 from {
 transform: translateY(-60%);
 opacity: 0;
 }
 to {
 transform: translateY(0);
 opacity: 1;
 }
 `),w(`@keyframes n-base-slot-machine-fade-up-out`,`
 from {
 transform: translateY(0%);
 opacity: 1;
 }
 to {
 transform: translateY(-60%);
 opacity: 0;
 }
 `),w(`@keyframes n-base-slot-machine-fade-down-out`,`
 from {
 transform: translateY(0%);
 opacity: 1;
 }
 to {
 transform: translateY(60%);
 opacity: 0;
 }
 `),C(`base-slot-machine`,`
 overflow: hidden;
 white-space: nowrap;
 display: inline-block;
 height: 18px;
 line-height: 18px;
 `,[C(`base-slot-machine-number`,`
 display: inline-block;
 position: relative;
 height: 18px;
 width: .6em;
 max-width: .6em;
 `,[z({duration:`.2s`}),N({duration:`.2s`,delay:`0s`}),C(`base-slot-machine-old-number`,`
 display: inline-block;
 opacity: 0;
 position: absolute;
 left: 0;
 right: 0;
 `,[m(`top`,{transform:`translateY(-100%)`}),m(`bottom`,{transform:`translateY(100%)`}),m(`down-scroll`,{animation:`n-base-slot-machine-fade-down-out .2s cubic-bezier(0, 0, .2, 1)`,animationIterationCount:1}),m(`up-scroll`,{animation:`n-base-slot-machine-fade-up-out .2s cubic-bezier(0, 0, .2, 1)`,animationIterationCount:1})]),C(`base-slot-machine-current-number`,`
 display: inline-block;
 position: absolute;
 left: 0;
 top: 0;
 bottom: 0;
 right: 0;
 opacity: 1;
 transform: translateY(0);
 width: .6em;
 `,[m(`down-scroll`,{animation:`n-base-slot-machine-fade-down-in .2s cubic-bezier(0, 0, .2, 1)`,animationIterationCount:1}),m(`up-scroll`,{animation:`n-base-slot-machine-fade-up-in .2s cubic-bezier(0, 0, .2, 1)`,animationIterationCount:1}),b(`inner`,`
 display: inline-block;
 position: absolute;
 right: 0;
 top: 0;
 width: .6em;
 `,[m(`not-number`,`
 right: unset;
 left: 0;
 `)])])])])]),V=f({name:`BaseSlotMachine`,props:{clsPrefix:{type:String,required:!0},value:{type:[Number,String],default:0},max:{type:Number,default:void 0},appeared:{type:Boolean,required:!0}},setup(e){k(`-base-slot-machine`,B,c(e,`clsPrefix`));let n=d(),r=d(),o=a(()=>{if(typeof e.value==`string`)return[];if(e.value<1)return[0];let t=[],n=e.value;for(e.max!==void 0&&(n=Math.min(e.max,n));n>=1;)t.push(n%10),n/=10,n=Math.floor(n);return t.reverse(),t});return i(c(e,`value`),(e,t)=>{typeof e==`string`?(r.value=void 0,n.value=void 0):typeof t==`string`?(r.value=e,n.value=void 0):(r.value=e,n.value=t)}),()=>{let{value:i,clsPrefix:a}=e;return typeof i==`number`?(t(),s(`span`,{key:1,class:T(`${a}-base-slot-machine`)},[p(P,{name:`fade-up-width-expand-transition`,tag:`span`},{default:()=>o.value.map((e,i)=>(t(),u(L,{clsPrefix:a,key:o.value.length-i-1,oldOriginalNumber:n.value,newOriginalNumber:r.value,value:e},null,8,[`clsPrefix`,`oldOriginalNumber`,`newOriginalNumber`,`value`])))},1024),p(O,{key:`+`,width:!0},{default:()=>e.max!==void 0&&e.max<i?(t(),u(L,{key:2,clsPrefix:a,value:`+`},null,8,[`clsPrefix`])):null},1024)],2)):(t(),s(`span`,{key:3,class:T(`${a}-base-slot-machine`)},[E(()=>i)],2))}}});function H(e){let{errorColor:t,infoColor:n,successColor:r,warningColor:i,fontFamily:a}=e;return{color:t,colorInfo:n,colorSuccess:r,colorError:t,colorWarning:i,fontSize:`12px`,fontFamily:a}}var U={name:`Badge`,common:y,self:H},W=w([w(`@keyframes badge-wave-spread`,{from:{boxShadow:`0 0 0.5px 0px var(--n-ripple-color)`,opacity:.6},to:{boxShadow:`0 0 0.5px 4.5px var(--n-ripple-color)`,opacity:0}}),C(`badge`,`
 display: inline-flex;
 position: relative;
 vertical-align: middle;
 font-family: var(--n-font-family);
 `,[m(`as-is`,[C(`badge-sup`,{position:`static`,transform:`translateX(0)`},[e({transformOrigin:`left bottom`,originalTransform:`translateX(0)`})])]),m(`dot`,[C(`badge-sup`,`
 height: 8px;
 width: 8px;
 padding: 0;
 min-width: 8px;
 left: 100%;
 bottom: calc(100% - 4px);
 `,[w(`::before`,`border-radius: 4px;`)])]),C(`badge-sup`,`
 background: var(--n-color);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 color: #FFF;
 position: absolute;
 height: 18px;
 line-height: 18px;
 border-radius: 9px;
 padding: 0 6px;
 text-align: center;
 font-size: var(--n-font-size);
 transform: translateX(-50%);
 left: 100%;
 bottom: calc(100% - 9px);
 font-variant-numeric: tabular-nums;
 z-index: 2;
 display: flex;
 align-items: center;
 `,[e({transformOrigin:`left bottom`,originalTransform:`translateX(-50%)`}),C(`base-wave`,{zIndex:1,animationDuration:`2s`,animationIterationCount:`infinite`,animationDelay:`1s`,animationTimingFunction:`var(--n-ripple-bezier)`,animationName:`badge-wave-spread`}),w(`&::before`,`
 opacity: 0;
 transform: scale(1);
 border-radius: 9px;
 content: "";
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `)])])]),G=[`title`],K={..._.props,value:[String,Number],max:Number,dot:Boolean,type:{type:String,default:`default`},show:{type:Boolean,default:!0},showZero:Boolean,processing:Boolean,color:String,offset:Array},q=f({name:`Badge`,props:K,setup(e,{slots:t}){let{mergedClsPrefixRef:n,inlineThemeDisabled:i,mergedRtlRef:o}=M(e),s=_(`Badge`,`-badge`,W,U,e,n),c=d(!1),l=()=>{c.value=!0},u=()=>{c.value=!1},f=a(()=>e.show&&(e.dot||e.value!==void 0&&!(!e.showZero&&Number(e.value)<=0)||!S(t.value)));r(()=>{f.value&&(c.value=!0)});let p=h(`Badge`,o,n),m=a(()=>{let{type:t,color:n}=e,{common:{cubicBezierEaseInOut:r,cubicBezierEaseOut:i},self:{[F(`color`,t)]:a,fontFamily:o,fontSize:c}}=s.value;return{"--n-font-size":c,"--n-font-family":o,"--n-color":n||a,"--n-ripple-color":n||a,"--n-bezier":r,"--n-ripple-bezier":i}}),g=i?v(`badge`,a(()=>{let t=``,{type:n,color:r}=e;return n&&(t+=n[0]),r&&(t+=x(r)),t}),m,e):void 0,y=a(()=>{let{offset:t}=e;if(!t)return;let[n,r]=t,i=typeof n==`number`?`${n}px`:n,a=typeof r==`number`?`${r}px`:r;return{transform:`translate(calc(${p?.value?`50%`:`-50%`} + ${i}), ${a})`}});return{rtlEnabled:p,mergedClsPrefix:n,appeared:c,showBadge:f,handleAfterEnter:l,handleAfterLeave:u,cssVars:i?void 0:m,themeClass:g?.themeClass,onRender:g?.onRender,offsetStyle:y}},render(){let{mergedClsPrefix:e,onRender:r,themeClass:i,$slots:a}=this;r?.();let o=a.default?.();return t(),s(`div`,{class:T([`${e}-badge`,this.rtlEnabled&&`${e}-badge--rtl`,i,{[`${e}-badge--dot`]:this.dot,[`${e}-badge--as-is`]:!o}]),style:n(this.cssVars)},[E(()=>o),(t(),u(j,{name:`fade-in-scale-up-transition`,onAfterEnter:this.handleAfterEnter,onAfterLeave:this.handleAfterLeave},{default:()=>this.showBadge?(t(),s(`sup`,{key:1,class:T(`${e}-badge-sup`),title:I(this.value),style:n(this.offsetStyle)},[E(()=>g(a.value,()=>[this.dot?null:(t(),u(V,{key:2,clsPrefix:e,appeared:this.appeared,max:this.max,value:this.value},null,8,[`clsPrefix`,`appeared`,`max`,`value`]))])),this.processing?(t(),u(A,{key:0,clsPrefix:e},null,8,[`clsPrefix`])):E(()=>null)],14,G)):null},1032,[`onAfterEnter`,`onAfterLeave`]))],6)}});export{q as t};