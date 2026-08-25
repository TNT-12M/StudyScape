import requests, json
s = requests.Session()
r0 = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r0.json().get('csrf_token','')

r = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'list_questions',
    'education_level': 'junior',
    'subject': '道德与法治',
    'csrf_token': csrf
}, timeout=15)
d = r.json()
print('data keys:', list(d['data'].keys()))
for q in d['data']['items'][:5]:
    print(f"  id={q['id']} is_html={q.get('is_html')} content starts={repr(q.get('content','')[:80])}")
