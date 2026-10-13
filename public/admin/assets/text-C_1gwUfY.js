import{L as e,Nt as t,a as n,d as r,h as i,w as a,x as o}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as s,F as c,I as l,Xt as u,at as d,ct as f,pt as p,tn as m}from"./Button-BtFx7EYM.js";import{r as h}from"./cssr-BRvHbqtS.js";import{n as g}from"./light-Dzur06Mh.js";var _=u(`text`,`
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
 `)]),v={...c.props,code:Boolean,type:{type:String,default:`default`},delete:Boolean,strong:Boolean,italic:Boolean,underline:Boolean,depth:[String,Number],tag:String,as:{type:String,validator:()=>!0,default:void 0}},y=o({name:`Text`,props:v,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n}=p(e),i=c(`Typography`,`-text`,_,g,e,t),a=r(()=>{let{depth:t,type:n}=e,r=n==="default"?t===void 0?`textColor`:`textColor${t}Depth`:m(`textColor`,n),{common:{fontWeightStrong:a,fontFamilyMono:o,cubicBezierEaseInOut:s},self:{codeTextColor:c,codeBorderRadius:l,codeColor:u,codeBorder:d,[r]:f}}=i.value;return{"--n-bezier":s,"--n-text-color":f,"--n-font-weight-strong":a,"--n-font-famliy-mono":o,"--n-code-border-radius":l,"--n-code-text-color":c,"--n-code-color":u,"--n-code-border":d}}),o=n?l(`text`,r(()=>`${e.type[0]}${e.depth||``}`),a,e):void 0;return{mergedClsPrefix:t,compitableTag:h(e,[`as`,`tag`]),cssVars:n?void 0:a,themeClass:o?.themeClass,onRender:o?.onRender}},render(){let{mergedClsPrefix:r}=this;this.onRender?.();let o=[`${r}-text`,this.themeClass,{[`${r}-text--code`]:this.code,[`${r}-text--delete`]:this.delete,[`${r}-text--strong`]:this.strong,[`${r}-text--italic`]:this.italic,[`${r}-text--underline`]:this.underline}],s=this.$slots.default?.();return this.code?(e(),i(`code`,{key:1,class:d(o),style:t(this.cssVars)},[this.delete?(e(),i(`del`,{key:0},[f(()=>s)])):(e(),i(n,{key:1},[f(()=>s)],64))],6)):this.delete?(e(),i(`del`,{key:2,class:d(o),style:t(this.cssVars)},[f(()=>s)],6)):a(this.compitableTag||`span`,{class:o,style:this.cssVars},s)}});export{y as t};