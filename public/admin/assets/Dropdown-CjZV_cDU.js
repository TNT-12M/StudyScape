import{A as e,B as t,H as n,R as r,V as i,w as a,z as o}from"./php-modules-jPihHAOi.js";import{A as s,E as c,F as l,K as u,L as d,S as f,at as p,d as m,dt as h,f as g,i as _,it as v,k as y,kt as b,m as x,ot as S,u as C,w,y as T}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as E,A as D,C as O,F as k,I as A,M as j,O as M,Qt as N,Xt as P,Yt as F,at as I,ct as L,en as R,gt as z,h as B,it as ee,on as te,ot as V,pt as H,tn as U,yt as W}from"./Button-CjW_3_5b.js";import{a as ne,i as re,n as G,o as ie,r as ae,t as oe}from"./Popover-CVRg6ES3.js";import{i as K,t as se}from"./create-C0zrQ-_N.js";import{_ as q,f as ce,h as le,s as J,v as ue,x as de}from"./light-BgIV-Nal.js";import{t as fe}from"./ChevronRight-BBrfgzkb.js";import{a as Y,t as pe,u as me}from"./light-DphdTuf2.js";import{t as he}from"./get-slot-6kXJmSMP.js";function ge(e={},t){let r=v({ctrl:!1,command:!1,win:!1,shift:!1,tab:!1}),{keydown:a,keyup:o}=e,c=e=>{switch(e.key){case`Control`:r.ctrl=!0;break;case`Meta`:r.command=!0,r.win=!0;break;case`Shift`:r.shift=!0;break;case`Tab`:r.tab=!0}a!==void 0&&Object.keys(a).forEach(t=>{if(t!==e.key)return;let n=a[t];if(typeof n==`function`)n(e);else{let{stop:t=!1,prevent:r=!1}=n;t&&e.stopPropagation(),r&&e.preventDefault(),n.handler(e)}})},l=e=>{switch(e.key){case`Control`:r.ctrl=!1;break;case`Meta`:r.command=!1,r.win=!1;break;case`Shift`:r.shift=!1;break;case`Tab`:r.tab=!1}o!==void 0&&Object.keys(o).forEach(t=>{if(t!==e.key)return;let n=o[t];if(typeof n==`function`)n(e);else{let{stop:t=!1,prevent:r=!1}=n;t&&e.stopPropagation(),r&&e.preventDefault(),n.handler(e)}})},d=()=>{(t===void 0||t.value)&&(n(`keydown`,document,c),n(`keyup`,document,l)),t!==void 0&&u(t,e=>{e?(n(`keydown`,document,c),n(`keyup`,document,l)):(i(`keydown`,document,c),i(`keyup`,document,l))})};return le()?(y(d),s(()=>{(t===void 0||t.value)&&(i(`keydown`,document,c),i(`keyup`,document,l))})):d(),p(r)}function X(e){return t=>{e.value=t?t.$el:null}}var _e=P(`radio`,`
 line-height: var(--n-label-line-height);
 outline: none;
 position: relative;
 user-select: none;
 -webkit-user-select: none;
 display: inline-flex;
 align-items: flex-start;
 flex-wrap: nowrap;
 font-size: var(--n-font-size);
 word-break: break-word;
`,[E(`checked`,[N(`dot`,`
 background-color: var(--n-color-active);
 `)]),N(`dot-wrapper`,`
 position: relative;
 flex-shrink: 0;
 flex-grow: 0;
 width: var(--n-radio-size);
 `),P(`radio-input`,`
 position: absolute;
 border: 0;
 width: 0;
 height: 0;
 opacity: 0;
 margin: 0;
 `),N(`dot`,`
 position: absolute;
 top: 50%;
 left: 0;
 transform: translateY(-50%);
 height: var(--n-radio-size);
 width: var(--n-radio-size);
 background: var(--n-color);
 box-shadow: var(--n-box-shadow);
 border-radius: 50%;
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 `,[F(`&::before`,`
 content: "";
 opacity: 0;
 position: absolute;
 left: 4px;
 top: 4px;
 height: calc(100% - 8px);
 width: calc(100% - 8px);
 border-radius: 50%;
 transform: scale(.8);
 background: var(--n-dot-color-active);
 transition: 
 opacity .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 transform .3s var(--n-bezier);
 `),E(`checked`,{boxShadow:`var(--n-box-shadow-active)`},[F(`&::before`,`
 opacity: 1;
 transform: scale(1);
 `)])]),N(`label`,`
 color: var(--n-text-color);
 padding: var(--n-label-padding);
 font-weight: var(--n-label-font-weight);
 display: inline-block;
 transition: color .3s var(--n-bezier);
 `),R(`disabled`,`
 cursor: pointer;
 `,[F(`&:hover`,[N(`dot`,{boxShadow:`var(--n-box-shadow-hover)`})]),E(`focus`,[F(`&:not(:active)`,[N(`dot`,{boxShadow:`var(--n-box-shadow-focus)`})])])]),E(`disabled`,`
 cursor: not-allowed;
 `,[N(`dot`,{boxShadow:`var(--n-box-shadow-disabled)`,backgroundColor:`var(--n-color-disabled)`},[F(`&::before`,{backgroundColor:`var(--n-dot-color-disabled)`}),E(`checked`,`
 opacity: 1;
 `)]),N(`label`,{color:`var(--n-text-color-disabled)`}),P(`radio-input`,`
 cursor: not-allowed;
 `)])]),ve={name:String,value:{type:[String,Number,Boolean],default:`on`},checked:{type:Boolean,default:void 0},defaultChecked:Boolean,disabled:{type:Boolean,default:void 0},label:String,size:String,onUpdateChecked:[Function,Array],"onUpdate:checked":[Function,Array],checkedValue:{type:Boolean,default:void 0}},ye=z(`n-radio-group`);function be(e){let n=w(ye,null),{mergedClsPrefixRef:r,mergedComponentPropsRef:i}=H(e),a=B(e,{mergedSize(t){let{size:r}=e;if(r!==void 0)return r;if(n){let{mergedSizeRef:{value:e}}=n;if(e!==void 0)return e}return t?t.mergedSize.value:i?.value?.Radio?.size||`medium`},mergedDisabled(t){return!!(e.disabled||n?.disabledRef.value||t?.disabled.value)}}),{mergedSizeRef:o,mergedDisabledRef:s}=a,c=S(null),l=S(null),u=S(e.defaultChecked),d=h(e,`checked`),f=t(d,u),p=j(()=>n?n.valueRef.value===e.value:f.value),m=j(()=>{let{name:t}=e;if(t!==void 0)return t;if(n)return n.nameRef.value}),g=S(!1);function _(){if(n){let{doUpdateValue:t}=n,{value:r}=e;D(t,r)}else{let{onUpdateChecked:t,"onUpdate:checked":n}=e,{nTriggerFormInput:r,nTriggerFormChange:i}=a;t&&D(t,!0),n&&D(n,!0),r(),i(),u.value=!0}}function v(){s.value||p.value||_()}function y(){v(),c.value&&(c.value.checked=p.value)}function b(){g.value=!1}function x(){g.value=!0}return{mergedClsPrefix:n?n.mergedClsPrefixRef:r,inputRef:c,labelRef:l,mergedName:m,mergedDisabled:s,renderSafeChecked:p,focus:g,mergedSize:o,handleRadioInputChange:y,handleRadioInputBlur:b,handleRadioInputFocus:x}}var xe=[`value`,`name`,`checked`,`disabled`,`onChange`,`onFocus`,`onBlur`],Se={...k.props,...ve},Ce=T({name:`Radio`,props:Se,setup(e){let t=be(e),n=k(`Radio`,`-radio`,_e,Y,e,t.mergedClsPrefix),r=C(()=>{let{mergedSize:{value:e}}=t,{common:{cubicBezierEaseInOut:r},self:{boxShadow:i,boxShadowActive:a,boxShadowDisabled:o,boxShadowFocus:s,boxShadowHover:c,color:l,colorDisabled:u,colorActive:d,textColor:f,textColorDisabled:p,dotColorActive:m,dotColorDisabled:h,labelPadding:g,labelLineHeight:_,labelFontWeight:v,[U(`fontSize`,e)]:y,[U(`radioSize`,e)]:b}}=n.value;return{"--n-bezier":r,"--n-label-line-height":_,"--n-label-font-weight":v,"--n-box-shadow":i,"--n-box-shadow-active":a,"--n-box-shadow-disabled":o,"--n-box-shadow-focus":s,"--n-box-shadow-hover":c,"--n-color":l,"--n-color-active":d,"--n-color-disabled":u,"--n-dot-color-active":m,"--n-dot-color-disabled":h,"--n-font-size":y,"--n-radio-size":b,"--n-text-color":f,"--n-text-color-disabled":p,"--n-label-padding":g}}),{inlineThemeDisabled:i,mergedClsPrefixRef:a,mergedRtlRef:o}=H(e),s=O(`Radio`,o,a),c=i?A(`radio`,C(()=>t.mergedSize.value[0]),r,e):void 0;return Object.assign(t,{rtlEnabled:s,cssVars:i?void 0:r,themeClass:c?.themeClass,onRender:c?.onRender})},render(){let{$slots:e,mergedClsPrefix:t,onRender:n,label:r}=this;return n?.(),(()=>{let n=ee(`f8c6901d8cd45c02`);return l(),x(`label`,{class:I([`${t}-radio`,this.themeClass,this.rtlEnabled&&`${t}-radio--rtl`,this.mergedDisabled&&`${t}-radio--disabled`,this.renderSafeChecked&&`${t}-radio--checked`,this.focus&&`${t}-radio--focus`]),style:b(this.cssVars)},[m(`div`,{class:I(`${t}-radio__dot-wrapper`)},[n[0]||=L(`\xA0`,-1),m(`div`,{class:I([`${t}-radio__dot`,this.renderSafeChecked&&`${t}-radio__dot--checked`])},null,2),m(`input`,{ref:`inputRef`,type:`radio`,class:I(`${t}-radio-input`),value:this.value,name:this.mergedName,checked:this.renderSafeChecked,disabled:this.mergedDisabled,onChange:this.handleRadioInputChange,onFocus:this.handleRadioInputFocus,onBlur:this.handleRadioInputBlur},null,42,xe)],2),L(()=>M(e.default,e=>!e&&!r?null:(l(),x(`div`,{ref:`labelRef`,class:I(`${t}-radio__label`)},[L(()=>e||r)],2))))],6)})()}}),we=P(`radio-group`,`
 display: inline-block;
 font-size: var(--n-font-size);
`,[N(`splitor`,`
 display: inline-block;
 vertical-align: bottom;
 width: 1px;
 transition:
 background-color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 background: var(--n-button-border-color);
 `,[E(`checked`,{backgroundColor:`var(--n-button-border-color-active)`}),E(`disabled`,{opacity:`var(--n-opacity-disabled)`})]),E(`button-group`,`
 white-space: nowrap;
 height: var(--n-height);
 line-height: var(--n-height);
 `,[P(`radio-button`,{height:`var(--n-height)`,lineHeight:`var(--n-height)`}),N(`splitor`,{height:`var(--n-height)`})]),P(`radio-button`,`
 vertical-align: bottom;
 outline: none;
 position: relative;
 user-select: none;
 -webkit-user-select: none;
 display: inline-block;
 box-sizing: border-box;
 padding-left: 14px;
 padding-right: 14px;
 white-space: nowrap;
 transition:
 background-color .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 border-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 background: var(--n-button-color);
 color: var(--n-button-text-color);
 border-top: 1px solid var(--n-button-border-color);
 border-bottom: 1px solid var(--n-button-border-color);
 `,[P(`radio-input`,`
 pointer-events: none;
 position: absolute;
 border: 0;
 border-radius: inherit;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 opacity: 0;
 z-index: 1;
 `),N(`state-border`,`
 z-index: 1;
 pointer-events: none;
 position: absolute;
 box-shadow: var(--n-button-box-shadow);
 transition: box-shadow .3s var(--n-bezier);
 left: -1px;
 bottom: -1px;
 right: -1px;
 top: -1px;
 `),F(`&:first-child`,`
 border-top-left-radius: var(--n-button-border-radius);
 border-bottom-left-radius: var(--n-button-border-radius);
 border-left: 1px solid var(--n-button-border-color);
 `,[N(`state-border`,`
 border-top-left-radius: var(--n-button-border-radius);
 border-bottom-left-radius: var(--n-button-border-radius);
 `)]),F(`&:last-child`,`
 border-top-right-radius: var(--n-button-border-radius);
 border-bottom-right-radius: var(--n-button-border-radius);
 border-right: 1px solid var(--n-button-border-color);
 `,[N(`state-border`,`
 border-top-right-radius: var(--n-button-border-radius);
 border-bottom-right-radius: var(--n-button-border-radius);
 `)]),R(`disabled`,`
 cursor: pointer;
 `,[F(`&:hover`,[N(`state-border`,`
 transition: box-shadow .3s var(--n-bezier);
 box-shadow: var(--n-button-box-shadow-hover);
 `),R(`checked`,{color:`var(--n-button-text-color-hover)`})]),E(`focus`,[F(`&:not(:active)`,[N(`state-border`,{boxShadow:`var(--n-button-box-shadow-focus)`})])])]),E(`checked`,`
 background: var(--n-button-color-active);
 color: var(--n-button-text-color-active);
 border-color: var(--n-button-border-color-active);
 `),E(`disabled`,`
 cursor: not-allowed;
 opacity: var(--n-opacity-disabled);
 `)])]),Te=[`onFocusin`,`onFocusout`];function Ee(e,t,n){let r=[],i=!1;for(let a=0;a<e.length;++a){let o=e[a],s=o.type?.name;s===`RadioButton`&&(i=!0);let c=o.props;if(s!==`RadioButton`){r.push(o);continue}if(a===0)r.push(o);else{let e=r[r.length-1].props,i=t===e.value,a=e.disabled,s=t===c.value,u=c.disabled,d=(i?2:0)+ +!a,f=(s?2:0)+ +!u,p={[`${n}-radio-group__splitor--disabled`]:a,[`${n}-radio-group__splitor--checked`]:i},m={[`${n}-radio-group__splitor--disabled`]:u,[`${n}-radio-group__splitor--checked`]:s},h=d<f?m:p;r.push((l(),x(`div`,{key:1,class:I([`${n}-radio-group__splitor`,h])},null,2)),o)}}return{children:r,isButtonGroup:i}}var De={...k.props,name:String,options:Array,labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},value:[String,Number,Boolean],defaultValue:{type:[String,Number,Boolean],default:null},size:String,disabled:{type:Boolean,default:void 0},"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array]},Oe=T({name:`RadioGroup`,props:De,setup(e){let n=S(null),{mergedSizeRef:r,mergedDisabledRef:i,nTriggerFormChange:a,nTriggerFormInput:o,nTriggerFormBlur:s,nTriggerFormFocus:c}=B(e),{mergedClsPrefixRef:l,inlineThemeDisabled:u,mergedRtlRef:f}=H(e),p=k(`Radio`,`-radio-group`,we,Y,e,l),m=S(e.defaultValue),g=h(e,`value`),_=t(g,m);function v(t){let{onUpdateValue:n,"onUpdate:value":r}=e;n&&D(n,t),r&&D(r,t),m.value=t,a(),o()}function y(e){let{value:t}=n;t&&(t.contains(e.relatedTarget)||c())}function b(e){let{value:t}=n;t&&(t.contains(e.relatedTarget)||s())}d(ye,{mergedClsPrefixRef:l,nameRef:h(e,`name`),valueRef:_,disabledRef:i,mergedSizeRef:r,doUpdateValue:v});let x=O(`Radio`,f,l),w=C(()=>{let{value:e}=r,{common:{cubicBezierEaseInOut:t},self:{buttonBorderColor:n,buttonBorderColorActive:i,buttonBorderRadius:a,buttonBoxShadow:o,buttonBoxShadowFocus:s,buttonBoxShadowHover:c,buttonColor:l,buttonColorActive:u,buttonTextColor:d,buttonTextColorActive:f,buttonTextColorHover:m,opacityDisabled:h,[U(`buttonHeight`,e)]:g,[U(`fontSize`,e)]:_}}=p.value;return{"--n-font-size":_,"--n-bezier":t,"--n-button-border-color":n,"--n-button-border-color-active":i,"--n-button-border-radius":a,"--n-button-box-shadow":o,"--n-button-box-shadow-focus":s,"--n-button-box-shadow-hover":c,"--n-button-color":l,"--n-button-color-active":u,"--n-button-text-color":d,"--n-button-text-color-hover":m,"--n-button-text-color-active":f,"--n-height":g,"--n-opacity-disabled":h}}),T=u?A(`radio-group`,C(()=>r.value[0]),w,e):void 0;return{selfElRef:n,rtlEnabled:x,mergedClsPrefix:l,mergedValue:_,handleFocusout:b,handleFocusin:y,cssVars:u?void 0:w,themeClass:T?.themeClass,onRender:T?.onRender}},render(){let{mergedValue:e,mergedClsPrefix:t,handleFocusin:n,handleFocusout:r}=this,{options:i,labelField:a,valueField:s}=this.$props,{children:c,isButtonGroup:u}=Ee(i?i.map(e=>{let t=e[s];return l(),g(Ce,{key:typeof t==`boolean`?`__n_${t}`:t,value:t,disabled:e.disabled,label:e[a]},null,8,[`value`,`disabled`,`label`])}):o(he(this)),e,t);return this.onRender?.(),l(),x(`div`,{onFocusin:n,onFocusout:r,ref:`selfElRef`,class:I([`${t}-radio-group`,this.rtlEnabled&&`${t}-radio-group--rtl`,this.themeClass,u&&`${t}-radio-group--button-group`]),style:b(this.cssVars)},[L(()=>c)],46,Te)}}),ke=P(`icon`,`
 height: 1em;
 width: 1em;
 line-height: 1em;
 text-align: center;
 display: inline-block;
 position: relative;
 fill: currentColor;
