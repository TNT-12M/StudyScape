# PaperCutter-VL 功能改进建议

本文档整理了 StudyScape 在线题库系统在使用 PaperCutter-VL 处理真题试卷时遇到的实际问题，建议在 PaperCutter-VL 的大语言模型（LLM）处理阶段和 OCR 后处理阶段直接解决，避免下游系统反复修补。

---

## 一、核心问题概述

当前 PaperCutter-VL 输出两个独立的 JSON 文件：
- **真题原卷文件**：包含题干、选项、图片、表格，但 `answer` 和 `resolve` 字段为空
- **答案文件**：包含答案和解析，但 `question_content` 等字段为空或仅含简短标签（如"13.(5分,每空1分)"）

这要求下游系统在导入时进行二次合并匹配，反复出现以下问题：
1. 题号错位导致答案与题目不匹配（尤其是复合大题）
2. 用户上传顺序（先答案后题目，或先题目后答案）影响合并结果
3. 单独导入答案文件时 0 道题匹配成功
4. 多科目混合试卷无法正确关联

### 改进方案：在 LLM 处理阶段直接合并答案

**建议 PaperCutter-VL 增加一个单文件输出模式，直接输出一份"题库+答案合并版 JSON"：**

```json
{
  "match_key": "2025年安徽中考化学",
  "paper_name": "2025年安徽中考化学",
  "subject": "化学",
  "education_level": "junior",
  "source_year": "2025",
  "source_province": "安徽",
  "questions": [
    {
      "question_id": 1,
      "question_type": "单选题",
      "question_content": "2025年安徽省政府工作报告提出……下列做法不符合该目标的是",
      "question_options": [
        "A. 建设可再生资源回收体系",
        "B. 全面实施绿美江淮行动",
        "C. 推广应用建筑光伏一体化",
        "D. 增大化石能源消费比例"
      ],
      "question_images": [
        "/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQg..."
      ],
      "question_tables": [],
      "analysis_images": [],
      "difficulty": 3,
      "source": "2025年安徽省初中学业水平考试",
      "knowledge_points": [],
      "sub_questions": [],
      "answer": "D",
      "resolve": "A、B、C均符合降碳减污扩绿增长目标；D增大化石能源消费比例不符合该目标，故选D。"
    }
  ]
}
```

### 关键变更点

| 字段 | 原卷当前值 | 答案文件当前值 | 建议合并后 |
|------|-----------|---------------|-----------|
| `question_content` | ✅ 完整题干 | ❌ 空 / "13.(5分)" | ✅ 完整题干 |
| `question_options` | ✅ 完整选项 | ❌ 空数组 | ✅ 完整选项 |
| `question_images` | ✅ base64图片 | ❌ 空数组 | ✅ base64图片 |
| `question_tables` | ✅ HTML表格 | ❌ 空数组 | ✅ HTML表格 |
| `answer` | ❌ 空字符串 | ✅ "D" | ✅ "D" |
| `resolve` | ❌ 空字符串 | ✅ 完整解析 | ✅ 完整解析 |
| `analysis_images` | ✅ base64图片（如有） | ✅ base64图片（如有） | ✅ base64图片（如有） |
| `question_type` | ✅ 正确 | ✅ 正确 | ✅ 正确 |
| `difficulty` | null | null | **建议由LLM预估 1-5 难度分** |
| `knowledge_points` | [] | [] | **建议由LLM自动标注知识点** |

### 实施思路（LLM Prompt 调整）

在当前"原卷解析"和"答案解析"两个流程之间增加一个**合并步骤**，或调整 Prompt 让 LLM 直接在一次解析中同时完成：

```
[现有Prompt的题目提取部分] + 

请同步将题目与参考答案和解析进行关联匹配：
- 每道题目的 answer 字段必须填入参考答案（选择题填字母，如 A/AB；填空题填具体答案）
- 每道题目的 resolve 字段必须填入完整解析内容，包括：
  - 选项类题目：说明为什么正确选项对，错误选项错
  - 计算类题目：写出关键步骤和结果
  - 填空/简答类题目：写出参考答案要点或得分点
- 每道题目的 difficulty 字段：根据该题的常见正确率估算，1=简单，3=中等，5=困难
- 每道题目的 knowledge_points 字段：列出涉及的2-5个知识点关键词
```

---

## 二、扫描/切题完整性问题

### 问题表现

