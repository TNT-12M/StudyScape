import requests, json
s = requests.Session()

# Step 1: login
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r.json().get('csrf_token','')
print('1. Login:', r.json()['success'])

# Step 2: load 1.json
with open(r'c:\Users\Administrator\Desktop\StudyScape\1.json','r',encoding='utf-8') as f:
    json_data = f.read()

# Step 3: import
r2 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'import_questions_json',
    'json_data': json_data,
    'education_level': 'junior',
    'category': '',  # auto detect
    'subject': '道德与法治',
    'csrf_token': csrf
}, timeout=60)
j = r2.json()
print('2. Import:', j['success'], '|', j['message'])
print('   parsed:', j['data']['parsed_count'], 'subject:', j['data']['subject'])
print('   stats:', j['data']['stats'])

# Step 4: Verify via list_questions - check the newly imported items include is_html=1
r3 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'list_questions','scope':'manage_all','page':'1','page_size':'30',
    'subject':'道德与法治','education_level':'junior','qtype':'','keyword':'','csrf_token':csrf
})
j3 = r3.json()
print('3. After import - 道德与法治 total:', j3['data']['total'])
items = j3['data']['items']
for it in items[:3]:
    print(f"   id={it['id']} type={it['question_type']} cat={it['category']} is_html={it.get('is_html')} content_len={len(it['content'])} opts={len(it['options'] or [])} answer={it['correct_answer'][:40]}")

# Step 5: Find any image-rich item and verify is_html=1
img_items = [it for it in items if it.get('is_html')]
print('\n4. HTML items count:', len(img_items))
if img_items:
    it = img_items[0]
    # Check if img base64 appears in content
    has_img = '<img' in it['content'] and 'data:image' in it['content']
    has_table = '<table' in it['content']
    print(f"   sample id={it['id']} is_html={it['is_html']} has_img={has_img} has_table={has_table}")
    # Check the non-HTML items are clean
    non_html = [it for it in items if not it.get('is_html')]
    print(f"   non-html items count={len(non_html)} - good")

# Step 6: Test get_question returns is_html correctly
if img_items:
    r4 = s.post('http://127.0.0.1:8080/api.php', data={
        'action':'get_question','id':img_items[0]['id'],'csrf_token':csrf
    })
    j4 = r4.json()
    q = j4['data']['question']
    print(f"\n5. get_question for id={q['id']}: is_html={q.get('is_html')}, has_base64_img_in_content={'data:image' in q['content']}")
