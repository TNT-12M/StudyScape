import{J as e,P as t,tt as n}from"./php-modules-GekPVnxA.js";import{$ as r,E as i,F as a,L as o,Mt as s,Nt as c,O as l,P as u,Pt as d,Q as f,U as p,V as m,X as h,Y as g,a as _,at as v,ct as y,d as b,f as x,h as S,ht as C,k as w,m as T,p as E,t as D,ut as O,v as k,vt as A,w as j,x as M,y as N,z as P}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as F,A as I,E as ee,F as L,I as te,M as R,N as ne,O as z,Qt as B,Xt as V,Yt as H,at as U,ct as W,dn as re,en as G,g as ie,gt as K,it as q,l as ae,ln as oe,pt as se,t as ce}from"./Button-BtFx7EYM.js";import{n as le,t as ue}from"./Tabs-BKQ5zmi1.js";import{r as de,t as fe}from"./create-BBG9Dvpd.js";import{S as pe,c as me,h as he,i as ge,l as _e,n as ve,o as ye,r as be,s as J}from"./store-DyuIHp0J.js";import{f as xe,s as Y}from"./light-B0o7KsWP.js";import{r as Se}from"./cssr-BRvHbqtS.js";import{n as Ce}from"./format-length-BCoLE4J2.js";import{a as we,i as Te,n as Ee,t as X}from"./Dropdown-H72JcQtU.js";import{t as De}from"./fade-in-height-expand.cssr-DnaCoNGx.js";import{t as Oe}from"./Avatar-DaWTI3-v.js";import{t as ke}from"./Tooltip-DPhNwnSI.js";import{t as Ae}from"./Space-DnowR1Mn.js";import{g as Z,h as je}from"./useApi-BPuI6ZR9-DFgTMBzL.js";import{t as Me}from"./session-DmnlV5N2.js";import{r as Ne,t as Pe}from"./index-RIU7AKSE.js";var Fe=V(`breadcrumb`,`
 white-space: nowrap;
 cursor: default;
 line-height: var(--n-item-line-height);
`,[H(`ul`,`
 list-style: none;
 padding: 0;
 margin: 0;
 `),H(`a`,`
 color: inherit;
 text-decoration: inherit;
 `),V(`breadcrumb-item`,`
 font-size: var(--n-font-size);
 transition: color .3s var(--n-bezier);
 display: inline-flex;
 align-items: center;
 `,[V(`icon`,`
 font-size: 18px;
 vertical-align: -.2em;
 transition: color .3s var(--n-bezier);
 color: var(--n-item-text-color);
 `),H(`&:not(:last-child)`,[F(`clickable`,[B(`link`,`
 cursor: pointer;
 `,[H(`&:hover`,`
 background-color: var(--n-item-color-hover);
 `),H(`&:active`,`
 background-color: var(--n-item-color-pressed); 
 `)])])]),B(`link`,`
 padding: 4px;
 border-radius: var(--n-item-border-radius);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 color: var(--n-item-text-color);
 position: relative;
 `,[H(`&:hover`,`
 color: var(--n-item-text-color-hover);
 `,[V(`icon`,`
 color: var(--n-item-text-color-hover);
 `)]),H(`&:active`,`
 color: var(--n-item-text-color-pressed);
 `,[V(`icon`,`
 color: var(--n-item-text-color-pressed);
 `)])]),B(`separator`,`
 margin: 0 8px;
 color: var(--n-separator-color);
 transition: color .3s var(--n-bezier);
 user-select: none;
 -webkit-user-select: none;
 `),H(`&:last-child`,[B(`link`,`
 font-weight: var(--n-font-weight-active);
 cursor: unset;
 color: var(--n-item-text-color-active);
 `,[V(`icon`,`
 color: var(--n-item-text-color-active);
 `)]),B(`separator`,`
 display: none;
 `)])])]),Ie=K(`n-breadcrumb`),Le={...L.props,separator:{type:String,default:`/`}},Re=M({name:`Breadcrumb`,props:Le,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=se(e),r=L(`Breadcrumb`,`-breadcrumb`,Fe,pe,e,t);P(Ie,{separatorRef:C(e,`separator`),mergedClsPrefixRef:t});let i=b(()=>{let{common:{cubicBezierEaseInOut:e},self:{separatorColor:t,itemTextColor:n,itemTextColorHover:i,itemTextColorPressed:a,itemTextColorActive:o,fontSize:s,fontWeightActive:c,itemBorderRadius:l,itemColorHover:u,itemColorPressed:d,itemLineHeight:f}}=r.value;return{"--n-font-size":s,"--n-bezier":e,"--n-item-text-color":n,"--n-item-text-color-hover":i,"--n-item-text-color-pressed":a,"--n-item-text-color-active":o,"--n-separator-color":t,"--n-item-color-hover":u,"--n-item-color-pressed":d,"--n-item-border-radius":l,"--n-font-weight-active":c,"--n-item-line-height":f}}),a=n?te(`breadcrumb`,void 0,i,e):void 0;return{mergedClsPrefix:t,cssVars:n?void 0:i,themeClass:a?.themeClass,onRender:a?.onRender}},render(){return this.onRender?.(),o(),S(`nav`,{class:U([`${this.mergedClsPrefix}-breadcrumb`,this.themeClass]),style:c(this.cssVars),"aria-label":`Breadcrumb`},[x(`ul`,null,[W(()=>this.$slots.default?.())])],6)}});function ze(e=ae?window:null){let t=()=>{let{hash:t,host:n,hostname:r,href:i,origin:a,pathname:o,port:s,protocol:c,search:l}=e?.location||{};return{hash:t,host:n,hostname:r,href:i,origin:a,pathname:o,port:s,protocol:c,search:l}},n=O(t()),r=()=>{n.value=t()};return u(()=>{e&&(e.addEventListener(`popstate`,r),e.addEventListener(`hashchange`,r))}),a(()=>{e&&(e.removeEventListener(`popstate`,r),e.removeEventListener(`hashchange`,r))}),n}var Be=M({name:`BreadcrumbItem`,props:{separator:String,href:String,clickable:{type:Boolean,default:!0},showSeparator:{type:Boolean,default:!0},onClick:Function},slots:Object,setup(e,{slots:t}){let n=i(Ie,null);if(!n)return()=>null;let{separatorRef:r,mergedClsPrefixRef:a}=n,s=ze(),c=b(()=>e.href?`a`:`span`),l=b(()=>s.value.href===e.href?`location`:null);return()=>{let{value:n}=a;return o(),S(`li`,{class:U([`${n}-breadcrumb-item`,e.clickable&&`${n}-breadcrumb-item--clickable`])},[W(()=>j(c.value,{class:`${n}-breadcrumb-item__link`,"aria-current":l.value,href:e.href,onClick:e.onClick},t)),W(()=>e.showSeparator&&(o(),S(`span`,{class:U(`${n}-breadcrumb-item__separator`),"aria-hidden":`true`},[W(()=>ee(t.separator,()=>[e.separator??r.value]))],2)))],2)}}}),Ve=[`value`,`name`,`checked`,`disabled`,`onChange`,`onFocus`,`onBlur`],He=M({name:`RadioButton`,props:Te,setup:we,render(){let{mergedClsPrefix:e}=this;return o(),S(`label`,{class:U([`${e}-radio-button`,this.mergedDisabled&&`${e}-radio-button--disabled`,this.renderSafeChecked&&`${e}-radio-button--checked`,this.focus&&[`${e}-radio-button--focus`]])},[x(`input`,{ref:`inputRef`,type:`radio`,class:U(`${e}-radio-input`),value:this.value,name:this.mergedName,checked:this.renderSafeChecked,disabled:this.mergedDisabled,onChange:this.handleRadioInputChange,onFocus:this.handleRadioInputFocus,onBlur:this.handleRadioInputBlur},null,42,Ve),x(`div`,{class:U(`${e}-radio-button__state-border`)},null,2),W(()=>z(this.$slots.default,t=>!t&&!this.label?null:(o(),S(`div`,{ref:`labelRef`,class:U(`${e}-radio__label`)},[W(()=>t||this.label)],2))))],2)}}),Ue=K(`n-layout-sider`),Q=K(`n-menu`),We=K(`n-submenu`),Ge=K(`n-menu-item-group`),Ke=[H(`&::before`,`background-color: var(--n-item-color-hover);`),B(`arrow`,`
 color: var(--n-arrow-color-hover);
 `),B(`icon`,`
 color: var(--n-item-icon-color-hover);
 `),V(`menu-item-content-header`,`
 color: var(--n-item-text-color-hover);
 `,[H(`a`,`
 color: var(--n-item-text-color-hover);
 `),B(`extra`,`
 color: var(--n-item-text-color-hover);
 `)])],qe=[B(`icon`,`
 color: var(--n-item-icon-color-hover-horizontal);
 `),V(`menu-item-content-header`,`
 color: var(--n-item-text-color-hover-horizontal);
 `,[H(`a`,`
 color: var(--n-item-text-color-hover-horizontal);
 `),B(`extra`,`
 color: var(--n-item-text-color-hover-horizontal);
 `)])],Je=H([V(`menu`,`
 background-color: var(--n-color);
 color: var(--n-item-text-color);
 overflow: hidden;
 transition: background-color .3s var(--n-bezier);
 box-sizing: border-box;
 font-size: var(--n-font-size);
 padding-bottom: 6px;
 `,[F(`horizontal`,`
 max-width: 100%;
 width: 100%;
 display: flex;
 overflow: hidden;
 padding-bottom: 0;
 `,[V(`submenu`,`margin: 0;`),V(`menu-item`,`margin: 0;`),V(`menu-item-content`,`
 padding: 0 20px;
 border-bottom: 2px solid #0000;
 `,[H(`&::before`,`display: none;`),F(`selected`,`border-bottom: 2px solid var(--n-border-color-horizontal)`)]),V(`menu-item-content`,[F(`selected`,[B(`icon`,`color: var(--n-item-icon-color-active-horizontal);`),V(`menu-item-content-header`,`
 color: var(--n-item-text-color-active-horizontal);
 `,[H(`a`,`color: var(--n-item-text-color-active-horizontal);`),B(`extra`,`color: var(--n-item-text-color-active-horizontal);`)])]),F(`child-active`,`
 border-bottom: 2px solid var(--n-border-color-horizontal);
 `,[V(`menu-item-content-header`,`
 color: var(--n-item-text-color-child-active-horizontal);
 `,[H(`a`,`
 color: var(--n-item-text-color-child-active-horizontal);
 `),B(`extra`,`
 color: var(--n-item-text-color-child-active-horizontal);
 `)]),B(`icon`,`
 color: var(--n-item-icon-color-child-active-horizontal);
 `)]),G(`disabled`,[G(`selected, child-active`,[H(`&:focus-within`,qe)]),F(`selected`,[$(null,[B(`icon`,`color: var(--n-item-icon-color-active-hover-horizontal);`),V(`menu-item-content-header`,`
 color: var(--n-item-text-color-active-hover-horizontal);
 `,[H(`a`,`color: var(--n-item-text-color-active-hover-horizontal);`),B(`extra`,`color: var(--n-item-text-color-active-hover-horizontal);`)])])]),F(`child-active`,[$(null,[B(`icon`,`color: var(--n-item-icon-color-child-active-hover-horizontal);`),V(`menu-item-content-header`,`
 color: var(--n-item-text-color-child-active-hover-horizontal);
 `,[H(`a`,`color: var(--n-item-text-color-child-active-hover-horizontal);`),B(`extra`,`color: var(--n-item-text-color-child-active-hover-horizontal);`)])])]),$(`border-bottom: 2px solid var(--n-border-color-horizontal);`,qe)]),V(`menu-item-content-header`,[H(`a`,`color: var(--n-item-text-color-horizontal);`)])])]),G(`responsive`,[V(`menu-item-content-header`,`
 overflow: hidden;
 text-overflow: ellipsis;
 `)]),F(`collapsed`,[V(`menu-item-content`,[F(`selected`,[H(`&::before`,`
 background-color: var(--n-item-color-active-collapsed) !important;
 `)]),V(`menu-item-content-header`,`opacity: 0;`),B(`arrow`,`opacity: 0;`),B(`icon`,`color: var(--n-item-icon-color-collapsed);`)])]),V(`menu-item`,`
 height: var(--n-item-height);
 margin-top: 6px;
 position: relative;
 `),V(`menu-item-content`,`
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
 `,[H(`> *`,`z-index: 1;`),H(`&::before`,`
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
 `),F(`disabled`,`
 opacity: .45;
 cursor: not-allowed;
 `),F(`collapsed`,[B(`arrow`,`transform: rotate(0);`)]),F(`selected`,[H(`&::before`,`background-color: var(--n-item-color-active);`),B(`arrow`,`color: var(--n-arrow-color-active);`),B(`icon`,`color: var(--n-item-icon-color-active);`),V(`menu-item-content-header`,`
 color: var(--n-item-text-color-active);
 `,[H(`a`,`color: var(--n-item-text-color-active);`),B(`extra`,`color: var(--n-item-text-color-active);`)])]),F(`child-active`,[V(`menu-item-content-header`,`
 color: var(--n-item-text-color-child-active);
 `,[H(`a`,`
 color: var(--n-item-text-color-child-active);
 `),B(`extra`,`
 color: var(--n-item-text-color-child-active);
 `)]),B(`arrow`,`
 color: var(--n-arrow-color-child-active);
 `),B(`icon`,`
 color: var(--n-item-icon-color-child-active);
 `)]),G(`disabled`,[G(`selected, child-active`,[H(`&:focus-within`,Ke)]),F(`selected`,[$(null,[B(`arrow`,`color: var(--n-arrow-color-active-hover);`),B(`icon`,`color: var(--n-item-icon-color-active-hover);`),V(`menu-item-content-header`,`
 color: var(--n-item-text-color-active-hover);
 `,[H(`a`,`color: var(--n-item-text-color-active-hover);`),B(`extra`,`color: var(--n-item-text-color-active-hover);`)])])]),F(`child-active`,[$(null,[B(`arrow`,`color: var(--n-arrow-color-child-active-hover);`),B(`icon`,`color: var(--n-item-icon-color-child-active-hover);`),V(`menu-item-content-header`,`
 color: var(--n-item-text-color-child-active-hover);
 `,[H(`a`,`color: var(--n-item-text-color-child-active-hover);`),B(`extra`,`color: var(--n-item-text-color-child-active-hover);`)])])]),F(`selected`,[$(null,[H(`&::before`,`background-color: var(--n-item-color-active-hover);`)])]),$(null,Ke)]),B(`icon`,`
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
 `),B(`arrow`,`
 grid-area: arrow;
 font-size: 16px;
 color: var(--n-arrow-color);
 transform: rotate(180deg);
 opacity: 1;
 transition:
 color .3s var(--n-bezier),
 transform 0.2s var(--n-bezier),
 opacity 0.2s var(--n-bezier);
 `),V(`menu-item-content-header`,`
 grid-area: content;
 transition:
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 opacity: 1;
 white-space: nowrap;
 color: var(--n-item-text-color);
 `,[H(`a`,`
 outline: none;
 text-decoration: none;
 transition: color .3s var(--n-bezier);
 color: var(--n-item-text-color);
 `,[H(`&::before`,`
 content: "";
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `)]),B(`extra`,`
 font-size: .93em;
 color: var(--n-group-text-color);
 transition: color .3s var(--n-bezier);
 `)])]),V(`submenu`,`
 cursor: pointer;
 position: relative;
 margin-top: 6px;
 `,[V(`menu-item-content`,`
 height: var(--n-item-height);
 `),V(`submenu-children`,`
 overflow: hidden;
 padding: 0;
 `,[De({duration:`.2s`})])]),V(`menu-item-group`,[V(`menu-item-group-title`,`
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
 `)])]),V(`menu-tooltip`,[H(`a`,`
 color: inherit;
 text-decoration: none;
 `)]),V(`menu-divider`,`
 transition: background-color .3s var(--n-bezier);
 background-color: var(--n-divider-color);
 height: 1px;
 margin: 6px 18px;
 `)]);function $(e,t){return[F(`hover`,e,t),H(`&:hover`,e,t)]}var Ye=M({name:`MenuDivider`,setup(){let{mergedClsPrefixRef:e,isHorizontalRef:t}=i(Q);return()=>t.value?null:(o(),S(`div`,{key:1,class:U(`${e.value}-menu-divider`)},null,2))}}),Xe=M({name:`ChevronDownFilled`,render(){return(()=>{let e=q(`f3af82a2aab086a5`);return e[0]||=x(`svg`,{viewBox:`0 0 16 16`,fill:`none`,xmlns:`http://www.w3.org/2000/svg`},[x(`path`,{d:`M3.20041 5.73966C3.48226 5.43613 3.95681 5.41856 4.26034 5.70041L8 9.22652L11.7397 5.70041C12.0432 5.41856 12.5177 5.43613 12.7996 5.73966C13.0815 6.0432 13.0639 6.51775 12.7603 6.7996L8.51034 10.7996C8.22258 11.0668 7.77743 11.0668 7.48967 10.7996L3.23966 6.7996C2.93613 6.51775 2.91856 6.0432 3.20041 5.73966Z`,fill:`currentColor`})],-1)})()}}),Ze=[`onClick`],Qe=M({name:`MenuOptionContent`,props:{collapsed:Boolean,disabled:Boolean,title:[String,Function],icon:Function,extra:[String,Function],showArrow:Boolean,childActive:Boolean,hover:Boolean,paddingLeft:Number,selected:Boolean,maxIconSize:{type:Number,required:!0},activeIconSize:{type:Number,required:!0},iconMarginRight:{type:Number,required:!0},clsPrefix:{type:String,required:!0},onClick:Function,tmNode:{type:Object,required:!0},isEllipsisPlaceholder:Boolean},setup(e){let{props:t}=i(Q);return{menuProps:t,style:b(()=>{let{paddingLeft:t}=e;return{paddingLeft:t&&`${t}px`}}),iconStyle:b(()=>{let{maxIconSize:t,activeIconSize:n,iconMarginRight:r}=e;return{width:`${t}px`,height:`${t}px`,fontSize:`${n}px`,marginRight:`${r}px`}})}},render(){let{clsPrefix:e,tmNode:t,menuProps:{renderIcon:n,renderLabel:r,renderExtra:i,expandIcon:a}}=this,s=n?n(t.rawNode):Y(this.icon);return(()=>{let n=q(`7bb10afc6caf8fa4`);return o(),S(`div`,{onClick:e=>{this.onClick?.(e)},role:`none`,class:U([`${e}-menu-item-content`,{[`${e}-menu-item-content--selected`]:this.selected,[`${e}-menu-item-content--collapsed`]:this.collapsed,[`${e}-menu-item-content--child-active`]:this.childActive,[`${e}-menu-item-content--disabled`]:this.disabled,[`${e}-menu-item-content--hover`]:this.hover}]),style:c(this.style)},[W(()=>s&&(o(),S(`div`,{class:U(`${e}-menu-item-content__icon`),style:c(this.iconStyle),role:`none`},[W(()=>[s])],6))),x(`div`,{class:U(`${e}-menu-item-content-header`),role:`none`},[this.isEllipsisPlaceholder?(o(),S(_,{key:0},[W(()=>this.title)],64)):(o(),S(_,{key:1},[r?(o(),S(_,{key:0},[W(()=>r(t.rawNode))],64)):(o(),S(_,{key:1},[W(()=>Y(this.title))],64))],64)),this.extra||i?(o(),S(`span`,{key:2,class:U(`${e}-menu-item-content-header__extra`)},[n[0]||=W(` `,-1),i?(o(),S(_,{key:0},[W(()=>i(t.rawNode))],64)):(o(),S(_,{key:1},[W(()=>Y(this.extra))],64))],2)):W(()=>null)],2),this.showArrow?(o(),E(ne,{key:0,ariaHidden:!0,class:U(`${e}-menu-item-content__arrow`),clsPrefix:e},{default:()=>a?a(t.rawNode):(o(),E(Xe,{key:1}))},1032,[`class`,`clsPrefix`])):W(()=>null)],14,Ze)})()}}),$e=8;function et(e){let t=i(Q),{props:n,mergedCollapsedRef:r}=t,a=i(We,null),o=i(Ge,null),s=b(()=>n.mode===`horizontal`),c=b(()=>s.value?n.dropdownPlacement:`tmNodes`in e?`right-start`:`right`),l=b(()=>Math.max(n.collapsedIconSize??n.iconSize,n.iconSize));return{dropdownPlacement:c,activeIconSize:b(()=>!s.value&&e.root&&r.value?n.collapsedIconSize??n.iconSize:n.iconSize),maxIconSize:l,paddingLeft:b(()=>{if(s.value)return;let{collapsedWidth:t,indent:i,rootIndent:c}=n,{root:u,isGroup:d}=e,f=c===void 0?i:c;return u?r.value?t/2-l.value/2:f:o&&typeof o.paddingLeftRef.value==`number`?r.value?t/2-l.value/2:i/2+o.paddingLeftRef.value:a&&typeof a.paddingLeftRef.value==`number`?(d?i/2:i)+a.paddingLeftRef.value:0}),iconMarginRight:b(()=>{let{collapsedWidth:t,indent:i,rootIndent:a}=n,{value:o}=l,{root:c}=e;return s.value||!c||!r.value?$e:(a===void 0?i:a)+o+$e-(t+o)/2}),NMenu:t,NSubmenu:a,NMenuOptionGroup:o}}var tt={internalKey:{type:[String,Number],required:!0},root:Boolean,isGroup:Boolean,level:{type:Number,required:!0},title:[String,Function],extra:[String,Function]},nt={...tt,tmNode:{type:Object,required:!0},disabled:Boolean,icon:Function,onClick:Function},rt=n(nt),it=M({name:`MenuOption`,props:nt,setup(e){let t=et(e),{NSubmenu:n,NMenu:r,NMenuOptionGroup:i}=t,{props:a,mergedClsPrefixRef:o,mergedCollapsedRef:s}=r,c=n?n.mergedDisabledRef:i?i.mergedDisabledRef:{value:!1},l=b(()=>c.value||e.disabled);function u(t){let{onClick:n}=e;n&&n(t)}function d(t){l.value||(r.doSelect(e.internalKey,e.tmNode.rawNode),u(t))}return{mergedClsPrefix:o,dropdownPlacement:t.dropdownPlacement,paddingLeft:t.paddingLeft,iconMarginRight:t.iconMarginRight,maxIconSize:t.maxIconSize,activeIconSize:t.activeIconSize,mergedTheme:r.mergedThemeRef,menuProps:a,dropdownEnabled:R(()=>e.root&&s.value&&a.mode!==`horizontal`&&!l.value),selected:R(()=>r.mergedValueRef.value===e.internalKey),mergedDisabled:l,handleClick:d}},render(){let{mergedClsPrefix:e,mergedTheme:t,tmNode:n,menuProps:{renderLabel:r,nodeProps:i}}=this,a=i?.(n.rawNode);return o(),S(`div`,l(a,{role:`menuitem`,class:[`${e}-menu-item`,a?.class]}),[(o(),E(ke,{theme:t.peers.Tooltip,themeOverrides:t.peerOverrides.Tooltip,trigger:`hover`,placement:this.dropdownPlacement,disabled:!this.dropdownEnabled||this.title===void 0,internalExtraClass:[`menu-tooltip`]},{default:()=>r?r(n.rawNode):Y(this.title),trigger:()=>(o(),E(Qe,{tmNode:n,clsPrefix:e,paddingLeft:this.paddingLeft,iconMarginRight:this.iconMarginRight,maxIconSize:this.maxIconSize,activeIconSize:this.activeIconSize,selected:this.selected,title:this.title,extra:this.extra,disabled:this.mergedDisabled,icon:this.icon,onClick:this.handleClick},null,8,[`tmNode`,`clsPrefix`,`paddingLeft`,`iconMarginRight`,`maxIconSize`,`activeIconSize`,`selected`,`title`,`extra`,`disabled`,`icon`,`onClick`]))},1032,[`theme`,`themeOverrides`,`placement`,`disabled`]))],16)}}),at={...tt,tmNode:{type:Object,required:!0},tmNodes:{type:Array,required:!0}},ot=n(at),st=M({name:`MenuOptionGroup`,props:at,setup(e){let t=et(e),{NSubmenu:n}=t,r=b(()=>n?.mergedDisabledRef.value?!0:e.tmNode.disabled);P(Ge,{paddingLeftRef:t.paddingLeft,mergedDisabledRef:r});let{mergedClsPrefixRef:a,props:s}=i(Q);return function(){let{value:n}=a,r=t.paddingLeft.value,{nodeProps:i}=s,c=i?.(e.tmNode.rawNode);return(()=>{let t=q(`45eca6a63be5028b`);return o(),S(`div`,{class:U(`${n}-menu-item-group`),role:`group`},[x(`div`,l(c,{class:[`${n}-menu-item-group-title`,c?.class],style:[c?.style||``,r===void 0?``:`padding-left: ${r}px;`]}),[W(()=>Y(e.title)),e.extra?(o(),S(_,{key:0},[t[0]||=W(` `,-1),W(()=>Y(e.extra))],64)):W(()=>null)],16),x(`div`,null,[W(()=>e.tmNodes.map(e=>ht(e,s)))])],2)})()}}}),ct=[`aria-expanded`,`id`],lt=[`aria-expanded`,`id`],ut={...tt,rawNodes:{type:Array,default:()=>[]},tmNodes:{type:Array,default:()=>[]},tmNode:{type:Object,required:!0},disabled:Boolean,icon:Function,onClick:Function,domId:String,virtualChildActive:{type:Boolean,default:void 0},isEllipsisPlaceholder:Boolean},dt=n(ut),ft=M({name:`Submenu`,props:ut,setup(e){let t=et(e),{NMenu:n,NSubmenu:r}=t,{props:i,mergedCollapsedRef:a,mergedThemeRef:o}=n,s=b(()=>{let{disabled:t}=e;return r?.mergedDisabledRef.value||i.disabled?!0:t}),c=O(!1);P(We,{paddingLeftRef:t.paddingLeft,mergedDisabledRef:s}),P(Ge,null);function l(){let{onClick:t}=e;t&&t()}function u(){s.value||(a.value||n.toggleExpand(e.internalKey),l())}function d(e){c.value=e}return{menuProps:i,mergedTheme:o,doSelect:n.doSelect,inverted:n.invertedRef,isHorizontal:n.isHorizontalRef,mergedClsPrefix:n.mergedClsPrefixRef,maxIconSize:t.maxIconSize,activeIconSize:t.activeIconSize,iconMarginRight:t.iconMarginRight,dropdownPlacement:t.dropdownPlacement,dropdownShow:c,paddingLeft:t.paddingLeft,mergedDisabled:s,mergedValue:n.mergedValueRef,childActive:R(()=>e.virtualChildActive??n.activePathRef.value.includes(e.internalKey)),collapsed:b(()=>i.mode===`horizontal`?!1:a.value?!0:!n.mergedExpandedKeysRef.value.includes(e.internalKey)),dropdownEnabled:b(()=>!s.value&&(i.mode===`horizontal`||a.value)),handlePopoverShowChange:d,handleClick:u}},render(){let{mergedClsPrefix:e,menuProps:{renderIcon:t,renderLabel:n}}=this,r=()=>{let{isHorizontal:e,paddingLeft:t,collapsed:n,mergedDisabled:r,maxIconSize:i,activeIconSize:a,title:s,childActive:c,icon:u,handleClick:d,menuProps:{nodeProps:f},dropdownShow:p,iconMarginRight:m,tmNode:h,mergedClsPrefix:g,isEllipsisPlaceholder:_,extra:v}=this,y=f?.(h.rawNode);return o(),S(`div`,l(y,{class:[`${g}-menu-item`,y?.class],role:`menuitem`}),[(o(),E(Qe,{tmNode:h,paddingLeft:t,collapsed:n,disabled:r,iconMarginRight:m,maxIconSize:i,activeIconSize:a,title:s,extra:v,showArrow:!e,childActive:c,clsPrefix:g,icon:u,hover:p,onClick:d,isEllipsisPlaceholder:_},null,8,[`tmNode`,`paddingLeft`,`collapsed`,`disabled`,`iconMarginRight`,`maxIconSize`,`activeIconSize`,`title`,`extra`,`showArrow`,`childActive`,`clsPrefix`,`icon`,`hover`,`onClick`,`isEllipsisPlaceholder`]))],16)},i=()=>(o(),E(ie,null,{default:()=>{let{tmNodes:t,collapsed:n}=this;return n?null:(o(),S(`div`,{key:1,class:U(`${e}-submenu-children`),role:`menu`},[W(()=>t.map(e=>ht(e,this.menuProps)))],2))}},1024));return this.root?(o(),E(X,l({key:2,size:`large`,trigger:`hover`},this.menuProps?.dropdownProps,{themeOverrides:this.mergedTheme.peerOverrides.Dropdown,theme:this.mergedTheme.peers.Dropdown,builtinThemeOverrides:{fontSizeLarge:`14px`,optionIconSizeLarge:`18px`},value:this.mergedValue,disabled:!this.dropdownEnabled,placement:this.dropdownPlacement,keyField:this.menuProps.keyField,labelField:this.menuProps.labelField,childrenField:this.menuProps.childrenField,onUpdateShow:this.handlePopoverShowChange,options:this.rawNodes,onSelect:this.doSelect,inverted:this.inverted,renderIcon:t,renderLabel:n}),{default:()=>(o(),S(`div`,{class:U(`${e}-submenu`),role:`menu`,"aria-expanded":!this.collapsed,id:this.domId},[W(()=>r()),this.isHorizontal?W(()=>null):(o(),S(_,{key:1},[W(()=>i())],64))],10,ct))},1040,[`themeOverrides`,`theme`,`value`,`disabled`,`placement`,`keyField`,`labelField`,`childrenField`,`onUpdateShow`,`options`,`onSelect`,`inverted`,`renderIcon`,`renderLabel`])):(o(),S(`div`,{key:3,class:U(`${e}-submenu`),role:`menu`,"aria-expanded":!this.collapsed,id:this.domId},[W(()=>r()),W(()=>i())],10,lt))}});function pt(e){return e.type===`divider`||e.type===`render`}function mt(e){return e.type===`divider`}function ht(e,t){let{rawNode:n}=e,{show:r}=n;if(r===!1)return null;if(pt(n))return mt(n)?(o(),E(Ye,l({key:e.key},n.props),null,16)):null;let{labelField:i}=t,{key:a,level:s,isGroup:c}=e,u={...n,title:n.title||n[i],extra:n.titleExtra||n.extra,key:a,internalKey:a,level:s,root:s===0,isGroup:c};return e.children?e.isGroup?j(st,xe(u,ot,{tmNode:e,tmNodes:e.children,key:a})):j(ft,xe(u,dt,{key:a,rawNodes:n[t.childrenField],tmNodes:e.children,tmNode:e})):j(it,xe(u,rt,{key:a,tmNode:e}))}var gt={...L.props,options:{type:Array,default:()=>[]},collapsed:{type:Boolean,default:void 0},collapsedWidth:{type:Number,default:48},iconSize:{type:Number,default:20},collapsedIconSize:{type:Number,default:24},rootIndent:Number,indent:{type:Number,default:32},labelField:{type:String,default:`label`},keyField:{type:String,default:`key`},childrenField:{type:String,default:`children`},disabledField:{type:String,default:`disabled`},defaultExpandAll:Boolean,defaultExpandedKeys:Array,expandedKeys:Array,value:[String,Number],defaultValue:{type:[String,Number],default:null},mode:{type:String,default:`vertical`},watchProps:{type:Array,default:void 0},disabled:Boolean,show:{type:Boolean,default:!0},inverted:Boolean,"onUpdate:expandedKeys":[Function,Array],onUpdateExpandedKeys:[Function,Array],onUpdateValue:[Function,Array],"onUpdate:value":[Function,Array],expandIcon:Function,renderIcon:Function,renderLabel:Function,renderExtra:Function,dropdownProps:Object,accordion:Boolean,nodeProps:Function,dropdownPlacement:{type:String,default:`bottom`},responsive:Boolean,items:Array,onOpenNamesChange:[Function,Array],onSelect:[Function,Array],onExpandedNamesChange:[Function,Array],expandedNames:Array,defaultExpandedNames:Array},_t=M({name:`Menu`,inheritAttrs:!1,props:gt,setup(t){let{mergedClsPrefixRef:n,inlineThemeDisabled:r}=se(t),a=L(`Menu`,`-menu`,Je,he,t,n),s=i(Ue,null),c=b(()=>{let{collapsed:e}=t;if(e!==void 0)return e;if(s){let{collapseModeRef:e,collapsedRef:t}=s;if(e.value===`width`)return t.value??!1}return!1}),l=b(()=>{let{keyField:e,childrenField:n,disabledField:r}=t;return fe(t.items||t.options,{getIgnored(e){return pt(e)},getChildren(e){return e[n]},getDisabled(e){return e[r]},getKey(t){return t[e]??t.name}})}),u=b(()=>new Set(l.value.treeNodes.map(e=>e.key))),{watchProps:d}=t,f=O(null);d?.includes(`defaultValue`)?h(()=>{f.value=t.defaultValue}):f.value=t.defaultValue;let p=C(t,`value`),m=Ce(p,f),g=O([]),_=()=>{g.value=t.defaultExpandAll?l.value.getNonLeafKeys():t.defaultExpandedNames||t.defaultExpandedKeys||l.value.getPath(m.value,{includeSelf:!1}).keyPath};d?.includes(`defaultExpandedKeys`)?h(_):_();let v=Se(t,[`expandedNames`,`expandedKeys`]),y=Ce(v,g),x=b(()=>l.value.treeNodes),S=b(()=>l.value.getPath(m.value).keyPath);P(Q,{props:t,mergedCollapsedRef:c,mergedThemeRef:a,mergedValueRef:m,mergedExpandedKeysRef:y,activePathRef:S,mergedClsPrefixRef:n,isHorizontalRef:b(()=>t.mode===`horizontal`),invertedRef:C(t,`inverted`),doSelect:w,toggleExpand:D});function w(e,n){let{"onUpdate:value":r,onUpdateValue:i,onSelect:a}=t;i&&I(i,e,n),r&&I(r,e,n),a&&I(a,e,n),f.value=e}function T(e){let{"onUpdate:expandedKeys":n,onUpdateExpandedKeys:r,onExpandedNamesChange:i,onOpenNamesChange:a}=t;n&&I(n,e),r&&I(r,e),i&&I(i,e),a&&I(a,e),g.value=e}function D(e){let n=Array.from(y.value),r=n.findIndex(t=>t===e);if(~r)n.splice(r,1);else{if(t.accordion&&u.value.has(e)){let e=n.findIndex(e=>u.value.has(e));e>-1&&n.splice(e,1)}n.push(e)}T(n)}let k=e=>{let n=l.value.getPath(e??m.value,{includeSelf:!1}).keyPath;if(!n.length)return;let r=Array.from(y.value),i=new Set([...r,...n]);t.accordion&&u.value.forEach(e=>{i.has(e)&&!n.includes(e)&&i.delete(e)}),T(Array.from(i))},A=b(()=>{let{inverted:e}=t,{common:{cubicBezierEaseInOut:n},self:r}=a.value,{borderRadius:i,borderColorHorizontal:o,fontSize:s,itemHeight:c,dividerColor:l}=r,u={"--n-divider-color":l,"--n-bezier":n,"--n-font-size":s,"--n-border-color-horizontal":o,"--n-border-radius":i,"--n-item-height":c};return e?(u[`--n-group-text-color`]=r.groupTextColorInverted,u[`--n-color`]=r.colorInverted,u[`--n-item-text-color`]=r.itemTextColorInverted,u[`--n-item-text-color-hover`]=r.itemTextColorHoverInverted,u[`--n-item-text-color-active`]=r.itemTextColorActiveInverted,u[`--n-item-text-color-child-active`]=r.itemTextColorChildActiveInverted,u[`--n-item-text-color-child-active-hover`]=r.itemTextColorChildActiveInverted,u[`--n-item-text-color-active-hover`]=r.itemTextColorActiveHoverInverted,u[`--n-item-icon-color`]=r.itemIconColorInverted,u[`--n-item-icon-color-hover`]=r.itemIconColorHoverInverted,u[`--n-item-icon-color-active`]=r.itemIconColorActiveInverted,u[`--n-item-icon-color-active-hover`]=r.itemIconColorActiveHoverInverted,u[`--n-item-icon-color-child-active`]=r.itemIconColorChildActiveInverted,u[`--n-item-icon-color-child-active-hover`]=r.itemIconColorChildActiveHoverInverted,u[`--n-item-icon-color-collapsed`]=r.itemIconColorCollapsedInverted,u[`--n-item-text-color-horizontal`]=r.itemTextColorHorizontalInverted,u[`--n-item-text-color-hover-horizontal`]=r.itemTextColorHoverHorizontalInverted,u[`--n-item-text-color-active-horizontal`]=r.itemTextColorActiveHorizontalInverted,u[`--n-item-text-color-child-active-horizontal`]=r.itemTextColorChildActiveHorizontalInverted,u[`--n-item-text-color-child-active-hover-horizontal`]=r.itemTextColorChildActiveHoverHorizontalInverted,u[`--n-item-text-color-active-hover-horizontal`]=r.itemTextColorActiveHoverHorizontalInverted,u[`--n-item-icon-color-horizontal`]=r.itemIconColorHorizontalInverted,u[`--n-item-icon-color-hover-horizontal`]=r.itemIconColorHoverHorizontalInverted,u[`--n-item-icon-color-active-horizontal`]=r.itemIconColorActiveHorizontalInverted,u[`--n-item-icon-color-active-hover-horizontal`]=r.itemIconColorActiveHoverHorizontalInverted,u[`--n-item-icon-color-child-active-horizontal`]=r.itemIconColorChildActiveHorizontalInverted,u[`--n-item-icon-color-child-active-hover-horizontal`]=r.itemIconColorChildActiveHoverHorizontalInverted,u[`--n-arrow-color`]=r.arrowColorInverted,u[`--n-arrow-color-hover`]=r.arrowColorHoverInverted,u[`--n-arrow-color-active`]=r.arrowColorActiveInverted,u[`--n-arrow-color-active-hover`]=r.arrowColorActiveHoverInverted,u[`--n-arrow-color-child-active`]=r.arrowColorChildActiveInverted,u[`--n-arrow-color-child-active-hover`]=r.arrowColorChildActiveHoverInverted,u[`--n-item-color-hover`]=r.itemColorHoverInverted,u[`--n-item-color-active`]=r.itemColorActiveInverted,u[`--n-item-color-active-hover`]=r.itemColorActiveHoverInverted,u[`--n-item-color-active-collapsed`]=r.itemColorActiveCollapsedInverted):(u[`--n-group-text-color`]=r.groupTextColor,u[`--n-color`]=r.color,u[`--n-item-text-color`]=r.itemTextColor,u[`--n-item-text-color-hover`]=r.itemTextColorHover,u[`--n-item-text-color-active`]=r.itemTextColorActive,u[`--n-item-text-color-child-active`]=r.itemTextColorChildActive,u[`--n-item-text-color-child-active-hover`]=r.itemTextColorChildActiveHover,u[`--n-item-text-color-active-hover`]=r.itemTextColorActiveHover,u[`--n-item-icon-color`]=r.itemIconColor,u[`--n-item-icon-color-hover`]=r.itemIconColorHover,u[`--n-item-icon-color-active`]=r.itemIconColorActive,u[`--n-item-icon-color-active-hover`]=r.itemIconColorActiveHover,u[`--n-item-icon-color-child-active`]=r.itemIconColorChildActive,u[`--n-item-icon-color-child-active-hover`]=r.itemIconColorChildActiveHover,u[`--n-item-icon-color-collapsed`]=r.itemIconColorCollapsed,u[`--n-item-text-color-horizontal`]=r.itemTextColorHorizontal,u[`--n-item-text-color-hover-horizontal`]=r.itemTextColorHoverHorizontal,u[`--n-item-text-color-active-horizontal`]=r.itemTextColorActiveHorizontal,u[`--n-item-text-color-child-active-horizontal`]=r.itemTextColorChildActiveHorizontal,u[`--n-item-text-color-child-active-hover-horizontal`]=r.itemTextColorChildActiveHoverHorizontal,u[`--n-item-text-color-active-hover-horizontal`]=r.itemTextColorActiveHoverHorizontal,u[`--n-item-icon-color-horizontal`]=r.itemIconColorHorizontal,u[`--n-item-icon-color-hover-horizontal`]=r.itemIconColorHoverHorizontal,u[`--n-item-icon-color-active-horizontal`]=r.itemIconColorActiveHorizontal,u[`--n-item-icon-color-active-hover-horizontal`]=r.itemIconColorActiveHoverHorizontal,u[`--n-item-icon-color-child-active-horizontal`]=r.itemIconColorChildActiveHorizontal,u[`--n-item-icon-color-child-active-hover-horizontal`]=r.itemIconColorChildActiveHoverHorizontal,u[`--n-arrow-color`]=r.arrowColor,u[`--n-arrow-color-hover`]=r.arrowColorHover,u[`--n-arrow-color-active`]=r.arrowColorActive,u[`--n-arrow-color-active-hover`]=r.arrowColorActiveHover,u[`--n-arrow-color-child-active`]=r.arrowColorChildActive,u[`--n-arrow-color-child-active-hover`]=r.arrowColorChildActiveHover,u[`--n-item-color-hover`]=r.itemColorHover,u[`--n-item-color-active`]=r.itemColorActive,u[`--n-item-color-active-hover`]=r.itemColorActiveHover,u[`--n-item-color-active-collapsed`]=r.itemColorActiveCollapsed),u}),j=r?te(`menu`,b(()=>t.inverted?`a`:`b`),A,t):void 0,M=e(),N=O(null),F=O(null),ee=!0,R=()=>{ee?ee=!1:N.value?.sync({showAllItemsBeforeCalculate:!0})};function ne(){return document.getElementById(M)}let z=O(-1);function B(e){z.value=t.options.length-e}function V(e){e||(z.value=-1)}let H=b(()=>{let e=z.value;return{children:e===-1?[]:t.options.slice(e)}}),U=b(()=>{let{childrenField:e,disabledField:n,keyField:r}=t;return fe([H.value],{getIgnored(e){return pt(e)},getChildren(t){return t[e]},getDisabled(e){return e[n]},getKey(e){return e[r]??e.name}})}),W=b(()=>fe([{}]).treeNodes[0]);function re(){if(z.value===-1)return o(),E(ft,{root:!0,level:0,key:`__ellpisisGroupPlaceholder__`,internalKey:`__ellpisisGroupPlaceholder__`,title:`···`,tmNode:W.value,domId:M,isEllipsisPlaceholder:!0},null,8,[`tmNode`,`domId`]);let e=U.value.treeNodes[0],t=S.value,n=!!e.children?.some(e=>t.includes(e.key));return o(),E(ft,{level:0,root:!0,key:`__ellpisisGroup__`,internalKey:`__ellpisisGroup__`,title:`···`,virtualChildActive:n,tmNode:e,domId:M,rawNodes:e.rawNode.children||[],tmNodes:e.children||[],isEllipsisPlaceholder:!0},null,8,[`virtualChildActive`,`tmNode`,`domId`,`rawNodes`,`tmNodes`])}return{mergedClsPrefix:n,controlledExpandedKeys:v,uncontrolledExpanededKeys:g,mergedExpandedKeys:y,uncontrolledValue:f,mergedValue:m,activePath:S,tmNodes:x,mergedTheme:a,mergedCollapsed:c,cssVars:r?void 0:A,themeClass:j?.themeClass,overflowRef:N,counterRef:F,updateCounter:()=>{},onResize:R,onUpdateOverflow:V,onUpdateCount:B,renderCounter:re,getCounter:ne,onRender:j?.onRender,showOption:k,deriveResponsiveState:R}},render(){let{mergedClsPrefix:e,mode:n,themeClass:r,onRender:i}=this;i?.();let a=()=>this.tmNodes.map(e=>ht(e,this.$props)),s=n===`horizontal`&&this.responsive,c=()=>j(`div`,l(this.$attrs,{role:n===`horizontal`?`menubar`:`menu`,class:[`${e}-menu`,r,`${e}-menu--${n}`,s&&`${e}-menu--responsive`,this.mergedCollapsed&&`${e}-menu--collapsed`],style:this.cssVars}),s?(o(),E(de,{key:2,ref:`overflowRef`,onUpdateOverflow:this.onUpdateOverflow,getCounter:this.getCounter,onUpdateCount:this.onUpdateCount,updateCounter:this.updateCounter,style:{width:`100%`,display:`flex`,overflow:`hidden`}},{default:a,counter:this.renderCounter},1032,[`onUpdateOverflow`,`getCounter`,`onUpdateCount`,`updateCounter`])):a());return s?(o(),E(t,{key:3,onResize:this.onResize},{default:c},1032,[`onResize`])):c()}}),vt={class:`flex items-center`},yt={__name:`BreadCrumb`,setup(e){let t=Z(),n=je(),r=ge(),i=O([]);g(()=>n.name,e=>{i.value=a(r.permissions,e)},{immediate:!0});function a(e,t,n=[]){for(let r of e){if(r.code===t)return[...n,r];if(r.children?.length){let e=a(r.children,t,[...n,r]);if(e)return e}}return null}function c(e){e.path&&e.code!==n.name&&t.push(e.path)}function l(e=[]){return e.filter(e=>e.show).map(e=>({label:e.name,key:e.code,icon:()=>j(`i`,{class:e.icon})}))}function u(e){e&&e!==n.name&&t.push({name:e})}return(e,t)=>{let r=Be,a=X,p=Re;return o(),E(p,null,{default:f(()=>[A(i)?.length?(o(!0),S(_,{key:1},m(A(i),(e,t)=>(o(),E(r,{key:e.code,clickable:!!e.path,onClick:t=>c(e)},{default:f(()=>[N(a,{options:t<A(i).length-1?l(e.children):[],onSelect:u},{default:f(()=>[x(`div`,vt,[x(`i`,{class:s([e.icon,`mr-8`])},null,2),k(` `+d(e.name),1)])]),_:2},1032,[`options`])]),_:2},1032,[`clickable`,`onClick`]))),128)):(o(),E(r,{key:0,clickable:!1},{default:f(()=>[k(d(A(n).meta.title),1)]),_:1}))]),_:1})}}},bt={__name:`MenuCollapse`,setup(e){let t=_e();return(e,n)=>(o(),S(`div`,{id:`menu-collapse`,class:`f-c-c cursor-pointer rounded-4 auto-bg-hover p-6 text-22 transition-all-300`,onClick:n[0]||=(...e)=>A(t).switchCollapsed&&A(t).switchCollapsed(...e)},[x(`i`,{class:s(A(t).collapsed?`i-line-md-menu-unfold-left`:`i-line-md-menu-fold-left`)},null,2)]))}},xt={getUser:()=>J.get(`/user/detail`),refreshToken:()=>J.get(`/auth/refresh/token`),logout:()=>J.post(`/auth/logout`,{},{needTip:!1}),switchCurrentRole:e=>J.post(`/auth/current-role/switch/${e}`),getRolePermissions:()=>J.get(`/role/permissions/tree`),validateMenuPath:e=>J.get(`/permission/menu/validate?path=${e}`)},St={class:`flex`},Ct={__name:`RoleSelect`,setup(e,{expose:t}){let n=ve(),r=me(),i=O(n.roles||[]),a=O(n.currentRole?.code??i.value[0]?.code??``),[c,l]=Ne();function u(e){c.value?.open({...e})}async function p(){try{l.value=!0;let{data:e}=await xt.switchCurrentRole(a.value);await r.switchCurrentRole(e),l.value=!1,$message.success(`切换成功`),c.value?.handleOk()}catch(e){return console.error(e),l.value=!1,!1}}async function h(){await xt.logout(),r.logout(),c.value?.close(),$message.success(`已退出登录`)}return t({open:u}),(e,t)=>{let r=He,u=Ae,g=Ee,y=ce;return o(),E(A(Pe),{ref_key:`modalRef`,ref:c,title:`请选择角色`,width:`360px`,class:`p-12`},{footer:f(()=>[x(`div`,St,[N(y,{class:`flex-1`,size:`large`,onClick:t[1]||=e=>h()},{default:f(()=>[...t[2]||=[k(` 退出登录 `,-1)]]),_:1}),N(y,{loading:A(l),class:`ml-20 flex-1`,type:`primary`,size:`large`,disabled:A(n).currentRole?.code===A(a),onClick:p},{default:f(()=>[...t[3]||=[k(` 确认 `,-1)]]),_:1},8,[`loading`,`disabled`])])]),default:f(()=>[N(g,{value:A(a),"onUpdate:value":t[0]||=e=>v(a)?a.value=e:null,class:`cus-scroll-y max-h-420 w-full py-16`},{default:f(()=>[N(u,{vertical:``,size:24,class:`mx-12`},{default:f(()=>[(o(!0),S(_,null,m(A(i),e=>(o(),E(r,{key:e.id,class:s([`h-36 w-full text-center text-16 leading-36`,{"bg-primary! color-white!":e.code===A(a)}]),value:e.code},{default:f(()=>[k(d(e.name),1)]),_:2},1032,[`class`,`value`]))),128))]),_:1})]),_:1},8,[`value`])]),_:1},512)}}},wt=`/admin/assets/isme-D6AR05SU.png`,Tt={},Et={class:`h-32 w-32 rounded-4 bg-primary`};function Dt(e,t){return o(),S(`div`,Et,[...t[0]||=[x(`img`,{src:wt,alt:`Logo`},null,-1)]])}var Ot=D(Tt,[[`render`,Dt]]),kt={__name:`SideLogo`,setup(e){let t=_e();return(e,n)=>{let i=Ot,a=p(`router-link`);return o(),E(a,{class:`h-60 f-c-c`,to:`/`},{default:f(()=>[N(i),r(x(`h2`,{class:`ml-10 max-w-140 flex-shrink-0 text-16 color-primary font-bold`},d(A(`学境・StudyScape — 学习的新境界`)),513),[[oe,!A(t).collapsed]])]),_:1})}}},At={__name:`SideMenu`,setup(e){let t=Z(),n=je(),r=_e(),i=ge(),a=b(()=>n.meta?.parentKey||n.name),s=O(null);g(n,async()=>{await w(),s.value?.showOption()});function c(e,n){if(ye(n.originPath))$dialog.confirm({type:`info`,title:`请选择打开方式`,positiveText:`外链打开`,negativeText:`在本站内嵌打开`,confirm(){window.open(n.originPath)},cancel:()=>{t.push(n.path)}});else{if(!n.path)return;t.push(n.path)}}return(e,t)=>{let n=_t;return o(),E(n,{ref_key:`menu`,ref:s,class:`side-menu`,accordion:``,indent:18,"collapsed-icon-size":22,"collapsed-width":64,collapsed:A(r).collapsed,options:A(i).menus,value:A(a),"onUpdate:value":c},null,8,[`collapsed`,`options`,`value`])}}},jt={__name:`ContextMenu`,props:{show:{type:Boolean,default:!1},currentPath:{type:String,default:``},x:{type:Number,default:0},y:{type:Number,default:0}},emits:[`update:show`],setup(e,{emit:t}){let n=e,r=t,i=be(),a=b(()=>[{label:`重新加载`,key:`reload`,disabled:n.currentPath!==i.activeTab,icon:()=>j(`i`,{class:`i-mdi:refresh text-14`})},{label:`关闭`,key:`close`,disabled:i.tabs.length<=1,icon:()=>j(`i`,{class:`i-mdi:close text-14`})},{label:`关闭其他`,key:`close-other`,disabled:i.tabs.length<=1,icon:()=>j(`i`,{class:`i-mdi:arrow-expand-horizontal text-14`})},{label:`关闭左侧`,key:`close-left`,disabled:i.tabs.length<=1||n.currentPath===i.tabs[0].path,icon:()=>j(`i`,{class:`i-mdi:arrow-expand-left text-14`})},{label:`关闭右侧`,key:`close-right`,disabled:i.tabs.length<=1||n.currentPath===i.tabs[i.tabs.length-1].path,icon:()=>j(`i`,{class:`i-mdi:arrow-expand-right text-14`})}]),s=je(),c=new Map([[`reload`,()=>{i.reloadTab(s.fullPath,s.meta?.keepAlive)}],[`close`,()=>{i.removeTab(n.currentPath)}],[`close-other`,()=>{i.removeOther(n.currentPath)}],[`close-left`,()=>{i.removeLeft(n.currentPath)}],[`close-right`,()=>{i.removeRight(n.currentPath)}]]);function l(){r(`update:show`,!1)}function u(e){let t=c.get(e);typeof t==`function`&&t(),l()}return(t,n)=>{let r=X;return o(),E(r,{show:e.show,options:A(a),x:e.x,y:e.y,placement:`bottom-start`,onClickoutside:l,onSelect:u},null,8,[`show`,`options`,`x`,`y`])}}},Mt={id:`top-tab`},Nt=D({__name:`index`,setup(e){let t=Z(),n=be(),r=y({show:!1,x:0,y:0,currentPath:``});function i(e){n.setActiveTab(e),t.push(e)}function a(){r.show=!0}function s(){r.show=!1}function c(e,t,n){Object.assign(r,{x:e,y:t,currentPath:n})}async function l(e,t){let{clientX:n,clientY:r}=e;s(),c(n,r,t.path),await w(),a()}return(e,t)=>{let a=le,s=ue;return o(),S(`div`,Mt,[N(s,{value:A(n).activeTab,closable:A(n).tabs.length>1,type:`card`,onClose:t[0]||=e=>A(n).removeTab(e)},{default:f(()=>[(o(!0),S(_,null,m(A(n).tabs,e=>(o(),E(a,{key:e.path,name:e.path,onClick:t=>i(e.path),onContextmenu:re(t=>l(t,e),[`prevent`])},{default:f(()=>[k(d(e.title),1)]),_:2},1032,[`name`,`onClick`,`onContextmenu`]))),128))]),_:1},8,[`value`,`closable`]),A(r).show?(o(),E(jt,{key:0,show:A(r).show,"onUpdate:show":t[1]||=e=>A(r).show=e,"current-path":A(r).currentPath,x:A(r).x,y:A(r).y},null,8,[`show`,`current-path`,`x`,`y`])):T(``,!0)])}}},[[`__scopeId`,`data-v-871ef196`]]),Pt={id:`user-dropdown`,class:`flex cursor-pointer items-center`},Ft={key:0,class:`ml-12 flex-col flex-shrink-0 items-center`},It={class:`text-14`},Lt={class:`text-12 opacity-50`},Rt={__name:`UserAvatar`,setup(e){let t=Z(),n=ve(),r=me(),i=ge(),a=y([{label:`个人资料`,key:`profile`,icon:()=>j(`i`,{class:`i-material-symbols:person-outline text-14`}),show:b(()=>i.accessRoutes?.some(e=>e.path===`/profile`))},{label:`切换角色`,key:`toggleRole`,icon:()=>j(`i`,{class:`i-basil:exchange-solid text-14`}),show:b(()=>n.roles.length>1)},{label:`退出登录`,key:`logout`,icon:()=>j(`i`,{class:`i-mdi:exit-to-app text-14`})}]),s=O(null);function c(e){switch(e){case`profile`:t.push(`/profile`);break;case`toggleRole`:s.value?.open({onOk(){location.reload()}});break;case`logout`:$dialog.confirm({title:`提示`,type:`info`,content:`确认退出？`,async confirm(){try{await xt.logout()}catch(e){console.error(e)}r.logout(),$message.success(`已退出登录`)}})}}return(e,t)=>{let r=Oe,i=X;return o(),S(_,null,[N(i,{options:A(a),onSelect:c},{default:f(()=>[x(`div`,Pt,[N(r,{round:``,size:36,src:A(n).avatar},null,8,[`src`]),A(n).userInfo?(o(),S(`div`,Ft,[x(`span`,It,d(A(n).nickName??A(n).username),1),x(`span`,Lt,`[`+d(A(n).currentRole?.name)+`]`,1)])):T(``,!0)])]),_:1},8,[`options`]),N(A(Ct),{ref_key:`roleSelectRef`,ref:s},null,512)],64)}}},zt=[`aria-label`],Bt={class:`profile-link__name`},Vt=D({__name:`SessionProfileLink`,props:{to:{type:String,required:!0}},setup(e){let t=e,n=Z(),r=Me(),i=O(``),a=b(()=>r.user?.nickname?.trim()||r.user?.username||`用户`),s=b(()=>a.value.slice(0,1).toUpperCase()),c=b(()=>`https://api.dicebear.com/9.x/initials/svg?seed=${encodeURIComponent(a.value)}`);return g(()=>r.user?.avatar,e=>{i.value=e||``},{immediate:!0}),g(()=>t.to,()=>{i.value=r.user?.avatar||``}),(t,r)=>{let l=Oe;return o(),S(`button`,{class:`profile-link`,type:`button`,"aria-label":`打开${A(a)}的个人资料`,onClick:r[1]||=t=>A(n).push(e.to)},[N(l,{round:``,size:36,src:A(i),"fallback-src":A(c),onError:r[0]||=e=>i.value=``},{default:f(()=>[k(d(A(s)),1)]),_:1},8,[`src`,`fallback-src`]),x(`span`,Bt,d(A(a)),1)],8,zt)}}},[[`__scopeId`,`data-v-2d19f33d`]]);export{kt as a,At as i,Rt as n,bt as o,Nt as r,yt as s,Vt as t};