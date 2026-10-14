import{D as e,H as t,U as n,et as r}from"./php-modules-GekPVnxA.js";import{$ as i,B as a,E as o,H as s,L as c,Mt as l,Nt as u,O as d,Pt as f,Q as p,R as ee,X as te,Y as ne,a as re,at as ie,d as m,f as h,h as g,ht as ae,k as oe,m as _,p as v,pt as se,ut as y,v as b,vt as x,x as S,y as C,z as ce}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$ as le,$t as w,A as ue,B as T,F as de,G as E,H as D,I as fe,J as pe,K as me,Q as he,Qt as O,V as k,W as ge,X as _e,Xt as A,Y as j,Yt as M,Z as N,at as P,ct as F,et as ve,gt as ye,h as be,it as xe,j as Se,k as I,lt as Ce,nt as we,on as L,ot as R,pt as Te,q as z,rt as Ee,t as De,tn as Oe,tt as B,yt as V}from"./Button-BtFx7EYM.js";import{t as ke}from"./use-locale-lDc6YEpD.js";import{a as H,i as U,o as W,s as Ae}from"./Popover-BmmAdlen.js";import{d as G,l as K,p as q,x as je}from"./store-DyuIHp0J.js";import{d as J,f as Me,o as Ne}from"./light-B0o7KsWP.js";import{n as Pe}from"./format-length-BCoLE4J2.js";import{t as Fe}from"./Input-s0WGiF1c.js";import{t as Ie}from"./Tooltip-DPhNwnSI.js";var Le=A(`input-group`,`
 display: inline-flex;
 width: 100%;
 flex-wrap: nowrap;
 vertical-align: bottom;
`,[M(`>`,[A(`input`,[M(`&:not(:last-child)`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `),M(`&:not(:first-child)`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 margin-left: -1px!important;
 `)]),A(`button`,[M(`&:not(:last-child)`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `,[O(`state-border, border`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `)]),M(`&:not(:first-child)`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `,[O(`state-border, border`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `)])]),M(`*`,[M(`&:not(:last-child)`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `,[M(`>`,[A(`input`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `),A(`base-selection`,[A(`base-selection-label`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `),A(`base-selection-tags`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `),O(`box-shadow, border, state-border`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `)])])]),M(`&:not(:first-child)`,`
 margin-left: -1px!important;
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `,[M(`>`,[A(`input`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `),A(`base-selection`,[A(`base-selection-label`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `),A(`base-selection-tags`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `),O(`box-shadow, border, state-border`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `)])])])])])]),Re=S({name:`InputGroup`,props:{},setup(e){let{mergedClsPrefixRef:t}=Te(e);return Ce(`-input-group`,Le,t),{mergedClsPrefix:t}},render(){let{mergedClsPrefix:e}=this;return c(),g(`div`,{class:P(`${e}-input-group`)},[F(()=>this.$slots.default?.())],2)}});function ze(e,t){switch(e[0]){case`hex`:return t?`#000000FF`:`#000000`;case`rgb`:return t?`rgba(0, 0, 0, 1)`:`rgb(0, 0, 0)`;case`hsl`:return t?`hsla(0, 0%, 0%, 1)`:`hsl(0, 0%, 0%)`;case`hsv`:return t?`hsva(0, 0%, 0%, 1)`:`hsv(0, 0%, 0%)`}return`#000000`}function Be(e){return e===null?null:/^ *#/.test(e)?`hex`:e.includes(`rgb`)?`rgb`:e.includes(`hsl`)?`hsl`:e.includes(`hsv`)?`hsv`:null}function Ve(e,t=[255,255,255],n=`AA`){let[r,i,a,o]=D(z(e));if(o===1){let e=He([r,i,a]),o=He(t);return(Math.max(e,o)+.05)/(Math.min(e,o)+.05)>=(n===`AA`?4.5:7)}let s=He([Math.round(r*o+t[0]*(1-o)),Math.round(i*o+t[1]*(1-o)),Math.round(a*o+t[2]*(1-o))]),c=He(t);return(Math.max(s,c)+.05)/(Math.min(s,c)+.05)>=(n===`AA`?4.5:7)}function He(e){let[t,n,r]=e.map(e=>(e/=255,e<=.03928?e/12.92:((e+.055)/1.055)**2.4));return .2126*t+.7152*n+.0722*r}function Y(e){return e=Math.round(e),e>=360?359:e<0?0:e}function Ue(e){return e=Math.round(e*100)/100,e>1?1:e<0?0:e}var X={rgb:{hex(e){return E(D(e))},hsl(e){let[t,n,r,i]=D(e);return z([...we(t,n,r),i])},hsv(e){let[t,n,r,i]=D(e);return j([...Ee(t,n,r),i])}},hex:{rgb(e){return N(D(e))},hsl(e){let[t,n,r,i]=D(e);return z([...we(t,n,r),i])},hsv(e){let[t,n,r,i]=D(e);return j([...Ee(t,n,r),i])}},hsl:{hex(e){let[t,n,r,i]=T(e);return E([...le(t,n,r),i])},rgb(e){let[t,n,r,i]=T(e);return N([...le(t,n,r),i])},hsv(e){let[t,n,r,i]=T(e);return j([...he(t,n,r),i])}},hsv:{hex(e){let[t,n,r,i]=k(e);return E([...B(t,n,r),i])},rgb(e){let[t,n,r,i]=k(e);return N([...B(t,n,r),i])},hsl(e){let[t,n,r,i]=k(e);return z([...ve(t,n,r),i])}}};function We(e,t,n){return n||=Be(e),n?n===t?e:X[n][t](e):null}var Ge=[`onMousedown`],Z=`12px`,Ke=12,Q=`6px`,qe=S({name:`AlphaSlider`,props:{clsPrefix:{type:String,required:!0},rgba:{type:Array,default:null},alpha:{type:Number,default:0},onUpdateAlpha:{type:Function,required:!0},onComplete:Function},setup(e){let r=y(null);function i(t){r.value&&e.rgba&&(n(`mousemove`,document,a),n(`mouseup`,document,o),a(t))}function a(t){let{value:n}=r;if(!n)return;let{width:i,left:a}=n.getBoundingClientRect(),o=(t.clientX-a)/(i-Ke);e.onUpdateAlpha(Ue(o))}function o(){t(`mousemove`,document,a),t(`mouseup`,document,o),e.onComplete?.()}return{railRef:r,railBackgroundImage:m(()=>{let{rgba:t}=e;return t?`linear-gradient(to right, rgba(${t[0]}, ${t[1]}, ${t[2]}, 0) 0%, rgba(${t[0]}, ${t[1]}, ${t[2]}, 1) 100%)`:``}),handleMouseDown:i}},render(){let{clsPrefix:e}=this;return c(),g(`div`,{class:P(`${e}-color-picker-slider`),ref:`railRef`,style:u({height:Z,borderRadius:Q}),onMousedown:this.handleMouseDown},[h(`div`,{style:u({borderRadius:Q,position:`absolute`,left:0,right:0,top:0,bottom:0,overflow:`hidden`})},[h(`div`,{class:P(`${e}-color-picker-checkboard`)},null,2),h(`div`,{class:P(`${e}-color-picker-slider__image`),style:u({backgroundImage:this.railBackgroundImage})},null,6)],4),F(()=>this.rgba&&(c(),g(`div`,{style:u({position:`absolute`,left:Q,right:Q,top:0,bottom:0})},[h(`div`,{class:P(`${e}-color-picker-handle`),style:u({left:`calc(${this.alpha*100}% - ${Q})`,borderRadius:Q,width:Z,height:Z})},[h(`div`,{class:P(`${e}-color-picker-handle__fill`),style:u({backgroundColor:N(this.rgba),borderRadius:Q,width:Z,height:Z})},null,6)],6)],4)))],46,Ge)}}),Je=ye(`n-color-picker`);function Ye(e){return/^\d{1,3}\.?\d*$/.test(e.trim())?Math.max(0,Math.min(Number.parseInt(e),255)):!1}function Xe(e){return/^\d{1,3}\.?\d*$/.test(e.trim())?Math.max(0,Math.min(Number.parseInt(e),360)):!1}function Ze(e){return/^\d{1,3}\.?\d*$/.test(e.trim())?Math.max(0,Math.min(Number.parseInt(e),100)):!1}function Qe(e){let t=e.trim();return/^#[0-9a-fA-F]+$/.test(t)?[4,5,7,9].includes(t.length):!1}function $e(e){return/^\d{1,3}\.?\d*%$/.test(e.trim())?Math.max(0,Math.min(Number.parseInt(e)/100,100)):!1}var et={paddingSmall:`0 4px`},tt=S({name:`ColorInputUnit`,props:{label:{type:String,required:!0},value:{type:[Number,String],default:null},showAlpha:Boolean,onUpdateValue:{type:Function,required:!0}},setup(e){let t=y(``),{themeRef:n}=o(Je,null);te(()=>{t.value=r()});function r(){let{value:t}=e;if(t===null)return``;let{label:n}=e;return n===`HEX`?t:n===`A`?`${Math.floor(t*100)}%`:String(Math.floor(t))}function i(e){t.value=e}function a(n){let i,a;switch(e.label){case`HEX`:a=Qe(n),a&&e.onUpdateValue(n),t.value=r();break;case`H`:i=Xe(n),i===!1?t.value=r():e.onUpdateValue(i);break;case`S`:case`L`:case`V`:i=Ze(n),i===!1?t.value=r():e.onUpdateValue(i);break;case`A`:i=$e(n),i===!1?t.value=r():e.onUpdateValue(i);break;case`R`:case`G`:case`B`:i=Ye(n),i===!1?t.value=r():e.onUpdateValue(i)}}return{mergedTheme:n,inputValue:t,handleInputChange:a,handleInputUpdateValue:i}},render(){let{mergedTheme:e}=this;return c(),v(Fe,{size:`small`,placeholder:this.label,theme:e.peers.Input,themeOverrides:e.peerOverrides.Input,builtinThemeOverrides:et,value:this.inputValue,onUpdateValue:this.handleInputUpdateValue,onChange:this.handleInputChange,style:u(this.label===`A`?`flex-grow: 1.25;`:``)},null,8,[`placeholder`,`theme`,`themeOverrides`,`builtinThemeOverrides`,`value`,`onUpdateValue`,`onChange`,`style`])}}),nt=[`onClick`],rt=S({name:`ColorInput`,props:{clsPrefix:{type:String,required:!0},mode:{type:String,required:!0},modes:{type:Array,required:!0},showAlpha:{type:Boolean,required:!0},value:{type:String,default:null},valueArr:{type:Array,default:null},onUpdateValue:{type:Function,required:!0},onUpdateMode:{type:Function,required:!0}},setup(e){return{handleUnitUpdateValue(t,n){let{showAlpha:r}=e;if(e.mode===`hex`){e.onUpdateValue((r?E:ge)(n));return}let i;switch(i=e.valueArr===null?[0,0,0,0]:Array.from(e.valueArr),e.mode){case`hsv`:i[t]=n,e.onUpdateValue((r?j:pe)(i));break;case`rgb`:i[t]=n,e.onUpdateValue((r?N:_e)(i));break;case`hsl`:i[t]=n,e.onUpdateValue((r?z:me)(i))}}}},render(){let{clsPrefix:e,modes:t}=this;return c(),g(`div`,{class:P(`${e}-color-picker-input`)},[h(`div`,{class:P(`${e}-color-picker-input__mode`),onClick:this.onUpdateMode,style:u({cursor:t.length===1?``:`pointer`})},[F(()=>this.mode.toUpperCase()+(this.showAlpha?`A`:``))],14,nt),C(Re,null,{default:()=>{let{mode:e,valueArr:t,showAlpha:n}=this;if(e===`hex`){let e=null;try{e=t===null?null:(n?E:ge)(t)}catch{}return c(),v(tt,{key:1,label:`HEX`,showAlpha:n,value:e,onUpdateValue:e=>{this.handleUnitUpdateValue(0,e)}},null,8,[`showAlpha`,`value`,`onUpdateValue`])}return(e+(n?`a`:``)).split(``).map((e,n)=>(c(),v(tt,{label:e.toUpperCase(),value:t===null?null:t[n],onUpdateValue:e=>{this.handleUnitUpdateValue(n,e)}},null,8,[`label`,`value`,`onUpdateValue`])))}},1024)],2)}}),it=[`onClick`,`onKeydown`];function at(e,t){if(t===`hsv`){let[t,n,r,i]=k(e);return N([...B(t,n,r),i])}return e}function ot(e){let t=document.createElement(`canvas`).getContext(`2d`);return t?(t.fillStyle=e,t.fillStyle):`#000000`}var st=S({name:`ColorPickerSwatches`,props:{clsPrefix:{type:String,required:!0},mode:{type:String,required:!0},swatches:{type:Array,required:!0},onUpdateColor:{type:Function,required:!0}},setup(e){let t=m(()=>e.swatches.map(e=>{let t=Be(e);return{value:e,mode:t,legalValue:at(e,t)}}));function n(t){let{mode:n}=e,{value:r,mode:i}=t;return i||(i=`hex`,/^[a-zA-Z]+$/.test(r)?r=ot(r):(V(`color-picker`,`color ${r} in swatches is invalid.`),r=`#000000`)),i===n?r:We(r,n,i)}function r(t){e.onUpdateColor(n(t))}function i(e,t){e.key===`Enter`&&r(t)}return{parsedSwatchesRef:t,handleSwatchSelect:r,handleSwatchKeyDown:i}},render(){let{clsPrefix:e}=this;return c(),g(`div`,{class:P(`${e}-color-picker-swatches`)},[F(()=>this.parsedSwatchesRef.map(t=>(c(),g(`div`,{class:P(`${e}-color-picker-swatch`),tabindex:0,onClick:()=>{this.handleSwatchSelect(t)},onKeydown:e=>{this.handleSwatchKeyDown(e,t)}},[h(`div`,{class:P(`${e}-color-picker-swatch__fill`),style:u({background:t.legalValue})},null,6)],42,it))))],2)}}),ct=[`onClick`],lt=S({name:`ColorPickerTrigger`,slots:Object,props:{clsPrefix:{type:String,required:!0},value:{type:String,default:null},hsla:{type:Array,default:null},disabled:Boolean,onClick:Function},setup(e){let{colorPickerSlots:t,renderLabelRef:n}=o(Je,null);return()=>{let{hsla:r,value:i,clsPrefix:a,onClick:o,disabled:s}=e,l=t.label||n.value;return c(),g(`div`,{class:P([`${a}-color-picker`,s&&`${a}-color-picker--disabled`]),onClick:s?void 0:o},[h(`div`,{class:P(`${a}-color-picker__fill`)},[h(`div`,{class:P(`${a}-color-picker-checkboard`)},null,2),h(`div`,{style:u({position:`absolute`,left:0,right:0,top:0,bottom:0,backgroundColor:r?z(r):``})},null,4),i&&r?(c(),g(`div`,{key:0,class:P(`${a}-color-picker__value`),style:u({color:Ve(r)?`white`:`black`})},[l?(c(),g(re,{key:0},[F(()=>l(i))],64)):(c(),g(re,{key:1},[F(()=>i)],64))],6)):F(()=>null)],2)],10,ct)}}}),ut=[`value`,`onChange`],dt=S({name:`ColorPreview`,props:{clsPrefix:{type:String,required:!0},mode:{type:String,required:!0},color:{type:String,default:null,validator:e=>{let t=Be(e);return!!(!e||t&&t!==`hsv`)}},onUpdateColor:{type:Function,required:!0}},setup(e){function t(t){let n=t.target.value;e.onUpdateColor?.(We(n.toUpperCase(),e.mode,`hex`)),t.stopPropagation()}return{handleChange:t}},render(){let{clsPrefix:e}=this;return c(),g(`div`,{class:P(`${e}-color-picker-preview__preview`)},[h(`span`,{class:P(`${e}-color-picker-preview__fill`),style:u({background:this.color||`#000000`})},null,6),h(`input`,{class:P(`${e}-color-picker-preview__input`),type:`color`,value:this.color,onChange:this.handleChange},null,42,ut)],2)}}),ft=[`onMousedown`],pt=`12px`,mt=12,$=`6px`,ht=6,gt=`linear-gradient(90deg,red,#ff0 16.66%,#0f0 33.33%,#0ff 50%,#00f 66.66%,#f0f 83.33%,red)`,_t=S({name:`HueSlider`,props:{clsPrefix:{type:String,required:!0},hue:{type:Number,required:!0},onUpdateHue:{type:Function,required:!0},onComplete:Function},setup(e){let r=y(null);function i(e){r.value&&(n(`mousemove`,document,a),n(`mouseup`,document,o),a(e))}function a(t){let{value:n}=r;if(!n)return;let{width:i,left:a}=n.getBoundingClientRect(),o=Y((t.clientX-a-ht)/(i-mt)*360);e.onUpdateHue(o)}function o(){t(`mousemove`,document,a),t(`mouseup`,document,o),e.onComplete?.()}return{railRef:r,handleMouseDown:i}},render(){let{clsPrefix:e}=this;return c(),g(`div`,{class:P(`${e}-color-picker-slider`),style:u({height:pt,borderRadius:$})},[h(`div`,{ref:`railRef`,style:u({boxShadow:`inset 0 0 2px 0 rgba(0, 0, 0, .24)`,boxSizing:`border-box`,backgroundImage:gt,height:pt,borderRadius:$,position:`relative`}),onMousedown:this.handleMouseDown},[h(`div`,{style:u({position:`absolute`,left:$,right:$,top:0,bottom:0})},[h(`div`,{class:P(`${e}-color-picker-handle`),style:u({left:`calc((${this.hue}%) / 359 * 100 - ${$})`,borderRadius:$,width:pt,height:pt})},[h(`div`,{class:P(`${e}-color-picker-handle__fill`),style:u({backgroundColor:`hsl(${this.hue}, 100%, 50%)`,borderRadius:$,width:pt,height:pt})},null,6)],6)],4)],44,ft)],6)}}),vt=[`onMousedown`],yt=`12px`,bt=`6px`,xt=S({name:`Pallete`,props:{clsPrefix:{type:String,required:!0},rgba:{type:Array,default:null},displayedHue:{type:Number,required:!0},displayedSv:{type:Array,required:!0},onUpdateSV:{type:Function,required:!0},onComplete:Function},setup(e){let r=y(null);function i(e){r.value&&(n(`mousemove`,document,a),n(`mouseup`,document,o),a(e))}function a(t){let{value:n}=r;if(!n)return;let{width:i,height:a,left:o,bottom:s}=n.getBoundingClientRect(),c=(s-t.clientY)/a,l=(t.clientX-o)/i,u=100*(l>1?1:l<0?0:l),d=100*(c>1?1:c<0?0:c);e.onUpdateSV(u,d)}function o(){t(`mousemove`,document,a),t(`mouseup`,document,o),e.onComplete?.()}return{palleteRef:r,handleColor:m(()=>{let{rgba:t}=e;return t?`rgb(${t[0]}, ${t[1]}, ${t[2]})`:``}),handleMouseDown:i}},render(){let{clsPrefix:e}=this;return c(),g(`div`,{class:P(`${e}-color-picker-pallete`),onMousedown:this.handleMouseDown,ref:`palleteRef`},[h(`div`,{class:P(`${e}-color-picker-pallete__layer`),style:u({backgroundImage:`linear-gradient(90deg, white, hsl(${this.displayedHue}, 100%, 50%))`})},null,6),h(`div`,{class:P(`${e}-color-picker-pallete__layer ${e}-color-picker-pallete__layer--shadowed`),style:{backgroundImage:`linear-gradient(180deg, rgba(0, 0, 0, 0%), rgba(0, 0, 0, 100%))`}},null,2),F(()=>this.rgba&&(c(),g(`div`,{class:P(`${e}-color-picker-handle`),style:u({width:yt,height:yt,borderRadius:bt,left:`calc(${this.displayedSv[0]}% - ${bt})`,bottom:`calc(${this.displayedSv[1]}% - ${bt})`})},[h(`div`,{class:P(`${e}-color-picker-handle__fill`),style:u({backgroundColor:this.handleColor,borderRadius:bt,width:yt,height:yt})},null,6)],6)))],42,vt)}}),St=M([A(`color-picker-panel`,`
 margin: 4px 0;
 width: 240px;
 font-size: var(--n-panel-font-size);
 color: var(--n-text-color);
 background-color: var(--n-color);
 transition:
 box-shadow .3s var(--n-bezier),
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 border-radius: var(--n-border-radius);
 box-shadow: var(--n-box-shadow);
 `,[e(),A(`input`,`
 text-align: center;
 `)]),A(`color-picker-checkboard`,`
 background: white; 
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `,[M(`&::after`,`
 background-image: linear-gradient(45deg, #DDD 25%, #0000 25%), linear-gradient(-45deg, #DDD 25%, #0000 25%), linear-gradient(45deg, #0000 75%, #DDD 75%), linear-gradient(-45deg, #0000 75%, #DDD 75%);
 background-size: 12px 12px;
 background-position: 0 0, 0 6px, 6px -6px, -6px 0px;
 background-repeat: repeat;
 content: "";
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `)]),A(`color-picker-slider`,`
 margin-bottom: 8px;
 position: relative;
 box-sizing: border-box;
 `,[O(`image`,`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `),M(`&::after`,`
 content: "";
 position: absolute;
 border-radius: inherit;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 box-shadow: inset 0 0 2px 0 rgba(0, 0, 0, .24);
 pointer-events: none;
 `)]),A(`color-picker-handle`,`
 z-index: 1;
 box-shadow: 0 0 2px 0 rgba(0, 0, 0, .45);
 position: absolute;
 background-color: white;
 overflow: hidden;
 `,[O(`fill`,`
 box-sizing: border-box;
 border: 2px solid white;
 `)]),A(`color-picker-pallete`,`
 height: 180px;
 position: relative;
 margin-bottom: 8px;
 cursor: crosshair;
 `,[O(`layer`,`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `,[w(`shadowed`,`
 box-shadow: inset 0 0 2px 0 rgba(0, 0, 0, .24);
 `)])]),A(`color-picker-preview`,`
 display: flex;
 `,[O(`sliders`,`
 flex: 1 0 auto;
 `),O(`preview`,`
 position: relative;
 height: 30px;
 width: 30px;
 margin: 0 0 8px 6px;
 border-radius: 50%;
 box-shadow: rgba(0, 0, 0, .15) 0px 0px 0px 1px inset;
 overflow: hidden;
 `),O(`fill`,`
 display: block;
 width: 30px;
 height: 30px;
 `),O(`input`,`
 position: absolute;
 top: 0;
 left: 0;
 width: 30px;
 height: 30px;
 opacity: 0;
 z-index: 1;
 `)]),A(`color-picker-input`,`
 display: flex;
 align-items: center;
 `,[A(`input`,`
 flex-grow: 1;
 flex-basis: 0;
 `),O(`mode`,`
 width: 72px;
 text-align: center;
 `)]),A(`color-picker-control`,`
 padding: 12px;
 `),A(`color-picker-action`,`
 display: flex;
 margin-top: -4px;
 border-top: 1px solid var(--n-divider-color);
 padding: 8px 12px;
 justify-content: flex-end;
 `,[A(`button`,`margin-left: 8px;`)]),A(`color-picker`,`
 display: inline-block;
 box-sizing: border-box;
 height: var(--n-height);
 font-size: var(--n-font-size);
 width: 100%;
 position: relative;
 cursor: pointer;
 border: var(--n-border);
 border-radius: var(--n-border-radius);
 transition: border-color .3s var(--n-bezier);
 `,[w(`disabled`,`cursor: not-allowed`),O(`value`,`
 white-space: nowrap;
 position: relative;
 `),O(`fill`,`
 border-radius: var(--n-border-radius);
 position: absolute;
 display: flex;
 align-items: center;
 justify-content: center;
 left: 4px;
 right: 4px;
 top: 4px;
 bottom: 4px;
 `),A(`color-picker-checkboard`,`
 border-radius: var(--n-border-radius);
 `,[M(`&::after`,`
 --n-block-size: calc((var(--n-height) - 8px) / 3);
 background-size: calc(var(--n-block-size) * 2) calc(var(--n-block-size) * 2);
 background-position: 0 0, 0 var(--n-block-size), var(--n-block-size) calc(-1 * var(--n-block-size)), calc(-1 * var(--n-block-size)) 0px; 
 `)])]),A(`color-picker-swatches`,`
 display: grid;
 grid-gap: 8px;
 flex-wrap: wrap;
 position: relative;
 grid-template-columns: repeat(auto-fill, 18px);
 margin-top: 10px;
 `,[A(`color-picker-swatch`,`
 width: 18px;
 height: 18px;
 background-image: linear-gradient(45deg, #DDD 25%, #0000 25%), linear-gradient(-45deg, #DDD 25%, #0000 25%), linear-gradient(45deg, #0000 75%, #DDD 75%), linear-gradient(-45deg, #0000 75%, #DDD 75%);
 background-size: 8px 8px;
 background-position: 0px 0, 0px 4px, 4px -4px, -4px 0px;
 background-repeat: repeat;
 `,[O(`fill`,`
 position: relative;
 width: 100%;
 height: 100%;
 border-radius: 3px;
 box-shadow: rgba(0, 0, 0, .15) 0px 0px 0px 1px inset;
 cursor: pointer;
 `),M(`&:focus`,`
 outline: none;
 `,[O(`fill`,[M(`&::after`,`
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 background: inherit;
 filter: blur(2px);
 content: "";
 `)])])])])]),Ct={...de.props,value:String,show:{type:Boolean,default:void 0},defaultShow:Boolean,defaultValue:String,modes:{type:Array,default:()=>[`rgb`,`hex`,`hsl`]},placement:{type:String,default:`bottom-start`},to:Ae.propTo,showAlpha:{type:Boolean,default:!0},showPreview:Boolean,swatches:Array,disabled:{type:Boolean,default:void 0},actions:{type:Array,default:null},internalActions:Array,size:String,renderLabel:Function,onComplete:Function,onConfirm:Function,onClear:Function,"onUpdate:show":[Function,Array],onUpdateShow:[Function,Array],"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array]},wt=S({name:`ColorPicker`,inheritAttrs:!1,props:Ct,slots:Object,setup(e,{slots:t}){let n=null;function i(e){n=e}let a=null,{mergedClsPrefixRef:o,namespaceRef:s,inlineThemeDisabled:l,mergedComponentPropsRef:d}=Te(e),f=be(e,{mergedSize:t=>{let{size:n}=e;if(n)return n;let{mergedSize:r}=t||{};return r?.value?r.value:d?.value?.ColorPicker?.size||`medium`}}),{mergedSizeRef:p,mergedDisabledRef:ee}=f,{localeRef:ie}=ke(`global`),_=de(`ColorPicker`,`-color-picker`,St,je,e,o);ce(Je,{themeRef:_,renderLabelRef:ae(e,`renderLabel`),colorPickerSlots:t});let se=y(e.defaultShow),b=Pe(ae(e,`show`),se);function x(t){let{onUpdateShow:n,"onUpdate:show":r}=e;n&&ue(n,t),r&&ue(r,t),se.value=t}let{defaultValue:S}=e,C=y(S===void 0?ze(e.modes,e.showAlpha):S),w=Pe(ae(e,`value`),C),O=y([w.value]),A=y(0),M=m(()=>Be(w.value)),{modes:ye}=e,I=y(Be(w.value)||ye[0]||`rgb`);function Ce(){let{modes:t}=e,{value:n}=I,r=t.findIndex(e=>e===n);~r?I.value=t[(r+1)%t.length]:I.value=`rgb`}let L,R,V,H,U,W,G,K,q=m(()=>{let{value:e}=w;if(!e)return null;switch(M.value){case`hsv`:return k(e);case`hsl`:return[L,R,V,K]=T(e),[...he(L,R,V),K];case`rgb`:case`hex`:return[U,W,G,K]=D(e),[...Ee(U,W,G),K]}}),J=m(()=>{let{value:e}=w;if(!e)return null;switch(M.value){case`rgb`:case`hex`:return D(e);case`hsv`:return[L,R,H,K]=k(e),[...B(L,R,H),K];case`hsl`:return[L,R,V,K]=T(e),[...le(L,R,V),K]}}),Me=m(()=>{let{value:e}=w;if(!e)return null;switch(M.value){case`hsl`:return T(e);case`hsv`:return[L,R,H,K]=k(e),[...ve(L,R,H),K];case`rgb`:case`hex`:return[U,W,G,K]=D(e),[...we(U,W,G),K]}}),Ne=m(()=>{switch(I.value){case`rgb`:case`hex`:return J.value;case`hsv`:return q.value;case`hsl`:return Me.value}}),Fe=y(0),Ie=y(1),Le=y([0,0]);function Re(t,n){let{value:r}=q,i=Fe.value,a=r?r[3]:1;Le.value=[t,n];let{showAlpha:o}=e;switch(I.value){case`hsv`:Y((o?j:pe)([i,t,n,a]),`cursor`);break;case`hsl`:Y((o?z:me)([...ve(i,t,n),a]),`cursor`);break;case`rgb`:Y((o?N:_e)([...B(i,t,n),a]),`cursor`);break;case`hex`:Y((o?E:ge)([...B(i,t,n),a]),`cursor`)}}function Ve(t){Fe.value=t;let{value:n}=q;if(!n)return;let[,r,i,a]=n,{showAlpha:o}=e;switch(I.value){case`hsv`:Y((o?j:pe)([t,r,i,a]),`cursor`);break;case`rgb`:Y((o?N:_e)([...B(t,r,i),a]),`cursor`);break;case`hex`:Y((o?E:ge)([...B(t,r,i),a]),`cursor`);break;case`hsl`:Y((o?z:me)([...ve(t,r,i),a]),`cursor`)}}function He(e){switch(I.value){case`hsv`:[L,R,H]=q.value,Y(j([L,R,H,e]),`cursor`);break;case`rgb`:[U,W,G]=J.value,Y(N([U,W,G,e]),`cursor`);break;case`hex`:[U,W,G]=J.value,Y(E([U,W,G,e]),`cursor`);break;case`hsl`:[L,R,V]=Me.value,Y(z([L,R,V,e]),`cursor`)}Ie.value=e}function Y(t,n){a=n===`cursor`?t:null;let{nTriggerFormChange:r,nTriggerFormInput:i}=f,{onUpdateValue:o,"onUpdate:value":s}=e;o&&ue(o,t),s&&ue(s,t),r(),i(),C.value=t}function Ue(e){Y(e,`input`),oe(X)}function X(t=!0){let{value:n}=w;if(n){let{nTriggerFormChange:r,nTriggerFormInput:i}=f,{onComplete:a}=e;a&&a(n);let{value:o}=O,{value:s}=A;t&&(o.splice(s+1,o.length,n),A.value=s+1),r(),i()}}function We(){let{value:e}=A;e-1<0||(Y(O.value[e-1],`input`),X(!1),A.value=e-1)}function Ge(){let{value:e}=A;e<0||e+1>=O.value.length||(Y(O.value[e+1],`input`),X(!1),A.value=e+1)}function Z(){Y(null,`input`);let{onClear:t}=e;t&&t(),x(!1)}function Ke(){let{value:t}=w,{onConfirm:n}=e;n&&n(t),x(!1)}let Q=m(()=>A.value>=1),Ye=m(()=>{let{value:e}=O;return e.length>1&&A.value<e.length-1});ne(b,e=>{e||(O.value=[w.value],A.value=0)}),te(()=>{if(!(a&&a===w.value)){let{value:e}=q;e&&(Fe.value=e[0],Ie.value=e[3],Le.value=[e[1],e[2]])}a=null});let Xe=m(()=>{let{value:e}=p,{common:{cubicBezierEaseInOut:t},self:{textColor:n,color:r,panelFontSize:i,boxShadow:a,border:o,borderRadius:s,dividerColor:c,[Oe(`height`,e)]:l,[Oe(`fontSize`,e)]:u}}=_.value;return{"--n-bezier":t,"--n-text-color":n,"--n-color":r,"--n-panel-font-size":i,"--n-font-size":u,"--n-box-shadow":a,"--n-border":o,"--n-border-radius":s,"--n-height":l,"--n-divider-color":c}}),Ze=l?fe(`color-picker`,m(()=>p.value[0]),Xe,e):void 0;function Qe(){let{value:n}=J,{value:r}=Fe,{internalActions:i,modes:a,actions:s}=e,{value:d}=_,{value:f}=o;return(()=>{let o=xe(`550d4636453f407b`);return c(),g(`div`,{class:P([`${f}-color-picker-panel`,Ze?.themeClass.value]),onDragstart:o[0]||=e=>{e.preventDefault()},style:u(l?void 0:Xe.value)},[h(`div`,{class:P(`${f}-color-picker-control`)},[(c(),v(xt,{clsPrefix:f,rgba:n,displayedHue:r,displayedSv:Le.value,onUpdateSV:Re,onComplete:X},null,8,[`clsPrefix`,`rgba`,`displayedHue`,`displayedSv`,`onUpdateSV`,`onComplete`])),h(`div`,{class:P(`${f}-color-picker-preview`)},[h(`div`,{class:P(`${f}-color-picker-preview__sliders`)},[(c(),v(_t,{clsPrefix:f,hue:r,onUpdateHue:Ve,onComplete:X},null,8,[`clsPrefix`,`hue`,`onUpdateHue`,`onComplete`])),e.showAlpha?(c(),v(qe,{key:0,clsPrefix:f,rgba:n,alpha:Ie.value,onUpdateAlpha:He,onComplete:X},null,8,[`clsPrefix`,`rgba`,`alpha`,`onUpdateAlpha`,`onComplete`])):F(()=>null)],2),e.showPreview?(c(),v(dt,{key:0,clsPrefix:f,mode:I.value,color:J.value&&ge(J.value),onUpdateColor:o[1]||=e=>{Y(e,`input`)}},null,8,[`clsPrefix`,`mode`,`color`])):F(()=>null)],2),(c(),v(rt,{clsPrefix:f,showAlpha:e.showAlpha,mode:I.value,modes:a,onUpdateMode:Ce,value:w.value,valueArr:Ne.value,onUpdateValue:Ue},null,8,[`clsPrefix`,`showAlpha`,`mode`,`modes`,`onUpdateMode`,`value`,`valueArr`,`onUpdateValue`])),F(()=>e.swatches?.length&&(()=>{let t=xe(`1de0b88852ebf5cb`);return c(),v(st,{clsPrefix:f,mode:I.value,swatches:e.swatches,onUpdateColor:t[0]||=e=>{Y(e,`input`)}},null,8,[`clsPrefix`,`mode`,`swatches`])})())],2),s?.length?(c(),g(`div`,{key:0,class:P(`${f}-color-picker-action`)},[F(()=>s.includes(`confirm`)&&(c(),v(De,{size:`small`,onClick:Ke,theme:d.peers.Button,themeOverrides:d.peerOverrides.Button},{default:()=>ie.value.confirm},1032,[`onClick`,`theme`,`themeOverrides`]))),F(()=>s.includes(`clear`)&&(c(),v(De,{size:`small`,onClick:Z,disabled:!w.value,theme:d.peers.Button,themeOverrides:d.peerOverrides.Button},{default:()=>ie.value.clear},1032,[`onClick`,`disabled`,`theme`,`themeOverrides`])))],2)):F(()=>null),t.action?(c(),g(`div`,{key:2,class:P(`${f}-color-picker-action`)},[F(()=>t.action?.())],2)):(c(),g(re,{key:3},[i?(c(),g(`div`,{key:0,class:P(`${f}-color-picker-action`)},[F(()=>i.includes(`undo`)&&(c(),v(De,{size:`small`,onClick:We,disabled:!Q.value,theme:d.peers.Button,themeOverrides:d.peerOverrides.Button},{default:()=>ie.value.undo},1032,[`onClick`,`disabled`,`theme`,`themeOverrides`]))),F(()=>i.includes(`redo`)&&(c(),v(De,{size:`small`,onClick:Ge,disabled:!Ye.value,theme:d.peers.Button,themeOverrides:d.peerOverrides.Button},{default:()=>ie.value.redo},1032,[`onClick`,`disabled`,`theme`,`themeOverrides`])))],2)):F(()=>null)],64))],38)})()}return{mergedClsPrefix:o,namespace:s,hsla:Me,rgba:J,mergedShow:b,mergedDisabled:ee,isMounted:Se(),adjustedTo:Ae(e),mergedValue:w,handleTriggerClick(){ee.value||x(!0)},setTriggerRef:i,handleClickOutside(e){if(n instanceof Element){if(n.contains(r(e)))return}else if(n&&n.$el.contains(r(e)))return;x(!1)},renderPanel:Qe,cssVars:l?void 0:Xe,themeClass:Ze?.themeClass,onRender:Ze?.onRender}},render(){let{mergedClsPrefix:e,onRender:t}=this;return t?.(),c(),v(W,null,{default:()=>[(c(),v(H,null,{default:()=>{let t=d(this.$attrs,{ref:this.setTriggerRef,value:this.mergedValue,style:this.cssVars,class:this.themeClass});return t.onClick=Ne([this.mergedDisabled?void 0:this.handleTriggerClick,this.$attrs.onClick]),I(this.$slots.trigger,Me(t,[`value`,`onClick`,`ref`]),n=>n||(c(),v(lt,d(t,{clsPrefix:e,hsla:this.hsla,disabled:this.mergedDisabled}),null,16,[`clsPrefix`,`hsla`,`disabled`])))}},1024)),(c(),v(U,{placement:this.placement,show:this.mergedShow,containerClass:this.namespace,teleportDisabled:this.adjustedTo===Ae.tdkey,to:this.adjustedTo},{_:1,default:R(()=>(c(),v(L,{name:`fade-in-scale-up-transition`,appear:this.isMounted},{_:1,default:R(()=>this.mergedShow?i(this.renderPanel(),[[J,this.handleClickOutside,void 0,{capture:!0}]]):null)},8,[`appear`])))},8,[`placement`,`show`,`containerClass`,`teleportDisabled`,`to`]))]},1024)}}),Tt=void 0,Et=(e,t)=>{let n=null,r=!0;return function(){if(!r)return;r=!1;let i=[...arguments];n&&clearTimeout(n),n=setTimeout(()=>{r=!0,e.apply(Tt,i)},t||1e3)}},Dt=S({name:`Vue3IntroStep`,props:{show:{type:Boolean,required:!0},config:{type:Object,required:!0}},emits:[`update:show`],data(){return{originalBox:{left:250,top:250,width:200,height:100},tipBoxPosition:`bottom`,currentIndex:0}},watch:{config:{deep:!0,handler(){this.currentIndex=0},immediate:!0},show(e){e?this.setBoxInfo():document.body.style.overflow=`auto`}},computed:{tipBoxStyle(){if(this.tipBoxPosition===`right`)return{left:`${this.originalBox.left+this.originalBox.width}px`,top:`${this.originalBox.top}px`};if(this.tipBoxPosition===`left`)return{right:`${window.innerWidth-this.originalBox.left}px`,top:`${this.originalBox.top}px`};if(this.tipBoxPosition===`top`)return{left:`${this.originalBox.left}px`,bottom:`${window.innerHeight-this.originalBox.top}px`};if(this.tipBoxPosition===`bottom`)return{left:`${this.originalBox.left>window.innerWidth-300?window.innerWidth-300:this.originalBox.left}px`,top:`${this.originalBox.top+this.originalBox.height}px`}}},created(){this.init()},mounted(){window.onresize=Et(()=>{this.show&&this.setBoxInfo()},100)},beforeUnmount(){window.onresize=null},methods:{async prev(){let e=!0;if(this.config.tips[this.currentIndex]&&this.config.tips[this.currentIndex].onPrev&&(e=await this.config.tips[this.currentIndex].onPrev()),!e)throw Error(`onPrev 需要 Promise.resolve(true) 才可以继续往下走`);this.setBoxInfo(this.currentIndex-1)},async next(){let e=!0;if(this.config.tips[this.currentIndex]&&this.config.tips[this.currentIndex].onNext&&(e=await this.config.tips[this.currentIndex].onNext()),!e)throw Error(`onNext 需要 Promise.resolve(true) 才可以继续往下走`);this.setBoxInfo(this.currentIndex+1)},done(){this.$emit(`update:show`,!1)},async setBoxInfo(e){try{e===void 0&&(e=this.currentIndex),this.show&&(document.body.style.overflow=`hidden`);let t=this.config.tips[e].el,n=document.querySelector(t);if(!n)throw Error(`没有找到相应的元素`);let r=n.getBoundingClientRect();this.originalBox={left:r.left,top:r.top,width:r.width,height:r.height},this.tipBoxPosition=this.config.tips[e].tipPosition,this.currentIndex=e}catch(e){throw Error(e.message)}},init(){let{tips:e}=this.config,t=null;if(e&&Array.isArray(e)){if(e.length>0){this.currentIndex=0;try{let n=document.querySelector(e[0].el);t=setInterval(()=>{n=document.querySelector(e[0].el),n&&(this.setBoxInfo(0),clearInterval(t))},0)}catch(e){throw Error(e.message)}}else throw Error(`tips数组不能为空`)}else throw Error(`config中的tips不存在或者不是数组`)}}}),Ot=e=>(a(`data-v-5d3b253c`),e=e(),ee(),e),kt={key:0,id:`intro_box`},At=[Ot(()=>h(`div`,{class:`round round-flicker`},null,-1))],jt={class:`tip-content`},Mt={class:`action`,style:{justifyContent:`center`}};function Nt(e,t,n,r,i,a){return c(),v(L,{name:`custom-classes-transition`,"enter-active-class":`animate__animated animate__fadeIn animate__faster`,"leave-active-class":`animate__animated animate__fadeOut animate__faster`},{default:p(()=>[e.show?(c(),g(`div`,kt,[h(`div`,{class:`top`,style:u({height:`${e.originalBox.top}px`,backgroundColor:`rgba(0, 0, 0, ${e.config.backgroundOpacity?e.config.backgroundOpacity:.9})`})},null,4),h(`div`,{class:`content`,style:u({height:`${e.originalBox.height}px`})},[h(`div`,{class:`left`,style:u({top:`${e.originalBox.top}px`,width:`${e.originalBox.left}px`,height:`${e.originalBox.height}px`,backgroundColor:`rgba(0, 0, 0, ${e.config.backgroundOpacity?e.config.backgroundOpacity:.9})`})},null,4),h(`div`,{class:`original-box`,style:u({top:`${e.originalBox.top}px`,left:`${e.originalBox.left}px`,width:`${e.originalBox.width}px`,height:`${e.originalBox.height}px`})},At,4),h(`div`,{class:`tip-box`,style:u(e.tipBoxStyle)},[h(`div`,jt,[e.config.tips[e.currentIndex].title?(c(),g(`div`,{key:0,class:`title`,style:u({textAlign:e.config.titleStyle&&e.config.titleStyle.textAlign?e.config.titleStyle.textAlign:`center`,fontSize:e.config.titleStyle&&e.config.titleStyle.fontSize?e.config.titleStyle.fontSize:`19px`})},f(e.config.tips[e.currentIndex].title),5)):_(``,!0),h(`div`,{class:`content`,style:u({textAlign:e.config.contentStyle&&e.config.contentStyle.textAlign?e.config.contentStyle.textAlign:`center`,fontSize:e.config.contentStyle&&e.config.contentStyle.fontSize?e.config.contentStyle.fontSize:`15px`})},f(e.config.tips[e.currentIndex].content),5),h(`div`,Mt,[e.currentIndex===0?_(``,!0):s(e.$slots,`prev`,{key:0,index:e.currentIndex,tipItem:e.config.tips[e.currentIndex]},()=>[h(`div`,{class:`item prev`,onClick:t[0]||=function(){return e.prev&&e.prev(...arguments)}},`上一步`)]),e.currentIndex===e.config.tips.length-1?_(``,!0):s(e.$slots,`next`,{key:1,index:e.currentIndex,tipItem:e.config.tips[e.currentIndex]},()=>[h(`div`,{class:`item next`,onClick:t[1]||=function(){return e.next&&e.next(...arguments)}},`下一步`)]),e.currentIndex===e.config.tips.length-1?s(e.$slots,`done`,{key:2,index:e.currentIndex,tipItem:e.config.tips[e.currentIndex]},()=>[h(`div`,{class:`item done`,onClick:t[2]||=function(){return e.done&&e.done(...arguments)}},`完成`)]):s(e.$slots,`skip`,{key:3,index:e.currentIndex,tipItem:e.config.tips[e.currentIndex]},()=>[h(`div`,{class:`item skip`,onClick:t[3]||=function(){return e.done&&e.done(...arguments)}},`跳过`)])])])],4),h(`div`,{class:`right`,style:u({top:`${e.originalBox.top}px`,left:`${e.originalBox.left+e.originalBox.width}px`,width:`calc(100% - ${e.originalBox.left+e.originalBox.width}px)`,height:`${e.originalBox.height}px`,backgroundColor:`rgba(0, 0, 0, ${e.config.backgroundOpacity?e.config.backgroundOpacity:.9})`}),ref:`tip_box`},null,4)],4),h(`div`,{class:`bottom`,style:u({height:`calc(100% - ${e.originalBox.top}px - ${e.originalBox.height}px)`,backgroundColor:`rgba(0, 0, 0, ${e.config.backgroundOpacity?e.config.backgroundOpacity:.9})`})},null,4)])):_(``,!0)]),_:3})}function Pt(e,t){t===void 0&&(t={});var n=t.insertAt;if(e&&typeof document<`u`){var r=document.head||document.getElementsByTagName(`head`)[0],i=document.createElement(`style`);i.type=`text/css`,n===`top`&&r.firstChild?r.insertBefore(i,r.firstChild):r.appendChild(i),i.styleSheet?i.styleSheet.cssText=e:i.appendChild(document.createTextNode(e))}}Pt(`
#intro_box[data-v-5d3b253c] {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  z-index: 99999;
}
#intro_box > .top[data-v-5d3b253c] {
  width: 100%;
}
#intro_box > .content[data-v-5d3b253c] {
  width: 100%;
}
#intro_box > .content > .left[data-v-5d3b253c] {
  position: absolute;
  left: 0;
}
#intro_box > .content > .original-box[data-v-5d3b253c] {
  position: absolute;
  background-color: transparent;
  transition: all 0.3s cubic-bezier(0, 0, 0.58, 1);
}
#intro_box > .content > .original-box .round[data-v-5d3b253c] {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 10px;
  height: 10px;
  border-radius: 50%;
  opacity: 0.65;
  background-color: #9900ff;
}
#intro_box > .content > .original-box .round-flicker[data-v-5d3b253c]:before,
#intro_box > .content > .original-box .round-flicker[data-v-5d3b253c]:after {
  content: '';
  width: 100%;
  height: 100%;
  position: absolute;
  left: -1px;
  top: -1px;
  box-shadow: #9900ff 0px 0px 2px 2px;
  border: 1px solid rgba(153, 0, 255, 0.5);
  border-radius: 50%;
  animation: warn-5d3b253c 2s linear 0s infinite;
}
@keyframes warn-5d3b253c {
0% {
    transform: scale(0.5);
    opacity: 1;
}
25% {
    transform: scale(1);
    opacity: 0.75;
}
50% {
    transform: scale(1.5);
    opacity: 0.5;
}
75% {
    transform: scale(2);
    opacity: 0.25;
}
100% {
    transform: scale(2.5);
    opacity: 0;
}
}
#intro_box > .content > .tip-box[data-v-5d3b253c] {
  position: absolute;
  /*宽度应为内容宽*/
  width: fit-content;
  max-width: 300px;
  box-sizing: border-box;
  /*高度应为内容高度*/
  height: fit-content;
  transition: all 0.3s;
  z-index: 99999;
  padding: 12px;
  font-size: 15px;
}
#intro_box > .content > .tip-box > .tip-content[data-v-5d3b253c] {
  border-radius: 10px;
  overflow: hidden;
  padding: 10px;
  color: #fff;
}
#intro_box > .content > .tip-box > .tip-content > .title[data-v-5d3b253c] {
  font-weight: bold;
  margin-bottom: 10px;
}
#intro_box > .content > .tip-box > .tip-content > .content[data-v-5d3b253c] {
  white-space: normal;
  overflow-wrap: break-word;
  line-height: 1.5;
}
#intro_box > .content > .tip-box > .tip-content > .action[data-v-5d3b253c] {
  margin-top: 15px;
  width: 100%;
  display: flex;
}
#intro_box > .content > .tip-box > .tip-content > .action > .item[data-v-5d3b253c] {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  border-radius: 15px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s;
  padding: 5px 15px;
  color: #fff;
  font-weight: bold;
  border: 1px solid #ccc;
  margin: 5px;
}
#intro_box > .content > .tip-box > .tip-content > .action > .item.prev[data-v-5d3b253c] {
  color: #ccc;
}
#intro_box > .content > .tip-box > .tip-content > .action > .item.next[data-v-5d3b253c] {
  color: #ccc;
}
#intro_box > .content > .tip-box > .tip-content > .action > .item.done[data-v-5d3b253c] {
  color: #ccc;
}
#intro_box > .content > .tip-box > .tip-content > .action > .item.skip[data-v-5d3b253c] {
  color: #ccc;
}
#intro_box > .content > .right[data-v-5d3b253c] {
  position: absolute;
  background-color: rgba(0, 0, 0, 0.9);
}
#intro_box > .bottom[data-v-5d3b253c] {
  width: 100%;
  background-color: rgba(0, 0, 0, 0.9);
}
`),Dt.render=Nt,Dt.__scopeId=`data-v-5d3b253c`;var Ft=(()=>{let e=Dt;return e.install=t=>{t.component(`Vue3IntroStep`,e)},e})(),It={__name:`BeginnerGuide`,setup(e){let t=se(null),n=se(!1),r={backgroundOpacity:.8,titleStyle:{textAlign:`left`,fontSize:`18px`},contentStyle:{textAlign:`left`,fontSize:`14px`},tips:[{el:`#toggleTheme`,tipPosition:`bottom`,title:`切换系统主题`,content:`一键开启护眼模式`},{el:`#fullscreen`,tipPosition:`bottom`,title:`全屏/退出全屏`,content:`一键开启全屏`},{el:`#theme-setting`,tipPosition:`bottom`,title:`设置主题色`,content:`调整为你喜欢的主题色`},{el:`#user-dropdown`,tipPosition:`bottom`,title:`个人中心`,content:`查看个人资料和退出系统`},{el:`#menu-collapse`,tipPosition:`bottom`,title:`展开/收起菜单`,content:`一键展开/收起菜单`},{el:`#top-tab`,tipPosition:`bottom`,title:`标签栏`,content:`鼠标滚轮滑动可调整至最佳视野`},{el:`#layout-setting`,tipPosition:`left`,title:`调整系统布局`,content:`将系统布局调整为你喜欢的样子`}]};function i(){n.value=!1}function a(){n.value=!1}function o(){t.value.next()}function s(){t.value.prev()}return(e,l)=>{let u=Ie,d=De;return c(),g(re,null,[C(u,{trigger:`hover`},{trigger:p(()=>[h(`i`,{class:`i-fe:beginner mr-16 cursor-pointer text-20`,onClick:l[0]||=e=>n.value=!0})]),default:p(()=>[l[2]||=b(` 操作指引 `,-1)]),_:1}),C(x(Ft),{ref_key:`myIntroStep`,ref:t,show:x(n),"onUpdate:show":l[1]||=e=>ie(n)?n.value=e:null,config:r},{prev:p(({tipItem:e,index:t})=>[C(d,{class:`mr-12`,type:`primary`,color:`#fff`,"text-color":`#fff`,ghost:``,round:``,size:`small`,onClick:n=>s(e,t)},{default:p(()=>[...l[3]||=[b(` 上一步 `,-1)]]),_:1},8,[`onClick`])]),next:p(({tipItem:e})=>[C(d,{class:`mr-12`,type:`primary`,color:`#fff`,"text-color":`#fff`,ghost:``,round:``,size:`small`,onClick:t=>o(e)},{default:p(()=>[...l[4]||=[b(` 下一步 `,-1)]]),_:1},8,[`onClick`])]),skip:p(()=>[C(d,{type:`primary`,color:`#fff`,"text-color":`#fff`,ghost:``,round:``,size:`small`,onClick:i},{default:p(()=>[...l[5]||=[b(` 跳过 `,-1)]]),_:1})]),done:p(()=>[C(d,{type:`primary`,color:`#fff`,"text-color":`#fff`,ghost:``,round:``,size:`small`,onClick:a},{default:p(()=>[...l[6]||=[b(` 完成 `,-1)]]),_:1})]),_:1},8,[`show`])],64)}}},Lt={__name:`Fullscreen`,setup(e){let{isFullscreen:t,toggle:n}=G();return(e,r)=>(c(),g(`i`,{id:`fullscreen`,class:l([`mr-16 cursor-pointer`,x(t)?`i-fe:minimize`:`i-fe:maximize`]),onClick:r[0]||=(...e)=>x(n)&&x(n)(...e)},null,2))}},Rt=q(),zt={class:`f-c-c`},Bt={id:`theme-setting`,class:`h-32 w-32`},Vt={__name:`ThemeSetting`,setup(e){let t=K(),n=Object.entries((0,Rt.getPresetColors)()).map(([,e])=>e.primary);return(e,r)=>{let i=wt,a=Ie;return c(),g(`div`,zt,[C(a,{trigger:`hover`,placement:`bottom`},{trigger:p(()=>[h(`div`,Bt,[C(i,{value:x(t).primaryColor,swatches:x(n),"on-update:value":e=>x(t).setPrimaryColor(e),"render-label":()=>``},null,8,[`value`,`swatches`,`on-update:value`])])]),default:p(()=>[r[0]||=b(` 设置主题色 `,-1)]),_:1})])}}};export{Lt as n,It as r,Vt as t};