import{D as e,H as t,N as n,U as r,V as i}from"./php-modules-GekPVnxA.js";import{E as a,L as o,M as s,Nt as c,O as l,Y as u,a as d,ct as f,d as p,f as m,h,ht as g,j as _,lt as v,p as y,ut as b,w as x,x as S,z as C}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as w,A as T,C as E,F as D,I as O,M as k,O as A,Qt as j,Xt as M,Yt as N,at as P,ct as F,en as I,gt as L,h as R,it as ee,on as te,ot as z,pt as B,tn as V,yt as H}from"./Button-BtFx7EYM.js";import{a as ne,i as re,n as U,o as ie,r as ae,t as oe}from"./Popover-BmmAdlen.js";import{n as se}from"./AppPage-BwDOpiOS.js";import{i as W,t as ce}from"./create-BBG9Dvpd.js";import{_ as G,f as le,h as ue,s as K,v as de,x as fe}from"./light-B0o7KsWP.js";import{n as q,t as pe}from"./format-length-BCoLE4J2.js";import{t as me}from"./ChevronRight-C8utyV0C.js";import{a as J,t as he,u as ge}from"./light-aa5lzvID.js";import{t as _e}from"./create-ref-setter-AIudcg5Z.js";function ve(e={},n){let i=f({ctrl:!1,command:!1,win:!1,shift:!1,tab:!1}),{keydown:a,keyup:o}=e,c=e=>{switch(e.key){case`Control`:i.ctrl=!0;break;case`Meta`:i.command=!0,i.win=!0;break;case`Shift`:i.shift=!0;break;case`Tab`:i.tab=!0}a!==void 0&&Object.keys(a).forEach(t=>{if(t!==e.key)return;let n=a[t];if(typeof n==`function`)n(e);else{let{stop:t=!1,prevent:r=!1}=n;t&&e.stopPropagation(),r&&e.preventDefault(),n.handler(e)}})},l=e=>{switch(e.key){case`Control`:i.ctrl=!1;break;case`Meta`:i.command=!1,i.win=!1;break;case`Shift`:i.shift=!1;break;case`Tab`:i.tab=!1}o!==void 0&&Object.keys(o).forEach(t=>{if(t!==e.key)return;let n=o[t];if(typeof n==`function`)n(e);else{let{stop:t=!1,prevent:r=!1}=n;t&&e.stopPropagation(),r&&e.preventDefault(),n.handler(e)}})},d=()=>{(n===void 0||n.value)&&(r(`keydown`,document,c),r(`keyup`,document,l)),n!==void 0&&u(n,e=>{e?(r(`keydown`,document,c),r(`keyup`,document,l)):(t(`keydown`,document,c),t(`keyup`,document,l))})};return ue()?(_(d),s(()=>{(n===void 0||n.value)&&(t(`keydown`,document,c),t(`keyup`,document,l))})):d(),v(i)}var ye=M(`radio`,`
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
`,[w(`checked`,[j(`dot`,`
 background-color: var(--n-color-active);
 `)]),j(`dot-wrapper`,`
 position: relative;
 flex-shrink: 0;
 flex-grow: 0;
 width: var(--n-radio-size);
 `),M(`radio-input`,`
 position: absolute;
 border: 0;
 width: 0;
 height: 0;
 opacity: 0;
 margin: 0;
 `),j(`dot`,`
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
 `,[N(`&::before`,`
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
 `),w(`checked`,{boxShadow:`var(--n-box-shadow-active)`},[N(`&::before`,`
 opacity: 1;
 transform: scale(1);
 `)])]),j(`label`,`
 color: var(--n-text-color);
 padding: var(--n-label-padding);
 font-weight: var(--n-label-font-weight);
 display: inline-block;
 transition: color .3s var(--n-bezier);
 `),I(`disabled`,`
 cursor: pointer;
 `,[N(`&:hover`,[j(`dot`,{boxShadow:`var(--n-box-shadow-hover)`})]),w(`focus`,[N(`&:not(:active)`,[j(`dot`,{boxShadow:`var(--n-box-shadow-focus)`})])])]),w(`disabled`,`
 cursor: not-allowed;
 `,[j(`dot`,{boxShadow:`var(--n-box-shadow-disabled)`,backgroundColor:`var(--n-color-disabled)`},[N(`&::before`,{backgroundColor:`var(--n-dot-color-disabled)`}),w(`checked`,`
 opacity: 1;
 `)]),j(`label`,{color:`var(--n-text-color-disabled)`}),M(`radio-input`,`
 cursor: not-allowed;
 `)])]),Y={name:String,value:{type:[String,Number,Boolean],default:`on`},checked:{type:Boolean,default:void 0},defaultChecked:Boolean,disabled:{type:Boolean,default:void 0},label:String,size:String,onUpdateChecked:[Function,Array],"onUpdate:checked":[Function,Array],checkedValue:{type:Boolean,default:void 0}},X=L(`n-radio-group`);function be(e){let t=a(X,null),{mergedClsPrefixRef:n,mergedComponentPropsRef:r}=B(e),i=R(e,{mergedSize(n){let{size:i}=e;if(i!==void 0)return i;if(t){let{mergedSizeRef:{value:e}}=t;if(e!==void 0)return e}return n?n.mergedSize.value:r?.value?.Radio?.size||`medium`},mergedDisabled(n){return!!(e.disabled||t?.disabledRef.value||n?.disabled.value)}}),{mergedSizeRef:o,mergedDisabledRef:s}=i,c=b(null),l=b(null),u=b(e.defaultChecked),d=g(e,`checked`),f=q(d,u),p=k(()=>t?t.valueRef.value===e.value:f.value),m=k(()=>{let{name:n}=e;if(n!==void 0)return n;if(t)return t.nameRef.value}),h=b(!1);function _(){if(t){let{doUpdateValue:n}=t,{value:r}=e;T(n,r)}else{let{onUpdateChecked:t,"onUpdate:checked":n}=e,{nTriggerFormInput:r,nTriggerFormChange:a}=i;t&&T(t,!0),n&&T(n,!0),r(),a(),u.value=!0}}function v(){s.value||p.value||_()}function y(){v(),c.value&&(c.value.checked=p.value)}function x(){h.value=!1}function S(){h.value=!0}return{mergedClsPrefix:t?t.mergedClsPrefixRef:n,inputRef:c,labelRef:l,mergedName:m,mergedDisabled:s,renderSafeChecked:p,focus:h,mergedSize:o,handleRadioInputChange:y,handleRadioInputBlur:x,handleRadioInputFocus:S}}var xe=[`value`,`name`,`checked`,`disabled`,`onChange`,`onFocus`,`onBlur`],Se={...D.props,...Y},Ce=S({name:`Radio`,props:Se,setup(e){let t=be(e),n=D(`Radio`,`-radio`,ye,J,e,t.mergedClsPrefix),r=p(()=>{let{mergedSize:{value:e}}=t,{common:{cubicBezierEaseInOut:r},self:{boxShadow:i,boxShadowActive:a,boxShadowDisabled:o,boxShadowFocus:s,boxShadowHover:c,color:l,colorDisabled:u,colorActive:d,textColor:f,textColorDisabled:p,dotColorActive:m,dotColorDisabled:h,labelPadding:g,labelLineHeight:_,labelFontWeight:v,[V(`fontSize`,e)]:y,[V(`radioSize`,e)]:b}}=n.value;return{"--n-bezier":r,"--n-label-line-height":_,"--n-label-font-weight":v,"--n-box-shadow":i,"--n-box-shadow-active":a,"--n-box-shadow-disabled":o,"--n-box-shadow-focus":s,"--n-box-shadow-hover":c,"--n-color":l,"--n-color-active":d,"--n-color-disabled":u,"--n-dot-color-active":m,"--n-dot-color-disabled":h,"--n-font-size":y,"--n-radio-size":b,"--n-text-color":f,"--n-text-color-disabled":p,"--n-label-padding":g}}),{inlineThemeDisabled:i,mergedClsPrefixRef:a,mergedRtlRef:o}=B(e),s=E(`Radio`,o,a),c=i?O(`radio`,p(()=>t.mergedSize.value[0]),r,e):void 0;return Object.assign(t,{rtlEnabled:s,cssVars:i?void 0:r,themeClass:c?.themeClass,onRender:c?.onRender})},render(){let{$slots:e,mergedClsPrefix:t,onRender:n,label:r}=this;return n?.(),(()=>{let n=ee(`f8c6901d8cd45c02`);return o(),h(`label`,{class:P([`${t}-radio`,this.themeClass,this.rtlEnabled&&`${t}-radio--rtl`,this.mergedDisabled&&`${t}-radio--disabled`,this.renderSafeChecked&&`${t}-radio--checked`,this.focus&&`${t}-radio--focus`]),style:c(this.cssVars)},[m(`div`,{class:P(`${t}-radio__dot-wrapper`)},[n[0]||=F(`\xA0`,-1),m(`div`,{class:P([`${t}-radio__dot`,this.renderSafeChecked&&`${t}-radio__dot--checked`])},null,2),m(`input`,{ref:`inputRef`,type:`radio`,class:P(`${t}-radio-input`),value:this.value,name:this.mergedName,checked:this.renderSafeChecked,disabled:this.mergedDisabled,onChange:this.handleRadioInputChange,onFocus:this.handleRadioInputFocus,onBlur:this.handleRadioInputBlur},null,42,xe)],2),F(()=>A(e.default,e=>!e&&!r?null:(o(),h(`div`,{ref:`labelRef`,class:P(`${t}-radio__label`)},[F(()=>e||r)],2))))],6)})()}}),we=M(`radio-group`,`
 display: inline-block;
 font-size: var(--n-font-size);
`,[j(`splitor`,`
 display: inline-block;
 vertical-align: bottom;
 width: 1px;
 transition:
 background-color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 background: var(--n-button-border-color);
 `,[w(`checked`,{backgroundColor:`var(--n-button-border-color-active)`}),w(`disabled`,{opacity:`var(--n-opacity-disabled)`})]),w(`button-group`,`
 white-space: nowrap;
 height: var(--n-height);
 line-height: var(--n-height);
 `,[M(`radio-button`,{height:`var(--n-height)`,lineHeight:`var(--n-height)`}),j(`splitor`,{height:`var(--n-height)`})]),M(`radio-button`,`
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
 `,[M(`radio-input`,`
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
 `),j(`state-border`,`
 z-index: 1;
 pointer-events: none;
 position: absolute;
 box-shadow: var(--n-button-box-shadow);
 transition: box-shadow .3s var(--n-bezier);
 left: -1px;
 bottom: -1px;
 right: -1px;
 top: -1px;
 `),N(`&:first-child`,`
 border-top-left-radius: var(--n-button-border-radius);
 border-bottom-left-radius: var(--n-button-border-radius);
 border-left: 1px solid var(--n-button-border-color);
 `,[j(`state-border`,`
 border-top-left-radius: var(--n-button-border-radius);
 border-bottom-left-radius: var(--n-button-border-radius);
 `)]),N(`&:last-child`,`
 border-top-right-radius: var(--n-button-border-radius);
 border-bottom-right-radius: var(--n-button-border-radius);
 border-right: 1px solid var(--n-button-border-color);
 `,[j(`state-border`,`
 border-top-right-radius: var(--n-button-border-radius);
 border-bottom-right-radius: var(--n-button-border-radius);
 `)]),I(`disabled`,`
 cursor: pointer;
 `,[N(`&:hover`,[j(`state-border`,`
 transition: box-shadow .3s var(--n-bezier);
 box-shadow: var(--n-button-box-shadow-hover);
 `),I(`checked`,{color:`var(--n-button-text-color-hover)`})]),w(`focus`,[N(`&:not(:active)`,[j(`state-border`,{boxShadow:`var(--n-button-box-shadow-focus)`})])])]),w(`checked`,`
 background: var(--n-button-color-active);
 color: var(--n-button-text-color-active);
 border-color: var(--n-button-border-color-active);
 `),w(`disabled`,`
 cursor: not-allowed;
 opacity: var(--n-opacity-disabled);
 `)])]),Te=[`onFocusin`,`onFocusout`];function Ee(e,t,n){let r=[],i=!1;for(let a=0;a<e.length;++a){let s=e[a],c=s.type?.name;c===`RadioButton`&&(i=!0);let l=s.props;if(c!==`RadioButton`){r.push(s);continue}if(a===0)r.push(s);else{let e=r[r.length-1].props,i=t===e.value,a=e.disabled,c=t===l.value,u=l.disabled,d=(i?2:0)+ +!a,f=(c?2:0)+ +!u,p={[`${n}-radio-group__splitor--disabled`]:a,[`${n}-radio-group__splitor--checked`]:i},m={[`${n}-radio-group__splitor--disabled`]:u,[`${n}-radio-group__splitor--checked`]:c},g=d<f?m:p;r.push((o(),h(`div`,{key:1,class:P([`${n}-radio-group__splitor`,g])},null,2)),s)}}return{children:r,isButtonGroup:i}}var De={...D.props,name:String,options:Array,labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},value:[String,Number,Boolean],defaultValue:{type:[String,Number,Boolean],default:null},size:String,disabled:{type:Boolean,default:void 0},"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array]},Oe=S({name:`RadioGroup`,props:De,setup(e){let t=b(null),{mergedSizeRef:n,mergedDisabledRef:r,nTriggerFormChange:i,nTriggerFormInput:a,nTriggerFormBlur:o,nTriggerFormFocus:s}=R(e),{mergedClsPrefixRef:c,inlineThemeDisabled:l,mergedRtlRef:u}=B(e),d=D(`Radio`,`-radio-group`,we,J,e,c),f=b(e.defaultValue),m=g(e,`value`),h=q(m,f);function _(t){let{onUpdateValue:n,"onUpdate:value":r}=e;n&&T(n,t),r&&T(r,t),f.value=t,i(),a()}function v(e){let{value:n}=t;n&&(n.contains(e.relatedTarget)||s())}function y(e){let{value:n}=t;n&&(n.contains(e.relatedTarget)||o())}C(X,{mergedClsPrefixRef:c,nameRef:g(e,`name`),valueRef:h,disabledRef:r,mergedSizeRef:n,doUpdateValue:_});let x=E(`Radio`,u,c),S=p(()=>{let{value:e}=n,{common:{cubicBezierEaseInOut:t},self:{buttonBorderColor:r,buttonBorderColorActive:i,buttonBorderRadius:a,buttonBoxShadow:o,buttonBoxShadowFocus:s,buttonBoxShadowHover:c,buttonColor:l,buttonColorActive:u,buttonTextColor:f,buttonTextColorActive:p,buttonTextColorHover:m,opacityDisabled:h,[V(`buttonHeight`,e)]:g,[V(`fontSize`,e)]:_}}=d.value;return{"--n-font-size":_,"--n-bezier":t,"--n-button-border-color":r,"--n-button-border-color-active":i,"--n-button-border-radius":a,"--n-button-box-shadow":o,"--n-button-box-shadow-focus":s,"--n-button-box-shadow-hover":c,"--n-button-color":l,"--n-button-color-active":u,"--n-button-text-color":f,"--n-button-text-color-hover":m,"--n-button-text-color-active":p,"--n-height":g,"--n-opacity-disabled":h}}),w=l?O(`radio-group`,p(()=>n.value[0]),S,e):void 0;return{selfElRef:t,rtlEnabled:x,mergedClsPrefix:c,mergedValue:h,handleFocusout:y,handleFocusin:v,cssVars:l?void 0:S,themeClass:w?.themeClass,onRender:w?.onRender}},render(){let{mergedValue:e,mergedClsPrefix:t,handleFocusin:n,handleFocusout:r}=this,{options:a,labelField:s,valueField:l}=this.$props,{children:u,isButtonGroup:d}=Ee(a?a.map(e=>{let t=e[l];return o(),y(Ce,{key:typeof t==`boolean`?`__n_${t}`:t,value:t,disabled:e.disabled,label:e[s]},null,8,[`value`,`disabled`,`label`])}):i(se(this)),e,t);return this.onRender?.(),o(),h(`div`,{onFocusin:n,onFocusout:r,ref:`selfElRef`,class:P([`${t}-radio-group`,this.rtlEnabled&&`${t}-radio-group--rtl`,this.themeClass,d&&`${t}-radio-group--button-group`]),style:c(this.cssVars)},[F(()=>u)],46,Te)}}),ke=M(`icon`,`
 height: 1em;
 width: 1em;
 line-height: 1em;
 text-align: center;
 display: inline-block;
 position: relative;
 fill: currentColor;
