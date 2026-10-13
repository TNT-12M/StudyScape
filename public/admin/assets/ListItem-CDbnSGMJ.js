import{E as e,L as t,Nt as n,d as r,h as i,ht as a,x as o,z as s}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as c,C as l,F as u,I as d,Qt as f,Xt as p,Yt as m,at as h,ct as g,gt as _,nn as v,pt as y,rn as b,vt as x}from"./Button-BtFx7EYM.js";import{t as S}from"./light-BSfIGUdi.js";var C=m([p(`list`,`
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
 `))]),w={...u.props,size:{type:String,default:`medium`},bordered:Boolean,clickable:Boolean,hoverable:Boolean,showDivider:{type:Boolean,default:!0}},T=_(`n-list`),E=o({name:`List`,props:w,slots:Object,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n,mergedRtlRef:i}=y(e),o=l(`List`,i,t),c=u(`List`,`-list`,C,S,e,t);s(T,{showDividerRef:a(e,`showDivider`),mergedClsPrefixRef:t});let f=r(()=>{let{common:{cubicBezierEaseInOut:e},self:{fontSize:t,textColor:n,color:r,colorModal:i,colorPopover:a,borderColor:o,borderColorModal:s,borderColorPopover:l,borderRadius:u,colorHover:d,colorHoverModal:f,colorHoverPopover:p}}=c.value;return{"--n-font-size":t,"--n-bezier":e,"--n-text-color":n,"--n-color":r,"--n-border-radius":u,"--n-border-color":o,"--n-border-color-modal":s,"--n-border-color-popover":l,"--n-color-modal":i,"--n-color-popover":a,"--n-color-hover":d,"--n-color-hover-modal":f,"--n-color-hover-popover":p}}),p=n?d(`list`,void 0,f,e):void 0;return{mergedClsPrefix:t,rtlEnabled:o,cssVars:n?void 0:f,themeClass:p?.themeClass,onRender:p?.onRender}},render(){let{$slots:e,mergedClsPrefix:r,onRender:a}=this;return a?.(),t(),i(`ul`,{class:h([`${r}-list`,this.rtlEnabled&&`${r}-list--rtl`,this.bordered&&`${r}-list--bordered`,this.showDivider&&`${r}-list--show-divider`,this.hoverable&&`${r}-list--hoverable`,this.clickable&&`${r}-list--clickable`,this.themeClass]),style:n(this.cssVars)},[e.header?(t(),i(`div`,{key:0,class:h(`${r}-list__header`)},[g(()=>e.header())],2)):g(()=>null),g(()=>e.default?.()),e.footer?(t(),i(`div`,{key:2,class:h(`${r}-list__footer`)},[g(()=>e.footer())],2)):g(()=>null)],6)}}),D=o({name:`ListItem`,slots:Object,setup(){let t=e(T,null);return t||x(`list-item`,"`n-list-item` must be placed in `n-list`."),{showDivider:t.showDividerRef,mergedClsPrefix:t.mergedClsPrefixRef}},render(){let{$slots:e,mergedClsPrefix:n}=this;return t(),i(`li`,{class:h(`${n}-list-item`)},[e.prefix?(t(),i(`div`,{key:0,class:h(`${n}-list-item__prefix`)},[g(()=>e.prefix())],2)):g(()=>null),e.default?(t(),i(`div`,{key:2,class:h(`${n}-list-item__main`)},[g(()=>e.default())],2)):g(()=>null),e.suffix?(t(),i(`div`,{key:4,class:h(`${n}-list-item__suffix`)},[g(()=>e.suffix())],2)):g(()=>null),g(()=>this.showDivider&&(t(),i(`div`,{class:h(`${n}-list-item__divider`)},null,2)))],2)}});export{E as n,D as t};