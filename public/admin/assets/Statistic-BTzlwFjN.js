import{F as e,d as t,i as n,kt as r,m as i,u as a,y as o}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{C as s,F as c,I as l,O as u,Qt as d,Xt as f,at as p,ct as m,pt as h}from"./Button-CjW_3_5b.js";import{n as g}from"./light-CmbsRQsU.js";var _=f(`statistic`,[d(`label`,`
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
 `,[f(`icon`,{verticalAlign:`-0.125em`})])])]),v={...c.props,tabularNums:Boolean,label:String,value:[String,Number]},y=o({name:`Statistic`,props:v,slots:Object,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n,mergedRtlRef:r}=h(e),i=c(`Statistic`,`-statistic`,_,g,e,t),o=s(`Statistic`,r,t),u=a(()=>{let{self:{labelFontWeight:e,valueFontSize:t,valueFontWeight:n,valuePrefixTextColor:r,labelTextColor:a,valueSuffixTextColor:o,valueTextColor:s,labelFontSize:c},common:{cubicBezierEaseInOut:l}}=i.value;return{"--n-bezier":l,"--n-label-font-size":c,"--n-label-font-weight":e,"--n-label-text-color":a,"--n-value-font-weight":n,"--n-value-font-size":t,"--n-value-prefix-text-color":r,"--n-value-suffix-text-color":o,"--n-value-text-color":s}}),d=n?l(`statistic`,void 0,u,e):void 0;return{rtlEnabled:o,mergedClsPrefix:t,cssVars:n?void 0:u,themeClass:d?.themeClass,onRender:d?.onRender}},render(){let{mergedClsPrefix:a,$slots:{default:o,label:s,prefix:c,suffix:l}}=this;return this.onRender?.(),e(),i(`div`,{class:p([`${a}-statistic`,this.themeClass,this.rtlEnabled&&`${a}-statistic--rtl`]),style:r(this.cssVars)},[m(()=>u(s,t=>(e(),i(`div`,{class:p(`${a}-statistic__label`)},[m(()=>this.label||t)],2)))),t(`div`,{class:p(`${a}-statistic-value`),style:r({fontVariantNumeric:this.tabularNums?`tabular-nums`:``})},[m(()=>u(c,t=>t&&(e(),i(`span`,{class:p(`${a}-statistic-value__prefix`)},[m(()=>t)],2)))),this.value===void 0?(e(),i(n,{key:1},[m(()=>u(o,t=>t&&(e(),i(`span`,{class:p(`${a}-statistic-value__content`)},[m(()=>t)],2))))],64)):(e(),i(`span`,{key:0,class:p(`${a}-statistic-value__content`)},[m(()=>this.value)],2)),m(()=>u(l,t=>t&&(e(),i(`span`,{class:p(`${a}-statistic-value__suffix`)},[m(()=>t)],2))))],6)],6)}});export{y as t};