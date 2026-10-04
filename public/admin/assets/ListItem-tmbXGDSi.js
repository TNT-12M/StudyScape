import{F as e,L as t,dt as n,kt as r,m as i,u as a,w as o,y as s}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as c,C as l,F as u,I as d,Qt as f,Xt as p,Yt as m,at as h,ct as g,gt as _,nn as v,pt as y,rn as b,vt as x}from"./Button-CjW_3_5b.js";import{t as S}from"./light-QnQ2t1_r.js";var C=m([p(`list`,`
 --n-merged-border-color: var(--n-border-color);
 --n-merged-color: var(--n-color);
 --n-merged-color-hover: var(--n-color-hover);
 margin: 0;
 font-size: var(--n-font-size);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 padding: 0;
 list-style-type: none;
 color: var(--n-text-color);
 background-color: var(--n-merged-color);
 `,[c(`show-divider`,[p(`list-item`,[m(`&:not(:last-child)`,[f(`divider`,`
 background-color: var(--n-merged-border-color);
 `)])])]),c(`clickable`,[p(`list-item`,`
 cursor: pointer;
 `)]),c(`bordered`,`
 border: 1px solid var(--n-merged-border-color);
 border-radius: var(--n-border-radius);
 `),c(`hoverable`,[p(`list-item`,`
 border-radius: var(--n-border-radius);
 `,[m(`&:hover`,`
 background-color: var(--n-merged-color-hover);
 `,[f(`divider`,`
 background-color: transparent;
 `)])])]),c(`bordered, hoverable`,[p(`list-item`,`
 padding: 12px 20px;
 `),f(`header, footer`,`
 padding: 12px 20px;
 `)]),f(`header, footer`,`
 padding: 12px 0;
 box-sizing: border-box;
 transition: border-color .3s var(--n-bezier);
 `,[m(`&:not(:last-child)`,`
 border-bottom: 1px solid var(--n-merged-border-color);
 `)]),p(`list-item`,`
 position: relative;
 padding: 12px 0; 
 box-sizing: border-box;
 display: flex;
 flex-wrap: nowrap;
 align-items: center;
 transition:
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[f(`prefix`,`
 margin-right: 20px;
 flex: 0;
 `),f(`suffix`,`
 margin-left: 20px;
 flex: 0;
 `),f(`main`,`
 flex: 1;
 `),f(`divider`,`
 height: 1px;
 position: absolute;
 bottom: 0;
 left: 0;
 right: 0;
 background-color: transparent;
 transition: background-color .3s var(--n-bezier);
 pointer-events: none;
 `)])]),v(p(`list`,`
 --n-merged-color-hover: var(--n-color-hover-modal);
 --n-merged-color: var(--n-color-modal);
 --n-merged-border-color: var(--n-border-color-modal);
 `)),b(p(`list`,`
 --n-merged-color-hover: var(--n-color-hover-popover);
 --n-merged-color: var(--n-color-popover);
 --n-merged-border-color: var(--n-border-color-popover);
 `))]),w={...u.props,size:{type:String,default:`medium`},bordered:Boolean,clickable:Boolean,hoverable:Boolean,showDivider:{type:Boolean,default:!0}},T=_(`n-list`),E=s({name:`List`,props:w,slots:Object,setup(e){let{mergedClsPrefixRef:r,inlineThemeDisabled:i,mergedRtlRef:o}=y(e),s=l(`List`,o,r),c=u(`List`,`-list`,C,S,e,r);t(T,{showDividerRef:n(e,`showDivider`),mergedClsPrefixRef:r});let f=a(()=>{let{common:{cubicBezierEaseInOut:e},self:{fontSize:t,textColor:n,color:r,colorModal:i,colorPopover:a,borderColor:o,borderColorModal:s,borderColorPopover:l,borderRadius:u,colorHover:d,colorHoverModal:f,colorHoverPopover:p}}=c.value;return{"--n-font-size":t,"--n-bezier":e,"--n-text-color":n,"--n-color":r,"--n-border-radius":u,"--n-border-color":o,"--n-border-color-modal":s,"--n-border-color-popover":l,"--n-color-modal":i,"--n-color-popover":a,"--n-color-hover":d,"--n-color-hover-modal":f,"--n-color-hover-popover":p}}),p=i?d(`list`,void 0,f,e):void 0;return{mergedClsPrefix:r,rtlEnabled:s,cssVars:i?void 0:f,themeClass:p?.themeClass,onRender:p?.onRender}},render(){let{$slots:t,mergedClsPrefix:n,onRender:a}=this;return a?.(),e(),i(`ul`,{class:h([`${n}-list`,this.rtlEnabled&&`${n}-list--rtl`,this.bordered&&`${n}-list--bordered`,this.showDivider&&`${n}-list--show-divider`,this.hoverable&&`${n}-list--hoverable`,this.clickable&&`${n}-list--clickable`,this.themeClass]),style:r(this.cssVars)},[t.header?(e(),i(`div`,{key:0,class:h(`${n}-list__header`)},[g(()=>t.header())],2)):g(()=>null),g(()=>t.default?.()),t.footer?(e(),i(`div`,{key:2,class:h(`${n}-list__footer`)},[g(()=>t.footer())],2)):g(()=>null)],6)}}),D=s({name:`ListItem`,slots:Object,setup(){let e=o(T,null);return e||x(`list-item`,"`n-list-item` must be placed in `n-list`."),{showDivider:e.showDividerRef,mergedClsPrefix:e.mergedClsPrefixRef}},render(){let{$slots:t,mergedClsPrefix:n}=this;return e(),i(`li`,{class:h(`${n}-list-item`)},[t.prefix?(e(),i(`div`,{key:0,class:h(`${n}-list-item__prefix`)},[g(()=>t.prefix())],2)):g(()=>null),t.default?(e(),i(`div`,{key:2,class:h(`${n}-list-item__main`)},[g(()=>t.default())],2)):g(()=>null),t.suffix?(e(),i(`div`,{key:4,class:h(`${n}-list-item__suffix`)},[g(()=>t.suffix())],2)):g(()=>null),g(()=>this.showDivider&&(e(),i(`div`,{class:h(`${n}-list-item__divider`)},null,2)))],2)}});export{E as n,D as t};