import{B as e,D as t,Z as n,j as r,z as i}from"./php-modules-BAZCMuH3.js";import{D as a,E as o,F as s,K as c,L as l,M as u,S as d,Y as f,d as p,dt as m,f as h,i as g,kt as _,l as v,m as y,ot as b,q as x,u as S,w as C,y as w}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as T,A as E,Bt as D,C as ee,F as O,I as k,N as A,O as j,Qt as M,Ut as N,Xt as P,Yt as F,at as I,ct as L,en as R,gt as z,it as te,ln as ne,pt as re,qt as ie,sn as B,st as V,t as H,tn as U,ut as W,vt as G}from"./Button-CjW_3_5b.js";import{s as K}from"./light-5ZEtuWM4.js";import{i as ae,n as oe,r as se,t as ce}from"./cssr-DSJD8hf8.js";import{t as le}from"./ChevronRight-BBrfgzkb.js";import{h as ue}from"./light-svItRArZ.js";import{t as de}from"./Add-C_QLGO_Q.js";import{n as fe}from"./light-D5vo7uwi.js";var pe=/\s/;function me(e){for(var t=e.length;t--&&pe.test(e.charAt(t)););return t}var he=/^\s+/;function ge(e){return e&&e.slice(0,me(e)+1).replace(he,``)}var q=NaN,J=/^[-+]0x[0-9a-f]+$/i,Y=/^0b[01]+$/i,X=/^0o[0-7]+$/i,_e=parseInt;function ve(e){if(typeof e==`number`)return e;if(N(e))return q;if(D(e)){var t=typeof e.valueOf==`function`?e.valueOf():e;e=D(t)?t+``:t}if(typeof e!=`string`)return e===0?e:+e;e=ge(e);var n=Y.test(e);return n||X.test(e)?_e(e.slice(2),n?2:8):J.test(e)?q:+e}var Z=function(){return ie.Date.now()},ye=`Expected a function`,Q=Math.max,be=Math.min;function xe(e,t,n){var r,i,a,o,s,c,l=0,u=!1,d=!1,f=!0;if(typeof e!=`function`)throw TypeError(ye);t=ve(t)||0,D(n)&&(u=!!n.leading,d=`maxWait`in n,a=d?Q(ve(n.maxWait)||0,t):a,f=`trailing`in n?!!n.trailing:f);function p(t){var n=r,a=i;return r=i=void 0,l=t,o=e.apply(a,n),o}function m(e){return l=e,s=setTimeout(_,t),u?p(e):o}function h(e){var n=e-c,r=e-l,i=t-n;return d?be(i,a-r):i}function g(e){var n=e-c,r=e-l;return c===void 0||n>=t||n<0||d&&r>=a}function _(){var e=Z();if(g(e))return v(e);s=setTimeout(_,h(e))}function v(e){return s=void 0,f&&r?p(e):(r=i=void 0,o)}function y(){s!==void 0&&clearTimeout(s),l=0,r=c=i=s=void 0}function b(){return s===void 0?o:v(Z())}function x(){var e=Z(),n=g(e);if(r=arguments,i=this,c=e,n){if(s===void 0)return m(c);if(d)return clearTimeout(s),s=setTimeout(_,t),p(c)}return s===void 0&&(s=setTimeout(_,t)),o}return x.cancel=y,x.flush=b,x}var Se=`Expected a function`;function Ce(e,t,n){var r=!0,i=!0;if(typeof e!=`function`)throw TypeError(Se);return D(n)&&(r=`leading`in n?!!n.leading:r,i=`trailing`in n?!!n.trailing:i),xe(e,t,{leading:r,maxWait:t,trailing:i})}var we=ce(`.v-x-scroll`,{overflow:`auto`,scrollbarWidth:`none`},[ce(`&::-webkit-scrollbar`,{width:0,height:0})]),$=w({name:`XScroll`,props:{disabled:Boolean,onScroll:Function},setup(){let e=b(null);function t(e){e.currentTarget.offsetWidth<e.currentTarget.scrollWidth&&e.deltaY!==0&&(e.currentTarget.scrollLeft+=e.deltaY+e.deltaX,e.preventDefault())}let n=W();return we.mount({id:`vueuc/x-scroll`,head:!0,anchorMetaName:oe,ssr:n}),Object.assign({selfRef:e,handleWheel:t},{scrollTo(...t){var n;(n=e.value)==null||n.scrollTo(...t)}})},render(){return d(`div`,{ref:`selfRef`,onScroll:this.onScroll,onWheel:this.disabled?void 0:this.handleWheel,class:`v-x-scroll`},this.$slots)}}),Te=w({name:`ChevronLeft`,render(){return(()=>{let e=te(`dfe229c2639b2082`);return e[0]||=p(`svg`,{viewBox:`0 0 16 16`,fill:`none`,xmlns:`http://www.w3.org/2000/svg`},[p(`path`,{d:`M10.3536 3.14645C10.5488 3.34171 10.5488 3.65829 10.3536 3.85355L6.20711 8L10.3536 12.1464C10.5488 12.3417 10.5488 12.6583 10.3536 12.8536C10.1583 13.0488 9.84171 13.0488 9.64645 12.8536L5.14645 8.35355C4.95118 8.15829 4.95118 7.84171 5.14645 7.64645L9.64645 3.14645C9.84171 2.95118 10.1583 2.95118 10.3536 3.14645Z`,fill:`currentColor`})],-1)})()}}),Ee=z(`n-tabs`),De={tab:[String,Number,Object,Function],name:{type:[String,Number],required:!0},disabled:Boolean,displayDirective:{type:String,default:`if`},closable:{type:Boolean,default:void 0},tabProps:Object,label:[String,Number,Object,Function]},Oe=w({__TAB_PANE__:!0,name:`TabPane`,alias:[`TabPanel`],props:De,slots:Object,setup(e){let t=C(Ee,null);return t||G(`tab-pane`,"`n-tab-pane` must be placed inside `n-tabs`."),{style:t.paneStyleRef,class:t.paneClassRef,mergedClsPrefix:t.mergedClsPrefixRef}},render(){return s(),y(`div`,{class:I([`${this.mergedClsPrefix}-tab-pane`,this.class]),style:_(this.style)},[L(()=>this.$slots.default?.())],6)}}),ke=[`data-name`,`data-disabled`],Ae={internalLeftPadded:Boolean,internalAddable:Boolean,internalCreatedByPane:Boolean,...ue(De,[`displayDirective`])},je=w({__TAB__:!0,inheritAttrs:!1,name:`Tab`,props:Ae,setup(e){let{mergedClsPrefixRef:t,valueRef:n,typeRef:r,closableRef:i,tabStyleRef:a,addTabStyleRef:o,tabClassRef:s,addTabClassRef:c,tabChangeIdRef:l,onBeforeLeaveRef:u,triggerRef:d,handleAdd:f,activateTab:p,handleClose:m}=C(Ee);return{trigger:d,mergedClosable:S(()=>{if(e.internalAddable)return!1;let{closable:t}=e;return t===void 0?i.value:t}),style:a,addStyle:o,tabClass:s,addTabClass:c,clsPrefix:t,value:n,type:r,handleClose(t){t.stopPropagation(),!e.disabled&&m(e.name)},activateTab(){if(e.disabled)return;if(e.internalAddable){f();return}let{name:t}=e,r=++l.id;if(t!==n.value){let{value:i}=u;i?Promise.resolve(i(e.name,n.value)).then(e=>{e&&l.id===r&&p(t)}):p(t)}}}},render(){let{internalAddable:e,clsPrefix:n,name:r,disabled:i,label:a,tab:c,value:l,mergedClosable:u,trigger:d,$slots:{default:f}}=this,m=a??c;return s(),y(`div`,{class:I(`${n}-tabs-tab-wrapper`)},[this.internalLeftPadded?(s(),y(`div`,{key:0,class:I(`${n}-tabs-tab-pad`)},null,2)):L(()=>null),(s(),y(`div`,o({key:r,"data-name":r,"data-disabled":i?!0:void 0},o({class:[`${n}-tabs-tab`,l===r&&`${n}-tabs-tab--active`,i&&`${n}-tabs-tab--disabled`,u&&`${n}-tabs-tab--closable`,e&&`${n}-tabs-tab--addable`,e?this.addTabClass:this.tabClass],onClick:d===`click`?this.activateTab:void 0,onMouseenter:d===`hover`?this.activateTab:void 0,style:e?this.addStyle:this.style},this.internalCreatedByPane?this.tabProps||{}:this.$attrs)),[p(`span`,{class:I(`${n}-tabs-tab__label`)},[e?(s(),y(g,{key:0},[p(`div`,{class:I(`${n}-tabs-tab__height-placeholder`)},`\xA0`,2),(s(),h(A,{clsPrefix:n},{default:()=>(s(),h(de))},1032,[`clsPrefix`]))],64)):(s(),y(g,{key:1},[f?(s(),y(g,{key:0},[L(()=>f())],64)):(s(),y(g,{key:1},[typeof m==`object`?(s(),y(g,{key:0},[L(()=>m)],64)):(s(),y(g,{key:1},[L(()=>K(m??r))],64))],64))],64))],2),u&&this.type===`card`?(s(),h(t,{key:0,clsPrefix:n,class:I(`${n}-tabs-tab__close`),onClick:this.handleClose,disabled:i},null,8,[`clsPrefix`,`class`,`onClick`,`disabled`])):L(()=>null)],16,ke))],2)}}),Me=P(`tabs`,`
 box-sizing: border-box;
 width: 100%;
 display: flex;
 flex-direction: column;
 transition:
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
`,[F(`&.transition-disabled`,[P(`tabs-tab`,`
 transition: none !important;
 `),P(`tabs-nav-scroll-content`,`
 transition: none !important;
 `),P(`tabs-tab-pad`,`
 transition: none !important;
 `)]),T(`segment-type`,[P(`tabs-rail`,[F(`&.transition-disabled`,[P(`tabs-capsule`,`
 transition: none;
 `)])])]),T(`top`,[P(`tab-pane`,`
 padding: var(--n-pane-padding-top) var(--n-pane-padding-right) var(--n-pane-padding-bottom) var(--n-pane-padding-left);
 `)]),T(`left`,[P(`tab-pane`,`
 padding: var(--n-pane-padding-right) var(--n-pane-padding-bottom) var(--n-pane-padding-left) var(--n-pane-padding-top);
 `)]),T(`left, right`,`
 flex-direction: row;
 `,[P(`tabs-bar`,`
 width: 2px;
 right: 0;
 transition:
 top .2s var(--n-bezier),
 max-height .2s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `),P(`tabs-tab`,`
 padding: var(--n-tab-padding-vertical); 
 `)]),T(`right`,`
 flex-direction: row-reverse;
 `,[P(`tab-pane`,`
 padding: var(--n-pane-padding-left) var(--n-pane-padding-top) var(--n-pane-padding-right) var(--n-pane-padding-bottom);
 `),P(`tabs-bar`,`
 left: 0;
 `)]),T(`bottom`,`
 flex-direction: column-reverse;
 justify-content: flex-end;
 `,[P(`tab-pane`,`
 padding: var(--n-pane-padding-bottom) var(--n-pane-padding-right) var(--n-pane-padding-top) var(--n-pane-padding-left);
 `),P(`tabs-bar`,`
 top: 0;
 `)]),P(`tabs-rail`,`
 position: relative;
 padding: 3px;
 border-radius: var(--n-tab-border-radius);
 width: 100%;
 background-color: var(--n-color-segment);
 transition: background-color .3s var(--n-bezier);
 display: flex;
 align-items: center;
 `,[P(`tabs-capsule`,`
 border-radius: var(--n-tab-border-radius);
 position: absolute;
 left: 0;
 top: 0;
 pointer-events: none;
 background-color: var(--n-tab-color-segment);
 box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .08);
 transition: transform 0.3s var(--n-bezier);
 `),P(`tabs-tab-wrapper`,`
 flex-basis: 0;
 flex-grow: 1;
 display: flex;
 align-items: center;
 justify-content: center;
 `,[P(`tabs-tab`,`
 overflow: hidden;
 border-radius: var(--n-tab-border-radius);
 width: 100%;
 display: flex;
 align-items: center;
 justify-content: center;
 `,[T(`active`,`
 font-weight: var(--n-font-weight-strong);
 color: var(--n-tab-text-color-active);
 `),F(`&:hover`,`
 color: var(--n-tab-text-color-hover);
 `)])])]),T(`flex`,[P(`tabs-nav`,`
 width: 100%;
 position: relative;
 `,[P(`tabs-wrapper`,`
 width: 100%;
 `,[P(`tabs-tab`,`
 margin-right: 0;
 `)])])]),P(`tabs-nav`,`
 box-sizing: border-box;
 line-height: 1.5;
 display: flex;
 transition: border-color .3s var(--n-bezier);
 `,[M(`prefix, suffix`,`
 display: flex;
 align-items: center;
 `),M(`prefix`,`padding-right: 16px;`),M(`suffix`,`padding-left: 16px;`)]),T(`top, bottom`,[F(`>`,[P(`tabs-nav`,[P(`tabs-nav-scroll-wrapper`,[F(`&::before`,`
 top: 0;
 bottom: 0;
 left: 0;
 width: 20px;
 `),F(`&::after`,`
 top: 0;
 bottom: 0;
 right: 0;
 width: 20px;
 `),T(`shadow-start`,[F(`&::before`,`
 box-shadow: inset 10px 0 8px -8px rgba(0, 0, 0, .12);
 `)]),T(`shadow-end`,[F(`&::after`,`
 box-shadow: inset -10px 0 8px -8px rgba(0, 0, 0, .12);
 `)])])])])]),T(`left, right`,[P(`tabs-nav-scroll-content`,`
 flex-direction: column;
 `),F(`>`,[P(`tabs-nav`,[P(`tabs-nav-scroll-wrapper`,[F(`&::before`,`
 top: 0;
 left: 0;
 right: 0;
 height: 20px;
 `),F(`&::after`,`
 bottom: 0;
 left: 0;
 right: 0;
 height: 20px;
 `),T(`shadow-start`,[F(`&::before`,`
 box-shadow: inset 0 10px 8px -8px rgba(0, 0, 0, .12);
 `)]),T(`shadow-end`,[F(`&::after`,`
 box-shadow: inset 0 -10px 8px -8px rgba(0, 0, 0, .12);
 `)])])])])]),P(`tabs-nav-scroll-wrapper`,`
 flex: 1;
 position: relative;
 overflow: hidden;
 `,[P(`tabs-nav-y-scroll`,`
 height: 100%;
 width: 100%;
 overflow-y: auto; 
 scrollbar-width: none;
 `,[F(`&::-webkit-scrollbar, &::-webkit-scrollbar-track-piece, &::-webkit-scrollbar-thumb`,`
 width: 0;
 height: 0;
 display: none;
 `)]),F(`&::before, &::after`,`
 transition: box-shadow .3s var(--n-bezier);
 pointer-events: none;
 content: "";
 position: absolute;
 z-index: 1;
 `),F(`&.transition-disabled`,[F(`&::before, &::after`,`
 transition: none;
 `)])]),P(`tabs-nav-scroll-content`,`
 display: flex;
 position: relative;
 min-width: 100%;
 min-height: 100%;
 width: fit-content;
 box-sizing: border-box;
 `),P(`tabs-wrapper`,`
 display: inline-flex;
 flex-wrap: nowrap;
 position: relative;
 `),P(`tabs-tab-wrapper`,`
 display: flex;
 flex-wrap: nowrap;
 flex-shrink: 0;
 flex-grow: 0;
 `),P(`tabs-tab`,`
 cursor: pointer;
 white-space: nowrap;
 flex-wrap: nowrap;
 display: inline-flex;
 align-items: center;
 color: var(--n-tab-text-color);
 font-size: var(--n-tab-font-size);
 background-clip: padding-box;
 padding: var(--n-tab-padding);
 transition:
 box-shadow .3s var(--n-bezier),
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[T(`disabled`,{cursor:`not-allowed`}),M(`close`,`
 margin-inline-start: 6px;
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `),M(`label`,`
 display: flex;
 align-items: center;
 z-index: 1;
 `)]),P(`tabs-bar`,`
 position: absolute;
 bottom: 0;
 height: 2px;
 border-radius: 1px;
 background-color: var(--n-bar-color);
 transition:
 left .2s var(--n-bezier),
 max-width .2s var(--n-bezier),
 opacity .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `,[F(`&.transition-disabled`,`
 transition: none;
 `),T(`disabled`,`
 background-color: var(--n-tab-text-color-disabled)
 `)]),P(`tabs-pane-wrapper`,`
 position: relative;
 overflow: hidden;
 transition: max-height .2s var(--n-bezier);
 `),P(`tab-pane`,`
 color: var(--n-pane-text-color);
 width: 100%;
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 opacity .2s var(--n-bezier);
 left: 0;
 right: 0;
 top: 0;
 `,[F(`&.next-transition-leave-active, &.prev-transition-leave-active, &.next-transition-enter-active, &.prev-transition-enter-active`,`
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 transform .2s var(--n-bezier),
 opacity .2s var(--n-bezier);
 `),F(`&.next-transition-leave-active, &.prev-transition-leave-active`,`
 position: absolute;
 `),F(`&.next-transition-enter-from, &.prev-transition-leave-to`,`
 transform: translateX(32px);
 opacity: 0;
 `),F(`&.next-transition-leave-to, &.prev-transition-enter-from`,`
 transform: translateX(-32px);
 opacity: 0;
 `),F(`&.next-transition-leave-from, &.next-transition-enter-to, &.prev-transition-leave-from, &.prev-transition-enter-to`,`
 transform: translateX(0);
 opacity: 1;
 `)]),P(`tabs-tab-pad`,`
 box-sizing: border-box;
 width: var(--n-tab-gap);
 flex-grow: 0;
 flex-shrink: 0;
 `),T(`line-type, bar-type`,[P(`tabs-tab`,`
 font-weight: var(--n-tab-font-weight);
 box-sizing: border-box;
 vertical-align: bottom;
 `,[F(`&:hover`,{color:`var(--n-tab-text-color-hover)`}),T(`active`,`
 color: var(--n-tab-text-color-active);
 font-weight: var(--n-tab-font-weight-active);
 `),T(`disabled`,{color:`var(--n-tab-text-color-disabled)`})])]),P(`tabs-nav`,[M(`prefix, suffix`,`
 border-color: var(--n-tab-border-color);
 `),P(`tabs-nav-scroll-content`,`
 border-color: var(--n-tab-border-color);
 `),T(`line-type`,[T(`top`,[M(`prefix, suffix`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),P(`tabs-nav-scroll-content`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),P(`tabs-bar`,`
 bottom: -1px;
 `)]),T(`left`,[M(`prefix, suffix`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),P(`tabs-nav-scroll-content`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),P(`tabs-bar`,`
 right: -1px;
 `)]),T(`right`,[M(`prefix, suffix`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),P(`tabs-nav-scroll-content`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),P(`tabs-bar`,`
 left: -1px;
 `)]),T(`bottom`,[M(`prefix, suffix`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),P(`tabs-nav-scroll-content`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),P(`tabs-bar`,`
 top: -1px;
 `)]),M(`prefix, suffix`,`
 transition: border-color .3s var(--n-bezier);
 `),P(`tabs-nav-scroll-content`,`
 transition: border-color .3s var(--n-bezier);
 `),P(`tabs-bar`,`
 border-radius: 0;
 `)]),T(`card-type`,[M(`prefix, suffix`,`
 transition: border-color .3s var(--n-bezier);
 `),P(`tabs-pad`,`
 flex-grow: 1;
 transition: border-color .3s var(--n-bezier);
 `),P(`tabs-tab-pad`,`
 transition: border-color .3s var(--n-bezier);
 `),P(`tabs-tab`,`
 font-weight: var(--n-tab-font-weight);
 border: 1px solid var(--n-tab-border-color);
 background-color: var(--n-tab-color);
 box-sizing: border-box;
 position: relative;
 vertical-align: bottom;
 display: flex;
 justify-content: space-between;
 font-size: var(--n-tab-font-size);
 color: var(--n-tab-text-color);
 `,[T(`addable`,`
 padding-left: 8px;
 padding-right: 8px;
 font-size: 16px;
 justify-content: center;
 `,[M(`height-placeholder`,`
 width: 0;
 font-size: var(--n-tab-font-size);
 `),R(`disabled`,[F(`&:hover`,`
 color: var(--n-tab-text-color-hover);
 `)])]),T(`closable`,`padding-inline-end: 8px;`),T(`active`,`
 background-color: #0000;
 font-weight: var(--n-tab-font-weight-active);
 color: var(--n-tab-text-color-active);
 `),T(`disabled`,`color: var(--n-tab-text-color-disabled);`)])]),T(`left, right`,`
 flex-direction: column; 
 `,[M(`prefix, suffix`,`
 padding: var(--n-tab-padding-vertical);
 `),P(`tabs-wrapper`,`
 flex-direction: column;
 `),P(`tabs-tab-wrapper`,`
 flex-direction: column;
 `,[P(`tabs-tab-pad`,`
 height: var(--n-tab-gap-vertical);
 width: 100%;
 `)])]),T(`top`,[T(`card-type`,[P(`tabs-scroll-padding`,`border-bottom: 1px solid var(--n-tab-border-color);`),M(`prefix, suffix`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),P(`tabs-tab`,`
 border-top-left-radius: var(--n-tab-border-radius);
 border-top-right-radius: var(--n-tab-border-radius);
 `,[T(`active`,`
 border-bottom: 1px solid #0000;
 `)]),P(`tabs-tab-pad`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),P(`tabs-pad`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `)])]),T(`left`,[T(`card-type`,[P(`tabs-scroll-padding`,`border-right: 1px solid var(--n-tab-border-color);`),M(`prefix, suffix`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),P(`tabs-tab`,`
 border-top-left-radius: var(--n-tab-border-radius);
 border-bottom-left-radius: var(--n-tab-border-radius);
 `,[T(`active`,`
 border-right: 1px solid #0000;
 `)]),P(`tabs-tab-pad`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),P(`tabs-pad`,`
 border-right: 1px solid var(--n-tab-border-color);
 `)])]),T(`right`,[T(`card-type`,[P(`tabs-scroll-padding`,`border-left: 1px solid var(--n-tab-border-color);`),M(`prefix, suffix`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),P(`tabs-tab`,`
 border-top-right-radius: var(--n-tab-border-radius);
 border-bottom-right-radius: var(--n-tab-border-radius);
 `,[T(`active`,`
 border-left: 1px solid #0000;
 `)]),P(`tabs-tab-pad`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),P(`tabs-pad`,`
 border-left: 1px solid var(--n-tab-border-color);
 `)])]),T(`bottom`,[T(`card-type`,[P(`tabs-scroll-padding`,`border-top: 1px solid var(--n-tab-border-color);`),M(`prefix, suffix`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),P(`tabs-tab`,`
 border-bottom-left-radius: var(--n-tab-border-radius);
 border-bottom-right-radius: var(--n-tab-border-radius);
 `,[T(`active`,`
 border-top: 1px solid #0000;
 `)]),P(`tabs-tab-pad`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),P(`tabs-pad`,`
 border-top: 1px solid var(--n-tab-border-color);
 `)])])]),P(`tabs-scroll-button`,[T(`start`,`
 padding-left: 10px;
 padding-right: 6px;
 `),T(`end`,`
 padding-right: 10px;
 padding-left: 6px;
 `),T(`up`,`
 padding-bottom: 10px;
 `),T(`down`,`
 padding-top: 10px;
 `)])]),Ne=w({name:`TabsButton`,props:{type:{type:String,default:`next`},mergedClsPrefix:{type:String,required:!0},vertical:Boolean,disabled:Boolean,rtl:Boolean,theme:Object,themeOverrides:Object,onClick:Function},setup(e){return{handleClick:()=>{e.disabled||e.onClick?.(e.type)}}},render(){let{mergedClsPrefix:e,disabled:t,type:n,vertical:r,rtl:i,theme:a,themeOverrides:o,handleClick:c}=this,l=n===`next`,u=r?l:i?!l:l;return s(),h(H,{text:!0,disabled:t,size:`small`,theme:a,themeOverrides:o,onClick:c,class:I([`${e}-tabs-scroll-button`,!r&&n===`prev`&&`${e}-tabs-scroll-button--start`,!r&&n===`next`&&`${e}-tabs-scroll-button--end`,r&&n===`prev`&&`${e}-tabs-scroll-button--up`,r&&n===`next`&&`${e}-tabs-scroll-button--down`])},{icon:()=>(s(),h(A,{clsPrefix:e,style:_(r?{transform:`rotate(90deg)`}:void 0)},{default:()=>u?(s(),h(le,{key:1})):(s(),h(Te,{key:2}))},1032,[`clsPrefix`,`style`]))},1032,[`disabled`,`theme`,`themeOverrides`,`onClick`,`class`])}}),Pe=Ce,Fe={...O.props,value:[String,Number],defaultValue:[String,Number],trigger:{type:String,default:`click`},type:{type:String,default:`bar`},closable:Boolean,justifyContent:String,size:String,placement:{type:String,default:`top`},tabStyle:[String,Object],tabClass:String,addTabStyle:[String,Object],addTabClass:String,barWidth:Number,paneClass:String,paneStyle:[String,Object],paneWrapperClass:String,paneWrapperStyle:[String,Object],addable:[Boolean,Object],tabsPadding:{type:Number,default:0},animated:Boolean,onBeforeLeave:Function,onAdd:Function,"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array],onClose:[Function,Array],labelSize:String,activeName:[String,Number],onActiveNameChange:[Function,Array],showScrollButton:Boolean,centerActiveTab:Boolean},Ie=w({name:`Tabs`,props:Fe,slots:Object,setup(t,{slots:r}){let{mergedClsPrefixRef:o,inlineThemeDisabled:s,mergedComponentPropsRef:d,mergedRtlRef:f}=re(t),p=ee(`Tabs`,f,o),h=S(()=>{let{placement:e}=t;return e===`start`?p?.value?`right`:`left`:e===`end`?p?.value?`left`:`right`:e}),g=O(`Tabs`,`-tabs`,Me,fe,t,o),_=b(null),v=b(null),y=b(null),C=b(null),w=b(null),T=b(null),D=b(null),A=b(!0),j=b(!0),M=se(t,[`labelSize`,`size`]),N=S(()=>M.value?M.value:d?.value?.Tabs?.size||`medium`),P=se(t,[`activeName`,`value`]),F=b(P.value??t.defaultValue??(r.default?i(r.default())[0]?.props?.name:null)),I=e(P,F),L={id:0},R=S(()=>{if(t.justifyContent&&t.type!==`card`)return{display:`flex`,justifyContent:t.justifyContent}});c(I,()=>{L.id=0,B(),a(()=>{H()})});function z(){let{value:e}=I;return e===null?null:_.value?.querySelector(`[data-name="${e}"]`)}function te(e){if(t.type===`card`)return;let{value:n}=y;if(!n)return;let r=n.style.opacity===`0`;if(e){let i=`${o.value}-tabs-bar--disabled`,{barWidth:a}=t,s=h.value;if(e.dataset.disabled===`true`?n.classList.add(i):n.classList.remove(i),[`top`,`bottom`].includes(s)){if(ie([`top`,`maxHeight`,`height`]),typeof a==`number`&&e.offsetWidth>=a){let t=Math.floor((e.offsetWidth-a)/2)+e.offsetLeft;n.style.left=`${t}px`,n.style.maxWidth=`${a}px`}else n.style.left=`${e.offsetLeft}px`,n.style.maxWidth=`${e.offsetWidth}px`;n.style.width=`8192px`,r&&(n.style.transition=`none`),n.offsetWidth,r&&(n.style.transition=``,n.style.opacity=`1`)}else{if(ie([`left`,`maxWidth`,`width`]),typeof a==`number`&&e.offsetHeight>=a){let t=Math.floor((e.offsetHeight-a)/2)+e.offsetTop;n.style.top=`${t}px`,n.style.maxHeight=`${a}px`}else n.style.top=`${e.offsetTop}px`,n.style.maxHeight=`${e.offsetHeight}px`;n.style.height=`8192px`,r&&(n.style.transition=`none`),n.offsetHeight,r&&(n.style.transition=``,n.style.opacity=`1`)}}}function ne(){if(t.type===`card`)return;let{value:e}=y;e&&(e.style.opacity=`0`)}function ie(e){let{value:t}=y;if(t)for(let n of e)t.style[n]=``}function B(){if(t.type===`card`)return;let e=z();e?te(e):ne()}function V(e,t,n,r){let i=e.getBoundingClientRect(),a=t.getBoundingClientRect(),o=n?`left`:`top`,s=n?`right`:`bottom`,c=0;r?c=(a[o]+a[s])/2-(i[o]+i[s])/2:a[o]<i[o]?c=a[o]-i[o]:a[s]>i[s]&&(c=a[s]-i[s]),c!==0&&e.scrollBy({[o]:c,behavior:`smooth`})}function H(){let e=[`top`,`bottom`].includes(h.value),n=z();if(n){if(e){let r=T.value?.$el;if(!r)return;V(r,n,e,t.centerActiveTab)}else{let{value:r}=D;if(!r)return;V(r,n,e,t.centerActiveTab)}}}let W=b(null),G=0,K=null;function oe(e){let t=W.value;if(t){G=e.getBoundingClientRect().height;let n=`${G}px`,r=()=>{t.style.height=n,t.style.maxHeight=n};K?(r(),K(),K=null):K=r}}function ce(e){let t=W.value;if(t){let n=e.getBoundingClientRect().height,r=()=>{document.body.offsetHeight,t.style.maxHeight=`${n}px`,t.style.height=`${Math.max(G,n)}px`};K?(K(),K=null,r()):K=r}}function le(){let e=W.value;if(e){e.style.maxHeight=``,e.style.height=``;let{paneWrapperStyle:n}=t;if(typeof n==`string`)e.style.cssText=n;else if(n){let{maxHeight:t,height:r}=n;t!==void 0&&(e.style.maxHeight=t),r!==void 0&&(e.style.height=r)}}}let ue={value:[]},de=b(`next`);function pe(e){let t=I.value,n=`next`;for(let r of ue.value){if(r===t)break;if(r===e){n=`prev`;break}}de.value=n,me(e)}function me(e){let{onActiveNameChange:n,onUpdateValue:r,"onUpdate:value":i}=t;n&&E(n,e),r&&E(r,e),i&&E(i,e),F.value=e}function he(e){let{onClose:n}=t;n&&E(n,e)}function ge(e){if([`top`,`bottom`].includes(h.value)){let{value:t}=T;if(!t)return;let n=t.$el;if(!n)return;let r=n.offsetWidth,i=!!p?.value,a=e===`next`?r:-r;n.scrollBy({left:i?-a:a,behavior:`smooth`})}else{let{value:t}=D;if(!t)return;let n=t.offsetHeight,r=e===`next`?t.scrollTop+n:t.scrollTop-n;t.scrollTo({top:r,left:0,behavior:`smooth`})}}let q=!0;function J(){let{value:e}=y;if(!e)return;q&&=!1;let t=`transition-disabled`;e.classList.add(t),B(),e.classList.remove(t)}let Y=b(null);function X({transitionDisabled:e}){let t=_.value;if(!t)return;e&&t.classList.add(`transition-disabled`);let n=z();n&&Y.value&&(Y.value.style.width=`${n.offsetWidth}px`,Y.value.style.height=`${n.offsetHeight}px`,Y.value.style.transform=`translate(${n.offsetLeft}px, ${n.offsetTop}px)`,e&&Y.value.offsetWidth),e&&t.classList.remove(`transition-disabled`)}c([I],()=>{t.type===`segment`&&a(()=>{X({transitionDisabled:!1})})}),u(()=>{t.type===`segment`&&X({transitionDisabled:!0})});let _e=0;function ve(e){if(e.contentRect.width===0&&e.contentRect.height===0||_e===e.contentRect.width)return;_e=e.contentRect.width;let{type:n}=t;(n===`line`||n===`bar`)&&(q||t.justifyContent?.startsWith(`space`))&&J(),n!==`segment`&&$(we())}let Z=Pe(ve,64);function ye(){let{type:e}=t;e===`line`||e===`bar`?J():e===`segment`&&X({transitionDisabled:!0})}c([()=>t.justifyContent,()=>t.size],()=>{a(()=>{(t.type===`line`||t.type===`bar`)&&J()})}),c([h,()=>p?.value],()=>{a(()=>{ye(),$(we(),{instantly:!0})})}),c(()=>t.type,()=>{a(()=>{let e=v.value;e&&(e.classList.add(`transition-disabled`),ye(),e.offsetWidth,e.classList.remove(`transition-disabled`))})});let Q=b(!1);function be(e){let{target:t,contentRect:{width:n,height:r}}=e,i=t.parentElement.parentElement.offsetWidth,a=t.parentElement.parentElement.offsetHeight,o=h.value;if(!Q.value)o===`top`||o===`bottom`?i<n&&(Q.value=!0):a<r&&(Q.value=!0);else{let{value:e}=w;if(!e)return;o===`top`||o===`bottom`?i-n>e.$el.offsetWidth&&(Q.value=!1):a-r>e.$el.offsetHeight&&(Q.value=!1)}$(T.value?.$el||null)}let xe=Pe(be,64);function Se(){let{onAdd:e}=t;e&&e()}let Ce=b(!1);function we(){let e=h.value;return(e===`top`||e===`bottom`?T.value?.$el:D.value)||null}function $(e,t={instantly:!1}){if(!e)return;let n=t.instantly?C.value:null;n&&n.classList.add(`transition-disabled`);let r=h.value;if(r===`top`||r===`bottom`){let{scrollLeft:t,scrollWidth:n,offsetWidth:r}=e,i=Math.abs(t);A.value=i<=1,j.value=i+r>=n-1,Ce.value=r<n-1}else{let{scrollTop:t,scrollHeight:n,offsetHeight:r}=e;A.value=t<=1,j.value=t+r>=n-1,Ce.value=r<n-1}n&&(n.offsetWidth,n.classList.remove(`transition-disabled`))}let Te=Pe(e=>{$(e.target)},64);l(Ee,{triggerRef:m(t,`trigger`),tabStyleRef:m(t,`tabStyle`),tabClassRef:m(t,`tabClass`),addTabStyleRef:m(t,`addTabStyle`),addTabClassRef:m(t,`addTabClass`),paneClassRef:m(t,`paneClass`),paneStyleRef:m(t,`paneStyle`),mergedClsPrefixRef:o,typeRef:m(t,`type`),closableRef:m(t,`closable`),valueRef:I,tabChangeIdRef:L,onBeforeLeaveRef:m(t,`onBeforeLeave`),activateTab:pe,handleClose:he,handleAdd:Se}),ae(()=>{B(),H()}),x(()=>{let{value:e}=C;if(!e)return;let{value:t}=o,n=`${t}-tabs-nav-scroll-wrapper--shadow-start`,r=`${t}-tabs-nav-scroll-wrapper--shadow-end`;A.value?e.classList.remove(n):e.classList.add(n),j.value?e.classList.remove(r):e.classList.add(r)});let De={syncBarPosition:()=>{B()},scrollToCurrentTab:()=>{H()}},Oe=()=>{X({transitionDisabled:!0})},ke=S(()=>{let{value:e}=N,{type:r}=t,i=`${e}${{card:`Card`,bar:`Bar`,line:`Line`,segment:`Segment`}[r]}`,{self:{barColor:a,closeIconColor:o,closeIconColorHover:s,closeIconColorPressed:c,tabColor:l,tabBorderColor:u,paneTextColor:d,tabFontWeight:f,tabBorderRadius:p,tabFontWeightActive:m,colorSegment:h,fontWeightStrong:_,tabColorSegment:v,closeSize:y,closeIconSize:b,closeColorHover:x,closeColorPressed:S,closeBorderRadius:C,[U(`panePadding`,e)]:w,[U(`tabPadding`,i)]:T,[U(`tabPaddingVertical`,i)]:E,[U(`tabGap`,i)]:D,[U(`tabGap`,`${i}Vertical`)]:ee,[U(`tabTextColor`,r)]:O,[U(`tabTextColorActive`,r)]:k,[U(`tabTextColorHover`,r)]:A,[U(`tabTextColorDisabled`,r)]:j,[U(`tabFontSize`,e)]:M},common:{cubicBezierEaseInOut:P}}=g.value;return{"--n-bezier":P,"--n-color-segment":h,"--n-bar-color":a,"--n-tab-font-size":M,"--n-tab-text-color":O,"--n-tab-text-color-active":k,"--n-tab-text-color-disabled":j,"--n-tab-text-color-hover":A,"--n-pane-text-color":d,"--n-tab-border-color":u,"--n-tab-border-radius":p,"--n-close-size":y,"--n-close-icon-size":b,"--n-close-color-hover":x,"--n-close-color-pressed":S,"--n-close-border-radius":C,"--n-close-icon-color":o,"--n-close-icon-color-hover":s,"--n-close-icon-color-pressed":c,"--n-tab-color":l,"--n-tab-font-weight":f,"--n-tab-font-weight-active":m,"--n-tab-padding":T,"--n-tab-padding-vertical":E,"--n-tab-gap":D,"--n-tab-gap-vertical":ee,"--n-pane-padding-left":n(w,`left`),"--n-pane-padding-right":n(w,`right`),"--n-pane-padding-top":n(w,`top`),"--n-pane-padding-bottom":n(w,`bottom`),"--n-font-weight-strong":_,"--n-tab-color-segment":v}}),Ae=s?k(`tabs`,S(()=>`${N.value[0]}${t.type[0]}`),ke,t):void 0;return{mergedClsPrefix:o,mergedValue:I,renderedNames:new Set,segmentCapsuleElRef:Y,tabsPaneWrapperRef:W,tabsElRef:_,selfElRef:v,barElRef:y,addTabInstRef:w,xScrollInstRef:T,scrollWrapperElRef:C,addTabFixed:Q,tabWrapperStyle:R,handleNavResize:Z,mergedSize:N,handleScroll:Te,handleTabsResize:xe,cssVars:s?void 0:ke,themeClass:Ae?.themeClass,animationDirection:de,renderNameListRef:ue,yScrollElRef:D,handleSegmentResize:Oe,onAnimationBeforeLeave:oe,onAnimationEnter:ce,onAnimationAfterEnter:le,onRender:Ae?.onRender,startReachedRef:A,endReachedRef:j,isOverflow:Ce,handleButtonClick:ge,mergedTheme:g,rtlEnabled:p,mergedPlacement:h,...De}},render(){let{mergedClsPrefix:e,type:t,mergedPlacement:n,addTabFixed:a,addable:c,mergedSize:l,renderNameListRef:u,onRender:d,paneWrapperClass:f,paneWrapperStyle:m,startReachedRef:v,endReachedRef:b,isOverflow:x,showScrollButton:S,handleButtonClick:C,mergedTheme:w,rtlEnabled:T,$slots:{default:E,prefix:D,suffix:ee}}=this;d?.();let O=E?i(E()).filter(e=>e.type.__TAB_PANE__===!0):[],k=E?i(E()).filter(e=>e.type.__TAB__===!0):[],A=!k.length,M=t===`card`,N=t===`segment`,P=!M&&!N&&this.justifyContent;u.value=[];let F=()=>{let t=(s(),y(`div`,{style:_(this.tabWrapperStyle),class:I(`${e}-tabs-wrapper`)},[P?L(()=>null):(s(),y(`div`,{key:1,class:I(`${e}-tabs-scroll-padding`),style:_(n===`top`||n===`bottom`?{width:`${this.tabsPadding}px`}:{height:`${this.tabsPadding}px`})},null,6)),A?(s(),y(g,{key:2},[L(()=>O.map((e,t)=>(u.value.push(e.props.name),Be((s(),h(je,o(e.props,{internalCreatedByPane:!0,internalLeftPadded:t!==0&&(!P||P===`center`||P===`start`||P===`end`)}),V(e.children?{default:e.children.tab}:void 0),1040,[`internalLeftPadded`]))))))],64)):(s(),y(g,{key:3},[L(()=>k.map((e,t)=>(u.value.push(e.props.name),Be(t!==0&&!P?ze(e):e))))],64)),!a&&c&&M?(s(),y(g,{key:4},[L(()=>Re(c,(A?O.length:k.length)!==0))],64)):L(()=>null),P?L(()=>null):(s(),y(`div`,{key:7,class:I(`${e}-tabs-scroll-padding`),style:_({width:`${this.tabsPadding}px`})},null,6)),M?L(()=>null):(s(),y(`div`,{key:9,ref:`barElRef`,class:I(`${e}-tabs-bar`)},null,2))],6));return s(),y(`div`,{ref:`tabsElRef`,class:I(`${e}-tabs-nav-scroll-content`)},[M&&c?(s(),h(r,{key:0,onResize:this.handleTabsResize},{default:()=>t},1032,[`onResize`])):(s(),y(g,{key:1},[L(()=>t)],64)),M?(s(),y(`div`,{key:2,class:I(`${e}-tabs-pad`)},null,2)):L(()=>null)],2)},R=N?`top`:n;return s(),y(`div`,{ref:`selfElRef`,class:I([`${e}-tabs`,this.themeClass,`${e}-tabs--${t}-type`,`${e}-tabs--${l}-size`,P&&`${e}-tabs--flex`,`${e}-tabs--${R}`,T&&`${e}-tabs--rtl`]),style:_(this.cssVars)},[p(`div`,{class:I([`${e}-tabs-nav--${t}-type`,`${e}-tabs-nav--${R}`,`${e}-tabs-nav`])},[L(()=>j(D,t=>t&&(s(),y(`div`,{class:I(`${e}-tabs-nav__prefix`)},[L(()=>t)],2)))),N?(s(),h(r,{key:0,onResize:this.handleSegmentResize},{default:()=>(s(),y(`div`,{class:I(`${e}-tabs-rail`),ref:`tabsElRef`},[p(`div`,{class:I(`${e}-tabs-capsule`),ref:`segmentCapsuleElRef`},[p(`div`,{class:I(`${e}-tabs-wrapper`)},[p(`div`,{class:I(`${e}-tabs-tab`)},null,2)],2)],2),A?(s(),y(g,{key:0},[L(()=>O.map((e,t)=>(u.value.push(e.props.name),s(),h(je,o(e.props,{internalCreatedByPane:!0,internalLeftPadded:t!==0}),V(e.children?{default:e.children.tab}:void 0),1040,[`internalLeftPadded`]))))],64)):(s(),y(g,{key:1},[L(()=>k.map((e,t)=>(u.value.push(e.props.name),t===0?e:ze(e))))],64))],2))},1032,[`onResize`])):(s(),y(g,{key:1},[L(()=>S&&x&&(s(),h(Ne,{mergedClsPrefix:e,type:`prev`,vertical:R===`left`||R===`right`,disabled:v,rtl:!!T,theme:w.peers.Button,themeOverrides:w.peerOverrides.Button,onClick:C},null,8,[`mergedClsPrefix`,`vertical`,`disabled`,`rtl`,`theme`,`themeOverrides`,`onClick`]))),(s(),h(r,{onResize:this.handleNavResize},{default:()=>(s(),y(`div`,{class:I(`${e}-tabs-nav-scroll-wrapper`),ref:`scrollWrapperElRef`},[[`top`,`bottom`].includes(R)?(s(),h($,{key:0,ref:`xScrollInstRef`,onScroll:this.handleScroll},{default:F},1032,[`onScroll`])):(s(),y(`div`,{key:1,class:I(`${e}-tabs-nav-y-scroll`),onScroll:this.handleScroll,ref:`yScrollElRef`},[L(()=>F())],42,[`onScroll`]))],2))},1032,[`onResize`])),L(()=>S&&x&&(s(),h(Ne,{mergedClsPrefix:e,type:`next`,vertical:R===`left`||R===`right`,disabled:b,rtl:!!T,theme:w.peers.Button,themeOverrides:w.peerOverrides.Button,onClick:C},null,8,[`mergedClsPrefix`,`vertical`,`disabled`,`rtl`,`theme`,`themeOverrides`,`onClick`])))],64)),a&&c&&M?(s(),y(g,{key:2},[L(()=>Re(c,!0))],64)):L(()=>null),L(()=>j(ee,t=>t&&(s(),y(`div`,{class:I(`${e}-tabs-nav__suffix`)},[L(()=>t)],2))))],2),L(()=>A&&(this.animated&&(R===`top`||R===`bottom`)?(s(),y(`div`,{key:1,ref:`tabsPaneWrapperRef`,style:_(m),class:I([`${e}-tabs-pane-wrapper`,f])},[L(()=>Le(O,this.mergedValue,this.renderedNames,this.onAnimationBeforeLeave,this.onAnimationEnter,this.onAnimationAfterEnter,this.animationDirection))],6)):Le(O,this.mergedValue,this.renderedNames)))],6)}});function Le(e,t,n,r,i,a,o){let c=[];return e.forEach(e=>{let{name:r,displayDirective:i,"display-directive":a}=e.props,o=e=>i===e||a===e,s=t===r;if(e.key!==void 0&&(e.key=r),s||o(`show`)||o(`show:lazy`)&&n.has(r)){n.has(r)||n.add(r);let t=!o(`if`);c.push(t?f(e,[[ne,s]]):e)}}),o?(s(),h(B,{name:`${o}-transition`,onBeforeLeave:r,onEnter:i,onAfterEnter:a},{default:()=>c},1032,[`name`,`onBeforeLeave`,`onEnter`,`onAfterEnter`])):c}function Re(e,t){return s(),h(je,{ref:`addTabInstRef`,key:`__addable`,name:`__addable`,internalCreatedByPane:!0,internalAddable:!0,internalLeftPadded:t,disabled:typeof e==`object`&&e.disabled},null,8,[`internalLeftPadded`,`disabled`])}function ze(e){let t=v(e);return t.props?t.props.internalLeftPadded=!0:t.props={internalLeftPadded:!0},t}function Be(e){return Array.isArray(e.dynamicProps)?e.dynamicProps.includes(`internalLeftPadded`)||e.dynamicProps.push(`internalLeftPadded`):e.dynamicProps=[`internalLeftPadded`],e}export{je as n,Oe as r,Ie as t};