`,[E(`color-transition`,{transition:`color .3s var(--n-bezier)`}),E(`depth`,{color:`var(--n-color)`},[F(`svg`,{opacity:`var(--n-opacity)`,transition:`opacity .3s var(--n-bezier)`})]),F(`svg`,{height:`1em`,width:`1em`})]),Ae={...k.props,depth:[String,Number],size:[Number,String],color:String,component:[Object,Function]},je=T({_n_icon__:!0,name:`Icon`,inheritAttrs:!1,props:Ae,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=H(e),i=k(`Icon`,`-icon`,ke,pe,e,t),a=C(()=>{let{depth:t}=e,{common:{cubicBezierEaseInOut:n},self:r}=i.value;if(t!==void 0){let{color:e,[`opacity${t}Depth`]:i}=r;return{"--n-bezier":n,"--n-color":e,"--n-opacity":i}}return{"--n-bezier":n,"--n-color":``,"--n-opacity":``}}),o=n?A(`icon`,C(()=>`${e.depth||`d`}`),a,e):void 0;return{mergedClsPrefix:t,mergedStyle:C(()=>{let{size:t,color:n}=e;return{fontSize:r(t),color:n}}),cssVars:n?void 0:a,themeClass:o?.themeClass,onRender:o?.onRender}},render(){let{$parent:e,depth:t,mergedClsPrefix:n,component:r,onRender:i,themeClass:a}=this;return e?.$options?._n_icon__&&W(`icon`,"don't wrap `n-icon` inside `n-icon`"),i?.(),f(`i`,c(this.$attrs,{role:`img`,class:[`${n}-icon`,a,{[`${n}-icon--depth`]:t,[`${n}-icon--color-transition`]:t!==void 0}],style:[this.cssVars,this.mergedStyle]}),r?f(r):this.$slots.default?.())}}),Z=z(`n-dropdown-menu`),Q=z(`n-dropdown`),Me=z(`n-dropdown-option`),Ne=T({name:`DropdownDivider`,props:{clsPrefix:{type:String,required:!0}},render(){return l(),x(`div`,{class:I(`${this.clsPrefix}-dropdown-divider`)},null,2)}});function $(e,t){return e.type===`submenu`||e.type===void 0&&e[t]!==void 0}function Pe(e){return e.type===`group`}function Fe(e){return e.type===`divider`}function Ie(e){return e.type===`render`}function Le(e,t,n){if(!t)return e;let r=S(e.value),i=null;return u(e,e=>{i!==null&&window.clearTimeout(i),e===!0?n&&!n.value?r.value=!0:i=window.setTimeout(()=>{r.value=!0},t):r.value=!1}),r}var Re=T({name:`DropdownOption`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0},parentKey:{type:[String,Number],default:null},placement:{type:String,default:`right-start`},props:Object,scrollable:Boolean},setup(e){let t=w(Q),{hoverKeyRef:n,keyboardKeyRef:r,lastToggledSubmenuKeyRef:i,pendingKeyPathRef:a,activeKeyPathRef:o,animatedRef:s,mergedShowRef:c,renderLabelRef:l,renderIconRef:u,labelFieldRef:f,childrenFieldRef:p,renderOptionRef:m,nodePropsRef:h,menuPropsRef:g}=t,_=w(Me,null),v=w(Z),y=w(q),b=C(()=>e.tmNode.rawNode),x=C(()=>{let{value:t}=p;return $(e.tmNode.rawNode,t)}),T=C(()=>{let{disabled:t}=e.tmNode;return t}),E=Le(C(()=>{if(!x.value)return!1;let{key:t,disabled:o}=e.tmNode;if(o)return!1;let{value:s}=n,{value:c}=r,{value:l}=i,{value:u}=a;return s===null?c===null?l!==null&&u.includes(t):u.includes(t)&&u[u.length-1]!==t:u.includes(t)}),300,C(()=>r.value===null&&!s.value)),D=C(()=>!!_?.enteringSubmenuRef.value),O=S(!1);d(Me,{enteringSubmenuRef:O});function k(){O.value=!0}function A(){O.value=!1}function M(){let{parentKey:t,tmNode:a}=e;a.disabled||c.value&&(i.value=t,r.value=null,n.value=a.key)}function N(){let{tmNode:t}=e;t.disabled||c.value&&n.value!==t.key&&M()}function P(t){if(e.tmNode.disabled||!c.value)return;let{relatedTarget:r}=t;r&&!K({target:r},`dropdownOption`)&&!K({target:r},`scrollbarRail`)&&(n.value=null)}function F(){let{value:n}=x,{tmNode:r}=e;c.value&&!n&&!r.disabled&&(t.doSelect(r.key,r.rawNode),t.doUpdateShow(!1))}return{labelField:f,renderLabel:l,renderIcon:u,siblingHasIcon:v.showIconRef,siblingHasSubmenu:v.hasSubmenuRef,menuProps:g,popoverBody:y,animated:s,mergedShowSubmenu:C(()=>E.value&&!D.value),rawNode:b,hasSubmenu:x,pending:j(()=>{let{value:t}=a,{key:n}=e.tmNode;return t.includes(n)}),childActive:j(()=>{let{value:t}=o,{key:n}=e.tmNode,r=t.findIndex(e=>n===e);return r!==-1&&r<t.length-1}),active:j(()=>{let{value:t}=o,{key:n}=e.tmNode,r=t.findIndex(e=>n===e);return r!==-1&&r===t.length-1}),mergedDisabled:T,renderOption:m,nodeProps:h,handleClick:F,handleMouseMove:N,handleMouseEnter:M,handleMouseLeave:P,handleSubmenuBeforeEnter:k,handleSubmenuAfterEnter:A}},render(){let{animated:e,rawNode:t,mergedShowSubmenu:n,clsPrefix:r,siblingHasIcon:i,siblingHasSubmenu:a,renderLabel:o,renderIcon:s,renderOption:u,nodeProps:d,props:p,scrollable:m}=this,h=null;if(n){let e=this.menuProps?.(t,t.children);h=(t=>(l(),g(He,c({key:1},e,{clsPrefix:r,scrollable:this.scrollable,tmNodes:this.tmNode.children,parentKey:this.tmNode.key}),null,16,[`clsPrefix`,`scrollable`,`tmNodes`,`parentKey`])))(h)}let v={class:[`${r}-dropdown-option-body`,this.pending&&`${r}-dropdown-option-body--pending`,this.active&&`${r}-dropdown-option-body--active`,this.childActive&&`${r}-dropdown-option-body--child-active`,this.mergedDisabled&&`${r}-dropdown-option-body--disabled`],onMousemove:this.handleMouseMove,onMouseenter:this.handleMouseEnter,onMouseleave:this.handleMouseLeave,onClick:this.handleClick},y=d?.(t),b=(l(),x(`div`,c({class:[`${r}-dropdown-option`,y?.class],"data-dropdown-option":!0},y),[L(()=>f(`div`,c(v,p),[(l(),x(`div`,{class:I([`${r}-dropdown-option-body__prefix`,i&&`${r}-dropdown-option-body__prefix--show-icon`])},[L(()=>[s?s(t):J(t.icon)])],2)),(l(),x(`div`,{"data-dropdown-option":!0,class:I(`${r}-dropdown-option-body__label`)},[o?(l(),x(_,{key:0},[L(()=>o(t))],64)):(l(),x(_,{key:1},[L(()=>J(t[this.labelField]??t.title))],64))],2)),(l(),x(`div`,{"data-dropdown-option":!0,class:I([`${r}-dropdown-option-body__suffix`,a&&`${r}-dropdown-option-body__suffix--has-submenu`])},[this.hasSubmenu?(l(),g(je,{key:0},{_:1,default:V(()=>(l(),g(fe)))})):L(()=>null)],2))])),this.hasSubmenu?(l(),g(ie,{key:0},{default:()=>[(l(),g(ne,null,{default:()=>(l(),x(`div`,{class:I(`${r}-dropdown-offset-container`)},[(l(),g(re,{show:this.mergedShowSubmenu,placement:this.placement,to:m&&this.popoverBody||void 0,teleportDisabled:!m},{default:()=>(l(),x(`div`,{class:I(`${r}-dropdown-menu-wrapper`)},[e?(l(),g(te,{key:0,onBeforeEnter:this.handleSubmenuBeforeEnter,onAfterEnter:this.handleSubmenuAfterEnter,name:`fade-in-scale-up-transition`,appear:!0},{default:()=>h},1032,[`onBeforeEnter`,`onAfterEnter`])):(l(),x(_,{key:1},[L(()=>h)],64))],2))},1032,[`show`,`placement`,`to`,`teleportDisabled`]))],2))},1024))]},1024)):L(()=>null)],16));return u?u({node:b,option:t}):b}}),ze=T({name:`DropdownGroupHeader`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(){let{showIconRef:e,hasSubmenuRef:t}=w(Z),{renderLabelRef:n,labelFieldRef:r,nodePropsRef:i,renderOptionRef:a}=w(Q);return{labelField:r,showIcon:e,hasSubmenu:t,renderLabel:n,nodeProps:i,renderOption:a}},render(){let{clsPrefix:e,hasSubmenu:t,showIcon:n,nodeProps:r,renderLabel:i,renderOption:a}=this,{rawNode:o}=this.tmNode,s=(l(),x(`div`,c({class:`${e}-dropdown-option`},r?.(o)),[m(`div`,{class:I(`${e}-dropdown-option-body ${e}-dropdown-option-body--group`)},[m(`div`,{"data-dropdown-option":!0,class:I([`${e}-dropdown-option-body__prefix`,n&&`${e}-dropdown-option-body__prefix--show-icon`])},[L(()=>J(o.icon))],2),m(`div`,{class:I(`${e}-dropdown-option-body__label`),"data-dropdown-option":!0},[i?(l(),x(_,{key:0},[L(()=>i(o))],64)):(l(),x(_,{key:1},[L(()=>J(o.title??o[this.labelField]))],64))],2),m(`div`,{class:I([`${e}-dropdown-option-body__suffix`,t&&`${e}-dropdown-option-body__suffix--has-submenu`]),"data-dropdown-option":!0},null,2)],2)],16));return a?a({node:s,option:o}):s}}),Be=T({name:`NDropdownGroup`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0},parentKey:{type:[String,Number],default:null}},render(){let{tmNode:e,parentKey:t,clsPrefix:n}=this,{children:r}=e;return l(),x(_,null,[(l(),g(ze,{clsPrefix:n,tmNode:e,key:e.key},null,8,[`clsPrefix`,`tmNode`])),L(()=>r?.map(e=>{let{rawNode:r}=e;return r.show===!1?null:Fe(r)?f(Ne,{clsPrefix:n,key:e.key}):e.isGroup?(W(`dropdown`,"`group` node is not allowed to be put in `group` node."),null):(l(),g(Re,{clsPrefix:n,tmNode:e,parentKey:t,key:e.key},null,8,[`clsPrefix`,`tmNode`,`parentKey`]))}))],64)}}),Ve=T({name:`DropdownRenderOption`,props:{tmNode:{type:Object,required:!0}},render(){let{rawNode:{render:e,props:t}}=this.tmNode;return f(`div`,t,[e?.()])}}),He=T({name:`DropdownMenu`,props:{scrollable:Boolean,showArrow:Boolean,arrowStyle:[String,Object],clsPrefix:{type:String,required:!0},tmNodes:{type:Array,default:()=>[]},parentKey:{type:[String,Number],default:null}},setup(e){let{renderIconRef:t,childrenFieldRef:n}=w(Q);d(Z,{showIconRef:C(()=>{let n=t.value;return e.tmNodes.some(e=>{if(e.isGroup)return e.children?.some(({rawNode:e})=>n?n(e):e.icon);let{rawNode:t}=e;return n?n(t):t.icon})}),hasSubmenuRef:C(()=>{let{value:t}=n;return e.tmNodes.some(e=>{if(e.isGroup)return e.children?.some(({rawNode:e})=>$(e,t));let{rawNode:n}=e;return $(n,t)})})});let r=S(null);return d(ue,null),d(de,null),d(q,r),{bodyRef:r}},render(){let{parentKey:t,clsPrefix:n,scrollable:r}=this,i=this.tmNodes.map(e=>{let{rawNode:i}=e;return i.show===!1?null:Ie(i)?(l(),g(Ve,{tmNode:e,key:e.key},null,8,[`tmNode`])):Fe(i)?(l(),g(Ne,{clsPrefix:n,key:e.key},null,8,[`clsPrefix`])):Pe(i)?(l(),g(Be,{clsPrefix:n,tmNode:e,parentKey:t,key:e.key},null,8,[`clsPrefix`,`tmNode`,`parentKey`])):(l(),g(Re,{clsPrefix:n,tmNode:e,parentKey:t,key:e.key,props:i.props,scrollable:r},null,8,[`clsPrefix`,`tmNode`,`parentKey`,`props`,`scrollable`]))});return l(),x(`div`,{class:I([`${n}-dropdown-menu`,r&&`${n}-dropdown-menu--scrollable`]),ref:`bodyRef`},[r?(l(),g(e,{key:0,contentClass:`${n}-dropdown-menu__content`},{default:()=>i},1032,[`contentClass`])):(l(),x(_,{key:1},[L(()=>i)],64)),this.showArrow?(l(),x(_,{key:2},[L(()=>ae({clsPrefix:n,arrowStyle:this.arrowStyle,arrowClass:void 0,arrowWrapperClass:void 0,arrowWrapperStyle:void 0}))],64)):L(()=>null)],2)}}),Ue=P(`dropdown-menu`,`
 transform-origin: var(--v-transform-origin);
 background-color: var(--n-color);
 border-radius: var(--n-border-radius);
 box-shadow: var(--n-box-shadow);
 position: relative;
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
`,[a(),P(`dropdown-option`,`
 position: relative;
 `,[F(`a`,`
 text-decoration: none;
 color: inherit;
 outline: none;
 `,[F(`&::before`,`
 content: "";
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `)]),P(`dropdown-option-body`,`
 display: flex;
 cursor: pointer;
 position: relative;
 height: var(--n-option-height);
 line-height: var(--n-option-height);
 font-size: var(--n-font-size);
 color: var(--n-option-text-color);
 transition: color .3s var(--n-bezier);
 `,[F(`&::before`,`
 content: "";
 position: absolute;
 top: 0;
 bottom: 0;
 left: 4px;
 right: 4px;
 transition: background-color .3s var(--n-bezier);
 border-radius: var(--n-border-radius);
 `),R(`disabled`,[E(`pending`,`
 color: var(--n-option-text-color-hover);
 `,[N(`prefix, suffix`,`
 color: var(--n-option-text-color-hover);
 `),F(`&::before`,`background-color: var(--n-option-color-hover);`)]),E(`active`,`
 color: var(--n-option-text-color-active);
 `,[N(`prefix, suffix`,`
 color: var(--n-option-text-color-active);
 `),F(`&::before`,`background-color: var(--n-option-color-active);`)]),E(`child-active`,`
 color: var(--n-option-text-color-child-active);
 `,[N(`prefix, suffix`,`
 color: var(--n-option-text-color-child-active);
 `)])]),E(`disabled`,`
 cursor: not-allowed;
 opacity: var(--n-option-opacity-disabled);
 `),E(`group`,`
 font-size: calc(var(--n-font-size) - 1px);
 color: var(--n-group-header-text-color);
 `,[N(`prefix`,`
 width: calc(var(--n-option-prefix-width) / 2);
 `,[E(`show-icon`,`
 width: calc(var(--n-option-icon-prefix-width) / 2);
 `)])]),N(`prefix`,`
 width: var(--n-option-prefix-width);
 display: flex;
 justify-content: center;
 align-items: center;
 color: var(--n-prefix-color);
 transition: color .3s var(--n-bezier);
 z-index: 1;
 `,[E(`show-icon`,`
 width: var(--n-option-icon-prefix-width);
 `),P(`icon`,`
 font-size: var(--n-option-icon-size);
 `)]),N(`label`,`
 white-space: nowrap;
 flex: 1;
 z-index: 1;
 `),N(`suffix`,`
 box-sizing: border-box;
 flex-grow: 0;
 flex-shrink: 0;
 display: flex;
 justify-content: flex-end;
 align-items: center;
 min-width: var(--n-option-suffix-width);
 padding: 0 8px;
 transition: color .3s var(--n-bezier);
 color: var(--n-suffix-color);
 z-index: 1;
 `,[E(`has-submenu`,`
 width: var(--n-option-icon-suffix-width);
 `),P(`icon`,`
 font-size: var(--n-option-icon-size);
 `)]),P(`dropdown-menu`,`pointer-events: all;`)]),P(`dropdown-offset-container`,`
 pointer-events: none;
 position: absolute;
 left: 0;
 right: 0;
 top: -4px;
 bottom: -4px;
 `)]),P(`dropdown-divider`,`
 transition: background-color .3s var(--n-bezier);
 background-color: var(--n-divider-color);
 height: 1px;
 margin: 4px 0;
 `),P(`dropdown-menu-wrapper`,`
 transform-origin: var(--v-transform-origin);
 width: fit-content;
 `),F(`>`,[P(`scrollbar`,`
 height: inherit;
 max-height: inherit;
 `)]),R(`scrollable`,`
 padding: var(--n-padding);
 `),E(`scrollable`,[N(`content`,`
 padding: var(--n-padding);
 `)])]),We={animated:{type:Boolean,default:!0},keyboard:{type:Boolean,default:!0},size:String,inverted:Boolean,placement:{type:String,default:`bottom`},onSelect:[Function,Array],options:{type:Array,default:()=>[]},menuProps:Function,showArrow:Boolean,renderLabel:Function,renderIcon:Function,renderOption:Function,nodeProps:Function,labelField:{type:String,default:`label`},keyField:{type:String,default:`key`},childrenField:{type:String,default:`children`},value:[String,Number]},Ge=Object.keys(G),Ke={...G,...We,...k.props},qe=T({name:`Dropdown`,inheritAttrs:!1,props:Ke,setup(e){let n=S(!1),r=t(h(e,`show`),n),i=C(()=>{let{keyField:t,childrenField:n}=e;return se(e.options,{getKey(e){return e[t]},getDisabled(e){return e.disabled===!0},getIgnored(e){return e.type===`divider`||e.type===`render`},getChildren(e){return e[n]}})}),a=C(()=>i.value.treeNodes),o=S(null),s=S(null),c=S(null),l=C(()=>o.value??s.value??c.value??null),f=C(()=>i.value.getPath(l.value).keyPath),p=C(()=>i.value.getPath(e.value).keyPath),m=j(()=>e.keyboard&&r.value);ge({keydown:{ArrowUp:{prevent:!0,handler:N},ArrowRight:{prevent:!0,handler:M},ArrowDown:{prevent:!0,handler:P},ArrowLeft:{prevent:!0,handler:O},Enter:{prevent:!0,handler:F},Escape:E}},m);let{mergedClsPrefixRef:g,inlineThemeDisabled:_,mergedComponentPropsRef:v}=H(e),y=C(()=>e.size||v?.value?.Dropdown?.size||`medium`),b=k(`Dropdown`,`-dropdown`,Ue,me,e,g);d(Q,{labelFieldRef:h(e,`labelField`),childrenFieldRef:h(e,`childrenField`),renderLabelRef:h(e,`renderLabel`),renderIconRef:h(e,`renderIcon`),hoverKeyRef:o,keyboardKeyRef:s,lastToggledSubmenuKeyRef:c,pendingKeyPathRef:f,activeKeyPathRef:p,animatedRef:h(e,`animated`),mergedShowRef:r,nodePropsRef:h(e,`nodeProps`),renderOptionRef:h(e,`renderOption`),menuPropsRef:h(e,`menuProps`),doSelect:x,doUpdateShow:w}),u(r,t=>{!e.animated&&!t&&T()});function x(t,n){let{onSelect:r}=e;r&&D(r,t,n)}function w(t){let{"onUpdate:show":r,onUpdateShow:i}=e;r&&D(r,t),i&&D(i,t),n.value=t}function T(){o.value=null,s.value=null,c.value=null}function E(){w(!1)}function O(){L(`left`)}function M(){L(`right`)}function N(){L(`up`)}function P(){L(`down`)}function F(){let e=I();e?.isLeaf&&r.value&&(x(e.key,e.rawNode),w(!1))}function I(){let{value:e}=i,{value:t}=l;return!e||t===null?null:e.getNode(t)??null}function L(e){let{value:t}=l,{value:{getFirstAvailableNode:n}}=i,r=null;if(t===null){let e=n();e!==null&&(r=e.key)}else{let t=I();if(t){let n;switch(e){case`down`:n=t.getNext();break;case`up`:n=t.getPrev();break;case`right`:n=t.getChild();break;case`left`:n=t.getParent()}n&&(r=n.key)}}r!==null&&(o.value=null,s.value=r)}let R=C(()=>{let{inverted:t}=e,n=y.value,{common:{cubicBezierEaseInOut:r},self:i}=b.value,{padding:a,dividerColor:o,borderRadius:s,optionOpacityDisabled:c,[U(`optionIconSuffixWidth`,n)]:l,[U(`optionSuffixWidth`,n)]:u,[U(`optionIconPrefixWidth`,n)]:d,[U(`optionPrefixWidth`,n)]:f,[U(`fontSize`,n)]:p,[U(`optionHeight`,n)]:m,[U(`optionIconSize`,n)]:h}=i,g={"--n-bezier":r,"--n-font-size":p,"--n-padding":a,"--n-border-radius":s,"--n-option-height":m,"--n-option-prefix-width":f,"--n-option-icon-prefix-width":d,"--n-option-suffix-width":u,"--n-option-icon-suffix-width":l,"--n-option-icon-size":h,"--n-divider-color":o,"--n-option-opacity-disabled":c};return t?(g[`--n-color`]=i.colorInverted,g[`--n-option-color-hover`]=i.optionColorHoverInverted,g[`--n-option-color-active`]=i.optionColorActiveInverted,g[`--n-option-text-color`]=i.optionTextColorInverted,g[`--n-option-text-color-hover`]=i.optionTextColorHoverInverted,g[`--n-option-text-color-active`]=i.optionTextColorActiveInverted,g[`--n-option-text-color-child-active`]=i.optionTextColorChildActiveInverted,g[`--n-prefix-color`]=i.prefixColorInverted,g[`--n-suffix-color`]=i.suffixColorInverted,g[`--n-group-header-text-color`]=i.groupHeaderTextColorInverted):(g[`--n-color`]=i.color,g[`--n-option-color-hover`]=i.optionColorHover,g[`--n-option-color-active`]=i.optionColorActive,g[`--n-option-text-color`]=i.optionTextColor,g[`--n-option-text-color-hover`]=i.optionTextColorHover,g[`--n-option-text-color-active`]=i.optionTextColorActive,g[`--n-option-text-color-child-active`]=i.optionTextColorChildActive,g[`--n-prefix-color`]=i.prefixColor,g[`--n-suffix-color`]=i.suffixColor,g[`--n-group-header-text-color`]=i.groupHeaderTextColor),g}),z=_?A(`dropdown`,C(()=>`${y.value[0]}${e.inverted?`i`:``}`),R,e):void 0;return{mergedClsPrefix:g,mergedTheme:b,mergedSize:y,tmNodes:a,mergedShow:r,handleAfterLeave:()=>{e.animated&&T()},doUpdateShow:w,cssVars:_?void 0:R,themeClass:z?.themeClass,onRender:z?.onRender}},render(){let e=(e,t,n,r,i)=>{let{mergedClsPrefix:a,menuProps:o}=this;this.onRender?.();let s=o?.(void 0,this.tmNodes.map(e=>e.rawNode))||{},l={ref:X(t),class:[e,`${a}-dropdown`,`${a}-dropdown--${this.mergedSize}-size`,this.themeClass],clsPrefix:a,tmNodes:this.tmNodes,style:[...n,this.cssVars],showArrow:this.showArrow,arrowStyle:this.arrowStyle,scrollable:this.scrollable,onMouseenter:r,onMouseleave:i};return f(He,c(this.$attrs,l,s))},{mergedTheme:t}=this,n={show:this.mergedShow,theme:t.peers.Popover,themeOverrides:t.peerOverrides.Popover,internalOnAfterLeave:this.handleAfterLeave,internalRenderBody:e,onUpdateShow:this.doUpdateShow,"onUpdate:show":void 0};return l(),g(oe,ce(this.$props,Ge,n),{_:1,trigger:V(()=>this.$slots.default?.())},16)}});export{be as a,ve as i,Oe as n,X as o,Ce as r,qe as t};