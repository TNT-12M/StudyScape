import{$ as e,B as t,H as n,V as r,w as i}from"./php-modules-BAZCMuH3.js";import{At as a,B as o,D as s,E as c,F as l,I as u,J as d,K as ee,L as te,Ot as ne,R as re,Y as ie,_ as f,d as p,dt as ae,f as m,g as h,i as oe,kt as g,lt as se,m as _,mt as v,ot as y,p as b,q as ce,tt as le,u as x,w as ue,y as S}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$ as de,$t as C,A as fe,B as w,F as pe,G as T,H as E,I as me,J as he,K as ge,Q as _e,Qt as D,V as O,W as ve,X as ye,Xt as k,Y as A,Yt as j,Z as M,at as N,ct as P,et as be,gt as xe,h as Se,it as Ce,j as we,k as F,lt as Te,nt as Ee,on as I,ot as L,pt as De,q as R,rt as Oe,t as ke,tn as Ae,tt as z,yt as B}from"./Button-CjW_3_5b.js";import{t as je}from"./use-locale-BNyEcri-.js";import{a as V,i as H,o as U,s as Me}from"./Popover-kh6leykI.js";import{S as Ne,f as W,m as G,u as K}from"./store-DGKF2Gsv.js";import{d as q,f as Pe,o as Fe}from"./light-5ZEtuWM4.js";import{t as Ie}from"./Input-CM3fr4TJ.js";import{t as Le}from"./Tooltip-DSsIQ8AL.js";var Re=k(`input-group`,`
 display: inline-flex;
 width: 100%;
 flex-wrap: nowrap;
 vertical-align: bottom;
`,[j(`>`,[k(`input`,[j(`&:not(:last-child)`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `),j(`&:not(:first-child)`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 margin-left: -1px!important;
 `)]),k(`button`,[j(`&:not(:last-child)`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `,[D(`state-border, border`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `)]),j(`&:not(:first-child)`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `,[D(`state-border, border`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `)])]),j(`*`,[j(`&:not(:last-child)`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `,[j(`>`,[k(`input`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `),k(`base-selection`,[k(`base-selection-label`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `),k(`base-selection-tags`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `),D(`box-shadow, border, state-border`,`
 border-top-right-radius: 0!important;
 border-bottom-right-radius: 0!important;
 `)])])]),j(`&:not(:first-child)`,`
 margin-left: -1px!important;
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `,[j(`>`,[k(`input`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `),k(`base-selection`,[k(`base-selection-label`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `),k(`base-selection-tags`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `),D(`box-shadow, border, state-border`,`
 border-top-left-radius: 0!important;
 border-bottom-left-radius: 0!important;
 `)])])])])])]),ze=S({name:`InputGroup`,props:{},setup(e){let{mergedClsPrefixRef:t}=De(e);return Te(`-input-group`,Re,t),{mergedClsPrefix:t}},render(){let{mergedClsPrefix:e}=this;return l(),_(`div`,{class:N(`${e}-input-group`)},[P(()=>this.$slots.default?.())],2)}});function Be(e,t){switch(e[0]){case`hex`:return t?`#000000FF`:`#000000`;case`rgb`:return t?`rgba(0, 0, 0, 1)`:`rgb(0, 0, 0)`;case`hsl`:return t?`hsla(0, 0%, 0%, 1)`:`hsl(0, 0%, 0%)`;case`hsv`:return t?`hsva(0, 0%, 0%, 1)`:`hsv(0, 0%, 0%)`}return`#000000`}function Ve(e){return e===null?null:/^ *#/.test(e)?`hex`:e.includes(`rgb`)?`rgb`:e.includes(`hsl`)?`hsl`:e.includes(`hsv`)?`hsv`:null}function He(e,t=[255,255,255],n=`AA`){let[r,i,a,o]=E(R(e));if(o===1){let e=Ue([r,i,a]),o=Ue(t);return(Math.max(e,o)+.05)/(Math.min(e,o)+.05)>=(n===`AA`?4.5:7)}let s=Ue([Math.round(r*o+t[0]*(1-o)),Math.round(i*o+t[1]*(1-o)),Math.round(a*o+t[2]*(1-o))]),c=Ue(t);return(Math.max(s,c)+.05)/(Math.min(s,c)+.05)>=(n===`AA`?4.5:7)}function Ue(e){let[t,n,r]=e.map(e=>(e/=255,e<=.03928?e/12.92:((e+.055)/1.055)**2.4));return .2126*t+.7152*n+.0722*r}function J(e){return e=Math.round(e),e>=360?359:e<0?0:e}function We(e){return e=Math.round(e*100)/100,e>1?1:e<0?0:e}var Y={rgb:{hex(e){return T(E(e))},hsl(e){let[t,n,r,i]=E(e);return R([...Ee(t,n,r),i])},hsv(e){let[t,n,r,i]=E(e);return A([...Oe(t,n,r),i])}},hex:{rgb(e){return M(E(e))},hsl(e){let[t,n,r,i]=E(e);return R([...Ee(t,n,r),i])},hsv(e){let[t,n,r,i]=E(e);return A([...Oe(t,n,r),i])}},hsl:{hex(e){let[t,n,r,i]=w(e);return T([...de(t,n,r),i])},rgb(e){let[t,n,r,i]=w(e);return M([...de(t,n,r),i])},hsv(e){let[t,n,r,i]=w(e);return A([..._e(t,n,r),i])}},hsv:{hex(e){let[t,n,r,i]=O(e);return T([...z(t,n,r),i])},rgb(e){let[t,n,r,i]=O(e);return M([...z(t,n,r),i])},hsl(e){let[t,n,r,i]=O(e);return R([...be(t,n,r),i])}}};function Ge(e,t,n){return n||=Ve(e),n?n===t?e:Y[n][t](e):null}var Ke=[`onMousedown`],X=`12px`,qe=12,Z=`6px`,Je=S({name:`AlphaSlider`,props:{clsPrefix:{type:String,required:!0},rgba:{type:Array,default:null},alpha:{type:Number,default:0},onUpdateAlpha:{type:Function,required:!0},onComplete:Function},setup(e){let t=y(null);function i(r){t.value&&e.rgba&&(n(`mousemove`,document,a),n(`mouseup`,document,o),a(r))}function a(n){let{value:r}=t;if(!r)return;let{width:i,left:a}=r.getBoundingClientRect(),o=(n.clientX-a)/(i-qe);e.onUpdateAlpha(We(o))}function o(){r(`mousemove`,document,a),r(`mouseup`,document,o),e.onComplete?.()}return{railRef:t,railBackgroundImage:x(()=>{let{rgba:t}=e;return t?`linear-gradient(to right, rgba(${t[0]}, ${t[1]}, ${t[2]}, 0) 0%, rgba(${t[0]}, ${t[1]}, ${t[2]}, 1) 100%)`:``}),handleMouseDown:i}},render(){let{clsPrefix:e}=this;return l(),_(`div`,{class:N(`${e}-color-picker-slider`),ref:`railRef`,style:g({height:X,borderRadius:Z}),onMousedown:this.handleMouseDown},[p(`div`,{style:g({borderRadius:Z,position:`absolute`,left:0,right:0,top:0,bottom:0,overflow:`hidden`})},[p(`div`,{class:N(`${e}-color-picker-checkboard`)},null,2),p(`div`,{class:N(`${e}-color-picker-slider__image`),style:g({backgroundImage:this.railBackgroundImage})},null,6)],4),P(()=>this.rgba&&(l(),_(`div`,{style:g({position:`absolute`,left:Z,right:Z,top:0,bottom:0})},[p(`div`,{class:N(`${e}-color-picker-handle`),style:g({left:`calc(${this.alpha*100}% - ${Z})`,borderRadius:Z,width:X,height:X})},[p(`div`,{class:N(`${e}-color-picker-handle__fill`),style:g({backgroundColor:M(this.rgba),borderRadius:Z,width:X,height:X})},null,6)],6)],4)))],46,Ke)}}),Ye=xe(`n-color-picker`);function Xe(e){return/^\d{1,3}\.?\d*$/.test(e.trim())?Math.max(0,Math.min(Number.parseInt(e),255)):!1}function Ze(e){return/^\d{1,3}\.?\d*$/.test(e.trim())?Math.max(0,Math.min(Number.parseInt(e),360)):!1}function Qe(e){return/^\d{1,3}\.?\d*$/.test(e.trim())?Math.max(0,Math.min(Number.parseInt(e),100)):!1}function $e(e){let t=e.trim();return/^#[0-9a-fA-F]+$/.test(t)?[4,5,7,9].includes(t.length):!1}function et(e){return/^\d{1,3}\.?\d*%$/.test(e.trim())?Math.max(0,Math.min(Number.parseInt(e)/100,100)):!1}var tt={paddingSmall:`0 4px`},nt=S({name:`ColorInputUnit`,props:{label:{type:String,required:!0},value:{type:[Number,String],default:null},showAlpha:Boolean,onUpdateValue:{type:Function,required:!0}},setup(e){let t=y(``),{themeRef:n}=ue(Ye,null);ce(()=>{t.value=r()});function r(){let{value:t}=e;if(t===null)return``;let{label:n}=e;return n===`HEX`?t:n===`A`?`${Math.floor(t*100)}%`:String(Math.floor(t))}function i(e){t.value=e}function a(n){let i,a;switch(e.label){case`HEX`:a=$e(n),a&&e.onUpdateValue(n),t.value=r();break;case`H`:i=Ze(n),i===!1?t.value=r():e.onUpdateValue(i);break;case`S`:case`L`:case`V`:i=Qe(n),i===!1?t.value=r():e.onUpdateValue(i);break;case`A`:i=et(n),i===!1?t.value=r():e.onUpdateValue(i);break;case`R`:case`G`:case`B`:i=Xe(n),i===!1?t.value=r():e.onUpdateValue(i)}}return{mergedTheme:n,inputValue:t,handleInputChange:a,handleInputUpdateValue:i}},render(){let{mergedTheme:e}=this;return l(),m(Ie,{size:`small`,placeholder:this.label,theme:e.peers.Input,themeOverrides:e.peerOverrides.Input,builtinThemeOverrides:tt,value:this.inputValue,onUpdateValue:this.handleInputUpdateValue,onChange:this.handleInputChange,style:g(this.label===`A`?`flex-grow: 1.25;`:``)},null,8,[`placeholder`,`theme`,`themeOverrides`,`builtinThemeOverrides`,`value`,`onUpdateValue`,`onChange`,`style`])}}),rt=[`onClick`],it=S({name:`ColorInput`,props:{clsPrefix:{type:String,required:!0},mode:{type:String,required:!0},modes:{type:Array,required:!0},showAlpha:{type:Boolean,required:!0},value:{type:String,default:null},valueArr:{type:Array,default:null},onUpdateValue:{type:Function,required:!0},onUpdateMode:{type:Function,required:!0}},setup(e){return{handleUnitUpdateValue(t,n){let{showAlpha:r}=e;if(e.mode===`hex`){e.onUpdateValue((r?T:ve)(n));return}let i;switch(i=e.valueArr===null?[0,0,0,0]:Array.from(e.valueArr),e.mode){case`hsv`:i[t]=n,e.onUpdateValue((r?A:he)(i));break;case`rgb`:i[t]=n,e.onUpdateValue((r?M:ye)(i));break;case`hsl`:i[t]=n,e.onUpdateValue((r?R:ge)(i))}}}},render(){let{clsPrefix:e,modes:t}=this;return l(),_(`div`,{class:N(`${e}-color-picker-input`)},[p(`div`,{class:N(`${e}-color-picker-input__mode`),onClick:this.onUpdateMode,style:g({cursor:t.length===1?``:`pointer`})},[P(()=>this.mode.toUpperCase()+(this.showAlpha?`A`:``))],14,rt),f(ze,null,{default:()=>{let{mode:e,valueArr:t,showAlpha:n}=this;if(e===`hex`){let e=null;try{e=t===null?null:(n?T:ve)(t)}catch{}return l(),m(nt,{key:1,label:`HEX`,showAlpha:n,value:e,onUpdateValue:e=>{this.handleUnitUpdateValue(0,e)}},null,8,[`showAlpha`,`value`,`onUpdateValue`])}return(e+(n?`a`:``)).split(``).map((e,n)=>(l(),m(nt,{label:e.toUpperCase(),value:t===null?null:t[n],onUpdateValue:e=>{this.handleUnitUpdateValue(n,e)}},null,8,[`label`,`value`,`onUpdateValue`])))}},1024)],2)}}),at=[`onClick`,`onKeydown`];function ot(e,t){if(t===`hsv`){let[t,n,r,i]=O(e);return M([...z(t,n,r),i])}return e}function st(e){let t=document.createElement(`canvas`).getContext(`2d`);return t?(t.fillStyle=e,t.fillStyle):`#000000`}var ct=S({name:`ColorPickerSwatches`,props:{clsPrefix:{type:String,required:!0},mode:{type:String,required:!0},swatches:{type:Array,required:!0},onUpdateColor:{type:Function,required:!0}},setup(e){let t=x(()=>e.swatches.map(e=>{let t=Ve(e);return{value:e,mode:t,legalValue:ot(e,t)}}));function n(t){let{mode:n}=e,{value:r,mode:i}=t;return i||(i=`hex`,/^[a-zA-Z]+$/.test(r)?r=st(r):(B(`color-picker`,`color ${r} in swatches is invalid.`),r=`#000000`)),i===n?r:Ge(r,n,i)}function r(t){e.onUpdateColor(n(t))}function i(e,t){e.key===`Enter`&&r(t)}return{parsedSwatchesRef:t,handleSwatchSelect:r,handleSwatchKeyDown:i}},render(){let{clsPrefix:e}=this;return l(),_(`div`,{class:N(`${e}-color-picker-swatches`)},[P(()=>this.parsedSwatchesRef.map(t=>(l(),_(`div`,{class:N(`${e}-color-picker-swatch`),tabindex:0,onClick:()=>{this.handleSwatchSelect(t)},onKeydown:e=>{this.handleSwatchKeyDown(e,t)}},[p(`div`,{class:N(`${e}-color-picker-swatch__fill`),style:g({background:t.legalValue})},null,6)],42,at))))],2)}}),lt=[`onClick`],ut=S({name:`ColorPickerTrigger`,slots:Object,props:{clsPrefix:{type:String,required:!0},value:{type:String,default:null},hsla:{type:Array,default:null},disabled:Boolean,onClick:Function},setup(e){let{colorPickerSlots:t,renderLabelRef:n}=ue(Ye,null);return()=>{let{hsla:r,value:i,clsPrefix:a,onClick:o,disabled:s}=e,c=t.label||n.value;return l(),_(`div`,{class:N([`${a}-color-picker`,s&&`${a}-color-picker--disabled`]),onClick:s?void 0:o},[p(`div`,{class:N(`${a}-color-picker__fill`)},[p(`div`,{class:N(`${a}-color-picker-checkboard`)},null,2),p(`div`,{style:g({position:`absolute`,left:0,right:0,top:0,bottom:0,backgroundColor:r?R(r):``})},null,4),i&&r?(l(),_(`div`,{key:0,class:N(`${a}-color-picker__value`),style:g({color:He(r)?`white`:`black`})},[c?(l(),_(oe,{key:0},[P(()=>c(i))],64)):(l(),_(oe,{key:1},[P(()=>i)],64))],6)):P(()=>null)],2)],10,lt)}}}),dt=[`value`,`onChange`],ft=S({name:`ColorPreview`,props:{clsPrefix:{type:String,required:!0},mode:{type:String,required:!0},color:{type:String,default:null,validator:e=>{let t=Ve(e);return!!(!e||t&&t!==`hsv`)}},onUpdateColor:{type:Function,required:!0}},setup(e){function t(t){let n=t.target.value;e.onUpdateColor?.(Ge(n.toUpperCase(),e.mode,`hex`)),t.stopPropagation()}return{handleChange:t}},render(){let{clsPrefix:e}=this;return l(),_(`div`,{class:N(`${e}-color-picker-preview__preview`)},[p(`span`,{class:N(`${e}-color-picker-preview__fill`),style:g({background:this.color||`#000000`})},null,6),p(`input`,{class:N(`${e}-color-picker-preview__input`),type:`color`,value:this.color,onChange:this.handleChange},null,42,dt)],2)}}),pt=[`onMousedown`],Q=`12px`,mt=12,$=`6px`,ht=6,gt=`linear-gradient(90deg,red,#ff0 16.66%,#0f0 33.33%,#0ff 50%,#00f 66.66%,#f0f 83.33%,red)`,_t=S({name:`HueSlider`,props:{clsPrefix:{type:String,required:!0},hue:{type:Number,required:!0},onUpdateHue:{type:Function,required:!0},onComplete:Function},setup(e){let t=y(null);function i(e){t.value&&(n(`mousemove`,document,a),n(`mouseup`,document,o),a(e))}function a(n){let{value:r}=t;if(!r)return;let{width:i,left:a}=r.getBoundingClientRect(),o=J((n.clientX-a-ht)/(i-mt)*360);e.onUpdateHue(o)}function o(){r(`mousemove`,document,a),r(`mouseup`,document,o),e.onComplete?.()}return{railRef:t,handleMouseDown:i}},render(){let{clsPrefix:e}=this;return l(),_(`div`,{class:N(`${e}-color-picker-slider`),style:g({height:Q,borderRadius:$})},[p(`div`,{ref:`railRef`,style:g({boxShadow:`inset 0 0 2px 0 rgba(0, 0, 0, .24)`,boxSizing:`border-box`,backgroundImage:gt,height:Q,borderRadius:$,position:`relative`}),onMousedown:this.handleMouseDown},[p(`div`,{style:g({position:`absolute`,left:$,right:$,top:0,bottom:0})},[p(`div`,{class:N(`${e}-color-picker-handle`),style:g({left:`calc((${this.hue}%) / 359 * 100 - ${$})`,borderRadius:$,width:Q,height:Q})},[p(`div`,{class:N(`${e}-color-picker-handle__fill`),style:g({backgroundColor:`hsl(${this.hue}, 100%, 50%)`,borderRadius:$,width:Q,height:Q})},null,6)],6)],4)],44,pt)],6)}}),vt=[`onMousedown`],yt=`12px`,bt=`6px`,xt=S({name:`Pallete`,props:{clsPrefix:{type:String,required:!0},rgba:{type:Array,default:null},displayedHue:{type:Number,required:!0},displayedSv:{type:Array,required:!0},onUpdateSV:{type:Function,required:!0},onComplete:Function},setup(e){let t=y(null);function i(e){t.value&&(n(`mousemove`,document,a),n(`mouseup`,document,o),a(e))}function a(n){let{value:r}=t;if(!r)return;let{width:i,height:a,left:o,bottom:s}=r.getBoundingClientRect(),c=(s-n.clientY)/a,l=(n.clientX-o)/i,u=100*(l>1?1:l<0?0:l),d=100*(c>1?1:c<0?0:c);e.onUpdateSV(u,d)}function o(){r(`mousemove`,document,a),r(`mouseup`,document,o),e.onComplete?.()}return{palleteRef:t,handleColor:x(()=>{let{rgba:t}=e;return t?`rgb(${t[0]}, ${t[1]}, ${t[2]})`:``}),handleMouseDown:i}},render(){let{clsPrefix:e}=this;return l(),_(`div`,{class:N(`${e}-color-picker-pallete`),onMousedown:this.handleMouseDown,ref:`palleteRef`},[p(`div`,{class:N(`${e}-color-picker-pallete__layer`),style:g({backgroundImage:`linear-gradient(90deg, white, hsl(${this.displayedHue}, 100%, 50%))`})},null,6),p(`div`,{class:N(`${e}-color-picker-pallete__layer ${e}-color-picker-pallete__layer--shadowed`),style:{backgroundImage:`linear-gradient(180deg, rgba(0, 0, 0, 0%), rgba(0, 0, 0, 100%))`}},null,2),P(()=>this.rgba&&(l(),_(`div`,{class:N(`${e}-color-picker-handle`),style:g({width:yt,height:yt,borderRadius:bt,left:`calc(${this.displayedSv[0]}% - ${bt})`,bottom:`calc(${this.displayedSv[1]}% - ${bt})`})},[p(`div`,{class:N(`${e}-color-picker-handle__fill`),style:g({backgroundColor:this.handleColor,borderRadius:bt,width:yt,height:yt})},null,6)],6)))],42,vt)}}),St=j([k(`color-picker-panel`,`
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
 `,[i(),k(`input`,`
 text-align: center;
 `)]),k(`color-picker-checkboard`,`
 background: white; 
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `,[j(`&::after`,`
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
 `)]),k(`color-picker-slider`,`
 margin-bottom: 8px;
 position: relative;
 box-sizing: border-box;
 `,[D(`image`,`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `),j(`&::after`,`
 content: "";
 position: absolute;
 border-radius: inherit;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 box-shadow: inset 0 0 2px 0 rgba(0, 0, 0, .24);
 pointer-events: none;
 `)]),k(`color-picker-handle`,`
 z-index: 1;
 box-shadow: 0 0 2px 0 rgba(0, 0, 0, .45);
 position: absolute;
 background-color: white;
 overflow: hidden;
 `,[D(`fill`,`
 box-sizing: border-box;
 border: 2px solid white;
 `)]),k(`color-picker-pallete`,`
 height: 180px;
 position: relative;
 margin-bottom: 8px;
 cursor: crosshair;
 `,[D(`layer`,`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `,[C(`shadowed`,`
 box-shadow: inset 0 0 2px 0 rgba(0, 0, 0, .24);
 `)])]),k(`color-picker-preview`,`
 display: flex;
 `,[D(`sliders`,`
 flex: 1 0 auto;
 `),D(`preview`,`
 position: relative;
 height: 30px;
 width: 30px;
 margin: 0 0 8px 6px;
 border-radius: 50%;
 box-shadow: rgba(0, 0, 0, .15) 0px 0px 0px 1px inset;
 overflow: hidden;
 `),D(`fill`,`
 display: block;
 width: 30px;
 height: 30px;
 `),D(`input`,`
 position: absolute;
 top: 0;
 left: 0;
 width: 30px;
 height: 30px;
 opacity: 0;
 z-index: 1;
 `)]),k(`color-picker-input`,`
 display: flex;
 align-items: center;
 `,[k(`input`,`
 flex-grow: 1;
 flex-basis: 0;
 `),D(`mode`,`
 width: 72px;
 text-align: center;
 `)]),k(`color-picker-control`,`
 padding: 12px;
 `),k(`color-picker-action`,`
 display: flex;
 margin-top: -4px;
 border-top: 1px solid var(--n-divider-color);
 padding: 8px 12px;
 justify-content: flex-end;
 `,[k(`button`,`margin-left: 8px;`)]),k(`color-picker`,`
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
 `,[C(`disabled`,`cursor: not-allowed`),D(`value`,`
 white-space: nowrap;
 position: relative;
 `),D(`fill`,`
 border-radius: var(--n-border-radius);
 position: absolute;
 display: flex;
 align-items: center;
 justify-content: center;
 left: 4px;
 right: 4px;
 top: 4px;
 bottom: 4px;
 `),k(`color-picker-checkboard`,`
 border-radius: var(--n-border-radius);
 `,[j(`&::after`,`
 --n-block-size: calc((var(--n-height) - 8px) / 3);
 background-size: calc(var(--n-block-size) * 2) calc(var(--n-block-size) * 2);
 background-position: 0 0, 0 var(--n-block-size), var(--n-block-size) calc(-1 * var(--n-block-size)), calc(-1 * var(--n-block-size)) 0px; 
 `)])]),k(`color-picker-swatches`,`
 display: grid;
 grid-gap: 8px;
 flex-wrap: wrap;
 position: relative;
 grid-template-columns: repeat(auto-fill, 18px);
 margin-top: 10px;
 `,[k(`color-picker-swatch`,`
 width: 18px;
 height: 18px;
 background-image: linear-gradient(45deg, #DDD 25%, #0000 25%), linear-gradient(-45deg, #DDD 25%, #0000 25%), linear-gradient(45deg, #0000 75%, #DDD 75%), linear-gradient(-45deg, #0000 75%, #DDD 75%);
 background-size: 8px 8px;
 background-position: 0px 0, 0px 4px, 4px -4px, -4px 0px;
 background-repeat: repeat;
 `,[D(`fill`,`
 position: relative;
 width: 100%;
 height: 100%;
 border-radius: 3px;
 box-shadow: rgba(0, 0, 0, .15) 0px 0px 0px 1px inset;
 cursor: pointer;
 `),j(`&:focus`,`
 outline: none;
 `,[D(`fill`,[j(`&::after`,`
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 background: inherit;
 filter: blur(2px);
 content: "";
 `)])])])])]),Ct={...pe.props,value:String,show:{type:Boolean,default:void 0},defaultShow:Boolean,defaultValue:String,modes:{type:Array,default:()=>[`rgb`,`hex`,`hsl`]},placement:{type:String,default:`bottom-start`},to:Me.propTo,showAlpha:{type:Boolean,default:!0},showPreview:Boolean,swatches:Array,disabled:{type:Boolean,default:void 0},actions:{type:Array,default:null},internalActions:Array,size:String,renderLabel:Function,onComplete:Function,onConfirm:Function,onClear:Function,"onUpdate:show":[Function,Array],onUpdateShow:[Function,Array],"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array]},wt=S({name:`ColorPicker`,inheritAttrs:!1,props:Ct,slots:Object,setup(n,{slots:r}){let i=null;function a(e){i=e}let o=null,{mergedClsPrefixRef:c,namespaceRef:u,inlineThemeDisabled:d,mergedComponentPropsRef:ne}=De(n),re=Se(n,{mergedSize:e=>{let{size:t}=n;if(t)return t;let{mergedSize:r}=e||{};return r?.value?r.value:ne?.value?.ColorPicker?.size||`medium`}}),{mergedSizeRef:ie,mergedDisabledRef:f}=re,{localeRef:h}=je(`global`),se=pe(`ColorPicker`,`-color-picker`,St,Ne,n,c);te(Ye,{themeRef:se,renderLabelRef:ae(n,`renderLabel`),colorPickerSlots:r});let v=y(n.defaultShow),b=t(ae(n,`show`),v);function le(e){let{onUpdateShow:t,"onUpdate:show":r}=n;t&&fe(t,e),r&&fe(r,e),v.value=e}let{defaultValue:ue}=n,S=y(ue===void 0?Be(n.modes,n.showAlpha):ue),C=t(ae(n,`value`),S),D=y([C.value]),k=y(0),j=x(()=>Ve(C.value)),{modes:xe}=n,F=y(Ve(C.value)||xe[0]||`rgb`);function Te(){let{modes:e}=n,{value:t}=F,r=e.findIndex(e=>e===t);~r?F.value=e[(r+1)%e.length]:F.value=`rgb`}let I,L,B,V,H,U,W,G,K=x(()=>{let{value:e}=C;if(!e)return null;switch(j.value){case`hsv`:return O(e);case`hsl`:return[I,L,B,G]=w(e),[..._e(I,L,B),G];case`rgb`:case`hex`:return[H,U,W,G]=E(e),[...Oe(H,U,W),G]}}),q=x(()=>{let{value:e}=C;if(!e)return null;switch(j.value){case`rgb`:case`hex`:return E(e);case`hsv`:return[I,L,V,G]=O(e),[...z(I,L,V),G];case`hsl`:return[I,L,B,G]=w(e),[...de(I,L,B),G]}}),Pe=x(()=>{let{value:e}=C;if(!e)return null;switch(j.value){case`hsl`:return w(e);case`hsv`:return[I,L,V,G]=O(e),[...be(I,L,V),G];case`rgb`:case`hex`:return[H,U,W,G]=E(e),[...Ee(H,U,W),G]}}),Fe=x(()=>{switch(F.value){case`rgb`:case`hex`:return q.value;case`hsv`:return K.value;case`hsl`:return Pe.value}}),Ie=y(0),Le=y(1),Re=y([0,0]);function ze(e,t){let{value:r}=K,i=Ie.value,a=r?r[3]:1;Re.value=[e,t];let{showAlpha:o}=n;switch(F.value){case`hsv`:J((o?A:he)([i,e,t,a]),`cursor`);break;case`hsl`:J((o?R:ge)([...be(i,e,t),a]),`cursor`);break;case`rgb`:J((o?M:ye)([...z(i,e,t),a]),`cursor`);break;case`hex`:J((o?T:ve)([...z(i,e,t),a]),`cursor`)}}function He(e){Ie.value=e;let{value:t}=K;if(!t)return;let[,r,i,a]=t,{showAlpha:o}=n;switch(F.value){case`hsv`:J((o?A:he)([e,r,i,a]),`cursor`);break;case`rgb`:J((o?M:ye)([...z(e,r,i),a]),`cursor`);break;case`hex`:J((o?T:ve)([...z(e,r,i),a]),`cursor`);break;case`hsl`:J((o?R:ge)([...be(e,r,i),a]),`cursor`)}}function Ue(e){switch(F.value){case`hsv`:[I,L,V]=K.value,J(A([I,L,V,e]),`cursor`);break;case`rgb`:[H,U,W]=q.value,J(M([H,U,W,e]),`cursor`);break;case`hex`:[H,U,W]=q.value,J(T([H,U,W,e]),`cursor`);break;case`hsl`:[I,L,B]=Pe.value,J(R([I,L,B,e]),`cursor`)}Le.value=e}function J(e,t){o=t===`cursor`?e:null;let{nTriggerFormChange:r,nTriggerFormInput:i}=re,{onUpdateValue:a,"onUpdate:value":s}=n;a&&fe(a,e),s&&fe(s,e),r(),i(),S.value=e}function We(e){J(e,`input`),s(Y)}function Y(e=!0){let{value:t}=C;if(t){let{nTriggerFormChange:r,nTriggerFormInput:i}=re,{onComplete:a}=n;a&&a(t);let{value:o}=D,{value:s}=k;e&&(o.splice(s+1,o.length,t),k.value=s+1),r(),i()}}function Ge(){let{value:e}=k;e-1<0||(J(D.value[e-1],`input`),Y(!1),k.value=e-1)}function Ke(){let{value:e}=k;e<0||e+1>=D.value.length||(J(D.value[e+1],`input`),Y(!1),k.value=e+1)}function X(){J(null,`input`);let{onClear:e}=n;e&&e(),le(!1)}function qe(){let{value:e}=C,{onConfirm:t}=n;t&&t(e),le(!1)}let Z=x(()=>k.value>=1),Xe=x(()=>{let{value:e}=D;return e.length>1&&k.value<e.length-1});ee(b,e=>{e||(D.value=[C.value],k.value=0)}),ce(()=>{if(!(o&&o===C.value)){let{value:e}=K;e&&(Ie.value=e[0],Le.value=e[3],Re.value=[e[1],e[2]])}o=null});let Ze=x(()=>{let{value:e}=ie,{common:{cubicBezierEaseInOut:t},self:{textColor:n,color:r,panelFontSize:i,boxShadow:a,border:o,borderRadius:s,dividerColor:c,[Ae(`height`,e)]:l,[Ae(`fontSize`,e)]:u}}=se.value;return{"--n-bezier":t,"--n-text-color":n,"--n-color":r,"--n-panel-font-size":i,"--n-font-size":u,"--n-box-shadow":a,"--n-border":o,"--n-border-radius":s,"--n-height":l,"--n-divider-color":c}}),Qe=d?me(`color-picker`,x(()=>ie.value[0]),Ze,n):void 0;function $e(){let{value:e}=q,{value:t}=Ie,{internalActions:i,modes:a,actions:o}=n,{value:s}=se,{value:u}=c;return(()=>{let c=Ce(`550d4636453f407b`);return l(),_(`div`,{class:N([`${u}-color-picker-panel`,Qe?.themeClass.value]),onDragstart:c[0]||=e=>{e.preventDefault()},style:g(d?void 0:Ze.value)},[p(`div`,{class:N(`${u}-color-picker-control`)},[(l(),m(xt,{clsPrefix:u,rgba:e,displayedHue:t,displayedSv:Re.value,onUpdateSV:ze,onComplete:Y},null,8,[`clsPrefix`,`rgba`,`displayedHue`,`displayedSv`,`onUpdateSV`,`onComplete`])),p(`div`,{class:N(`${u}-color-picker-preview`)},[p(`div`,{class:N(`${u}-color-picker-preview__sliders`)},[(l(),m(_t,{clsPrefix:u,hue:t,onUpdateHue:He,onComplete:Y},null,8,[`clsPrefix`,`hue`,`onUpdateHue`,`onComplete`])),n.showAlpha?(l(),m(Je,{key:0,clsPrefix:u,rgba:e,alpha:Le.value,onUpdateAlpha:Ue,onComplete:Y},null,8,[`clsPrefix`,`rgba`,`alpha`,`onUpdateAlpha`,`onComplete`])):P(()=>null)],2),n.showPreview?(l(),m(ft,{key:0,clsPrefix:u,mode:F.value,color:q.value&&ve(q.value),onUpdateColor:c[1]||=e=>{J(e,`input`)}},null,8,[`clsPrefix`,`mode`,`color`])):P(()=>null)],2),(l(),m(it,{clsPrefix:u,showAlpha:n.showAlpha,mode:F.value,modes:a,onUpdateMode:Te,value:C.value,valueArr:Fe.value,onUpdateValue:We},null,8,[`clsPrefix`,`showAlpha`,`mode`,`modes`,`onUpdateMode`,`value`,`valueArr`,`onUpdateValue`])),P(()=>n.swatches?.length&&(()=>{let e=Ce(`1de0b88852ebf5cb`);return l(),m(ct,{clsPrefix:u,mode:F.value,swatches:n.swatches,onUpdateColor:e[0]||=e=>{J(e,`input`)}},null,8,[`clsPrefix`,`mode`,`swatches`])})())],2),o?.length?(l(),_(`div`,{key:0,class:N(`${u}-color-picker-action`)},[P(()=>o.includes(`confirm`)&&(l(),m(ke,{size:`small`,onClick:qe,theme:s.peers.Button,themeOverrides:s.peerOverrides.Button},{default:()=>h.value.confirm},1032,[`onClick`,`theme`,`themeOverrides`]))),P(()=>o.includes(`clear`)&&(l(),m(ke,{size:`small`,onClick:X,disabled:!C.value,theme:s.peers.Button,themeOverrides:s.peerOverrides.Button},{default:()=>h.value.clear},1032,[`onClick`,`disabled`,`theme`,`themeOverrides`])))],2)):P(()=>null),r.action?(l(),_(`div`,{key:2,class:N(`${u}-color-picker-action`)},[P(()=>r.action?.())],2)):(l(),_(oe,{key:3},[i?(l(),_(`div`,{key:0,class:N(`${u}-color-picker-action`)},[P(()=>i.includes(`undo`)&&(l(),m(ke,{size:`small`,onClick:Ge,disabled:!Z.value,theme:s.peers.Button,themeOverrides:s.peerOverrides.Button},{default:()=>h.value.undo},1032,[`onClick`,`disabled`,`theme`,`themeOverrides`]))),P(()=>i.includes(`redo`)&&(l(),m(ke,{size:`small`,onClick:Ke,disabled:!Xe.value,theme:s.peers.Button,themeOverrides:s.peerOverrides.Button},{default:()=>h.value.redo},1032,[`onClick`,`disabled`,`theme`,`themeOverrides`])))],2)):P(()=>null)],64))],38)})()}return{mergedClsPrefix:c,namespace:u,hsla:Pe,rgba:q,mergedShow:b,mergedDisabled:f,isMounted:we(),adjustedTo:Me(n),mergedValue:C,handleTriggerClick(){f.value||le(!0)},setTriggerRef:a,handleClickOutside(t){if(i instanceof Element){if(i.contains(e(t)))return}else if(i&&i.$el.contains(e(t)))return;le(!1)},renderPanel:$e,cssVars:d?void 0:Ze,themeClass:Qe?.themeClass,onRender:Qe?.onRender}},render(){let{mergedClsPrefix:e,onRender:t}=this;return t?.(),l(),m(U,null,{default:()=>[(l(),m(V,null,{default:()=>{let t=c(this.$attrs,{ref:this.setTriggerRef,value:this.mergedValue,style:this.cssVars,class:this.themeClass});return t.onClick=Fe([this.mergedDisabled?void 0:this.handleTriggerClick,this.$attrs.onClick]),F(this.$slots.trigger,Pe(t,[`value`,`onClick`,`ref`]),n=>n||(l(),m(ut,c(t,{clsPrefix:e,hsla:this.hsla,disabled:this.mergedDisabled}),null,16,[`clsPrefix`,`hsla`,`disabled`])))}},1024)),(l(),m(H,{placement:this.placement,show:this.mergedShow,containerClass:this.namespace,teleportDisabled:this.adjustedTo===Me.tdkey,to:this.adjustedTo},{_:1,default:L(()=>(l(),m(I,{name:`fade-in-scale-up-transition`,appear:this.isMounted},{_:1,default:L(()=>this.mergedShow?ie(this.renderPanel(),[[q,this.handleClickOutside,void 0,{capture:!0}]]):null)},8,[`appear`])))},8,[`placement`,`show`,`containerClass`,`teleportDisabled`,`to`]))]},1024)}}),Tt=void 0,Et=(e,t)=>{let n=null,r=!0;return function(){if(!r)return;r=!1;let i=[...arguments];n&&clearTimeout(n),n=setTimeout(()=>{r=!0,e.apply(Tt,i)},t||1e3)}},Dt=S({name:`Vue3IntroStep`,props:{show:{type:Boolean,required:!0},config:{type:Object,required:!0}},emits:[`update:show`],data(){return{originalBox:{left:250,top:250,width:200,height:100},tipBoxPosition:`bottom`,currentIndex:0}},watch:{config:{deep:!0,handler(){this.currentIndex=0},immediate:!0},show(e){e?this.setBoxInfo():document.body.style.overflow=`auto`}},computed:{tipBoxStyle(){if(this.tipBoxPosition===`right`)return{left:`${this.originalBox.left+this.originalBox.width}px`,top:`${this.originalBox.top}px`};if(this.tipBoxPosition===`left`)return{right:`${window.innerWidth-this.originalBox.left}px`,top:`${this.originalBox.top}px`};if(this.tipBoxPosition===`top`)return{left:`${this.originalBox.left}px`,bottom:`${window.innerHeight-this.originalBox.top}px`};if(this.tipBoxPosition===`bottom`)return{left:`${this.originalBox.left>window.innerWidth-300?window.innerWidth-300:this.originalBox.left}px`,top:`${this.originalBox.top+this.originalBox.height}px`}}},created(){this.init()},mounted(){window.onresize=Et(()=>{this.show&&this.setBoxInfo()},100)},beforeUnmount(){window.onresize=null},methods:{async prev(){let e=!0;if(this.config.tips[this.currentIndex]&&this.config.tips[this.currentIndex].onPrev&&(e=await this.config.tips[this.currentIndex].onPrev()),!e)throw Error(`onPrev 需要 Promise.resolve(true) 才可以继续往下走`);this.setBoxInfo(this.currentIndex-1)},async next(){let e=!0;if(this.config.tips[this.currentIndex]&&this.config.tips[this.currentIndex].onNext&&(e=await this.config.tips[this.currentIndex].onNext()),!e)throw Error(`onNext 需要 Promise.resolve(true) 才可以继续往下走`);this.setBoxInfo(this.currentIndex+1)},done(){this.$emit(`update:show`,!1)},async setBoxInfo(e){try{e===void 0&&(e=this.currentIndex),this.show&&(document.body.style.overflow=`hidden`);let t=this.config.tips[e].el,n=document.querySelector(t);if(!n)throw Error(`没有找到相应的元素`);let r=n.getBoundingClientRect();this.originalBox={left:r.left,top:r.top,width:r.width,height:r.height},this.tipBoxPosition=this.config.tips[e].tipPosition,this.currentIndex=e}catch(e){throw Error(e.message)}},init(){let{tips:e}=this.config,t=null;if(e&&Array.isArray(e)){if(e.length>0){this.currentIndex=0;try{let n=document.querySelector(e[0].el);t=setInterval(()=>{n=document.querySelector(e[0].el),n&&(this.setBoxInfo(0),clearInterval(t))},0)}catch(e){throw Error(e.message)}}else throw Error(`tips数组不能为空`)}else throw Error(`config中的tips不存在或者不是数组`)}}}),Ot=e=>(re(`data-v-5d3b253c`),e=e(),u(),e),kt={key:0,id:`intro_box`},At=[Ot(()=>p(`div`,{class:`round round-flicker`},null,-1))],jt={class:`tip-content`},Mt={class:`action`,style:{justifyContent:`center`}};function Nt(e,t,n,r,i,s){return l(),m(I,{name:`custom-classes-transition`,"enter-active-class":`animate__animated animate__fadeIn animate__faster`,"leave-active-class":`animate__animated animate__fadeOut animate__faster`},{default:d(()=>[e.show?(l(),_(`div`,kt,[p(`div`,{class:`top`,style:g({height:`${e.originalBox.top}px`,backgroundColor:`rgba(0, 0, 0, ${e.config.backgroundOpacity?e.config.backgroundOpacity:.9})`})},null,4),p(`div`,{class:`content`,style:g({height:`${e.originalBox.height}px`})},[p(`div`,{class:`left`,style:g({top:`${e.originalBox.top}px`,width:`${e.originalBox.left}px`,height:`${e.originalBox.height}px`,backgroundColor:`rgba(0, 0, 0, ${e.config.backgroundOpacity?e.config.backgroundOpacity:.9})`})},null,4),p(`div`,{class:`original-box`,style:g({top:`${e.originalBox.top}px`,left:`${e.originalBox.left}px`,width:`${e.originalBox.width}px`,height:`${e.originalBox.height}px`})},At,4),p(`div`,{class:`tip-box`,style:g(e.tipBoxStyle)},[p(`div`,jt,[e.config.tips[e.currentIndex].title?(l(),_(`div`,{key:0,class:`title`,style:g({textAlign:e.config.titleStyle&&e.config.titleStyle.textAlign?e.config.titleStyle.textAlign:`center`,fontSize:e.config.titleStyle&&e.config.titleStyle.fontSize?e.config.titleStyle.fontSize:`19px`})},a(e.config.tips[e.currentIndex].title),5)):b(``,!0),p(`div`,{class:`content`,style:g({textAlign:e.config.contentStyle&&e.config.contentStyle.textAlign?e.config.contentStyle.textAlign:`center`,fontSize:e.config.contentStyle&&e.config.contentStyle.fontSize?e.config.contentStyle.fontSize:`15px`})},a(e.config.tips[e.currentIndex].content),5),p(`div`,Mt,[e.currentIndex===0?b(``,!0):o(e.$slots,`prev`,{key:0,index:e.currentIndex,tipItem:e.config.tips[e.currentIndex]},()=>[p(`div`,{class:`item prev`,onClick:t[0]||=function(){return e.prev&&e.prev(...arguments)}},`上一步`)]),e.currentIndex===e.config.tips.length-1?b(``,!0):o(e.$slots,`next`,{key:1,index:e.currentIndex,tipItem:e.config.tips[e.currentIndex]},()=>[p(`div`,{class:`item next`,onClick:t[1]||=function(){return e.next&&e.next(...arguments)}},`下一步`)]),e.currentIndex===e.config.tips.length-1?o(e.$slots,`done`,{key:2,index:e.currentIndex,tipItem:e.config.tips[e.currentIndex]},()=>[p(`div`,{class:`item done`,onClick:t[2]||=function(){return e.done&&e.done(...arguments)}},`完成`)]):o(e.$slots,`skip`,{key:3,index:e.currentIndex,tipItem:e.config.tips[e.currentIndex]},()=>[p(`div`,{class:`item skip`,onClick:t[3]||=function(){return e.done&&e.done(...arguments)}},`跳过`)])])])],4),p(`div`,{class:`right`,style:g({top:`${e.originalBox.top}px`,left:`${e.originalBox.left+e.originalBox.width}px`,width:`calc(100% - ${e.originalBox.left+e.originalBox.width}px)`,height:`${e.originalBox.height}px`,backgroundColor:`rgba(0, 0, 0, ${e.config.backgroundOpacity?e.config.backgroundOpacity:.9})`}),ref:`tip_box`},null,4)],4),p(`div`,{class:`bottom`,style:g({height:`calc(100% - ${e.originalBox.top}px - ${e.originalBox.height}px)`,backgroundColor:`rgba(0, 0, 0, ${e.config.backgroundOpacity?e.config.backgroundOpacity:.9})`})},null,4)])):b(``,!0)]),_:3})}function Pt(e,t){t===void 0&&(t={});var n=t.insertAt;if(e&&typeof document<`u`){var r=document.head||document.getElementsByTagName(`head`)[0],i=document.createElement(`style`);i.type=`text/css`,n===`top`&&r.firstChild?r.insertBefore(i,r.firstChild):r.appendChild(i),i.styleSheet?i.styleSheet.cssText=e:i.appendChild(document.createTextNode(e))}}Pt(`
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
`),Dt.render=Nt,Dt.__scopeId=`data-v-5d3b253c`;var Ft=(()=>{let e=Dt;return e.install=t=>{t.component(`Vue3IntroStep`,e)},e})(),It={__name:`BeginnerGuide`,setup(e){let t=se(null),n=se(!1),r={backgroundOpacity:.8,titleStyle:{textAlign:`left`,fontSize:`18px`},contentStyle:{textAlign:`left`,fontSize:`14px`},tips:[{el:`#toggleTheme`,tipPosition:`bottom`,title:`切换系统主题`,content:`一键开启护眼模式`},{el:`#fullscreen`,tipPosition:`bottom`,title:`全屏/退出全屏`,content:`一键开启全屏`},{el:`#theme-setting`,tipPosition:`bottom`,title:`设置主题色`,content:`调整为你喜欢的主题色`},{el:`#user-dropdown`,tipPosition:`bottom`,title:`个人中心`,content:`查看个人资料和退出系统`},{el:`#menu-collapse`,tipPosition:`bottom`,title:`展开/收起菜单`,content:`一键展开/收起菜单`},{el:`#top-tab`,tipPosition:`bottom`,title:`标签栏`,content:`鼠标滚轮滑动可调整至最佳视野`},{el:`#layout-setting`,tipPosition:`left`,title:`调整系统布局`,content:`将系统布局调整为你喜欢的样子`}]};function i(){n.value=!1}function a(){n.value=!1}function o(){t.value.next()}function s(){t.value.prev()}return(e,c)=>{let u=Le,ee=ke;return l(),_(oe,null,[f(u,{trigger:`hover`},{trigger:d(()=>[p(`i`,{class:`i-fe:beginner mr-16 cursor-pointer text-20`,onClick:c[0]||=e=>n.value=!0})]),default:d(()=>[c[2]||=h(` 操作指引 `,-1)]),_:1}),f(v(Ft),{ref_key:`myIntroStep`,ref:t,show:v(n),"onUpdate:show":c[1]||=e=>le(n)?n.value=e:null,config:r},{prev:d(({tipItem:e,index:t})=>[f(ee,{class:`mr-12`,type:`primary`,color:`#fff`,"text-color":`#fff`,ghost:``,round:``,size:`small`,onClick:n=>s(e,t)},{default:d(()=>[...c[3]||=[h(` 上一步 `,-1)]]),_:1},8,[`onClick`])]),next:d(({tipItem:e})=>[f(ee,{class:`mr-12`,type:`primary`,color:`#fff`,"text-color":`#fff`,ghost:``,round:``,size:`small`,onClick:t=>o(e)},{default:d(()=>[...c[4]||=[h(` 下一步 `,-1)]]),_:1},8,[`onClick`])]),skip:d(()=>[f(ee,{type:`primary`,color:`#fff`,"text-color":`#fff`,ghost:``,round:``,size:`small`,onClick:i},{default:d(()=>[...c[5]||=[h(` 跳过 `,-1)]]),_:1})]),done:d(()=>[f(ee,{type:`primary`,color:`#fff`,"text-color":`#fff`,ghost:``,round:``,size:`small`,onClick:a},{default:d(()=>[...c[6]||=[h(` 完成 `,-1)]]),_:1})]),_:1},8,[`show`])],64)}}},Lt={__name:`Fullscreen`,setup(e){let{isFullscreen:t,toggle:n}=W();return(e,r)=>(l(),_(`i`,{id:`fullscreen`,class:ne([`mr-16 cursor-pointer`,v(t)?`i-fe:minimize`:`i-fe:maximize`]),onClick:r[0]||=(...e)=>v(n)&&v(n)(...e)},null,2))}},Rt=G(),zt={class:`f-c-c`},Bt={id:`theme-setting`,class:`h-32 w-32`},Vt={__name:`ThemeSetting`,setup(e){let t=K(),n=Object.entries((0,Rt.getPresetColors)()).map(([,e])=>e.primary);return(e,r)=>{let i=wt,a=Le;return l(),_(`div`,zt,[f(a,{trigger:`hover`,placement:`bottom`},{trigger:d(()=>[p(`div`,Bt,[f(i,{value:v(t).primaryColor,swatches:v(n),"on-update:value":e=>v(t).setPrimaryColor(e),"render-label":()=>``},null,8,[`value`,`swatches`,`on-update:value`])])]),default:d(()=>[r[0]||=h(` 设置主题色 `,-1)]),_:1})])}}};export{Lt as n,It as r,Vt as t};