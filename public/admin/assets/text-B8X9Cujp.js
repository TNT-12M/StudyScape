import{F as e,S as t,i as n,kt as r,m as i,u as a,y as o}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as s,F as c,I as l,Xt as u,at as d,ct as f,pt as p,tn as m}from"./Button-CjW_3_5b.js";import{r as h}from"./cssr-DQVwT7QU.js";import{n as g}from"./light-Csm0iG69.js";var _=u(`text`,`
 transition: color .3s var(--n-bezier);
 color: var(--n-text-color);
`,[s(`strong`,`
 font-weight: var(--n-font-weight-strong);
 `),s(`italic`,{fontStyle:`italic`}),s(`underline`,{textDecoration:`underline`}),s(`code`,`
 line-height: 1.4;
 display: inline-block;
 font-family: var(--n-font-famliy-mono);
 transition: 
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 box-sizing: border-box;
 padding: .05em .35em 0 .35em;
 border-radius: var(--n-code-border-radius);
 font-size: .9em;
 color: var(--n-code-text-color);
 background-color: var(--n-code-color);
 border: var(--n-code-border);
 `)]),v={...c.props,code:Boolean,type:{type:String,default:`default`},delete:Boolean,strong:Boolean,italic:Boolean,underline:Boolean,depth:[String,Number],tag:String,as:{type:String,validator:()=>!0,default:void 0}},y=o({name:`Text`,props:v,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=p(e),r=c(`Typography`,`-text`,_,g,e,t),i=a(()=>{let{depth:t,type:n}=e,i=n==="default"?t===void 0?`textColor`:`textColor${t}Depth`:m(`textColor`,n),{common:{fontWeightStrong:a,fontFamilyMono:o,cubicBezierEaseInOut:s},self:{codeTextColor:c,codeBorderRadius:l,codeColor:u,codeBorder:d,[i]:f}}=r.value;return{"--n-bezier":s,"--n-text-color":f,"--n-font-weight-strong":a,"--n-font-famliy-mono":o,"--n-code-border-radius":l,"--n-code-text-color":c,"--n-code-color":u,"--n-code-border":d}}),o=n?l(`text`,a(()=>`${e.type[0]}${e.depth||``}`),i,e):void 0;return{mergedClsPrefix:t,compitableTag:h(e,[`as`,`tag`]),cssVars:n?void 0:i,themeClass:o?.themeClass,onRender:o?.onRender}},render(){let{mergedClsPrefix:a}=this;this.onRender?.();let o=[`${a}-text`,this.themeClass,{[`${a}-text--code`]:this.code,[`${a}-text--delete`]:this.delete,[`${a}-text--strong`]:this.strong,[`${a}-text--italic`]:this.italic,[`${a}-text--underline`]:this.underline}],s=this.$slots.default?.();return this.code?(e(),i(`code`,{key:1,class:d(o),style:r(this.cssVars)},[this.delete?(e(),i(`del`,{key:0},[f(()=>s)])):(e(),i(n,{key:1},[f(()=>s)],64))],6)):this.delete?(e(),i(`del`,{key:2,class:d(o),style:r(this.cssVars)},[f(()=>s)],6)):t(this.compitableTag||`span`,{class:o,style:this.cssVars},s)}});export{y as t};