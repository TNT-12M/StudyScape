import{$ as e,z as t}from"./php-modules-GekPVnxA.js";import{L as n,Nt as r,X as i,d as a,f as o,h as s,p as c,ut as l,x as u,y as d}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as f,F as p,I as m,Xt as h,Yt as g,at as _,ct as v,d as y,on as b,pt as x,tn as S,u as C}from"./Button-BtFx7EYM.js";import{r as w}from"./cssr-BRvHbqtS.js";import{n as T}from"./light-hjFAsF69.js";var E=g([g(`@keyframes spin-rotate`,`
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
 `,[t()])]),h(`spin-body`,`
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
 `)])]),D={small:20,medium:18,large:16},O={...p.props,contentClass:String,contentStyle:[Object,String],description:String,size:{type:[String,Number],default:`medium`},show:{type:Boolean,default:!0},rotate:{type:Boolean,default:!0},spinning:{type:Boolean,validator:()=>!0,default:void 0},delay:Number,...y,strokeWidth:Number},k=u({name:`Spin`,props:O,slots:Object,setup(t){let{mergedClsPrefixRef:n,inlineThemeDisabled:r}=x(t),o=p(`Spin`,`-spin`,E,T,t,n),s=a(()=>{let{size:n}=t,{common:{cubicBezierEaseInOut:r},self:i}=o.value,{opacitySpinning:a,color:s,textColor:c}=i;return{"--n-bezier":r,"--n-opacity-spinning":a,"--n-size":typeof n==`number`?e(n):i[S(`size`,n)],"--n-color":s,"--n-text-color":c}}),c=r?m(`spin`,a(()=>{let{size:e}=t;return typeof e==`number`?String(e):e[0]}),s,t):void 0,u=w(t,[`spinning`,`show`]),d=l(!1);return i(e=>{let n;if(u.value){let{delay:r}=t;if(r){n=window.setTimeout(()=>{d.value=!0},r),e(()=>{clearTimeout(n)});return}}d.value=u.value}),{mergedClsPrefix:n,active:d,mergedStrokeWidth:a(()=>{let{strokeWidth:e}=t;if(e!==void 0)return e;let{size:n}=t;return D[typeof n==`number`?`medium`:n]}),cssVars:r?void 0:s,themeClass:c?.themeClass,onRender:c?.onRender}},render(){let{$slots:e,mergedClsPrefix:t,description:i}=this,a=e.icon&&this.rotate,l=(i||e.description)&&(n(),s(`div`,{class:_(`${t}-spin-description`)},[v(()=>i||e.description?.())],2)),u=e.icon?(n(),s(`div`,{key:1,class:_([`${t}-spin-body`,this.themeClass])},[o(`div`,{class:_([`${t}-spin`,a&&`${t}-spin--rotate`]),style:r(e.default?``:this.cssVars)},[v(()=>e.icon())],6),v(()=>l)],2)):(n(),s(`div`,{key:2,class:_([`${t}-spin-body`,this.themeClass])},[(n(),c(C,{clsPrefix:t,style:r(e.default?``:this.cssVars),stroke:this.stroke,"stroke-width":this.mergedStrokeWidth,radius:this.radius,scale:this.scale,class:_(`${t}-spin`)},null,8,[`clsPrefix`,`style`,`stroke`,`stroke-width`,`radius`,`scale`,`class`])),v(()=>l)],2));return this.onRender?.(),e.default?(n(),s(`div`,{key:3,class:_([`${t}-spin-container`,this.themeClass]),style:r(this.cssVars)},[o(`div`,{class:_([`${t}-spin-content`,this.active&&`${t}-spin-content--spinning`,this.contentClass]),style:r(this.contentStyle)},[v(()=>e.default?.())],6),d(b,{name:`fade-in-transition`},{default:()=>this.active?u:null},1024)],6)):u}});export{k as t};