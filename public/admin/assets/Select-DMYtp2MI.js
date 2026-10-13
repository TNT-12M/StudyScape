import{$ as e,B as t,D as n,F as r,M as i,P as a,Q as o,X as s,et as c}from"./php-modules-GekPVnxA.js";import{$ as l,A as u,E as d,L as f,M as p,N as m,Nt as h,O as g,P as _,X as v,Y as y,a as b,d as x,f as S,h as C,ht as w,k as T,p as E,ut as D,w as O,x as k,y as A,z as j}from"./_plugin-vue_export-helper-Dgj4zzGK.js";import{$t as M,A as N,C as P,E as F,F as I,I as ee,M as L,N as te,O as R,Qt as z,Xt as B,Yt as V,at as H,ct as U,en as W,h as ne,it as G,j as re,ln as K,on as q,ot as J,pt as ie,tn as Y,u as ae,ut as X}from"./Button-BtFx7EYM.js";import{t as oe}from"./use-locale-D8UuvCme.js";import{a as Z,c as se,i as ce,l as le,o as ue,s as de,t as fe}from"./Popover-BmmAdlen.js";import{t as pe}from"./next-frame-once-qdYFoq8G.js";import{i as Q,n as me,r as he,t as ge}from"./create-BBG9Dvpd.js";import{t as _e}from"./Empty-DPTFV5uV.js";import{a as ve,d as ye,i as be,o as xe,s as $,t as Se,w as Ce}from"./light-B0o7KsWP.js";import{n as we,r as Te,t as Ee}from"./cssr-BRvHbqtS.js";import{n as De}from"./format-length-BCoLE4J2.js";import{t as Oe}from"./Tag-B_F6rbWt.js";import{t as ke}from"./attribute-CYKlYlWW.js";import{n as Ae}from"./Input-BE-RUkcn.js";function je(e){return e&-e}var Me=class{constructor(e,t){this.l=e,this.min=t;let n=Array(e+1);for(let t=0;t<e+1;++t)n[t]=0;this.ft=n}add(e,t){if(t===0)return;let{l:n,ft:r}=this;for(e+=1;e<=n;)r[e]+=t,e+=je(e)}get(e){return this.sum(e+1)-this.sum(e)}sum(e){if(e===void 0&&(e=this.l),e<=0)return 0;let{ft:t,min:n,l:r}=this;if(e>r)throw Error("[FinweckTree.sum]: `i` is larger than length.");let i=e*n;for(;e>0;)i+=t[e],e-=je(e);return i}getBound(e){let t=0,n=this.l;for(;n>t;){let r=Math.floor((t+n)/2),i=this.sum(r);if(i>e){n=r;continue}if(i<e){if(t===r)return this.sum(t+1)<=e?t+1:r;t=r}else return r}return t}},Ne;function Pe(){return typeof document>`u`?!1:(Ne===void 0&&(Ne=`matchMedia`in window&&window.matchMedia(`(pointer:coarse)`).matches),Ne)}var Fe;function Ie(){return typeof document>`u`?1:(Fe===void 0&&(Fe=`chrome`in window?window.devicePixelRatio:1),Fe)}var Le=`VVirtualListXScroll`;function Re({columnsRef:e,renderColRef:t,renderItemWithColsRef:n}){let r=D(0),i=D(0),a=x(()=>{let t=e.value;if(t.length===0)return null;let n=new Me(t.length,0);return t.forEach((e,t)=>{n.add(t,e.width)}),n}),o=L(()=>{let e=a.value;return e===null?0:Math.max(e.getBound(i.value)-1,0)}),s=e=>{let t=a.value;return t===null?0:t.sum(e)},c=L(()=>{let t=a.value;return t===null?0:Math.min(t.getBound(i.value+r.value)+1,e.value.length-1)});return j(Le,{startIndexRef:o,endIndexRef:c,columnsRef:e,renderColRef:t,renderItemWithColsRef:n,getLeft:s}),{listWidthRef:r,scrollLeftRef:i}}var ze=k({name:`VirtualListRow`,props:{index:{type:Number,required:!0},item:{type:Object,required:!0}},setup(){let{startIndexRef:e,endIndexRef:t,columnsRef:n,getLeft:r,renderColRef:i,renderItemWithColsRef:a}=d(Le);return{startIndex:e,endIndex:t,columns:n,renderCol:i,renderItemWithCols:a,getLeft:r}},render(){let{startIndex:e,endIndex:t,columns:n,renderCol:r,renderItemWithCols:i,getLeft:a,item:o}=this;if(i!=null)return i({itemIndex:this.index,startColIndex:e,endColIndex:t,allColumns:n,item:o,getLeft:a});if(r!=null){let i=[];for(let s=e;s<=t;++s){let e=n[s];i.push(r({column:e,left:a(s),item:o}))}return i}return null}}),Be=Ee(`.v-vl`,{maxHeight:`inherit`,height:`100%`,overflow:`auto`,minWidth:`1px`},[Ee(`&:not(.v-vl--show-scrollbar)`,{scrollbarWidth:`none`},[Ee(`&::-webkit-scrollbar, &::-webkit-scrollbar-track-piece, &::-webkit-scrollbar-thumb`,{width:0,height:0,display:`none`})])]),Ve=k({name:`VirtualList`,inheritAttrs:!1,props:{showScrollbar:{type:Boolean,default:!0},columns:{type:Array,default:()=>[]},renderCol:Function,renderItemWithCols:Function,items:{type:Array,default:()=>[]},itemSize:{type:Number,required:!0},itemResizable:Boolean,itemsStyle:[String,Object],visibleItemsTag:{type:[String,Object],default:`div`},visibleItemsProps:Object,ignoreItemResize:Boolean,onScroll:Function,onWheel:Function,onResize:Function,defaultScrollKey:[Number,String],defaultScrollIndex:Number,keyField:{type:String,default:`key`},paddingTop:{type:[Number,String],default:0},paddingBottom:{type:[Number,String],default:0}},setup(t){let n=X();Be.mount({id:`vueuc/virtual-list`,head:!0,anchorMetaName:we,ssr:n}),_(()=>{let{defaultScrollIndex:e,defaultScrollKey:n}=t;e==null?n!=null&&S({key:n}):S({index:e})});let r=!1,i=!1;u(()=>{if(r=!1,!i){i=!0;return}S({top:v.value,left:c.value})}),m(()=>{r=!0,i||=!0});let a=L(()=>{if(t.renderCol==null&&t.renderItemWithCols==null||t.columns.length===0)return;let e=0;return t.columns.forEach(t=>{e+=t.width}),e}),o=x(()=>{let e=new Map,{keyField:n}=t;return t.items.forEach((t,r)=>{e.set(t[n],r)}),e}),{scrollLeftRef:c,listWidthRef:l}=Re({columnsRef:w(t,`columns`),renderColRef:w(t,`renderCol`),renderItemWithColsRef:w(t,`renderItemWithCols`)}),d=D(null),f=D(void 0),p=new Map,h=x(()=>{let{items:e,itemSize:n,keyField:r}=t,i=new Me(e.length,n);return e.forEach((e,t)=>{let n=e[r],a=p.get(n);a!==void 0&&i.add(t,a)}),i}),g=D(0),v=D(0),y=L(()=>Math.max(h.value.getBound(v.value-s(t.paddingTop))-1,0)),b=x(()=>{let{value:e}=f;if(e===void 0)return[];let{items:n,itemSize:r}=t,i=y.value,a=Math.min(i+Math.ceil(e/r+1),n.length-1),o=[];for(let e=i;e<=a;++e)o.push(n[e]);return o}),S=(e,t)=>{if(typeof e==`number`){O(e,t,`auto`);return}let{left:n,top:r,index:i,key:a,position:s,behavior:c,debounce:l=!0}=e;if(n!==void 0||r!==void 0)O(n,r,c);else if(i!==void 0)E(i,c,l);else if(a!==void 0){let e=o.value.get(a);e!==void 0&&E(e,c,l)}else s===`bottom`?O(0,2**53-1,c):s===`top`&&O(0,0,c)},C,T=null;function E(e,n,r){let i=d.value;if(i==null)return;let{value:a}=h,o=a.sum(e)+s(t.paddingTop);if(!r)i.scrollTo({left:0,top:o,behavior:n});else{C=e,T!==null&&window.clearTimeout(T),T=window.setTimeout(()=>{C=void 0,T=null},16);let{scrollTop:t,offsetHeight:r}=i;if(o>t){let s=a.get(e);o+s<=t+r||i.scrollTo({left:0,top:o+s-r,behavior:n})}else i.scrollTo({left:0,top:o,behavior:n})}}function O(e,t,n){d.value?.scrollTo({left:e,top:t,behavior:n})}function k(e,n){if(r||t.ignoreItemResize||I(n.target))return;let{value:i}=h,a=o.value.get(e),s=i.get(a),c=n.borderBoxSize?.[0]?.blockSize??n.contentRect.height;if(c===s)return;c-t.itemSize===0?p.delete(e):p.set(e,c-t.itemSize);let l=c-s;if(l===0)return;i.add(a,l);let u=d.value;if(u!=null){if(C===void 0){let e=i.sum(a);u.scrollTop>e&&u.scrollBy(0,l)}else(a<C||a===C&&c+i.sum(a)>u.scrollTop+u.offsetHeight)&&u.scrollBy(0,l);F()}g.value++}let A=!Pe(),j=!1;function M(e){var n;(n=t.onScroll)==null||n.call(t,e),(!A||!j)&&F()}function N(e){var n;if((n=t.onWheel)==null||n.call(t,e),A){let t=d.value;if(t!=null){if(e.deltaX===0&&(t.scrollTop===0&&e.deltaY<=0||t.scrollTop+t.offsetHeight>=t.scrollHeight&&e.deltaY>=0))return;e.preventDefault(),t.scrollTop+=e.deltaY/Ie(),t.scrollLeft+=e.deltaX/Ie(),F(),j=!0,pe(()=>{j=!1})}}}function P(e){if(r||I(e.target))return;if(t.renderCol==null&&t.renderItemWithCols==null){if(e.contentRect.height===f.value)return}else if(e.contentRect.height===f.value&&e.contentRect.width===l.value)return;f.value=e.contentRect.height,l.value=e.contentRect.width;let{onResize:n}=t;n!==void 0&&n(e)}function F(){let{value:e}=d;e!=null&&(v.value=e.scrollTop,c.value=e.scrollLeft)}function I(e){let t=e;for(;t!==null;){if(t.style.display===`none`)return!0;t=t.parentElement}return!1}return{listHeight:f,listStyle:{overflow:`auto`},keyToIndex:o,itemsStyle:x(()=>{let{itemResizable:n}=t,r=e(h.value.sum());return g.value,[t.itemsStyle,{boxSizing:`content-box`,width:e(a.value),height:n?``:r,minHeight:n?r:``,paddingTop:e(t.paddingTop),paddingBottom:e(t.paddingBottom)}]}),visibleItemsStyle:x(()=>(g.value,{transform:`translateY(${e(h.value.sum(y.value))})`})),viewportItems:b,listElRef:d,itemsElRef:D(null),scrollTo:S,handleListResize:P,handleListScroll:M,handleListWheel:N,handleItemResize:k}},render(){let{itemResizable:e,keyField:t,keyToIndex:n,visibleItemsTag:r}=this;return O(a,{onResize:this.handleListResize},{default:()=>{var i;return O(`div`,g(this.$attrs,{class:[`v-vl`,this.showScrollbar&&`v-vl--show-scrollbar`],onScroll:this.handleListScroll,onWheel:this.handleListWheel,ref:`listElRef`}),[this.items.length===0?(i=this.$slots).empty?.call(i):O(`div`,{ref:`itemsElRef`,class:`v-vl-items`,style:this.itemsStyle},[O(r,Object.assign({class:`v-vl-visible-items`,style:this.visibleItemsStyle},this.visibleItemsProps),{default:()=>{let{renderCol:r,renderItemWithCols:i}=this;return this.viewportItems.map(o=>{let s=o[t],c=n.get(s),l=r==null?void 0:O(ze,{index:c,item:o}),u=i==null?void 0:O(ze,{index:c,item:o}),d=this.$slots.default({item:o,renderedCols:l,renderedItemWithCols:u,index:c})[0];return e?O(a,{key:s,onResize:e=>this.handleItemResize(s,e)},{default:()=>d}):(d.key=s,d)})}})])])}})}});function He(e,t){t&&(_(()=>{let{value:n}=e;n&&r.registerHandler(n,t)}),y(e,(e,t)=>{t&&r.unregisterHandler(t)},{deep:!1}),p(()=>{let{value:t}=e;t&&r.unregisterHandler(t)}))}var Ue=k({props:{onFocus:Function,onBlur:Function},setup(e){return()=>(()=>{let t=G(`d16ead82505dc285`);return f(),C(`div`,{style:`width: 0; height: 0`,tabindex:0,onFocus:t[0]||=t=>e.onFocus?.(t),onBlur:t[1]||=t=>e.onBlur?.(t)},null,32)})()}}),We=k({name:`NBaseSelectGroupHeader`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(){let{renderLabelRef:e,renderOptionRef:t,labelFieldRef:n,nodePropsRef:r}=d(le);return{labelField:n,nodeProps:r,renderLabel:e,renderOption:t}},render(){let{clsPrefix:e,renderLabel:t,renderOption:n,nodeProps:r,tmNode:{rawNode:i}}=this,a=r?.(i),o=t?t(i,!1):$(i[this.labelField],i,!1),s=(f(),C(`div`,g(a,{class:[`${e}-base-select-group-header`,a?.class]}),[U(()=>o)],16));return i.render?i.render({node:s,option:i}):n?n({node:s,option:i,selected:!1}):s}}),Ge=k({name:`Checkmark`,render(){return(()=>{let e=G(`3c84eac8ae4e1f96`);return e[0]||=S(`svg`,{xmlns:`http://www.w3.org/2000/svg`,viewBox:`0 0 16 16`},[S(`g`,{fill:`none`},[S(`path`,{d:`M14.046 3.486a.75.75 0 0 1-.032 1.06l-7.93 7.474a.85.85 0 0 1-1.188-.022l-2.68-2.72a.75.75 0 1 1 1.068-1.053l2.234 2.267l7.468-7.038a.75.75 0 0 1 1.06.032z`,fill:`currentColor`})])],-1)})()}}),Ke=[`onClick`,`onMouseenter`,`onMousemove`];function qe(e,t){return f(),E(q,{name:`fade-in-scale-up-transition`},{default:()=>e?(f(),E(te,{key:1,clsPrefix:t,class:H(`${t}-base-select-option__check`)},{default:()=>O(Ge)},1032,[`clsPrefix`,`class`])):null},1024)}var Je=k({name:`NBaseSelectOption`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(e){let{valueRef:t,pendingTmNodeRef:n,multipleRef:r,valueSetRef:i,renderLabelRef:a,renderOptionRef:o,labelFieldRef:s,valueFieldRef:c,showCheckmarkRef:l,nodePropsRef:u,handleOptionClick:f,handleOptionMouseEnter:p}=d(le),m=L(()=>{let{value:t}=n;return t?e.tmNode.key===t.key:!1});function h(t){let{tmNode:n}=e;n.disabled||f(t,n)}function g(t){let{tmNode:n}=e;n.disabled||p(t,n)}function _(t){let{tmNode:n}=e,{value:r}=m;n.disabled||r||p(t,n)}return{multiple:r,isGrouped:L(()=>{let{tmNode:t}=e,{parent:n}=t;return n&&n.rawNode.type===`group`}),showCheckmark:l,nodeProps:u,isPending:m,isSelected:L(()=>{let{value:n}=t,{value:a}=r;if(n===null)return!1;let o=e.tmNode.rawNode[c.value];if(a){let{value:e}=i;return e.has(o)}return n===o}),labelField:s,renderLabel:a,renderOption:o,handleMouseMove:_,handleMouseEnter:g,handleClick:h}},render(){let{clsPrefix:e,tmNode:{rawNode:t},isSelected:n,isPending:r,isGrouped:i,showCheckmark:a,nodeProps:o,renderOption:s,renderLabel:c,handleClick:l,handleMouseEnter:u,handleMouseMove:d}=this,p=qe(n,e),m=c?[c(t,n),a&&p]:[$(t[this.labelField],t,n),a&&p],h=o?.(t),_=(f(),C(`div`,g(h,{class:[`${e}-base-select-option`,t.class,h?.class,{[`${e}-base-select-option--disabled`]:t.disabled,[`${e}-base-select-option--selected`]:n,[`${e}-base-select-option--grouped`]:i,[`${e}-base-select-option--pending`]:r,[`${e}-base-select-option--show-checkmark`]:a}],style:[h?.style||``,t.style||``],onClick:xe([l,h?.onClick]),onMouseenter:xe([u,h?.onMouseenter]),onMousemove:xe([d,h?.onMousemove])}),[S(`div`,{class:H(`${e}-base-select-option__content`)},[U(()=>m)],2)],16,Ke));return t.render?t.render({node:_,option:t,selected:n}):s?s({node:_,option:t,selected:n}):_}}),Ye=B(`base-select-menu`,`
 line-height: 1.5;
 outline: none;
 z-index: 0;
 position: relative;
 border-radius: var(--n-border-radius);
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 background-color: var(--n-color);
`,[B(`scrollbar`,`
 max-height: var(--n-height);
 `),B(`virtual-list`,`
 max-height: var(--n-height);
 `),B(`base-select-option`,`
 min-height: var(--n-option-height);
 font-size: var(--n-option-font-size);
 display: flex;
 align-items: center;
 `,[z(`content`,`
 z-index: 1;
 white-space: nowrap;
 text-overflow: ellipsis;
 overflow: hidden;
 `)]),B(`base-select-group-header`,`
 min-height: var(--n-option-height);
 font-size: .93em;
 display: flex;
 align-items: center;
 `),B(`base-select-menu-option-wrapper`,`
 position: relative;
 width: 100%;
 `),z(`loading, empty`,`
 display: flex;
 padding: 12px 32px;
 flex: 1;
 justify-content: center;
 `),z(`loading`,`
 color: var(--n-loading-color);
 font-size: var(--n-loading-size);
 `),z(`header`,`
 padding: 8px var(--n-option-padding-left);
 font-size: var(--n-option-font-size);
 transition: 
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 border-bottom: 1px solid var(--n-action-divider-color);
 color: var(--n-action-text-color);
 `),z(`action`,`
 padding: 8px var(--n-option-padding-left);
 font-size: var(--n-option-font-size);
 transition: 
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 border-top: 1px solid var(--n-action-divider-color);
 color: var(--n-action-text-color);
 `),B(`base-select-group-header`,`
 position: relative;
 cursor: default;
 padding: var(--n-option-padding);
 color: var(--n-group-header-text-color);
 `),B(`base-select-option`,`
 cursor: pointer;
 position: relative;
 padding: var(--n-option-padding);
 transition:
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 box-sizing: border-box;
 color: var(--n-option-text-color);
 opacity: 1;
 `,[M(`show-checkmark`,`
 padding-right: calc(var(--n-option-padding-right) + 20px);
 `),V(`&::before`,`
 content: "";
 position: absolute;
 left: 4px;
 right: 4px;
 top: 0;
 bottom: 0;
 border-radius: var(--n-border-radius);
 transition: background-color .3s var(--n-bezier);
 `),V(`&:active`,`
 color: var(--n-option-text-color-pressed);
 `),M(`grouped`,`
 padding-left: calc(var(--n-option-padding-left) * 1.5);
 `),M(`pending`,[V(`&::before`,`
 background-color: var(--n-option-color-pending);
 `)]),M(`selected`,`
 color: var(--n-option-text-color-active);
 `,[V(`&::before`,`
 background-color: var(--n-option-color-active);
 `),M(`pending`,[V(`&::before`,`
 background-color: var(--n-option-color-active-pending);
 `)])]),M(`disabled`,`
 cursor: not-allowed;
 `,[W(`selected`,`
 color: var(--n-option-text-color-disabled);
 `),M(`selected`,`
 opacity: var(--n-option-opacity-disabled);
 `)]),z(`check`,`
 font-size: 16px;
 position: absolute;
 right: calc(var(--n-option-padding-right) - 4px);
 top: calc(50% - 7px);
 color: var(--n-option-check-color);
 transition: color .3s var(--n-bezier);
 `,[n({enterScale:`0.5`})])])]),Xe=[`tabindex`,`onFocusin`,`onFocusout`,`onKeyup`,`onKeydown`,`onMousedown`,`onMouseenter`,`onMouseleave`],Ze=k({name:`InternalSelectMenu`,props:{...I.props,clsPrefix:{type:String,required:!0},scrollable:{type:Boolean,default:!0},treeMate:{type:Object,required:!0},multiple:Boolean,size:{type:String,default:`medium`},value:{type:[String,Number,Array],default:null},autoPending:Boolean,virtualScroll:{type:Boolean,default:!0},show:{type:Boolean,default:!0},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},loading:Boolean,focusable:Boolean,renderLabel:Function,renderOption:Function,nodeProps:Function,showCheckmark:{type:Boolean,default:!0},onMousedown:Function,onScroll:Function,onFocus:Function,onBlur:Function,onKeyup:Function,onKeydown:Function,onTabOut:Function,onMouseenter:Function,onMouseleave:Function,onResize:Function,resetMenuOnOptionsChange:{type:Boolean,default:!0},inlineThemeDisabled:Boolean,scrollbarProps:Object,onToggle:Function},setup(e){let{mergedClsPrefixRef:t,mergedRtlRef:n,mergedComponentPropsRef:r}=ie(e),i=P(`InternalSelectMenu`,n,t),a=I(`InternalSelectMenu`,`-internal-select-menu`,Ye,Ce,e,w(e,`clsPrefix`)),c=D(null),l=D(null),u=D(null),d=x(()=>e.treeMate.getFlattenedNodes()),f=x(()=>me(d.value)),m=D(null);function h(){let{treeMate:t}=e,n=null,{value:r}=e;r===null?n=t.getFirstAvailableNode():(n=e.multiple?t.getNode((r||[])[(r||[]).length-1]):t.getNode(r),(!n||n.disabled)&&(n=t.getFirstAvailableNode())),U(n||null)}function g(){let{value:t}=m;t&&!e.treeMate.getNode(t.key)&&(m.value=null)}let v;y(()=>e.show,t=>{t?v=y(()=>e.treeMate,()=>{e.resetMenuOnOptionsChange?(e.autoPending?h():g(),T(W)):g()},{immediate:!0}):v?.()},{immediate:!0}),p(()=>{v?.()});let b=x(()=>s(a.value.self[Y(`optionHeight`,e.size)])),S=x(()=>o(a.value.self[Y(`padding`,e.size)])),C=x(()=>e.multiple&&Array.isArray(e.value)?new Set(e.value):new Set),E=x(()=>{let e=d.value;return e&&e.length===0}),O=x(()=>r?.value?.Select?.renderEmpty);function k(t){let{onToggle:n}=e;n&&n(t)}function A(t){let{onScroll:n}=e;n&&n(t)}function M(e){u.value?.sync(),A(e)}function N(){u.value?.sync()}function F(){let{value:e}=m;return e||null}function L(e,t){t.disabled||U(t,!1)}function te(e,t){t.disabled||k(t)}function R(t){Q(t,`action`)||e.onKeyup?.(t)}function z(t){Q(t,`action`)||e.onKeydown?.(t)}function B(t){e.onMousedown?.(t),!e.focusable&&t.preventDefault()}function V(){let{value:e}=m;e&&U(e.getNext({loop:!0}),!0)}function H(){let{value:e}=m;e&&U(e.getPrev({loop:!0}),!0)}function U(e,t=!1){m.value=e,t&&W()}function W(){let t=m.value;if(!t)return;let n=f.value(t.key);n!==null&&(e.virtualScroll?l.value?.scrollTo({index:n}):u.value?.scrollTo({index:n,elSize:b.value}))}function ne(t){c.value?.contains(t.target)&&e.onFocus?.(t)}function G(t){c.value?.contains(t.relatedTarget)||e.onBlur?.(t)}j(le,{handleOptionMouseEnter:L,handleOptionClick:te,valueSetRef:C,pendingTmNodeRef:m,nodePropsRef:w(e,`nodeProps`),showCheckmarkRef:w(e,`showCheckmark`),multipleRef:w(e,`multiple`),valueRef:w(e,`value`),renderLabelRef:w(e,`renderLabel`),renderOptionRef:w(e,`renderOption`),labelFieldRef:w(e,`labelField`),valueFieldRef:w(e,`valueField`)}),j(se,c),_(()=>{let{value:e}=u;e&&e.sync()});let re=x(()=>{let{size:t}=e,{common:{cubicBezierEaseInOut:n},self:{height:r,borderRadius:i,color:s,groupHeaderTextColor:c,actionDividerColor:l,optionTextColorPressed:u,optionTextColor:d,optionTextColorDisabled:f,optionTextColorActive:p,optionOpacityDisabled:m,optionCheckColor:h,actionTextColor:g,optionColorPending:_,optionColorActive:v,loadingColor:y,loadingSize:b,optionColorActivePending:x,[Y(`optionFontSize`,t)]:S,[Y(`optionHeight`,t)]:C,[Y(`optionPadding`,t)]:w}}=a.value;return{"--n-height":r,"--n-action-divider-color":l,"--n-action-text-color":g,"--n-bezier":n,"--n-border-radius":i,"--n-color":s,"--n-option-font-size":S,"--n-group-header-text-color":c,"--n-option-check-color":h,"--n-option-color-pending":_,"--n-option-color-active":v,"--n-option-color-active-pending":x,"--n-option-height":C,"--n-option-opacity-disabled":m,"--n-option-text-color":d,"--n-option-text-color-active":p,"--n-option-text-color-disabled":f,"--n-option-text-color-pressed":u,"--n-option-padding":w,"--n-option-padding-left":o(w,`left`),"--n-option-padding-right":o(w,`right`),"--n-loading-color":y,"--n-loading-size":b}}),{inlineThemeDisabled:K}=e,q=K?ee(`internal-select-menu`,x(()=>e.size[0]),re,e):void 0,J={selfRef:c,next:V,prev:H,getPendingTmNode:F};return He(c,e.onResize),{mergedTheme:a,mergedClsPrefix:t,rtlEnabled:i,virtualListRef:l,scrollbarRef:u,itemSize:b,padding:S,flattenedNodes:d,empty:E,mergedRenderEmpty:O,virtualListContainer(){let{value:e}=l;return e?.listElRef},virtualListContent(){let{value:e}=l;return e?.itemsElRef},doScroll:A,handleFocusin:ne,handleFocusout:G,handleKeyUp:R,handleKeyDown:z,handleMouseDown:B,handleVirtualListResize:N,handleVirtualListScroll:M,cssVars:K?void 0:re,themeClass:q?.themeClass,onRender:q?.onRender,...J}},render(){let{$slots:e,virtualScroll:t,clsPrefix:n,mergedTheme:r,themeClass:a,onRender:o}=this;return o?.(),f(),C(`div`,{ref:`selfRef`,tabindex:this.focusable?0:-1,class:H([`${n}-base-select-menu`,`${n}-base-select-menu--${this.size}-size`,this.rtlEnabled&&`${n}-base-select-menu--rtl`,a,this.multiple&&`${n}-base-select-menu--multiple`]),style:h(this.cssVars),onFocusin:this.handleFocusin,onFocusout:this.handleFocusout,onKeyup:this.handleKeyUp,onKeydown:this.handleKeyDown,onMousedown:this.handleMouseDown,onMouseenter:this.onMouseenter,onMouseleave:this.onMouseleave},[U(()=>R(e.header,e=>e&&(f(),C(`div`,{class:H(`${n}-base-select-menu__header`),"data-header":!0,key:`header`},[U(()=>e)],2)))),this.loading?(f(),C(`div`,{key:0,class:H(`${n}-base-select-menu__loading`)},[(f(),E(ae,{clsPrefix:n,strokeWidth:20},null,8,[`clsPrefix`]))],2)):(f(),C(b,{key:1},[this.empty?(f(),C(`div`,{key:1,class:H(`${n}-base-select-menu__empty`),"data-empty":!0},[U(()=>F(e.empty,()=>[this.mergedRenderEmpty?.()||(f(),E(_e,{theme:r.peers.Empty,themeOverrides:r.peerOverrides.Empty,size:this.size},null,8,[`theme`,`themeOverrides`,`size`]))]))],2)):(f(),E(i,g({key:0,ref:`scrollbarRef`,theme:r.peers.Scrollbar,themeOverrides:r.peerOverrides.Scrollbar,scrollable:this.scrollable,container:t?this.virtualListContainer:void 0,content:t?this.virtualListContent:void 0,onScroll:t?void 0:this.doScroll},this.scrollbarProps),{default:()=>t?(f(),E(Ve,{key:1,ref:`virtualListRef`,class:H(`${n}-virtual-list`),items:this.flattenedNodes,itemSize:this.itemSize,showScrollbar:!1,paddingTop:this.padding.top,paddingBottom:this.padding.bottom,onResize:this.handleVirtualListResize,onScroll:this.handleVirtualListScroll,itemResizable:!0},{default:({item:e})=>e.isGroup?(f(),E(We,{key:e.key,clsPrefix:n,tmNode:e},null,8,[`clsPrefix`,`tmNode`])):e.ignored?null:(f(),E(Je,{clsPrefix:n,key:e.key,tmNode:e},null,8,[`clsPrefix`,`tmNode`]))},1032,[`class`,`items`,`itemSize`,`paddingTop`,`paddingBottom`,`onResize`,`onScroll`])):(f(),C(`div`,{key:4,class:H(`${n}-base-select-menu-option-wrapper`),style:h({paddingTop:this.padding.top,paddingBottom:this.padding.bottom})},[U(()=>this.flattenedNodes.map(e=>e.isGroup?(f(),E(We,{key:e.key,clsPrefix:n,tmNode:e},null,8,[`clsPrefix`,`tmNode`])):(f(),E(Je,{clsPrefix:n,key:e.key,tmNode:e},null,8,[`clsPrefix`,`tmNode`]))))],6))},1040,[`theme`,`themeOverrides`,`scrollable`,`container`,`content`,`onScroll`]))],64)),U(()=>R(e.action,e=>e&&[(f(),C(`div`,{class:H(`${n}-base-select-menu__action`),"data-action":!0,key:`action`},[U(()=>e)],2)),(f(),E(Ue,{onFocus:this.onTabOut,key:`focus-detector`},null,8,[`onFocus`]))]))],46,Xe)}});function Qe(e){return e.type===`group`}function $e(e){return e.type===`ignored`}function et(e,t){try{return!!(1+t.toString().toLowerCase().indexOf(e.trim().toLowerCase()))}catch{return!1}}function tt(e,t){return{getIsGroup:Qe,getIgnored:$e,getKey(t){return Qe(t)?t.name||t.key||`key-required`:t[e]},getChildren(e){return e[t]}}}function nt(e,t,n,r){if(!t)return e;function i(e){if(!Array.isArray(e))return[];let a=[];for(let o of e)if(Qe(o)){let e=i(o[r]);e.length&&a.push(Object.assign({},o,{[r]:e}))}else if($e(o))continue;else t(n,o)&&a.push(o);return a}return i(e)}function rt(e,t,n){let r=new Map;return e.forEach(e=>{Qe(e)?e[n].forEach(e=>{r.set(e[t],e)}):r.set(e[t],e)}),r}var it=V([B(`base-selection`,`
 --n-padding-single: var(--n-padding-single-top) var(--n-padding-single-right) var(--n-padding-single-bottom) var(--n-padding-single-left);
 --n-padding-multiple: var(--n-padding-multiple-top) var(--n-padding-multiple-right) var(--n-padding-multiple-bottom) var(--n-padding-multiple-left);
 position: relative;
 z-index: auto;
 box-shadow: none;
 width: 100%;
 max-width: 100%;
 display: inline-block;
 vertical-align: bottom;
 border-radius: var(--n-border-radius);
 min-height: var(--n-height);
 line-height: 1.5;
 font-size: var(--n-font-size);
 `,[B(`base-loading`,`
 color: var(--n-loading-color);
 `),B(`base-selection-tags`,`min-height: var(--n-height);`),z(`border, state-border`,`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 pointer-events: none;
 border: var(--n-border);
 border-radius: inherit;
 transition:
 box-shadow .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `),z(`state-border`,`
 z-index: 1;
 border-color: #0000;
 `),B(`base-suffix`,`
 cursor: pointer;
 position: absolute;
 top: 50%;
 transform: translateY(-50%);
 right: 10px;
 `,[z(`arrow`,`
 font-size: var(--n-arrow-size);
 color: var(--n-arrow-color);
 transition: color .3s var(--n-bezier);
 `)]),B(`base-selection-overlay`,`
 display: flex;
 align-items: center;
 white-space: nowrap;
 pointer-events: none;
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 padding: var(--n-padding-single);
 transition: color .3s var(--n-bezier);
 `,[z(`wrapper`,`
 flex-basis: 0;
 flex-grow: 1;
 overflow: hidden;
 text-overflow: ellipsis;
 `)]),B(`base-selection-placeholder`,`
 color: var(--n-placeholder-color);
 `,[z(`inner`,`
 max-width: 100%;
 overflow: hidden;
 `)]),B(`base-selection-tags`,`
 cursor: pointer;
 outline: none;
 box-sizing: border-box;
 position: relative;
 z-index: auto;
 display: flex;
 padding: var(--n-padding-multiple);
 flex-wrap: wrap;
 align-items: center;
 width: 100%;
 vertical-align: bottom;
 background-color: var(--n-color);
 border-radius: inherit;
 transition:
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `),B(`base-selection-label`,`
 height: var(--n-height);
 display: inline-flex;
 width: 100%;
 vertical-align: bottom;
 cursor: pointer;
 outline: none;
 z-index: auto;
 box-sizing: border-box;
 position: relative;
 transition:
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 border-radius: inherit;
 background-color: var(--n-color);
 align-items: center;
 `,[B(`base-selection-input`,`
 font-size: inherit;
 line-height: inherit;
 outline: none;
 cursor: pointer;
 box-sizing: border-box;
 border:none;
 width: 100%;
 padding: var(--n-padding-single);
 background-color: #0000;
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 caret-color: var(--n-caret-color);
 `,[z(`content`,`
 text-overflow: ellipsis;
 overflow: hidden;
 white-space: nowrap; 
 `)]),z(`render-label`,`
 color: var(--n-text-color);
 `)]),W(`disabled`,[V(`&:hover`,[z(`state-border`,`
 box-shadow: var(--n-box-shadow-hover);
 border: var(--n-border-hover);
 `)]),M(`focus`,[z(`state-border`,`
 box-shadow: var(--n-box-shadow-focus);
 border: var(--n-border-focus);
 `)]),M(`active`,[z(`state-border`,`
 box-shadow: var(--n-box-shadow-active);
 border: var(--n-border-active);
 `),B(`base-selection-label`,`background-color: var(--n-color-active);`),B(`base-selection-tags`,`background-color: var(--n-color-active);`)])]),M(`disabled`,`cursor: not-allowed;`,[z(`arrow`,`
 color: var(--n-arrow-color-disabled);
 `),B(`base-selection-label`,`
 cursor: not-allowed;
 background-color: var(--n-color-disabled);
 `,[B(`base-selection-input`,`
 cursor: not-allowed;
 color: var(--n-text-color-disabled);
 `),z(`render-label`,`
 color: var(--n-text-color-disabled);
 `)]),B(`base-selection-tags`,`
 cursor: not-allowed;
 background-color: var(--n-color-disabled);
 `),B(`base-selection-placeholder`,`
 cursor: not-allowed;
 color: var(--n-placeholder-color-disabled);
 `)]),B(`base-selection-input-tag`,`
 height: calc(var(--n-height) - 6px);
 line-height: calc(var(--n-height) - 6px);
 outline: none;
 display: none;
 position: relative;
 margin-bottom: 3px;
 max-width: 100%;
 vertical-align: bottom;
 `,[z(`input`,`
 font-size: inherit;
 font-family: inherit;
 min-width: 1px;
 padding: 0;
 background-color: #0000;
 outline: none;
 border: none;
 max-width: 100%;
 overflow: hidden;
 width: 1em;
 line-height: inherit;
 cursor: pointer;
 color: var(--n-text-color);
 caret-color: var(--n-caret-color);
 `),z(`mirror`,`
 position: absolute;
 left: 0;
 top: 0;
 white-space: pre;
 visibility: hidden;
 user-select: none;
 -webkit-user-select: none;
 opacity: 0;
 `)]),[`warning`,`error`].map(e=>M(`${e}-status`,[z(`state-border`,`border: var(--n-border-${e});`),W(`disabled`,[V(`&:hover`,[z(`state-border`,`
 box-shadow: var(--n-box-shadow-hover-${e});
 border: var(--n-border-hover-${e});
 `)]),M(`active`,[z(`state-border`,`
 box-shadow: var(--n-box-shadow-active-${e});
 border: var(--n-border-active-${e});
 `),B(`base-selection-label`,`background-color: var(--n-color-active-${e});`),B(`base-selection-tags`,`background-color: var(--n-color-active-${e});`)]),M(`focus`,[z(`state-border`,`
 box-shadow: var(--n-box-shadow-focus-${e});
 border: var(--n-border-focus-${e});
 `)])])]))]),B(`base-selection-popover`,`
 margin-bottom: -3px;
 display: flex;
 flex-wrap: wrap;
 margin-right: -8px;
 `),B(`base-selection-tag-wrapper`,`
 max-width: 100%;
 display: inline-flex;
 padding: 0 7px 3px 0;
 `,[V(`&:last-child`,`padding-right: 0;`),B(`tag`,`
 font-size: 14px;
 max-width: 100%;
 `,[z(`content`,`
 line-height: 1.25;
 text-overflow: ellipsis;
 overflow: hidden;
 `)])])]),at=[`disabled`,`value`,`autofocus`,`onBlur`,`onFocus`,`onKeydown`,`onInput`,`onCompositionstart`,`onCompositionend`],ot=[`tabindex`],st=[`title`],ct=[`value`,`readonly`,`disabled`,`autofocus`,`onFocus`,`onBlur`,`onInput`,`onCompositionstart`,`onCompositionend`],lt=[`tabindex`],ut=[`onClick`,`onMouseenter`,`onMouseleave`,`onKeydown`,`onFocusin`,`onFocusout`,`onMousedown`],dt=k({name:`InternalSelection`,props:{...I.props,clsPrefix:{type:String,required:!0},bordered:{type:Boolean,default:void 0},active:Boolean,pattern:{type:String,default:``},placeholder:String,selectedOption:{type:Object,default:null},selectedOptions:{type:Array,default:null},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},multiple:Boolean,filterable:Boolean,clearable:Boolean,disabled:Boolean,size:{type:String,default:`medium`},loading:Boolean,autofocus:Boolean,showArrow:{type:Boolean,default:!0},inputProps:Object,focused:Boolean,renderTag:Function,onKeydown:Function,onClick:Function,onBlur:Function,onFocus:Function,onDeleteOption:Function,maxTagCount:[String,Number],ellipsisTagPopoverProps:Object,onClear:Function,onPatternInput:Function,onPatternFocus:Function,onPatternBlur:Function,renderLabel:Function,status:String,inlineThemeDisabled:Boolean,ignoreComposition:{type:Boolean,default:!0},onResize:Function},setup(e){let{mergedClsPrefixRef:t,mergedRtlRef:n}=ie(e),r=P(`InternalSelection`,n,t),i=D(null),a=D(null),s=D(null),c=D(null),l=D(null),u=D(null),d=D(null),f=D(null),p=D(null),m=D(null),h=D(!1),g=D(!1),b=D(!1),S=I(`InternalSelection`,`-internal-selection`,it,ve,e,w(e,`clsPrefix`)),C=x(()=>e.clearable&&!e.disabled&&(b.value||e.active)),E=x(()=>e.selectedOption?e.renderTag?e.renderTag({option:e.selectedOption,handleClose:()=>{}}):e.renderLabel?e.renderLabel(e.selectedOption,!0):$(e.selectedOption[e.labelField],e.selectedOption,!0):e.placeholder),O=x(()=>{let t=e.selectedOption;if(t)return t[e.labelField]}),k=x(()=>e.multiple?!!(Array.isArray(e.selectedOptions)&&e.selectedOptions.length):e.selectedOption!==null);function A(){let{value:t}=i;if(t){let{value:n}=a;n&&(n.style.width=`${t.offsetWidth}px`,e.maxTagCount!==`responsive`&&p.value?.sync({showAllItemsBeforeCalculate:!1}))}}function j(){let{value:e}=m;e&&(e.style.display=`none`)}function M(){let{value:e}=m;e&&(e.style.display=`inline-block`)}y(w(e,`active`),e=>{e||j()}),y(w(e,`pattern`),()=>{e.multiple&&T(A)});function N(t){let{onFocus:n}=e;n&&n(t)}function F(t){let{onBlur:n}=e;n&&n(t)}function L(t){let{onDeleteOption:n}=e;n&&n(t)}function te(t){let{onClear:n}=e;n&&n(t)}function R(t){let{onPatternInput:n}=e;n&&n(t)}function z(e){(!e.relatedTarget||!s.value?.contains(e.relatedTarget))&&N(e)}function B(e){s.value?.contains(e.relatedTarget)||F(e)}function V(e){te(e)}function H(){b.value=!0}function U(){b.value=!1}function W(t){e.active&&e.filterable&&t.target!==a.value&&t.preventDefault()}function ne(e){L(e)}let G=D(!1);function re(t){if(t.key===`Backspace`&&!G.value&&!e.pattern.length){let{selectedOptions:t}=e;t?.length&&ne(t[t.length-1])}}let K=null;function q(t){let{value:n}=i;n&&(n.textContent=t.target.value,A()),e.ignoreComposition&&G.value?K=t:R(t)}function J(){G.value=!0}function ae(){G.value=!1,e.ignoreComposition&&R(K),K=null}function X(t){g.value=!0,e.onPatternFocus?.(t)}function oe(t){g.value=!1,e.onPatternBlur?.(t)}function Z(){if(e.filterable)g.value=!1,u.value?.blur(),a.value?.blur();else if(e.multiple){let{value:e}=c;e?.blur()}else{let{value:e}=l;e?.blur()}}function se(){e.filterable?(g.value=!1,u.value?.focus()):e.multiple?c.value?.focus():l.value?.focus()}function ce(){let{value:e}=a;e&&(M(),e.focus())}function le(){let{value:e}=a;e&&e.blur()}function ue(e){let{value:t}=d;t&&t.setTextContent(`+${e}`)}function de(){let{value:e}=f;return e}function fe(){return a.value}let pe=null;function Q(){pe!==null&&window.clearTimeout(pe)}function me(){e.active||(Q(),pe=window.setTimeout(()=>{k.value&&(h.value=!0)},100))}function he(){Q()}function ge(e){e||(Q(),h.value=!1)}y(k,e=>{e||(h.value=!1)}),_(()=>{v(()=>{let t=u.value;t&&(e.disabled?t.removeAttribute(`tabindex`):t.tabIndex=g.value?-1:0)})}),He(s,e.onResize);let{inlineThemeDisabled:_e}=e,ye=x(()=>{let{size:t}=e,{common:{cubicBezierEaseInOut:n},self:{fontWeight:r,borderRadius:i,color:a,placeholderColor:s,textColor:c,paddingSingle:l,paddingMultiple:u,caretColor:d,colorDisabled:f,textColorDisabled:p,placeholderColorDisabled:m,colorActive:h,boxShadowFocus:g,boxShadowActive:_,boxShadowHover:v,border:y,borderFocus:b,borderHover:x,borderActive:C,arrowColor:w,arrowColorDisabled:T,loadingColor:E,colorActiveWarning:D,boxShadowFocusWarning:O,boxShadowActiveWarning:k,boxShadowHoverWarning:A,borderWarning:j,borderFocusWarning:M,borderHoverWarning:N,borderActiveWarning:P,colorActiveError:F,boxShadowFocusError:I,boxShadowActiveError:ee,boxShadowHoverError:L,borderError:te,borderFocusError:R,borderHoverError:z,borderActiveError:B,clearColor:V,clearColorHover:H,clearColorPressed:U,clearSize:W,arrowSize:ne,[Y(`height`,t)]:G,[Y(`fontSize`,t)]:re}}=S.value,K=o(l),q=o(u);return{"--n-bezier":n,"--n-border":y,"--n-border-active":C,"--n-border-focus":b,"--n-border-hover":x,"--n-border-radius":i,"--n-box-shadow-active":_,"--n-box-shadow-focus":g,"--n-box-shadow-hover":v,"--n-caret-color":d,"--n-color":a,"--n-color-active":h,"--n-color-disabled":f,"--n-font-size":re,"--n-height":G,"--n-padding-single-top":K.top,"--n-padding-multiple-top":q.top,"--n-padding-single-right":K.right,"--n-padding-multiple-right":q.right,"--n-padding-single-left":K.left,"--n-padding-multiple-left":q.left,"--n-padding-single-bottom":K.bottom,"--n-padding-multiple-bottom":q.bottom,"--n-placeholder-color":s,"--n-placeholder-color-disabled":m,"--n-text-color":c,"--n-text-color-disabled":p,"--n-arrow-color":w,"--n-arrow-color-disabled":T,"--n-loading-color":E,"--n-color-active-warning":D,"--n-box-shadow-focus-warning":O,"--n-box-shadow-active-warning":k,"--n-box-shadow-hover-warning":A,"--n-border-warning":j,"--n-border-focus-warning":M,"--n-border-hover-warning":N,"--n-border-active-warning":P,"--n-color-active-error":F,"--n-box-shadow-focus-error":I,"--n-box-shadow-active-error":ee,"--n-box-shadow-hover-error":L,"--n-border-error":te,"--n-border-focus-error":R,"--n-border-hover-error":z,"--n-border-active-error":B,"--n-clear-size":W,"--n-clear-color":V,"--n-clear-color-hover":H,"--n-clear-color-pressed":U,"--n-arrow-size":ne,"--n-font-weight":r}}),be=_e?ee(`internal-selection`,x(()=>e.size[0]),ye,e):void 0;return{mergedTheme:S,mergedClearable:C,mergedClsPrefix:t,rtlEnabled:r,patternInputFocused:g,filterablePlaceholder:E,label:O,selected:k,showTagsPanel:h,isComposing:G,counterRef:d,counterWrapperRef:f,patternInputMirrorRef:i,patternInputRef:a,selfRef:s,multipleElRef:c,singleElRef:l,patternInputWrapperRef:u,overflowRef:p,inputTagElRef:m,handleMouseDown:W,handleFocusin:z,handleClear:V,handleMouseEnter:H,handleMouseLeave:U,handleDeleteOption:ne,handlePatternKeyDown:re,handlePatternInputInput:q,handlePatternInputBlur:oe,handlePatternInputFocus:X,handleMouseEnterCounter:me,handleMouseLeaveCounter:he,handleFocusout:B,handleCompositionEnd:ae,handleCompositionStart:J,onPopoverUpdateShow:ge,focus:se,focusInput:ce,blur:Z,blurInput:le,updateCounter:ue,getCounter:de,getTail:fe,renderLabel:e.renderLabel,cssVars:_e?void 0:ye,themeClass:be?.themeClass,onRender:be?.onRender}},render(){let{status:e,multiple:n,size:r,disabled:i,filterable:a,maxTagCount:o,bordered:s,clsPrefix:c,ellipsisTagPopoverProps:l,onRender:u,renderTag:d,renderLabel:p}=this;u?.();let m=o===`responsive`,_=typeof o==`number`,v=m||_,y=(f(),E(t,null,{default:()=>(f(),E(Ae,{clsPrefix:c,loading:this.loading,showArrow:this.showArrow,showClear:this.mergedClearable&&this.selected,onClear:this.handleClear},{default:()=>this.$slots.arrow?.()},1032,[`clsPrefix`,`loading`,`showArrow`,`showClear`,`onClear`]))},1024)),x;if(n){let{labelField:e}=this,t=t=>(f(),C(`div`,{class:H(`${c}-base-selection-tag-wrapper`),key:t.value},[d?(f(),C(b,{key:0},[U(()=>d({option:t,handleClose:()=>{this.handleDeleteOption(t)}}))],64)):(f(),E(Oe,{key:1,size:r,closable:!t.disabled,disabled:i,onClose:()=>{this.handleDeleteOption(t)},internalCloseIsButtonTag:!1,internalCloseFocusable:!1},{default:()=>p?p(t,!0):$(t[e],t,!0)},1032,[`size`,`closable`,`disabled`,`onClose`]))],2)),n=()=>(_?this.selectedOptions.slice(0,o):this.selectedOptions).map(t),s=a?(f(),C(`div`,{class:H(`${c}-base-selection-input-tag`),ref:`inputTagElRef`,key:`__input-tag__`},[S(`input`,g(this.inputProps,{ref:`patternInputRef`,tabindex:-1,disabled:i,value:this.pattern,autofocus:this.autofocus,class:`${c}-base-selection-input-tag__input`,onBlur:this.handlePatternInputBlur,onFocus:this.handlePatternInputFocus,onKeydown:this.handlePatternKeyDown,onInput:this.handlePatternInputInput,onCompositionstart:this.handleCompositionStart,onCompositionend:this.handleCompositionEnd}),null,16,at),S(`span`,{ref:`patternInputMirrorRef`,class:H(`${c}-base-selection-input-tag__mirror`)},[U(()=>this.pattern)],2)],2)):null,u=m?()=>(f(),C(`div`,{class:H(`${c}-base-selection-tag-wrapper`),ref:`counterWrapperRef`},[(f(),E(Oe,{size:r,ref:`counterRef`,onMouseenter:this.handleMouseEnterCounter,onMouseleave:this.handleMouseLeaveCounter,disabled:i},null,8,[`size`,`onMouseenter`,`onMouseleave`,`disabled`]))],2)):void 0,h;if(_){let e=this.selectedOptions.length-o;e>0&&(h=(t=>(f(),C(`div`,{class:H(`${c}-base-selection-tag-wrapper`),key:`__counter__`},[(f(),E(Oe,{size:r,ref:`counterRef`,onMouseenter:this.handleMouseEnterCounter,disabled:i},{default:()=>`+${e}`},1032,[`size`,`onMouseenter`,`disabled`]))],2)))(h))}let w=m?a?(f(),E(he,{key:3,ref:`overflowRef`,updateCounter:this.updateCounter,getCounter:this.getCounter,getTail:this.getTail,style:{width:`100%`,display:`flex`,overflow:`hidden`}},{default:n,counter:u,tail:()=>s},1032,[`updateCounter`,`getCounter`,`getTail`])):(f(),E(he,{key:4,ref:`overflowRef`,updateCounter:this.updateCounter,getCounter:this.getCounter,style:{width:`100%`,display:`flex`,overflow:`hidden`}},{default:n,counter:u},1032,[`updateCounter`,`getCounter`])):_&&h?n().concat(h):n(),T=v?()=>(f(),C(`div`,{class:H(`${c}-base-selection-popover`)},[m?(f(),C(b,{key:0},[U(()=>n())],64)):(f(),C(b,{key:1},[U(()=>this.selectedOptions.map(t))],64))],2)):void 0,D=v?{show:this.showTagsPanel,trigger:`hover`,overlap:!0,placement:`top`,width:`trigger`,onUpdateShow:this.onPopoverUpdateShow,theme:this.mergedTheme.peers.Popover,themeOverrides:this.mergedTheme.peerOverrides.Popover,...l}:null,O=!this.selected&&(!this.active||!this.pattern&&!this.isComposing)?(f(),C(`div`,{key:5,class:H(`${c}-base-selection-placeholder ${c}-base-selection-overlay`)},[S(`div`,{class:H(`${c}-base-selection-placeholder__inner`)},[U(()=>this.placeholder)],2)],2)):null,k=a?(f(),C(`div`,{key:6,ref:`patternInputWrapperRef`,class:H(`${c}-base-selection-tags`)},[U(()=>w),m?U(()=>null):(f(),C(b,{key:1},[U(()=>s)],64)),U(()=>y)],2)):(f(),C(`div`,{key:7,ref:`multipleElRef`,class:H(`${c}-base-selection-tags`),tabindex:i?void 0:0},[U(()=>w),U(()=>y)],10,ot));x=(e=>(f(),C(b,{key:8},[v?(f(),E(fe,g({key:0},D,{scrollable:!0,style:`max-height: calc(var(--v-target-height) * 6.6);`}),{trigger:()=>k,default:T},1040)):(f(),C(b,{key:1},[U(()=>k)],64)),U(()=>O)],64)))(x)}else if(a){let e=this.pattern||this.isComposing,t=this.active?!e:!this.selected,n=!this.active&&this.selected;x=(e=>(f(),C(`div`,{key:9,ref:`patternInputWrapperRef`,class:H(`${c}-base-selection-label`),title:this.patternInputFocused?void 0:ke(this.label)},[S(`input`,g(this.inputProps,{ref:`patternInputRef`,class:`${c}-base-selection-input`,value:this.active?this.pattern:``,placeholder:``,readonly:i,disabled:i,tabindex:-1,autofocus:this.autofocus,onFocus:this.handlePatternInputFocus,onBlur:this.handlePatternInputBlur,onInput:this.handlePatternInputInput,onCompositionstart:this.handleCompositionStart,onCompositionend:this.handleCompositionEnd}),null,16,ct),n?(f(),C(`div`,{class:H(`${c}-base-selection-label__render-label ${c}-base-selection-overlay`),key:`input`},[S(`div`,{class:H(`${c}-base-selection-overlay__wrapper`)},[d?(f(),C(b,{key:0},[U(()=>d({option:this.selectedOption,handleClose:()=>{}}))],64)):(f(),C(b,{key:1},[p?(f(),C(b,{key:0},[U(()=>p(this.selectedOption,!0))],64)):(f(),C(b,{key:1},[U(()=>$(this.label,this.selectedOption,!0))],64))],64))],2)],2)):U(()=>null),t?(f(),C(`div`,{class:H(`${c}-base-selection-placeholder ${c}-base-selection-overlay`),key:`placeholder`},[S(`div`,{class:H(`${c}-base-selection-overlay__wrapper`)},[U(()=>this.filterablePlaceholder)],2)],2)):U(()=>null),U(()=>y)],10,st)))(x)}else x=(e=>(f(),C(`div`,{key:10,ref:`singleElRef`,class:H(`${c}-base-selection-label`),tabindex:this.disabled?void 0:0},[this.label===void 0?(f(),C(`div`,{class:H(`${c}-base-selection-placeholder ${c}-base-selection-overlay`),key:`placeholder`},[S(`div`,{class:H(`${c}-base-selection-placeholder__inner`)},[U(()=>this.placeholder)],2)],2)):(f(),C(`div`,{class:H(`${c}-base-selection-input`),title:ke(this.label),key:`input`},[S(`div`,{class:H(`${c}-base-selection-input__content`)},[d?(f(),C(b,{key:0},[U(()=>d({option:this.selectedOption,handleClose:()=>{}}))],64)):(f(),C(b,{key:1},[p?(f(),C(b,{key:0},[U(()=>p(this.selectedOption,!0))],64)):(f(),C(b,{key:1},[U(()=>$(this.label,this.selectedOption,!0))],64))],64))],2)],10,[`title`])),U(()=>y)],10,lt)))(x);return f(),C(`div`,{ref:`selfRef`,class:H([`${c}-base-selection`,this.rtlEnabled&&`${c}-base-selection--rtl`,this.themeClass,e&&`${c}-base-selection--${e}-status`,{[`${c}-base-selection--active`]:this.active,[`${c}-base-selection--selected`]:this.selected||this.active&&this.pattern,[`${c}-base-selection--disabled`]:this.disabled,[`${c}-base-selection--multiple`]:this.multiple,[`${c}-base-selection--focus`]:this.focused}]),style:h(this.cssVars),onClick:this.onClick,onMouseenter:this.handleMouseEnter,onMouseleave:this.handleMouseLeave,onKeydown:this.onKeydown,onFocusin:this.handleFocusin,onFocusout:this.handleFocusout,onMousedown:this.handleMouseDown},[U(()=>x),s?(f(),C(`div`,{key:0,class:H(`${c}-base-selection__border`)},null,2)):U(()=>null),s?(f(),C(`div`,{key:2,class:H(`${c}-base-selection__state-border`)},null,2)):U(()=>null)],46,ut)}}),ft=V([B(`select`,`
 z-index: auto;
 outline: none;
 width: 100%;
 position: relative;
 font-weight: var(--n-font-weight);
 `),B(`select-menu`,`
 margin: 4px 0;
 box-shadow: var(--n-menu-box-shadow);
 `,[n({originalTransition:`background-color .3s var(--n-bezier), box-shadow .3s var(--n-bezier)`})])]),pt={...I.props,to:de.propTo,bordered:{type:Boolean,default:void 0},clearable:Boolean,clearCreatedOptionsOnClear:{type:Boolean,default:!0},clearFilterAfterSelect:{type:Boolean,default:!0},options:{type:Array,default:()=>[]},defaultValue:{type:[String,Number,Array],default:null},keyboard:{type:Boolean,default:!0},value:[String,Number,Array],placeholder:String,menuProps:Object,multiple:Boolean,size:String,menuSize:{type:String},filterable:Boolean,disabled:{type:Boolean,default:void 0},remote:Boolean,loading:Boolean,filter:Function,placement:{type:String,default:`bottom-start`},widthMode:{type:String,default:`trigger`},tag:Boolean,onCreate:Function,fallbackOption:{type:[Function,Boolean],default:void 0},show:{type:Boolean,default:void 0},showArrow:{type:Boolean,default:!0},maxTagCount:[Number,String],ellipsisTagPopoverProps:Object,consistentMenuWidth:{type:Boolean,default:!0},virtualScroll:{type:Boolean,default:!0},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},childrenField:{type:String,default:`children`},renderLabel:Function,renderOption:Function,renderTag:Function,"onUpdate:value":[Function,Array],inputProps:Object,nodeProps:Function,ignoreComposition:{type:Boolean,default:!0},showOnFocus:Boolean,onUpdateValue:[Function,Array],onBlur:[Function,Array],onClear:[Function,Array],onFocus:[Function,Array],onScroll:[Function,Array],onSearch:[Function,Array],onUpdateShow:[Function,Array],"onUpdate:show":[Function,Array],displayDirective:{type:String,default:`show`},resetMenuOnOptionsChange:{type:Boolean,default:!0},status:String,showCheckmark:{type:Boolean,default:!0},scrollbarProps:Object,onChange:[Function,Array],items:Array},mt=k({name:`Select`,props:pt,slots:Object,setup(e){let{mergedClsPrefixRef:t,mergedBorderedRef:n,namespaceRef:r,inlineThemeDisabled:i,mergedComponentPropsRef:a}=ie(e),o=I(`Select`,`-select`,ft,Se,e,t),s=D(e.defaultValue),l=w(e,`value`),u=De(l,s),d=D(!1),f=D(``),p=Te(e,[`items`,`options`]),m=D([]),h=D([]),g=x(()=>h.value.concat(m.value).concat(p.value)),_=x(()=>{let{filter:t}=e;if(t)return t;let{labelField:n,valueField:r}=e;return(e,t)=>{if(!t)return!1;let i=t[n];if(typeof i==`string`)return et(e,i);let a=t[r];return typeof a==`string`?et(e,a):typeof a==`number`&&et(e,String(a))}}),v=x(()=>{if(e.remote)return p.value;{let{value:t}=g,{value:n}=f;return!n.length||!e.filterable?t:nt(t,_.value,n,e.childrenField)}}),b=x(()=>{let{valueField:t,childrenField:n}=e,r=tt(t,n);return ge(v.value,r)}),S=x(()=>rt(g.value,e.valueField,e.childrenField)),C=D(!1),T=De(w(e,`show`),C),E=D(null),O=D(null),k=D(null),{localeRef:A}=oe(`Select`),j=x(()=>e.placeholder??A.value.placeholder),M=[],P=D(new Map),F=x(()=>{let{fallbackOption:t}=e;if(t===void 0){let{labelField:t,valueField:n}=e;return e=>({[t]:String(e),[n]:e})}return t===!1?!1:e=>Object.assign(t(e),{value:e})});function L(t){let n=e.remote,{value:r}=P,{value:i}=S,{value:a}=F,o=[];return t.forEach(e=>{if(i.has(e))o.push(i.get(e));else if(n&&r.has(e))o.push(r.get(e));else if(a){let t=a(e);t&&o.push(t)}}),o}let te=x(()=>{if(e.multiple){let{value:e}=u;return Array.isArray(e)?L(e):[]}return null}),R=x(()=>{let{value:t}=u;return!e.multiple&&!Array.isArray(t)?t===null?null:L([t])[0]||null:null}),z=ne(e,{mergedSize:t=>{let{size:n}=e;if(n)return n;let{mergedSize:r}=t||{};return r?.value?r.value:a?.value?.Select?.size||`medium`}}),{mergedSizeRef:B,mergedDisabledRef:V,mergedStatusRef:H}=z;function U(t,n){let{onChange:r,"onUpdate:value":i,onUpdateValue:a}=e,{nTriggerFormChange:o,nTriggerFormInput:c}=z;r&&N(r,t,n),a&&N(a,t,n),i&&N(i,t,n),s.value=t,o(),c()}function W(t){let{onBlur:n}=e,{nTriggerFormBlur:r}=z;n&&N(n,t),r()}function G(){let{onClear:t}=e;t&&N(t)}function K(t){let{onFocus:n,showOnFocus:r}=e,{nTriggerFormFocus:i}=z;n&&N(n,t),i(),r&&X()}function q(t){let{onSearch:n}=e;n&&N(n,t)}function J(t){let{onScroll:n}=e;n&&N(n,t)}function Y(){let{remote:t,multiple:n}=e;if(t){let{value:t}=P;if(n){let{valueField:n}=e;te.value?.forEach(e=>{t.set(e[n],e)})}else{let n=R.value;n&&t.set(n[e.valueField],n)}}}function ae(t){let{onUpdateShow:n,"onUpdate:show":r}=e;n&&N(n,t),r&&N(r,t),C.value=t}function X(){V.value||(ae(!0),C.value=!0,e.filterable&&Ne())}function Z(){ae(!1)}function se(){f.value=``,h.value=M}let ce=D(!1);function le(){e.filterable&&(ce.value=!0)}function ue(){e.filterable&&(ce.value=!1,T.value||se())}function fe(){V.value||(T.value?e.filterable?Ne():Z():X())}function pe(e){k.value?.selfRef?.contains(e.relatedTarget)||(d.value=!1,W(e),Z())}function me(e){K(e),d.value=!0}function he(){d.value=!0}function _e(e){E.value?.$el.contains(e.relatedTarget)||(d.value=!1,W(e),Z())}function ve(){E.value?.focus(),Z()}function ye(e){T.value&&(E.value?.$el.contains(c(e))||Z())}function xe(t){if(!Array.isArray(t))return[];if(F.value)return Array.from(t);{let{remote:n}=e,{value:r}=S;if(n){let{value:e}=P;return t.filter(t=>r.has(t)||e.has(t))}return t.filter(e=>r.has(e))}}function $(e){Ce(e.rawNode)}function Ce(t){if(V.value)return;let{tag:n,remote:r,clearFilterAfterSelect:i,valueField:a}=e;if(n&&!r){let{value:e}=h,t=e[0]||null;if(t){let e=m.value;e.length?e.push(t):m.value=[t],h.value=M}}if(r&&P.value.set(t[a],t),e.multiple){let e=xe(u.value),o=e.findIndex(e=>e===t[a]);if(~o){if(e.splice(o,1),n&&!r){let e=we(t[a]);~e&&(m.value.splice(e,1),i&&(f.value=``))}}else e.push(t[a]),i&&(f.value=``);U(e,L(e))}else{if(n&&!r){let e=we(t[a]);~e?m.value=[m.value[e]]:m.value=M}Me(),Z(),U(t[a],t)}}function we(t){return m.value.findIndex(n=>n[e.valueField]===t)}function Ee(t){T.value||X();let{value:n}=t.target;f.value=n;let{tag:r,remote:i}=e;if(q(n),r&&!i){if(!n){h.value=M;return}let{onCreate:t}=e,r=t?t(n):{[e.labelField]:n,[e.valueField]:n},{valueField:i,labelField:a}=e;p.value.some(e=>e[i]===r[i]||e[a]===r[a])||m.value.some(e=>e[i]===r[i]||e[a]===r[a])?h.value=M:h.value=[r]}}function Oe(t){t.stopPropagation();let{multiple:n,tag:r,remote:i,clearCreatedOptionsOnClear:a}=e;!n&&e.filterable&&Z(),r&&!i&&a&&(m.value=M),G(),n?U([],[]):U(null,null)}function ke(e){!Q(e,`action`)&&!Q(e,`empty`)&&!Q(e,`header`)&&e.preventDefault()}function Ae(e){J(e)}function je(t){if(!e.keyboard){t.preventDefault();return}switch(t.key){case` `:if(e.filterable)break;t.preventDefault();case`Enter`:if(!E.value?.isComposing){if(T.value){let t=k.value?.getPendingTmNode();t?$(t):e.filterable||(Z(),Me())}else if(X(),e.tag&&ce.value){let t=h.value[0];if(t){let n=t[e.valueField],{value:r}=u;e.multiple&&Array.isArray(r)&&r.includes(n)||Ce(t)}}}t.preventDefault();break;case`ArrowUp`:if(t.preventDefault(),e.loading)return;T.value&&k.value?.prev();break;case`ArrowDown`:if(t.preventDefault(),e.loading)return;T.value?k.value?.next():X();break;case`Escape`:T.value&&(be(t),Z()),E.value?.focus()}}function Me(){E.value?.focus()}function Ne(){E.value?.focusInput()}function Pe(){T.value&&O.value?.syncPosition()}Y(),y(w(e,`options`),Y);let Fe={focus:()=>{E.value?.focus()},focusInput:()=>{E.value?.focusInput()},blur:()=>{E.value?.blur()},blurInput:()=>{E.value?.blurInput()}},Ie=x(()=>{let{self:{menuBoxShadow:e}}=o.value;return{"--n-menu-box-shadow":e}}),Le=i?ee(`select`,void 0,Ie,e):void 0;return{...Fe,mergedStatus:H,mergedClsPrefix:t,mergedBordered:n,namespace:r,treeMate:b,isMounted:re(),triggerRef:E,menuRef:k,pattern:f,uncontrolledShow:C,mergedShow:T,adjustedTo:de(e),uncontrolledValue:s,mergedValue:u,followerRef:O,localizedPlaceholder:j,selectedOption:R,selectedOptions:te,mergedSize:B,mergedDisabled:V,focused:d,activeWithoutMenuOpen:ce,inlineThemeDisabled:i,onTriggerInputFocus:le,onTriggerInputBlur:ue,handleTriggerOrMenuResize:Pe,handleMenuFocus:he,handleMenuBlur:_e,handleMenuTabOut:ve,handleTriggerClick:fe,handleToggle:$,handleDeleteOption:Ce,handlePatternInput:Ee,handleClear:Oe,handleTriggerBlur:pe,handleTriggerFocus:me,handleKeydown:je,handleMenuAfterLeave:se,handleMenuClickOutside:ye,handleMenuScroll:Ae,handleMenuKeydown:je,handleMenuMousedown:ke,mergedTheme:o,cssVars:i?void 0:Ie,themeClass:Le?.themeClass,onRender:Le?.onRender}},render(){return f(),C(`div`,{class:H(`${this.mergedClsPrefix}-select`)},[A(ue,null,{_:1,default:J(()=>[(f(),E(Z,null,{_:1,default:J(()=>(f(),E(dt,{ref:`triggerRef`,inlineThemeDisabled:this.inlineThemeDisabled,status:this.mergedStatus,inputProps:this.inputProps,clsPrefix:this.mergedClsPrefix,showArrow:this.showArrow,maxTagCount:this.maxTagCount,ellipsisTagPopoverProps:this.ellipsisTagPopoverProps,bordered:this.mergedBordered,active:this.activeWithoutMenuOpen||this.mergedShow,pattern:this.pattern,placeholder:this.localizedPlaceholder,selectedOption:this.selectedOption,selectedOptions:this.selectedOptions,multiple:this.multiple,renderTag:this.renderTag,renderLabel:this.renderLabel,filterable:this.filterable,clearable:this.clearable,disabled:this.mergedDisabled,size:this.mergedSize,theme:this.mergedTheme.peers.InternalSelection,labelField:this.labelField,valueField:this.valueField,themeOverrides:this.mergedTheme.peerOverrides.InternalSelection,loading:this.loading,focused:this.focused,onClick:this.handleTriggerClick,onDeleteOption:this.handleDeleteOption,onPatternInput:this.handlePatternInput,onClear:this.handleClear,onBlur:this.handleTriggerBlur,onFocus:this.handleTriggerFocus,onKeydown:this.handleKeydown,onPatternBlur:this.onTriggerInputBlur,onPatternFocus:this.onTriggerInputFocus,onResize:this.handleTriggerOrMenuResize,ignoreComposition:this.ignoreComposition},{_:1,arrow:J(()=>[this.$slots.arrow?.()])},8,`inlineThemeDisabled.status.inputProps.clsPrefix.showArrow.maxTagCount.ellipsisTagPopoverProps.bordered.active.pattern.placeholder.selectedOption.selectedOptions.multiple.renderTag.renderLabel.filterable.clearable.disabled.size.theme.labelField.valueField.themeOverrides.loading.focused.onClick.onDeleteOption.onPatternInput.onClear.onBlur.onFocus.onKeydown.onPatternBlur.onPatternFocus.onResize.ignoreComposition`.split(`.`))))})),(f(),E(ce,{ref:`followerRef`,show:this.mergedShow,to:this.adjustedTo,teleportDisabled:this.adjustedTo===de.tdkey,containerClass:this.namespace,width:this.consistentMenuWidth?`target`:void 0,minWidth:`target`,placement:this.placement},{_:1,default:J(()=>(f(),E(q,{name:`fade-in-scale-up-transition`,appear:this.isMounted,onAfterLeave:this.handleMenuAfterLeave},{_:1,default:J(()=>this.mergedShow||this.displayDirective===`show`?(this.onRender?.(),l((f(),E(Ze,g(this.menuProps,{ref:`menuRef`,onResize:this.handleTriggerOrMenuResize,inlineThemeDisabled:this.inlineThemeDisabled,virtualScroll:this.consistentMenuWidth&&this.virtualScroll,class:[`${this.mergedClsPrefix}-select-menu`,this.themeClass,this.menuProps?.class],clsPrefix:this.mergedClsPrefix,focusable:!0,labelField:this.labelField,valueField:this.valueField,autoPending:!0,nodeProps:this.nodeProps,theme:this.mergedTheme.peers.InternalSelectMenu,themeOverrides:this.mergedTheme.peerOverrides.InternalSelectMenu,treeMate:this.treeMate,multiple:this.multiple,size:this.menuSize,renderOption:this.renderOption,renderLabel:this.renderLabel,value:this.mergedValue,style:[this.menuProps?.style,this.cssVars],onToggle:this.handleToggle,onScroll:this.handleMenuScroll,onFocus:this.handleMenuFocus,onBlur:this.handleMenuBlur,onKeydown:this.handleMenuKeydown,onTabOut:this.handleMenuTabOut,onMousedown:this.handleMenuMousedown,show:this.mergedShow,showCheckmark:this.showCheckmark,resetMenuOnOptionsChange:this.resetMenuOnOptionsChange,scrollbarProps:this.scrollbarProps}),{_:1,empty:J(()=>[this.$slots.empty?.()]),header:J(()=>[this.$slots.header?.()]),action:J(()=>[this.$slots.action?.()])},16,`onResize.inlineThemeDisabled.virtualScroll.class.clsPrefix.labelField.valueField.nodeProps.theme.themeOverrides.treeMate.multiple.size.renderOption.renderLabel.value.style.onToggle.onScroll.onFocus.onBlur.onKeydown.onTabOut.onMousedown.show.showCheckmark.resetMenuOnOptionsChange.scrollbarProps`.split(`.`))),this.displayDirective===`show`?[[K,this.mergedShow],[ye,this.handleMenuClickOutside,void 0,{capture:!0}]]:[[ye,this.handleMenuClickOutside,void 0,{capture:!0}]])):null)},8,[`appear`,`onAfterLeave`])))},8,[`show`,`to`,`teleportDisabled`,`containerClass`,`width`,`placement`]))])})],2)}});export{Ve as i,tt as n,Ze as r,mt as t};