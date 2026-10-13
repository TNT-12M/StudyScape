import{A as e,Q as t}from"./php-modules-GekPVnxA.js";import{L as n,O as r,d as i,f as a,h as o,p as s,ut as c,x as l}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as u,C as d,E as f,F as p,I as m,L as h,N as g,O as _,Qt as v,R as y,Xt as b,Yt as x,_ as S,at as C,b as w,ct as T,g as E,pt as D,tn as O,v as k,y as A,z as j}from"./Button-BtFx7EYM.js";import{n as M,t as N}from"./fade-in-height-expand.cssr-DnaCoNGx.js";function P(e){let{lineHeight:t,borderRadius:n,fontWeightStrong:r,baseColor:i,dividerColor:a,actionColor:o,textColor1:s,textColor2:c,closeColorHover:l,closeColorPressed:u,closeIconColor:d,closeIconColorHover:f,closeIconColorPressed:p,infoColor:m,successColor:h,warningColor:g,errorColor:_,fontSize:v}=e;return{...M,fontSize:v,lineHeight:t,titleFontWeight:r,borderRadius:n,border:`1px solid ${a}`,color:o,titleTextColor:s,iconColor:c,contentTextColor:c,closeBorderRadius:n,closeColorHover:l,closeColorPressed:u,closeIconColor:d,closeIconColorHover:f,closeIconColorPressed:p,borderInfo:`1px solid ${j(i,y(m,{alpha:.25}))}`,colorInfo:j(i,y(m,{alpha:.08})),titleTextColorInfo:s,iconColorInfo:m,contentTextColorInfo:c,closeColorHoverInfo:l,closeColorPressedInfo:u,closeIconColorInfo:d,closeIconColorHoverInfo:f,closeIconColorPressedInfo:p,borderSuccess:`1px solid ${j(i,y(h,{alpha:.25}))}`,colorSuccess:j(i,y(h,{alpha:.08})),titleTextColorSuccess:s,iconColorSuccess:h,contentTextColorSuccess:c,closeColorHoverSuccess:l,closeColorPressedSuccess:u,closeIconColorSuccess:d,closeIconColorHoverSuccess:f,closeIconColorPressedSuccess:p,borderWarning:`1px solid ${j(i,y(g,{alpha:.33}))}`,colorWarning:j(i,y(g,{alpha:.08})),titleTextColorWarning:s,iconColorWarning:g,contentTextColorWarning:c,closeColorHoverWarning:l,closeColorPressedWarning:u,closeIconColorWarning:d,closeIconColorHoverWarning:f,closeIconColorPressedWarning:p,borderError:`1px solid ${j(i,y(_,{alpha:.25}))}`,colorError:j(i,y(_,{alpha:.08})),titleTextColorError:s,iconColorError:_,contentTextColorError:c,closeColorHoverError:l,closeColorPressedError:u,closeIconColorError:d,closeIconColorHoverError:f,closeIconColorPressedError:p}}var F={name:`Alert`,common:h,self:P},I=b(`alert`,`
 line-height: var(--n-line-height);
 border-radius: var(--n-border-radius);
 position: relative;
 transition: background-color .3s var(--n-bezier);
 background-color: var(--n-color);
 text-align: start;
 word-break: break-word;
`,[v(`border`,`
 border-radius: inherit;
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 transition: border-color .3s var(--n-bezier);
 border: var(--n-border);
 pointer-events: none;
 `),u(`closable`,[b(`alert-body`,[v(`title`,`
 padding-right: 24px;
 `)])]),v(`icon`,{color:`var(--n-icon-color)`}),b(`alert-body`,{padding:`var(--n-padding)`},[v(`title`,{color:`var(--n-title-text-color)`}),v(`content`,{color:`var(--n-content-text-color)`})]),N({originalTransition:`transform .3s var(--n-bezier)`,enterToProps:{transform:`scale(1)`},leaveToProps:{transform:`scale(0.9)`}}),v(`icon`,`
 position: absolute;
 left: 0;
 top: 0;
 align-items: center;
 justify-content: center;
 display: flex;
 width: var(--n-icon-size);
 height: var(--n-icon-size);
 font-size: var(--n-icon-size);
 margin: var(--n-icon-margin);
 `),v(`close`,`
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 position: absolute;
 right: 0;
 top: 0;
 margin: var(--n-close-margin);
 `),u(`show-icon`,[b(`alert-body`,{paddingLeft:`calc(var(--n-icon-margin-left) + var(--n-icon-size) + var(--n-icon-margin-right))`})]),u(`right-adjust`,[b(`alert-body`,{paddingRight:`calc(var(--n-close-size) + var(--n-padding) + 2px)`})]),b(`alert-body`,`
 border-radius: var(--n-border-radius);
 transition: border-color .3s var(--n-bezier);
 `,[v(`title`,`
 transition: color .3s var(--n-bezier);
 font-size: 16px;
 line-height: 19px;
 font-weight: var(--n-title-font-weight);
 `,[x(`& +`,[v(`content`,{marginTop:`9px`})])]),v(`content`,{transition:`color .3s var(--n-bezier)`,fontSize:`var(--n-font-size)`})]),v(`icon`,{transition:`color .3s var(--n-bezier)`})]),L={...p.props,title:String,showIcon:{type:Boolean,default:!0},type:{type:String,default:`default`},bordered:{type:Boolean,default:!0},closable:Boolean,onClose:Function,onAfterLeave:Function,onAfterHide:Function},R=l({name:`Alert`,inheritAttrs:!1,props:L,slots:Object,setup(e){let{mergedClsPrefixRef:n,mergedBorderedRef:r,inlineThemeDisabled:a,mergedRtlRef:o}=D(e),s=p(`Alert`,`-alert`,I,F,e,n),l=d(`Alert`,o,n),u=i(()=>{let{common:{cubicBezierEaseInOut:n},self:r}=s.value,{fontSize:i,borderRadius:a,titleFontWeight:o,lineHeight:c,iconSize:l,iconMargin:u,iconMarginRtl:d,closeIconSize:f,closeBorderRadius:p,closeSize:m,closeMargin:h,closeMarginRtl:g,padding:_}=r,{type:v}=e,{left:y,right:b}=t(u);return{"--n-bezier":n,"--n-color":r[O(`color`,v)],"--n-close-icon-size":f,"--n-close-border-radius":p,"--n-close-color-hover":r[O(`closeColorHover`,v)],"--n-close-color-pressed":r[O(`closeColorPressed`,v)],"--n-close-icon-color":r[O(`closeIconColor`,v)],"--n-close-icon-color-hover":r[O(`closeIconColorHover`,v)],"--n-close-icon-color-pressed":r[O(`closeIconColorPressed`,v)],"--n-icon-color":r[O(`iconColor`,v)],"--n-border":r[O(`border`,v)],"--n-title-text-color":r[O(`titleTextColor`,v)],"--n-content-text-color":r[O(`contentTextColor`,v)],"--n-line-height":c,"--n-border-radius":a,"--n-font-size":i,"--n-title-font-weight":o,"--n-icon-size":l,"--n-icon-margin":u,"--n-icon-margin-rtl":d,"--n-close-size":m,"--n-close-margin":h,"--n-close-margin-rtl":g,"--n-padding":_,"--n-icon-margin-left":y,"--n-icon-margin-right":b}}),f=a?m(`alert`,i(()=>e.type[0]),u,e):void 0,h=c(!0),g=()=>{let{onAfterLeave:t,onAfterHide:n}=e;t&&t(),n&&n()};return{rtlEnabled:l,mergedClsPrefix:n,mergedBordered:r,visible:h,handleCloseClick:()=>{Promise.resolve(e.onClose?.()).then(e=>{e!==!1&&(h.value=!1)})},handleAfterLeave:()=>{g()},mergedTheme:s,cssVars:a?void 0:u,themeClass:f?.themeClass,onRender:f?.onRender}},render(){return this.onRender?.(),n(),s(E,{onAfterLeave:this.handleAfterLeave},{default:()=>{let{mergedClsPrefix:t,$slots:i}=this,c={class:[`${t}-alert`,this.themeClass,this.closable&&`${t}-alert--closable`,this.showIcon&&`${t}-alert--show-icon`,!this.title&&this.closable&&`${t}-alert--right-adjust`,this.rtlEnabled&&`${t}-alert--rtl`],style:this.cssVars,role:`alert`};return this.visible?(n(),o(`div`,r({key:1},r(this.$attrs,c)),[T(()=>this.closable&&(n(),s(e,{clsPrefix:t,class:C(`${t}-alert__close`),onClick:this.handleCloseClick},null,8,[`clsPrefix`,`class`,`onClick`]))),T(()=>this.bordered&&(n(),o(`div`,{class:C(`${t}-alert__border`)},null,2))),T(()=>this.showIcon&&(n(),o(`div`,{class:C(`${t}-alert__icon`),"aria-hidden":`true`},[T(()=>f(i.icon,()=>[(n(),s(g,{clsPrefix:t},{default:()=>{switch(this.type){case`success`:return n(),s(k,{key:3});case`info`:return n(),s(A,{key:4});case`warning`:return n(),s(S,{key:5});case`error`:return n(),s(w,{key:6});default:return null}}},1032,[`clsPrefix`]))]))],2))),a(`div`,{class:C([`${t}-alert-body`,this.mergedBordered&&`${t}-alert-body--bordered`])},[T(()=>_(i.header,e=>{let r=e||this.title;return r?(n(),o(`div`,{key:2,class:C(`${t}-alert-body__title`)},[T(()=>r)],2)):null})),T(()=>i.default&&(n(),o(`div`,{class:C(`${t}-alert-body__content`)},[T(()=>i.default())],2)))],2)],16)):null}},1032,[`onAfterLeave`])}});export{R as t};