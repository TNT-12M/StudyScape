import{D as e,O as t,Z as n}from"./php-modules-BAZCMuH3.js";import{F as r,L as i,d as a,dt as o,f as s,kt as c,m as l,ot as u,u as d,y as f}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as p,A as m,C as h,F as g,I as _,L as v,O as y,Qt as b,R as x,S,Xt as C,Yt as w,at as T,ct as E,en as D,gt as O,pt as k,tn as A}from"./Button-CjW_3_5b.js";function j(e){let{textColor2:n,primaryColorHover:r,primaryColorPressed:i,primaryColor:a,infoColor:o,successColor:s,warningColor:c,errorColor:l,baseColor:u,borderColor:d,opacityDisabled:f,tagColor:p,closeIconColor:m,closeIconColorHover:h,closeIconColorPressed:g,borderRadiusSmall:_,fontSizeMini:v,fontSizeTiny:y,fontSizeSmall:b,fontSizeMedium:S,heightMini:C,heightTiny:w,heightSmall:T,heightMedium:E,closeColorHover:D,closeColorPressed:O,buttonColor2Hover:k,buttonColor2Pressed:A,fontWeightStrong:j}=e;return{...t,closeBorderRadius:_,heightTiny:C,heightSmall:w,heightMedium:T,heightLarge:E,borderRadius:_,opacityDisabled:f,fontSizeTiny:v,fontSizeSmall:y,fontSizeMedium:b,fontSizeLarge:S,fontWeightStrong:j,textColorCheckable:n,textColorHoverCheckable:n,textColorPressedCheckable:n,textColorChecked:u,colorCheckable:`#0000`,colorHoverCheckable:k,colorPressedCheckable:A,colorChecked:a,colorCheckedHover:r,colorCheckedPressed:i,border:`1px solid ${d}`,textColor:n,color:p,colorBordered:`rgb(250, 250, 252)`,closeIconColor:m,closeIconColorHover:h,closeIconColorPressed:g,closeColorHover:D,closeColorPressed:O,borderPrimary:`1px solid ${x(a,{alpha:.3})}`,textColorPrimary:a,colorPrimary:x(a,{alpha:.12}),colorBorderedPrimary:x(a,{alpha:.1}),closeIconColorPrimary:a,closeIconColorHoverPrimary:a,closeIconColorPressedPrimary:a,closeColorHoverPrimary:x(a,{alpha:.12}),closeColorPressedPrimary:x(a,{alpha:.18}),borderInfo:`1px solid ${x(o,{alpha:.3})}`,textColorInfo:o,colorInfo:x(o,{alpha:.12}),colorBorderedInfo:x(o,{alpha:.1}),closeIconColorInfo:o,closeIconColorHoverInfo:o,closeIconColorPressedInfo:o,closeColorHoverInfo:x(o,{alpha:.12}),closeColorPressedInfo:x(o,{alpha:.18}),borderSuccess:`1px solid ${x(s,{alpha:.3})}`,textColorSuccess:s,colorSuccess:x(s,{alpha:.12}),colorBorderedSuccess:x(s,{alpha:.1}),closeIconColorSuccess:s,closeIconColorHoverSuccess:s,closeIconColorPressedSuccess:s,closeColorHoverSuccess:x(s,{alpha:.12}),closeColorPressedSuccess:x(s,{alpha:.18}),borderWarning:`1px solid ${x(c,{alpha:.35})}`,textColorWarning:c,colorWarning:x(c,{alpha:.15}),colorBorderedWarning:x(c,{alpha:.12}),closeIconColorWarning:c,closeIconColorHoverWarning:c,closeIconColorPressedWarning:c,closeColorHoverWarning:x(c,{alpha:.12}),closeColorPressedWarning:x(c,{alpha:.18}),borderError:`1px solid ${x(l,{alpha:.23})}`,textColorError:l,colorError:x(l,{alpha:.1}),colorBorderedError:x(l,{alpha:.08}),closeIconColorError:l,closeIconColorHoverError:l,closeIconColorPressedError:l,closeColorHoverError:x(l,{alpha:.12}),closeColorPressedError:x(l,{alpha:.18})}}var M={name:`Tag`,common:v,self:j},N={color:Object,type:{type:String,default:`default`},round:Boolean,size:String,closable:Boolean,disabled:{type:Boolean,default:void 0}},P=C(`tag`,`
 --n-close-margin: var(--n-close-margin-top) var(--n-close-margin-right) var(--n-close-margin-bottom) var(--n-close-margin-left);
 white-space: nowrap;
 position: relative;
 box-sizing: border-box;
 cursor: default;
 display: inline-flex;
 align-items: center;
 flex-wrap: nowrap;
 padding: var(--n-padding);
 border-radius: var(--n-border-radius);
 color: var(--n-text-color);
 background-color: var(--n-color);
 transition: 
 border-color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 line-height: 1;
 height: var(--n-height);
 font-size: var(--n-font-size);
`,[p(`strong`,`
 font-weight: var(--n-font-weight-strong);
 `),b(`border`,`
 pointer-events: none;
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 border-radius: inherit;
 border: var(--n-border);
 transition: border-color .3s var(--n-bezier);
 `),b(`icon`,`
 display: flex;
 margin: 0 4px 0 0;
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 font-size: var(--n-avatar-size-override);
 `),b(`avatar`,`
 display: flex;
 margin: 0 6px 0 0;
 `),b(`close`,`
 margin: var(--n-close-margin);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `),p(`round`,`
 padding: 0 calc(var(--n-height) / 3);
 border-radius: calc(var(--n-height) / 2);
 `,[b(`icon`,`
 margin: 0 4px 0 calc((var(--n-height) - 8px) / -2);
 `),b(`avatar`,`
 margin: 0 6px 0 calc((var(--n-height) - 8px) / -2);
 `),p(`closable`,`
 padding: 0 calc(var(--n-height) / 4) 0 calc(var(--n-height) / 3);
 `)]),p(`icon, avatar`,[p(`round`,`
 padding: 0 calc(var(--n-height) / 3) 0 calc(var(--n-height) / 2);
 `)]),p(`disabled`,`
 cursor: not-allowed !important;
 opacity: var(--n-opacity-disabled);
 `),p(`checkable`,`
 cursor: pointer;
 box-shadow: none;
 color: var(--n-text-color-checkable);
 background-color: var(--n-color-checkable);
 `,[D(`disabled`,[w(`&:hover`,`background-color: var(--n-color-hover-checkable);`,[D(`checked`,`color: var(--n-text-color-hover-checkable);`)]),w(`&:active`,`background-color: var(--n-color-pressed-checkable);`,[D(`checked`,`color: var(--n-text-color-pressed-checkable);`)])]),p(`checked`,`
 color: var(--n-text-color-checked);
 background-color: var(--n-color-checked);
 `,[D(`disabled`,[w(`&:hover`,`background-color: var(--n-color-checked-hover);`),w(`&:active`,`background-color: var(--n-color-checked-pressed);`)])])])]),F=[`onClick`,`onMouseenter`,`onMouseleave`],I={...g.props,...N,bordered:{type:Boolean,default:void 0},checked:Boolean,checkable:Boolean,strong:Boolean,triggerClickOnClose:Boolean,onClose:[Array,Function],onMouseenter:Function,onMouseleave:Function,"onUpdate:checked":Function,onUpdateChecked:Function,internalCloseFocusable:{type:Boolean,default:!0},internalCloseIsButtonTag:{type:Boolean,default:!0},onCheckedChange:Function},L=O(`n-tag`),R=f({name:`Tag`,props:I,slots:Object,setup(e){let t=u(null),{mergedBorderedRef:r,mergedClsPrefixRef:a,inlineThemeDisabled:s,mergedRtlRef:c,mergedComponentPropsRef:l}=k(e),f=d(()=>e.size||l?.value?.Tag?.size||`medium`),p=g(`Tag`,`-tag`,P,M,e,a);i(L,{roundRef:o(e,`round`)});function v(){if(!e.disabled&&e.checkable){let{checked:t,onCheckedChange:n,onUpdateChecked:r,"onUpdate:checked":i}=e;r&&r(!t),i&&i(!t),n&&n(!t)}}function y(t){if(e.triggerClickOnClose||t.stopPropagation(),!e.disabled){let{onClose:n}=e;n&&m(n,t)}}let b={setTextContent(e){let{value:n}=t;n&&(n.textContent=e)}},x=h(`Tag`,c,a),C=d(()=>{let{type:t,color:{color:i,textColor:a}={}}=e,o=f.value,{common:{cubicBezierEaseInOut:s},self:{padding:c,closeMargin:l,borderRadius:u,opacityDisabled:d,textColorCheckable:m,textColorHoverCheckable:h,textColorPressedCheckable:g,textColorChecked:_,colorCheckable:v,colorHoverCheckable:y,colorPressedCheckable:b,colorChecked:x,colorCheckedHover:S,colorCheckedPressed:C,closeBorderRadius:w,fontWeightStrong:T,[A(`colorBordered`,t)]:E,[A(`closeSize`,o)]:D,[A(`closeIconSize`,o)]:O,[A(`fontSize`,o)]:k,[A(`height`,o)]:j,[A(`color`,t)]:M,[A(`textColor`,t)]:N,[A(`border`,t)]:P,[A(`closeIconColor`,t)]:F,[A(`closeIconColorHover`,t)]:I,[A(`closeIconColorPressed`,t)]:L,[A(`closeColorHover`,t)]:R,[A(`closeColorPressed`,t)]:z}}=p.value,B=n(l);return{"--n-font-weight-strong":T,"--n-avatar-size-override":`calc(${j} - 8px)`,"--n-bezier":s,"--n-border-radius":u,"--n-border":P,"--n-close-icon-size":O,"--n-close-color-pressed":z,"--n-close-color-hover":R,"--n-close-border-radius":w,"--n-close-icon-color":F,"--n-close-icon-color-hover":I,"--n-close-icon-color-pressed":L,"--n-close-icon-color-disabled":F,"--n-close-margin-top":B.top,"--n-close-margin-right":B.right,"--n-close-margin-bottom":B.bottom,"--n-close-margin-left":B.left,"--n-close-size":D,"--n-color":i||(r.value?E:M),"--n-color-checkable":v,"--n-color-checked":x,"--n-color-checked-hover":S,"--n-color-checked-pressed":C,"--n-color-hover-checkable":y,"--n-color-pressed-checkable":b,"--n-font-size":k,"--n-height":j,"--n-opacity-disabled":d,"--n-padding":c,"--n-text-color":a||N,"--n-text-color-checkable":m,"--n-text-color-checked":_,"--n-text-color-hover-checkable":h,"--n-text-color-pressed-checkable":g}}),w=s?_(`tag`,d(()=>{let t=``,{type:n,color:{color:i,textColor:a}={}}=e;return t+=n[0],t+=f.value[0],i&&(t+=`a${S(i)}`),a&&(t+=`b${S(a)}`),r.value&&(t+=`c`),t}),C,e):void 0;return{...b,rtlEnabled:x,mergedClsPrefix:a,contentRef:t,mergedBordered:r,handleClick:v,handleCloseClick:y,cssVars:s?void 0:C,themeClass:w?.themeClass,onRender:w?.onRender}},render(){let{mergedClsPrefix:t,rtlEnabled:n,closable:i,color:{borderColor:o}={},round:u,onRender:d,$slots:f}=this;d?.();let p=y(f.avatar,e=>e&&(r(),l(`div`,{class:T(`${t}-tag__avatar`)},[E(()=>e)],2))),m=y(f.icon,e=>e&&(r(),l(`div`,{class:T(`${t}-tag__icon`)},[E(()=>e)],2)));return r(),l(`div`,{class:T([`${t}-tag`,this.themeClass,{[`${t}-tag--rtl`]:n,[`${t}-tag--strong`]:this.strong,[`${t}-tag--disabled`]:this.disabled,[`${t}-tag--checkable`]:this.checkable,[`${t}-tag--checked`]:this.checkable&&this.checked,[`${t}-tag--round`]:u,[`${t}-tag--avatar`]:p,[`${t}-tag--icon`]:m,[`${t}-tag--closable`]:i}]),style:c(this.cssVars),onClick:this.handleClick,onMouseenter:this.onMouseenter,onMouseleave:this.onMouseleave},[E(()=>m||p),a(`span`,{class:T(`${t}-tag__content`),ref:`contentRef`},[E(()=>this.$slots.default?.())],2),!this.checkable&&i?(r(),s(e,{key:0,clsPrefix:t,class:T(`${t}-tag__close`),disabled:this.disabled,onClick:this.handleCloseClick,focusable:this.internalCloseFocusable,round:u,isButtonTag:this.internalCloseIsButtonTag,absolute:!0},null,8,[`clsPrefix`,`class`,`disabled`,`onClick`,`focusable`,`round`,`isButtonTag`])):E(()=>null),!this.checkable&&this.mergedBordered?(r(),l(`div`,{key:2,class:T(`${t}-tag__border`),style:c({borderColor:o})},null,6)):E(()=>null)],46,F)}});export{L as n,R as t};