`,[w(`color-transition`,{transition:`color .3s var(--n-bezier)`}),w(`depth`,{color:`var(--n-color)`},[N(`svg`,{opacity:`var(--n-opacity)`,transition:`opacity .3s var(--n-bezier)`})]),N(`svg`,{height:`1em`,width:`1em`})]),Ae={...D.props,depth:[String,Number],size:[Number,String],color:String,component:[Object,Function]},je=S({_n_icon__:!0,name:`Icon`,inheritAttrs:!1,props:Ae,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=B(e),r=D(`Icon`,`-icon`,ke,he,e,t),i=p(()=>{let{depth:t}=e,{common:{cubicBezierEaseInOut:n},self:i}=r.value;if(t!==void 0){let{color:e,[`opacity${t}Depth`]:r}=i;return{"--n-bezier":n,"--n-color":e,"--n-opacity":r}}return{"--n-bezier":n,"--n-color":``,"--n-opacity":``}}),a=n?O(`icon`,p(()=>`${e.depth||`d`}`),i,e):void 0;return{mergedClsPrefix:t,mergedStyle:p(()=>{let{size:t,color:n}=e;return{fontSize:pe(t),color:n}}),cssVars:n?void 0:i,themeClass:a?.themeClass,onRender:a?.onRender}},render(){let{$parent:e,depth:t,mergedClsPrefix:n,component:r,onRender:i,themeClass:a}=this;return e?.$options?._n_icon__&&H(`icon`,"don't wrap `n-icon` inside `n-icon`"),i?.(),x(`i`,l(this.$attrs,{role:`img`,class:[`${n}-icon`,a,{[`${n}-icon--depth`]:t,[`${n}-icon--color-transition`]:t!==void 0}],style:[this.cssVars,this.mergedStyle]}),r?x(r):this.$slots.default?.())}}),Z=L(`n-dropdown-menu`),Q=L(`n-dropdown`),Me=L(`n-dropdown-option`),Ne=S({name:`DropdownDivider`,props:{clsPrefix:{type:String,required:!0}},render(){return o(),h(`div`,{class:P(`${this.clsPrefix}-dropdown-divider`)},null,2)}});function $(e,t){return e.type===`submenu`||e.type===void 0&&e[t]!==void 0}function Pe(e){return e.type===`group`}function Fe(e){return e.type===`divider`}function Ie(e){return e.type===`render`}function Le(e,t,n){if(!t)return e;let r=b(e.value),i=null;return u(e,e=>{i!==null&&window.clearTimeout(i),e===!0?n&&!n.value?r.value=!0:i=window.setTimeout(()=>{r.value=!0},t):r.value=!1}),r}var Re=S({name:`DropdownOption`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0},parentKey:{type:[String,Number],default:null},placement:{type:String,default:`right-start`},props:Object,scrollable:Boolean},setup(e){let t=a(Q),{hoverKeyRef:n,keyboardKeyRef:r,lastToggledSubmenuKeyRef:i,pendingKeyPathRef:o,activeKeyPathRef:s,animatedRef:c,mergedShowRef:l,renderLabelRef:u,renderIconRef:d,labelFieldRef:f,childrenFieldRef:m,renderOptionRef:h,nodePropsRef:g,menuPropsRef:_}=t,v=a(Me,null),y=a(Z),x=a(G),S=p(()=>e.tmNode.rawNode),w=p(()=>{let{value:t}=m;return $(e.tmNode.rawNode,t)}),T=p(()=>{let{disabled:t}=e.tmNode;return t}),E=Le(p(()=>{if(!w.value)return!1;let{key:t,disabled:a}=e.tmNode;if(a)return!1;let{value:s}=n,{value:c}=r,{value:l}=i,{value:u}=o;return s===null?c===null?l!==null&&u.includes(t):u.includes(t)&&u[u.length-1]!==t:u.includes(t)}),300,p(()=>r.value===null&&!c.value)),D=p(()=>!!v?.enteringSubmenuRef.value),O=b(!1);C(Me,{enteringSubmenuRef:O});function A(){O.value=!0}function j(){O.value=!1}function M(){let{parentKey:t,tmNode:a}=e;a.disabled||l.value&&(i.value=t,r.value=null,n.value=a.key)}function N(){let{tmNode:t}=e;t.disabled||l.value&&n.value!==t.key&&M()}function P(t){if(e.tmNode.disabled||!l.value)return;let{relatedTarget:r}=t;r&&!W({target:r},`dropdownOption`)&&!W({target:r},`scrollbarRail`)&&(n.value=null)}function F(){let{value:n}=w,{tmNode:r}=e;l.value&&!n&&!r.disabled&&(t.doSelect(r.key,r.rawNode),t.doUpdateShow(!1))}return{labelField:f,renderLabel:u,renderIcon:d,siblingHasIcon:y.showIconRef,siblingHasSubmenu:y.hasSubmenuRef,menuProps:_,popoverBody:x,animated:c,mergedShowSubmenu:p(()=>E.value&&!D.value),rawNode:S,hasSubmenu:w,pending:k(()=>{let{value:t}=o,{key:n}=e.tmNode;return t.includes(n)}),childActive:k(()=>{let{value:t}=s,{key:n}=e.tmNode,r=t.findIndex(e=>n===e);return r!==-1&&r<t.length-1}),active:k(()=>{let{value:t}=s,{key:n}=e.tmNode,r=t.findIndex(e=>n===e);return r!==-1&&r===t.length-1}),mergedDisabled:T,renderOption:h,nodeProps:g,handleClick:F,handleMouseMove:N,handleMouseEnter:M,handleMouseLeave:P,handleSubmenuBeforeEnter:A,handleSubmenuAfterEnter:j}},render(){let{animated:e,rawNode:t,mergedShowSubmenu:n,clsPrefix:r,siblingHasIcon:i,siblingHasSubmenu:a,renderLabel:s,renderIcon:c,renderOption:u,nodeProps:f,props:p,scrollable:m}=this,g=null;if(n){let e=this.menuProps?.(t,t.children);g=(t=>(o(),y(He,l({key:1},e,{clsPrefix:r,scrollable:this.scrollable,tmNodes:this.tmNode.children,parentKey:this.tmNode.key}),null,16,[`clsPrefix`,`scrollable`,`tmNodes`,`parentKey`])))(g)}let _={class:[`${r}-dropdown-option-body`,this.pending&&`${r}-dropdown-option-body--pending`,this.active&&`${r}-dropdown-option-body--active`,this.childActive&&`${r}-dropdown-option-body--child-active`,this.mergedDisabled&&`${r}-dropdown-option-body--disabled`],onMousemove:this.handleMouseMove,onMouseenter:this.handleMouseEnter,onMouseleave:this.handleMouseLeave,onClick:this.handleClick},v=f?.(t),b=(o(),h(`div`,l({class:[`${r}-dropdown-option`,v?.class],"data-dropdown-option":!0},v),[F(()=>x(`div`,l(_,p),[(o(),h(`div`,{class:P([`${r}-dropdown-option-body__prefix`,i&&`${r}-dropdown-option-body__prefix--show-icon`])},[F(()=>[c?c(t):K(t.icon)])],2)),(o(),h(`div`,{"data-dropdown-option":!0,class:P(`${r}-dropdown-option-body__label`)},[s?(o(),h(d,{key:0},[F(()=>s(t))],64)):(o(),h(d,{key:1},[F(()=>K(t[this.labelField]??t.title))],64))],2)),(o(),h(`div`,{"data-dropdown-option":!0,class:P([`${r}-dropdown-option-body__suffix`,a&&`${r}-dropdown-option-body__suffix--has-submenu`])},[this.hasSubmenu?(o(),y(je,{key:0},{_:1,default:z(()=>(o(),y(me)))})):F(()=>null)],2))])),this.hasSubmenu?(o(),y(ie,{key:0},{default:()=>[(o(),y(ne,null,{default:()=>(o(),h(`div`,{class:P(`${r}-dropdown-offset-container`)},[(o(),y(re,{show:this.mergedShowSubmenu,placement:this.placement,to:m&&this.popoverBody||void 0,teleportDisabled:!m},{default:()=>(o(),h(`div`,{class:P(`${r}-dropdown-menu-wrapper`)},[e?(o(),y(te,{key:0,onBeforeEnter:this.handleSubmenuBeforeEnter,onAfterEnter:this.handleSubmenuAfterEnter,name:`fade-in-scale-up-transition`,appear:!0},{default:()=>g},1032,[`onBeforeEnter`,`onAfterEnter`])):(o(),h(d,{key:1},[F(()=>g)],64))],2))},1032,[`show`,`placement`,`to`,`teleportDisabled`]))],2))},1024))]},1024)):F(()=>null)],16));return u?u({node:b,option:t}):b}}),ze=S({name:`DropdownGroupHeader`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(){let{showIconRef:e,hasSubmenuRef:t}=a(Z),{renderLabelRef:n,labelFieldRef:r,nodePropsRef:i,renderOptionRef:o}=a(Q);return{labelField:r,showIcon:e,hasSubmenu:t,renderLabel:n,nodeProps:i,renderOption:o}},render(){let{clsPrefix:e,hasSubmenu:t,showIcon:n,nodeProps:r,renderLabel:i,renderOption:a}=this,{rawNode:s}=this.tmNode,c=(o(),h(`div`,l({class:`${e}-dropdown-option`},r?.(s)),[m(`div`,{class:P(`${e}-dropdown-option-body ${e}-dropdown-option-body--group`)},[m(`div`,{"data-dropdown-option":!0,class:P([`${e}-dropdown-option-body__prefix`,n&&`${e}-dropdown-option-body__prefix--show-icon`])},[F(()=>K(s.icon))],2),m(`div`,{class:P(`${e}-dropdown-option-body__label`),"data-dropdown-option":!0},[i?(o(),h(d,{key:0},[F(()=>i(s))],64)):(o(),h(d,{key:1},[F(()=>K(s.title??s[this.labelField]))],64))],2),m(`div`,{class:P([`${e}-dropdown-option-body__suffix`,t&&`${e}-dropdown-option-body__suffix--has-submenu`]),"data-dropdown-option":!0},null,2)],2)],16));return a?a({node:c,option:s}):c}}),Be=S({name:`NDropdownGroup`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0},parentKey:{type:[String,Number],default:null}},render(){let{tmNode:e,parentKey:t,clsPrefix:n}=this,{children:r}=e;return o(),h(d,null,[(o(),y(ze,{clsPrefix:n,tmNode:e,key:e.key},null,8,[`clsPrefix`,`tmNode`])),F(()=>r?.map(e=>{let{rawNode:r}=e;return r.show===!1?null:Fe(r)?x(Ne,{clsPrefix:n,key:e.key}):e.isGroup?(H(`dropdown`,"`group` node is not allowed to be put in `group` node."),null):(o(),y(Re,{clsPrefix:n,tmNode:e,parentKey:t,key:e.key},null,8,[`clsPrefix`,`tmNode`,`parentKey`]))}))],64)}}),Ve=S({name:`DropdownRenderOption`,props:{tmNode:{type:Object,required:!0}},render(){let{rawNode:{render:e,props:t}}=this.tmNode;return x(`div`,t,[e?.()])}}),He=S({name:`DropdownMenu`,props:{scrollable:Boolean,showArrow:Boolean,arrowStyle:[String,Object],clsPrefix:{type:String,required:!0},tmNodes:{type:Array,default:()=>[]},parentKey:{type:[String,Number],default:null}},setup(e){let{renderIconRef:t,childrenFieldRef:n}=a(Q);C(Z,{showIconRef:p(()=>{let n=t.value;return e.tmNodes.some(e=>{if(e.isGroup)return e.children?.some(({rawNode:e})=>n?n(e):e.icon);let{rawNode:t}=e;return n?n(t):t.icon})}),hasSubmenuRef:p(()=>{let{value:t}=n;return e.tmNodes.some(e=>{if(e.isGroup)return e.children?.some(({rawNode:e})=>$(e,t));let{rawNode:n}=e;return $(n,t)})})});let r=b(null);return C(de,null),C(fe,null),C(G,r),{bodyRef:r}},render(){let{parentKey:e,clsPrefix:t,scrollable:r}=this,i=this.tmNodes.map(n=>{let{rawNode:i}=n;return i.show===!1?null:Ie(i)?(o(),y(Ve,{tmNode:n,key:n.key},null,8,[`tmNode`])):Fe(i)?(o(),y(Ne,{clsPrefix:t,key:n.key},null,8,[`clsPrefix`])):Pe(i)?(o(),y(Be,{clsPrefix:t,tmNode:n,parentKey:e,key:n.key},null,8,[`clsPrefix`,`tmNode`,`parentKey`])):(o(),y(Re,{clsPrefix:t,tmNode:n,parentKey:e,key:n.key,props:i.props,scrollable:r},null,8,[`clsPrefix`,`tmNode`,`parentKey`,`props`,`scrollable`]))});return o(),h(`div`,{class:P([`${t}-dropdown-menu`,r&&`${t}-dropdown-menu--scrollable`]),ref:`bodyRef`},[r?(o(),y(n,{key:0,contentClass:`${t}-dropdown-menu__content`},{default:()=>i},1032,[`contentClass`])):(o(),h(d,{key:1},[F(()=>i)],64)),this.showArrow?(o(),h(d,{key:2},[F(()=>ae({clsPrefix:t,arrowStyle:this.arrowStyle,arrowClass:void 0,arrowWrapperClass:void 0,arrowWrapperStyle:void 0}))],64)):F(()=>null)],2)}}),Ue=M(`dropdown-menu`,`
 transform-origin: var(--v-transform-origin);
 background-color: var(--n-color);
 border-radius: var(--n-border-radius);
 box-shadow: var(--n-box-shadow);
 position: relative;
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
`,[e(),M(`dropdown-option`,`
 position: relative;
 `,[N(`a`,`
 text-decoration: none;
 color: inherit;
 outline: none;
 `,[N(`&::before`,`
 content: "";
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `)]),M(`dropdown-option-body`,`
 display: flex;
 cursor: pointer;
 position: relative;
 height: var(--n-option-height);
 line-height: var(--n-option-height);
 font-size: var(--n-font-size);
 color: var(--n-option-text-color);
 transition: color .3s var(--n-bezier);
 `,[N(`&::before`,`
 content: "";
 position: absolute;
 top: 0;
 bottom: 0;
 left: 4px;
 right: 4px;
 transition: background-color .3s var(--n-bezier);
 border-radius: var(--n-border-radius);
 `),I(`disabled`,[w(`pending`,`
 color: var(--n-option-text-color-hover);
 `,[j(`prefix, suffix`,`
 color: var(--n-option-text-color-hover);
 `),N(`&::before`,`background-color: var(--n-option-color-hover);`)]),w(`active`,`
 color: var(--n-option-text-color-active);
 `,[j(`prefix, suffix`,`
 color: var(--n-option-text-color-active);
 `),N(`&::before`,`background-color: var(--n-option-color-active);`)]),w(`child-active`,`
 color: var(--n-option-text-color-child-active);
 `,[j(`prefix, suffix`,`
 color: var(--n-option-text-color-child-active);
 `)])]),w(`disabled`,`
 cursor: not-allowed;
 opacity: var(--n-option-opacity-disabled);
 `),w(`group`,`
 font-size: calc(var(--n-font-size) - 1px);
 color: var(--n-group-header-text-color);
 `,[j(`prefix`,`
 width: calc(var(--n-option-prefix-width) / 2);
 `,[w(`show-icon`,`
 width: calc(var(--n-option-icon-prefix-width) / 2);
 `)])]),j(`prefix`,`
 width: var(--n-option-prefix-width);
 display: flex;
 justify-content: center;
 align-items: center;
 color: var(--n-prefix-color);
 transition: color .3s var(--n-bezier);
 z-index: 1;
 `,[w(`show-icon`,`
 width: var(--n-option-icon-prefix-width);
 `),M(`icon`,`
 font-size: var(--n-option-icon-size);
 `)]),j(`label`,`
 white-space: nowrap;
 flex: 1;
 z-index: 1;
 `),j(`suffix`,`
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
 `,[w(`has-submenu`,`
 width: var(--n-option-icon-suffix-width);
 `),M(`icon`,`
 font-size: var(--n-option-icon-size);
 `)]),M(`dropdown-menu`,`pointer-events: all;`)]),M(`dropdown-offset-container`,`
 pointer-events: none;
 position: absolute;
 left: 0;
 right: 0;
 top: -4px;
 bottom: -4px;
 `)]),M(`dropdown-divider`,`
 transition: background-color .3s var(--n-bezier);
 background-color: var(--n-divider-color);
 height: 1px;
 margin: 4px 0;
 `),M(`dropdown-menu-wrapper`,`
 transform-origin: var(--v-transform-origin);
 width: fit-content;
 `),N(`>`,[M(`scrollbar`,`
 height: inherit;
 max-height: inherit;
 `)]),I(`scrollable`,`
 padding: var(--n-padding);
 `),w(`scrollable`,[j(`content`,`
 padding: var(--n-padding);
 `)])]),We={animated:{type:Boolean,default:!0},keyboard:{type:Boolean,default:!0},size:String,inverted:Boolean,placement:{type:String,default:`bottom`},onSelect:[Function,Array],options:{type:Array,default:()=>[]},menuProps:Function,showArrow:Boolean,renderLabel:Function,renderIcon:Function,renderOption:Function,nodeProps:Function,labelField:{type:String,default:`label`},keyField:{type:String,default:`key`},childrenField:{type:String,default:`children`},value:[String,Number]},Ge=Object.keys(U),Ke={...U,...We,...D.props},qe=S({name:`Dropdown`,inheritAttrs:!1,props:Ke,setup(e){let t=b(!1),n=q(g(e,`show`),t),r=p(()=>{let{keyField:t,childrenField:n}=e;return ce(e.options,{getKey(e){return e[t]},getDisabled(e){return e.disabled===!0},getIgnored(e){return e.type===`divider`||e.type===`render`},getChildren(e){return e[n]}})}),i=p(()=>r.value.treeNodes),a=b(null),o=b(null),s=b(null),c=p(()=>a.value??o.value??s.value??null),l=p(()=>r.value.getPath(c.value).keyPath),d=p(()=>r.value.getPath(e.value).keyPath),f=k(()=>e.keyboard&&n.value);ve({keydown:{ArrowUp:{prevent:!0,handler:M},ArrowRight:{prevent:!0,handler:j},ArrowDown:{prevent:!0,handler:N},ArrowLeft:{prevent:!0,handler:A},Enter:{prevent:!0,handler:P},Escape:E}},f);let{mergedClsPrefixRef:m,inlineThemeDisabled:h,mergedComponentPropsRef:_}=B(e),v=p(()=>e.size||_?.value?.Dropdown?.size||`medium`),y=D(`Dropdown`,`-dropdown`,Ue,ge,e,m);C(Q,{labelFieldRef:g(e,`labelField`),childrenFieldRef:g(e,`childrenField`),renderLabelRef:g(e,`renderLabel`),renderIconRef:g(e,`renderIcon`),hoverKeyRef:a,keyboardKeyRef:o,lastToggledSubmenuKeyRef:s,pendingKeyPathRef:l,activeKeyPathRef:d,animatedRef:g(e,`animated`),mergedShowRef:n,nodePropsRef:g(e,`nodeProps`),renderOptionRef:g(e,`renderOption`),menuPropsRef:g(e,`menuProps`),doSelect:x,doUpdateShow:S}),u(n,t=>{!e.animated&&!t&&w()});function x(t,n){let{onSelect:r}=e;r&&T(r,t,n)}function S(n){let{"onUpdate:show":r,onUpdateShow:i}=e;r&&T(r,n),i&&T(i,n),t.value=n}function w(){a.value=null,o.value=null,s.value=null}function E(){S(!1)}function A(){I(`left`)}function j(){I(`right`)}function M(){I(`up`)}function N(){I(`down`)}function P(){let e=F();e?.isLeaf&&n.value&&(x(e.key,e.rawNode),S(!1))}function F(){let{value:e}=r,{value:t}=c;return!e||t===null?null:e.getNode(t)??null}function I(e){let{value:t}=c,{value:{getFirstAvailableNode:n}}=r,i=null;if(t===null){let e=n();e!==null&&(i=e.key)}else{let t=F();if(t){let n;switch(e){case`down`:n=t.getNext();break;case`up`:n=t.getPrev();break;case`right`:n=t.getChild();break;case`left`:n=t.getParent()}n&&(i=n.key)}}i!==null&&(a.value=null,o.value=i)}let L=p(()=>{let{inverted:t}=e,n=v.value,{common:{cubicBezierEaseInOut:r},self:i}=y.value,{padding:a,dividerColor:o,borderRadius:s,optionOpacityDisabled:c,[V(`optionIconSuffixWidth`,n)]:l,[V(`optionSuffixWidth`,n)]:u,[V(`optionIconPrefixWidth`,n)]:d,[V(`optionPrefixWidth`,n)]:f,[V(`fontSize`,n)]:p,[V(`optionHeight`,n)]:m,[V(`optionIconSize`,n)]:h}=i,g={"--n-bezier":r,"--n-font-size":p,"--n-padding":a,"--n-border-radius":s,"--n-option-height":m,"--n-option-prefix-width":f,"--n-option-icon-prefix-width":d,"--n-option-suffix-width":u,"--n-option-icon-suffix-width":l,"--n-option-icon-size":h,"--n-divider-color":o,"--n-option-opacity-disabled":c};return t?(g[`--n-color`]=i.colorInverted,g[`--n-option-color-hover`]=i.optionColorHoverInverted,g[`--n-option-color-active`]=i.optionColorActiveInverted,g[`--n-option-text-color`]=i.optionTextColorInverted,g[`--n-option-text-color-hover`]=i.optionTextColorHoverInverted,g[`--n-option-text-color-active`]=i.optionTextColorActiveInverted,g[`--n-option-text-color-child-active`]=i.optionTextColorChildActiveInverted,g[`--n-prefix-color`]=i.prefixColorInverted,g[`--n-suffix-color`]=i.suffixColorInverted,g[`--n-group-header-text-color`]=i.groupHeaderTextColorInverted):(g[`--n-color`]=i.color,g[`--n-option-color-hover`]=i.optionColorHover,g[`--n-option-color-active`]=i.optionColorActive,g[`--n-option-text-color`]=i.optionTextColor,g[`--n-option-text-color-hover`]=i.optionTextColorHover,g[`--n-option-text-color-active`]=i.optionTextColorActive,g[`--n-option-text-color-child-active`]=i.optionTextColorChildActive,g[`--n-prefix-color`]=i.prefixColor,g[`--n-suffix-color`]=i.suffixColor,g[`--n-group-header-text-color`]=i.groupHeaderTextColor),g}),R=h?O(`dropdown`,p(()=>`${v.value[0]}${e.inverted?`i`:``}`),L,e):void 0;return{mergedClsPrefix:m,mergedTheme:y,mergedSize:v,tmNodes:i,mergedShow:n,handleAfterLeave:()=>{e.animated&&w()},doUpdateShow:S,cssVars:h?void 0:L,themeClass:R?.themeClass,onRender:R?.onRender}},render(){let e=(e,t,n,r,i)=>{let{mergedClsPrefix:a,menuProps:o}=this;this.onRender?.();let s=o?.(void 0,this.tmNodes.map(e=>e.rawNode))||{},c={ref:_e(t),class:[e,`${a}-dropdown`,`${a}-dropdown--${this.mergedSize}-size`,this.themeClass],clsPrefix:a,tmNodes:this.tmNodes,style:[...n,this.cssVars],showArrow:this.showArrow,arrowStyle:this.arrowStyle,scrollable:this.scrollable,onMouseenter:r,onMouseleave:i};return x(He,l(this.$attrs,c,s))},{mergedTheme:t}=this,n={show:this.mergedShow,theme:t.peers.Popover,themeOverrides:t.peerOverrides.Popover,internalOnAfterLeave:this.handleAfterLeave,internalRenderBody:e,onUpdateShow:this.doUpdateShow,"onUpdate:show":void 0};return o(),y(oe,le(this.$props,Ge,n),{_:1,trigger:z(()=>this.$slots.default?.())},16)}});export{be as a,Y as i,Oe as n,Ce as r,qe as t};