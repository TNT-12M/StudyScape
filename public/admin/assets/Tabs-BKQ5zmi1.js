import{A as e,P as t,Q as n,V as r}from"./php-modules-GekPVnxA.js";import{$ as i,E as a,L as o,Nt as s,O as c,P as l,X as u,Y as d,a as f,d as p,f as m,h,ht as g,k as _,p as v,u as y,ut as b,w as x,x as S,z as C}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as w,A as T,Bt as E,C as ee,F as D,I as O,N as k,O as A,Qt as j,Ut as M,Xt as N,Yt as P,at as F,ct as I,en as L,gt as R,it as te,ln as ne,pt as re,qt as ie,sn as z,st as B,t as V,tn as H,ut as U,vt as W}from"./Button-BtFx7EYM.js";import{s as G}from"./light-B0o7KsWP.js";import{i as ae,n as oe,r as se,t as ce}from"./cssr-BRvHbqtS.js";import{n as le}from"./format-length-BCoLE4J2.js";import{t as ue}from"./ChevronRight-C8utyV0C.js";import{i as de}from"./light-CAgdrZkk.js";import{t as fe}from"./Add-CQ1KBBNn.js";import{n as pe}from"./light-DBOFVMlJ.js";var me=/\s/;function he(e){for(var t=e.length;t--&&me.test(e.charAt(t)););return t}var ge=/^\s+/;function _e(e){return e&&e.slice(0,he(e)+1).replace(ge,``)}var K=NaN,q=/^[-+]0x[0-9a-f]+$/i,J=/^0b[01]+$/i,Y=/^0o[0-7]+$/i,ve=parseInt;function ye(e){if(typeof e==`number`)return e;if(M(e))return K;if(E(e)){var t=typeof e.valueOf==`function`?e.valueOf():e;e=E(t)?t+``:t}if(typeof e!=`string`)return e===0?e:+e;e=_e(e);var n=J.test(e);return n||Y.test(e)?ve(e.slice(2),n?2:8):q.test(e)?K:+e}var X=function(){return ie.Date.now()},be=`Expected a function`,Z=Math.max,xe=Math.min;function Se(e,t,n){var r,i,a,o,s,c,l=0,u=!1,d=!1,f=!0;if(typeof e!=`function`)throw TypeError(be);t=ye(t)||0,E(n)&&(u=!!n.leading,d=`maxWait`in n,a=d?Z(ye(n.maxWait)||0,t):a,f=`trailing`in n?!!n.trailing:f);function p(t){var n=r,a=i;return r=i=void 0,l=t,o=e.apply(a,n),o}function m(e){return l=e,s=setTimeout(_,t),u?p(e):o}function h(e){var n=e-c,r=e-l,i=t-n;return d?xe(i,a-r):i}function g(e){var n=e-c,r=e-l;return c===void 0||n>=t||n<0||d&&r>=a}function _(){var e=X();if(g(e))return v(e);s=setTimeout(_,h(e))}function v(e){return s=void 0,f&&r?p(e):(r=i=void 0,o)}function y(){s!==void 0&&clearTimeout(s),l=0,r=c=i=s=void 0}function b(){return s===void 0?o:v(X())}function x(){var e=X(),n=g(e);if(r=arguments,i=this,c=e,n){if(s===void 0)return m(c);if(d)return clearTimeout(s),s=setTimeout(_,t),p(c)}return s===void 0&&(s=setTimeout(_,t)),o}return x.cancel=y,x.flush=b,x}var Ce=`Expected a function`;function we(e,t,n){var r=!0,i=!0;if(typeof e!=`function`)throw TypeError(Ce);return E(n)&&(r=`leading`in n?!!n.leading:r,i=`trailing`in n?!!n.trailing:i),Se(e,t,{leading:r,maxWait:t,trailing:i})}var Te=ce(`.v-x-scroll`,{overflow:`auto`,scrollbarWidth:`none`},[ce(`&::-webkit-scrollbar`,{width:0,height:0})]),Q=S({name:`XScroll`,props:{disabled:Boolean,onScroll:Function},setup(){let e=b(null);function t(e){e.currentTarget.offsetWidth<e.currentTarget.scrollWidth&&e.deltaY!==0&&(e.currentTarget.scrollLeft+=e.deltaY+e.deltaX,e.preventDefault())}let n=U();return Te.mount({id:`vueuc/x-scroll`,head:!0,anchorMetaName:oe,ssr:n}),Object.assign({selfRef:e,handleWheel:t},{scrollTo(...t){var n;(n=e.value)==null||n.scrollTo(...t)}})},render(){return x(`div`,{ref:`selfRef`,onScroll:this.onScroll,onWheel:this.disabled?void 0:this.handleWheel,class:`v-x-scroll`},this.$slots)}}),Ee=S({name:`ChevronLeft`,render(){return(()=>{let e=te(`dfe229c2639b2082`);return e[0]||=m(`svg`,{viewBox:`0 0 16 16`,fill:`none`,xmlns:`http://www.w3.org/2000/svg`},[m(`path`,{d:`M10.3536 3.14645C10.5488 3.34171 10.5488 3.65829 10.3536 3.85355L6.20711 8L10.3536 12.1464C10.5488 12.3417 10.5488 12.6583 10.3536 12.8536C10.1583 13.0488 9.84171 13.0488 9.64645 12.8536L5.14645 8.35355C4.95118 8.15829 4.95118 7.84171 5.14645 7.64645L9.64645 3.14645C9.84171 2.95118 10.1583 2.95118 10.3536 3.14645Z`,fill:`currentColor`})],-1)})()}}),De=R(`n-tabs`),Oe={tab:[String,Number,Object,Function],name:{type:[String,Number],required:!0},disabled:Boolean,displayDirective:{type:String,default:`if`},closable:{type:Boolean,default:void 0},tabProps:Object,label:[String,Number,Object,Function]},ke=S({__TAB_PANE__:!0,name:`TabPane`,alias:[`TabPanel`],props:Oe,slots:Object,setup(e){let t=a(De,null);return t||W(`tab-pane`,"`n-tab-pane` must be placed inside `n-tabs`."),{style:t.paneStyleRef,class:t.paneClassRef,mergedClsPrefix:t.mergedClsPrefixRef}},render(){return o(),h(`div`,{class:F([`${this.mergedClsPrefix}-tab-pane`,this.class]),style:s(this.style)},[I(()=>this.$slots.default?.())],6)}}),Ae=[`data-name`,`data-disabled`],je={internalLeftPadded:Boolean,internalAddable:Boolean,internalCreatedByPane:Boolean,...de(Oe,[`displayDirective`])},$=S({__TAB__:!0,inheritAttrs:!1,name:`Tab`,props:je,setup(e){let{mergedClsPrefixRef:t,valueRef:n,typeRef:r,closableRef:i,tabStyleRef:o,addTabStyleRef:s,tabClassRef:c,addTabClassRef:l,tabChangeIdRef:u,onBeforeLeaveRef:d,triggerRef:f,handleAdd:m,activateTab:h,handleClose:g}=a(De);return{trigger:f,mergedClosable:p(()=>{if(e.internalAddable)return!1;let{closable:t}=e;return t===void 0?i.value:t}),style:o,addStyle:s,tabClass:c,addTabClass:l,clsPrefix:t,value:n,type:r,handleClose(t){t.stopPropagation(),!e.disabled&&g(e.name)},activateTab(){if(e.disabled)return;if(e.internalAddable){m();return}let{name:t}=e,r=++u.id;if(t!==n.value){let{value:i}=d;i?Promise.resolve(i(e.name,n.value)).then(e=>{e&&u.id===r&&h(t)}):h(t)}}}},render(){let{internalAddable:t,clsPrefix:n,name:r,disabled:i,label:a,tab:s,value:l,mergedClosable:u,trigger:d,$slots:{default:p}}=this,g=a??s;return o(),h(`div`,{class:F(`${n}-tabs-tab-wrapper`)},[this.internalLeftPadded?(o(),h(`div`,{key:0,class:F(`${n}-tabs-tab-pad`)},null,2)):I(()=>null),(o(),h(`div`,c({key:r,"data-name":r,"data-disabled":i?!0:void 0},c({class:[`${n}-tabs-tab`,l===r&&`${n}-tabs-tab--active`,i&&`${n}-tabs-tab--disabled`,u&&`${n}-tabs-tab--closable`,t&&`${n}-tabs-tab--addable`,t?this.addTabClass:this.tabClass],onClick:d===`click`?this.activateTab:void 0,onMouseenter:d===`hover`?this.activateTab:void 0,style:t?this.addStyle:this.style},this.internalCreatedByPane?this.tabProps||{}:this.$attrs)),[m(`span`,{class:F(`${n}-tabs-tab__label`)},[t?(o(),h(f,{key:0},[m(`div`,{class:F(`${n}-tabs-tab__height-placeholder`)},`\xA0`,2),(o(),v(k,{clsPrefix:n},{default:()=>(o(),v(fe))},1032,[`clsPrefix`]))],64)):(o(),h(f,{key:1},[p?(o(),h(f,{key:0},[I(()=>p())],64)):(o(),h(f,{key:1},[typeof g==`object`?(o(),h(f,{key:0},[I(()=>g)],64)):(o(),h(f,{key:1},[I(()=>G(g??r))],64))],64))],64))],2),u&&this.type===`card`?(o(),v(e,{key:0,clsPrefix:n,class:F(`${n}-tabs-tab__close`),onClick:this.handleClose,disabled:i},null,8,[`clsPrefix`,`class`,`onClick`,`disabled`])):I(()=>null)],16,Ae))],2)}}),Me=N(`tabs`,`
 box-sizing: border-box;
 width: 100%;
 display: flex;
 flex-direction: column;
 transition:
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
`,[P(`&.transition-disabled`,[N(`tabs-tab`,`
 transition: none !important;
 `),N(`tabs-nav-scroll-content`,`
 transition: none !important;
 `),N(`tabs-tab-pad`,`
 transition: none !important;
 `)]),w(`segment-type`,[N(`tabs-rail`,[P(`&.transition-disabled`,[N(`tabs-capsule`,`
 transition: none;
 `)])])]),w(`top`,[N(`tab-pane`,`
 padding: var(--n-pane-padding-top) var(--n-pane-padding-right) var(--n-pane-padding-bottom) var(--n-pane-padding-left);
 `)]),w(`left`,[N(`tab-pane`,`
 padding: var(--n-pane-padding-right) var(--n-pane-padding-bottom) var(--n-pane-padding-left) var(--n-pane-padding-top);
 `)]),w(`left, right`,`
 flex-direction: row;
 `,[N(`tabs-bar`,`
 width: 2px;
 right: 0;
 transition:
 top .2s var(--n-bezier),
 max-height .2s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `),N(`tabs-tab`,`
 padding: var(--n-tab-padding-vertical); 
 `)]),w(`right`,`
 flex-direction: row-reverse;
 `,[N(`tab-pane`,`
 padding: var(--n-pane-padding-left) var(--n-pane-padding-top) var(--n-pane-padding-right) var(--n-pane-padding-bottom);
 `),N(`tabs-bar`,`
 left: 0;
 `)]),w(`bottom`,`
 flex-direction: column-reverse;
 justify-content: flex-end;
 `,[N(`tab-pane`,`
 padding: var(--n-pane-padding-bottom) var(--n-pane-padding-right) var(--n-pane-padding-top) var(--n-pane-padding-left);
 `),N(`tabs-bar`,`
 top: 0;
 `)]),N(`tabs-rail`,`
 position: relative;
 padding: 3px;
 border-radius: var(--n-tab-border-radius);
 width: 100%;
 background-color: var(--n-color-segment);
 transition: background-color .3s var(--n-bezier);
 display: flex;
 align-items: center;
 `,[N(`tabs-capsule`,`
 border-radius: var(--n-tab-border-radius);
 position: absolute;
 left: 0;
 top: 0;
 pointer-events: none;
 background-color: var(--n-tab-color-segment);
 box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .08);
 transition: transform 0.3s var(--n-bezier);
 `),N(`tabs-tab-wrapper`,`
 flex-basis: 0;
 flex-grow: 1;
 display: flex;
 align-items: center;
 justify-content: center;
 `,[N(`tabs-tab`,`
 overflow: hidden;
 border-radius: var(--n-tab-border-radius);
 width: 100%;
 display: flex;
 align-items: center;
 justify-content: center;
 `,[w(`active`,`
 font-weight: var(--n-font-weight-strong);
 color: var(--n-tab-text-color-active);
 `),P(`&:hover`,`
 color: var(--n-tab-text-color-hover);
 `)])])]),w(`flex`,[N(`tabs-nav`,`
 width: 100%;
 position: relative;
 `,[N(`tabs-wrapper`,`
 width: 100%;
 `,[N(`tabs-tab`,`
 margin-right: 0;
 `)])])]),N(`tabs-nav`,`
 box-sizing: border-box;
 line-height: 1.5;
 display: flex;
 transition: border-color .3s var(--n-bezier);
 `,[j(`prefix, suffix`,`
 display: flex;
 align-items: center;
 `),j(`prefix`,`padding-right: 16px;`),j(`suffix`,`padding-left: 16px;`)]),w(`top, bottom`,[P(`>`,[N(`tabs-nav`,[N(`tabs-nav-scroll-wrapper`,[P(`&::before`,`
 top: 0;
 bottom: 0;
 left: 0;
 width: 20px;
 `),P(`&::after`,`
 top: 0;
 bottom: 0;
 right: 0;
 width: 20px;
 `),w(`shadow-start`,[P(`&::before`,`
 box-shadow: inset 10px 0 8px -8px rgba(0, 0, 0, .12);
 `)]),w(`shadow-end`,[P(`&::after`,`
 box-shadow: inset -10px 0 8px -8px rgba(0, 0, 0, .12);
 `)])])])])]),w(`left, right`,[N(`tabs-nav-scroll-content`,`
 flex-direction: column;
 `),P(`>`,[N(`tabs-nav`,[N(`tabs-nav-scroll-wrapper`,[P(`&::before`,`
 top: 0;
 left: 0;
 right: 0;
 height: 20px;
 `),P(`&::after`,`
 bottom: 0;
 left: 0;
 right: 0;
 height: 20px;
 `),w(`shadow-start`,[P(`&::before`,`
 box-shadow: inset 0 10px 8px -8px rgba(0, 0, 0, .12);
 `)]),w(`shadow-end`,[P(`&::after`,`
 box-shadow: inset 0 -10px 8px -8px rgba(0, 0, 0, .12);
 `)])])])])]),N(`tabs-nav-scroll-wrapper`,`
 flex: 1;
 position: relative;
 overflow: hidden;
 `,[N(`tabs-nav-y-scroll`,`
 height: 100%;
 width: 100%;
 overflow-y: auto; 
 scrollbar-width: none;
 `,[P(`&::-webkit-scrollbar, &::-webkit-scrollbar-track-piece, &::-webkit-scrollbar-thumb`,`
 width: 0;
 height: 0;
 display: none;
 `)]),P(`&::before, &::after`,`
 transition: box-shadow .3s var(--n-bezier);
 pointer-events: none;
 content: "";
 position: absolute;
 z-index: 1;
 `),P(`&.transition-disabled`,[P(`&::before, &::after`,`
 transition: none;
 `)])]),N(`tabs-nav-scroll-content`,`
 display: flex;
 position: relative;
 min-width: 100%;
 min-height: 100%;
 width: fit-content;
 box-sizing: border-box;
 `),N(`tabs-wrapper`,`
 display: inline-flex;
 flex-wrap: nowrap;
 position: relative;
 `),N(`tabs-tab-wrapper`,`
 display: flex;
 flex-wrap: nowrap;
 flex-shrink: 0;
 flex-grow: 0;
 `),N(`tabs-tab`,`
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
 `,[w(`disabled`,{cursor:`not-allowed`}),j(`close`,`
 margin-inline-start: 6px;
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `),j(`label`,`
 display: flex;
 align-items: center;
 z-index: 1;
 `)]),N(`tabs-bar`,`
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
 `,[P(`&.transition-disabled`,`
 transition: none;
 `),w(`disabled`,`
 background-color: var(--n-tab-text-color-disabled)
 `)]),N(`tabs-pane-wrapper`,`
 position: relative;
 overflow: hidden;
 transition: max-height .2s var(--n-bezier);
 `),N(`tab-pane`,`
 color: var(--n-pane-text-color);
 width: 100%;
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 opacity .2s var(--n-bezier);
 left: 0;
 right: 0;
 top: 0;
 `,[P(`&.next-transition-leave-active, &.prev-transition-leave-active, &.next-transition-enter-active, &.prev-transition-enter-active`,`
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 transform .2s var(--n-bezier),
 opacity .2s var(--n-bezier);
 `),P(`&.next-transition-leave-active, &.prev-transition-leave-active`,`
 position: absolute;
 `),P(`&.next-transition-enter-from, &.prev-transition-leave-to`,`
 transform: translateX(32px);
 opacity: 0;
 `),P(`&.next-transition-leave-to, &.prev-transition-enter-from`,`
 transform: translateX(-32px);
 opacity: 0;
 `),P(`&.next-transition-leave-from, &.next-transition-enter-to, &.prev-transition-leave-from, &.prev-transition-enter-to`,`
 transform: translateX(0);
 opacity: 1;
 `)]),N(`tabs-tab-pad`,`
 box-sizing: border-box;
 width: var(--n-tab-gap);
 flex-grow: 0;
 flex-shrink: 0;
 `),w(`line-type, bar-type`,[N(`tabs-tab`,`
 font-weight: var(--n-tab-font-weight);
 box-sizing: border-box;
 vertical-align: bottom;
 `,[P(`&:hover`,{color:`var(--n-tab-text-color-hover)`}),w(`active`,`
 color: var(--n-tab-text-color-active);
 font-weight: var(--n-tab-font-weight-active);
 `),w(`disabled`,{color:`var(--n-tab-text-color-disabled)`})])]),N(`tabs-nav`,[j(`prefix, suffix`,`
 border-color: var(--n-tab-border-color);
 `),N(`tabs-nav-scroll-content`,`
 border-color: var(--n-tab-border-color);
 `),w(`line-type`,[w(`top`,[j(`prefix, suffix`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),N(`tabs-nav-scroll-content`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),N(`tabs-bar`,`
 bottom: -1px;
 `)]),w(`left`,[j(`prefix, suffix`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),N(`tabs-nav-scroll-content`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),N(`tabs-bar`,`
 right: -1px;
 `)]),w(`right`,[j(`prefix, suffix`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),N(`tabs-nav-scroll-content`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),N(`tabs-bar`,`
 left: -1px;
 `)]),w(`bottom`,[j(`prefix, suffix`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),N(`tabs-nav-scroll-content`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),N(`tabs-bar`,`
 top: -1px;
 `)]),j(`prefix, suffix`,`
 transition: border-color .3s var(--n-bezier);
 `),N(`tabs-nav-scroll-content`,`
 transition: border-color .3s var(--n-bezier);
 `),N(`tabs-bar`,`
 border-radius: 0;
 `)]),w(`card-type`,[j(`prefix, suffix`,`
 transition: border-color .3s var(--n-bezier);
 `),N(`tabs-pad`,`
 flex-grow: 1;
 transition: border-color .3s var(--n-bezier);
 `),N(`tabs-tab-pad`,`
 transition: border-color .3s var(--n-bezier);
 `),N(`tabs-tab`,`
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
 `,[w(`addable`,`
 padding-left: 8px;
 padding-right: 8px;
 font-size: 16px;
 justify-content: center;
 `,[j(`height-placeholder`,`
 width: 0;
 font-size: var(--n-tab-font-size);
 `),L(`disabled`,[P(`&:hover`,`
 color: var(--n-tab-text-color-hover);
 `)])]),w(`closable`,`padding-inline-end: 8px;`),w(`active`,`
 background-color: #0000;
 font-weight: var(--n-tab-font-weight-active);
 color: var(--n-tab-text-color-active);
 `),w(`disabled`,`color: var(--n-tab-text-color-disabled);`)])]),w(`left, right`,`
 flex-direction: column; 
 `,[j(`prefix, suffix`,`
 padding: var(--n-tab-padding-vertical);
 `),N(`tabs-wrapper`,`
 flex-direction: column;
 `),N(`tabs-tab-wrapper`,`
 flex-direction: column;
 `,[N(`tabs-tab-pad`,`
 height: var(--n-tab-gap-vertical);
 width: 100%;
 `)])]),w(`top`,[w(`card-type`,[N(`tabs-scroll-padding`,`border-bottom: 1px solid var(--n-tab-border-color);`),j(`prefix, suffix`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),N(`tabs-tab`,`
 border-top-left-radius: var(--n-tab-border-radius);
 border-top-right-radius: var(--n-tab-border-radius);
 `,[w(`active`,`
 border-bottom: 1px solid #0000;
 `)]),N(`tabs-tab-pad`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),N(`tabs-pad`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `)])]),w(`left`,[w(`card-type`,[N(`tabs-scroll-padding`,`border-right: 1px solid var(--n-tab-border-color);`),j(`prefix, suffix`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),N(`tabs-tab`,`
 border-top-left-radius: var(--n-tab-border-radius);
 border-bottom-left-radius: var(--n-tab-border-radius);
 `,[w(`active`,`
 border-right: 1px solid #0000;
 `)]),N(`tabs-tab-pad`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),N(`tabs-pad`,`
 border-right: 1px solid var(--n-tab-border-color);
 `)])]),w(`right`,[w(`card-type`,[N(`tabs-scroll-padding`,`border-left: 1px solid var(--n-tab-border-color);`),j(`prefix, suffix`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),N(`tabs-tab`,`
 border-top-right-radius: var(--n-tab-border-radius);
 border-bottom-right-radius: var(--n-tab-border-radius);
 `,[w(`active`,`
 border-left: 1px solid #0000;
 `)]),N(`tabs-tab-pad`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),N(`tabs-pad`,`
 border-left: 1px solid var(--n-tab-border-color);
 `)])]),w(`bottom`,[w(`card-type`,[N(`tabs-scroll-padding`,`border-top: 1px solid var(--n-tab-border-color);`),j(`prefix, suffix`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),N(`tabs-tab`,`
 border-bottom-left-radius: var(--n-tab-border-radius);
 border-bottom-right-radius: var(--n-tab-border-radius);
 `,[w(`active`,`
 border-top: 1px solid #0000;
 `)]),N(`tabs-tab-pad`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),N(`tabs-pad`,`
 border-top: 1px solid var(--n-tab-border-color);
 `)])])]),N(`tabs-scroll-button`,[w(`start`,`
 padding-left: 10px;
 padding-right: 6px;
 `),w(`end`,`
 padding-right: 10px;
 padding-left: 6px;
 `),w(`up`,`
 padding-bottom: 10px;
 `),w(`down`,`
 padding-top: 10px;
 `)])]),Ne=S({name:`TabsButton`,props:{type:{type:String,default:`next`},mergedClsPrefix:{type:String,required:!0},vertical:Boolean,disabled:Boolean,rtl:Boolean,theme:Object,themeOverrides:Object,onClick:Function},setup(e){return{handleClick:()=>{e.disabled||e.onClick?.(e.type)}}},render(){let{mergedClsPrefix:e,disabled:t,type:n,vertical:r,rtl:i,theme:a,themeOverrides:c,handleClick:l}=this,u=n===`next`,d=r?u:i?!u:u;return o(),v(V,{text:!0,disabled:t,size:`small`,theme:a,themeOverrides:c,onClick:l,class:F([`${e}-tabs-scroll-button`,!r&&n===`prev`&&`${e}-tabs-scroll-button--start`,!r&&n===`next`&&`${e}-tabs-scroll-button--end`,r&&n===`prev`&&`${e}-tabs-scroll-button--up`,r&&n===`next`&&`${e}-tabs-scroll-button--down`])},{icon:()=>(o(),v(k,{clsPrefix:e,style:s(r?{transform:`rotate(90deg)`}:void 0)},{default:()=>d?(o(),v(ue,{key:1})):(o(),v(Ee,{key:2}))},1032,[`clsPrefix`,`style`]))},1032,[`disabled`,`theme`,`themeOverrides`,`onClick`,`class`])}}),Pe=we,Fe={...D.props,value:[String,Number],defaultValue:[String,Number],trigger:{type:String,default:`click`},type:{type:String,default:`bar`},closable:Boolean,justifyContent:String,size:String,placement:{type:String,default:`top`},tabStyle:[String,Object],tabClass:String,addTabStyle:[String,Object],addTabClass:String,barWidth:Number,paneClass:String,paneStyle:[String,Object],paneWrapperClass:String,paneWrapperStyle:[String,Object],addable:[Boolean,Object],tabsPadding:{type:Number,default:0},animated:Boolean,onBeforeLeave:Function,onAdd:Function,"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array],onClose:[Function,Array],labelSize:String,activeName:[String,Number],onActiveNameChange:[Function,Array],showScrollButton:Boolean,centerActiveTab:Boolean},Ie=S({name:`Tabs`,props:Fe,slots:Object,setup(e,{slots:t}){let{mergedClsPrefixRef:i,inlineThemeDisabled:a,mergedComponentPropsRef:o,mergedRtlRef:s}=re(e),c=ee(`Tabs`,s,i),f=p(()=>{let{placement:t}=e;return t===`start`?c?.value?`right`:`left`:t===`end`?c?.value?`left`:`right`:t}),m=D(`Tabs`,`-tabs`,Me,pe,e,i),h=b(null),v=b(null),y=b(null),x=b(null),S=b(null),w=b(null),E=b(null),k=b(!0),A=b(!0),j=se(e,[`labelSize`,`size`]),M=p(()=>j.value?j.value:o?.value?.Tabs?.size||`medium`),N=se(e,[`activeName`,`value`]),P=b(N.value??e.defaultValue??(t.default?r(t.default())[0]?.props?.name:null)),F=le(N,P),I={id:0},L=p(()=>{if(e.justifyContent&&e.type!==`card`)return{display:`flex`,justifyContent:e.justifyContent}});d(F,()=>{I.id=0,z(),_(()=>{V()})});function R(){let{value:e}=F;return e===null?null:h.value?.querySelector(`[data-name="${e}"]`)}function te(t){if(e.type===`card`)return;let{value:n}=y;if(!n)return;let r=n.style.opacity===`0`;if(t){let a=`${i.value}-tabs-bar--disabled`,{barWidth:o}=e,s=f.value;if(t.dataset.disabled===`true`?n.classList.add(a):n.classList.remove(a),[`top`,`bottom`].includes(s)){if(ie([`top`,`maxHeight`,`height`]),typeof o==`number`&&t.offsetWidth>=o){let e=Math.floor((t.offsetWidth-o)/2)+t.offsetLeft;n.style.left=`${e}px`,n.style.maxWidth=`${o}px`}else n.style.left=`${t.offsetLeft}px`,n.style.maxWidth=`${t.offsetWidth}px`;n.style.width=`8192px`,r&&(n.style.transition=`none`),n.offsetWidth,r&&(n.style.transition=``,n.style.opacity=`1`)}else{if(ie([`left`,`maxWidth`,`width`]),typeof o==`number`&&t.offsetHeight>=o){let e=Math.floor((t.offsetHeight-o)/2)+t.offsetTop;n.style.top=`${e}px`,n.style.maxHeight=`${o}px`}else n.style.top=`${t.offsetTop}px`,n.style.maxHeight=`${t.offsetHeight}px`;n.style.height=`8192px`,r&&(n.style.transition=`none`),n.offsetHeight,r&&(n.style.transition=``,n.style.opacity=`1`)}}}function ne(){if(e.type===`card`)return;let{value:t}=y;t&&(t.style.opacity=`0`)}function ie(e){let{value:t}=y;if(t)for(let n of e)t.style[n]=``}function z(){if(e.type===`card`)return;let t=R();t?te(t):ne()}function B(e,t,n,r){let i=e.getBoundingClientRect(),a=t.getBoundingClientRect(),o=n?`left`:`top`,s=n?`right`:`bottom`,c=0;r?c=(a[o]+a[s])/2-(i[o]+i[s])/2:a[o]<i[o]?c=a[o]-i[o]:a[s]>i[s]&&(c=a[s]-i[s]),c!==0&&e.scrollBy({[o]:c,behavior:`smooth`})}function V(){let t=[`top`,`bottom`].includes(f.value),n=R();if(n){if(t){let r=w.value?.$el;if(!r)return;B(r,n,t,e.centerActiveTab)}else{let{value:r}=E;if(!r)return;B(r,n,t,e.centerActiveTab)}}}let U=b(null),W=0,G=null;function oe(e){let t=U.value;if(t){W=e.getBoundingClientRect().height;let n=`${W}px`,r=()=>{t.style.height=n,t.style.maxHeight=n};G?(r(),G(),G=null):G=r}}function ce(e){let t=U.value;if(t){let n=e.getBoundingClientRect().height,r=()=>{document.body.offsetHeight,t.style.maxHeight=`${n}px`,t.style.height=`${Math.max(W,n)}px`};G?(G(),G=null,r()):G=r}}function ue(){let t=U.value;if(t){t.style.maxHeight=``,t.style.height=``;let{paneWrapperStyle:n}=e;if(typeof n==`string`)t.style.cssText=n;else if(n){let{maxHeight:e,height:r}=n;e!==void 0&&(t.style.maxHeight=e),r!==void 0&&(t.style.height=r)}}}let de={value:[]},fe=b(`next`);function me(e){let t=F.value,n=`next`;for(let r of de.value){if(r===t)break;if(r===e){n=`prev`;break}}fe.value=n,he(e)}function he(t){let{onActiveNameChange:n,onUpdateValue:r,"onUpdate:value":i}=e;n&&T(n,t),r&&T(r,t),i&&T(i,t),P.value=t}function ge(t){let{onClose:n}=e;n&&T(n,t)}function _e(e){if([`top`,`bottom`].includes(f.value)){let{value:t}=w;if(!t)return;let n=t.$el;if(!n)return;let r=n.offsetWidth,i=!!c?.value,a=e===`next`?r:-r;n.scrollBy({left:i?-a:a,behavior:`smooth`})}else{let{value:t}=E;if(!t)return;let n=t.offsetHeight,r=e===`next`?t.scrollTop+n:t.scrollTop-n;t.scrollTo({top:r,left:0,behavior:`smooth`})}}let K=!0;function q(){let{value:e}=y;if(!e)return;K&&=!1;let t=`transition-disabled`;e.classList.add(t),z(),e.classList.remove(t)}let J=b(null);function Y({transitionDisabled:e}){let t=h.value;if(!t)return;e&&t.classList.add(`transition-disabled`);let n=R();n&&J.value&&(J.value.style.width=`${n.offsetWidth}px`,J.value.style.height=`${n.offsetHeight}px`,J.value.style.transform=`translate(${n.offsetLeft}px, ${n.offsetTop}px)`,e&&J.value.offsetWidth),e&&t.classList.remove(`transition-disabled`)}d([F],()=>{e.type===`segment`&&_(()=>{Y({transitionDisabled:!1})})}),l(()=>{e.type===`segment`&&Y({transitionDisabled:!0})});let ve=0;function ye(t){if(t.contentRect.width===0&&t.contentRect.height===0||ve===t.contentRect.width)return;ve=t.contentRect.width;let{type:n}=e;(n===`line`||n===`bar`)&&(K||e.justifyContent?.startsWith(`space`))&&q(),n!==`segment`&&Q(Te())}let X=Pe(ye,64);function be(){let{type:t}=e;t===`line`||t===`bar`?q():t===`segment`&&Y({transitionDisabled:!0})}d([()=>e.justifyContent,()=>e.size],()=>{_(()=>{(e.type===`line`||e.type===`bar`)&&q()})}),d([f,()=>c?.value],()=>{_(()=>{be(),Q(Te(),{instantly:!0})})}),d(()=>e.type,()=>{_(()=>{let e=v.value;e&&(e.classList.add(`transition-disabled`),be(),e.offsetWidth,e.classList.remove(`transition-disabled`))})});let Z=b(!1);function xe(e){let{target:t,contentRect:{width:n,height:r}}=e,i=t.parentElement.parentElement.offsetWidth,a=t.parentElement.parentElement.offsetHeight,o=f.value;if(!Z.value)o===`top`||o===`bottom`?i<n&&(Z.value=!0):a<r&&(Z.value=!0);else{let{value:e}=S;if(!e)return;o===`top`||o===`bottom`?i-n>e.$el.offsetWidth&&(Z.value=!1):a-r>e.$el.offsetHeight&&(Z.value=!1)}Q(w.value?.$el||null)}let Se=Pe(xe,64);function Ce(){let{onAdd:t}=e;t&&t()}let we=b(!1);function Te(){let e=f.value;return(e===`top`||e===`bottom`?w.value?.$el:E.value)||null}function Q(e,t={instantly:!1}){if(!e)return;let n=t.instantly?x.value:null;n&&n.classList.add(`transition-disabled`);let r=f.value;if(r===`top`||r===`bottom`){let{scrollLeft:t,scrollWidth:n,offsetWidth:r}=e,i=Math.abs(t);k.value=i<=1,A.value=i+r>=n-1,we.value=r<n-1}else{let{scrollTop:t,scrollHeight:n,offsetHeight:r}=e;k.value=t<=1,A.value=t+r>=n-1,we.value=r<n-1}n&&(n.offsetWidth,n.classList.remove(`transition-disabled`))}let Ee=Pe(e=>{Q(e.target)},64);C(De,{triggerRef:g(e,`trigger`),tabStyleRef:g(e,`tabStyle`),tabClassRef:g(e,`tabClass`),addTabStyleRef:g(e,`addTabStyle`),addTabClassRef:g(e,`addTabClass`),paneClassRef:g(e,`paneClass`),paneStyleRef:g(e,`paneStyle`),mergedClsPrefixRef:i,typeRef:g(e,`type`),closableRef:g(e,`closable`),valueRef:F,tabChangeIdRef:I,onBeforeLeaveRef:g(e,`onBeforeLeave`),activateTab:me,handleClose:ge,handleAdd:Ce}),ae(()=>{z(),V()}),u(()=>{let{value:e}=x;if(!e)return;let{value:t}=i,n=`${t}-tabs-nav-scroll-wrapper--shadow-start`,r=`${t}-tabs-nav-scroll-wrapper--shadow-end`;k.value?e.classList.remove(n):e.classList.add(n),A.value?e.classList.remove(r):e.classList.add(r)});let Oe={syncBarPosition:()=>{z()},scrollToCurrentTab:()=>{V()}},ke=()=>{Y({transitionDisabled:!0})},Ae=p(()=>{let{value:t}=M,{type:r}=e,i=`${t}${{card:`Card`,bar:`Bar`,line:`Line`,segment:`Segment`}[r]}`,{self:{barColor:a,closeIconColor:o,closeIconColorHover:s,closeIconColorPressed:c,tabColor:l,tabBorderColor:u,paneTextColor:d,tabFontWeight:f,tabBorderRadius:p,tabFontWeightActive:h,colorSegment:g,fontWeightStrong:_,tabColorSegment:v,closeSize:y,closeIconSize:b,closeColorHover:x,closeColorPressed:S,closeBorderRadius:C,[H(`panePadding`,t)]:w,[H(`tabPadding`,i)]:T,[H(`tabPaddingVertical`,i)]:E,[H(`tabGap`,i)]:ee,[H(`tabGap`,`${i}Vertical`)]:D,[H(`tabTextColor`,r)]:O,[H(`tabTextColorActive`,r)]:k,[H(`tabTextColorHover`,r)]:A,[H(`tabTextColorDisabled`,r)]:j,[H(`tabFontSize`,t)]:N},common:{cubicBezierEaseInOut:P}}=m.value;return{"--n-bezier":P,"--n-color-segment":g,"--n-bar-color":a,"--n-tab-font-size":N,"--n-tab-text-color":O,"--n-tab-text-color-active":k,"--n-tab-text-color-disabled":j,"--n-tab-text-color-hover":A,"--n-pane-text-color":d,"--n-tab-border-color":u,"--n-tab-border-radius":p,"--n-close-size":y,"--n-close-icon-size":b,"--n-close-color-hover":x,"--n-close-color-pressed":S,"--n-close-border-radius":C,"--n-close-icon-color":o,"--n-close-icon-color-hover":s,"--n-close-icon-color-pressed":c,"--n-tab-color":l,"--n-tab-font-weight":f,"--n-tab-font-weight-active":h,"--n-tab-padding":T,"--n-tab-padding-vertical":E,"--n-tab-gap":ee,"--n-tab-gap-vertical":D,"--n-pane-padding-left":n(w,`left`),"--n-pane-padding-right":n(w,`right`),"--n-pane-padding-top":n(w,`top`),"--n-pane-padding-bottom":n(w,`bottom`),"--n-font-weight-strong":_,"--n-tab-color-segment":v}}),je=a?O(`tabs`,p(()=>`${M.value[0]}${e.type[0]}`),Ae,e):void 0;return{mergedClsPrefix:i,mergedValue:F,renderedNames:new Set,segmentCapsuleElRef:J,tabsPaneWrapperRef:U,tabsElRef:h,selfElRef:v,barElRef:y,addTabInstRef:S,xScrollInstRef:w,scrollWrapperElRef:x,addTabFixed:Z,tabWrapperStyle:L,handleNavResize:X,mergedSize:M,handleScroll:Ee,handleTabsResize:Se,cssVars:a?void 0:Ae,themeClass:je?.themeClass,animationDirection:fe,renderNameListRef:de,yScrollElRef:E,handleSegmentResize:ke,onAnimationBeforeLeave:oe,onAnimationEnter:ce,onAnimationAfterEnter:ue,onRender:je?.onRender,startReachedRef:k,endReachedRef:A,isOverflow:we,handleButtonClick:_e,mergedTheme:m,rtlEnabled:c,mergedPlacement:f,...Oe}},render(){let{mergedClsPrefix:e,type:n,mergedPlacement:i,addTabFixed:a,addable:l,mergedSize:u,renderNameListRef:d,onRender:p,paneWrapperClass:g,paneWrapperStyle:_,startReachedRef:y,endReachedRef:b,isOverflow:x,showScrollButton:S,handleButtonClick:C,mergedTheme:w,rtlEnabled:T,$slots:{default:E,prefix:ee,suffix:D}}=this;p?.();let O=E?r(E()).filter(e=>e.type.__TAB_PANE__===!0):[],k=E?r(E()).filter(e=>e.type.__TAB__===!0):[],j=!k.length,M=n===`card`,N=n===`segment`,P=!M&&!N&&this.justifyContent;d.value=[];let L=()=>{let n=(o(),h(`div`,{style:s(this.tabWrapperStyle),class:F(`${e}-tabs-wrapper`)},[P?I(()=>null):(o(),h(`div`,{key:1,class:F(`${e}-tabs-scroll-padding`),style:s(i===`top`||i===`bottom`?{width:`${this.tabsPadding}px`}:{height:`${this.tabsPadding}px`})},null,6)),j?(o(),h(f,{key:2},[I(()=>O.map((e,t)=>(d.value.push(e.props.name),Be((o(),v($,c(e.props,{internalCreatedByPane:!0,internalLeftPadded:t!==0&&(!P||P===`center`||P===`start`||P===`end`)}),B(e.children?{default:e.children.tab}:void 0),1040,[`internalLeftPadded`]))))))],64)):(o(),h(f,{key:3},[I(()=>k.map((e,t)=>(d.value.push(e.props.name),Be(t!==0&&!P?ze(e):e))))],64)),!a&&l&&M?(o(),h(f,{key:4},[I(()=>Re(l,(j?O.length:k.length)!==0))],64)):I(()=>null),P?I(()=>null):(o(),h(`div`,{key:7,class:F(`${e}-tabs-scroll-padding`),style:s({width:`${this.tabsPadding}px`})},null,6)),M?I(()=>null):(o(),h(`div`,{key:9,ref:`barElRef`,class:F(`${e}-tabs-bar`)},null,2))],6));return o(),h(`div`,{ref:`tabsElRef`,class:F(`${e}-tabs-nav-scroll-content`)},[M&&l?(o(),v(t,{key:0,onResize:this.handleTabsResize},{default:()=>n},1032,[`onResize`])):(o(),h(f,{key:1},[I(()=>n)],64)),M?(o(),h(`div`,{key:2,class:F(`${e}-tabs-pad`)},null,2)):I(()=>null)],2)},R=N?`top`:i;return o(),h(`div`,{ref:`selfElRef`,class:F([`${e}-tabs`,this.themeClass,`${e}-tabs--${n}-type`,`${e}-tabs--${u}-size`,P&&`${e}-tabs--flex`,`${e}-tabs--${R}`,T&&`${e}-tabs--rtl`]),style:s(this.cssVars)},[m(`div`,{class:F([`${e}-tabs-nav--${n}-type`,`${e}-tabs-nav--${R}`,`${e}-tabs-nav`])},[I(()=>A(ee,t=>t&&(o(),h(`div`,{class:F(`${e}-tabs-nav__prefix`)},[I(()=>t)],2)))),N?(o(),v(t,{key:0,onResize:this.handleSegmentResize},{default:()=>(o(),h(`div`,{class:F(`${e}-tabs-rail`),ref:`tabsElRef`},[m(`div`,{class:F(`${e}-tabs-capsule`),ref:`segmentCapsuleElRef`},[m(`div`,{class:F(`${e}-tabs-wrapper`)},[m(`div`,{class:F(`${e}-tabs-tab`)},null,2)],2)],2),j?(o(),h(f,{key:0},[I(()=>O.map((e,t)=>(d.value.push(e.props.name),o(),v($,c(e.props,{internalCreatedByPane:!0,internalLeftPadded:t!==0}),B(e.children?{default:e.children.tab}:void 0),1040,[`internalLeftPadded`]))))],64)):(o(),h(f,{key:1},[I(()=>k.map((e,t)=>(d.value.push(e.props.name),t===0?e:ze(e))))],64))],2))},1032,[`onResize`])):(o(),h(f,{key:1},[I(()=>S&&x&&(o(),v(Ne,{mergedClsPrefix:e,type:`prev`,vertical:R===`left`||R===`right`,disabled:y,rtl:!!T,theme:w.peers.Button,themeOverrides:w.peerOverrides.Button,onClick:C},null,8,[`mergedClsPrefix`,`vertical`,`disabled`,`rtl`,`theme`,`themeOverrides`,`onClick`]))),(o(),v(t,{onResize:this.handleNavResize},{default:()=>(o(),h(`div`,{class:F(`${e}-tabs-nav-scroll-wrapper`),ref:`scrollWrapperElRef`},[[`top`,`bottom`].includes(R)?(o(),v(Q,{key:0,ref:`xScrollInstRef`,onScroll:this.handleScroll},{default:L},1032,[`onScroll`])):(o(),h(`div`,{key:1,class:F(`${e}-tabs-nav-y-scroll`),onScroll:this.handleScroll,ref:`yScrollElRef`},[I(()=>L())],42,[`onScroll`]))],2))},1032,[`onResize`])),I(()=>S&&x&&(o(),v(Ne,{mergedClsPrefix:e,type:`next`,vertical:R===`left`||R===`right`,disabled:b,rtl:!!T,theme:w.peers.Button,themeOverrides:w.peerOverrides.Button,onClick:C},null,8,[`mergedClsPrefix`,`vertical`,`disabled`,`rtl`,`theme`,`themeOverrides`,`onClick`])))],64)),a&&l&&M?(o(),h(f,{key:2},[I(()=>Re(l,!0))],64)):I(()=>null),I(()=>A(D,t=>t&&(o(),h(`div`,{class:F(`${e}-tabs-nav__suffix`)},[I(()=>t)],2))))],2),I(()=>j&&(this.animated&&(R===`top`||R===`bottom`)?(o(),h(`div`,{key:1,ref:`tabsPaneWrapperRef`,style:s(_),class:F([`${e}-tabs-pane-wrapper`,g])},[I(()=>Le(O,this.mergedValue,this.renderedNames,this.onAnimationBeforeLeave,this.onAnimationEnter,this.onAnimationAfterEnter,this.animationDirection))],6)):Le(O,this.mergedValue,this.renderedNames)))],6)}});function Le(e,t,n,r,a,s,c){let l=[];return e.forEach(e=>{let{name:r,displayDirective:a,"display-directive":o}=e.props,s=e=>a===e||o===e,c=t===r;if(e.key!==void 0&&(e.key=r),c||s(`show`)||s(`show:lazy`)&&n.has(r)){n.has(r)||n.add(r);let t=!s(`if`);l.push(t?i(e,[[ne,c]]):e)}}),c?(o(),v(z,{name:`${c}-transition`,onBeforeLeave:r,onEnter:a,onAfterEnter:s},{default:()=>l},1032,[`name`,`onBeforeLeave`,`onEnter`,`onAfterEnter`])):l}function Re(e,t){return o(),v($,{ref:`addTabInstRef`,key:`__addable`,name:`__addable`,internalCreatedByPane:!0,internalAddable:!0,internalLeftPadded:t,disabled:typeof e==`object`&&e.disabled},null,8,[`internalLeftPadded`,`disabled`])}function ze(e){let t=y(e);return t.props?t.props.internalLeftPadded=!0:t.props={internalLeftPadded:!0},t}function Be(e){return Array.isArray(e.dynamicProps)?e.dynamicProps.includes(`internalLeftPadded`)||e.dynamicProps.push(`internalLeftPadded`):e.dynamicProps=[`internalLeftPadded`],e}export{$ as n,ke as r,Ie as t};