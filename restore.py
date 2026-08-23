import requests
s = requests.Session()
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r.json().get('csrf_token','')
r2 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_update','id':'5','filename':'高一英语必备词汇.docx','education_level':'junior','subject':'','description':'','csrf_token':csrf})
print('Restore:', r2.json()['success'])
