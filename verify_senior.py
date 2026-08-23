import requests, json
s = requests.Session()
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r.json().get('csrf_token','')

r2 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'list_questions','scope':'manage_all','page':'1','page_size':'20',
    'subject':'','education_level':'senior','csrf_token':csrf
})
j2 = r2.json()
print('success:', j2['success'], 'total:', j2['data']['total'], 'items:', len(j2['data']['items']))
for idx, it in enumerate(j2['data']['items']):
    print(f"  [{idx}] id={it['id']} subject={it['subject']} actual_education_level_DB={it['education_level']} qtype={it.get('type','')} category={it.get('category','')}")
