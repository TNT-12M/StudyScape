import { degrees, PDFDocument } from 'pdf-lib'
import QRCode from 'qrcode'

// 资料下载水印：全部在用户浏览器内完成，服务器不参与渲染。
// 参考「学境资料标注工具」：封面 + 平铺水印 + 页脚 + 末页声明 + 封底。
const SITE_DOMAIN = 'x9b1.cc'
const SITE_URL = 'https://x9b1.cc'
const BRAND = '#316C72'
const STAMP_TAG = 'xj-stamped-v3'
const A4 = [595.28, 841.89]
const FONT = '"Microsoft YaHei", "PingFang SC", "Noto Sans SC", sans-serif'

export function isPdfFile(filename) {
  return /\.pdf$/i.test(String(filename || ''))
}

function createCanvas(width, height) {
  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height
  return canvas
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = reject
    img.src = src
  })
}

async function qrImage() {
  const dataUrl = await QRCode.toDataURL(SITE_URL, {
    margin: 1,
    width: 480,
    color: { dark: '#0f2027', light: '#ffffff' },
  })
  return loadImage(dataUrl)
}

function cleanTitle(filename) {
  const name = String(filename || '学境资料').replace(/\.[^.]+$/, '')
  return name.length > 42 ? `${name.slice(0, 42)}…` : name
}

/* ---------- 平铺水印 ---------- */
function watermarkTile() {
  const fontSize = 26
  const scale = 2
  const text = '学境 · StudyScape'
  const probe = createCanvas(10, 10).getContext('2d')
  probe.font = `600 ${fontSize * scale}px ${FONT}`
  const textWidth = Math.ceil(probe.measureText(text).width)
  const canvas = createCanvas(textWidth + 40, fontSize * scale + 40)
  const ctx = canvas.getContext('2d')
  ctx.font = `600 ${fontSize * scale}px ${FONT}`
  ctx.fillStyle = 'rgba(49, 108, 114, 0.55)'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText(text, canvas.width / 2, canvas.height / 2)
  return { canvas, wPts: canvas.width / scale, hPts: canvas.height / scale }
}

/* ---------- 页脚（三行说明 + 二维码） ---------- */
async function footerCanvas(pageWidth, qr) {
  const scale = 2
  const baseWidth = 1240
  const height = 150
  const canvas = createCanvas(baseWidth, height)
  const ctx = canvas.getContext('2d')
  ctx.fillStyle = 'rgba(255, 255, 255, 0.94)'
  ctx.fillRect(0, 0, baseWidth, height)
  ctx.strokeStyle = 'rgba(49, 108, 114, 0.35)'
  ctx.lineWidth = 1
  ctx.beginPath()
  ctx.moveTo(0, 1)
  ctx.lineTo(baseWidth, 1)
  ctx.stroke()

  ctx.textAlign = 'left'
  ctx.textBaseline = 'middle'
  const lines = [
    ['来源与版权：资料版权归原作者、命题学校与出版机构所有', BRAND],
    ['使用范围：仅供个人学习交流，禁止商业传播与二次售卖', '#444444'],
    [`免责说明：如有版权异议，请通过学境官网 ${SITE_DOMAIN} 联系官方，72 小时内处理`, '#666666'],
  ]
  lines.forEach(([text, color], index) => {
    ctx.font = `${index === 0 ? 600 : 400} 20px ${FONT}`
    ctx.fillStyle = color
    ctx.fillText(text, 28, 40 + index * 38)
  })

  // 右侧二维码
  const qrSize = 104
  const qrX = baseWidth - qrSize - 36
  const qrY = (height - qrSize) / 2
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(qrX - 4, qrY - 4, qrSize + 8, qrSize + 8)
  ctx.drawImage(qr, qrX, qrY, qrSize, qrSize)
  ctx.font = `400 14px ${FONT}`
  ctx.fillStyle = '#888888'
  ctx.textAlign = 'center'
  ctx.fillText('扫码访问官网', qrX + qrSize / 2, height - 10)

  // band 为「画布高 / 画布宽」的比例，实际绘制时按页面宽度等比缩放
  return { canvas, band: height / baseWidth, baseWidth, scale, pageWidth }
}

