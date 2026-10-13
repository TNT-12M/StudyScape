import{Yt as e,dt as t}from"./Button-BtFx7EYM.js";var n={iconMargin:`11px 8px 0 12px`,iconMarginRtl:`11px 12px 0 8px`,iconSize:`24px`,closeIconSize:`16px`,closeSize:`20px`,closeMargin:`13px 14px 0 0`,closeMarginRtl:`13px 0 0 14px`,padding:`13px`},{cubicBezierEaseInOut:r,cubicBezierEaseOut:i,cubicBezierEaseIn:a}=t;function o({overflow:t=`hidden`,duration:n=`.3s`,originalTransition:o=``,leavingDelay:s=`0s`,foldPadding:c=!1,enterToProps:l=void 0,leaveToProps:u=void 0,reverse:d=!1}={}){let f=d?`leave`:`enter`,p=d?`enter`:`leave`;return[e(`&.fade-in-height-expand-transition-${p}-from,
 &.fade-in-height-expand-transition-${f}-to`,{...l,opacity:1}),e(`&.fade-in-height-expand-transition-${p}-to,
 &.fade-in-height-expand-transition-${f}-from`,{...u,opacity:0,marginTop:`0 !important`,marginBottom:`0 !important`,paddingTop:c?`0 !important`:void 0,paddingBottom:c?`0 !important`:void 0}),e(`&.fade-in-height-expand-transition-${p}-active`,`
 overflow: ${t};
 transition:
 max-height ${n} ${r} ${s},
 opacity ${n} ${i} ${s},
 margin-top ${n} ${r} ${s},
 margin-bottom ${n} ${r} ${s},
 padding-top ${n} ${r} ${s},
 padding-bottom ${n} ${r} ${s}
 ${o?`,${o}`:``}
 `),e(`&.fade-in-height-expand-transition-${f}-active`,`
 overflow: ${t};
 transition:
 max-height ${n} ${r},
 opacity ${n} ${a},
 margin-top ${n} ${r},
 margin-bottom ${n} ${r},
 padding-top ${n} ${r},
 padding-bottom ${n} ${r}
 ${o?`,${o}`:``}
 `)]}export{n,o as t};