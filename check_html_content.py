import sqlite3
conn = sqlite3.connect(r'c:\Users\Administrator\Desktop\StudyScape\exam.db')
cur = conn.cursor()
# Find questions with is_html=1
cur.execute("SELECT id, is_html, substr(content, 1, 500) FROM questions WHERE is_html=1 ORDER BY id DESC LIMIT 5")
rows = cur.fetchall()
for r in rows:
    print(f"=== id={r[0]}, is_html={r[1]} ===")
    print(r[2])
    print("...")
    print()
conn.close()
