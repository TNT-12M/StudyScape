# OCR流水线 → StudyScape网站 对接文档

## 一、整体架构

```
PDF文件 → [阿里云OCR] → [图片裁剪base64] → [DeepSeek LLM结构化] → PaperCutter-VL格式JSON → 网站导入
```

脚本: `ocr_pipeline.py`
输出目录: `d:\lian\新建文件夹\ocr_output\`

## 二、输出JSON格式

输出为 **PaperCutter-VL包装格式**，网站 `api.php` 的 `import_questions_json` 接口已原生支持（模式0a）。

### 2.1 题目文件格式（真题原卷）

```json
{
  "match_key": "2025年安徽中考化学真题原卷完整版",
  "paper_name": "2025年安徽中考化学真题原卷完整版",
  "subject": "化学",
  "education_level": "junior",
  "questions": [
    {
      "question_id": "1",
      "question_type": "单选题",
      "question_content": "2025年安徽省政府工作报告提出...",
      "question_options": ["A. 建设可再生资源...", "B. ...", "C. ...", "D. ..."],
      "question_images": ["/9j/4AAQ..."],
      "question_tables": [],
      "answer": "",
      "resolve": "",
      "analysis_images": [],
      "difficulty": 3,
      "source": "2025年安徽中考化学真题原卷完整版",
      "subject": "化学",
      "education_level": "junior",
      "knowledge_points": [],
      "sub_questions": []
    }
  ]
}
```

### 2.2 答案文件格式（答案/解析）

```json
{
  "match_key": "2025年安徽中考化学答案",
  "paper_name": "2025年安徽中考化学答案",
  "subject": "化学",
  "education_level": "junior",
  "questions": [
    {
      "question_id": "1",
      "question_type": "单选题",
      "question_content": "第1题",
      "question_options": [],
      "question_images": [],
      "question_tables": [],
      "answer": "D",
      "resolve": "",
      "analysis_images": [],
      "difficulty": 3,
      "source": "2025年安徽中考化学答案",
      "subject": "化学",
      "education_level": "junior",
      "knowledge_points": [],
      "sub_questions": []
    }
  ]
}
```

**答案文件识别逻辑**: `question_content` 为空或≤30字 且有 `answer`/`resolve` → 网站判定为答案文件，按题号顺序自动匹配到已导入的题目。

## 三、图片处理

### 3.1 图片提取流程

1. PyMuPDF 渲染PDF页面为PNG（200 DPI）
2. 调用阿里云OCR，API返回 `figure` 数组（含 x, y, w, h 坐标）
3. 按坐标从渲染图片中裁剪区域
4. 转为 base64 JPEG（quality=85）
5. 按y→x坐标排序，在OCR文本中插入 `[图片N]` 标记
6. LLM将图片标记分配到对应题目的 `question_images` 字段
7. 脚本将标记替换为实际 base64 字符串

### 3.2 网站端图片渲染

网站 `api.php` 第2784-2839行已实现：
- 从 `question_images` 数组提取 base64
- 包装为 `<div style="text-align:center"><img src="data:image/jpeg;base64,..." style="max-width:100%;height:auto;"></div>`
- 追加到 `content` 字段
- 从 `analysis_images` 数组提取，追加到 `explanation` 字段
- 按 base64 前80字符去重

**无需额外适配**，现有代码已完全兼容。

## 四、上传使用流程

### 步骤1: 运行脚本处理PDF

```bash
python ocr_pipeline.py
```

### 步骤2: 上传题目文件

1. 登录网站管理后台
2. 题库管理 → 批量导入
3. 上传 `2025年安徽中考化学真题原卷完整版.json`
4. 选择学段（初中/高中）
5. 提交 → 题目入库（无答案）

### 步骤3: 上传答案文件

1. 同一页面，上传 `2025年安徽中考化学答案.json`
2. 网站自动识别为答案文件
3. 按题号顺序匹配到已导入的题目
4. 回填 `correct_answer` 和 `explanation`

### 步骤4: 验证

1. 题库列表中查看题目
2. 确认题干、选项、答案、图片均正确显示

## 五、给网站开发工程师的建议

### 5.1 已兼容（无需改动）

| 功能 | 状态 |
|------|------|
| PaperCutter-VL格式JSON导入 | ✅ 已支持 |
| base64图片自动嵌入HTML | ✅ 已支持 |
| 答案文件自动匹配（按match_key+题号精准匹配） | ✅ 已支持 |
| 图片去重 | ✅ 已支持 |
| 富文本标记 is_html | ✅ 已支持 |
| 科目自动识别（优先JSON的subject字段） | ✅ 已支持 |
| 学段自动识别（优先JSON的education_level字段） | ✅ 已支持 |
| match_key 匹配码 | ✅ 已支持 |

### 5.2 答案匹配机制说明

网站端实现了**两级匹配**机制：

1. **精准匹配（推荐）**：当 JSON 中包含 `match_key` 字段时，使用 `match_key + question_id` 精准匹配题目和答案。同一套卷的题目文件和答案文件使用相同的 `match_key` 即可确保 100% 准确匹配，不同试卷之间不会串题。

2. **顺序匹配（兜底）**：当没有 `match_key` 时，按"科目+学段下无答案的题目顺序"依次匹配（兼容旧版导入方式）。

### 5.3 建议优化（非阻塞）

1. **大文件支持**: 多页试卷的JSON可能超过1MB（含base64图片），建议上传接口的 `post_max_size` 和 `upload_max_filesize` 设为至少 20MB
2. **批量处理进度**: 考虑添加批量上传多个JSON文件的接口，脚本一次会输出320+个文件

### 5.4 数据库字段映射

| JSON字段 | 数据库字段 | 说明 |
|----------|-----------|------|
| question_content | content | 题干 |
| question_options | options (JSON array) | 选项 |
| answer | correct_answer | 正确答案 |
| resolve | explanation | 解析 |
| difficulty | difficulty (1-5) | 难度 |
| subject | subject | 科目 |
| education_level | education_level | 学段 |
| question_type → 标准值 | question_type | single/multiple/judge/fill/multi_fill/short |
| question_images (base64) | content (HTML嵌入) | 图片内嵌到题干 |
| analysis_images (base64) | explanation (HTML嵌入) | 图片内嵌到解析 |
| is_html (自动标记) | is_html | 1=含HTML |
| match_key | match_key | 匹配码（题目-答案精准匹配用） |
| question_id | source_qid | 原始题号（配合 match_key 做精准匹配） |

## 六、脚本配置

```python
# 阿里云OCR
ALIYUN_ACCESS_KEY_ID = "your_access_key_id_here"
ALIYUN_ACCESS_KEY_SECRET = "your_access_key_secret_here"

# DeepSeek LLM
DEEPSEEK_API_KEY = "sk-your_deepseek_api_key_here"
DEEPSEEK_BASE_URL = "https://api.deepseek.com/v1"
DEEPSEEK_MODEL = "deepseek-v4-flash"

# 渲染参数
RENDER_DPI = 200  # PDF渲染分辨率
API_INTERVAL = 0.5  # API调用间隔(秒)
MAX_RETRIES = 3  # 失败重试次数
```

## 七、全量处理

当前脚本 `main()` 中的 `test_files` 列表改为扫描整个目录即可全量处理:

```python
import glob
test_files = glob.glob(os.path.join(SOURCE_DIR, "**", "*.pdf"), recursive=True)
```

320份PDF预计处理时间: 约2-3小时（OCR ~3秒/页 + LLM ~10秒/文件）
