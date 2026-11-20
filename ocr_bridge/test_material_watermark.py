# -*- coding: utf-8 -*-

import os
import tempfile
import time
import unittest
from pathlib import Path

try:
    import pymupdf as fitz
except ImportError:
    try:
        import fitz
    except ImportError:
        fitz = None

import material_watermark as watermark


@unittest.skipIf(fitz is None or watermark._DEPENDENCY_ERROR is not None,
                 "PDF watermark dependencies are not installed")
class MaterialWatermarkTest(unittest.TestCase):
    def setUp(self):
        try:
            watermark.find_cjk_font()
        except watermark.CJKFontNotFoundError as exc:
            self.skipTest(str(exc))

    def test_two_stage_rendering_and_cache(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            root = Path(temp_dir)
            source = root / "source.pdf"
            platform = root / "platform.pdf"
            user = root / "user.pdf"

            with fitz.open() as document:
                for text in ("source page one", "source page two"):
                    page = document.new_page()
                    page.insert_text((72, 72), text)
                document.save(source)

            source_hash = watermark.sha256_file(source)
            self.assertTrue(watermark.validate_pdf(source))
            watermark.render_platform_watermark(source, platform, "测试资料")
            self.assertEqual(source_hash, watermark.sha256_file(source))
            self.assertTrue(watermark.validate_pdf(platform))

            self.assertEqual(watermark.platform_source_sha256(platform), source_hash)
            self.assertIsNone(watermark.platform_source_sha256(source))

            with fitz.open(platform) as document:
                self.assertEqual(document.page_count, 5)
                tags = watermark._parse_tags(document.metadata)
                self.assertEqual(tags["renderer"], watermark.PLATFORM_MARK)
                self.assertEqual(tags["body_start"], "1")
                self.assertEqual(tags["body_count"], "2")

            platform_mtime = platform.stat().st_mtime_ns
            time.sleep(0.01)
            watermark.render_platform_watermark(source, platform, "测试资料")
            self.assertEqual(platform_mtime, platform.stat().st_mtime_ns)

            platform_hash = watermark.sha256_file(platform)
            watermark.render_user_watermark(platform, user, "张三", 1001)
            self.assertEqual(platform_hash, watermark.sha256_file(platform))
            self.assertTrue(watermark.validate_pdf(user))

            with fitz.open(user) as document:
                self.assertEqual(document.page_count, 5)
                tags = watermark._parse_tags(document.metadata)
                self.assertEqual(tags["renderer"], watermark.USER_MARK)
                self.assertEqual(tags["username"], "张三")
                self.assertEqual(tags["user_id"], "1001")
                self.assertNotIn("仅限用户", document[0].get_text())
                body_one = document[1].get_text().replace("\xa0", " ")
                body_two = document[2].get_text().replace("\xa0", " ")
                self.assertIn("仅限用户 张三 使用", body_one)
                self.assertIn("仅限用户 张三 使用", body_two)
                self.assertNotIn("仅限用户", document[3].get_text())

            user_mtime = user.stat().st_mtime_ns
            time.sleep(0.01)
            watermark.render_user_watermark(platform, user, "张三", 1001)
            self.assertEqual(user_mtime, user.stat().st_mtime_ns)

    def test_rejects_invalid_input_and_same_output(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            root = Path(temp_dir)
            invalid = root / "invalid.pdf"
            invalid.write_bytes(b"not a pdf")
            self.assertFalse(watermark.validate_pdf(invalid))
            with self.assertRaises(ValueError):
                watermark.render_platform_watermark(invalid, root / "out.pdf", "资料")

            source = root / "source.pdf"
            with fitz.open() as document:
                document.new_page()
                document.save(source)
            with self.assertRaises(ValueError):
                watermark.render_platform_watermark(source, source, "资料")


if __name__ == "__main__":
    unittest.main()
