import{$ as e,B as t,L as n,M as r,Q as i,Y as a,Z as o,j as s,k as c,w as l}from"./php-modules-jPihHAOi.js";import{A as u,D as d,E as f,F as p,K as m,L as h,M as g,O as _,S as v,Y as y,_ as b,d as x,dt as S,f as C,i as w,j as T,kt as E,m as D,ot as O,q as k,u as A,w as j,y as M}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as N,A as P,C as F,E as I,F as L,I as ee,M as R,N as te,O as z,Qt as B,Xt as V,Yt as H,at as U,ct as W,en as G,h as K,it as ne,j as q,ln as J,on as re,ot as Y,pt as ie,tn as X,u as ae,ut as Z}from"./Button-CjW_3_5b.js";import{t as oe}from"./use-locale-DhR889QW.js";import{a as Q,c as se,i as ce,l as le,o as ue,s as de,t as fe}from"./Popover-CVRg6ES3.js";import{t as pe}from"./next-frame-once-qdYFoq8G.js";import{n as me}from"./AppPage-BdYkxGOX.js";import{i as he,n as ge,r as _e,t as ve}from"./create-C0zrQ-_N.js";import{a as ye,d as be,i as xe,o as Se,s as $,t as Ce,w as we}from"./light-BgIV-Nal.js";import{n as Te,r as Ee,t as De}from"./cssr-DQVwT7QU.js";import{t as Oe}from"./Tag-BtfU4pby.js";import{t as ke}from"./attribute-CYKlYlWW.js";import{n as Ae}from"./Input-Buw1BAO_.js";function je(e){return e&-e}var Me=class{constructor(e,t){this.l=e,this.min=t;let n=Array(e+1);for(let t=0;t<e+1;++t)n[t]=0;this.ft=n}add(e,t){if(t===0)return;let{l:n,ft:r}=this;for(e+=1;e<=n;)r[e]+=t,e+=je(e)}get(e){return this.sum(e+1)-this.sum(e)}sum(e){if(e===void 0&&(e=this.l),e<=0)return 0;let{ft:t,min:n,l:r}=this;if(e>r)throw Error("[FinweckTree.sum]: `i` is larger than length.");let i=e*n;for(;e>0;)i+=t[e],e-=je(e);return i}getBound(e){let t=0,n=this.l;for(;n>t;){let r=Math.floor((t+n)/2),i=this.sum(r);if(i>e){n=r;continue}if(i<e){if(t===r)return this.sum(t+1)<=e?t+1:r;t=r}else return r}return t}},Ne;function Pe(){return typeof document>`u`?!1:(Ne===void 0&&(Ne=`matchMedia`in window&&window.matchMedia(`(pointer:coarse)`).matches),Ne)}var Fe;function Ie(){return typeof document>`u`?1:(Fe===void 0&&(Fe=`chrome`in window?window.devicePixelRatio:1),Fe)}var Le=`VVirtualListXScroll`;function Re({columnsRef:e,renderColRef:t,renderItemWithColsRef:n}){let r=O(0),i=O(0),a=A(()=>{let t=e.value;if(t.length===0)return null;let n=new Me(t.length,0);return t.forEach((e,t)=>{n.add(t,e.width)}),n}),o=R(()=>{let e=a.value;return e===null?0:Math.max(e.getBound(i.value)-1,0)}),s=e=>{let t=a.value;return t===null?0:t.sum(e)},c=R(()=>{let t=a.value;return t===null?0:Math.min(t.getBound(i.value+r.value)+1,e.value.length-1)});return h(Le,{startIndexRef:o,endIndexRef:c,columnsRef:e,renderColRef:t,renderItemWithColsRef:n,getLeft:s}),{listWidthRef:r,scrollLeftRef:i}}var ze=M({name:`VirtualListRow`,props:{index:{type:Number,required:!0},item:{type:Object,required:!0}},setup(){let{startIndexRef:e,endIndexRef:t,columnsRef:n,getLeft:r,renderColRef:i,renderItemWithColsRef:a}=j(Le);return{startIndex:e,endIndex:t,columns:n,renderCol:i,renderItemWithCols:a,getLeft:r}},render(){let{startIndex:e,endIndex:t,columns:n,renderCol:r,renderItemWithCols:i,getLeft:a,item:o}=this;if(i!=null)return i({itemIndex:this.index,startColIndex:e,endColIndex:t,allColumns:n,item:o,getLeft:a});if(r!=null){let i=[];for(let s=e;s<=t;++s){let e=n[s];i.push(r({column:e,left:a(s),item:o}))}return i}return null}}),Be=De(`.v-vl`,{maxHeight:`inherit`,height:`100%`,overflow:`auto`,minWidth:`1px`},[De(`&:not(.v-vl--show-scrollbar)`,{scrollbarWidth:`none`},[De(`&::-webkit-scrollbar, &::-webkit-scrollbar-track-piece, &::-webkit-scrollbar-thumb`,{width:0,height:0,display:`none`})])]),Ve=M({name:`VirtualList`,inheritAttrs:!1,props:{showScrollbar:{type:Boolean,default:!0},columns:{type:Array,default:()=>[]},renderCol:Function,renderItemWithCols:Function,items:{type:Array,default:()=>[]},itemSize:{type:Number,required:!0},itemResizable:Boolean,itemsStyle:[String,Object],visibleItemsTag:{type:[String,Object],default:`div`},visibleItemsProps:Object,ignoreItemResize:Boolean,onScroll:Function,onWheel:Function,onResize:Function,defaultScrollKey:[Number,String],defaultScrollIndex:Number,keyField:{type:String,default:`key`},paddingTop:{type:[Number,String],default:0},paddingBottom:{type:[Number,String],default:0}},setup(e){let t=Z();Be.mount({id:`vueuc/virtual-list`,head:!0,anchorMetaName:Te,ssr:t}),g(()=>{let{defaultScrollIndex:t,defaultScrollKey:n}=e;t==null?n!=null&&b({key:n}):b({index:t})});let n=!1,r=!1;_(()=>{if(n=!1,!r){r=!0;return}b({top:h.value,left:c.value})}),T(()=>{n=!0,r||=!0});let o=R(()=>{if(e.renderCol==null&&e.renderItemWithCols==null||e.columns.length===0)return;let t=0;return e.columns.forEach(e=>{t+=e.width}),t}),s=A(()=>{let t=new Map,{keyField:n}=e;return e.items.forEach((e,r)=>{t.set(e[n],r)}),t}),{scrollLeftRef:c,listWidthRef:l}=Re({columnsRef:S(e,`columns`),renderColRef:S(e,`renderCol`),renderItemWithColsRef:S(e,`renderItemWithCols`)}),u=O(null),d=O(void 0),f=new Map,p=A(()=>{let{items:t,itemSize:n,keyField:r}=e,i=new Me(t.length,n);return t.forEach((e,t)=>{let n=e[r],a=f.get(n);a!==void 0&&i.add(t,a)}),i}),m=O(0),h=O(0),v=R(()=>Math.max(p.value.getBound(h.value-a(e.paddingTop))-1,0)),y=A(()=>{let{value:t}=d;if(t===void 0)return[];let{items:n,itemSize:r}=e,i=v.value,a=Math.min(i+Math.ceil(t/r+1),n.length-1),o=[];for(let e=i;e<=a;++e)o.push(n[e]);return o}),b=(e,t)=>{if(typeof e==`number`){E(e,t,`auto`);return}let{left:n,top:r,index:i,key:a,position:o,behavior:c,debounce:l=!0}=e;if(n!==void 0||r!==void 0)E(n,r,c);else if(i!==void 0)w(i,c,l);else if(a!==void 0){let e=s.value.get(a);e!==void 0&&w(e,c,l)}else o===`bottom`?E(0,2**53-1,c):o===`top`&&E(0,0,c)},x,C=null;function w(t,n,r){let i=u.value;if(i==null)return;let{value:o}=p,s=o.sum(t)+a(e.paddingTop);if(!r)i.scrollTo({left:0,top:s,behavior:n});else{x=t,C!==null&&window.clearTimeout(C),C=window.setTimeout(()=>{x=void 0,C=null},16);let{scrollTop:e,offsetHeight:r}=i;if(s>e){let a=o.get(t);s+a<=e+r||i.scrollTo({left:0,top:s+a-r,behavior:n})}else i.scrollTo({left:0,top:s,behavior:n})}}function E(e,t,n){u.value?.scrollTo({left:e,top:t,behavior:n})}function D(t,r){if(n||e.ignoreItemResize||I(r.target))return;let{value:i}=p,a=s.value.get(t),o=i.get(a),c=r.borderBoxSize?.[0]?.blockSize??r.contentRect.height;if(c===o)return;c-e.itemSize===0?f.delete(t):f.set(t,c-e.itemSize);let l=c-o;if(l===0)return;i.add(a,l);let d=u.value;if(d!=null){if(x===void 0){let e=i.sum(a);d.scrollTop>e&&d.scrollBy(0,l)}else(a<x||a===x&&c+i.sum(a)>d.scrollTop+d.offsetHeight)&&d.scrollBy(0,l);F()}m.value++}let k=!Pe(),j=!1;function M(t){var n;(n=e.onScroll)==null||n.call(e,t),(!k||!j)&&F()}function N(t){var n;if((n=e.onWheel)==null||n.call(e,t),k){let e=u.value;if(e!=null){if(t.deltaX===0&&(e.scrollTop===0&&t.deltaY<=0||e.scrollTop+e.offsetHeight>=e.scrollHeight&&t.deltaY>=0))return;t.preventDefault(),e.scrollTop+=t.deltaY/Ie(),e.scrollLeft+=t.deltaX/Ie(),F(),j=!0,pe(()=>{j=!1})}}}function P(t){if(n||I(t.target))return;if(e.renderCol==null&&e.renderItemWithCols==null){if(t.contentRect.height===d.value)return}else if(t.contentRect.height===d.value&&t.contentRect.width===l.value)return;d.value=t.contentRect.height,l.value=t.contentRect.width;let{onResize:r}=e;r!==void 0&&r(t)}function F(){let{value:e}=u;e!=null&&(h.value=e.scrollTop,c.value=e.scrollLeft)}function I(e){let t=e;for(;t!==null;){if(t.style.display===`none`)return!0;t=t.parentElement}return!1}return{listHeight:d,listStyle:{overflow:`auto`},keyToIndex:s,itemsStyle:A(()=>{let{itemResizable:t}=e,n=i(p.value.sum());return m.value,[e.itemsStyle,{boxSizing:`content-box`,width:i(o.value),height:t?``:n,minHeight:t?n:``,paddingTop:i(e.paddingTop),paddingBottom:i(e.paddingBottom)}]}),visibleItemsStyle:A(()=>(m.value,{transform:`translateY(${i(p.value.sum(v.value))})`})),viewportItems:y,listElRef:u,itemsElRef:O(null),scrollTo:b,handleListResize:P,handleListScroll:M,handleListWheel:N,handleItemResize:D}},render(){let{itemResizable:e,keyField:t,keyToIndex:n,visibleItemsTag:r}=this;return v(s,{onResize:this.handleListResize},{default:()=>{var i;return v(`div`,f(this.$attrs,{class:[`v-vl`,this.showScrollbar&&`v-vl--show-scrollbar`],onScroll:this.handleListScroll,onWheel:this.handleListWheel,ref:`listElRef`}),[this.items.length===0?(i=this.$slots).empty?.call(i):v(`div`,{ref:`itemsElRef`,class:`v-vl-items`,style:this.itemsStyle},[v(r,Object.assign({class:`v-vl-visible-items`,style:this.visibleItemsStyle},this.visibleItemsProps),{default:()=>{let{renderCol:r,renderItemWithCols:i}=this;return this.viewportItems.map(a=>{let o=a[t],c=n.get(o),l=r==null?void 0:v(ze,{index:c,item:a}),u=i==null?void 0:v(ze,{index:c,item:a}),d=this.$slots.default({item:a,renderedCols:l,renderedItemWithCols:u,index:c})[0];return e?v(s,{key:o,onResize:e=>this.handleItemResize(o,e)},{default:()=>d}):(d.key=o,d)})}})])])}})}});function He(e,t){t&&(g(()=>{let{value:n}=e;n&&r.registerHandler(n,t)}),m(e,(e,t)=>{t&&r.unregisterHandler(t)},{deep:!1}),u(()=>{let{value:t}=e;t&&r.unregisterHandler(t)}))}var Ue=M({props:{onFocus:Function,onBlur:Function},setup(e){return()=>(()=>{let t=ne(`d16ead82505dc285`);return p(),D(`div`,{style:`width: 0; height: 0`,tabindex:0,onFocus:t[0]||=t=>e.onFocus?.(t),onBlur:t[1]||=t=>e.onBlur?.(t)},null,32)})()}}),We=M({name:`NBaseSelectGroupHeader`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(){let{renderLabelRef:e,renderOptionRef:t,labelFieldRef:n,nodePropsRef:r}=j(le);return{labelField:n,nodeProps:r,renderLabel:e,renderOption:t}},render(){let{clsPrefix:e,renderLabel:t,renderOption:n,nodeProps:r,tmNode:{rawNode:i}}=this,a=r?.(i),o=t?t(i,!1):$(i[this.labelField],i,!1),s=(p(),D(`div`,f(a,{class:[`${e}-base-select-group-header`,a?.class]}),[W(()=>o)],16));return i.render?i.render({node:s,option:i}):n?n({node:s,option:i,selected:!1}):s}}),Ge=M({name:`Checkmark`,render(){return(()=>{let e=ne(`3c84eac8ae4e1f96`);return e[0]||=x(`svg`,{xmlns:`http://www.w3.org/2000/svg`,viewBox:`0 0 16 16`},[x(`g`,{fill:`none`},[x(`path`,{d:`M14.046 3.486a.75.75 0 0 1-.032 1.06l-7.93 7.474a.85.85 0 0 1-1.188-.022l-2.68-2.72a.75.75 0 1 1 1.068-1.053l2.234 2.267l7.468-7.038a.75.75 0 0 1 1.06.032z`,fill:`currentColor`})])],-1)})()}}),Ke=[`onClick`,`onMouseenter`,`onMousemove`];function qe(e,t){return p(),C(re,{name:`fade-in-scale-up-transition`},{default:()=>e?(p(),C(te,{key:1,clsPrefix:t,class:U(`${t}-base-select-option__check`)},{default:()=>v(Ge)},1032,[`clsPrefix`,`class`])):null},1024)}var Je=M({name:`NBaseSelectOption`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(e){let{valueRef:t,pendingTmNodeRef:n,multipleRef:r,valueSetRef:i,renderLabelRef:a,renderOptionRef:o,labelFieldRef:s,valueFieldRef:c,showCheckmarkRef:l,nodePropsRef:u,handleOptionClick:d,handleOptionMouseEnter:f}=j(le),p=R(()=>{let{value:t}=n;return t?e.tmNode.key===t.key:!1});function m(t){let{tmNode:n}=e;n.disabled||d(t,n)}function h(t){let{tmNode:n}=e;n.disabled||f(t,n)}function g(t){let{tmNode:n}=e,{value:r}=p;n.disabled||r||f(t,n)}return{multiple:r,isGrouped:R(()=>{let{tmNode:t}=e,{parent:n}=t;return n&&n.rawNode.type===`group`}),showCheckmark:l,nodeProps:u,isPending:p,isSelected:R(()=>{let{value:n}=t,{value:a}=r;if(n===null)return!1;let o=e.tmNode.rawNode[c.value];if(a){let{value:e}=i;return e.has(o)}return n===o}),labelField:s,renderLabel:a,renderOption:o,handleMouseMove:g,handleMouseEnter:h,handleClick:m}},render(){let{clsPrefix:e,tmNode:{rawNode:t},isSelected:n,isPending:r,isGrouped:i,showCheckmark:a,nodeProps:o,renderOption:s,renderLabel:c,handleClick:l,handleMouseEnter:u,handleMouseMove:d}=this,m=qe(n,e),h=c?[c(t,n),a&&m]:[$(t[this.labelField],t,n),a&&m],g=o?.(t),_=(p(),D(`div`,f(g,{class:[`${e}-base-select-option`,t.class,g?.class,{[`${e}-base-select-option--disabled`]:t.disabled,[`${e}-base-select-option--selected`]:n,[`${e}-base-select-option--grouped`]:i,[`${e}-base-select-option--pending`]:r,[`${e}-base-select-option--show-checkmark`]:a}],style:[g?.style||``,t.style||``],onClick:Se([l,g?.onClick]),onMouseenter:Se([u,g?.onMouseenter]),onMousemove:Se([d,g?.onMousemove])}),[x(`div`,{class:U(`${e}-base-select-option__content`)},[W(()=>h)],2)],16,Ke));return t.render?t.render({node:_,option:t,selected:n}):s?s({node:_,option:t,selected:n}):_}}),Ye=V(`base-select-menu`,`
 line-height: 1.5;
 outline: none;
 z-index: 0;
 position: relative;
 border-radius: var(--n-border-radius);
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 background-color: var(--n-color);
`,[V(`scrollbar`,`
 max-height: var(--n-height);
 `),V(`virtual-list`,`
 max-height: var(--n-height);
 `),V(`base-select-option`,`
 min-height: var(--n-option-height);
 font-size: var(--n-option-font-size);
 display: flex;
 align-items: center;
 `,[B(`content`,`
 z-index: 1;
 white-space: nowrap;
 text-overflow: ellipsis;
 overflow: hidden;
 `)]),V(`base-select-group-header`,`
 min-height: var(--n-option-height);
 font-size: .93em;
 display: flex;
 align-items: center;
 `),V(`base-select-menu-option-wrapper`,`
 position: relative;
 width: 100%;
 `),B(`loading, empty`,`
 display: flex;
 padding: 12px 32px;
 flex: 1;
 justify-content: center;
 `),B(`loading`,`
 color: var(--n-loading-color);
 font-size: var(--n-loading-size);
 `),B(`header`,`
 padding: 8px var(--n-option-padding-left);
 font-size: var(--n-option-font-size);
 transition: 
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 border-bottom: 1px solid var(--n-action-divider-color);
 color: var(--n-action-text-color);
 `),B(`action`,`
 padding: 8px var(--n-option-padding-left);
 font-size: var(--n-option-font-size);
 transition: 
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 border-top: 1px solid var(--n-action-divider-color);
 color: var(--n-action-text-color);
 `),V(`base-select-group-header`,`
 position: relative;
 cursor: default;
 padding: var(--n-option-padding);
 color: var(--n-group-header-text-color);
 `),V(`base-select-option`,`
 cursor: pointer;
 position: relative;
 padding: var(--n-option-padding);
 transition:
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 box-sizing: border-box;
 color: var(--n-option-text-color);
 opacity: 1;
 `,[N(`show-checkmark`,`
 padding-right: calc(var(--n-option-padding-right) + 20px);
 `),H(`&::before`,`
 content: "";
 position: absolute;
 left: 4px;
 right: 4px;
 top: 0;
 bottom: 0;
 border-radius: var(--n-border-radius);
 transition: background-color .3s var(--n-bezier);
 `),H(`&:active`,`
 color: var(--n-option-text-color-pressed);
 `),N(`grouped`,`
 padding-left: calc(var(--n-option-padding-left) * 1.5);
 `),N(`pending`,[H(`&::before`,`
 background-color: var(--n-option-color-pending);
 `)]),N(`selected`,`
 color: var(--n-option-text-color-active);
 `,[H(`&::before`,`
 background-color: var(--n-option-color-active);
 `),N(`pending`,[H(`&::before`,`
 background-color: var(--n-option-color-active-pending);
 `)])]),N(`disabled`,`
 cursor: not-allowed;
 `,[G(`selected`,`
 color: var(--n-option-text-color-disabled);
 `),N(`selected`,`
 opacity: var(--n-option-opacity-disabled);
 `)]),B(`check`,`
 font-size: 16px;
 position: absolute;
 right: calc(var(--n-option-padding-right) - 4px);
 top: calc(50% - 7px);
 color: var(--n-option-check-color);
 transition: color .3s var(--n-bezier);
 `,[l({enterScale:`0.5`})])])]),Xe=[`tabindex`,`onFocusin`,`onFocusout`,`onKeyup`,`onKeydown`,`onMousedown`,`onMouseenter`,`onMouseleave`],Ze=M({name:`InternalSelectMenu`,props:{...L.props,clsPrefix:{type:String,required:!0},scrollable:{type:Boolean,default:!0},treeMate:{type:Object,required:!0},multiple:Boolean,size:{type:String,default:`medium`},value:{type:[String,Number,Array],default:null},autoPending:Boolean,virtualScroll:{type:Boolean,default:!0},show:{type:Boolean,default:!0},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},loading:Boolean,focusable:Boolean,renderLabel:Function,renderOption:Function,nodeProps:Function,showCheckmark:{type:Boolean,default:!0},onMousedown:Function,onScroll:Function,onFocus:Function,onBlur:Function,onKeyup:Function,onKeydown:Function,onTabOut:Function,onMouseenter:Function,onMouseleave:Function,onResize:Function,resetMenuOnOptionsChange:{type:Boolean,default:!0},inlineThemeDisabled:Boolean,scrollbarProps:Object,onToggle:Function},setup(e){let{mergedClsPrefixRef:t,mergedRtlRef:n,mergedComponentPropsRef:r}=ie(e),i=F(`InternalSelectMenu`,n,t),s=L(`InternalSelectMenu`,`-internal-select-menu`,Ye,we,e,S(e,`clsPrefix`)),c=O(null),l=O(null),f=O(null),p=A(()=>e.treeMate.getFlattenedNodes()),_=A(()=>ge(p.value)),v=O(null);function y(){let{treeMate:t}=e,n=null,{value:r}=e;r===null?n=t.getFirstAvailableNode():(n=e.multiple?t.getNode((r||[])[(r||[]).length-1]):t.getNode(r),(!n||n.disabled)&&(n=t.getFirstAvailableNode())),U(n||null)}function b(){let{value:t}=v;t&&!e.treeMate.getNode(t.key)&&(v.value=null)}let x;m(()=>e.show,t=>{t?x=m(()=>e.treeMate,()=>{e.resetMenuOnOptionsChange?(e.autoPending?y():b(),d(W)):b()},{immediate:!0}):x?.()},{immediate:!0}),u(()=>{x?.()});let C=A(()=>a(s.value.self[X(`optionHeight`,e.size)])),w=A(()=>o(s.value.self[X(`padding`,e.size)])),T=A(()=>e.multiple&&Array.isArray(e.value)?new Set(e.value):new Set),E=A(()=>{let e=p.value;return e&&e.length===0}),D=A(()=>r?.value?.Select?.renderEmpty);function k(t){let{onToggle:n}=e;n&&n(t)}function j(t){let{onScroll:n}=e;n&&n(t)}function M(e){f.value?.sync(),j(e)}function N(){f.value?.sync()}function P(){let{value:e}=v;return e||null}function I(e,t){t.disabled||U(t,!1)}function R(e,t){t.disabled||k(t)}function te(t){he(t,`action`)||e.onKeyup?.(t)}function z(t){he(t,`action`)||e.onKeydown?.(t)}function B(t){e.onMousedown?.(t),!e.focusable&&t.preventDefault()}function V(){let{value:e}=v;e&&U(e.getNext({loop:!0}),!0)}function H(){let{value:e}=v;e&&U(e.getPrev({loop:!0}),!0)}function U(e,t=!1){v.value=e,t&&W()}function W(){let t=v.value;if(!t)return;let n=_.value(t.key);n!==null&&(e.virtualScroll?l.value?.scrollTo({index:n}):f.value?.scrollTo({index:n,elSize:C.value}))}function G(t){c.value?.contains(t.target)&&e.onFocus?.(t)}function K(t){c.value?.contains(t.relatedTarget)||e.onBlur?.(t)}h(le,{handleOptionMouseEnter:I,handleOptionClick:R,valueSetRef:T,pendingTmNodeRef:v,nodePropsRef:S(e,`nodeProps`),showCheckmarkRef:S(e,`showCheckmark`),multipleRef:S(e,`multiple`),valueRef:S(e,`value`),renderLabelRef:S(e,`renderLabel`),renderOptionRef:S(e,`renderOption`),labelFieldRef:S(e,`labelField`),valueFieldRef:S(e,`valueField`)}),h(se,c),g(()=>{let{value:e}=f;e&&e.sync()});let ne=A(()=>{let{size:t}=e,{common:{cubicBezierEaseInOut:n},self:{height:r,borderRadius:i,color:a,groupHeaderTextColor:c,actionDividerColor:l,optionTextColorPressed:u,optionTextColor:d,optionTextColorDisabled:f,optionTextColorActive:p,optionOpacityDisabled:m,optionCheckColor:h,actionTextColor:g,optionColorPending:_,optionColorActive:v,loadingColor:y,loadingSize:b,optionColorActivePending:x,[X(`optionFontSize`,t)]:S,[X(`optionHeight`,t)]:C,[X(`optionPadding`,t)]:w}}=s.value;return{"--n-height":r,"--n-action-divider-color":l,"--n-action-text-color":g,"--n-bezier":n,"--n-border-radius":i,"--n-color":a,"--n-option-font-size":S,"--n-group-header-text-color":c,"--n-option-check-color":h,"--n-option-color-pending":_,"--n-option-color-active":v,"--n-option-color-active-pending":x,"--n-option-height":C,"--n-option-opacity-disabled":m,"--n-option-text-color":d,"--n-option-text-color-active":p,"--n-option-text-color-disabled":f,"--n-option-text-color-pressed":u,"--n-option-padding":w,"--n-option-padding-left":o(w,`left`),"--n-option-padding-right":o(w,`right`),"--n-loading-color":y,"--n-loading-size":b}}),{inlineThemeDisabled:q}=e,J=q?ee(`internal-select-menu`,A(()=>e.size[0]),ne,e):void 0,re={selfRef:c,next:V,prev:H,getPendingTmNode:P};return He(c,e.onResize),{mergedTheme:s,mergedClsPrefix:t,rtlEnabled:i,virtualListRef:l,scrollbarRef:f,itemSize:C,padding:w,flattenedNodes:p,empty:E,mergedRenderEmpty:D,virtualListContainer(){let{value:e}=l;return e?.listElRef},virtualListContent(){let{value:e}=l;return e?.itemsElRef},doScroll:j,handleFocusin:G,handleFocusout:K,handleKeyUp:te,handleKeyDown:z,handleMouseDown:B,handleVirtualListResize:N,handleVirtualListScroll:M,cssVars:q?void 0:ne,themeClass:J?.themeClass,onRender:J?.onRender,...re}},render(){let{$slots:e,virtualScroll:t,clsPrefix:n,mergedTheme:r,themeClass:i,onRender:a}=this;return a?.(),p(),D(`div`,{ref:`selfRef`,tabindex:this.focusable?0:-1,class:U([`${n}-base-select-menu`,`${n}-base-select-menu--${this.size}-size`,this.rtlEnabled&&`${n}-base-select-menu--rtl`,i,this.multiple&&`${n}-base-select-menu--multiple`]),style:E(this.cssVars),onFocusin:this.handleFocusin,onFocusout:this.handleFocusout,onKeyup:this.handleKeyUp,onKeydown:this.handleKeyDown,onMousedown:this.handleMouseDown,onMouseenter:this.onMouseenter,onMouseleave:this.onMouseleave},[W(()=>z(e.header,e=>e&&(p(),D(`div`,{class:U(`${n}-base-select-menu__header`),"data-header":!0,key:`header`},[W(()=>e)],2)))),this.loading?(p(),D(`div`,{key:0,class:U(`${n}-base-select-menu__loading`)},[(p(),C(ae,{clsPrefix:n,strokeWidth:20},null,8,[`clsPrefix`]))],2)):(p(),D(w,{key:1},[this.empty?(p(),D(`div`,{key:1,class:U(`${n}-base-select-menu__empty`),"data-empty":!0},[W(()=>I(e.empty,()=>[this.mergedRenderEmpty?.()||(p(),C(me,{theme:r.peers.Empty,themeOverrides:r.peerOverrides.Empty,size:this.size},null,8,[`theme`,`themeOverrides`,`size`]))]))],2)):(p(),C(c,f({key:0,ref:`scrollbarRef`,theme:r.peers.Scrollbar,themeOverrides:r.peerOverrides.Scrollbar,scrollable:this.scrollable,container:t?this.virtualListContainer:void 0,content:t?this.virtualListContent:void 0,onScroll:t?void 0:this.doScroll},this.scrollbarProps),{default:()=>t?(p(),C(Ve,{key:1,ref:`virtualListRef`,class:U(`${n}-virtual-list`),items:this.flattenedNodes,itemSize:this.itemSize,showScrollbar:!1,paddingTop:this.padding.top,paddingBottom:this.padding.bottom,onResize:this.handleVirtualListResize,onScroll:this.handleVirtualListScroll,itemResizable:!0},{default:({item:e})=>e.isGroup?(p(),C(We,{key:e.key,clsPrefix:n,tmNode:e},null,8,[`clsPrefix`,`tmNode`])):e.ignored?null:(p(),C(Je,{clsPrefix:n,key:e.key,tmNode:e},null,8,[`clsPrefix`,`tmNode`]))},1032,[`class`,`items`,`itemSize`,`paddingTop`,`paddingBottom`,`onResize`,`onScroll`])):(p(),D(`div`,{key:4,class:U(`${n}-base-select-menu-option-wrapper`),style:E({paddingTop:this.padding.top,paddingBottom:this.padding.bottom})},[W(()=>this.flattenedNodes.map(e=>e.isGroup?(p(),C(We,{key:e.key,clsPrefix:n,tmNode:e},null,8,[`clsPrefix`,`tmNode`])):(p(),C(Je,{clsPrefix:n,key:e.key,tmNode:e},null,8,[`clsPrefix`,`tmNode`]))))],6))},1040,[`theme`,`themeOverrides`,`scrollable`,`container`,`content`,`onScroll`]))],64)),W(()=>z(e.action,e=>e&&[(p(),D(`div`,{class:U(`${n}-base-select-menu__action`),"data-action":!0,key:`action`},[W(()=>e)],2)),(p(),C(Ue,{onFocus:this.onTabOut,key:`focus-detector`},null,8,[`onFocus`]))]))],46,Xe)}});function Qe(e){return e.type===`group`}function $e(e){return e.type===`ignored`}function et(e,t){try{return!!(1+t.toString().toLowerCase().indexOf(e.trim().toLowerCase()))}catch{return!1}}function tt(e,t){return{getIsGroup:Qe,getIgnored:$e,getKey(t){return Qe(t)?t.name||t.key||`key-required`:t[e]},getChildren(e){return e[t]}}}function nt(e,t,n,r){if(!t)return e;function i(e){if(!Array.isArray(e))return[];let a=[];for(let o of e)if(Qe(o)){let e=i(o[r]);e.length&&a.push(Object.assign({},o,{[r]:e}))}else if($e(o))continue;else t(n,o)&&a.push(o);return a}return i(e)}function rt(e,t,n){let r=new Map;return e.forEach(e=>{Qe(e)?e[n].forEach(e=>{r.set(e[t],e)}):r.set(e[t],e)}),r}var it=H([V(`base-selection`,`
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
 `,[V(`base-loading`,`
 color: var(--n-loading-color);
 `),V(`base-selection-tags`,`min-height: var(--n-height);`),B(`border, state-border`,`
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
 `),B(`state-border`,`
 z-index: 1;
 border-color: #0000;
 `),V(`base-suffix`,`
 cursor: pointer;
 position: absolute;
 top: 50%;
 transform: translateY(-50%);
 right: 10px;
 `,[B(`arrow`,`
 font-size: var(--n-arrow-size);
 color: var(--n-arrow-color);
 transition: color .3s var(--n-bezier);
 `)]),V(`base-selection-overlay`,`
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
 `,[B(`wrapper`,`
 flex-basis: 0;
 flex-grow: 1;
 overflow: hidden;
 text-overflow: ellipsis;
 `)]),V(`base-selection-placeholder`,`
 color: var(--n-placeholder-color);
 `,[B(`inner`,`
 max-width: 100%;
 overflow: hidden;
 `)]),V(`base-selection-tags`,`
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
 `),V(`base-selection-label`,`
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
 `,[V(`base-selection-input`,`
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
 `,[B(`content`,`
 text-overflow: ellipsis;
 overflow: hidden;
 white-space: nowrap; 
 `)]),B(`render-label`,`
 color: var(--n-text-color);
 `)]),G(`disabled`,[H(`&:hover`,[B(`state-border`,`
 box-shadow: var(--n-box-shadow-hover);
 border: var(--n-border-hover);
 `)]),N(`focus`,[B(`state-border`,`
 box-shadow: var(--n-box-shadow-focus);
 border: var(--n-border-focus);
 `)]),N(`active`,[B(`state-border`,`
 box-shadow: var(--n-box-shadow-active);
 border: var(--n-border-active);
 `),V(`base-selection-label`,`background-color: var(--n-color-active);`),V(`base-selection-tags`,`background-color: var(--n-color-active);`)])]),N(`disabled`,`cursor: not-allowed;`,[B(`arrow`,`
 color: var(--n-arrow-color-disabled);
 `),V(`base-selection-label`,`
 cursor: not-allowed;
 background-color: var(--n-color-disabled);
 `,[V(`base-selection-input`,`
 cursor: not-allowed;
 color: var(--n-text-color-disabled);
 `),B(`render-label`,`
 color: var(--n-text-color-disabled);
 `)]),V(`base-selection-tags`,`
 cursor: not-allowed;
 background-color: var(--n-color-disabled);
 `),V(`base-selection-placeholder`,`
 cursor: not-allowed;
 color: var(--n-placeholder-color-disabled);
 `)]),V(`base-selection-input-tag`,`
 height: calc(var(--n-height) - 6px);
 line-height: calc(var(--n-height) - 6px);
 outline: none;
 display: none;
 position: relative;
 margin-bottom: 3px;
 max-width: 100%;
 vertical-align: bottom;
 `,[B(`input`,`
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
 `),B(`mirror`,`
 position: absolute;
 left: 0;
 top: 0;
 white-space: pre;
 visibility: hidden;
 user-select: none;
 -webkit-user-select: none;
 opacity: 0;
 `)]),[`warning`,`error`].map(e=>N(`${e}-status`,[B(`state-border`,`border: var(--n-border-${e});`),G(`disabled`,[H(`&:hover`,[B(`state-border`,`
 box-shadow: var(--n-box-shadow-hover-${e});
 border: var(--n-border-hover-${e});
 `)]),N(`active`,[B(`state-border`,`
 box-shadow: var(--n-box-shadow-active-${e});
 border: var(--n-border-active-${e});
 `),V(`base-selection-label`,`background-color: var(--n-color-active-${e});`),V(`base-selection-tags`,`background-color: var(--n-color-active-${e});`)]),N(`focus`,[B(`state-border`,`
 box-shadow: var(--n-box-shadow-focus-${e});
 border: var(--n-border-focus-${e});
 `)])])]))]),V(`base-selection-popover`,`
 margin-bottom: -3px;
 display: flex;
 flex-wrap: wrap;
 margin-right: -8px;
 `),V(`base-selection-tag-wrapper`,`
 max-width: 100%;
 display: inline-flex;
 padding: 0 7px 3px 0;
 `,[H(`&:last-child`,`padding-right: 0;`),V(`tag`,`
 font-size: 14px;
 max-width: 100%;
 `,[B(`content`,`
 line-height: 1.25;
 text-overflow: ellipsis;
 overflow: hidden;
 `)])])]),at=[`disabled`,`value`,`autofocus`,`onBlur`,`onFocus`,`onKeydown`,`onInput`,`onCompositionstart`,`onCompositionend`],ot=[`tabindex`],st=[`title`],ct=[`value`,`readonly`,`disabled`,`autofocus`,`onFocus`,`onBlur`,`onInput`,`onCompositionstart`,`onCompositionend`],lt=[`tabindex`],ut=[`onClick`,`onMouseenter`,`onMouseleave`,`onKeydown`,`onFocusin`,`onFocusout`,`onMousedown`],dt=M({name:`InternalSelection`,props:{...L.props,clsPrefix:{type:String,required:!0},bordered:{type:Boolean,default:void 0},active:Boolean,pattern:{type:String,default:``},placeholder:String,selectedOption:{type:Object,default:null},selectedOptions:{type:Array,default:null},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},multiple:Boolean,filterable:Boolean,clearable:Boolean,disabled:Boolean,size:{type:String,default:`medium`},loading:Boolean,autofocus:Boolean,showArrow:{type:Boolean,default:!0},inputProps:Object,focused:Boolean,renderTag:Function,onKeydown:Function,onClick:Function,onBlur:Function,onFocus:Function,onDeleteOption:Function,maxTagCount:[String,Number],ellipsisTagPopoverProps:Object,onClear:Function,onPatternInput:Function,onPatternFocus:Function,onPatternBlur:Function,renderLabel:Function,status:String,inlineThemeDisabled:Boolean,ignoreComposition:{type:Boolean,default:!0},onResize:Function},setup(e){let{mergedClsPrefixRef:t,mergedRtlRef:n}=ie(e),r=F(`InternalSelection`,n,t),i=O(null),a=O(null),s=O(null),c=O(null),l=O(null),u=O(null),f=O(null),p=O(null),h=O(null),_=O(null),v=O(!1),y=O(!1),b=O(!1),x=L(`InternalSelection`,`-internal-selection`,it,ye,e,S(e,`clsPrefix`)),C=A(()=>e.clearable&&!e.disabled&&(b.value||e.active)),w=A(()=>e.selectedOption?e.renderTag?e.renderTag({option:e.selectedOption,handleClose:()=>{}}):e.renderLabel?e.renderLabel(e.selectedOption,!0):$(e.selectedOption[e.labelField],e.selectedOption,!0):e.placeholder),T=A(()=>{let t=e.selectedOption;if(t)return t[e.labelField]}),E=A(()=>e.multiple?!!(Array.isArray(e.selectedOptions)&&e.selectedOptions.length):e.selectedOption!==null);function D(){let{value:t}=i;if(t){let{value:n}=a;n&&(n.style.width=`${t.offsetWidth}px`,e.maxTagCount!==`responsive`&&h.value?.sync({showAllItemsBeforeCalculate:!1}))}}function j(){let{value:e}=_;e&&(e.style.display=`none`)}function M(){let{value:e}=_;e&&(e.style.display=`inline-block`)}m(S(e,`active`),e=>{e||j()}),m(S(e,`pattern`),()=>{e.multiple&&d(D)});function N(t){let{onFocus:n}=e;n&&n(t)}function P(t){let{onBlur:n}=e;n&&n(t)}function I(t){let{onDeleteOption:n}=e;n&&n(t)}function R(t){let{onClear:n}=e;n&&n(t)}function te(t){let{onPatternInput:n}=e;n&&n(t)}function z(e){(!e.relatedTarget||!s.value?.contains(e.relatedTarget))&&N(e)}function B(e){s.value?.contains(e.relatedTarget)||P(e)}function V(e){R(e)}function H(){b.value=!0}function U(){b.value=!1}function W(t){e.active&&e.filterable&&t.target!==a.value&&t.preventDefault()}function G(e){I(e)}let K=O(!1);function ne(t){if(t.key===`Backspace`&&!K.value&&!e.pattern.length){let{selectedOptions:t}=e;t?.length&&G(t[t.length-1])}}let q=null;function J(t){let{value:n}=i;n&&(n.textContent=t.target.value,D()),e.ignoreComposition&&K.value?q=t:te(t)}function re(){K.value=!0}function Y(){K.value=!1,e.ignoreComposition&&te(q),q=null}function ae(t){y.value=!0,e.onPatternFocus?.(t)}function Z(t){y.value=!1,e.onPatternBlur?.(t)}function oe(){if(e.filterable)y.value=!1,u.value?.blur(),a.value?.blur();else if(e.multiple){let{value:e}=c;e?.blur()}else{let{value:e}=l;e?.blur()}}function Q(){e.filterable?(y.value=!1,u.value?.focus()):e.multiple?c.value?.focus():l.value?.focus()}function se(){let{value:e}=a;e&&(M(),e.focus())}function ce(){let{value:e}=a;e&&e.blur()}function le(e){let{value:t}=f;t&&t.setTextContent(`+${e}`)}function ue(){let{value:e}=p;return e}function de(){return a.value}let fe=null;function pe(){fe!==null&&window.clearTimeout(fe)}function me(){e.active||(pe(),fe=window.setTimeout(()=>{E.value&&(v.value=!0)},100))}function he(){pe()}function ge(e){e||(pe(),v.value=!1)}m(E,e=>{e||(v.value=!1)}),g(()=>{k(()=>{let t=u.value;t&&(e.disabled?t.removeAttribute(`tabindex`):t.tabIndex=y.value?-1:0)})}),He(s,e.onResize);let{inlineThemeDisabled:_e}=e,ve=A(()=>{let{size:t}=e,{common:{cubicBezierEaseInOut:n},self:{fontWeight:r,borderRadius:i,color:a,placeholderColor:s,textColor:c,paddingSingle:l,paddingMultiple:u,caretColor:d,colorDisabled:f,textColorDisabled:p,placeholderColorDisabled:m,colorActive:h,boxShadowFocus:g,boxShadowActive:_,boxShadowHover:v,border:y,borderFocus:b,borderHover:S,borderActive:C,arrowColor:w,arrowColorDisabled:T,loadingColor:E,colorActiveWarning:D,boxShadowFocusWarning:O,boxShadowActiveWarning:k,boxShadowHoverWarning:A,borderWarning:j,borderFocusWarning:M,borderHoverWarning:N,borderActiveWarning:P,colorActiveError:F,boxShadowFocusError:I,boxShadowActiveError:L,boxShadowHoverError:ee,borderError:R,borderFocusError:te,borderHoverError:z,borderActiveError:B,clearColor:V,clearColorHover:H,clearColorPressed:U,clearSize:W,arrowSize:G,[X(`height`,t)]:K,[X(`fontSize`,t)]:ne}}=x.value,q=o(l),J=o(u);return{"--n-bezier":n,"--n-border":y,"--n-border-active":C,"--n-border-focus":b,"--n-border-hover":S,"--n-border-radius":i,"--n-box-shadow-active":_,"--n-box-shadow-focus":g,"--n-box-shadow-hover":v,"--n-caret-color":d,"--n-color":a,"--n-color-active":h,"--n-color-disabled":f,"--n-font-size":ne,"--n-height":K,"--n-padding-single-top":q.top,"--n-padding-multiple-top":J.top,"--n-padding-single-right":q.right,"--n-padding-multiple-right":J.right,"--n-padding-single-left":q.left,"--n-padding-multiple-left":J.left,"--n-padding-single-bottom":q.bottom,"--n-padding-multiple-bottom":J.bottom,"--n-placeholder-color":s,"--n-placeholder-color-disabled":m,"--n-text-color":c,"--n-text-color-disabled":p,"--n-arrow-color":w,"--n-arrow-color-disabled":T,"--n-loading-color":E,"--n-color-active-warning":D,"--n-box-shadow-focus-warning":O,"--n-box-shadow-active-warning":k,"--n-box-shadow-hover-warning":A,"--n-border-warning":j,"--n-border-focus-warning":M,"--n-border-hover-warning":N,"--n-border-active-warning":P,"--n-color-active-error":F,"--n-box-shadow-focus-error":I,"--n-box-shadow-active-error":L,"--n-box-shadow-hover-error":ee,"--n-border-error":R,"--n-border-focus-error":te,"--n-border-hover-error":z,"--n-border-active-error":B,"--n-clear-size":W,"--n-clear-color":V,"--n-clear-color-hover":H,"--n-clear-color-pressed":U,"--n-arrow-size":G,"--n-font-weight":r}}),be=_e?ee(`internal-selection`,A(()=>e.size[0]),ve,e):void 0;return{mergedTheme:x,mergedClearable:C,mergedClsPrefix:t,rtlEnabled:r,patternInputFocused:y,filterablePlaceholder:w,label:T,selected:E,showTagsPanel:v,isComposing:K,counterRef:f,counterWrapperRef:p,patternInputMirrorRef:i,patternInputRef:a,selfRef:s,multipleElRef:c,singleElRef:l,patternInputWrapperRef:u,overflowRef:h,inputTagElRef:_,handleMouseDown:W,handleFocusin:z,handleClear:V,handleMouseEnter:H,handleMouseLeave:U,handleDeleteOption:G,handlePatternKeyDown:ne,handlePatternInputInput:J,handlePatternInputBlur:Z,handlePatternInputFocus:ae,handleMouseEnterCounter:me,handleMouseLeaveCounter:he,handleFocusout:B,handleCompositionEnd:Y,handleCompositionStart:re,onPopoverUpdateShow:ge,focus:Q,focusInput:se,blur:oe,blurInput:ce,updateCounter:le,getCounter:ue,getTail:de,renderLabel:e.renderLabel,cssVars:_e?void 0:ve,themeClass:be?.themeClass,onRender:be?.onRender}},render(){let{status:e,multiple:t,size:r,disabled:i,filterable:a,maxTagCount:o,bordered:s,clsPrefix:c,ellipsisTagPopoverProps:l,onRender:u,renderTag:d,renderLabel:m}=this;u?.();let h=o===`responsive`,g=typeof o==`number`,_=h||g,v=(p(),C(n,null,{default:()=>(p(),C(Ae,{clsPrefix:c,loading:this.loading,showArrow:this.showArrow,showClear:this.mergedClearable&&this.selected,onClear:this.handleClear},{default:()=>this.$slots.arrow?.()},1032,[`clsPrefix`,`loading`,`showArrow`,`showClear`,`onClear`]))},1024)),y;if(t){let{labelField:e}=this,t=t=>(p(),D(`div`,{class:U(`${c}-base-selection-tag-wrapper`),key:t.value},[d?(p(),D(w,{key:0},[W(()=>d({option:t,handleClose:()=>{this.handleDeleteOption(t)}}))],64)):(p(),C(Oe,{key:1,size:r,closable:!t.disabled,disabled:i,onClose:()=>{this.handleDeleteOption(t)},internalCloseIsButtonTag:!1,internalCloseFocusable:!1},{default:()=>m?m(t,!0):$(t[e],t,!0)},1032,[`size`,`closable`,`disabled`,`onClose`]))],2)),n=()=>(g?this.selectedOptions.slice(0,o):this.selectedOptions).map(t),s=a?(p(),D(`div`,{class:U(`${c}-base-selection-input-tag`),ref:`inputTagElRef`,key:`__input-tag__`},[x(`input`,f(this.inputProps,{ref:`patternInputRef`,tabindex:-1,disabled:i,value:this.pattern,autofocus:this.autofocus,class:`${c}-base-selection-input-tag__input`,onBlur:this.handlePatternInputBlur,onFocus:this.handlePatternInputFocus,onKeydown:this.handlePatternKeyDown,onInput:this.handlePatternInputInput,onCompositionstart:this.handleCompositionStart,onCompositionend:this.handleCompositionEnd}),null,16,at),x(`span`,{ref:`patternInputMirrorRef`,class:U(`${c}-base-selection-input-tag__mirror`)},[W(()=>this.pattern)],2)],2)):null,u=h?()=>(p(),D(`div`,{class:U(`${c}-base-selection-tag-wrapper`),ref:`counterWrapperRef`},[(p(),C(Oe,{size:r,ref:`counterRef`,onMouseenter:this.handleMouseEnterCounter,onMouseleave:this.handleMouseLeaveCounter,disabled:i},null,8,[`size`,`onMouseenter`,`onMouseleave`,`disabled`]))],2)):void 0,b;if(g){let e=this.selectedOptions.length-o;e>0&&(b=(t=>(p(),D(`div`,{class:U(`${c}-base-selection-tag-wrapper`),key:`__counter__`},[(p(),C(Oe,{size:r,ref:`counterRef`,onMouseenter:this.handleMouseEnterCounter,disabled:i},{default:()=>`+${e}`},1032,[`size`,`onMouseenter`,`disabled`]))],2)))(b))}let S=h?a?(p(),C(_e,{key:3,ref:`overflowRef`,updateCounter:this.updateCounter,getCounter:this.getCounter,getTail:this.getTail,style:{width:`100%`,display:`flex`,overflow:`hidden`}},{default:n,counter:u,tail:()=>s},1032,[`updateCounter`,`getCounter`,`getTail`])):(p(),C(_e,{key:4,ref:`overflowRef`,updateCounter:this.updateCounter,getCounter:this.getCounter,style:{width:`100%`,display:`flex`,overflow:`hidden`}},{default:n,counter:u},1032,[`updateCounter`,`getCounter`])):g&&b?n().concat(b):n(),T=_?()=>(p(),D(`div`,{class:U(`${c}-base-selection-popover`)},[h?(p(),D(w,{key:0},[W(()=>n())],64)):(p(),D(w,{key:1},[W(()=>this.selectedOptions.map(t))],64))],2)):void 0,E=_?{show:this.showTagsPanel,trigger:`hover`,overlap:!0,placement:`top`,width:`trigger`,onUpdateShow:this.onPopoverUpdateShow,theme:this.mergedTheme.peers.Popover,themeOverrides:this.mergedTheme.peerOverrides.Popover,...l}:null,O=!this.selected&&(!this.active||!this.pattern&&!this.isComposing)?(p(),D(`div`,{key:5,class:U(`${c}-base-selection-placeholder ${c}-base-selection-overlay`)},[x(`div`,{class:U(`${c}-base-selection-placeholder__inner`)},[W(()=>this.placeholder)],2)],2)):null,k=a?(p(),D(`div`,{key:6,ref:`patternInputWrapperRef`,class:U(`${c}-base-selection-tags`)},[W(()=>S),h?W(()=>null):(p(),D(w,{key:1},[W(()=>s)],64)),W(()=>v)],2)):(p(),D(`div`,{key:7,ref:`multipleElRef`,class:U(`${c}-base-selection-tags`),tabindex:i?void 0:0},[W(()=>S),W(()=>v)],10,ot));y=(e=>(p(),D(w,{key:8},[_?(p(),C(fe,f({key:0},E,{scrollable:!0,style:`max-height: calc(var(--v-target-height) * 6.6);`}),{trigger:()=>k,default:T},1040)):(p(),D(w,{key:1},[W(()=>k)],64)),W(()=>O)],64)))(y)}else if(a){let e=this.pattern||this.isComposing,t=this.active?!e:!this.selected,n=!this.active&&this.selected;y=(e=>(p(),D(`div`,{key:9,ref:`patternInputWrapperRef`,class:U(`${c}-base-selection-label`),title:this.patternInputFocused?void 0:ke(this.label)},[x(`input`,f(this.inputProps,{ref:`patternInputRef`,class:`${c}-base-selection-input`,value:this.active?this.pattern:``,placeholder:``,readonly:i,disabled:i,tabindex:-1,autofocus:this.autofocus,onFocus:this.handlePatternInputFocus,onBlur:this.handlePatternInputBlur,onInput:this.handlePatternInputInput,onCompositionstart:this.handleCompositionStart,onCompositionend:this.handleCompositionEnd}),null,16,ct),n?(p(),D(`div`,{class:U(`${c}-base-selection-label__render-label ${c}-base-selection-overlay`),key:`input`},[x(`div`,{class:U(`${c}-base-selection-overlay__wrapper`)},[d?(p(),D(w,{key:0},[W(()=>d({option:this.selectedOption,handleClose:()=>{}}))],64)):(p(),D(w,{key:1},[m?(p(),D(w,{key:0},[W(()=>m(this.selectedOption,!0))],64)):(p(),D(w,{key:1},[W(()=>$(this.label,this.selectedOption,!0))],64))],64))],2)],2)):W(()=>null),t?(p(),D(`div`,{class:U(`${c}-base-selection-placeholder ${c}-base-selection-overlay`),key:`placeholder`},[x(`div`,{class:U(`${c}-base-selection-overlay__wrapper`)},[W(()=>this.filterablePlaceholder)],2)],2)):W(()=>null),W(()=>v)],10,st)))(y)}else y=(e=>(p(),D(`div`,{key:10,ref:`singleElRef`,class:U(`${c}-base-selection-label`),tabindex:this.disabled?void 0:0},[this.label===void 0?(p(),D(`div`,{class:U(`${c}-base-selection-placeholder ${c}-base-selection-overlay`),key:`placeholder`},[x(`div`,{class:U(`${c}-base-selection-placeholder__inner`)},[W(()=>this.placeholder)],2)],2)):(p(),D(`div`,{class:U(`${c}-base-selection-input`),title:ke(this.label),key:`input`},[x(`div`,{class:U(`${c}-base-selection-input__content`)},[d?(p(),D(w,{key:0},[W(()=>d({option:this.selectedOption,handleClose:()=>{}}))],64)):(p(),D(w,{key:1},[m?(p(),D(w,{key:0},[W(()=>m(this.selectedOption,!0))],64)):(p(),D(w,{key:1},[W(()=>$(this.label,this.selectedOption,!0))],64))],64))],2)],10,[`title`])),W(()=>v)],10,lt)))(y);return p(),D(`div`,{ref:`selfRef`,class:U([`${c}-base-selection`,this.rtlEnabled&&`${c}-base-selection--rtl`,this.themeClass,e&&`${c}-base-selection--${e}-status`,{[`${c}-base-selection--active`]:this.active,[`${c}-base-selection--selected`]:this.selected||this.active&&this.pattern,[`${c}-base-selection--disabled`]:this.disabled,[`${c}-base-selection--multiple`]:this.multiple,[`${c}-base-selection--focus`]:this.focused}]),style:E(this.cssVars),onClick:this.onClick,onMouseenter:this.handleMouseEnter,onMouseleave:this.handleMouseLeave,onKeydown:this.onKeydown,onFocusin:this.handleFocusin,onFocusout:this.handleFocusout,onMousedown:this.handleMouseDown},[W(()=>y),s?(p(),D(`div`,{key:0,class:U(`${c}-base-selection__border`)},null,2)):W(()=>null),s?(p(),D(`div`,{key:2,class:U(`${c}-base-selection__state-border`)},null,2)):W(()=>null)],46,ut)}}),ft=H([V(`select`,`
 z-index: auto;
 outline: none;
 width: 100%;
 position: relative;
 font-weight: var(--n-font-weight);
 `),V(`select-menu`,`
 margin: 4px 0;
 box-shadow: var(--n-menu-box-shadow);
 `,[l({originalTransition:`background-color .3s var(--n-bezier), box-shadow .3s var(--n-bezier)`})])]),pt={...L.props,to:de.propTo,bordered:{type:Boolean,default:void 0},clearable:Boolean,clearCreatedOptionsOnClear:{type:Boolean,default:!0},clearFilterAfterSelect:{type:Boolean,default:!0},options:{type:Array,default:()=>[]},defaultValue:{type:[String,Number,Array],default:null},keyboard:{type:Boolean,default:!0},value:[String,Number,Array],placeholder:String,menuProps:Object,multiple:Boolean,size:String,menuSize:{type:String},filterable:Boolean,disabled:{type:Boolean,default:void 0},remote:Boolean,loading:Boolean,filter:Function,placement:{type:String,default:`bottom-start`},widthMode:{type:String,default:`trigger`},tag:Boolean,onCreate:Function,fallbackOption:{type:[Function,Boolean],default:void 0},show:{type:Boolean,default:void 0},showArrow:{type:Boolean,default:!0},maxTagCount:[Number,String],ellipsisTagPopoverProps:Object,consistentMenuWidth:{type:Boolean,default:!0},virtualScroll:{type:Boolean,default:!0},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},childrenField:{type:String,default:`children`},renderLabel:Function,renderOption:Function,renderTag:Function,"onUpdate:value":[Function,Array],inputProps:Object,nodeProps:Function,ignoreComposition:{type:Boolean,default:!0},showOnFocus:Boolean,onUpdateValue:[Function,Array],onBlur:[Function,Array],onClear:[Function,Array],onFocus:[Function,Array],onScroll:[Function,Array],onSearch:[Function,Array],onUpdateShow:[Function,Array],"onUpdate:show":[Function,Array],displayDirective:{type:String,default:`show`},resetMenuOnOptionsChange:{type:Boolean,default:!0},status:String,showCheckmark:{type:Boolean,default:!0},scrollbarProps:Object,onChange:[Function,Array],items:Array},mt=M({name:`Select`,props:pt,slots:Object,setup(n){let{mergedClsPrefixRef:r,mergedBorderedRef:i,namespaceRef:a,inlineThemeDisabled:o,mergedComponentPropsRef:s}=ie(n),c=L(`Select`,`-select`,ft,Ce,n,r),l=O(n.defaultValue),u=S(n,`value`),d=t(u,l),f=O(!1),p=O(``),h=Ee(n,[`items`,`options`]),g=O([]),_=O([]),v=A(()=>_.value.concat(g.value).concat(h.value)),y=A(()=>{let{filter:e}=n;if(e)return e;let{labelField:t,valueField:r}=n;return(e,n)=>{if(!n)return!1;let i=n[t];if(typeof i==`string`)return et(e,i);let a=n[r];return typeof a==`string`?et(e,a):typeof a==`number`&&et(e,String(a))}}),b=A(()=>{if(n.remote)return h.value;{let{value:e}=v,{value:t}=p;return!t.length||!n.filterable?e:nt(e,y.value,t,n.childrenField)}}),x=A(()=>{let{valueField:e,childrenField:t}=n,r=tt(e,t);return ve(b.value,r)}),C=A(()=>rt(v.value,n.valueField,n.childrenField)),w=O(!1),T=t(S(n,`show`),w),E=O(null),D=O(null),k=O(null),{localeRef:j}=oe(`Select`),M=A(()=>n.placeholder??j.value.placeholder),N=[],F=O(new Map),I=A(()=>{let{fallbackOption:e}=n;if(e===void 0){let{labelField:e,valueField:t}=n;return n=>({[e]:String(n),[t]:n})}return e===!1?!1:t=>Object.assign(e(t),{value:t})});function R(e){let t=n.remote,{value:r}=F,{value:i}=C,{value:a}=I,o=[];return e.forEach(e=>{if(i.has(e))o.push(i.get(e));else if(t&&r.has(e))o.push(r.get(e));else if(a){let t=a(e);t&&o.push(t)}}),o}let te=A(()=>{if(n.multiple){let{value:e}=d;return Array.isArray(e)?R(e):[]}return null}),z=A(()=>{let{value:e}=d;return!n.multiple&&!Array.isArray(e)?e===null?null:R([e])[0]||null:null}),B=K(n,{mergedSize:e=>{let{size:t}=n;if(t)return t;let{mergedSize:r}=e||{};return r?.value?r.value:s?.value?.Select?.size||`medium`}}),{mergedSizeRef:V,mergedDisabledRef:H,mergedStatusRef:U}=B;function W(e,t){let{onChange:r,"onUpdate:value":i,onUpdateValue:a}=n,{nTriggerFormChange:o,nTriggerFormInput:s}=B;r&&P(r,e,t),a&&P(a,e,t),i&&P(i,e,t),l.value=e,o(),s()}function G(e){let{onBlur:t}=n,{nTriggerFormBlur:r}=B;t&&P(t,e),r()}function ne(){let{onClear:e}=n;e&&P(e)}function J(e){let{onFocus:t,showOnFocus:r}=n,{nTriggerFormFocus:i}=B;t&&P(t,e),i(),r&&Z()}function re(e){let{onSearch:t}=n;t&&P(t,e)}function Y(e){let{onScroll:t}=n;t&&P(t,e)}function X(){let{remote:e,multiple:t}=n;if(e){let{value:e}=F;if(t){let{valueField:t}=n;te.value?.forEach(n=>{e.set(n[t],n)})}else{let t=z.value;t&&e.set(t[n.valueField],t)}}}function ae(e){let{onUpdateShow:t,"onUpdate:show":r}=n;t&&P(t,e),r&&P(r,e),w.value=e}function Z(){H.value||(ae(!0),w.value=!0,n.filterable&&Ne())}function Q(){ae(!1)}function se(){p.value=``,_.value=N}let ce=O(!1);function le(){n.filterable&&(ce.value=!0)}function ue(){n.filterable&&(ce.value=!1,T.value||se())}function fe(){H.value||(T.value?n.filterable?Ne():Q():Z())}function pe(e){k.value?.selfRef?.contains(e.relatedTarget)||(f.value=!1,G(e),Q())}function me(e){J(e),f.value=!0}function ge(){f.value=!0}function _e(e){E.value?.$el.contains(e.relatedTarget)||(f.value=!1,G(e),Q())}function ye(){E.value?.focus(),Q()}function be(t){T.value&&(E.value?.$el.contains(e(t))||Q())}function Se(e){if(!Array.isArray(e))return[];if(I.value)return Array.from(e);{let{remote:t}=n,{value:r}=C;if(t){let{value:t}=F;return e.filter(e=>r.has(e)||t.has(e))}return e.filter(e=>r.has(e))}}function $(e){we(e.rawNode)}function we(e){if(H.value)return;let{tag:t,remote:r,clearFilterAfterSelect:i,valueField:a}=n;if(t&&!r){let{value:e}=_,t=e[0]||null;if(t){let e=g.value;e.length?e.push(t):g.value=[t],_.value=N}}if(r&&F.value.set(e[a],e),n.multiple){let n=Se(d.value),o=n.findIndex(t=>t===e[a]);if(~o){if(n.splice(o,1),t&&!r){let t=Te(e[a]);~t&&(g.value.splice(t,1),i&&(p.value=``))}}else n.push(e[a]),i&&(p.value=``);W(n,R(n))}else{if(t&&!r){let t=Te(e[a]);~t?g.value=[g.value[t]]:g.value=N}Me(),Q(),W(e[a],e)}}function Te(e){return g.value.findIndex(t=>t[n.valueField]===e)}function De(e){T.value||Z();let{value:t}=e.target;p.value=t;let{tag:r,remote:i}=n;if(re(t),r&&!i){if(!t){_.value=N;return}let{onCreate:e}=n,r=e?e(t):{[n.labelField]:t,[n.valueField]:t},{valueField:i,labelField:a}=n;h.value.some(e=>e[i]===r[i]||e[a]===r[a])||g.value.some(e=>e[i]===r[i]||e[a]===r[a])?_.value=N:_.value=[r]}}function Oe(e){e.stopPropagation();let{multiple:t,tag:r,remote:i,clearCreatedOptionsOnClear:a}=n;!t&&n.filterable&&Q(),r&&!i&&a&&(g.value=N),ne(),t?W([],[]):W(null,null)}function ke(e){!he(e,`action`)&&!he(e,`empty`)&&!he(e,`header`)&&e.preventDefault()}function Ae(e){Y(e)}function je(e){if(!n.keyboard){e.preventDefault();return}switch(e.key){case` `:if(n.filterable)break;e.preventDefault();case`Enter`:if(!E.value?.isComposing){if(T.value){let e=k.value?.getPendingTmNode();e?$(e):n.filterable||(Q(),Me())}else if(Z(),n.tag&&ce.value){let e=_.value[0];if(e){let t=e[n.valueField],{value:r}=d;n.multiple&&Array.isArray(r)&&r.includes(t)||we(e)}}}e.preventDefault();break;case`ArrowUp`:if(e.preventDefault(),n.loading)return;T.value&&k.value?.prev();break;case`ArrowDown`:if(e.preventDefault(),n.loading)return;T.value?k.value?.next():Z();break;case`Escape`:T.value&&(xe(e),Q()),E.value?.focus()}}function Me(){E.value?.focus()}function Ne(){E.value?.focusInput()}function Pe(){T.value&&D.value?.syncPosition()}X(),m(S(n,`options`),X);let Fe={focus:()=>{E.value?.focus()},focusInput:()=>{E.value?.focusInput()},blur:()=>{E.value?.blur()},blurInput:()=>{E.value?.blurInput()}},Ie=A(()=>{let{self:{menuBoxShadow:e}}=c.value;return{"--n-menu-box-shadow":e}}),Le=o?ee(`select`,void 0,Ie,n):void 0;return{...Fe,mergedStatus:U,mergedClsPrefix:r,mergedBordered:i,namespace:a,treeMate:x,isMounted:q(),triggerRef:E,menuRef:k,pattern:p,uncontrolledShow:w,mergedShow:T,adjustedTo:de(n),uncontrolledValue:l,mergedValue:d,followerRef:D,localizedPlaceholder:M,selectedOption:z,selectedOptions:te,mergedSize:V,mergedDisabled:H,focused:f,activeWithoutMenuOpen:ce,inlineThemeDisabled:o,onTriggerInputFocus:le,onTriggerInputBlur:ue,handleTriggerOrMenuResize:Pe,handleMenuFocus:ge,handleMenuBlur:_e,handleMenuTabOut:ye,handleTriggerClick:fe,handleToggle:$,handleDeleteOption:we,handlePatternInput:De,handleClear:Oe,handleTriggerBlur:pe,handleTriggerFocus:me,handleKeydown:je,handleMenuAfterLeave:se,handleMenuClickOutside:be,handleMenuScroll:Ae,handleMenuKeydown:je,handleMenuMousedown:ke,mergedTheme:c,cssVars:o?void 0:Ie,themeClass:Le?.themeClass,onRender:Le?.onRender}},render(){return p(),D(`div`,{class:U(`${this.mergedClsPrefix}-select`)},[b(ue,null,{_:1,default:Y(()=>[(p(),C(Q,null,{_:1,default:Y(()=>(p(),C(dt,{ref:`triggerRef`,inlineThemeDisabled:this.inlineThemeDisabled,status:this.mergedStatus,inputProps:this.inputProps,clsPrefix:this.mergedClsPrefix,showArrow:this.showArrow,maxTagCount:this.maxTagCount,ellipsisTagPopoverProps:this.ellipsisTagPopoverProps,bordered:this.mergedBordered,active:this.activeWithoutMenuOpen||this.mergedShow,pattern:this.pattern,placeholder:this.localizedPlaceholder,selectedOption:this.selectedOption,selectedOptions:this.selectedOptions,multiple:this.multiple,renderTag:this.renderTag,renderLabel:this.renderLabel,filterable:this.filterable,clearable:this.clearable,disabled:this.mergedDisabled,size:this.mergedSize,theme:this.mergedTheme.peers.InternalSelection,labelField:this.labelField,valueField:this.valueField,themeOverrides:this.mergedTheme.peerOverrides.InternalSelection,loading:this.loading,focused:this.focused,onClick:this.handleTriggerClick,onDeleteOption:this.handleDeleteOption,onPatternInput:this.handlePatternInput,onClear:this.handleClear,onBlur:this.handleTriggerBlur,onFocus:this.handleTriggerFocus,onKeydown:this.handleKeydown,onPatternBlur:this.onTriggerInputBlur,onPatternFocus:this.onTriggerInputFocus,onResize:this.handleTriggerOrMenuResize,ignoreComposition:this.ignoreComposition},{_:1,arrow:Y(()=>[this.$slots.arrow?.()])},8,`inlineThemeDisabled.status.inputProps.clsPrefix.showArrow.maxTagCount.ellipsisTagPopoverProps.bordered.active.pattern.placeholder.selectedOption.selectedOptions.multiple.renderTag.renderLabel.filterable.clearable.disabled.size.theme.labelField.valueField.themeOverrides.loading.focused.onClick.onDeleteOption.onPatternInput.onClear.onBlur.onFocus.onKeydown.onPatternBlur.onPatternFocus.onResize.ignoreComposition`.split(`.`))))})),(p(),C(ce,{ref:`followerRef`,show:this.mergedShow,to:this.adjustedTo,teleportDisabled:this.adjustedTo===de.tdkey,containerClass:this.namespace,width:this.consistentMenuWidth?`target`:void 0,minWidth:`target`,placement:this.placement},{_:1,default:Y(()=>(p(),C(re,{name:`fade-in-scale-up-transition`,appear:this.isMounted,onAfterLeave:this.handleMenuAfterLeave},{_:1,default:Y(()=>this.mergedShow||this.displayDirective===`show`?(this.onRender?.(),y((p(),C(Ze,f(this.menuProps,{ref:`menuRef`,onResize:this.handleTriggerOrMenuResize,inlineThemeDisabled:this.inlineThemeDisabled,virtualScroll:this.consistentMenuWidth&&this.virtualScroll,class:[`${this.mergedClsPrefix}-select-menu`,this.themeClass,this.menuProps?.class],clsPrefix:this.mergedClsPrefix,focusable:!0,labelField:this.labelField,valueField:this.valueField,autoPending:!0,nodeProps:this.nodeProps,theme:this.mergedTheme.peers.InternalSelectMenu,themeOverrides:this.mergedTheme.peerOverrides.InternalSelectMenu,treeMate:this.treeMate,multiple:this.multiple,size:this.menuSize,renderOption:this.renderOption,renderLabel:this.renderLabel,value:this.mergedValue,style:[this.menuProps?.style,this.cssVars],onToggle:this.handleToggle,onScroll:this.handleMenuScroll,onFocus:this.handleMenuFocus,onBlur:this.handleMenuBlur,onKeydown:this.handleMenuKeydown,onTabOut:this.handleMenuTabOut,onMousedown:this.handleMenuMousedown,show:this.mergedShow,showCheckmark:this.showCheckmark,resetMenuOnOptionsChange:this.resetMenuOnOptionsChange,scrollbarProps:this.scrollbarProps}),{_:1,empty:Y(()=>[this.$slots.empty?.()]),header:Y(()=>[this.$slots.header?.()]),action:Y(()=>[this.$slots.action?.()])},16,`onResize.inlineThemeDisabled.virtualScroll.class.clsPrefix.labelField.valueField.nodeProps.theme.themeOverrides.treeMate.multiple.size.renderOption.renderLabel.value.style.onToggle.onScroll.onFocus.onBlur.onKeydown.onTabOut.onMousedown.show.showCheckmark.resetMenuOnOptionsChange.scrollbarProps`.split(`.`))),this.displayDirective===`show`?[[J,this.mergedShow],[be,this.handleMenuClickOutside,void 0,{capture:!0}]]:[[be,this.handleMenuClickOutside,void 0,{capture:!0}]])):null)},8,[`appear`,`onAfterLeave`])))},8,[`show`,`to`,`teleportDisabled`,`containerClass`,`width`,`placement`]))])})],2)}});export{Ve as i,tt as n,Ze as r,mt as t};