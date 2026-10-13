import{P as e}from"./php-modules-GekPVnxA.js";import{E as t,L as n,M as r,Nt as i,P as a,X as o,Y as s,d as c,h as l,p as u,ut as d,w as f,x as p}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{E as m,F as h,I as g,O as _,Qt as v,S as y,Xt as b,Yt as x,at as S,ct as C,gt as w,nn as T,pt as E,rn as D,tn as O}from"./Button-BtFx7EYM.js";import{C as k}from"./store-DyuIHp0J.js";import{n as A}from"./Tag-B_F6rbWt.js";import{n as j,t as M}from"./utils-DfuaHj3T.js";var N=w(`n-avatar-group`),P=b(`avatar`,`
 width: var(--n-merged-size);
 height: var(--n-merged-size);
 color: #FFF;
 font-size: var(--n-font-size);
 display: inline-flex;
 position: relative;
 overflow: hidden;
 text-align: center;
 border: var(--n-border);
 border-radius: var(--n-border-radius);
 --n-merged-color: var(--n-color);
 background-color: var(--n-merged-color);
 transition:
 border-color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
`,[T(x(`&`,`--n-merged-color: var(--n-color-modal);`)),D(x(`&`,`--n-merged-color: var(--n-color-popover);`)),x(`img`,`
 width: 100%;
 height: 100%;
 `),v(`text`,`
 white-space: nowrap;
 display: inline-block;
 position: absolute;
 left: 50%;
 top: 50%;
 `),b(`icon`,`
 vertical-align: bottom;
 font-size: calc(var(--n-merged-size) - 6px);
 `),v(`text`,`line-height: 1.25`)]),F=[`src`],I={...h.props,size:[String,Number],src:String,circle:{type:Boolean,default:void 0},objectFit:String,round:{type:Boolean,default:void 0},bordered:{type:Boolean,default:void 0},onError:Function,fallbackSrc:String,intersectionObserverOptions:Object,lazy:Boolean,onLoad:Function,renderPlaceholder:Function,renderFallback:Function,imgProps:Object,color:String},L=p({name:`Avatar`,props:I,slots:Object,setup(e){let{mergedClsPrefixRef:n,inlineThemeDisabled:i}=E(e),l=d(!1),u=null,f=d(null),p=d(null),m=()=>{let{value:e}=f;if(e&&(u===null||u!==e.innerHTML)){u=e.innerHTML;let{value:t}=p;if(t){let{offsetWidth:n,offsetHeight:r}=t,{offsetWidth:i,offsetHeight:a}=e,o=.9,s=Math.min(n/i*o,r/a*o,1);e.style.transform=`translateX(-50%) translateY(-50%) scale(${s})`}}},_=t(N,null),v=c(()=>{let{size:t}=e;if(t)return t;let{size:n}=_||{};return n||`medium`}),b=h(`Avatar`,`-avatar`,P,k,e,n),x=t(A,null),S=c(()=>{if(_)return!0;let{round:t,circle:n}=e;return t!==void 0||n!==void 0?t||n:x?x.roundRef.value:!1}),C=c(()=>_?!0:e.bordered||!1),w=c(()=>{let t=v.value,n=S.value,r=C.value,{color:i}=e,{self:{borderRadius:a,fontSize:o,color:s,border:c,colorModal:l,colorPopover:u},common:{cubicBezierEaseInOut:d}}=b.value,f;return f=typeof t==`number`?`${t}px`:b.value.self[O(`height`,t)],{"--n-font-size":o,"--n-border":r?c:`none`,"--n-border-radius":n?`50%`:a,"--n-color":i||s,"--n-color-modal":i||l,"--n-color-popover":i||u,"--n-bezier":d,"--n-merged-size":`var(--n-avatar-size-override, ${f})`}}),T=i?g(`avatar`,c(()=>{let t=v.value,n=S.value,r=C.value,{color:i}=e,a=``;return t&&(a+=typeof t==`number`?`a${t}`:t[0]),n&&(a+=`b`),r&&(a+=`c`),i&&(a+=y(i)),a}),w,e):void 0,D=d(!e.lazy);a(()=>{if(e.lazy&&e.intersectionObserverOptions){let t,n=o(()=>{t?.(),t=void 0,e.lazy&&(t=M(p.value,e.intersectionObserverOptions,D))});r(()=>{n(),t?.()})}}),s(()=>e.src||e.imgProps?.src,()=>{l.value=!1});let j=d(!e.lazy);return{textRef:f,selfRef:p,mergedRoundRef:S,mergedClsPrefix:n,fitTextTransform:m,cssVars:i?void 0:w,themeClass:T?.themeClass,onRender:T?.onRender,hasLoadError:l,shouldStartLoading:D,loaded:j,mergedOnError:t=>{if(!D.value)return;l.value=!0;let{onError:n,imgProps:{onError:r}={}}=e;n?.(t),r?.(t)},mergedOnLoad:t=>{let{onLoad:n,imgProps:{onLoad:r}={}}=e;n?.(t),r?.(t),j.value=!0}}},render(){let{$slots:t,src:r,mergedClsPrefix:a,lazy:o,onRender:s,loaded:c,hasLoadError:d,imgProps:p={}}=this;s?.();let h,g=!c&&!d&&(this.renderPlaceholder?this.renderPlaceholder():this.$slots.placeholder?.());return h=this.hasLoadError?this.renderFallback?this.renderFallback():m(t.fallback,()=>[(n(),l(`img`,{src:this.fallbackSrc,style:i({objectFit:this.objectFit})},null,12,F))]):_(t.default,t=>{if(t)return n(),u(e,{key:1,onResize:this.fitTextTransform},{default:()=>(n(),l(`span`,{ref:`textRef`,class:S(`${a}-avatar__text`)},[C(()=>t)],2))},1032,[`onResize`]);if(r||p.src){let e=this.src||p.src;return f(`img`,{...p,loading:j&&!this.intersectionObserverOptions&&o?`lazy`:`eager`,src:o&&this.intersectionObserverOptions?this.shouldStartLoading?e:void 0:e,"data-image-src":e,onLoad:this.mergedOnLoad,onError:this.mergedOnError,style:[p.style||``,{objectFit:this.objectFit},g?{height:`0`,width:`0`,visibility:`hidden`,position:`absolute`}:``]})}}),n(),l(`span`,{ref:`selfRef`,class:S([`${a}-avatar`,this.themeClass]),style:i(this.cssVars)},[C(()=>h),C(()=>o&&g)],6)}});export{L as t};