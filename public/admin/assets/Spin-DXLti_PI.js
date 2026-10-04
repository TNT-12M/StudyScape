import{I as e,Q as t}from"./php-modules-BAZCMuH3.js";import{F as n,_ as r,d as i,f as a,kt as o,m as s,ot as c,q as l,u,y as d}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as f,F as p,I as m,Xt as h,Yt as g,at as _,ct as v,d as y,on as b,pt as x,tn as S,u as C}from"./Button-CjW_3_5b.js";import{r as w}from"./cssr-DSJD8hf8.js";import{n as T}from"./light-DnrU9fK9.js";var E=g([g(`@keyframes spin-rotate`,`
 from {
 transform: rotate(0);
 }
 to {
 transform: rotate(360deg);
 }
 `),h(`spin-container`,`
 position: relative;
 `,[h(`spin-body`,`
 position: absolute;
 top: 50%;
 left: 50%;
 transform: translateX(-50%) translateY(-50%);
 `,[e()])]),h(`spin-body`,`
 display: inline-flex;
 align-items: center;
 justify-content: center;
 flex-direction: column;
 `),h(`spin`,`
 display: inline-flex;
 height: var(--n-size);
 width: var(--n-size);
 font-size: var(--n-size);
 color: var(--n-color);
 `,[f(`rotate`,`
 animation: spin-rotate 2s linear infinite;
 `)]),h(`spin-description`,`
 display: inline-block;
 font-size: var(--n-font-size);
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 margin-top: 8px;
 `),h(`spin-content`,`
 opacity: 1;
 transition: opacity .3s var(--n-bezier);
 pointer-events: all;
 `,[f(`spinning`,`
 user-select: none;
 -webkit-user-select: none;
 pointer-events: none;
 opacity: var(--n-opacity-spinning);
 `)])]),D={small:20,medium:18,large:16},O={...p.props,contentClass:String,contentStyle:[Object,String],description:String,size:{type:[String,Number],default:`medium`},show:{type:Boolean,default:!0},rotate:{type:Boolean,default:!0},spinning:{type:Boolean,validator:()=>!0,default:void 0},delay:Number,...y,strokeWidth:Number},k=d({name:`Spin`,props:O,slots:Object,setup(e){let{mergedClsPrefixRef:n,inlineThemeDisabled:r}=x(e),i=p(`Spin`,`-spin`,E,T,e,n),a=u(()=>{let{size:n}=e,{common:{cubicBezierEaseInOut:r},self:a}=i.value,{opacitySpinning:o,color:s,textColor:c}=a;return{"--n-bezier":r,"--n-opacity-spinning":o,"--n-size":typeof n==`number`?t(n):a[S(`size`,n)],"--n-color":s,"--n-text-color":c}}),o=r?m(`spin`,u(()=>{let{size:t}=e;return typeof t==`number`?String(t):t[0]}),a,e):void 0,s=w(e,[`spinning`,`show`]),d=c(!1);return l(t=>{let n;if(s.value){let{delay:r}=e;if(r){n=window.setTimeout(()=>{d.value=!0},r),t(()=>{clearTimeout(n)});return}}d.value=s.value}),{mergedClsPrefix:n,active:d,mergedStrokeWidth:u(()=>{let{strokeWidth:t}=e;if(t!==void 0)return t;let{size:n}=e;return D[typeof n==`number`?`medium`:n]}),cssVars:r?void 0:a,themeClass:o?.themeClass,onRender:o?.onRender}},render(){let{$slots:e,mergedClsPrefix:t,description:c}=this,l=e.icon&&this.rotate,u=(c||e.description)&&(n(),s(`div`,{class:_(`${t}-spin-description`)},[v(()=>c||e.description?.())],2)),d=e.icon?(n(),s(`div`,{key:1,class:_([`${t}-spin-body`,this.themeClass])},[i(`div`,{class:_([`${t}-spin`,l&&`${t}-spin--rotate`]),style:o(e.default?``:this.cssVars)},[v(()=>e.icon())],6),v(()=>u)],2)):(n(),s(`div`,{key:2,class:_([`${t}-spin-body`,this.themeClass])},[(n(),a(C,{clsPrefix:t,style:o(e.default?``:this.cssVars),stroke:this.stroke,"stroke-width":this.mergedStrokeWidth,radius:this.radius,scale:this.scale,class:_(`${t}-spin`)},null,8,[`clsPrefix`,`style`,`stroke`,`stroke-width`,`radius`,`scale`,`class`])),v(()=>u)],2));return this.onRender?.(),e.default?(n(),s(`div`,{key:3,class:_([`${t}-spin-container`,this.themeClass]),style:o(this.cssVars)},[i(`div`,{class:_([`${t}-spin-content`,this.active&&`${t}-spin-content--spinning`,this.contentClass]),style:o(this.contentStyle)},[v(()=>e.default?.())],6),r(b,{name:`fade-in-transition`},{default:()=>this.active?d:null},1024)],6)):d}});export{k as t};