当前 OCR + LLM 处理后，常有以下"不完整题目"出现在输出中：

#### 问题1：只有材料/引言，没有问题（材料阅读理解类题目）

**错误示例**：
```json
{
  "question_id": 13,
  "question_type": "单选题",
  "question_content": "阅读下列材料，完成7~8小题。元素周期表中原子序数57~71的元素……钆(Gd)被誉为'世界上最冷的金属'。",
  "question_options": [],
  "answer": ""
}
```

**问题描述**：这段内容是第7~8小题的共用材料背景，不是独立题目。但被单独切成了一道题，选项空、答案空。

**建议改进**：
1. **共用材料识别**：LLM Prompt 中明确：遇到"阅读下列材料，完成N~M小题""请回答N~M题""根据以上信息完成N~M题"等表述时，应识别为**共用材料**，不单独作为题目。
2. **将共用材料复制到子题目**：把共用材料的文本和图片 prepend 到第7题、第8题……的 `question_content` 开头，并在 `sub_questions` 数组中标记题号范围。
3. **材料图片处理**：若共用材料中含有图表、示意图等图片，也需要复制到每个小题的 `question_images` 中。

#### 问题2：大题被切成"只有小题标签"的碎片

**错误示例**：
```json
{
  "question_id": 15,
  "question_content": "15.(8分,每空2分)",
  "question_type": "单选题",
  "question_options": [],
  "answer": "(1)过滤 (2)NaCl (3)蒸发结晶"
}
```

**问题描述**：这是大题（非选择题）的分数标签和答案，但是题目问题、材料被切到了上一个 question 或完全丢失。用户看到的是"题干=分数标签，选项为空"的无效题目。

**建议改进**：
1. **大题完整性检测**：对于 `question_type` 为"填空题""简答题""计算题"等非选择题，LLM 必须输出完整的题目问题，不能仅输出"13.(5分)"这种题号+分值标签。
2. **最小长度校验**：`question_content` 去除题号/分值标签后，长度小于20个汉字且不含图片时，判定为**不完整题目**，LLM 需要回溯前面的材料合并。
3. **大题-小题结构化**：建议为非选择题增加 `sub_questions` 结构：
   ```json
   {
     "question_id": 15,
     "question_type": "填空题",
     "question_content": "【完整的大题材料+所有小问题干】",
     "sub_questions": [
       {
         "sub_id": 1,
         "content": "(1) 操作①的名称是______",
         "answer": "过滤",
         "points": 2
       },
       {
         "sub_id": 2,
         "content": "(2) 溶液B中的溶质是______",
         "answer": "NaCl",
         "points": 2
       }
     ],
     "question_images": ["/9j/..."],
     "answer": "(1)过滤 (2)NaCl (3)蒸发结晶",
     "resolve": "……"
   }
   ```

#### 问题3：选项被丢失或合并

**错误示例**：
```json
{
  "question_id": 8,
  "question_content": "下列说法正确的是",
  "question_options": [],
  "question_type": "单选题"
}
```

**问题描述**：A/B/C/D 四个选项没有出现在 `question_options` 数组中，可能是被合并进了题干、或者被跳过了。

**建议改进**：
1. **选项完整性校验**：对于 `question_type` 为"单选题"或"多选题"的题目，若 `question_options` 选项数量小于 2 个，判定为**不完整**，触发 LLM 重新提取。
2. **选项格式标准化**：所有选项必须去掉"A. ""A、""A)"等前缀后保留纯文本内容，或保留"前缀+文本"但保持格式一致。下游系统不应该再承担"清洗选项前缀"的工作。
3. **判断题识别**：如果选项只有两个且内容为"正确""错误"或"对""错"，则 `question_type` 应标记为"判断题"而非"单选题"。

#### 问题4：图表/表格与题目文字分离

**问题描述**：表格/流程图/示意图的 base64 图片单独放在 `question_images` 中，但题干文字里没有对图片位置的描述，用户阅读时可能不知道图片对应哪道题的哪个部分。

**建议改进**：
1. **图文位置关联**：LLM 可以在生成 `question_content` 时，在适当位置加入图片引用标记（如 `[图1]`、`[表2]`），并在 `question_images` 中增加 `caption` 字段进行对应。
2. **表格优先转 HTML**：除了将表格存为 base64 图片，建议同步将表格内容转成 HTML `<table>` 格式追加到 `question_content` 中，方便文字搜索。

---

