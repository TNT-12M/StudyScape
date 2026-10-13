import{V as e,Y as t}from"./php-modules-GekPVnxA.js";import{L as n,Nt as r,d as i,f as a,h as o,x as s}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as c,F as l,I as u,Qt as d,Xt as f,Yt as p,at as m,ct as h,en as g,nn as _,pt as v,rn as y,tn as b}from"./Button-BtFx7EYM.js";import{n as x}from"./AppPage-BwDOpiOS.js";import{r as S}from"./cssr-BRvHbqtS.js";import{t as C}from"./light-BQ4GAXf3.js";function w(e,t=`default`,n=[]){let{children:r}=e;if(typeof r==`object`&&r&&!Array.isArray(r)){let e=r[t];if(typeof e==`function`)return e()}return n}var T=p([f(`descriptions`,{fontSize:`var(--n-font-size)`},[f(`descriptions-separator`,`
 display: inline-block;
 margin: 0 8px 0 2px;
 `),f(`descriptions-table-wrapper`,[f(`descriptions-table`,[f(`descriptions-table-row`,[f(`descriptions-table-header`,{padding:`var(--n-th-padding)`}),f(`descriptions-table-content`,{padding:`var(--n-td-padding)`})])])]),g(`bordered`,[f(`descriptions-table-wrapper`,[f(`descriptions-table`,[f(`descriptions-table-row`,[p(`&:last-child`,[f(`descriptions-table-content`,{paddingBottom:0})])])])])]),c(`left-label-placement`,[f(`descriptions-table-content`,[p(`> *`,{verticalAlign:`top`})])]),c(`left-label-align`,[p(`th`,{textAlign:`left`})]),c(`center-label-align`,[p(`th`,{textAlign:`center`})]),c(`right-label-align`,[p(`th`,{textAlign:`right`})]),c(`bordered`,[f(`descriptions-table-wrapper`,`
 border-radius: var(--n-border-radius);
 overflow: hidden;
 background: var(--n-merged-td-color);
 border: 1px solid var(--n-merged-border-color);
 `,[f(`descriptions-table`,[f(`descriptions-table-row`,[p(`&:not(:last-child)`,[f(`descriptions-table-content`,{borderBottom:`1px solid var(--n-merged-border-color)`}),f(`descriptions-table-header`,{borderBottom:`1px solid var(--n-merged-border-color)`})]),f(`descriptions-table-header`,`
 font-weight: 400;
 background-clip: padding-box;
 background-color: var(--n-merged-th-color);
 `,[p(`&:not(:last-child)`,{borderRight:`1px solid var(--n-merged-border-color)`})]),f(`descriptions-table-content`,[p(`&:not(:last-child)`,{borderRight:`1px solid var(--n-merged-border-color)`})])])])])]),f(`descriptions-header`,`
 font-weight: var(--n-th-font-weight);
 font-size: 18px;
 transition: color .3s var(--n-bezier);
 line-height: var(--n-line-height);
 margin-bottom: 16px;
 color: var(--n-title-text-color);
 `),f(`descriptions-table-wrapper`,`
 transition:
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[f(`descriptions-table`,`
 width: 100%;
 border-collapse: separate;
 border-spacing: 0;
 box-sizing: border-box;
 `,[f(`descriptions-table-row`,`
 box-sizing: border-box;
 transition: border-color .3s var(--n-bezier);
 `,[f(`descriptions-table-header`,`
 font-weight: var(--n-th-font-weight);
 line-height: var(--n-line-height);
 display: table-cell;
 box-sizing: border-box;
 color: var(--n-th-text-color);
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `),f(`descriptions-table-content`,`
 vertical-align: top;
 line-height: var(--n-line-height);
 display: table-cell;
 box-sizing: border-box;
 color: var(--n-td-text-color);
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[d(`content`,`
 transition: color .3s var(--n-bezier);
 display: inline-block;
 color: var(--n-td-text-color);
 `)]),d(`label`,`
 font-weight: var(--n-th-font-weight);
 transition: color .3s var(--n-bezier);
 display: inline-block;
 margin-right: 14px;
 color: var(--n-th-text-color);
 `)])])])]),f(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color);
 --n-merged-td-color: var(--n-td-color);
 --n-merged-border-color: var(--n-border-color);
 `),_(f(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color-modal);
 --n-merged-td-color: var(--n-td-color-modal);
 --n-merged-border-color: var(--n-border-color-modal);
 `)),y(f(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color-popover);
 --n-merged-td-color: var(--n-td-color-popover);
 --n-merged-border-color: var(--n-border-color-popover);
 `))]),E=`DESCRIPTION_ITEM_FLAG`;function D(e){return typeof e==`object`&&e&&!Array.isArray(e)?e.type&&e.type.DESCRIPTION_ITEM_FLAG:!1}var O=[`colspan`],k=[`colspan`],A=[`colspan`],j=[`colspan`],M={...l.props,title:String,column:{type:Number,default:3},columns:Number,labelPlacement:{type:String,default:`top`},labelAlign:{type:String,default:`left`},separator:{type:String,default:`:`},size:String,bordered:Boolean,labelClass:String,labelStyle:[Object,String],contentClass:String,contentStyle:[Object,String]},N=s({name:`Descriptions`,props:M,slots:Object,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n,mergedComponentPropsRef:r}=v(e),a=i(()=>e.size||r?.value?.Descriptions?.size||`medium`),o=l(`Descriptions`,`-descriptions`,T,C,e,t),s=i(()=>{let{bordered:t}=e,n=a.value,{common:{cubicBezierEaseInOut:r},self:{titleTextColor:i,thColor:s,thColorModal:c,thColorPopover:l,thTextColor:u,thFontWeight:d,tdTextColor:f,tdColor:p,tdColorModal:m,tdColorPopover:h,borderColor:g,borderColorModal:_,borderColorPopover:v,borderRadius:y,lineHeight:x,[b(`fontSize`,n)]:S,[b(t?`thPaddingBordered`:`thPadding`,n)]:C,[b(t?`tdPaddingBordered`:`tdPadding`,n)]:w}}=o.value;return{"--n-title-text-color":i,"--n-th-padding":C,"--n-td-padding":w,"--n-font-size":S,"--n-bezier":r,"--n-th-font-weight":d,"--n-line-height":x,"--n-th-text-color":u,"--n-td-text-color":f,"--n-th-color":s,"--n-th-color-modal":c,"--n-th-color-popover":l,"--n-td-color":p,"--n-td-color-modal":m,"--n-td-color-popover":h,"--n-border-radius":y,"--n-border-color":g,"--n-border-color-modal":_,"--n-border-color-popover":v}}),c=n?u(`descriptions`,i(()=>{let t=``,{bordered:n}=e;return n&&(t+=`a`),t+=a.value[0],t}),s,e):void 0;return{mergedClsPrefix:t,cssVars:n?void 0:s,themeClass:c?.themeClass,onRender:c?.onRender,compitableColumn:S(e,[`columns`,`column`]),inlineThemeDisabled:n,mergedSize:a}},render(){let i=this.$slots.default,s=i?e(i()):[];s.length;let{contentClass:c,labelClass:l,compitableColumn:u,labelPlacement:d,labelAlign:f,mergedSize:p,bordered:g,title:_,cssVars:v,mergedClsPrefix:y,separator:b,onRender:S}=this;S?.();let C=s.filter(e=>D(e)),T=C.reduce((e,t,i)=>{let s=t.props||{},f=C.length-1===i,p=[`label`in s?s.label:w(t,`label`)],_=[w(t)],v=s.span||1,x=e.span;e.span+=v;let S=s.labelStyle||s[`label-style`]||this.labelStyle,T=s.contentStyle||s[`content-style`]||this.contentStyle;if(d===`left`)g?e.row.push((n(),o(`th`,{key:1,class:m([`${y}-descriptions-table-header`,l]),colspan:1,style:r(S)},[h(()=>p)],6)),(n(),o(`td`,{key:2,class:m([`${y}-descriptions-table-content`,c]),colspan:f?(u-x)*2+1:v*2-1,style:r(T)},[h(()=>_)],14,O))):e.row.push((n(),o(`td`,{key:3,class:m(`${y}-descriptions-table-content`),colspan:f?(u-x)*2:v*2},[a(`span`,{class:m([`${y}-descriptions-table-content__label`,l]),style:r(S)},[h(()=>[...p,b&&(n(),o(`span`,{key:4,class:m(`${y}-descriptions-separator`)},[h(()=>b)],2))])],6),a(`span`,{class:m([`${y}-descriptions-table-content__content`,c]),style:r(T)},[h(()=>_)],6)],10,k)));else{let t=f?(u-x)*2:v*2;e.row.push((n(),o(`th`,{key:5,class:m([`${y}-descriptions-table-header`,l]),colspan:t,style:r(S)},[h(()=>p)],14,A))),e.secondRow.push((n(),o(`td`,{key:6,class:m([`${y}-descriptions-table-content`,c]),colspan:t,style:r(T)},[h(()=>_)],14,j)))}return(e.span>=u||f)&&(e.span=0,e.row.length&&(e.rows.push(e.row),e.row=[]),d!==`left`&&e.secondRow.length&&(e.rows.push(e.secondRow),e.secondRow=[])),e},{span:0,row:[],secondRow:[],rows:[]}).rows.map(e=>(n(),o(`tr`,{class:m(`${y}-descriptions-table-row`)},[h(()=>e)],2)));return n(),o(`div`,{style:r(v),class:m([`${y}-descriptions`,this.themeClass,`${y}-descriptions--${d}-label-placement`,`${y}-descriptions--${f}-label-align`,`${y}-descriptions--${p}-size`,g&&`${y}-descriptions--bordered`])},[_||this.$slots.header?(n(),o(`div`,{key:0,class:m(`${y}-descriptions-header`)},[h(()=>_||x(this,`header`))],2)):h(()=>null),a(`div`,{class:m(`${y}-descriptions-table-wrapper`)},[a(`table`,{class:m(`${y}-descriptions-table`)},[a(`tbody`,null,[h(()=>d===`top`&&(n(),o(`tr`,{class:m(`${y}-descriptions-table-row`),style:{visibility:`collapse`}},[h(()=>t(u*2,(n(),o(`td`))))],2))),h(()=>T)])],2)],2)],6)}}),P={label:String,span:{type:Number,default:1},labelClass:String,labelStyle:[Object,String],contentClass:String,contentStyle:[Object,String]},F=s({name:`DescriptionsItem`,[E]:!0,props:P,slots:Object,render(){return null}});export{N as n,F as t};