import{w as e}from"./php-modules-BAZCMuH3.js";import{D as t,F as n,K as r,M as i,_ as a,d as o,dt as s,f as c,kt as l,m as u,ot as d,u as f,y as p}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as m,C as h,E as g,F as _,I as v,L as y,Qt as b,S as x,T as S,Xt as C,Yt as w,at as T,ct as E,dt as D,g as O,lt as k,o as A,on as j,pt as M,s as N,sn as P,tn as F}from"./Button-CjW_3_5b.js";import{t as I}from"./attribute-CYKlYlWW.js";var L=p({name:`SlotMachineNumber`,props:{clsPrefix:{type:String,required:!0},value:{type:[Number,String],required:!0},oldOriginalNumber:{type:Number,default:void 0},newOriginalNumber:{type:Number,default:void 0}},setup(e){let i=d(null),a=d(e.value),c=d(e.value),l=d(`up`),p=d(!1),m=f(()=>p.value?`${e.clsPrefix}-base-slot-machine-current-number--${l.value}-scroll`:null),h=f(()=>p.value?`${e.clsPrefix}-base-slot-machine-old-number--${l.value}-scroll`:null);r(s(e,`value`),(e,n)=>{a.value=n,c.value=e,t(g)});function g(){let t=e.newOriginalNumber,n=e.oldOriginalNumber;n!==void 0&&t!==void 0&&(t>n?_(`up`):n>t&&_(`down`))}function _(e){l.value=e,p.value=!1,t(()=>{i.value?.offsetWidth,p.value=!0})}return()=>{let{clsPrefix:t}=e;return n(),u(`span`,{ref:i,class:T(`${t}-base-slot-machine-number`)},[a.value===null?E(()=>null):(n(),u(`span`,{key:0,class:T([`${t}-base-slot-machine-old-number ${t}-base-slot-machine-old-number--top`,h.value])},[E(()=>a.value)],2)),o(`span`,{class:T([`${t}-base-slot-machine-current-number`,m.value])},[o(`span`,{ref:`numberWrapper`,class:T([`${t}-base-slot-machine-current-number__inner`,typeof e.value!=`number`&&`${t}-base-slot-machine-current-number__inner--not-number`])},[E(()=>c.value)],2)],2),a.value===null?E(()=>null):(n(),u(`span`,{key:2,class:T([`${t}-base-slot-machine-old-number ${t}-base-slot-machine-old-number--bottom`,h.value])},[E(()=>a.value)],2))],2)}}}),{cubicBezierEaseOut:R}=D;function z({duration:e=`.2s`}={}){return[w(`&.fade-up-width-expand-transition-leave-active`,{transition:`
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
 `)])])])])]),V=p({name:`BaseSlotMachine`,props:{clsPrefix:{type:String,required:!0},value:{type:[Number,String],default:0},max:{type:Number,default:void 0},appeared:{type:Boolean,required:!0}},setup(e){k(`-base-slot-machine`,B,s(e,`clsPrefix`));let t=d(),i=d(),o=f(()=>{if(typeof e.value==`string`)return[];if(e.value<1)return[0];let t=[],n=e.value;for(e.max!==void 0&&(n=Math.min(e.max,n));n>=1;)t.push(n%10),n/=10,n=Math.floor(n);return t.reverse(),t});return r(s(e,`value`),(e,n)=>{typeof e==`string`?(i.value=void 0,t.value=void 0):typeof n==`string`?(i.value=e,t.value=void 0):(i.value=e,t.value=n)}),()=>{let{value:r,clsPrefix:s}=e;return typeof r==`number`?(n(),u(`span`,{key:1,class:T(`${s}-base-slot-machine`)},[a(P,{name:`fade-up-width-expand-transition`,tag:`span`},{default:()=>o.value.map((e,r)=>(n(),c(L,{clsPrefix:s,key:o.value.length-r-1,oldOriginalNumber:t.value,newOriginalNumber:i.value,value:e},null,8,[`clsPrefix`,`oldOriginalNumber`,`newOriginalNumber`,`value`])))},1024),a(O,{key:`+`,width:!0},{default:()=>e.max!==void 0&&e.max<r?(n(),c(L,{key:2,clsPrefix:s,value:`+`},null,8,[`clsPrefix`])):null},1024)],2)):(n(),u(`span`,{key:3,class:T(`${s}-base-slot-machine`)},[E(()=>r)],2))}}});function H(e){let{errorColor:t,infoColor:n,successColor:r,warningColor:i,fontFamily:a}=e;return{color:t,colorInfo:n,colorSuccess:r,colorError:t,colorWarning:i,fontSize:`12px`,fontFamily:a}}var U={name:`Badge`,common:y,self:H},W=w([w(`@keyframes badge-wave-spread`,{from:{boxShadow:`0 0 0.5px 0px var(--n-ripple-color)`,opacity:.6},to:{boxShadow:`0 0 0.5px 4.5px var(--n-ripple-color)`,opacity:0}}),C(`badge`,`
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
 `)])])]),G=[`title`],K={..._.props,value:[String,Number],max:Number,dot:Boolean,type:{type:String,default:`default`},show:{type:Boolean,default:!0},showZero:Boolean,processing:Boolean,color:String,offset:Array},q=p({name:`Badge`,props:K,setup(e,{slots:t}){let{mergedClsPrefixRef:n,inlineThemeDisabled:r,mergedRtlRef:a}=M(e),o=_(`Badge`,`-badge`,W,U,e,n),s=d(!1),c=()=>{s.value=!0},l=()=>{s.value=!1},u=f(()=>e.show&&(e.dot||e.value!==void 0&&!(!e.showZero&&Number(e.value)<=0)||!S(t.value)));i(()=>{u.value&&(s.value=!0)});let p=h(`Badge`,a,n),m=f(()=>{let{type:t,color:n}=e,{common:{cubicBezierEaseInOut:r,cubicBezierEaseOut:i},self:{[F(`color`,t)]:a,fontFamily:s,fontSize:c}}=o.value;return{"--n-font-size":c,"--n-font-family":s,"--n-color":n||a,"--n-ripple-color":n||a,"--n-bezier":r,"--n-ripple-bezier":i}}),g=r?v(`badge`,f(()=>{let t=``,{type:n,color:r}=e;return n&&(t+=n[0]),r&&(t+=x(r)),t}),m,e):void 0,y=f(()=>{let{offset:t}=e;if(!t)return;let[n,r]=t,i=typeof n==`number`?`${n}px`:n,a=typeof r==`number`?`${r}px`:r;return{transform:`translate(calc(${p?.value?`50%`:`-50%`} + ${i}), ${a})`}});return{rtlEnabled:p,mergedClsPrefix:n,appeared:s,showBadge:u,handleAfterEnter:c,handleAfterLeave:l,cssVars:r?void 0:m,themeClass:g?.themeClass,onRender:g?.onRender,offsetStyle:y}},render(){let{mergedClsPrefix:e,onRender:t,themeClass:r,$slots:i}=this;t?.();let a=i.default?.();return n(),u(`div`,{class:T([`${e}-badge`,this.rtlEnabled&&`${e}-badge--rtl`,r,{[`${e}-badge--dot`]:this.dot,[`${e}-badge--as-is`]:!a}]),style:l(this.cssVars)},[E(()=>a),(n(),c(j,{name:`fade-in-scale-up-transition`,onAfterEnter:this.handleAfterEnter,onAfterLeave:this.handleAfterLeave},{default:()=>this.showBadge?(n(),u(`sup`,{key:1,class:T(`${e}-badge-sup`),title:I(this.value),style:l(this.offsetStyle)},[E(()=>g(i.value,()=>[this.dot?null:(n(),c(V,{key:2,clsPrefix:e,appeared:this.appeared,max:this.max,value:this.value},null,8,[`clsPrefix`,`appeared`,`max`,`value`]))])),this.processing?(n(),c(A,{key:0,clsPrefix:e},null,8,[`clsPrefix`])):E(()=>null)],14,G)):null},1032,[`onAfterEnter`,`onAfterLeave`]))],6)}});export{q as t};