## 三、输出质量自检清单（建议在 PaperCutter-VL 输出前自动校验）

建议在最终输出 JSON 之前，增加一轮程序校验（不需要再调 LLM，纯规则即可），对未通过校验的题目打印警告，方便人工检查：

| # | 检查项 | 判定规则 | 不通过处理 |
|---|--------|---------|-----------|
| 1 | 题干非空 | `trim(question_content)` 去除题号/分值标签后长度 < 20 且无图片/表格 | ⚠️ 警告 |
| 2 | 答案非空 | `trim(answer)` 为空 | ❌ 必须修复 |
| 3 | 选项完整 | 单选题/多选题的 `question_options.length < 2` | ⚠️ 警告 |
| 4 | 材料题检测 | 含"完成N~M小题""回答N~M题"且本身没有明确问题 | ⚠️ 标记为共用材料，不单独输出 |
| 5 | 题号连续 | `question_id` 不连续（例如缺少第5题） | ⚠️ 警告 |
| 6 | 题型匹配 | 非选择题但选项非空 / 选择题但选项为空 | ⚠️ 警告 |
| 7 | 图片有效性 | `question_images` 中的 base64 能成功解码为图片（长度>100字节） | ⚠️ 警告 |
| 8 | 解析长度 | 答案解析 `trim(resolve)` 为空（考试真题应有解析） | ⚠️ 警告 |

---

## 四、StudyScape 端的接口要求

如果 PaperCutter-VL 按以上建议合并输出，StudyScape 端只需要：

1. 上传**单个**合并版 JSON 文件 → 系统直接入库，不需要答案合并
2. `questions[n].answer` 直接映射到数据库 `correct_answer`
3. `questions[n].resolve` 直接映射到数据库 `explanation`
4. `questions[n].difficulty` 直接映射（默认 1-5 分）
5. `questions[n].knowledge_points` 可以存入题目元数据或直接拼接进 `explanation`

**不再需要**：
- 单独的"答案文件"上传按钮
- 前端/后端的题号位置匹配和对齐逻辑
- OFFSET 错位修复
- 上传顺序兼容代码

---

## 五、附录：当前 StudyScape 支持的题目结构

```python
# 数据库 questions 表字段
{
  "id": INTEGER PRIMARY KEY,          # 自增
  "subject": TEXT NOT NULL,           # 科目：化学 / 物理 / 道德与法治 / ...
  "question_type": TEXT NOT NULL,     # single / multiple / judge / fill / multi_fill / short
  "category": TEXT,                   # 通常与 question_type 相同
  "education_level": TEXT NOT NULL,   # junior (初中) / senior (高中)
  "content": TEXT NOT NULL,           # 题干（可含 HTML <img> / <table>）
  "options": JSON,                    # 选项数组 ["A. xxx", "B. yyy"] 或 null
  "correct_answer": TEXT NOT NULL,    # 参考答案（关键！必须非空）
  "explanation": TEXT,                # 解析 / 答案说明
  "difficulty": INTEGER DEFAULT 3,    # 1-5
  "points": REAL DEFAULT 1.0,         # 分值
  "is_html": INTEGER DEFAULT 0,       # 1=content含HTML/img/table，0=纯文本
  "created_at": TIMESTAMP,
  "updated_at": TIMESTAMP
}
```

PaperCutter-VL 的输出字段与上表的映射关系：

| PaperCutter-VL 字段 | StudyScape DB 字段 | 备注 |
|---|---|---|
| `subject` | `subject` | 直接使用 |
| `question_type` | `question_type` + `category` | 需转换：单选题→single，多选题→multiple，判断题→judge，填空题→fill 或 multi_fill，简答题→short |
| `question_content` | `content` | 若含 `<img>` / `<table>` 需同时设置 `is_html=1` |
| `question_options` | `options` | JSON 数组，建议保留或去除 "A." 前缀均可 |
| `answer` | `correct_answer` | **必须非空** |
| `resolve` | `explanation` | 建议非空 |
| `question_images` | → 嵌入 `content` 中为 `<img src="data:image/jpeg;base64,...">` | 或由下游系统处理 |
| `question_tables` | → 嵌入 `content` 中为 HTML `<table>` | 或由下游系统处理 |
| `difficulty` | `difficulty` | 直接使用 |
| `knowledge_points` | → 追加到 `explanation` 开头 | 例："【知识点：溶解度、饱和溶液】xxxxx" |
