# OCR 流水线与 StudyScape 对接说明

本文说明当前仓库中的 OCR Worker、PaperCutter-VL JSON 导入格式和审核入库流程。

## 1. 当前实现

仓库内实际可运行的 OCR Worker 是：

```text
ocr/worker.py
```

它由 PHP API 异步启动，使用阿里云教育试卷结构化 OCR，将 PDF/图片转换为待审核题目 JSON：

```text
PDF / 图片
  ↓
PyMuPDF 渲染 PDF 页面
  ↓
阿里云 RecognizeEduPaperStructed
  ↓
ocr/worker.py 标准化题干、选项、答案、解析和图片
  ↓
ocr_import_batches 批次
  ↓
Vue 管理端审核、编辑、剔除
  ↓
统一入库 questions 表
```

PHP 入口：

- `ocr_batch_create`：创建 OCR 批次并启动 Worker。
- `ocr_batch_list`：查看批次和状态。
- `ocr_batch_get`：读取待审核结果。
- `ocr_batch_retry`：重试失败批次并重新启动 Worker。
- `ocr_batch_delete`：删除批次。
- `ocr_batch_commit`：把审核后的题目写入题库。

这些接口只允许 root 或 content_admin 使用。

## 2. 运行环境

安装 Python 依赖：

```bash
python -m pip install -r ocr/requirements.txt
```

`ocr/requirements.txt` 当前包含：

```text
alibabacloud_ocr_api20210707
alibabacloud_tea_openapi
alibabacloud_tea_util
Pillow
PyMuPDF
```

Worker 从项目外的 `AccessKey .env` 读取：

```dotenv
ALIYUN_ACCESS_KEY_ID=your_access_key_id
ALIYUN_ACCESS_KEY_SECRET=your_access_key_secret
```

不要把真实 AccessKey 写进 Python 文件、JSON、Git 或前端。生产环境建议将该文件设置为 PHP-FPM/Worker 用户可读、其他用户不可读。

## 3. Worker 输出格式

Worker 输出标准包装对象：

```json
{
  "paper_name": "sample.pdf",
  "subject": "化学",
  "education_level": "junior",
  "questions": [
    {
      "source_qid": "1",
      "part_title": "选择题",
      "question_type": "single",
      "content": "题干内容",
      "options": ["选项一", "选项二", "选项三", "选项四"],
      "correct_answer": "",
      "explanation": "",
      "difficulty": 3,
      "points": 1,
      "is_html": 0,
      "images": [],
      "included": true
    }
  ]
}
```

字段说明：

| 字段 | 说明 |
|---|---|
| `source_qid` | 原始题号，答案匹配和审核时使用 |
| `question_type` | 标准题型，如 `single`、`multiple`、`judge`、`fill`、`short` |
| `content` | 题干 |
| `options` | 选项数组 |
| `correct_answer` | 答案，可为空，后续用答案 JSON 回填 |
| `explanation` | 解析 |
| `difficulty` | 1-5 的难度 |
| `points` | 题目分值 |
| `images` | OCR 识别出的图片和坐标信息 |
| `included` | 审核时是否保留，设为 `false` 的题目不会入库 |

## 4. PaperCutter-VL JSON 导入

除 OCR Worker 输出外，题库管理还支持 PaperCutter-VL 包装 JSON。题目文件示例：

```json
{
  "match_key": "2025_anhui_zhongkao_chemistry",
  "paper_name": "2025年安徽中考化学真题",
  "subject": "化学",
  "education_level": "junior",
  "questions": [
    {
      "question_id": "1",
      "question_type": "单选题",
      "question_content": "题干内容",
      "question_options": ["A. 选项一", "B. 选项二", "C. 选项三", "D. 选项四"],
      "question_images": [],
      "question_tables": [],
      "answer": "",
      "resolve": "",
      "difficulty": 3
    }
  ]
}
```

答案文件可以使用同一个 `match_key`：

```json
{
  "match_key": "2025_anhui_zhongkao_chemistry",
  "paper_name": "2025年安徽中考化学答案",
  "subject": "化学",
  "education_level": "junior",
  "questions": [
    {
      "question_id": "1",
      "question_content": "第1题",
      "answer": "D",
      "resolve": "解析内容"
    }
  ]
}
```

导入接口会将字段转换为：

| JSON | `questions` 表 |
|---|---|
| `question_content` | `content` |
| `question_options` | `options` |
| `answer` | `correct_answer` |
| `resolve` | `explanation` |
| `question_type` | 标准化后的 `question_type` 和 `category` |
| `education_level` | `education_level` |
| `match_key` | `match_key` |
| `question_id` | `source_qid` |
| `question_images` | 题干 HTML 图片 |
| `analysis_images` | 解析 HTML 图片 |

## 5. 题目和答案匹配

推荐在题目和答案 JSON 中使用相同的 `match_key`。导入答案时，后端按以下顺序匹配：

1. `match_key + source_qid` 精确匹配；
2. 同科目、同学段、同 `source_qid` 兜底匹配；
3. 同科目、同学段下尚未填写答案的题目顺序匹配。

最终入库仍会按题干、科目和学段查重。

## 6. 使用流程

### OCR 文件

1. root 或 content_admin 登录 `/admin/`。
2. 打开“智能 OCR 审核”。
3. 上传 PDF 或图片。
4. 等待批次变成“待审核”。
5. 修改题干、选项、答案、解析，剔除错误题目。
6. 点击“统一入库”。

### PaperCutter-VL JSON

1. 打开“题库管理”。
2. 选择 JSON/OCR 导入。
3. 上传题目 JSON，必要时同时读取答案 JSON。
4. 确认学段、科目和分类。
5. 执行导入并查看新增、更新、跳过和失败数量。

## 7. 图片和大文件注意事项

- 图片通常以 Base64 嵌入题干或解析，文件会明显变大。
- PHP `upload_max_filesize` 和 `post_max_size` 应根据题目 JSON 大小调整，生产建议至少 20MB，含大量图片时提高到 128MB。
- OCR 原文件暂存于 `data/ocr_batches/`，该目录必须允许 PHP-FPM 写入，但禁止 Nginx 直接访问。
- 不要把 OCR 运行结果、原始上传文件和 AccessKey 提交到 Git。