/* ---------- 封面 ---------- */
async function coverCanvas(title, qr) {
  const w = 1190
  const h = 1684
  const canvas = createCanvas(w, h)
  const ctx = canvas.getContext('2d')
  const gradient = ctx.createLinearGradient(0, 0, w, h)
  gradient.addColorStop(0, '#2f6d73')
  gradient.addColorStop(0.55, '#1d4a4f')
  gradient.addColorStop(1, '#0f2027')
  ctx.fillStyle = gradient
  ctx.fillRect(0, 0, w, h)

  // 装饰圆环
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.18)'
  ctx.lineWidth = 2
  ctx.beginPath()
  ctx.arc(w / 2, h * 0.36, 220, 0, Math.PI * 2)
  ctx.stroke()
  ctx.beginPath()
  ctx.arc(w / 2, h * 0.36, 268, 0, Math.PI * 2)
  ctx.stroke()

  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillStyle = '#ffffff'
  ctx.font = `700 96px ${FONT}`
  ctx.fillText('学境', w / 2, h * 0.33)
  ctx.font = `600 34px ${FONT}`
  ctx.globalAlpha = 0.85
  ctx.fillText('StudyScape', w / 2, h * 0.41)
  ctx.globalAlpha = 1

  // 资料标题
  ctx.font = `600 44px ${FONT}`
  ctx.fillText(title, w / 2, h * 0.56, w - 160)
  ctx.font = `400 24px ${FONT}`
  ctx.globalAlpha = 0.7
  ctx.fillText('学境资料中心 · 学习专用', w / 2, h * 0.62)
  ctx.globalAlpha = 1

  // 二维码
  const qrSize = 220
  const qrX = (w - qrSize) / 2
  const qrY = h * 0.70
  ctx.fillStyle = 'rgba(255, 255, 255, 0.96)'
  ctx.fillRect(qrX - 12, qrY - 12, qrSize + 24, qrSize + 24)
  ctx.drawImage(qr, qrX, qrY, qrSize, qrSize)

  ctx.globalAlpha = 0.85
  ctx.font = `400 22px ${FONT}`
  ctx.fillStyle = '#ffffff'
  ctx.fillText(`扫码访问学境官网 · ${SITE_DOMAIN}`, w / 2, h * 0.70 + qrSize + 58)
  ctx.globalAlpha = 0.55
  ctx.font = `400 20px ${FONT}`
  ctx.fillText('公益学习平台 · 不作任何提分承诺', w / 2, h * 0.70 + qrSize + 100)
  ctx.globalAlpha = 1
  return canvas
}

/* ---------- 末页声明 ---------- */
async function disclaimerCanvas(qr) {
  const w = 1190
  const h = 1684
  const canvas = createCanvas(w, h)
  const ctx = canvas.getContext('2d')
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, w, h)

  ctx.textAlign = 'left'
  ctx.textBaseline = 'top'
  ctx.fillStyle = BRAND
  ctx.font = `700 44px ${FONT}`
  ctx.fillText('资料使用与版权声明', 120, 130)
  ctx.strokeStyle = BRAND
  ctx.lineWidth = 3
  ctx.beginPath()
  ctx.moveTo(120, 200)
  ctx.lineTo(w - 120, 200)
  ctx.stroke()

  const paragraphs = [
    '一、本资料由学境 StudyScape 整理与分享，资料版权归原作者、命题学校及出版机构所有。',
    '二、学境不对资料内容主张任何著作权，仅对整理、排版与分享行为负责。',
    '三、本资料仅供个人学习交流使用，禁止用于商业用途、二次售卖或批量传播。',
    '四、如权利人认为本资料侵犯其合法权益，请通过学境官网联系官方，我们将在 72 小时内处理。',
    '五、资料中的题目与答案仅供参考，如有错误欢迎通过站内「开发者反馈」告知我们。',
  ]
  ctx.fillStyle = '#333333'
  ctx.font = `400 24px ${FONT}`
  let y = 270
  for (const text of paragraphs) {
    y = drawWrappedText(ctx, text, 120, y, w - 240, 46)
    y += 22
  }

  // 官网与二维码
  ctx.fillStyle = BRAND
  ctx.font = `700 28px ${FONT}`
  ctx.fillText(`官网：${SITE_DOMAIN}`, 120, y + 30)
  const qrSize = 180
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(w - 120 - qrSize - 10, y - 10, qrSize + 20, qrSize + 20)
  ctx.strokeStyle = 'rgba(49, 108, 114, 0.4)'
  ctx.lineWidth = 2
  ctx.strokeRect(w - 120 - qrSize - 10, y - 10, qrSize + 20, qrSize + 20)
  ctx.drawImage(qr, w - 120 - qrSize, y, qrSize, qrSize)

  ctx.fillStyle = '#999999'
  ctx.font = `400 18px ${FONT}`
  ctx.fillText(`学境 StudyScape · ${new Date().toLocaleDateString('zh-CN')}`, 120, h - 130)
  return canvas
}

