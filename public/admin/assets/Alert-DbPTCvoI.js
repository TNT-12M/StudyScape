import{D as e,Z as t}from"./php-modules-jPihHAOi.js";import{E as n,F as r,d as i,f as a,m as o,ot as s,u as c,y as l}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as u,C as d,E as f,F as p,I as m,L as h,N as g,O as _,Qt as v,R as y,Xt as b,Yt as x,_ as S,at as C,b as w,ct as T,g as E,pt as D,tn as O,v as k,y as A,z as j}from"./Button-CjW_3_5b.js";import{t as M}from"./_common-DnEpalBx.js";import{t as N}from"./fade-in-height-expand.cssr-QD47wAtv.js";function P(e){let{lineHeight:t,borderRadius:n,fontWeightStrong:r,baseColor:i,dividerColor:a,actionColor:o,textColor1:s,textColor2:c,closeColorHover:l,closeColorPressed:u,closeIconColor:d,closeIconColorHover:f,closeIconColorPressed:p,infoColor:m,successColor:h,warningColor:g,errorColor:_,fontSize:v}=e;return{...M,fontSize:v,lineHeight:t,titleFontWeight:r,borderRadius:n,border:`1px solid ${a}`,color:o,titleTextColor:s,iconColor:c,contentTextColor:c,closeBorderRadius:n,closeColorHover:l,closeColorPressed:u,closeIconColor:d,closeIconColorHover:f,closeIconColorPressed:p,borderInfo:`1px solid ${j(i,y(m,{alpha:.25}))}`,colorInfo:j(i,y(m,{alpha:.08})),titleTextColorInfo:s,iconColorInfo:m,contentTextColorInfo:c,closeColorHoverInfo:l,closeColorPressedInfo:u,closeIconColorInfo:d,closeIconColorHoverInfo:f,closeIconColorPressedInfo:p,borderSuccess:`1px solid ${j(i,y(h,{alpha:.25}))}`,colorSuccess:j(i,y(h,{alpha:.08})),titleTextColorSuccess:s,iconColorSuccess:h,contentTextColorSuccess:c,closeColorHoverSuccess:l,closeColorPressedSuccess:u,closeIconColorSuccess:d,closeIconColorHoverSuccess:f,closeIconColorPressedSuccess:p,borderWarning:`1px solid ${j(i,y(g,{alpha:.33}))}`,colorWarning:j(i,y(g,{alpha:.08})),titleTextColorWarning:s,iconColorWarning:g,contentTextColorWarning:c,closeColorHoverWarning:l,closeColorPressedWarning:u,closeIconColorWarning:d,closeIconColorHoverWarning:f,closeIconColorPressedWarning:p,borderError:`1px solid ${j(i,y(_,{alpha:.25}))}`,colorError:j(i,y(_,{alpha:.08})),titleTextColorError:s,iconColorError:_,contentTextColorError:c,closeColorHoverError:l,closeColorPressedError:u,closeIconColorError:d,closeIconColorHoverError:f,closeIconColorPressedError:p}}var F={name:`Alert`,common:h,self:P},I=b(`alert`,`
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
 `,[x(`& +`,[v(`content`,{marginTop:`9px`})])]),v(`content`,{transition:`color .3s var(--n-bezier)`,fontSize:`var(--n-font-size)`})]),v(`icon`,{transition:`color .3s var(--n-bezier)`})]),L={...p.props,title:String,showIcon:{type:Boolean,default:!0},type:{type:String,default:`default`},bordered:{type:Boolean,default:!0},closable:Boolean,onClose:Function,onAfterLeave:Function,onAfterHide:Function},R=l({name:`Alert`,inheritAttrs:!1,props:L,slots:Object,setup(e){let{mergedClsPrefixRef:n,mergedBorderedRef:r,inlineThemeDisabled:i,mergedRtlRef:a}=D(e),o=p(`Alert`,`-alert`,I,F,e,n),l=d(`Alert`,a,n),u=c(()=>{let{common:{cubicBezierEaseInOut:n},self:r}=o.value,{fontSize:i,borderRadius:a,titleFontWeight:s,lineHeight:c,iconSize:l,iconMargin:u,iconMarginRtl:d,closeIconSize:f,closeBorderRadius:p,closeSize:m,closeMargin:h,closeMarginRtl:g,padding:_}=r,{type:v}=e,{left:y,right:b}=t(u);return{"--n-bezier":n,"--n-color":r[O(`color`,v)],"--n-close-icon-size":f,"--n-close-border-radius":p,"--n-close-color-hover":r[O(`closeColorHover`,v)],"--n-close-color-pressed":r[O(`closeColorPressed`,v)],"--n-close-icon-color":r[O(`closeIconColor`,v)],"--n-close-icon-color-hover":r[O(`closeIconColorHover`,v)],"--n-close-icon-color-pressed":r[O(`closeIconColorPressed`,v)],"--n-icon-color":r[O(`iconColor`,v)],"--n-border":r[O(`border`,v)],"--n-title-text-color":r[O(`titleTextColor`,v)],"--n-content-text-color":r[O(`contentTextColor`,v)],"--n-line-height":c,"--n-border-radius":a,"--n-font-size":i,"--n-title-font-weight":s,"--n-icon-size":l,"--n-icon-margin":u,"--n-icon-margin-rtl":d,"--n-close-size":m,"--n-close-margin":h,"--n-close-margin-rtl":g,"--n-padding":_,"--n-icon-margin-left":y,"--n-icon-margin-right":b}}),f=i?m(`alert`,c(()=>e.type[0]),u,e):void 0,h=s(!0),g=()=>{let{onAfterLeave:t,onAfterHide:n}=e;t&&t(),n&&n()};return{rtlEnabled:l,mergedClsPrefix:n,mergedBordered:r,visible:h,handleCloseClick:()=>{Promise.resolve(e.onClose?.()).then(e=>{e!==!1&&(h.value=!1)})},handleAfterLeave:()=>{g()},mergedTheme:o,cssVars:i?void 0:u,themeClass:f?.themeClass,onRender:f?.onRender}},render(){return this.onRender?.(),r(),a(E,{onAfterLeave:this.handleAfterLeave},{default:()=>{let{mergedClsPrefix:t,$slots:s}=this,c={class:[`${t}-alert`,this.themeClass,this.closable&&`${t}-alert--closable`,this.showIcon&&`${t}-alert--show-icon`,!this.title&&this.closable&&`${t}-alert--right-adjust`,this.rtlEnabled&&`${t}-alert--rtl`],style:this.cssVars,role:`alert`};return this.visible?(r(),o(`div`,n({key:1},n(this.$attrs,c)),[T(()=>this.closable&&(r(),a(e,{clsPrefix:t,class:C(`${t}-alert__close`),onClick:this.handleCloseClick},null,8,[`clsPrefix`,`class`,`onClick`]))),T(()=>this.bordered&&(r(),o(`div`,{class:C(`${t}-alert__border`)},null,2))),T(()=>this.showIcon&&(r(),o(`div`,{class:C(`${t}-alert__icon`),"aria-hidden":`true`},[T(()=>f(s.icon,()=>[(r(),a(g,{clsPrefix:t},{default:()=>{switch(this.type){case`success`:return r(),a(k,{key:3});case`info`:return r(),a(A,{key:4});case`warning`:return r(),a(S,{key:5});case`error`:return r(),a(w,{key:6});default:return null}}},1032,[`clsPrefix`]))]))],2))),i(`div`,{class:C([`${t}-alert-body`,this.mergedBordered&&`${t}-alert-body--bordered`])},[T(()=>_(s.header,e=>{let n=e||this.title;return n?(r(),o(`div`,{key:2,class:C(`${t}-alert-body__title`)},[T(()=>n)],2)):null})),T(()=>s.default&&(r(),o(`div`,{class:C(`${t}-alert-body__content`)},[T(()=>s.default())],2)))],2)],16)):null}},1032,[`onAfterLeave`])}});export{R as t};