import requests
s = requests.Session()
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r.json().get('csrf_token','')

for level in ['', 'junior', 'senior']:
    r2 = s.post('http://127.0.0.1:8080/api.php', data={
        'action':'list_questions','scope':'manage_all','page':'1','page_size':'10',
        'subject':'','education_level':level,'qtype':'','question_type':'','keyword':'','csrf_token':csrf
    })
    j2 = r2.json()
    items = j2.get('data',{}).get('items',[]) if j2.get('success') else []
    levels = set(i['education_level'] for i in items)
    print(f'level="{level}": success={j2["success"]}, total_ret={j2.get("data",{}).get("total")}, items={len(items)}, levels_in_page={levels}')
