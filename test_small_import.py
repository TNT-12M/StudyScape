import requests
s = requests.Session()
r0 = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r0.json().get('csrf_token','')
print('csrf:', csrf[:20], '...')

test_json = '[{"question_id":"1","question_content":"测试题目","question_options":["A. 选项一","B. 选项二"],"question_images":[],"question_tables":[],"analysis_images":[],"difficulty":"","question_type":"选择题","source":"2025安徽中考","answer":"","resolve":"","source_year":"2025","source_province":"安徽"}]'
r = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'import_questions_json',
    'json_data': test_json,
    'education_level': 'junior',
    'category': '',
    'subject': '道德与法治',
    'csrf_token': csrf
}, timeout=30)
print('status:', r.status_code)
print('body:', r.text[:800])