/* ---------- 封底 ---------- */
async function backCoverCanvas(qr) {
  const w = 1190
  const h = 1684
  const canvas = createCanvas(w, h)
  const ctx = canvas.getContext('2d')
  const gradient = ctx.createLinearGradient(0, 0, 0, h)
  gradient.addColorStop(0, '#0f2027')
  gradient.addColorStop(1, '#1d4a4f')
  ctx.fillStyle = gradient
  ctx.fillRect(0, 0, w, h)

  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.35)'
  ctx.lineWidth = 2
  ctx.beginPath()
  ctx.arc(w / 2, h * 0.34, 96, 0, Math.PI * 2)
  ctx.stroke()
  ctx.fillStyle = '#ffffff'
  ctx.font = `700 56px ${FONT}`
  ctx.fillText('学境', w / 2, h * 0.342)
  ctx.globalAlpha = 0.85
  ctx.font = `400 26px ${FONT}`
  ctx.fillText('StudyScape · 学习的新境界', w / 2, h * 0.43)
  ctx.globalAlpha = 0.7
  ctx.font = `400 22px ${FONT}`
  const lines = [
    '资料版权归原作者、命题学校与出版机构所有',
    '学境仅对整理与分享行为负责，不主张内容著作权',
    `如有版权异议，请通过学境官网 ${SITE_DOMAIN} 联系官方`,
  ]
  lines.forEach((line, index) => ctx.fillText(line, w / 2, h * 0.49 + index * 46))

  const qrSize = 190
  ctx.globalAlpha = 1
  ctx.fillStyle = '#ffffff'
  ctx.fillRect((w - qrSize) / 2 - 10, h * 0.66 - 10, qrSize + 20, qrSize + 20)
  ctx.drawImage(qr, (w - qrSize) / 2, h * 0.66, qrSize, qrSize)
  ctx.globalAlpha = 0.85
  ctx.font = `400 22px ${FONT}`
  ctx.fillText(`扫码访问官网 · ${SITE_DOMAIN}`, w / 2, h * 0.66 + qrSize + 56)
  ctx.globalAlpha = 0.5
  ctx.font = `400 18px ${FONT}`
  ctx.fillText('© 2026 学境 StudyScape', w / 2, h - 110)
  ctx.globalAlpha = 1
  return canvas
}

function drawWrappedText(ctx, text, x, y, maxWidth, lineHeight) {
  let line = ''
  for (const char of text) {
    const test = line + char
    if (ctx.measureText(test).width > maxWidth && line) {
      ctx.fillText(line, x, y)
      line = char
      y += lineHeight
    }
    else {
      line = test
    }
  }
  if (line) {
    ctx.fillText(line, x, y)
    y += lineHeight
  }
  return y
}

/**
 * 给资料 PDF 加学境水印。返回 { blob, stamped }。
 * 已带 xj-stamped 标记的文件原样返回，避免重复叠加。
 */
export async function stampMaterialPdf(blob, filename) {
  const bytes = await blob.arrayBuffer()
  const doc = await PDFDocument.load(bytes)
  let keywords = ''
  try {
    keywords = String(doc.getKeywords() || '')
  }
  catch { /* 无元数据 */ }
  if (keywords.includes('xj-stamped')) {
    return { blob, stamped: false }
  }

  const contentPages = doc.getPages()
  const qr = await qrImage()

  // 1) 正文平铺水印
  const tile = watermarkTile()
  const tilePng = await doc.embedPng(tile.canvas.toDataURL('image/png'))
  for (const page of contentPages) {
    const { width, height } = page.getSize()
    const stepX = tile.wPts * 1.9
    const stepY = 78
    let row = 0
    for (let y = -height * 0.2; y < height * 1.25; y += stepY) {
      const offset = row % 2 ? stepX * 0.5 : 0
      for (let x = -width * 0.25; x < width * 1.25; x += stepX) {
        try {
          page.drawImage(tilePng, {
            x: x + offset + tile.wPts / 2,
            y: y - tile.hPts / 2,
            width: tile.wPts,
            height: tile.hPts,
            rotate: degrees(45),
            opacity: 0.14,
          })
        }
        catch { /* 单块失败不阻断整页 */ }
      }
      row += 1
    }
  }

  // 2) 页脚（按每页宽度等比拉伸）
  const firstWidth = contentPages[0]?.getSize().width || A4[0]
  const footer = await footerCanvas(firstWidth, qr)
  const footerPng = await doc.embedPng(footer.canvas.toDataURL('image/png'))
  for (const page of contentPages) {
    const { width } = page.getSize()
    const bandHeight = footer.band * (width / footer.baseWidth)
    try {
      page.drawImage(footerPng, { x: 0, y: 0, width, height: bandHeight })
    }
    catch { /* 页脚失败不阻断 */ }
  }

  // 3) 封面
  const coverPng = await doc.embedPng((await coverCanvas(cleanTitle(filename), qr)).toDataURL('image/png'))
  doc.insertPage(0, A4).drawImage(coverPng, { x: 0, y: 0, width: A4[0], height: A4[1] })

  // 4) 末页声明
  const disclaimerPng = await doc.embedPng((await disclaimerCanvas(qr)).toDataURL('image/png'))
  doc.addPage(A4).drawImage(disclaimerPng, { x: 0, y: 0, width: A4[0], height: A4[1] })

  // 5) 封底
  const backPng = await doc.embedPng((await backCoverCanvas(qr)).toDataURL('image/png'))
  doc.addPage(A4).drawImage(backPng, { x: 0, y: 0, width: A4[0], height: A4[1] })

  try {
    doc.setKeywords([`${STAMP_TAG} xj-ban/${SITE_DOMAIN}`])
  }
  catch { /* 元数据可选 */ }
  try {
    doc.setProducer('StudyScape')
  }
  catch { /* 元数据可选 */ }

  const out = await doc.save({ useObjectStreams: false })
  return { blob: new Blob([out], { type: 'application/pdf' }), stamped: true }
}

export function saveBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename || '学境资料.pdf'
  document.body.appendChild(link)
  link.click()
  link.remove()
  setTimeout(() => URL.revokeObjectURL(url), 5000)
}
