import{L as e,Nt as t,a as n,d as r,f as i,h as a,x as o}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{C as s,F as c,I as l,O as u,Qt as d,Xt as f,at as p,ct as m,pt as h}from"./Button-BtFx7EYM.js";import{n as g}from"./light-B1DJIAzJ.js";var _=f(`statistic`,[d(`label`,`
 font-weight: var(--n-label-font-weight);
 transition: .3s color var(--n-bezier);
 font-size: var(--n-label-font-size);
 color: var(--n-label-text-color);
 `),f(`statistic-value`,`
 margin-top: 4px;
 font-weight: var(--n-value-font-weight);
 `,[d(`prefix`,`
 margin: 0 4px 0 0;
 font-size: var(--n-value-font-size);
 transition: .3s color var(--n-bezier);
 color: var(--n-value-prefix-text-color);
 `,[f(`icon`,{verticalAlign:`-0.125em`})]),d(`content`,`
 font-size: var(--n-value-font-size);
 transition: .3s color var(--n-bezier);
 color: var(--n-value-text-color);
 `),d(`suffix`,`
 margin: 0 0 0 4px;
 font-size: var(--n-value-font-size);
 transition: .3s color var(--n-bezier);
 color: var(--n-value-suffix-text-color);
 `,[f(`icon`,{verticalAlign:`-0.125em`})])])]),v={...c.props,tabularNums:Boolean,label:String,value:[String,Number]},y=o({name:`Statistic`,props:v,slots:Object,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n,mergedRtlRef:i}=h(e),a=c(`Statistic`,`-statistic`,_,g,e,t),o=s(`Statistic`,i,t),u=r(()=>{let{self:{labelFontWeight:e,valueFontSize:t,valueFontWeight:n,valuePrefixTextColor:r,labelTextColor:i,valueSuffixTextColor:o,valueTextColor:s,labelFontSize:c},common:{cubicBezierEaseInOut:l}}=a.value;return{"--n-bezier":l,"--n-label-font-size":c,"--n-label-font-weight":e,"--n-label-text-color":i,"--n-value-font-weight":n,"--n-value-font-size":t,"--n-value-prefix-text-color":r,"--n-value-suffix-text-color":o,"--n-value-text-color":s}}),d=n?l(`statistic`,void 0,u,e):void 0;return{rtlEnabled:o,mergedClsPrefix:t,cssVars:n?void 0:u,themeClass:d?.themeClass,onRender:d?.onRender}},render(){let{mergedClsPrefix:r,$slots:{default:o,label:s,prefix:c,suffix:l}}=this;return this.onRender?.(),e(),a(`div`,{class:p([`${r}-statistic`,this.themeClass,this.rtlEnabled&&`${r}-statistic--rtl`]),style:t(this.cssVars)},[m(()=>u(s,t=>(e(),a(`div`,{class:p(`${r}-statistic__label`)},[m(()=>this.label||t)],2)))),i(`div`,{class:p(`${r}-statistic-value`),style:t({fontVariantNumeric:this.tabularNums?`tabular-nums`:``})},[m(()=>u(c,t=>t&&(e(),a(`span`,{class:p(`${r}-statistic-value__prefix`)},[m(()=>t)],2)))),this.value===void 0?(e(),a(n,{key:1},[m(()=>u(o,t=>t&&(e(),a(`span`,{class:p(`${r}-statistic-value__content`)},[m(()=>t)],2))))],64)):(e(),a(`span`,{key:0,class:p(`${r}-statistic-value__content`)},[m(()=>this.value)],2)),m(()=>u(l,t=>t&&(e(),a(`span`,{class:p(`${r}-statistic-value__suffix`)},[m(()=>t)],2))))],6)],6)}});export{y as t};