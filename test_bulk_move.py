import requests, json
s = requests.Session()
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r.json().get('csrf_token','')
print('Login:', r.json()['success'])

# Pick 2 junior questions
r2 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'list_questions','scope':'manage_all','page':'1','page_size':'5',
    'subject':'','education_level':'junior','qtype':'','question_type':'','keyword':'','csrf_token':csrf
})
items = r2.json()['data']['items']
ids = [i['id'] for i in items[:2]]
print('Moving ids:', ids, 'from junior → senior')

r3 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'bulk_move_questions',
    'ids': json.dumps(ids),
    'education_level': 'senior',
    'csrf_token': csrf
})
print('bulk_move:', r3.json())

# Verify
r4 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'list_questions','scope':'manage_all','page':'1','page_size':'5',
    'subject':'','education_level':'junior','csrf_token':csrf
})
print('junior total after:', r4.json()['data']['total'])

r5 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'list_questions','scope':'manage_all','page':'1','page_size':'5',
    'subject':'','education_level':'senior','csrf_token':csrf
})
print('senior total after:', r5.json()['data']['total'])

# Move back
r6 = s.post('http://127.0.0.1:8080/api.php', data={
    'action':'bulk_move_questions',
    'ids': json.dumps(ids),
    'education_level': 'junior',
    'csrf_token': s.cookies.get_dict().get('csrf_token', csrf)
})
print('move back:', r6.json())
