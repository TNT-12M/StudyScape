import{F as e,d as t,i as n,kt as r,m as i,u as a,y as o}from"./runtime-core.esm-bundler-xeKD6iRk.js";import{C as s,F as c,I as l,Qt as u,Xt as d,Yt as f,at as p,ct as m,pt as h}from"./Button-CjW_3_5b.js";import{n as g}from"./light-DiyMVm8Z.js";var _=d(`thing`,`
 display: flex;
 transition: color .3s var(--n-bezier);
 font-size: var(--n-font-size);
 color: var(--n-text-color);
`,[d(`thing-avatar`,`
 margin-right: 12px;
 margin-top: 2px;
 `),d(`thing-avatar-header-wrapper`,`
 display: flex;
 flex-wrap: nowrap;
 `,[d(`thing-header-wrapper`,`
 flex: 1;
 `)]),d(`thing-main`,`
 flex-grow: 1;
 `,[d(`thing-header`,`
 display: flex;
 margin-bottom: 4px;
 justify-content: space-between;
 align-items: center;
 `,[u(`title`,`
 font-size: 16px;
 font-weight: var(--n-title-font-weight);
 transition: color .3s var(--n-bezier);
 color: var(--n-title-text-color);
 `)]),u(`description`,[f(`&:not(:last-child)`,`
 margin-bottom: 4px;
 `)]),u(`content`,[f(`&:not(:first-child)`,`
 margin-top: 12px;
 `)]),u(`footer`,[f(`&:not(:first-child)`,`
 margin-top: 12px;
 `)]),u(`action`,[f(`&:not(:first-child)`,`
 margin-top: 12px;
 `)])])]),v={...c.props,title:String,titleExtra:String,description:String,descriptionClass:String,descriptionStyle:[String,Object],content:String,contentClass:String,contentStyle:[String,Object],contentIndented:Boolean},y=o({name:`Thing`,props:v,slots:Object,setup(o,{slots:u}){let{mergedClsPrefixRef:d,inlineThemeDisabled:f,mergedRtlRef:v}=h(o),y=c(`Thing`,`-thing`,_,g,o,d),b=s(`Thing`,v,d),x=a(()=>{let{self:{titleTextColor:e,textColor:t,titleFontWeight:n,fontSize:r},common:{cubicBezierEaseInOut:i}}=y.value;return{"--n-bezier":i,"--n-font-size":r,"--n-text-color":t,"--n-title-font-weight":n,"--n-title-text-color":e}}),S=f?l(`thing`,void 0,x,o):void 0;return()=>{let{value:a}=d,s=b?b.value:!1;return S?.onRender?.(),e(),i(`div`,{class:p([`${a}-thing`,S?.themeClass,s&&`${a}-thing--rtl`]),style:r(f?void 0:x.value)},[u.avatar&&o.contentIndented?(e(),i(`div`,{key:0,class:p(`${a}-thing-avatar`)},[m(()=>u.avatar())],2)):m(()=>null),t(`div`,{class:p(`${a}-thing-main`)},[!o.contentIndented&&(u.header||o.title||u[`header-extra`]||o.titleExtra||u.avatar)?(e(),i(`div`,{key:0,class:p(`${a}-thing-avatar-header-wrapper`)},[u.avatar?(e(),i(`div`,{key:0,class:p(`${a}-thing-avatar`)},[m(()=>u.avatar())],2)):m(()=>null),u.header||o.title||u[`header-extra`]||o.titleExtra?(e(),i(`div`,{key:2,class:p(`${a}-thing-header-wrapper`)},[t(`div`,{class:p(`${a}-thing-header`)},[u.header||o.title?(e(),i(`div`,{key:0,class:p(`${a}-thing-header__title`)},[u.header?(e(),i(n,{key:0},[m(()=>u.header())],64)):(e(),i(n,{key:1},[m(()=>o.title)],64))],2)):m(()=>null),u[`header-extra`]||o.titleExtra?(e(),i(`div`,{key:2,class:p(`${a}-thing-header__extra`)},[u[`header-extra`]?(e(),i(n,{key:0},[m(()=>u[`header-extra`]())],64)):(e(),i(n,{key:1},[m(()=>o.titleExtra)],64))],2)):m(()=>null)],2),u.description||o.description?(e(),i(`div`,{key:0,class:p([`${a}-thing-main__description`,o.descriptionClass]),style:r(o.descriptionStyle)},[u.description?(e(),i(n,{key:0},[m(()=>u.description())],64)):(e(),i(n,{key:1},[m(()=>o.description)],64))],6)):m(()=>null)],2)):m(()=>null)],2)):(e(),i(n,{key:1},[u.header||o.title||u[`header-extra`]||o.titleExtra?(e(),i(`div`,{key:0,class:p(`${a}-thing-header`)},[u.header||o.title?(e(),i(`div`,{key:0,class:p(`${a}-thing-header__title`)},[u.header?(e(),i(n,{key:0},[m(()=>u.header())],64)):(e(),i(n,{key:1},[m(()=>o.title)],64))],2)):m(()=>null),u[`header-extra`]||o.titleExtra?(e(),i(`div`,{key:2,class:p(`${a}-thing-header__extra`)},[u[`header-extra`]?(e(),i(n,{key:0},[m(()=>u[`header-extra`]())],64)):(e(),i(n,{key:1},[m(()=>o.titleExtra)],64))],2)):m(()=>null)],2)):m(()=>null),u.description||o.description?(e(),i(`div`,{key:2,class:p([`${a}-thing-main__description`,o.descriptionClass]),style:r(o.descriptionStyle)},[u.description?(e(),i(n,{key:0},[m(()=>u.description())],64)):(e(),i(n,{key:1},[m(()=>o.description)],64))],6)):m(()=>null)],64)),u.default||o.content?(e(),i(`div`,{key:2,class:p([`${a}-thing-main__content`,o.contentClass]),style:r(o.contentStyle)},[u.default?(e(),i(n,{key:0},[m(()=>u.default())],64)):(e(),i(n,{key:1},[m(()=>o.content)],64))],6)):m(()=>null),u.footer?(e(),i(`div`,{key:4,class:p(`${a}-thing-main__footer`)},[m(()=>u.footer())],2)):m(()=>null),u.action?(e(),i(`div`,{key:6,class:p(`${a}-thing-main__action`)},[m(()=>u.action())],2)):m(()=>null)],2)],6)}}});export{y as t};