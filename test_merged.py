import requests
s = requests.Session()
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
j = r.json()
csrf = j.get('csrf_token','')
print(f'Login: {j["success"]}, csrf set: {csrf != ""}')

# Test list_questions with scope=manage_all and empty education_level (what admin sends)
r2 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'list_questions',
    'scope':'manage_all',
    'page':'1',
    'page_size':'10',
    'subject':'',
    'education_level':'',
    'qtype':'',
    'question_type':'',
    'keyword':'',
    'csrf_token':csrf
})
j2 = r2.json()
print(f'list_questions scope=manage_all: success={j2.get("success")}, msg={j2.get("message")}, items={len(j2.get("data",{}).get("items",[])) if j2.get("success") else 0}')
