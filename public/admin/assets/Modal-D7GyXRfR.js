import{$ as e,D as t,G as n,H as r,I as i,N as a,S as o,V as s,Z as c,_ as l,et as u,g as d,k as f,v as p,w as m,y as h}from"./php-modules-jPihHAOi.js";import{A as g,D as _,E as v,F as y,K as b,L as x,N as S,Ot as C,Y as w,at as T,d as E,dt as D,f as O,i as k,k as A,kt as j,l as ee,m as M,ot as N,u as P,w as F,y as te}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as I,A as L,C as ne,E as re,F as R,I as ie,Jt as ae,L as oe,N as se,O as z,P as ce,Qt as B,Xt as V,Yt as H,_ as le,at as U,b as ue,ct as W,gt as G,i as de,j as fe,l as pe,ln as me,nn as he,on as ge,pt as _e,st as K,t as ve,tn as ye,v as be,vt as xe,y as Se,yt as Ce}from"./Button-CjW_3_5b.js";import{_ as we,b as Te,d as Ee,f as q,g as De,h as Oe,l as ke,m as Ae,r as je,s as J,u as Me,v as Ne,x as Pe,y as Fe}from"./light-BgIV-Nal.js";var Y=N(null);function Ie(e){if(e.clientX>0||e.clientY>0)Y.value={x:e.clientX,y:e.clientY};else{let{target:t}=e;if(t instanceof Element){let{left:e,top:n,width:r,height:i}=t.getBoundingClientRect();Y.value=e>0||n>0?{x:e+r/2,y:n+i/2}:{x:0,y:0}}else Y.value=null}}var X=0,Le=!0;function Re(){if(!De)return T(N(null));X===0&&r(`click`,document,Ie,!0);let e=()=>{X+=1};return(Le&&=Oe())?(A(e),g(()=>{--X,X===0&&s(`click`,document,Ie,!0)})):e(),T(Y)}var ze=N(void 0),Z=0;function Be(){ze.value=Date.now()}var Ve=!0;function He(e){if(!De)return T(N(!1));let t=N(!1),n=null;function i(){n!==null&&window.clearTimeout(n)}function a(){i(),t.value=!0,n=window.setTimeout(()=>{t.value=!1},e)}Z===0&&r(`click`,window,Be,!0);let o=()=>{Z+=1,r(`click`,window,a,!0)};return(Ve&&=Oe())?(A(o),g(()=>{--Z,Z===0&&s(`click`,window,Be,!0),s(`click`,window,a,!0),i()})):o(),T(t)}var Ue=G(`n-dialog-provider`),We=G(`n-dialog-api`),Ge=G(`n-dialog-reactive-list`),Ke={titleFontSize:`18px`,padding:`16px 28px 20px 28px`,iconSize:`28px`,actionSpace:`12px`,contentMargin:`8px 0 16px 0`,iconMargin:`0 4px 0 0`,iconMarginIconTop:`4px 0 8px 0`,closeSize:`22px`,closeIconSize:`18px`,closeMargin:`20px 26px 0 0`,closeMarginIconTop:`10px 16px 0 0`};function qe(e){let{textColor1:t,textColor2:n,modalColor:r,closeIconColor:i,closeIconColorHover:a,closeIconColorPressed:o,closeColorHover:s,closeColorPressed:c,infoColor:l,successColor:u,warningColor:d,errorColor:f,primaryColor:p,dividerColor:m,borderRadius:h,fontWeightStrong:g,lineHeight:_,fontSize:v}=e;return{...Ke,fontSize:v,lineHeight:_,border:`1px solid ${m}`,titleTextColor:t,textColor:n,color:r,closeColorHover:s,closeColorPressed:c,closeIconColor:i,closeIconColorHover:a,closeIconColorPressed:o,closeBorderRadius:h,iconColor:p,iconColorInfo:l,iconColorSuccess:u,iconColorWarning:d,iconColorError:f,borderRadius:h,titleFontWeight:g}}var Je=ce({name:`Dialog`,common:oe,peers:{Button:de},self:qe}),Q={icon:Function,type:{type:String,default:`default`},title:[String,Function],closable:{type:Boolean,default:!0},negativeText:String,positiveText:String,positiveButtonProps:Object,negativeButtonProps:Object,content:[String,Function],action:Function,showIcon:{type:Boolean,default:!0},loading:Boolean,bordered:Boolean,iconPlacement:String,titleClass:[String,Array],titleStyle:[String,Object],contentClass:[String,Array],contentStyle:[String,Object],actionClass:[String,Array],actionStyle:[String,Object],onPositiveClick:Function,onNegativeClick:Function,onClose:Function,closeFocusable:Boolean},Ye=u(Q),Xe=H([V(`dialog`,`
 --n-icon-margin: var(--n-icon-margin-top) var(--n-icon-margin-right) var(--n-icon-margin-bottom) var(--n-icon-margin-left);
 word-break: break-word;
 line-height: var(--n-line-height);
 position: relative;
 background: var(--n-color);
 color: var(--n-text-color);
 box-sizing: border-box;
 margin: auto;
 border-radius: var(--n-border-radius);
 padding: var(--n-padding);
 transition: 
 border-color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `,[B(`icon`,`
 color: var(--n-icon-color);
 `),I(`bordered`,`
 border: var(--n-border);
 `),I(`icon-top`,[B(`close`,`
 margin: var(--n-close-margin);
 `),B(`icon`,`
 margin: var(--n-icon-margin);
 `),B(`content`,`
 text-align: center;
 `),B(`title`,`
 justify-content: center;
 `),B(`action`,`
 justify-content: center;
 `)]),I(`icon-left`,[B(`icon`,`
 margin: var(--n-icon-margin);
 `),I(`closable`,[B(`title`,`
 padding-right: calc(var(--n-close-size) + 6px);
 `)])]),B(`close`,`
 position: absolute;
 right: 0;
 top: 0;
 margin: var(--n-close-margin);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 z-index: 1;
 `),B(`content`,`
 font-size: var(--n-font-size);
 margin: var(--n-content-margin);
 position: relative;
 word-break: break-word;
 `,[I(`last`,`margin-bottom: 0;`)]),B(`action`,`
 display: flex;
 justify-content: flex-end;
 `,[H(`> *:not(:last-child)`,`
 margin-right: var(--n-action-space);
 `)]),B(`icon`,`
 font-size: var(--n-icon-size);
 transition: color .3s var(--n-bezier);
 `),B(`title`,`
 transition: color .3s var(--n-bezier);
 display: flex;
 align-items: center;
 font-size: var(--n-title-font-size);
 font-weight: var(--n-title-font-weight);
 color: var(--n-title-text-color);
 `),V(`dialog-icon-container`,`
 display: flex;
 justify-content: center;
 `)]),he(V(`dialog`,`
 width: 446px;
 max-width: calc(100vw - 32px);
 `)),V(`dialog`,[ae(`
 width: 446px;
 max-width: calc(100vw - 32px);
 `)])]),Ze={default:()=>(y(),O(Se)),info:()=>(y(),O(Se)),success:()=>(y(),O(be)),warning:()=>(y(),O(le)),error:()=>(y(),O(ue))},Qe=te({name:`Dialog`,alias:[`NimbusConfirmCard`,`Confirm`],props:{...R.props,...Q},slots:Object,setup(e){let{mergedComponentPropsRef:t,mergedClsPrefixRef:n,inlineThemeDisabled:r,mergedRtlRef:i}=_e(e),a=ne(`Dialog`,i,n),o=P(()=>{let{iconPlacement:n}=e;return n||t?.value?.Dialog?.iconPlacement||`left`});function s(t){let{onPositiveClick:n}=e;n&&n(t)}function l(t){let{onNegativeClick:n}=e;n&&n(t)}function u(){let{onClose:t}=e;t&&t()}let d=R(`Dialog`,`-dialog`,Xe,Je,e,n),f=P(()=>{let{type:t}=e,n=o.value,{common:{cubicBezierEaseInOut:r},self:{fontSize:i,lineHeight:a,border:s,titleTextColor:l,textColor:u,color:f,closeBorderRadius:p,closeColorHover:m,closeColorPressed:h,closeIconColor:g,closeIconColorHover:_,closeIconColorPressed:v,closeIconSize:y,borderRadius:b,titleFontWeight:x,titleFontSize:S,padding:C,iconSize:w,actionSpace:T,contentMargin:E,closeSize:D,[n===`top`?`iconMarginIconTop`:`iconMargin`]:O,[n===`top`?`closeMarginIconTop`:`closeMargin`]:k,[ye(`iconColor`,t)]:A}}=d.value,j=c(O);return{"--n-font-size":i,"--n-icon-color":A,"--n-bezier":r,"--n-close-margin":k,"--n-icon-margin-top":j.top,"--n-icon-margin-right":j.right,"--n-icon-margin-bottom":j.bottom,"--n-icon-margin-left":j.left,"--n-icon-size":w,"--n-close-size":D,"--n-close-icon-size":y,"--n-close-border-radius":p,"--n-close-color-hover":m,"--n-close-color-pressed":h,"--n-close-icon-color":g,"--n-close-icon-color-hover":_,"--n-close-icon-color-pressed":v,"--n-color":f,"--n-text-color":u,"--n-border-radius":b,"--n-padding":C,"--n-line-height":a,"--n-border":s,"--n-content-margin":E,"--n-title-font-size":S,"--n-title-font-weight":x,"--n-title-text-color":l,"--n-action-space":T}}),p=r?ie(`dialog`,P(()=>`${e.type[0]}${o.value[0]}`),f,e):void 0;return{mergedClsPrefix:n,rtlEnabled:a,mergedIconPlacement:o,mergedTheme:d,handlePositiveClick:s,handleNegativeClick:l,handleCloseClick:u,cssVars:r?void 0:f,themeClass:p?.themeClass,onRender:p?.onRender}},render(){let{bordered:e,mergedIconPlacement:n,cssVars:r,closable:i,showIcon:a,title:o,content:s,action:c,negativeText:l,positiveText:u,positiveButtonProps:d,negativeButtonProps:f,handlePositiveClick:p,handleNegativeClick:m,mergedTheme:h,loading:g,type:_,mergedClsPrefix:b}=this;this.onRender?.();let x=a?(y(),O(se,{key:1,clsPrefix:b,class:U(`${b}-dialog__icon`)},{default:()=>z(this.$slots.icon,e=>e||(this.icon?J(this.icon):Ze[this.type]()))},1032,[`clsPrefix`,`class`])):null,S=z(this.$slots.action,e=>e||u||l||c?(y(),M(`div`,{key:2,class:U([`${b}-dialog__action`,this.actionClass]),style:j(this.actionStyle)},[W(()=>e||(c?[J(c)]:[this.negativeText&&(y(),O(ve,v({key:3,theme:h.peers.Button,themeOverrides:h.peerOverrides.Button,ghost:!0,size:`small`,onClick:m},f),{default:()=>J(this.negativeText)},1040,[`theme`,`themeOverrides`,`onClick`])),this.positiveText&&(y(),O(ve,v({key:4,theme:h.peers.Button,themeOverrides:h.peerOverrides.Button,size:`small`,type:_==="default"?`primary`:_,disabled:g,loading:g,onClick:p},d),{default:()=>J(this.positiveText)},1040,[`theme`,`themeOverrides`,`type`,`disabled`,`loading`,`onClick`]))]))],6)):null);return y(),M(`div`,{class:U([`${b}-dialog`,this.themeClass,this.closable&&`${b}-dialog--closable`,`${b}-dialog--icon-${n}`,e&&`${b}-dialog--bordered`,this.rtlEnabled&&`${b}-dialog--rtl`]),style:j(r),role:`dialog`},[i?(y(),M(k,{key:0},[W(()=>z(this.$slots.close,e=>{let n=[`${b}-dialog__close`,this.rtlEnabled&&`${b}-dialog--rtl`];return e?(y(),M(`div`,{key:5,class:U(n)},[W(()=>e)],2)):(y(),O(t,{key:6,focusable:this.closeFocusable,clsPrefix:b,class:U(n),onClick:this.handleCloseClick},null,8,[`focusable`,`clsPrefix`,`class`,`onClick`]))}))],64)):W(()=>null),a&&n===`top`?(y(),M(`div`,{key:2,class:U(`${b}-dialog-icon-container`)},[W(()=>x)],2)):W(()=>null),E(`div`,{class:U([`${b}-dialog__title`,this.titleClass]),style:j(this.titleStyle)},[a&&n===`left`?(y(),M(k,{key:0},[W(()=>x)],64)):W(()=>null),W(()=>re(this.$slots.header,()=>[J(o)]))],6),E(`div`,{class:U([`${b}-dialog__content`,S?``:`${b}-dialog__content--last`,this.contentClass]),style:j(this.contentStyle)},[W(()=>re(this.$slots.default,()=>[J(s)]))],6),W(()=>S)],6)}});function $e(e){let{modalColor:t,textColor2:n,boxShadow3:r}=e;return{color:t,textColor:n,boxShadow:r}}var et=ce({name:`Modal`,common:oe,peers:{Scrollbar:n,Dialog:Je,Card:h},self:$e}),tt=G(`n-modal-provider`),nt=G(`n-modal-api`),rt=G(`n-modal-reactive-list`);function it(){let e=F(nt,null);return e===null&&xe(`use-modal`,`No outer <n-modal-provider /> founded.`),e}var at=`n-draggable`;function ot(e,t){let n,i=N(null),a=N(null),o=P(()=>e.value!==!1),c=P(()=>o.value?at:``),l=P(()=>{let t=e.value;return t===!0||t===!1||!t||t.bounds!==`none`});function u(e){let o=e.querySelector(`.${at}`);if(!o||!c.value)return;let u=0,d=0,f=0,p=0,m=0,h=0,g,v=null,y=null;function b(t){t.preventDefault(),g=t;let{x:n,y:r,right:o,bottom:s}=e.getBoundingClientRect();if(d=n,p=r,u=window.innerWidth-o,f=window.innerHeight-s,i.value!==null&&a.value!==null)h=i.value,m=a.value;else{let{left:t,top:n}=e.style;m=+n.slice(0,-2),h=+t.slice(0,-2)}}function x(){y&&=(i.value=y.x,a.value=y.y,null),v=null}function S(e){if(!g)return;let{clientX:t,clientY:n}=g,r=e.clientX-t,i=e.clientY-n;l.value&&(r>u?r=u:-r>d&&(r=-d),i>f?i=f:-i>p&&(i=-p)),y={x:r+h,y:i+m},v||=requestAnimationFrame(x)}function C(){g=void 0,v&&=(cancelAnimationFrame(v),null),y&&=(i.value=y.x,a.value=y.y,null),_(()=>{t.onEnd(e)})}r(`mousedown`,o,b),r(`mousemove`,window,S),r(`mouseup`,window,C),n=()=>{v&&cancelAnimationFrame(v),s(`mousedown`,o,b),s(`mousemove`,window,S),s(`mouseup`,window,C)}}function d(){n&&=(n(),void 0),i.value=null,a.value=null}return S(d),{stopDrag:d,startDrag:u,draggableRef:o,draggableClassRef:c,dragX:i,dragY:a}}var st=N(!1);function ct(){st.value=!0}function lt(){st.value=!1}var $=0;function ut(){return pe&&(A(()=>{$||(window.addEventListener(`compositionstart`,ct),window.addEventListener(`compositionend`,lt)),$++}),g(()=>{$<=1?(window.removeEventListener(`compositionstart`,ct),window.removeEventListener(`compositionend`,lt),$=0):$--})),st}var dt={...p,...Q},ft=u(dt).filter(e=>e!==`onClose`&&e!==`onPositiveClick`&&e!==`onNegativeClick`),pt=te({name:`ModalBody`,inheritAttrs:!1,slots:Object,props:{show:{type:Boolean,required:!0},preset:String,displayDirective:{type:String,required:!0},trapFocus:{type:Boolean,default:!0},autoFocus:{type:Boolean,default:!0},blockScroll:Boolean,draggable:{type:[Boolean,Object],default:!1},maskHidden:Boolean,...dt,onClickoutside:{type:Function,required:!0},onBeforeLeave:{type:Function,required:!0},onAfterLeave:{type:Function,required:!0},onPositiveClick:{type:Function,required:!0},onNegativeClick:{type:Function,required:!0},onClose:{type:Function,required:!0},onAfterEnter:Function,onEsc:Function},setup(e){let t=N(null),n=N(null),r=N(e.show),i=N(null),a=N(null),s=F(Fe),c=null;b(D(e,`show`),e=>{e&&(c=s.getMousePosition())},{immediate:!0});let{stopDrag:l,startDrag:u,draggableRef:d,draggableClassRef:f,dragX:p,dragY:m}=ot(D(e,`draggable`),{onEnd:e=>{y(e)}}),h=P(()=>C([e.titleClass,f.value])),g=P(()=>C([e.headerClass,f.value]));b(D(e,`show`),e=>{e&&(r.value=!0)}),o(P(()=>e.blockScroll&&r.value));function v(){if(s.transformOriginRef.value===`center`)return``;let{value:e}=i,{value:t}=a;return e===null||t===null?``:n.value?`${e}px ${t+n.value.containerScrollTop}px`:``}function y(e){if(s.transformOriginRef.value===`center`||!c||!n.value)return;let t=n.value.containerScrollTop,{offsetLeft:r,offsetTop:o}=e,l=c.y,u=c.x;i.value=-(r-u),a.value=-(o-l-t),e.style.transformOrigin=v()}function S(e){_(()=>{y(e)})}function w(t){t.style.transformOrigin=v(),e.onBeforeLeave()}function T(t){let n=t;d.value&&u(n),e.onAfterEnter&&e.onAfterEnter(n)}function E(){r.value=!1,i.value=null,a.value=null,l(),e.onAfterLeave()}function O(){let{onClose:t}=e;t&&t()}function k(){e.onNegativeClick()}function A(){e.onPositiveClick()}let j=N(null);return b(j,e=>{e&&_(()=>{let n=e.el;n&&t.value!==n&&(t.value=n)})}),x(Ne,t),x(Pe,null),x(we,null),{mergedTheme:s.mergedThemeRef,appear:s.appearRef,isMounted:s.isMountedRef,mergedClsPrefix:s.mergedClsPrefixRef,bodyRef:t,scrollbarRef:n,draggableClass:f,displayed:r,childNodeRef:j,cardHeaderClass:g,dialogTitleClass:h,handlePositiveClick:A,handleNegativeClick:k,handleCloseClick:O,handleAfterEnter:T,handleAfterLeave:E,handleBeforeLeave:w,handleEnter:S,dragX:p,dragY:m}},render(){let{$slots:e,$attrs:t,handleEnter:n,handleAfterEnter:r,handleAfterLeave:i,handleBeforeLeave:a,preset:o,mergedClsPrefix:s,dragX:c,dragY:u}=this,p={...t};c!==null&&u!==null&&(p.style=j([p.style,{left:`${c}px`,top:`${u}px`}]));let m=null;if(!o){if(m=Ae(`default`,e.default,{draggableClass:this.draggableClass}),!m){Ce(`modal`,`default slot is empty`);return}m=ee(m),m.props=v({class:`${s}-modal`},p,m.props||{})}return this.displayDirective===`show`||this.displayed||this.show?w((y(),M(`div`,{key:1,role:`none`,class:U([`${s}-modal-body-wrapper`,this.maskHidden&&`${s}-modal-body-wrapper--mask-hidden`])},[(y(),O(f,{ref:`scrollbarRef`,theme:this.mergedTheme.peers.Scrollbar,themeOverrides:this.mergedTheme.peerOverrides.Scrollbar,contentClass:`${s}-modal-scroll-content`},{default:()=>(y(),O(ke,{disabled:!this.trapFocus||this.maskHidden,active:this.show,onEsc:this.onEsc,autoFocus:this.autoFocus},{default:()=>(y(),O(ge,{name:`fade-in-scale-up-transition`,appear:this.appear??this.isMounted,onEnter:n,onAfterEnter:r,onAfterLeave:i,onBeforeLeave:a},{default:()=>{let t=[[me,this.show]];return t.push([Ee,this.onClickoutside,void 0,{capture:!0}]),w(this.preset===`confirm`||this.preset===`dialog`?(y(),O(Qe,v({key:2},p,{class:[`${s}-modal`,p.class],ref:`bodyRef`,theme:this.mergedTheme.peers.Dialog,themeOverrides:this.mergedTheme.peerOverrides.Dialog},q(this.$props,Ye),{titleClass:this.dialogTitleClass,"aria-modal":`true`}),K(e),1040,[`class`,`theme`,`themeOverrides`,`titleClass`])):this.preset===`card`?(y(),O(d,v({key:3},p,{ref:`bodyRef`,class:[`${s}-modal`,p.class],theme:this.mergedTheme.peers.Card,themeOverrides:this.mergedTheme.peerOverrides.Card},q(this.$props,l),{headerClass:this.cardHeaderClass,"aria-modal":`true`,role:`dialog`}),K(e),1040,[`class`,`theme`,`themeOverrides`,`headerClass`])):this.childNodeRef=m,t)}},1032,[`appear`,`onEnter`,`onAfterEnter`,`onAfterLeave`,`onBeforeLeave`]))},1032,[`disabled`,`active`,`onEsc`,`autoFocus`]))},1032,[`theme`,`themeOverrides`,`contentClass`]))],2)),[[me,this.displayDirective===`if`||this.displayed||this.show]]):null}}),mt=H([V(`modal-container`,`
 position: fixed;
 left: 0;
 top: 0;
 height: 0;
 width: 0;
 display: flex;
 `),V(`modal-mask`,`
 position: fixed;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 background-color: rgba(0, 0, 0, .4);
 `,[i({enterDuration:`.25s`,leaveDuration:`.25s`,enterCubicBezier:`var(--n-bezier-ease-out)`,leaveCubicBezier:`var(--n-bezier-ease-out)`})]),V(`modal-body-wrapper`,`
 position: fixed;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 overflow: visible;
 `,[V(`modal-scroll-content`,`
 min-height: 100%;
 display: flex;
 position: relative;
 `),I(`mask-hidden`,`pointer-events: none;`,[V(`modal-scroll-content`,[H(`> *`,`
 pointer-events: all;
 `)])])]),V(`modal`,`
 position: relative;
 align-self: center;
 color: var(--n-text-color);
 margin: auto;
 box-shadow: var(--n-box-shadow);
 `,[m({duration:`.25s`,enterScale:`.5`}),H(`.${at}`,`
 cursor: move;
 user-select: none;
 `)])]),ht={...R.props,show:Boolean,showMask:{type:Boolean,default:!0},maskClosable:{type:Boolean,default:!0},preset:String,to:[String,Object],displayDirective:{type:String,default:`if`},transformOrigin:{type:String,default:`mouse`},zIndex:Number,autoFocus:{type:Boolean,default:!0},trapFocus:{type:Boolean,default:!0},closeOnEsc:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!0},...dt,draggable:[Boolean,Object],onEsc:Function,"onUpdate:show":[Function,Array],onUpdateShow:[Function,Array],onAfterEnter:Function,onBeforeLeave:Function,onAfterLeave:Function,onClose:Function,onPositiveClick:Function,onNegativeClick:Function,onMaskClick:Function,internalDialog:Boolean,internalModal:Boolean,internalAppear:{type:Boolean,default:void 0},overlayStyle:[String,Object],onBeforeHide:Function,onAfterHide:Function,onHide:Function,unstableShowMask:{type:Boolean,default:void 0}},gt=te({name:`Modal`,inheritAttrs:!1,props:ht,slots:Object,setup(t){let n=N(null),{mergedClsPrefixRef:r,namespaceRef:i,inlineThemeDisabled:a}=_e(t),o=R(`Modal`,`-modal`,mt,et,t,r),s=He(64),c=Re(),l=fe(),u=t.internalDialog?F(Ue,null):null,d=t.internalModal?F(Te,null):null,f=ut();function p(e){let{onUpdateShow:n,"onUpdate:show":r,onHide:i}=t;n&&L(n,e),r&&L(r,e),i&&!e&&i(e)}function m(){let{onClose:e}=t;e?Promise.resolve(e()).then(e=>{e!==!1&&p(!1)}):p(!1)}function h(){let{onPositiveClick:e}=t;e?Promise.resolve(e()).then(e=>{e!==!1&&p(!1)}):p(!1)}function g(){let{onNegativeClick:e}=t;e?Promise.resolve(e()).then(e=>{e!==!1&&p(!1)}):p(!1)}function _(){let{onBeforeLeave:e,onBeforeHide:n}=t;e&&L(e),n&&n()}function v(){let{onAfterLeave:e,onAfterHide:n}=t;e&&L(e),n&&n()}function y(r){let{onMaskClick:i}=t;i&&i(r),t.maskClosable&&n.value?.contains(e(r))&&p(!1)}function b(e){t.onEsc?.(),t.show&&t.closeOnEsc&&je(e)&&(f.value||p(!1))}x(Fe,{getMousePosition:()=>{let e=u||d;if(e){let{clickedRef:t,clickedPositionRef:n}=e;if(t.value&&n.value)return n.value}return s.value?c.value:null},mergedClsPrefixRef:r,mergedThemeRef:o,isMountedRef:l,appearRef:D(t,`internalAppear`),transformOriginRef:D(t,`transformOrigin`)});let S=P(()=>{let{common:{cubicBezierEaseOut:e},self:{boxShadow:t,color:n,textColor:r}}=o.value;return{"--n-bezier-ease-out":e,"--n-box-shadow":t,"--n-color":n,"--n-text-color":r}}),C=a?ie(`theme-class`,void 0,S,t):void 0;return{mergedClsPrefix:r,namespace:i,isMounted:l,containerRef:n,presetProps:P(()=>q(t,ft)),handleEsc:b,handleAfterLeave:v,handleClickoutside:y,handleBeforeLeave:_,doUpdateShow:p,handleNegativeClick:g,handlePositiveClick:h,handleCloseClick:m,cssVars:a?void 0:S,themeClass:C?.themeClass,onRender:C?.onRender}},render(){let{mergedClsPrefix:e}=this;return y(),O(a,{to:this.to,show:this.show},{default:()=>{this.onRender?.();let{showMask:t}=this;return w((y(),M(`div`,{role:`none`,ref:`containerRef`,class:U([`${e}-modal-container`,this.themeClass,this.namespace]),style:j(this.cssVars)},[t?(y(),O(ge,{name:`fade-in-transition`,key:`mask`,appear:this.internalAppear??this.isMounted},{default:()=>this.show?(y(),M(`div`,{key:1,"aria-hidden":!0,class:U(`${e}-modal-mask`)},null,2)):null},1032,[`appear`])):W(()=>null),(y(),O(pt,v({style:this.overlayStyle},this.$attrs,{ref:`bodyWrapper`,displayDirective:this.displayDirective,show:this.show,preset:this.preset,autoFocus:this.autoFocus,trapFocus:this.trapFocus,draggable:this.draggable,blockScroll:this.blockScroll,maskHidden:!t},this.presetProps,{onEsc:this.handleEsc,onClose:this.handleCloseClick,onNegativeClick:this.handleNegativeClick,onPositiveClick:this.handlePositiveClick,onBeforeLeave:this.handleBeforeLeave,onAfterEnter:this.onAfterEnter,onAfterLeave:this.handleAfterLeave,onClickoutside:this.handleClickoutside}),K(this.$slots),1040,[`style`,`displayDirective`,`show`,`preset`,`autoFocus`,`trapFocus`,`draggable`,`blockScroll`,`maskHidden`,`onEsc`,`onClose`,`onNegativeClick`,`onPositiveClick`,`onBeforeLeave`,`onAfterEnter`,`onAfterLeave`,`onClickoutside`]))],6)),[[Me,{zIndex:this.zIndex,enabled:this.show}]])}},1032,[`to`,`show`])}});export{tt as a,Qe as c,qe as d,We as f,Re as g,He as h,nt as i,Ye as l,Ge as m,ht as n,rt as o,Ue as p,it as r,$e as s,gt as t,Q as u};