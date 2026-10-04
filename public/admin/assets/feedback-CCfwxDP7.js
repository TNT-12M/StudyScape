import{J as e,g as t,n,z as r}from"./php-modules-BAZCMuH3.js";import{At as i,F as a,J as o,M as s,S as c,_ as l,d as u,f as d,g as f,it as p,kt as m,m as h,mt as g,ot as _,p as v,tt as y,u as b,y as x}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{$t as S,F as C,I as w,Qt as T,Xt as E,Yt as D,at as O,ct as k,en as A,nn as j,pt as M,rn as N,t as P,tn as F,un as ee}from"./Button-CjW_3_5b.js";import{t as I}from"./DataTable-BcbhBH0d.js";import{n as L,t as R}from"./AppPage-BeGCQgqj.js";import{r as z}from"./cssr-DSJD8hf8.js";import{t as B}from"./Modal-BfujoQmj.js";import{t as V}from"./Select-L-VPO81e.js";import{t as H}from"./Tag-CY_O7VS9.js";import{i as U,t as W}from"./FormItem-BoSqoO_u.js";import{t as G}from"./Input-CM3fr4TJ.js";import{t as K}from"./get-slot-6kXJmSMP.js";import{t as q}from"./light-ocLNACT7.js";import{t as J}from"./Space-DvrIaxNs.js";import{t as Y}from"./_plugin-vue_export-helper-BDNMzG2s.js";function X(e,t=`default`,n=[]){let{children:r}=e;if(typeof r==`object`&&r&&!Array.isArray(r)){let e=r[t];if(typeof e==`function`)return e()}return n}var Z=D([E(`descriptions`,{fontSize:`var(--n-font-size)`},[E(`descriptions-separator`,`
 display: inline-block;
 margin: 0 8px 0 2px;
 `),E(`descriptions-table-wrapper`,[E(`descriptions-table`,[E(`descriptions-table-row`,[E(`descriptions-table-header`,{padding:`var(--n-th-padding)`}),E(`descriptions-table-content`,{padding:`var(--n-td-padding)`})])])]),A(`bordered`,[E(`descriptions-table-wrapper`,[E(`descriptions-table`,[E(`descriptions-table-row`,[D(`&:last-child`,[E(`descriptions-table-content`,{paddingBottom:0})])])])])]),S(`left-label-placement`,[E(`descriptions-table-content`,[D(`> *`,{verticalAlign:`top`})])]),S(`left-label-align`,[D(`th`,{textAlign:`left`})]),S(`center-label-align`,[D(`th`,{textAlign:`center`})]),S(`right-label-align`,[D(`th`,{textAlign:`right`})]),S(`bordered`,[E(`descriptions-table-wrapper`,`
 border-radius: var(--n-border-radius);
 overflow: hidden;
 background: var(--n-merged-td-color);
 border: 1px solid var(--n-merged-border-color);
 `,[E(`descriptions-table`,[E(`descriptions-table-row`,[D(`&:not(:last-child)`,[E(`descriptions-table-content`,{borderBottom:`1px solid var(--n-merged-border-color)`}),E(`descriptions-table-header`,{borderBottom:`1px solid var(--n-merged-border-color)`})]),E(`descriptions-table-header`,`
 font-weight: 400;
 background-clip: padding-box;
 background-color: var(--n-merged-th-color);
 `,[D(`&:not(:last-child)`,{borderRight:`1px solid var(--n-merged-border-color)`})]),E(`descriptions-table-content`,[D(`&:not(:last-child)`,{borderRight:`1px solid var(--n-merged-border-color)`})])])])])]),E(`descriptions-header`,`
 font-weight: var(--n-th-font-weight);
 font-size: 18px;
 transition: color .3s var(--n-bezier);
 line-height: var(--n-line-height);
 margin-bottom: 16px;
 color: var(--n-title-text-color);
 `),E(`descriptions-table-wrapper`,`
 transition:
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[E(`descriptions-table`,`
 width: 100%;
 border-collapse: separate;
 border-spacing: 0;
 box-sizing: border-box;
 `,[E(`descriptions-table-row`,`
 box-sizing: border-box;
 transition: border-color .3s var(--n-bezier);
 `,[E(`descriptions-table-header`,`
 font-weight: var(--n-th-font-weight);
 line-height: var(--n-line-height);
 display: table-cell;
 box-sizing: border-box;
 color: var(--n-th-text-color);
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `),E(`descriptions-table-content`,`
 vertical-align: top;
 line-height: var(--n-line-height);
 display: table-cell;
 box-sizing: border-box;
 color: var(--n-td-text-color);
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[T(`content`,`
 transition: color .3s var(--n-bezier);
 display: inline-block;
 color: var(--n-td-text-color);
 `)]),T(`label`,`
 font-weight: var(--n-th-font-weight);
 transition: color .3s var(--n-bezier);
 display: inline-block;
 margin-right: 14px;
 color: var(--n-th-text-color);
 `)])])])]),E(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color);
 --n-merged-td-color: var(--n-td-color);
 --n-merged-border-color: var(--n-border-color);
 `),j(E(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color-modal);
 --n-merged-td-color: var(--n-td-color-modal);
 --n-merged-border-color: var(--n-border-color-modal);
 `)),N(E(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color-popover);
 --n-merged-td-color: var(--n-td-color-popover);
 --n-merged-border-color: var(--n-border-color-popover);
 `))]),Q=`DESCRIPTION_ITEM_FLAG`;function te(e){return typeof e==`object`&&e&&!Array.isArray(e)?e.type&&e.type.DESCRIPTION_ITEM_FLAG:!1}var ne=[`colspan`],re=[`colspan`],ie=[`colspan`],ae=[`colspan`],oe={...C.props,title:String,column:{type:Number,default:3},columns:Number,labelPlacement:{type:String,default:`top`},labelAlign:{type:String,default:`left`},separator:{type:String,default:`:`},size:String,bordered:Boolean,labelClass:String,labelStyle:[Object,String],contentClass:String,contentStyle:[Object,String]},$=x({name:`Descriptions`,props:oe,slots:Object,setup(e){let{mergedClsPrefixRef:t,inlineThemeDisabled:n,mergedComponentPropsRef:r}=M(e),i=b(()=>e.size||r?.value?.Descriptions?.size||`medium`),a=C(`Descriptions`,`-descriptions`,Z,q,e,t),o=b(()=>{let{bordered:t}=e,n=i.value,{common:{cubicBezierEaseInOut:r},self:{titleTextColor:o,thColor:s,thColorModal:c,thColorPopover:l,thTextColor:u,thFontWeight:d,tdTextColor:f,tdColor:p,tdColorModal:m,tdColorPopover:h,borderColor:g,borderColorModal:_,borderColorPopover:v,borderRadius:y,lineHeight:b,[F(`fontSize`,n)]:x,[F(t?`thPaddingBordered`:`thPadding`,n)]:S,[F(t?`tdPaddingBordered`:`tdPadding`,n)]:C}}=a.value;return{"--n-title-text-color":o,"--n-th-padding":S,"--n-td-padding":C,"--n-font-size":x,"--n-bezier":r,"--n-th-font-weight":d,"--n-line-height":b,"--n-th-text-color":u,"--n-td-text-color":f,"--n-th-color":s,"--n-th-color-modal":c,"--n-th-color-popover":l,"--n-td-color":p,"--n-td-color-modal":m,"--n-td-color-popover":h,"--n-border-radius":y,"--n-border-color":g,"--n-border-color-modal":_,"--n-border-color-popover":v}}),s=n?w(`descriptions`,b(()=>{let t=``,{bordered:n}=e;return n&&(t+=`a`),t+=i.value[0],t}),o,e):void 0;return{mergedClsPrefix:t,cssVars:n?void 0:o,themeClass:s?.themeClass,onRender:s?.onRender,compitableColumn:z(e,[`columns`,`column`]),inlineThemeDisabled:n,mergedSize:i}},render(){let t=this.$slots.default,n=t?r(t()):[];n.length;let{contentClass:i,labelClass:o,compitableColumn:s,labelPlacement:c,labelAlign:l,mergedSize:d,bordered:f,title:p,cssVars:g,mergedClsPrefix:_,separator:v,onRender:y}=this;y?.();let b=n.filter(e=>te(e)),x=b.reduce((e,t,n)=>{let r=t.props||{},l=b.length-1===n,d=[`label`in r?r.label:X(t,`label`)],p=[X(t)],g=r.span||1,y=e.span;e.span+=g;let x=r.labelStyle||r[`label-style`]||this.labelStyle,S=r.contentStyle||r[`content-style`]||this.contentStyle;if(c===`left`)f?e.row.push((a(),h(`th`,{key:1,class:O([`${_}-descriptions-table-header`,o]),colspan:1,style:m(x)},[k(()=>d)],6)),(a(),h(`td`,{key:2,class:O([`${_}-descriptions-table-content`,i]),colspan:l?(s-y)*2+1:g*2-1,style:m(S)},[k(()=>p)],14,ne))):e.row.push((a(),h(`td`,{key:3,class:O(`${_}-descriptions-table-content`),colspan:l?(s-y)*2:g*2},[u(`span`,{class:O([`${_}-descriptions-table-content__label`,o]),style:m(x)},[k(()=>[...d,v&&(a(),h(`span`,{key:4,class:O(`${_}-descriptions-separator`)},[k(()=>v)],2))])],6),u(`span`,{class:O([`${_}-descriptions-table-content__content`,i]),style:m(S)},[k(()=>p)],6)],10,re)));else{let t=l?(s-y)*2:g*2;e.row.push((a(),h(`th`,{key:5,class:O([`${_}-descriptions-table-header`,o]),colspan:t,style:m(x)},[k(()=>d)],14,ie))),e.secondRow.push((a(),h(`td`,{key:6,class:O([`${_}-descriptions-table-content`,i]),colspan:t,style:m(S)},[k(()=>p)],14,ae)))}return(e.span>=s||l)&&(e.span=0,e.row.length&&(e.rows.push(e.row),e.row=[]),c!==`left`&&e.secondRow.length&&(e.rows.push(e.secondRow),e.secondRow=[])),e},{span:0,row:[],secondRow:[],rows:[]}).rows.map(e=>(a(),h(`tr`,{class:O(`${_}-descriptions-table-row`)},[k(()=>e)],2)));return a(),h(`div`,{style:m(g),class:O([`${_}-descriptions`,this.themeClass,`${_}-descriptions--${c}-label-placement`,`${_}-descriptions--${l}-label-align`,`${_}-descriptions--${d}-size`,f&&`${_}-descriptions--bordered`])},[p||this.$slots.header?(a(),h(`div`,{key:0,class:O(`${_}-descriptions-header`)},[k(()=>p||K(this,`header`))],2)):k(()=>null),u(`div`,{class:O(`${_}-descriptions-table-wrapper`)},[u(`table`,{class:O(`${_}-descriptions-table`)},[u(`tbody`,null,[k(()=>c===`top`&&(a(),h(`tr`,{class:O(`${_}-descriptions-table-row`),style:{visibility:`collapse`}},[k(()=>e(s*2,(a(),h(`td`))))],2))),k(()=>x)])],2)],2)],6)}}),se={label:String,span:{type:Number,default:1},labelClass:String,labelStyle:[Object,String],contentClass:String,contentStyle:[Object,String]},ce=x({name:`DescriptionsItem`,[Q]:!0,props:se,slots:Object,render(){return null}}),le={class:`content`},ue=Y({__name:`index`,setup(e){let r=window.$message,m=_([]),h=_(!1),b=_(!1),x=_(!1),S=_(null),C=p({status:null,keyword:``}),w=p({id:null,status:`open`,admin_note:``}),T=p({page:1,pageSize:20,itemCount:0,showSizePicker:!0,pageSizes:[10,20,50],onChange:e=>{T.page=e,j()},onUpdatePageSize:e=>{T.pageSize=e,T.page=1,j()}}),E=[{label:`待处理`,value:`open`},{label:`已关闭`,value:`closed`}],D=[{title:`反馈内容`,key:`content`,ellipsis:{tooltip:!0},render:e=>e.content||`-`},{title:`用户`,key:`username`,render:e=>e.username||e.email||e.user_id||`-`},{title:`状态`,key:`status`,width:100,render:e=>c(H,{type:k(e.status)},{default:()=>O(e.status)})},{title:`提交时间`,key:`created_at`,width:180},{title:`操作`,key:`actions`,width:100,render:e=>c(P,{size:`small`,onClick:()=>A(e)},{default:()=>`处理`})}];function O(e){return E.find(t=>t.value===e)?.label||e||`待处理`}function k(e){return e===`resolved`?`success`:e===`closed`?`default`:`info`}function A(e){S.value=e,Object.assign(w,{id:e.id,status:e.status||`open`,admin_note:e.admin_note||``}),x.value=!0}async function j(){h.value=!0;try{let e=(await n.list({...C,page:T.page,page_size:T.pageSize})).data||{};m.value=e.items||e.feedback||e.list||[],T.itemCount=Number(e.pagination?.total??e.total??m.value.length)||0}catch(e){r.error(e.message||`反馈加载失败`)}finally{h.value=!1}}async function M(){if(w.id){b.value=!0;try{await n.update({...w}),r.success(`反馈已更新`),x.value=!1,await j()}catch(e){r.error(e.message||`反馈更新失败`)}finally{b.value=!1}}}async function N(e){if(S.value?.id){b.value=!0;try{await n.update({id:S.value.id,feedback_action:e,admin_note:w.admin_note}),r.success(e===`adopt`?`已采纳并回复`:e===`ignore`?`已忽略`:`已重新打开`),x.value=!1,await j()}catch(e){r.error(e.message||`反馈操作失败`)}finally{b.value=!1}}}return s(j),(e,n)=>{let r=V,s=W,c=G,p=U,_=t,O=I,k=L,A=ce,F=$,z=B,H=R;return a(),d(H,null,{default:o(()=>[l(g(J),{vertical:``,size:`large`},{default:o(()=>[l(_,{title:`反馈筛选`,segmented:``},{default:o(()=>[l(p,{inline:``,"label-placement":`left`},{default:o(()=>[l(s,{label:`处理状态`},{default:o(()=>[l(r,{value:g(C).status,"onUpdate:value":[n[0]||=e=>g(C).status=e,j],clearable:``,options:E,style:{width:`150px`}},null,8,[`value`])]),_:1}),l(s,{label:`关键词`},{default:o(()=>[l(c,{value:g(C).keyword,"onUpdate:value":n[1]||=e=>g(C).keyword=e,clearable:``,placeholder:`内容、联系方式或用户`,onKeyup:ee(j,[`enter`])},null,8,[`value`])]),_:1}),l(s,null,{default:o(()=>[l(g(P),{type:`primary`,loading:g(h),onClick:j},{default:o(()=>[...n[9]||=[f(`查询`,-1)]]),_:1},8,[`loading`])]),_:1})]),_:1})]),_:1}),l(_,{title:`用户反馈`,segmented:``},{default:o(()=>[l(O,{columns:D,data:g(m),loading:g(h),pagination:g(T)},null,8,[`data`,`loading`,`pagination`]),!g(h)&&!g(m).length?(a(),d(k,{key:0,description:`暂无反馈`})):v(``,!0)]),_:1})]),_:1}),l(z,{show:g(x),"onUpdate:show":n[8]||=e=>y(x)?x.value=e:null,preset:`card`,title:`反馈详情`,style:{width:`min(680px, 94vw)`}},{footer:o(()=>[l(g(J),{justify:`end`},{default:o(()=>[l(g(P),{onClick:n[4]||=e=>x.value=!1},{default:o(()=>[...n[10]||=[f(`取消`,-1)]]),_:1}),g(S)?.status===`resolved`?v(``,!0):(a(),d(g(P),{key:0,type:`success`,loading:g(b),onClick:n[5]||=e=>N(`adopt`)},{default:o(()=>[...n[11]||=[f(`采纳并回复`,-1)]]),_:1},8,[`loading`])),g(S)?.status===`closed`?v(``,!0):(a(),d(g(P),{key:1,type:`warning`,loading:g(b),onClick:n[6]||=e=>N(`ignore`)},{default:o(()=>[...n[12]||=[f(`忽略`,-1)]]),_:1},8,[`loading`])),g(S)?.status===`closed`||g(S)?.status===`resolved`?(a(),d(g(P),{key:2,loading:g(b),onClick:n[7]||=e=>N(`reopen`)},{default:o(()=>[...n[13]||=[f(`重新处理`,-1)]]),_:1},8,[`loading`])):v(``,!0),l(g(P),{type:`primary`,loading:g(b),onClick:M},{default:o(()=>[...n[14]||=[f(`保存`,-1)]]),_:1},8,[`loading`])]),_:1})]),default:o(()=>[g(S)?(a(),d(F,{key:0,bordered:``,column:1},{default:o(()=>[l(A,{label:`用户`},{default:o(()=>[f(i(g(S).username||g(S).email||g(S).user_id||`-`),1)]),_:1}),l(A,{label:`联系方式`},{default:o(()=>[f(i(g(S).contact||`未提供`),1)]),_:1}),l(A,{label:`提交时间`},{default:o(()=>[f(i(g(S).created_at||g(S).createdAt||`-`),1)]),_:1}),l(A,{label:`反馈内容`},{default:o(()=>[u(`div`,le,i(g(S).content||g(S).message||`-`),1)]),_:1}),l(A,{label:`已有回复`},{default:o(()=>[f(i(g(S).reply_content||`暂无`),1)]),_:1}),l(A,{label:`处理备注`},{default:o(()=>[l(c,{value:g(w).admin_note,"onUpdate:value":n[2]||=e=>g(w).admin_note=e,type:`textarea`,rows:4,placeholder:`内部备注，可选`},null,8,[`value`])]),_:1}),l(A,{label:`处理状态`},{default:o(()=>[l(r,{value:g(w).status,"onUpdate:value":n[3]||=e=>g(w).status=e,options:E},null,8,[`value`])]),_:1})]),_:1})):v(``,!0)]),_:1},8,[`show`])]),_:1})}}},[[`__scopeId`,`data-v-074608ea`]]);export{ue as default};