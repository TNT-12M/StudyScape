import requests
s = requests.Session()
r0 = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r0.json().get('csrf_token','')
print('csrf:', csrf[:20])

# Get question id=137
r = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'get_question',
    'id': 137,
    'csrf_token': csrf
}, timeout=15)
d = r.json()
q = d['data']['question']
print('is_html:', q.get('is_html'))
print('content type:', type(q.get('content')).__name__)
print('content starts:', repr(q.get('content','')[:200]))
print('content has img tag:', '<img' in (q.get('content') or ''))
print('content has center tag:', '<center>' in (q.get('content') or ''))
print()
# Print all keys
print('All keys:', list(q.keys()))
