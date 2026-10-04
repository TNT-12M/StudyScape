import{F as e,d as t,i as n,kt as r,m as i,u as a,y as o}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as s,F as c,I as l,Qt as u,Xt as d,at as f,ct as p,en as m,pt as h}from"./Button-CjW_3_5b.js";import{t as g}from"./light-yk4iBDG7.js";var _=d(`divider`,`
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
 `),m(`dashed`,[u(`line`,{backgroundColor:`var(--n-color)`})]),s(`dashed`,[u(`line`,{borderColor:`var(--n-color)`})]),s(`vertical`,{backgroundColor:`var(--n-color)`})]),v={...c.props,titlePlacement:{type:String,default:`center`},dashed:Boolean,vertical:Boolean},y=o({name:`Divider`,props:v,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=h(e),r=c(`Divider`,`-divider`,_,g,e,t),i=a(()=>{let{common:{cubicBezierEaseInOut:e},self:{color:t,textColor:n,fontWeight:i}}=r.value;return{"--n-bezier":e,"--n-color":t,"--n-text-color":n,"--n-font-weight":i}}),o=n?l(`divider`,void 0,i,e):void 0;return{mergedClsPrefix:t,cssVars:n?void 0:i,themeClass:o?.themeClass,onRender:o?.onRender}},render(){let{$slots:a,titlePlacement:o,vertical:s,dashed:c,cssVars:l,mergedClsPrefix:u}=this;return this.onRender?.(),e(),i(`div`,{role:`separator`,class:f([`${u}-divider`,this.themeClass,{[`${u}-divider--vertical`]:s,[`${u}-divider--no-title`]:!a.default,[`${u}-divider--dashed`]:c,[`${u}-divider--title-position-${o}`]:a.default&&o}]),style:r(l)},[s?p(()=>null):(e(),i(`div`,{key:0,class:f(`${u}-divider__line ${u}-divider__line--left`)},null,2)),!s&&a.default?(e(),i(n,{key:2},[t(`div`,{class:f(`${u}-divider__title`)},[p(()=>this.$slots.default?.())],2),t(`div`,{class:f(`${u}-divider__line ${u}-divider__line--right`)},null,2)],64)):p(()=>null)],6)}});export{y as t};