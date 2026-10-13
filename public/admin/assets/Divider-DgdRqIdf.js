import{L as e,Nt as t,a as n,d as r,f as i,h as a,x as o}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as s,F as c,I as l,Qt as u,Xt as d,at as f,ct as p,en as m,pt as h}from"./Button-BtFx7EYM.js";import{t as g}from"./light-7NAseEVc.js";var _=d(`divider`,`
 position: relative;
 display: flex;
 width: 100%;
 box-sizing: border-box;
 font-size: 16px;
 color: var(--n-text-color);
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
`,[m(`vertical`,`
 margin-top: 24px;
 margin-bottom: 24px;
 `,[m(`no-title`,`
 display: flex;
 align-items: center;
 `)]),u(`title`,`
 display: flex;
 align-items: center;
 margin-left: 12px;
 margin-right: 12px;
 white-space: nowrap;
 font-weight: var(--n-font-weight);
 `),s(`title-position-left`,[u(`line`,[s(`left`,{width:`28px`})])]),s(`title-position-right`,[u(`line`,[s(`right`,{width:`28px`})])]),s(`dashed`,[u(`line`,`
 background-color: #0000;
 height: 0px;
 width: 100%;
 border-style: dashed;
 border-width: 1px 0 0;
 `)]),s(`vertical`,`
 display: inline-block;
 height: 1em;
 margin: 0 8px;
 vertical-align: middle;
 width: 1px;
 `),u(`line`,`
 border: none;
 transition: background-color .3s var(--n-bezier), border-color .3s var(--n-bezier);
 height: 1px;
 width: 100%;
 margin: 0;
 `),m(`dashed`,[u(`line`,{backgroundColor:`var(--n-color)`})]),s(`dashed`,[u(`line`,{borderColor:`var(--n-color)`})]),s(`vertical`,{backgroundColor:`var(--n-color)`})]),v={...c.props,titlePlacement:{type:String,default:`center`},dashed:Boolean,vertical:Boolean},y=o({name:`Divider`,props:v,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=h(e),i=c(`Divider`,`-divider`,_,g,e,t),a=r(()=>{let{common:{cubicBezierEaseInOut:e},self:{color:t,textColor:n,fontWeight:r}}=i.value;return{"--n-bezier":e,"--n-color":t,"--n-text-color":n,"--n-font-weight":r}}),o=n?l(`divider`,void 0,a,e):void 0;return{mergedClsPrefix:t,cssVars:n?void 0:a,themeClass:o?.themeClass,onRender:o?.onRender}},render(){let{$slots:r,titlePlacement:o,vertical:s,dashed:c,cssVars:l,mergedClsPrefix:u}=this;return this.onRender?.(),e(),a(`div`,{role:`separator`,class:f([`${u}-divider`,this.themeClass,{[`${u}-divider--vertical`]:s,[`${u}-divider--no-title`]:!r.default,[`${u}-divider--dashed`]:c,[`${u}-divider--title-position-${o}`]:r.default&&o}]),style:t(l)},[s?p(()=>null):(e(),a(`div`,{key:0,class:f(`${u}-divider__line ${u}-divider__line--left`)},null,2)),!s&&r.default?(e(),a(n,{key:2},[i(`div`,{class:f(`${u}-divider__title`)},[p(()=>this.$slots.default?.())],2),i(`div`,{class:f(`${u}-divider__line ${u}-divider__line--right`)},null,2)],64)):p(()=>null)],6)}});export{y as t};