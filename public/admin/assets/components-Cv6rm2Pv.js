import{B as e,et as t,j as n,q as r}from"./php-modules-jPihHAOi.js";import{A as i,At as a,D as o,E as s,F as c,J as l,K as u,L as d,M as f,N as p,Ot as m,S as h,V as g,Y as _,_ as v,d as y,dt as b,f as x,g as S,i as C,it as ee,kt as w,m as T,mt as E,ot as D,p as O,q as te,tt as ne,u as k,w as A,y as j,z as M}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as N,A as P,E as F,F as I,I as re,M as L,N as ie,O as R,Qt as z,S as ae,Xt as B,Yt as V,at as H,ct as U,dn as oe,en as W,g as se,gt as G,it as K,l as ce,ln as le,nn as ue,pt as de,rn as fe,t as pe,tn as me}from"./Button-CjW_3_5b.js";import{n as he,t as ge}from"./Tabs-CbaG80jM.js";import{r as _e,t as ve}from"./create-C0zrQ-_N.js";import{C as ye,a as be,c as q,g as xe,l as Se,n as Ce,r as we,s as Te,u as Ee,w as De}from"./store-B34YMwPz.js";import{f as Oe,s as J}from"./light-BgIV-Nal.js";import{r as ke}from"./cssr-DQVwT7QU.js";import{a as Ae,i as je,n as Me,t as Y}from"./Dropdown-CjZV_cDU.js";import{n as Ne}from"./Tag-BtfU4pby.js";import{t as Pe}from"./fade-in-height-expand.cssr-QD47wAtv.js";import{n as Fe,t as Ie}from"./utils-DoF92TZP.js";import{t as Le}from"./Tooltip-Cb5kufdJ.js";import{t as Re}from"./Space-rdenqf0s.js";import{g as X,h as ze}from"./useApi-BPuI6ZR9-aAXjkYm3.js";import{t as Be}from"./_plugin-vue_export-helper-BDNMzG2s.js";import{r as Ve,t as He}from"./index-Ck3P-gU-.js";var Ue=G(`n-avatar-group`),We=B(`avatar`,`
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
`,[ue(V(`&`,`--n-merged-color: var(--n-color-modal);`)),fe(V(`&`,`--n-merged-color: var(--n-color-popover);`)),V(`img`,`
 width: 100%;
 height: 100%;
 `),z(`text`,`
 white-space: nowrap;
 display: inline-block;
 position: absolute;
 left: 50%;
 top: 50%;
 `),B(`icon`,`
 vertical-align: bottom;
 font-size: calc(var(--n-merged-size) - 6px);
 `),z(`text`,`line-height: 1.25`)]),Ge=[`src`],Ke={...I.props,size:[String,Number],src:String,circle:{type:Boolean,default:void 0},objectFit:String,round:{type:Boolean,default:void 0},bordered:{type:Boolean,default:void 0},onError:Function,fallbackSrc:String,intersectionObserverOptions:Object,lazy:Boolean,onLoad:Function,renderPlaceholder:Function,renderFallback:Function,imgProps:Object,color:String},qe=j({name:`Avatar`,props:Ke,slots:Object,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=de(e),r=D(!1),a=null,o=D(null),s=D(null),c=()=>{let{value:e}=o;if(e&&(a===null||a!==e.innerHTML)){a=e.innerHTML;let{value:t}=s;if(t){let{offsetWidth:n,offsetHeight:r}=t,{offsetWidth:i,offsetHeight:a}=e,o=.9,s=Math.min(n/i*o,r/a*o,1);e.style.transform=`translateX(-50%) translateY(-50%) scale(${s})`}}},l=A(Ue,null),d=k(()=>{let{size:t}=e;if(t)return t;let{size:n}=l||{};return n||`medium`}),p=I(`Avatar`,`-avatar`,We,De,e,t),m=A(Ne,null),h=k(()=>{if(l)return!0;let{round:t,circle:n}=e;return t!==void 0||n!==void 0?t||n:m?m.roundRef.value:!1}),g=k(()=>l?!0:e.bordered||!1),_=k(()=>{let t=d.value,n=h.value,r=g.value,{color:i}=e,{self:{borderRadius:a,fontSize:o,color:s,border:c,colorModal:l,colorPopover:u},common:{cubicBezierEaseInOut:f}}=p.value,m;return m=typeof t==`number`?`${t}px`:p.value.self[me(`height`,t)],{"--n-font-size":o,"--n-border":r?c:`none`,"--n-border-radius":n?`50%`:a,"--n-color":i||s,"--n-color-modal":i||l,"--n-color-popover":i||u,"--n-bezier":f,"--n-merged-size":`var(--n-avatar-size-override, ${m})`}}),v=n?re(`avatar`,k(()=>{let t=d.value,n=h.value,r=g.value,{color:i}=e,a=``;return t&&(a+=typeof t==`number`?`a${t}`:t[0]),n&&(a+=`b`),r&&(a+=`c`),i&&(a+=ae(i)),a}),_,e):void 0,y=D(!e.lazy);f(()=>{if(e.lazy&&e.intersectionObserverOptions){let t,n=te(()=>{t?.(),t=void 0,e.lazy&&(t=Ie(s.value,e.intersectionObserverOptions,y))});i(()=>{n(),t?.()})}}),u(()=>e.src||e.imgProps?.src,()=>{r.value=!1});let b=D(!e.lazy);return{textRef:o,selfRef:s,mergedRoundRef:h,mergedClsPrefix:t,fitTextTransform:c,cssVars:n?void 0:_,themeClass:v?.themeClass,onRender:v?.onRender,hasLoadError:r,shouldStartLoading:y,loaded:b,mergedOnError:t=>{if(!y.value)return;r.value=!0;let{onError:n,imgProps:{onError:i}={}}=e;n?.(t),i?.(t)},mergedOnLoad:t=>{let{onLoad:n,imgProps:{onLoad:r}={}}=e;n?.(t),r?.(t),b.value=!0}}},render(){let{$slots:e,src:t,mergedClsPrefix:r,lazy:i,onRender:a,loaded:o,hasLoadError:s,imgProps:l={}}=this;a?.();let u,d=!o&&!s&&(this.renderPlaceholder?this.renderPlaceholder():this.$slots.placeholder?.());return u=this.hasLoadError?this.renderFallback?this.renderFallback():F(e.fallback,()=>[(c(),T(`img`,{src:this.fallbackSrc,style:w({objectFit:this.objectFit})},null,12,Ge))]):R(e.default,e=>{if(e)return c(),x(n,{key:1,onResize:this.fitTextTransform},{default:()=>(c(),T(`span`,{ref:`textRef`,class:H(`${r}-avatar__text`)},[U(()=>e)],2))},1032,[`onResize`]);if(t||l.src){let e=this.src||l.src;return h(`img`,{...l,loading:Fe&&!this.intersectionObserverOptions&&i?`lazy`:`eager`,src:i&&this.intersectionObserverOptions?this.shouldStartLoading?e:void 0:e,"data-image-src":e,onLoad:this.mergedOnLoad,onError:this.mergedOnError,style:[l.style||``,{objectFit:this.objectFit},d?{height:`0`,width:`0`,visibility:`hidden`,position:`absolute`}:``]})}}),c(),T(`span`,{ref:`selfRef`,class:H([`${r}-avatar`,this.themeClass]),style:w(this.cssVars)},[U(()=>u),U(()=>i&&d)],6)}}),Je=B(`breadcrumb`,`
 white-space: nowrap;
 cursor: default;
 line-height: var(--n-item-line-height);
`,[V(`ul`,`
 list-style: none;
 padding: 0;
 margin: 0;
 `),V(`a`,`
 color: inherit;
 text-decoration: inherit;
 `),B(`breadcrumb-item`,`
 font-size: var(--n-font-size);
 transition: color .3s var(--n-bezier);
 display: inline-flex;
 align-items: center;
 `,[B(`icon`,`
 font-size: 18px;
 vertical-align: -.2em;
 transition: color .3s var(--n-bezier);
 color: var(--n-item-text-color);
 `),V(`&:not(:last-child)`,[N(`clickable`,[z(`link`,`
 cursor: pointer;
 `,[V(`&:hover`,`
 background-color: var(--n-item-color-hover);
 `),V(`&:active`,`
 background-color: var(--n-item-color-pressed); 
 `)])])]),z(`link`,`
 padding: 4px;
 border-radius: var(--n-item-border-radius);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 color: var(--n-item-text-color);
 position: relative;
 `,[V(`&:hover`,`
 color: var(--n-item-text-color-hover);
 `,[B(`icon`,`
 color: var(--n-item-text-color-hover);
 `)]),V(`&:active`,`
 color: var(--n-item-text-color-pressed);
 `,[B(`icon`,`
 color: var(--n-item-text-color-pressed);
 `)])]),z(`separator`,`
 margin: 0 8px;
 color: var(--n-separator-color);
 transition: color .3s var(--n-bezier);
 user-select: none;
 -webkit-user-select: none;
 `),V(`&:last-child`,[z(`link`,`
 font-weight: var(--n-font-weight-active);
 cursor: unset;
 color: var(--n-item-text-color-active);
 `,[B(`icon`,`
 color: var(--n-item-text-color-active);
 `)]),z(`separator`,`
 display: none;
 `)])])]),Ye=G(`n-breadcrumb`),Xe={...I.props,separator:{type:String,default:`/`}},Ze=j({name:`Breadcrumb`,props:Xe,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=de(e),r=I(`Breadcrumb`,`-breadcrumb`,Je,ye,e,t);d(Ye,{separatorRef:b(e,`separator`),mergedClsPrefixRef:t});let i=k(()=>{let{common:{cubicBezierEaseInOut:e},self:{separatorColor:t,itemTextColor:n,itemTextColorHover:i,itemTextColorPressed:a,itemTextColorActive:o,fontSize:s,fontWeightActive:c,itemBorderRadius:l,itemColorHover:u,itemColorPressed:d,itemLineHeight:f}}=r.value;return{"--n-font-size":s,"--n-bezier":e,"--n-item-text-color":n,"--n-item-text-color-hover":i,"--n-item-text-color-pressed":a,"--n-item-text-color-active":o,"--n-separator-color":t,"--n-item-color-hover":u,"--n-item-color-pressed":d,"--n-item-border-radius":l,"--n-font-weight-active":c,"--n-item-line-height":f}}),a=n?re(`breadcrumb`,void 0,i,e):void 0;return{mergedClsPrefix:t,cssVars:n?void 0:i,themeClass:a?.themeClass,onRender:a?.onRender}},render(){return this.onRender?.(),c(),T(`nav`,{class:H([`${this.mergedClsPrefix}-breadcrumb`,this.themeClass]),style:w(this.cssVars),"aria-label":`Breadcrumb`},[y(`ul`,null,[U(()=>this.$slots.default?.())])],6)}});function Qe(e=ce?window:null){let t=()=>{let{hash:t,host:n,hostname:r,href:i,origin:a,pathname:o,port:s,protocol:c,search:l}=e?.location||{};return{hash:t,host:n,hostname:r,href:i,origin:a,pathname:o,port:s,protocol:c,search:l}},n=D(t()),r=()=>{n.value=t()};return f(()=>{e&&(e.addEventListener(`popstate`,r),e.addEventListener(`hashchange`,r))}),p(()=>{e&&(e.removeEventListener(`popstate`,r),e.removeEventListener(`hashchange`,r))}),n}var $e=j({name:`BreadcrumbItem`,props:{separator:String,href:String,clickable:{type:Boolean,default:!0},showSeparator:{type:Boolean,default:!0},onClick:Function},slots:Object,setup(e,{slots:t}){let n=A(Ye,null);if(!n)return()=>null;let{separatorRef:r,mergedClsPrefixRef:i}=n,a=Qe(),o=k(()=>e.href?`a`:`span`),s=k(()=>a.value.href===e.href?`location`:null);return()=>{let{value:n}=i;return c(),T(`li`,{class:H([`${n}-breadcrumb-item`,e.clickable&&`${n}-breadcrumb-item--clickable`])},[U(()=>h(o.value,{class:`${n}-breadcrumb-item__link`,"aria-current":s.value,href:e.href,onClick:e.onClick},t)),U(()=>e.showSeparator&&(c(),T(`span`,{class:H(`${n}-breadcrumb-item__separator`),"aria-hidden":`true`},[U(()=>F(t.separator,()=>[e.separator??r.value]))],2)))],2)}}}),et=[`value`,`name`,`checked`,`disabled`,`onChange`,`onFocus`,`onBlur`],tt=j({name:`RadioButton`,props:je,setup:Ae,render(){let{mergedClsPrefix:e}=this;return c(),T(`label`,{class:H([`${e}-radio-button`,this.mergedDisabled&&`${e}-radio-button--disabled`,this.renderSafeChecked&&`${e}-radio-button--checked`,this.focus&&[`${e}-radio-button--focus`]])},[y(`input`,{ref:`inputRef`,type:`radio`,class:H(`${e}-radio-input`),value:this.value,name:this.mergedName,checked:this.renderSafeChecked,disabled:this.mergedDisabled,onChange:this.handleRadioInputChange,onFocus:this.handleRadioInputFocus,onBlur:this.handleRadioInputBlur},null,42,et),y(`div`,{class:H(`${e}-radio-button__state-border`)},null,2),U(()=>R(this.$slots.default,t=>!t&&!this.label?null:(c(),T(`div`,{ref:`labelRef`,class:H(`${e}-radio__label`)},[U(()=>t||this.label)],2))))],2)}}),nt=G(`n-layout-sider`),Z=G(`n-menu`),rt=G(`n-submenu`),it=G(`n-menu-item-group`),at=[V(`&::before`,`background-color: var(--n-item-color-hover);`),z(`arrow`,`
 color: var(--n-arrow-color-hover);
 `),z(`icon`,`
 color: var(--n-item-icon-color-hover);
 `),B(`menu-item-content-header`,`
 color: var(--n-item-text-color-hover);
 `,[V(`a`,`
 color: var(--n-item-text-color-hover);
 `),z(`extra`,`
 color: var(--n-item-text-color-hover);
 `)])],ot=[z(`icon`,`
 color: var(--n-item-icon-color-hover-horizontal);
 `),B(`menu-item-content-header`,`
 color: var(--n-item-text-color-hover-horizontal);
 `,[V(`a`,`
 color: var(--n-item-text-color-hover-horizontal);
 `),z(`extra`,`
 color: var(--n-item-text-color-hover-horizontal);
 `)])],st=V([B(`menu`,`
 background-color: var(--n-color);
 color: var(--n-item-text-color);
 overflow: hidden;
 transition: background-color .3s var(--n-bezier);
 box-sizing: border-box;
 font-size: var(--n-font-size);
 padding-bottom: 6px;
 `,[N(`horizontal`,`
 max-width: 100%;
 width: 100%;
 display: flex;
 overflow: hidden;
 padding-bottom: 0;
 `,[B(`submenu`,`margin: 0;`),B(`menu-item`,`margin: 0;`),B(`menu-item-content`,`
 padding: 0 20px;
 border-bottom: 2px solid #0000;
 `,[V(`&::before`,`display: none;`),N(`selected`,`border-bottom: 2px solid var(--n-border-color-horizontal)`)]),B(`menu-item-content`,[N(`selected`,[z(`icon`,`color: var(--n-item-icon-color-active-horizontal);`),B(`menu-item-content-header`,`
 color: var(--n-item-text-color-active-horizontal);
 `,[V(`a`,`color: var(--n-item-text-color-active-horizontal);`),z(`extra`,`color: var(--n-item-text-color-active-horizontal);`)])]),N(`child-active`,`
 border-bottom: 2px solid var(--n-border-color-horizontal);
 `,[B(`menu-item-content-header`,`
 color: var(--n-item-text-color-child-active-horizontal);
 `,[V(`a`,`
 color: var(--n-item-text-color-child-active-horizontal);
 `),z(`extra`,`
 color: var(--n-item-text-color-child-active-horizontal);
 `)]),z(`icon`,`
 color: var(--n-item-icon-color-child-active-horizontal);
 `)]),W(`disabled`,[W(`selected, child-active`,[V(`&:focus-within`,ot)]),N(`selected`,[Q(null,[z(`icon`,`color: var(--n-item-icon-color-active-hover-horizontal);`),B(`menu-item-content-header`,`
 color: var(--n-item-text-color-active-hover-horizontal);
 `,[V(`a`,`color: var(--n-item-text-color-active-hover-horizontal);`),z(`extra`,`color: var(--n-item-text-color-active-hover-horizontal);`)])])]),N(`child-active`,[Q(null,[z(`icon`,`color: var(--n-item-icon-color-child-active-hover-horizontal);`),B(`menu-item-content-header`,`
 color: var(--n-item-text-color-child-active-hover-horizontal);
 `,[V(`a`,`color: var(--n-item-text-color-child-active-hover-horizontal);`),z(`extra`,`color: var(--n-item-text-color-child-active-hover-horizontal);`)])])]),Q(`border-bottom: 2px solid var(--n-border-color-horizontal);`,ot)]),B(`menu-item-content-header`,[V(`a`,`color: var(--n-item-text-color-horizontal);`)])])]),W(`responsive`,[B(`menu-item-content-header`,`
 overflow: hidden;
 text-overflow: ellipsis;
 `)]),N(`collapsed`,[B(`menu-item-content`,[N(`selected`,[V(`&::before`,`
 background-color: var(--n-item-color-active-collapsed) !important;
 `)]),B(`menu-item-content-header`,`opacity: 0;`),z(`arrow`,`opacity: 0;`),z(`icon`,`color: var(--n-item-icon-color-collapsed);`)])]),B(`menu-item`,`
 height: var(--n-item-height);
 margin-top: 6px;
 position: relative;
 `),B(`menu-item-content`,`
 box-sizing: border-box;
 line-height: 1.75;
 height: 100%;
 display: grid;
 grid-template-areas: "icon content arrow";
 grid-template-columns: auto 1fr auto;
 align-items: center;
 cursor: pointer;
 position: relative;
 padding-right: 18px;
 transition:
 background-color .3s var(--n-bezier),
 padding-left .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[V(`> *`,`z-index: 1;`),V(`&::before`,`
 z-index: auto;
 content: "";
 background-color: #0000;
 position: absolute;
 left: 8px;
 right: 8px;
 top: 0;
 bottom: 0;
 pointer-events: none;
 border-radius: var(--n-border-radius);
 transition: background-color .3s var(--n-bezier);
 `),N(`disabled`,`
 opacity: .45;
 cursor: not-allowed;
 `),N(`collapsed`,[z(`arrow`,`transform: rotate(0);`)]),N(`selected`,[V(`&::before`,`background-color: var(--n-item-color-active);`),z(`arrow`,`color: var(--n-arrow-color-active);`),z(`icon`,`color: var(--n-item-icon-color-active);`),B(`menu-item-content-header`,`
 color: var(--n-item-text-color-active);
 `,[V(`a`,`color: var(--n-item-text-color-active);`),z(`extra`,`color: var(--n-item-text-color-active);`)])]),N(`child-active`,[B(`menu-item-content-header`,`
 color: var(--n-item-text-color-child-active);
 `,[V(`a`,`
 color: var(--n-item-text-color-child-active);
 `),z(`extra`,`
 color: var(--n-item-text-color-child-active);
 `)]),z(`arrow`,`
 color: var(--n-arrow-color-child-active);
 `),z(`icon`,`
 color: var(--n-item-icon-color-child-active);
 `)]),W(`disabled`,[W(`selected, child-active`,[V(`&:focus-within`,at)]),N(`selected`,[Q(null,[z(`arrow`,`color: var(--n-arrow-color-active-hover);`),z(`icon`,`color: var(--n-item-icon-color-active-hover);`),B(`menu-item-content-header`,`
 color: var(--n-item-text-color-active-hover);
 `,[V(`a`,`color: var(--n-item-text-color-active-hover);`),z(`extra`,`color: var(--n-item-text-color-active-hover);`)])])]),N(`child-active`,[Q(null,[z(`arrow`,`color: var(--n-arrow-color-child-active-hover);`),z(`icon`,`color: var(--n-item-icon-color-child-active-hover);`),B(`menu-item-content-header`,`
 color: var(--n-item-text-color-child-active-hover);
 `,[V(`a`,`color: var(--n-item-text-color-child-active-hover);`),z(`extra`,`color: var(--n-item-text-color-child-active-hover);`)])])]),N(`selected`,[Q(null,[V(`&::before`,`background-color: var(--n-item-color-active-hover);`)])]),Q(null,at)]),z(`icon`,`
 grid-area: icon;
 color: var(--n-item-icon-color);
 transition:
 color .3s var(--n-bezier),
 font-size .3s var(--n-bezier),
 margin-right .3s var(--n-bezier);
 box-sizing: content-box;
 display: inline-flex;
 align-items: center;
 justify-content: center;
 `),z(`arrow`,`
 grid-area: arrow;
 font-size: 16px;
 color: var(--n-arrow-color);
 transform: rotate(180deg);
 opacity: 1;
 transition:
 color .3s var(--n-bezier),
 transform 0.2s var(--n-bezier),
 opacity 0.2s var(--n-bezier);
 `),B(`menu-item-content-header`,`
 grid-area: content;
 transition:
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 opacity: 1;
 white-space: nowrap;
 color: var(--n-item-text-color);
 `,[V(`a`,`
 outline: none;
 text-decoration: none;
 transition: color .3s var(--n-bezier);
 color: var(--n-item-text-color);
 `,[V(`&::before`,`
 content: "";
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `)]),z(`extra`,`
 font-size: .93em;
 color: var(--n-group-text-color);
 transition: color .3s var(--n-bezier);
 `)])]),B(`submenu`,`
 cursor: pointer;
 position: relative;
 margin-top: 6px;
 `,[B(`menu-item-content`,`
 height: var(--n-item-height);
 `),B(`submenu-children`,`
 overflow: hidden;
 padding: 0;
 `,[Pe({duration:`.2s`})])]),B(`menu-item-group`,[B(`menu-item-group-title`,`
 margin-top: 6px;
 color: var(--n-group-text-color);
 cursor: default;
 font-size: .93em;
 height: 36px;
 display: flex;
 align-items: center;
 transition:
 padding-left .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `)])]),B(`menu-tooltip`,[V(`a`,`
 color: inherit;
 text-decoration: none;
 `)]),B(`menu-divider`,`
 transition: background-color .3s var(--n-bezier);
 background-color: var(--n-divider-color);
 height: 1px;
 margin: 6px 18px;
 `)]);function Q(e,t){return[N(`hover`,e,t),V(`&:hover`,e,t)]}var ct=j({name:`MenuDivider`,setup(){let{mergedClsPrefixRef:e,isHorizontalRef:t}=A(Z);return()=>t.value?null:(c(),T(`div`,{key:1,class:H(`${e.value}-menu-divider`)},null,2))}}),lt=j({name:`ChevronDownFilled`,render(){return(()=>{let e=K(`f3af82a2aab086a5`);return e[0]||=y(`svg`,{viewBox:`0 0 16 16`,fill:`none`,xmlns:`http://www.w3.org/2000/svg`},[y(`path`,{d:`M3.20041 5.73966C3.48226 5.43613 3.95681 5.41856 4.26034 5.70041L8 9.22652L11.7397 5.70041C12.0432 5.41856 12.5177 5.43613 12.7996 5.73966C13.0815 6.0432 13.0639 6.51775 12.7603 6.7996L8.51034 10.7996C8.22258 11.0668 7.77743 11.0668 7.48967 10.7996L3.23966 6.7996C2.93613 6.51775 2.91856 6.0432 3.20041 5.73966Z`,fill:`currentColor`})],-1)})()}}),ut=[`onClick`],dt=j({name:`MenuOptionContent`,props:{collapsed:Boolean,disabled:Boolean,title:[String,Function],icon:Function,extra:[String,Function],showArrow:Boolean,childActive:Boolean,hover:Boolean,paddingLeft:Number,selected:Boolean,maxIconSize:{type:Number,required:!0},activeIconSize:{type:Number,required:!0},iconMarginRight:{type:Number,required:!0},clsPrefix:{type:String,required:!0},onClick:Function,tmNode:{type:Object,required:!0},isEllipsisPlaceholder:Boolean},setup(e){let{props:t}=A(Z);return{menuProps:t,style:k(()=>{let{paddingLeft:t}=e;return{paddingLeft:t&&`${t}px`}}),iconStyle:k(()=>{let{maxIconSize:t,activeIconSize:n,iconMarginRight:r}=e;return{width:`${t}px`,height:`${t}px`,fontSize:`${n}px`,marginRight:`${r}px`}})}},render(){let{clsPrefix:e,tmNode:t,menuProps:{renderIcon:n,renderLabel:r,renderExtra:i,expandIcon:a}}=this,o=n?n(t.rawNode):J(this.icon);return(()=>{let n=K(`7bb10afc6caf8fa4`);return c(),T(`div`,{onClick:e=>{this.onClick?.(e)},role:`none`,class:H([`${e}-menu-item-content`,{[`${e}-menu-item-content--selected`]:this.selected,[`${e}-menu-item-content--collapsed`]:this.collapsed,[`${e}-menu-item-content--child-active`]:this.childActive,[`${e}-menu-item-content--disabled`]:this.disabled,[`${e}-menu-item-content--hover`]:this.hover}]),style:w(this.style)},[U(()=>o&&(c(),T(`div`,{class:H(`${e}-menu-item-content__icon`),style:w(this.iconStyle),role:`none`},[U(()=>[o])],6))),y(`div`,{class:H(`${e}-menu-item-content-header`),role:`none`},[this.isEllipsisPlaceholder?(c(),T(C,{key:0},[U(()=>this.title)],64)):(c(),T(C,{key:1},[r?(c(),T(C,{key:0},[U(()=>r(t.rawNode))],64)):(c(),T(C,{key:1},[U(()=>J(this.title))],64))],64)),this.extra||i?(c(),T(`span`,{key:2,class:H(`${e}-menu-item-content-header__extra`)},[n[0]||=U(` `,-1),i?(c(),T(C,{key:0},[U(()=>i(t.rawNode))],64)):(c(),T(C,{key:1},[U(()=>J(this.extra))],64))],2)):U(()=>null)],2),this.showArrow?(c(),x(ie,{key:0,ariaHidden:!0,class:H(`${e}-menu-item-content__arrow`),clsPrefix:e},{default:()=>a?a(t.rawNode):(c(),x(lt,{key:1}))},1032,[`class`,`clsPrefix`])):U(()=>null)],14,ut)})()}}),ft=8;function pt(e){let t=A(Z),{props:n,mergedCollapsedRef:r}=t,i=A(rt,null),a=A(it,null),o=k(()=>n.mode===`horizontal`),s=k(()=>o.value?n.dropdownPlacement:`tmNodes`in e?`right-start`:`right`),c=k(()=>Math.max(n.collapsedIconSize??n.iconSize,n.iconSize));return{dropdownPlacement:s,activeIconSize:k(()=>!o.value&&e.root&&r.value?n.collapsedIconSize??n.iconSize:n.iconSize),maxIconSize:c,paddingLeft:k(()=>{if(o.value)return;let{collapsedWidth:t,indent:s,rootIndent:l}=n,{root:u,isGroup:d}=e,f=l===void 0?s:l;return u?r.value?t/2-c.value/2:f:a&&typeof a.paddingLeftRef.value==`number`?r.value?t/2-c.value/2:s/2+a.paddingLeftRef.value:i&&typeof i.paddingLeftRef.value==`number`?(d?s/2:s)+i.paddingLeftRef.value:0}),iconMarginRight:k(()=>{let{collapsedWidth:t,indent:i,rootIndent:a}=n,{value:s}=c,{root:l}=e;return o.value||!l||!r.value?ft:(a===void 0?i:a)+s+ft-(t+s)/2}),NMenu:t,NSubmenu:i,NMenuOptionGroup:a}}var mt={internalKey:{type:[String,Number],required:!0},root:Boolean,isGroup:Boolean,level:{type:Number,required:!0},title:[String,Function],extra:[String,Function]},ht={...mt,tmNode:{type:Object,required:!0},disabled:Boolean,icon:Function,onClick:Function},gt=t(ht),_t=j({name:`MenuOption`,props:ht,setup(e){let t=pt(e),{NSubmenu:n,NMenu:r,NMenuOptionGroup:i}=t,{props:a,mergedClsPrefixRef:o,mergedCollapsedRef:s}=r,c=n?n.mergedDisabledRef:i?i.mergedDisabledRef:{value:!1},l=k(()=>c.value||e.disabled);function u(t){let{onClick:n}=e;n&&n(t)}function d(t){l.value||(r.doSelect(e.internalKey,e.tmNode.rawNode),u(t))}return{mergedClsPrefix:o,dropdownPlacement:t.dropdownPlacement,paddingLeft:t.paddingLeft,iconMarginRight:t.iconMarginRight,maxIconSize:t.maxIconSize,activeIconSize:t.activeIconSize,mergedTheme:r.mergedThemeRef,menuProps:a,dropdownEnabled:L(()=>e.root&&s.value&&a.mode!==`horizontal`&&!l.value),selected:L(()=>r.mergedValueRef.value===e.internalKey),mergedDisabled:l,handleClick:d}},render(){let{mergedClsPrefix:e,mergedTheme:t,tmNode:n,menuProps:{renderLabel:r,nodeProps:i}}=this,a=i?.(n.rawNode);return c(),T(`div`,s(a,{role:`menuitem`,class:[`${e}-menu-item`,a?.class]}),[(c(),x(Le,{theme:t.peers.Tooltip,themeOverrides:t.peerOverrides.Tooltip,trigger:`hover`,placement:this.dropdownPlacement,disabled:!this.dropdownEnabled||this.title===void 0,internalExtraClass:[`menu-tooltip`]},{default:()=>r?r(n.rawNode):J(this.title),trigger:()=>(c(),x(dt,{tmNode:n,clsPrefix:e,paddingLeft:this.paddingLeft,iconMarginRight:this.iconMarginRight,maxIconSize:this.maxIconSize,activeIconSize:this.activeIconSize,selected:this.selected,title:this.title,extra:this.extra,disabled:this.mergedDisabled,icon:this.icon,onClick:this.handleClick},null,8,[`tmNode`,`clsPrefix`,`paddingLeft`,`iconMarginRight`,`maxIconSize`,`activeIconSize`,`selected`,`title`,`extra`,`disabled`,`icon`,`onClick`]))},1032,[`theme`,`themeOverrides`,`placement`,`disabled`]))],16)}}),vt={...mt,tmNode:{type:Object,required:!0},tmNodes:{type:Array,required:!0}},yt=t(vt),bt=j({name:`MenuOptionGroup`,props:vt,setup(e){let t=pt(e),{NSubmenu:n}=t,r=k(()=>n?.mergedDisabledRef.value?!0:e.tmNode.disabled);d(it,{paddingLeftRef:t.paddingLeft,mergedDisabledRef:r});let{mergedClsPrefixRef:i,props:a}=A(Z);return function(){let{value:n}=i,r=t.paddingLeft.value,{nodeProps:o}=a,l=o?.(e.tmNode.rawNode);return(()=>{let t=K(`45eca6a63be5028b`);return c(),T(`div`,{class:H(`${n}-menu-item-group`),role:`group`},[y(`div`,s(l,{class:[`${n}-menu-item-group-title`,l?.class],style:[l?.style||``,r===void 0?``:`padding-left: ${r}px;`]}),[U(()=>J(e.title)),e.extra?(c(),T(C,{key:0},[t[0]||=U(` `,-1),U(()=>J(e.extra))],64)):U(()=>null)],16),y(`div`,null,[U(()=>e.tmNodes.map(e=>Dt(e,a)))])],2)})()}}}),xt=[`aria-expanded`,`id`],St=[`aria-expanded`,`id`],Ct={...mt,rawNodes:{type:Array,default:()=>[]},tmNodes:{type:Array,default:()=>[]},tmNode:{type:Object,required:!0},disabled:Boolean,icon:Function,onClick:Function,domId:String,virtualChildActive:{type:Boolean,default:void 0},isEllipsisPlaceholder:Boolean},wt=t(Ct),Tt=j({name:`Submenu`,props:Ct,setup(e){let t=pt(e),{NMenu:n,NSubmenu:r}=t,{props:i,mergedCollapsedRef:a,mergedThemeRef:o}=n,s=k(()=>{let{disabled:t}=e;return r?.mergedDisabledRef.value||i.disabled?!0:t}),c=D(!1);d(rt,{paddingLeftRef:t.paddingLeft,mergedDisabledRef:s}),d(it,null);function l(){let{onClick:t}=e;t&&t()}function u(){s.value||(a.value||n.toggleExpand(e.internalKey),l())}function f(e){c.value=e}return{menuProps:i,mergedTheme:o,doSelect:n.doSelect,inverted:n.invertedRef,isHorizontal:n.isHorizontalRef,mergedClsPrefix:n.mergedClsPrefixRef,maxIconSize:t.maxIconSize,activeIconSize:t.activeIconSize,iconMarginRight:t.iconMarginRight,dropdownPlacement:t.dropdownPlacement,dropdownShow:c,paddingLeft:t.paddingLeft,mergedDisabled:s,mergedValue:n.mergedValueRef,childActive:L(()=>e.virtualChildActive??n.activePathRef.value.includes(e.internalKey)),collapsed:k(()=>i.mode===`horizontal`?!1:a.value?!0:!n.mergedExpandedKeysRef.value.includes(e.internalKey)),dropdownEnabled:k(()=>!s.value&&(i.mode===`horizontal`||a.value)),handlePopoverShowChange:f,handleClick:u}},render(){let{mergedClsPrefix:e,menuProps:{renderIcon:t,renderLabel:n}}=this,r=()=>{let{isHorizontal:e,paddingLeft:t,collapsed:n,mergedDisabled:r,maxIconSize:i,activeIconSize:a,title:o,childActive:l,icon:u,handleClick:d,menuProps:{nodeProps:f},dropdownShow:p,iconMarginRight:m,tmNode:h,mergedClsPrefix:g,isEllipsisPlaceholder:_,extra:v}=this,y=f?.(h.rawNode);return c(),T(`div`,s(y,{class:[`${g}-menu-item`,y?.class],role:`menuitem`}),[(c(),x(dt,{tmNode:h,paddingLeft:t,collapsed:n,disabled:r,iconMarginRight:m,maxIconSize:i,activeIconSize:a,title:o,extra:v,showArrow:!e,childActive:l,clsPrefix:g,icon:u,hover:p,onClick:d,isEllipsisPlaceholder:_},null,8,[`tmNode`,`paddingLeft`,`collapsed`,`disabled`,`iconMarginRight`,`maxIconSize`,`activeIconSize`,`title`,`extra`,`showArrow`,`childActive`,`clsPrefix`,`icon`,`hover`,`onClick`,`isEllipsisPlaceholder`]))],16)},i=()=>(c(),x(se,null,{default:()=>{let{tmNodes:t,collapsed:n}=this;return n?null:(c(),T(`div`,{key:1,class:H(`${e}-submenu-children`),role:`menu`},[U(()=>t.map(e=>Dt(e,this.menuProps)))],2))}},1024));return this.root?(c(),x(Y,s({key:2,size:`large`,trigger:`hover`},this.menuProps?.dropdownProps,{themeOverrides:this.mergedTheme.peerOverrides.Dropdown,theme:this.mergedTheme.peers.Dropdown,builtinThemeOverrides:{fontSizeLarge:`14px`,optionIconSizeLarge:`18px`},value:this.mergedValue,disabled:!this.dropdownEnabled,placement:this.dropdownPlacement,keyField:this.menuProps.keyField,labelField:this.menuProps.labelField,childrenField:this.menuProps.childrenField,onUpdateShow:this.handlePopoverShowChange,options:this.rawNodes,onSelect:this.doSelect,inverted:this.inverted,renderIcon:t,renderLabel:n}),{default:()=>(c(),T(`div`,{class:H(`${e}-submenu`),role:`menu`,"aria-expanded":!this.collapsed,id:this.domId},[U(()=>r()),this.isHorizontal?U(()=>null):(c(),T(C,{key:1},[U(()=>i())],64))],10,xt))},1040,[`themeOverrides`,`theme`,`value`,`disabled`,`placement`,`keyField`,`labelField`,`childrenField`,`onUpdateShow`,`options`,`onSelect`,`inverted`,`renderIcon`,`renderLabel`])):(c(),T(`div`,{key:3,class:H(`${e}-submenu`),role:`menu`,"aria-expanded":!this.collapsed,id:this.domId},[U(()=>r()),U(()=>i())],10,St))}});function $(e){return e.type===`divider`||e.type===`render`}function Et(e){return e.type===`divider`}function Dt(e,t){let{rawNode:n}=e,{show:r}=n;if(r===!1)return null;if($(n))return Et(n)?(c(),x(ct,s({key:e.key},n.props),null,16)):null;let{labelField:i}=t,{key:a,level:o,isGroup:l}=e,u={...n,title:n.title||n[i],extra:n.titleExtra||n.extra,key:a,internalKey:a,level:o,root:o===0,isGroup:l};return e.children?e.isGroup?h(bt,Oe(u,yt,{tmNode:e,tmNodes:e.children,key:a})):h(Tt,Oe(u,wt,{key:a,rawNodes:n[t.childrenField],tmNodes:e.children,tmNode:e})):h(_t,Oe(u,gt,{key:a,tmNode:e}))}var Ot={...I.props,options:{type:Array,default:()=>[]},collapsed:{type:Boolean,default:void 0},collapsedWidth:{type:Number,default:48},iconSize:{type:Number,default:20},collapsedIconSize:{type:Number,default:24},rootIndent:Number,indent:{type:Number,default:32},labelField:{type:String,default:`label`},keyField:{type:String,default:`key`},childrenField:{type:String,default:`children`},disabledField:{type:String,default:`disabled`},defaultExpandAll:Boolean,defaultExpandedKeys:Array,expandedKeys:Array,value:[String,Number],defaultValue:{type:[String,Number],default:null},mode:{type:String,default:`vertical`},watchProps:{type:Array,default:void 0},disabled:Boolean,show:{type:Boolean,default:!0},inverted:Boolean,"onUpdate:expandedKeys":[Function,Array],onUpdateExpandedKeys:[Function,Array],onUpdateValue:[Function,Array],"onUpdate:value":[Function,Array],expandIcon:Function,renderIcon:Function,renderLabel:Function,renderExtra:Function,dropdownProps:Object,accordion:Boolean,nodeProps:Function,dropdownPlacement:{type:String,default:`bottom`},responsive:Boolean,items:Array,onOpenNamesChange:[Function,Array],onSelect:[Function,Array],onExpandedNamesChange:[Function,Array],expandedNames:Array,defaultExpandedNames:Array},kt=j({name:`Menu`,inheritAttrs:!1,props:Ot,setup(t){let{mergedClsPrefixRef:n,inlineThemeDisabled:i}=de(t),a=I(`Menu`,`-menu`,st,xe,t,n),o=A(nt,null),s=k(()=>{let{collapsed:e}=t;if(e!==void 0)return e;if(o){let{collapseModeRef:e,collapsedRef:t}=o;if(e.value===`width`)return t.value??!1}return!1}),l=k(()=>{let{keyField:e,childrenField:n,disabledField:r}=t;return ve(t.items||t.options,{getIgnored(e){return $(e)},getChildren(e){return e[n]},getDisabled(e){return e[r]},getKey(t){return t[e]??t.name}})}),u=k(()=>new Set(l.value.treeNodes.map(e=>e.key))),{watchProps:f}=t,p=D(null);f?.includes(`defaultValue`)?te(()=>{p.value=t.defaultValue}):p.value=t.defaultValue;let m=b(t,`value`),h=e(m,p),g=D([]),_=()=>{g.value=t.defaultExpandAll?l.value.getNonLeafKeys():t.defaultExpandedNames||t.defaultExpandedKeys||l.value.getPath(h.value,{includeSelf:!1}).keyPath};f?.includes(`defaultExpandedKeys`)?te(_):_();let v=ke(t,[`expandedNames`,`expandedKeys`]),y=e(v,g),S=k(()=>l.value.treeNodes),C=k(()=>l.value.getPath(h.value).keyPath);d(Z,{props:t,mergedCollapsedRef:s,mergedThemeRef:a,mergedValueRef:h,mergedExpandedKeysRef:y,activePathRef:C,mergedClsPrefixRef:n,isHorizontalRef:k(()=>t.mode===`horizontal`),invertedRef:b(t,`inverted`),doSelect:ee,toggleExpand:T});function ee(e,n){let{"onUpdate:value":r,onUpdateValue:i,onSelect:a}=t;i&&P(i,e,n),r&&P(r,e,n),a&&P(a,e,n),p.value=e}function w(e){let{"onUpdate:expandedKeys":n,onUpdateExpandedKeys:r,onExpandedNamesChange:i,onOpenNamesChange:a}=t;n&&P(n,e),r&&P(r,e),i&&P(i,e),a&&P(a,e),g.value=e}function T(e){let n=Array.from(y.value),r=n.findIndex(t=>t===e);if(~r)n.splice(r,1);else{if(t.accordion&&u.value.has(e)){let e=n.findIndex(e=>u.value.has(e));e>-1&&n.splice(e,1)}n.push(e)}w(n)}let E=e=>{let n=l.value.getPath(e??h.value,{includeSelf:!1}).keyPath;if(!n.length)return;let r=Array.from(y.value),i=new Set([...r,...n]);t.accordion&&u.value.forEach(e=>{i.has(e)&&!n.includes(e)&&i.delete(e)}),w(Array.from(i))},O=k(()=>{let{inverted:e}=t,{common:{cubicBezierEaseInOut:n},self:r}=a.value,{borderRadius:i,borderColorHorizontal:o,fontSize:s,itemHeight:c,dividerColor:l}=r,u={"--n-divider-color":l,"--n-bezier":n,"--n-font-size":s,"--n-border-color-horizontal":o,"--n-border-radius":i,"--n-item-height":c};return e?(u[`--n-group-text-color`]=r.groupTextColorInverted,u[`--n-color`]=r.colorInverted,u[`--n-item-text-color`]=r.itemTextColorInverted,u[`--n-item-text-color-hover`]=r.itemTextColorHoverInverted,u[`--n-item-text-color-active`]=r.itemTextColorActiveInverted,u[`--n-item-text-color-child-active`]=r.itemTextColorChildActiveInverted,u[`--n-item-text-color-child-active-hover`]=r.itemTextColorChildActiveInverted,u[`--n-item-text-color-active-hover`]=r.itemTextColorActiveHoverInverted,u[`--n-item-icon-color`]=r.itemIconColorInverted,u[`--n-item-icon-color-hover`]=r.itemIconColorHoverInverted,u[`--n-item-icon-color-active`]=r.itemIconColorActiveInverted,u[`--n-item-icon-color-active-hover`]=r.itemIconColorActiveHoverInverted,u[`--n-item-icon-color-child-active`]=r.itemIconColorChildActiveInverted,u[`--n-item-icon-color-child-active-hover`]=r.itemIconColorChildActiveHoverInverted,u[`--n-item-icon-color-collapsed`]=r.itemIconColorCollapsedInverted,u[`--n-item-text-color-horizontal`]=r.itemTextColorHorizontalInverted,u[`--n-item-text-color-hover-horizontal`]=r.itemTextColorHoverHorizontalInverted,u[`--n-item-text-color-active-horizontal`]=r.itemTextColorActiveHorizontalInverted,u[`--n-item-text-color-child-active-horizontal`]=r.itemTextColorChildActiveHorizontalInverted,u[`--n-item-text-color-child-active-hover-horizontal`]=r.itemTextColorChildActiveHoverHorizontalInverted,u[`--n-item-text-color-active-hover-horizontal`]=r.itemTextColorActiveHoverHorizontalInverted,u[`--n-item-icon-color-horizontal`]=r.itemIconColorHorizontalInverted,u[`--n-item-icon-color-hover-horizontal`]=r.itemIconColorHoverHorizontalInverted,u[`--n-item-icon-color-active-horizontal`]=r.itemIconColorActiveHorizontalInverted,u[`--n-item-icon-color-active-hover-horizontal`]=r.itemIconColorActiveHoverHorizontalInverted,u[`--n-item-icon-color-child-active-horizontal`]=r.itemIconColorChildActiveHorizontalInverted,u[`--n-item-icon-color-child-active-hover-horizontal`]=r.itemIconColorChildActiveHoverHorizontalInverted,u[`--n-arrow-color`]=r.arrowColorInverted,u[`--n-arrow-color-hover`]=r.arrowColorHoverInverted,u[`--n-arrow-color-active`]=r.arrowColorActiveInverted,u[`--n-arrow-color-active-hover`]=r.arrowColorActiveHoverInverted,u[`--n-arrow-color-child-active`]=r.arrowColorChildActiveInverted,u[`--n-arrow-color-child-active-hover`]=r.arrowColorChildActiveHoverInverted,u[`--n-item-color-hover`]=r.itemColorHoverInverted,u[`--n-item-color-active`]=r.itemColorActiveInverted,u[`--n-item-color-active-hover`]=r.itemColorActiveHoverInverted,u[`--n-item-color-active-collapsed`]=r.itemColorActiveCollapsedInverted):(u[`--n-group-text-color`]=r.groupTextColor,u[`--n-color`]=r.color,u[`--n-item-text-color`]=r.itemTextColor,u[`--n-item-text-color-hover`]=r.itemTextColorHover,u[`--n-item-text-color-active`]=r.itemTextColorActive,u[`--n-item-text-color-child-active`]=r.itemTextColorChildActive,u[`--n-item-text-color-child-active-hover`]=r.itemTextColorChildActiveHover,u[`--n-item-text-color-active-hover`]=r.itemTextColorActiveHover,u[`--n-item-icon-color`]=r.itemIconColor,u[`--n-item-icon-color-hover`]=r.itemIconColorHover,u[`--n-item-icon-color-active`]=r.itemIconColorActive,u[`--n-item-icon-color-active-hover`]=r.itemIconColorActiveHover,u[`--n-item-icon-color-child-active`]=r.itemIconColorChildActive,u[`--n-item-icon-color-child-active-hover`]=r.itemIconColorChildActiveHover,u[`--n-item-icon-color-collapsed`]=r.itemIconColorCollapsed,u[`--n-item-text-color-horizontal`]=r.itemTextColorHorizontal,u[`--n-item-text-color-hover-horizontal`]=r.itemTextColorHoverHorizontal,u[`--n-item-text-color-active-horizontal`]=r.itemTextColorActiveHorizontal,u[`--n-item-text-color-child-active-horizontal`]=r.itemTextColorChildActiveHorizontal,u[`--n-item-text-color-child-active-hover-horizontal`]=r.itemTextColorChildActiveHoverHorizontal,u[`--n-item-text-color-active-hover-horizontal`]=r.itemTextColorActiveHoverHorizontal,u[`--n-item-icon-color-horizontal`]=r.itemIconColorHorizontal,u[`--n-item-icon-color-hover-horizontal`]=r.itemIconColorHoverHorizontal,u[`--n-item-icon-color-active-horizontal`]=r.itemIconColorActiveHorizontal,u[`--n-item-icon-color-active-hover-horizontal`]=r.itemIconColorActiveHoverHorizontal,u[`--n-item-icon-color-child-active-horizontal`]=r.itemIconColorChildActiveHorizontal,u[`--n-item-icon-color-child-active-hover-horizontal`]=r.itemIconColorChildActiveHoverHorizontal,u[`--n-arrow-color`]=r.arrowColor,u[`--n-arrow-color-hover`]=r.arrowColorHover,u[`--n-arrow-color-active`]=r.arrowColorActive,u[`--n-arrow-color-active-hover`]=r.arrowColorActiveHover,u[`--n-arrow-color-child-active`]=r.arrowColorChildActive,u[`--n-arrow-color-child-active-hover`]=r.arrowColorChildActiveHover,u[`--n-item-color-hover`]=r.itemColorHover,u[`--n-item-color-active`]=r.itemColorActive,u[`--n-item-color-active-hover`]=r.itemColorActiveHover,u[`--n-item-color-active-collapsed`]=r.itemColorActiveCollapsed),u}),ne=i?re(`menu`,k(()=>t.inverted?`a`:`b`),O,t):void 0,j=r(),M=D(null),N=D(null),F=!0,L=()=>{F?F=!1:M.value?.sync({showAllItemsBeforeCalculate:!0})};function ie(){return document.getElementById(j)}let R=D(-1);function z(e){R.value=t.options.length-e}function ae(e){e||(R.value=-1)}let B=k(()=>{let e=R.value;return{children:e===-1?[]:t.options.slice(e)}}),V=k(()=>{let{childrenField:e,disabledField:n,keyField:r}=t;return ve([B.value],{getIgnored(e){return $(e)},getChildren(t){return t[e]},getDisabled(e){return e[n]},getKey(e){return e[r]??e.name}})}),H=k(()=>ve([{}]).treeNodes[0]);function U(){if(R.value===-1)return c(),x(Tt,{root:!0,level:0,key:`__ellpisisGroupPlaceholder__`,internalKey:`__ellpisisGroupPlaceholder__`,title:`···`,tmNode:H.value,domId:j,isEllipsisPlaceholder:!0},null,8,[`tmNode`,`domId`]);let e=V.value.treeNodes[0],t=C.value,n=!!e.children?.some(e=>t.includes(e.key));return c(),x(Tt,{level:0,root:!0,key:`__ellpisisGroup__`,internalKey:`__ellpisisGroup__`,title:`···`,virtualChildActive:n,tmNode:e,domId:j,rawNodes:e.rawNode.children||[],tmNodes:e.children||[],isEllipsisPlaceholder:!0},null,8,[`virtualChildActive`,`tmNode`,`domId`,`rawNodes`,`tmNodes`])}return{mergedClsPrefix:n,controlledExpandedKeys:v,uncontrolledExpanededKeys:g,mergedExpandedKeys:y,uncontrolledValue:p,mergedValue:h,activePath:C,tmNodes:S,mergedTheme:a,mergedCollapsed:s,cssVars:i?void 0:O,themeClass:ne?.themeClass,overflowRef:M,counterRef:N,updateCounter:()=>{},onResize:L,onUpdateOverflow:ae,onUpdateCount:z,renderCounter:U,getCounter:ie,onRender:ne?.onRender,showOption:E,deriveResponsiveState:L}},render(){let{mergedClsPrefix:e,mode:t,themeClass:r,onRender:i}=this;i?.();let a=()=>this.tmNodes.map(e=>Dt(e,this.$props)),o=t===`horizontal`&&this.responsive,l=()=>h(`div`,s(this.$attrs,{role:t===`horizontal`?`menubar`:`menu`,class:[`${e}-menu`,r,`${e}-menu--${t}`,o&&`${e}-menu--responsive`,this.mergedCollapsed&&`${e}-menu--collapsed`],style:this.cssVars}),o?(c(),x(_e,{key:2,ref:`overflowRef`,onUpdateOverflow:this.onUpdateOverflow,getCounter:this.getCounter,onUpdateCount:this.onUpdateCount,updateCounter:this.updateCounter,style:{width:`100%`,display:`flex`,overflow:`hidden`}},{default:a,counter:this.renderCounter},1032,[`onUpdateOverflow`,`getCounter`,`onUpdateCount`,`updateCounter`])):a());return o?(c(),x(n,{key:3,onResize:this.onResize},{default:l},1032,[`onResize`])):l()}}),At={class:`flex items-center`},jt={__name:`BreadCrumb`,setup(e){let t=X(),n=ze(),r=be(),i=D([]);u(()=>n.name,e=>{i.value=o(r.permissions,e)},{immediate:!0});function o(e,t,n=[]){for(let r of e){if(r.code===t)return[...n,r];if(r.children?.length){let e=o(r.children,t,[...n,r]);if(e)return e}}return null}function s(e){e.path&&e.code!==n.name&&t.push(e.path)}function d(e=[]){return e.filter(e=>e.show).map(e=>({label:e.name,key:e.code,icon:()=>h(`i`,{class:e.icon})}))}function f(e){e&&e!==n.name&&t.push({name:e})}return(e,t)=>{let r=$e,o=Y,u=Ze;return c(),x(u,null,{default:l(()=>[E(i)?.length?(c(!0),T(C,{key:1},M(E(i),(e,t)=>(c(),x(r,{key:e.code,clickable:!!e.path,onClick:t=>s(e)},{default:l(()=>[v(o,{options:t<E(i).length-1?d(e.children):[],onSelect:f},{default:l(()=>[y(`div`,At,[y(`i`,{class:m([e.icon,`mr-8`])},null,2),S(` `+a(e.name),1)])]),_:2},1032,[`options`])]),_:2},1032,[`clickable`,`onClick`]))),128)):(c(),x(r,{key:0,clickable:!1},{default:l(()=>[S(a(E(n).meta.title),1)]),_:1}))]),_:1})}}},Mt={__name:`MenuCollapse`,setup(e){let t=Ee();return(e,n)=>(c(),T(`div`,{id:`menu-collapse`,class:`f-c-c cursor-pointer rounded-4 auto-bg-hover p-6 text-22 transition-all-300`,onClick:n[0]||=(...e)=>E(t).switchCollapsed&&E(t).switchCollapsed(...e)},[y(`i`,{class:m(E(t).collapsed?`i-line-md-menu-unfold-left`:`i-line-md-menu-fold-left`)},null,2)]))}},Nt={getUser:()=>q.get(`/user/detail`),refreshToken:()=>q.get(`/auth/refresh/token`),logout:()=>q.post(`/auth/logout`,{},{needTip:!1}),switchCurrentRole:e=>q.post(`/auth/current-role/switch/${e}`),getRolePermissions:()=>q.get(`/role/permissions/tree`),validateMenuPath:e=>q.get(`/permission/menu/validate?path=${e}`)},Pt={class:`flex`},Ft={__name:`RoleSelect`,setup(e,{expose:t}){let n=Ce(),r=Se(),i=D(n.roles||[]),o=D(n.currentRole?.code??i.value[0]?.code??``),[s,u]=Ve();function d(e){s.value?.open({...e})}async function f(){try{u.value=!0;let{data:e}=await Nt.switchCurrentRole(o.value);await r.switchCurrentRole(e),u.value=!1,$message.success(`切换成功`),s.value?.handleOk()}catch(e){return console.error(e),u.value=!1,!1}}async function p(){await Nt.logout(),r.logout(),s.value?.close(),$message.success(`已退出登录`)}return t({open:d}),(e,t)=>{let r=tt,d=Re,h=Me,g=pe;return c(),x(E(He),{ref_key:`modalRef`,ref:s,title:`请选择角色`,width:`360px`,class:`p-12`},{footer:l(()=>[y(`div`,Pt,[v(g,{class:`flex-1`,size:`large`,onClick:t[1]||=e=>p()},{default:l(()=>[...t[2]||=[S(` 退出登录 `,-1)]]),_:1}),v(g,{loading:E(u),class:`ml-20 flex-1`,type:`primary`,size:`large`,disabled:E(n).currentRole?.code===E(o),onClick:f},{default:l(()=>[...t[3]||=[S(` 确认 `,-1)]]),_:1},8,[`loading`,`disabled`])])]),default:l(()=>[v(h,{value:E(o),"onUpdate:value":t[0]||=e=>ne(o)?o.value=e:null,class:`cus-scroll-y max-h-420 w-full py-16`},{default:l(()=>[v(d,{vertical:``,size:24,class:`mx-12`},{default:l(()=>[(c(!0),T(C,null,M(E(i),e=>(c(),x(r,{key:e.id,class:m([`h-36 w-full text-center text-16 leading-36`,{"bg-primary! color-white!":e.code===E(o)}]),value:e.code},{default:l(()=>[S(a(e.name),1)]),_:2},1032,[`class`,`value`]))),128))]),_:1})]),_:1},8,[`value`])]),_:1},512)}}},It=`/admin/assets/isme-D6AR05SU.png`,Lt={},Rt={class:`h-32 w-32 rounded-4 bg-primary`};function zt(e,t){return c(),T(`div`,Rt,[...t[0]||=[y(`img`,{src:It,alt:`Logo`},null,-1)]])}var Bt=Be(Lt,[[`render`,zt]]),Vt={__name:`SideLogo`,setup(e){let t=Ee();return(e,n)=>{let r=Bt,i=g(`router-link`);return c(),x(i,{class:`h-60 f-c-c`,to:`/`},{default:l(()=>[v(r),_(y(`h2`,{class:`ml-10 max-w-140 flex-shrink-0 text-16 color-primary font-bold`},a(E(`学境・StudyScape — 学习的新境界`)),513),[[le,!E(t).collapsed]])]),_:1})}}},Ht={__name:`SideMenu`,setup(e){let t=X(),n=ze(),r=Ee(),i=be(),a=k(()=>n.meta?.parentKey||n.name),s=D(null);u(n,async()=>{await o(),s.value?.showOption()});function l(e,n){if(Te(n.originPath))$dialog.confirm({type:`info`,title:`请选择打开方式`,positiveText:`外链打开`,negativeText:`在本站内嵌打开`,confirm(){window.open(n.originPath)},cancel:()=>{t.push(n.path)}});else{if(!n.path)return;t.push(n.path)}}return(e,t)=>{let n=kt;return c(),x(n,{ref_key:`menu`,ref:s,class:`side-menu`,accordion:``,indent:18,"collapsed-icon-size":22,"collapsed-width":64,collapsed:E(r).collapsed,options:E(i).menus,value:E(a),"onUpdate:value":l},null,8,[`collapsed`,`options`,`value`])}}},Ut={__name:`ContextMenu`,props:{show:{type:Boolean,default:!1},currentPath:{type:String,default:``},x:{type:Number,default:0},y:{type:Number,default:0}},emits:[`update:show`],setup(e,{emit:t}){let n=e,r=t,i=we(),a=k(()=>[{label:`重新加载`,key:`reload`,disabled:n.currentPath!==i.activeTab,icon:()=>h(`i`,{class:`i-mdi:refresh text-14`})},{label:`关闭`,key:`close`,disabled:i.tabs.length<=1,icon:()=>h(`i`,{class:`i-mdi:close text-14`})},{label:`关闭其他`,key:`close-other`,disabled:i.tabs.length<=1,icon:()=>h(`i`,{class:`i-mdi:arrow-expand-horizontal text-14`})},{label:`关闭左侧`,key:`close-left`,disabled:i.tabs.length<=1||n.currentPath===i.tabs[0].path,icon:()=>h(`i`,{class:`i-mdi:arrow-expand-left text-14`})},{label:`关闭右侧`,key:`close-right`,disabled:i.tabs.length<=1||n.currentPath===i.tabs[i.tabs.length-1].path,icon:()=>h(`i`,{class:`i-mdi:arrow-expand-right text-14`})}]),o=ze(),s=new Map([[`reload`,()=>{i.reloadTab(o.fullPath,o.meta?.keepAlive)}],[`close`,()=>{i.removeTab(n.currentPath)}],[`close-other`,()=>{i.removeOther(n.currentPath)}],[`close-left`,()=>{i.removeLeft(n.currentPath)}],[`close-right`,()=>{i.removeRight(n.currentPath)}]]);function l(){r(`update:show`,!1)}function u(e){let t=s.get(e);typeof t==`function`&&t(),l()}return(t,n)=>{let r=Y;return c(),x(r,{show:e.show,options:E(a),x:e.x,y:e.y,placement:`bottom-start`,onClickoutside:l,onSelect:u},null,8,[`show`,`options`,`x`,`y`])}}},Wt={id:`top-tab`},Gt=Be({__name:`index`,setup(e){let t=X(),n=we(),r=ee({show:!1,x:0,y:0,currentPath:``});function i(e){n.setActiveTab(e),t.push(e)}function s(){r.show=!0}function u(){r.show=!1}function d(e,t,n){Object.assign(r,{x:e,y:t,currentPath:n})}async function f(e,t){let{clientX:n,clientY:r}=e;u(),d(n,r,t.path),await o(),s()}return(e,t)=>{let o=he,s=ge;return c(),T(`div`,Wt,[v(s,{value:E(n).activeTab,closable:E(n).tabs.length>1,type:`card`,onClose:t[0]||=e=>E(n).removeTab(e)},{default:l(()=>[(c(!0),T(C,null,M(E(n).tabs,e=>(c(),x(o,{key:e.path,name:e.path,onClick:t=>i(e.path),onContextmenu:oe(t=>f(t,e),[`prevent`])},{default:l(()=>[S(a(e.title),1)]),_:2},1032,[`name`,`onClick`,`onContextmenu`]))),128))]),_:1},8,[`value`,`closable`]),E(r).show?(c(),x(Ut,{key:0,show:E(r).show,"onUpdate:show":t[1]||=e=>E(r).show=e,"current-path":E(r).currentPath,x:E(r).x,y:E(r).y},null,8,[`show`,`current-path`,`x`,`y`])):O(``,!0)])}}},[[`__scopeId`,`data-v-871ef196`]]),Kt={id:`user-dropdown`,class:`flex cursor-pointer items-center`},qt={key:0,class:`ml-12 flex-col flex-shrink-0 items-center`},Jt={class:`text-14`},Yt={class:`text-12 opacity-50`},Xt={__name:`UserAvatar`,setup(e){let t=X(),n=Ce(),r=Se(),i=be(),o=ee([{label:`个人资料`,key:`profile`,icon:()=>h(`i`,{class:`i-material-symbols:person-outline text-14`}),show:k(()=>i.accessRoutes?.some(e=>e.path===`/profile`))},{label:`切换角色`,key:`toggleRole`,icon:()=>h(`i`,{class:`i-basil:exchange-solid text-14`}),show:k(()=>n.roles.length>1)},{label:`退出登录`,key:`logout`,icon:()=>h(`i`,{class:`i-mdi:exit-to-app text-14`})}]),s=D(null);function u(e){switch(e){case`profile`:t.push(`/profile`);break;case`toggleRole`:s.value?.open({onOk(){location.reload()}});break;case`logout`:$dialog.confirm({title:`提示`,type:`info`,content:`确认退出？`,async confirm(){try{await Nt.logout()}catch(e){console.error(e)}r.logout(),$message.success(`已退出登录`)}})}}return(e,t)=>{let r=qe,i=Y;return c(),T(C,null,[v(i,{options:E(o),onSelect:u},{default:l(()=>[y(`div`,Kt,[v(r,{round:``,size:36,src:E(n).avatar},null,8,[`src`]),E(n).userInfo?(c(),T(`div`,qt,[y(`span`,Jt,a(E(n).nickName??E(n).username),1),y(`span`,Yt,`[`+a(E(n).currentRole?.name)+`]`,1)])):O(``,!0)])]),_:1},8,[`options`]),v(E(Ft),{ref_key:`roleSelectRef`,ref:s},null,512)],64)}}};export{Mt as a,Vt as i,Gt as n,jt as o,Ht as r,Xt as t};